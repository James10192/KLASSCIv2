<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Repartit un versement sur les frais qu'il couvre reellement.
 *
 * Un paiement ne portait qu'une seule categorie. L'etudiant qui reglait
 * plusieurs frais d'un geste voyait donc tout atterrir sur celle que le caissier
 * avait choisie — et le calcul du restant, qui fait `max(0, du - paye)` par
 * categorie, ECRETAIT l'excedent. Sur ISLG, 105 000 F d'un etudiant sont ainsi
 * devenus invisibles : ni imputes, ni signales.
 *
 * On ne cree AUCUN paiement : on dit seulement ou l'argent deja encaisse est
 * alle. Le frais que le caissier avait designe est servi en premier — c'est
 * l'intention explicite du versement — puis les autres dans l'ordre ou ils sont
 * dus. On n'alloue jamais plus que ce qu'un frais reclame.
 *
 * Quand tous les frais sont soldes et qu'il reste de l'argent, le surplus
 * demeure sur la categorie d'origine : c'est une avance, elle appartient a
 * l'etudiant et n'a pas a etre repartie sur des dettes qui n'existent pas.
 */
class RepartitionTropPercu
{
    /**
     * @return array{inscriptions: int, paiements: int, allocations: int, lignes: array, applique: bool}
     */
    public function executer(bool $appliquer = false, ?int $inscriptionId = null, ?int $anneeId = null): array
    {
        $inscriptions = ESBTPInscription::query()
            ->when($inscriptionId, fn ($q) => $q->where('id', $inscriptionId))
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when(! $inscriptionId, fn ($q) => $q->where('status', 'active'))
            ->with('etudiant')
            ->get();

        $lignes = [];
        $aEcrire = [];

        foreach ($inscriptions as $inscription) {
            foreach ($this->planifier($inscription) as $entree) {
                $lignes[] = $entree['ligne'];

                foreach ($entree['allocations'] as $allocation) {
                    $aEcrire[] = $allocation;
                }
            }
        }

        if (! $appliquer || $aEcrire === []) {
            return $this->resultat($lignes, count($aEcrire), false);
        }

        DB::transaction(function () use ($aEcrire): void {
            foreach ($aEcrire as $a) {
                ESBTPPaiementAllocation::updateOrCreate(
                    ['paiement_id' => $a['paiement_id'], 'frais_category_id' => $a['frais_category_id']],
                    ['montant' => $a['montant']]
                );
            }
        });

        Log::warning('[frais] repartition de versements sur plusieurs frais', [
            'allocations' => count($aEcrire),
            'inscription_id' => $inscriptionId,
            'annee_id' => $anneeId,
        ]);

        return $this->resultat($lignes, count($aEcrire), true);
    }

    /**
     * @param  array<int, array>  $lignes
     */
    private function resultat(array $lignes, int $allocations, bool $applique): array
    {
        return [
            'inscriptions' => count(array_unique(array_column($lignes, 'inscription_id'))),
            'paiements' => count($lignes),
            'allocations' => $allocations,
            'lignes' => $lignes,
            'applique' => $applique,
        ];
    }

    /**
     * Ce qu'il faudrait ecrire pour cette inscription, sans rien ecrire.
     *
     * @return array<int, array{ligne: array, allocations: array}>
     */
    private function planifier(ESBTPInscription $inscription): array
    {
        $paiements = ESBTPPaiement::query()
            ->where('inscription_id', $inscription->id)
            ->valides()
            ->encaissements()
            ->whereDoesntHave('allocations')
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        if ($paiements->isEmpty()) {
            return [];
        }

        $reste = $this->resteParCategorie($inscription);

        if ($reste === []) {
            return [];
        }

        $sorties = [];

        foreach ($paiements as $paiement) {
            $allocations = $this->repartirUnVersement($paiement, $reste);

            if ($allocations === []) {
                continue;
            }

            $etudiant = $inscription->etudiant;

            $sorties[] = [
                'ligne' => [
                    'inscription_id' => $inscription->id,
                    'paiement_id' => $paiement->id,
                    'numero_recu' => $paiement->numero_recu,
                    'etudiant' => $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : null,
                    'matricule' => $etudiant->matricule ?? null,
                    'montant' => (float) $paiement->montant,
                    'reparti_sur' => count($allocations),
                    'details' => $allocations,
                ],
                'allocations' => $allocations,
            ];
        }

        return $sorties;
    }

    /**
     * Ce que chaque frais reclame encore, une fois deduit ce qui lui est deja
     * alloue par des versements anterieurs.
     *
     * @return array<int, float>
     */
    private function resteParCategorie(ESBTPInscription $inscription): array
    {
        $dejaAlloue = ESBTPPaiementAllocation::query()
            ->whereIn('paiement_id', ESBTPPaiement::query()
                ->where('inscription_id', $inscription->id)
                ->valides()
                ->encaissements()
                ->select('id'))
            ->groupBy('frais_category_id')
            ->selectRaw('frais_category_id, SUM(montant) as total')
            ->pluck('total', 'frais_category_id');

        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($s) => $s->subscribed_at ?? $s->created_at)
            ->values();

        $reste = [];

        foreach ($souscriptions as $sub) {
            $du = (float) $sub->chargedAmount() - (float) ($dejaAlloue[$sub->frais_category_id] ?? 0);

            if ($du > 0.009) {
                $reste[$sub->frais_category_id] = $du;
            }
        }

        return $reste;
    }

    /**
     * Repartit UN versement, et met a jour ce qui reste du.
     *
     * Rend un tableau vide quand la repartition ne dirait rien de plus que le
     * paiement lui-meme — un seul frais servi, pour la totalite du versement,
     * sur la categorie que le paiement porte deja.
     *
     * @param  array<int, float>  $reste
     * @return array<int, array{paiement_id: int, frais_category_id: int, montant: float}>
     */
    private function repartirUnVersement(ESBTPPaiement $paiement, array &$reste): array
    {
        $aRepartir = (float) $paiement->montant;
        $categorieDuPaiement = (int) $paiement->frais_category_id;
        $allocations = [];

        // Le frais que le caissier a designe passe en premier : c'est
        // l'intention explicite du versement, elle prime sur l'ordre d'echeance.
        $ordre = array_keys($reste);

        if (isset($reste[$categorieDuPaiement])) {
            $ordre = array_merge(
                [$categorieDuPaiement],
                array_values(array_diff($ordre, [$categorieDuPaiement]))
            );
        }

        foreach ($ordre as $categoryId) {
            if ($aRepartir <= 0.009) {
                break;
            }

            $part = min($aRepartir, $reste[$categoryId]);

            if ($part <= 0.009) {
                continue;
            }

            $allocations[$categoryId] = round(($allocations[$categoryId] ?? 0) + $part, 2);
            $reste[$categoryId] = round($reste[$categoryId] - $part, 2);
            $aRepartir = round($aRepartir - $part, 2);
        }

        // Tous les frais soldes et il reste de l'argent : c'est une avance. Elle
        // demeure sur la categorie d'origine, ou elle se trouve deja.
        if ($aRepartir > 0.009) {
            $allocations[$categorieDuPaiement] = round(($allocations[$categorieDuPaiement] ?? 0) + $aRepartir, 2);
        }

        // La repartition ne dit rien de plus que le paiement : on n'ecrit pas.
        if (count($allocations) === 1
            && array_key_first($allocations) === $categorieDuPaiement
            && abs($allocations[$categorieDuPaiement] - (float) $paiement->montant) < 0.01) {
            return [];
        }

        $sortie = [];

        foreach ($allocations as $categoryId => $montant) {
            $sortie[] = [
                'paiement_id' => (int) $paiement->id,
                'frais_category_id' => (int) $categoryId,
                'montant' => $montant,
            ];
        }

        return $sortie;
    }
}
