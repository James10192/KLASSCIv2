<?php

namespace App\Services\Paiements;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use Illuminate\Support\Collection;

class EtatRecuPaiement
{
    /**
     * @return array{
     *     lignes: Collection,
     *     reste: float,
     *     totalDu: float,
     *     totalVerse: float,
     *     versementsAvant: Collection,
     *     versementsApres: Collection,
     *     affectationLabel: string
     * }
     */
    public function construire(ESBTPPaiement $paiement): array
    {
        $inscriptionId = (int) $paiement->inscription_id;

        $subscriptions = ESBTPFraisSubscription::where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get();

        // Le recu doit voir l'argent DEJA ENTRE, y compris un versement encore
        // en attente : le caissier imprime souvent avant la validation.
        $payeParCategorie = ESBTPPaiement::netPaidByCategory($inscriptionId, true);

        // La categorie propre est ajoutee en plus de la ventilation : le recu
        // met en avant tout ce que ce versement touche, y compris le frais que
        // le caissier avait designe s'il ne figure dans aucune allocation.
        $categoriesDeCeVersement = $paiement->ventilation()
            ->pluck('frais_id')
            ->push($paiement->frais_category_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $lignes = $subscriptions->map(function ($sub) use ($payeParCategorie, $categoriesDeCeVersement) {
            $due = $sub->chargedAmount();
            $paye = (float) ($payeParCategorie[$sub->frais_category_id] ?? 0);
            $inKind = (bool) $sub->satisfied_in_kind;

            return [
                'name' => $sub->fraisCategory->name ?? 'N/A',
                'restant' => max(0.0, $due - $paye),
                'in_kind' => $inKind,
                'non_configure' => $sub->montantNonDefini(),
                'checked' => $sub->estSolde($paye),
                'current' => $categoriesDeCeVersement->contains((int) $sub->frais_category_id),
            ];
        })->values();

        // Le reste du recu est le SOLDE GLOBAL : ce que les frais reclament,
        // moins TOUT ce qui a ete encaisse sur l'inscription.
        //
        // Sommer les restes par frais (`max(0, du - paye)` categorie par
        // categorie) avalait l'argent qui n'avait pas atterri sur une
        // souscription — categorie absente, montant encore a definir, ou
        // ventilation qui n'a pas suivi le versement. Deux paiements de
        // 40 000 et 60 000 pouvaient ainsi laisser le « reste a payer »
        // identique sur les deux recus, comme s'ils n'avaient pas eu lieu.
        $totalDu = (float) $subscriptions->sum(fn ($sub) => $sub->chargedAmount());
        $totalVerse = (float) ESBTPPaiement::query()
            ->where('inscription_id', $inscriptionId)
            ->whereIn('status', ['validé', 'en_attente'])
            ->encaissements()
            ->horsReliquat()
            ->sum('montant');
        $avoirs = (float) ESBTPPaiement::query()
            ->where('inscription_id', $inscriptionId)
            ->valides()
            ->avoires()
            ->sum('montant');
        $reliquats = (float) ESBTPReliquatDetail::where('inscription_destination_id', $inscriptionId)
            ->actifs()
            ->get()
            ->sum(fn ($r) => $r->solde_restant);
        $netVerse = max(0.0, $totalVerse - $avoirs);
        $reste = self::soldeGlobal($totalDu, $netVerse, $reliquats);

        $autres = ESBTPPaiement::encaissements()
            ->where('inscription_id', $inscriptionId)
            ->where('id', '!=', $paiement->id)
            ->whereIn('status', ['validé', 'en_attente'])
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get(['id', 'date_paiement', 'montant', 'numero_recu', 'status']);

        $classes = $this->classerVersements($autres, $paiement);

        return [
            'lignes' => $lignes,
            'reste' => $reste,
            'totalDu' => $totalDu,
            'totalVerse' => $netVerse,
            'versementsAvant' => $classes['avant'],
            'versementsApres' => $classes['apres'],
            'affectationLabel' => $paiement->inscription?->affectationStatusLabel() ?? '—',
        ];
    }

    public static function soldeGlobal(float $du, float $verse, float $reliquats = 0.0): float
    {
        return max(0.0, $du - $verse) + $reliquats;
    }

    /**
     * @param  Collection<int, ESBTPPaiement>  $autres
     * @return array{avant: Collection, apres: Collection}
     */
    public function classerVersements(Collection $autres, ESBTPPaiement $courant): array
    {
        $date = $courant->date_paiement;
        $id = (int) $courant->id;

        $estAvant = function (ESBTPPaiement $autre) use ($date, $id): bool {
            if ($date === null || $autre->date_paiement === null) {
                return $autre->id < $id;
            }

            if ($autre->date_paiement->lt($date)) {
                return true;
            }

            return $autre->date_paiement->equalTo($date) && $autre->id < $id;
        };

        return [
            'avant' => $autres->filter($estAvant)->values(),
            'apres' => $autres->reject($estAvant)->values(),
        ];
    }
}
