<?php

namespace App\Domain\Comptabilite\Paiements\Actions;

use App\Models\ESBTPPaiement;
use App\Models\NotificationReminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Supprime un versement, avec son motif et son auteur.
 *
 * Le droit d'accomplir ce geste (`paiements.delete`) et les verrous de période
 * comptable et de réconciliation sont vérifiés par l'appelant : l'action ne
 * connaît que la règle qui vaut partout, motif obligatoire et trace conservée.
 *
 * Le motif et l'auteur sont écrits AVANT la suppression logique, pour que le
 * journal d'audit du versement les porte (événement « modifié ») en plus de
 * l'événement « supprimé » lui-même.
 */
class SupprimerPaiement
{
    public const MOTIF_MIN = 10;

    public function execute(ESBTPPaiement $paiement, User $auteur, string $motif): ESBTPPaiement
    {
        $motif = trim($motif);
        if (mb_strlen($motif) < self::MOTIF_MIN) {
            throw new \DomainException('Le motif de suppression doit compter au moins '.self::MOTIF_MIN.' caractères.');
        }

        $this->refuserSiDesEcrituresEnDependent($paiement);

        return DB::transaction(function () use ($paiement, $auteur, $motif) {
            $this->desactiverLesRappels($paiement);

            $paiement->forceFill([
                'deleted_by' => $auteur->id,
                'motif_suppression' => $motif,
            ])->save();

            $paiement->delete();

            Log::warning('[paiements] Versement supprimé', [
                'paiement_id' => $paiement->id,
                'numero_recu' => $paiement->numero_recu,
                'montant' => $paiement->montant,
                'inscription_id' => $paiement->inscription_id,
                'frais_category_id' => $paiement->frais_category_id,
                'deleted_by' => $auteur->id,
                'motif' => $motif,
            ]);

            return $paiement;
        });
    }

    /**
     * Deux versements ne se suppriment pas d'un geste, parce que d'autres
     * écritures reposent dessus et ne seraient pas défaites :
     * - un versement de reliquat validé a marqué une dette d'année antérieure
     *   comme réglée (`esbtp_reliquat_details.montant_regle`) ;
     * - un versement sur lequel un avoir a été émis en est le support.
     * Ceux-là se traitent par un avoir ou par une session de réconciliation.
     */
    private function refuserSiDesEcrituresEnDependent(ESBTPPaiement $paiement): void
    {
        if ($paiement->type_paiement === 'reliquat' && $paiement->reliquat_detail_id && $paiement->status === 'validé') {
            throw new \DomainException(
                'Ce versement a réglé un reliquat d\'une année antérieure : il ne se supprime pas. Émettez un avoir ou passez par une réconciliation.'
            );
        }

        if ($paiement->childAvoirs()->exists()) {
            throw new \DomainException(
                'Un avoir a été émis sur ce versement : supprimez ou annulez l\'avoir d\'abord.'
            );
        }
    }

    private function desactiverLesRappels(ESBTPPaiement $paiement): void
    {
        try {
            NotificationReminder::where('remindable_type', ESBTPPaiement::class)
                ->where('remindable_id', $paiement->id)
                ->get()
                ->each(fn (NotificationReminder $rappel) => $rappel->deactivate());
        } catch (\Throwable $e) {
            Log::warning('Impossible de désactiver le rappel du paiement avant suppression', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
