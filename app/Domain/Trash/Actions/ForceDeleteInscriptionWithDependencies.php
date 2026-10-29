<?php

namespace App\Domain\Trash\Actions;

use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression définitive d'une inscription déjà en corbeille, avec ses
 * dépendances.
 *
 * Deux raisons d'exister plutôt qu'un `forceDelete()` nu :
 *
 * 1. `esbtp_factures.inscription_id` est une clé étrangère RESTRICT : sans
 *    purge préalable des factures, la base refuse la suppression et renvoie
 *    une erreur SQL à l'écran. Les `esbtp_facture_details` suivent en cascade
 *    au niveau base.
 * 2. `esbtp_paiements.inscription_id` est au contraire une clé ON DELETE
 *    CASCADE : sans garde applicative, supprimer l'inscription emporterait
 *    SILENCIEUSEMENT des encaissements validés. C'est la garde OHADA, la même
 *    que celle de {@see ForceDeleteEtudiantWithDependencies}.
 *
 * Toutes les autres clés étrangères vers `esbtp_inscriptions` sont en CASCADE
 * ou en SET NULL : elles se règlent seules.
 */
class ForceDeleteInscriptionWithDependencies
{
    /**
     * @return array{inscription_id:int, inscription_label:string, factures_deleted:int,
     *               paiements_cascade:int, frais_subscriptions_cascade:int}
     *
     * @throws \DomainException                                        si des paiements validés actifs subsistent
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException    si l'inscription n'est pas en corbeille
     */
    public function execute(int $inscriptionId, User $user): array
    {
        $inscription = ESBTPInscription::onlyTrashed()->findOrFail($inscriptionId);
        $label = $this->libelle($inscription);

        // ─── Garde OHADA : encaissements validés actifs (non contournable) ───
        $paiementsValidesActifs = DB::table('esbtp_paiements')
            ->where('inscription_id', $inscriptionId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['validé', 'valide', 'validated'])
            ->count();

        if ($paiementsValidesActifs > 0) {
            throw new \DomainException(
                "Suppression bloquée : {$paiementsValidesActifs} versement(s) validé(s) sont rattachés à cette inscription. "
                ."Ces encaissements sont confirmés et seraient effacés avec elle (intégrité comptable — non contournable). "
                ."Annulez-les ou supprimez-les d'abord depuis la fiche de versement."
            );
        }

        $facturesDeleted = 0;
        $paiementsCascade = 0;
        $fraisSubsCascade = 0;

        DB::transaction(function () use (
            $inscription, $inscriptionId, $user, $label,
            &$facturesDeleted, &$paiementsCascade, &$fraisSubsCascade
        ) {
            // Ce que la base emportera d'elle-même — compté avant, pour le journal.
            $paiementsCascade = DB::table('esbtp_paiements')
                ->where('inscription_id', $inscriptionId)
                ->count();

            if (Schema::hasTable('esbtp_frais_subscriptions')) {
                $fraisSubsCascade = DB::table('esbtp_frais_subscriptions')
                    ->where('inscription_id', $inscriptionId)
                    ->count();
            }

            // Les factures, elles, bloquent : on les retire explicitement.
            if (Schema::hasTable('esbtp_factures')) {
                $facturesDeleted = DB::table('esbtp_factures')
                    ->where('inscription_id', $inscriptionId)
                    ->delete();
            }

            $inscription->forceDelete();

            Log::warning('FORCE_DELETE_INSCRIPTION', [
                'inscription_id' => $inscriptionId,
                'inscription_label' => $label,
                'performed_by_user_id' => $user->id,
                'performed_by_name' => $user->name,
                'factures_force_deleted' => $facturesDeleted,
                'paiements_cascade_db' => $paiementsCascade,
                'frais_subscriptions_cascade_db' => $fraisSubsCascade,
                'timestamp' => now()->toIso8601String(),
            ]);
        });

        return [
            'inscription_id' => $inscriptionId,
            'inscription_label' => $label,
            'factures_deleted' => $facturesDeleted,
            'paiements_cascade' => $paiementsCascade,
            'frais_subscriptions_cascade' => $fraisSubsCascade,
        ];
    }

    private function libelle(ESBTPInscription $inscription): string
    {
        $label = 'Inscription #'.$inscription->id;

        $etudiant = $inscription->etudiant_id
            ? \App\Models\ESBTPEtudiant::withTrashed()->find($inscription->etudiant_id)
            : null;

        if ($etudiant) {
            $nom = trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));
            if ($nom !== '') {
                $label .= ' — '.$nom;
            }
        }

        return $label;
    }
}
