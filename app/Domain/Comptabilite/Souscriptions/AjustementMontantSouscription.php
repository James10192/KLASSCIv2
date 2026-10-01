<?php

namespace App\Domain\Comptabilite\Souscriptions;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\EcheancierSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Change ce qu'un étudiant DOIT sur un frais : remise, exonération, ou dette
 * reprise d'un autre outil qui ne correspond pas à la réalité.
 *
 * Un seul chemin pour l'endpoint CLI et pour l'action de Nanan. Deux temps :
 * examiner() ne touche à rien et rend tout ce qu'il faut pour décider ;
 * appliquer() réécrit en vérifiant que rien n'a bougé entre-temps.
 *
 * Ce qui est refusé, et pourquoi :
 *  - descendre sous ce qui est déjà payé : l'excédent deviendrait un trop-perçu
 *    que personne n'a décidé de rembourser ;
 *  - un frais satisfait en nature : son montant n'est pas facturé, le changer
 *    ne change rien au solde et brouille l'historique ;
 *  - un motif de moins de dix caractères : c'est la seule trace du pourquoi.
 *
 * Le modèle est audité : ce changement est donc reconnu comme « retouché à la
 * main » et la régénération des frais ne l'écrase pas d'elle-même.
 */
final class AjustementMontantSouscription
{
    public const MOTIF_MIN = 10;

    public function __construct(private EcheancierSnapshotService $echeanciers)
    {
    }

    /**
     * @return array{
     *   refus: string[], avertissements: string[], choix: array<int, array{categorie_id: int, categorie: string, montant: float}>,
     *   inscription_id: int, souscription_id: ?int, categorie_id: ?int, categorie: ?string,
     *   avant: ?float, apres: float, deja_paye: ?float, solde_avant: ?float, solde_apres: ?float,
     *   etat: array
     * }
     */
    public function examiner(int $inscriptionId, ?int $categorieId, float $nouveauMontant): array
    {
        $examen = [
            'refus' => [], 'avertissements' => [], 'choix' => [],
            'inscription_id' => $inscriptionId, 'souscription_id' => null,
            'categorie_id' => $categorieId, 'categorie' => null,
            'avant' => null, 'apres' => round($nouveauMontant, 2), 'deja_paye' => null,
            'solde_avant' => null, 'solde_apres' => null, 'etat' => [],
        ];

        if (! ESBTPInscription::whereKey($inscriptionId)->exists()) {
            $examen['refus'][] = "Inscription {$inscriptionId} introuvable.";

            return $examen;
        }
        if ($nouveauMontant < 0) {
            $examen['refus'][] = 'Le nouveau montant ne peut pas être négatif.';
        }

        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->with('fraisCategory:id,name')
            ->get();
        $examen['choix'] = $souscriptions->map(fn (ESBTPFraisSubscription $s) => [
            'categorie_id' => (int) $s->frais_category_id,
            'categorie' => (string) ($s->fraisCategory->name ?? '#'.$s->frais_category_id),
            'montant' => (float) $s->amount,
        ])->values()->all();

        $souscription = $categorieId
            ? $souscriptions->firstWhere('frais_category_id', $categorieId)
            : ($souscriptions->count() === 1 ? $souscriptions->first() : null);

        if (! $souscription) {
            $examen['refus'][] = $souscriptions->isEmpty()
                ? "Cette inscription n'a aucun frais actif."
                : ($categorieId
                    ? "Ce frais n'est pas souscrit par cette inscription."
                    : 'Cette inscription a plusieurs frais : précisez lequel.');

            return $examen;
        }

        $paye = ESBTPPaiement::netPaidByCategory($inscriptionId, true);
        $dejaPaye = round((float) ($paye[$souscription->frais_category_id] ?? 0), 2);
        $soldeAvant = $this->soldeTotal($souscriptions, $paye);

        $examen['souscription_id'] = (int) $souscription->id;
        $examen['categorie_id'] = (int) $souscription->frais_category_id;
        $examen['categorie'] = (string) ($souscription->fraisCategory->name ?? '');
        $examen['avant'] = (float) $souscription->amount;
        $examen['deja_paye'] = $dejaPaye;
        $examen['solde_avant'] = $soldeAvant;
        $examen['solde_apres'] = round(
            $soldeAvant - max(0.0, (float) $souscription->amount - $dejaPaye) + max(0.0, $nouveauMontant - $dejaPaye),
            2
        );
        $examen['etat'] = [
            'id' => (int) $souscription->id,
            'amount' => (string) $souscription->amount,
            'updated_at' => $souscription->updated_at?->toIso8601String(),
        ];

        if ($souscription->satisfied_in_kind) {
            $examen['refus'][] = "Ce frais est réglé en nature : son montant n'est pas facturé, il n'y a rien à ajuster.";
        }
        if ($nouveauMontant < $dejaPaye) {
            $examen['refus'][] = sprintf(
                "L'étudiant a déjà versé %s FCFA sur ce frais : le montant dû ne peut pas descendre en dessous.",
                number_format($dejaPaye, 0, ',', ' ')
            );
        }
        if (round($nouveauMontant, 2) === round((float) $souscription->amount, 2)) {
            $examen['refus'][] = 'Le montant est déjà celui-ci.';
        }
        if ($nouveauMontant == 0.0) {
            $examen['avertissements'][] = "Un frais à 0 s'affiche « Montant non défini » sur l'état financier : il n'est plus réclamé.";
        }

        return $examen;
    }

    /**
     * @return array{souscription_id: int, avant: float, apres: float}
     */
    public function appliquer(array $examen, string $motif, int $auteurId): array
    {
        $motif = trim($motif);
        if (mb_strlen($motif) < self::MOTIF_MIN) {
            throw new AjustementRefuse('Le motif doit faire au moins '.self::MOTIF_MIN.' caractères.');
        }
        if ($examen['refus'] !== [] || ! $examen['souscription_id']) {
            throw new AjustementRefuse(implode(' ', $examen['refus']) ?: 'Rien à ajuster.');
        }

        $resultat = DB::transaction(function () use ($examen, $motif, $auteurId) {
            $souscription = ESBTPFraisSubscription::query()->lockForUpdate()->find($examen['souscription_id']);
            if (! $souscription
                || (string) $souscription->amount !== $examen['etat']['amount']
                || $souscription->updated_at?->toIso8601String() !== $examen['etat']['updated_at']) {
                throw new AjustementRefuse("Ce frais a changé depuis l'examen : relancez-le.");
            }

            // Le même plancher, relu sous verrou : un versement encaissé entre
            // l'examen et la validation l'aurait relevé.
            $paye = (float) (ESBTPPaiement::netPaidByCategory((int) $souscription->inscription_id, true)[$souscription->frais_category_id] ?? 0);
            if ($examen['apres'] < round($paye, 2)) {
                throw new AjustementRefuse('Un versement a été encaissé entre-temps : le nouveau montant passerait sous ce qui est payé.');
            }

            $trace = sprintf(
                '[%s] Montant dû %s → %s FCFA (utilisateur #%d) : %s',
                now()->format('Y-m-d H:i'),
                number_format((float) $souscription->amount, 0, ',', ' '),
                number_format($examen['apres'], 0, ',', ' '),
                $auteurId,
                $motif
            );
            $souscription->update([
                'amount' => $examen['apres'],
                'notes' => trim(($souscription->notes ? $souscription->notes."\n" : '').$trace),
            ]);

            return ['souscription_id' => (int) $souscription->id, 'avant' => (float) $examen['avant'], 'apres' => (float) $examen['apres']];
        });

        $inscription = ESBTPInscription::find($examen['inscription_id']);
        if ($inscription) {
            $this->echeanciers->refreshForInscription($inscription);
        }

        Log::info('[frais] montant de souscription ajuste', $resultat + [
            'inscription_id' => $examen['inscription_id'],
            'categorie_id' => $examen['categorie_id'],
            'par_utilisateur' => $auteurId,
            'motif' => $motif,
        ]);

        return $resultat;
    }

    private function soldeTotal($souscriptions, \ArrayAccess|array $paye): float
    {
        return round($souscriptions->sum(function (ESBTPFraisSubscription $s) use ($paye) {
            return max(0.0, $s->chargedAmount() - (float) ($paye[$s->frais_category_id] ?? 0));
        }), 2);
    }
}
