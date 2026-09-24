<?php

namespace App\Domain\Comptabilite\Paiements\Actions;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remet un versement supprimé, avec son inscription et son étudiant s'ils
 * avaient été supprimés en cascade.
 *
 * Le droit d'accomplir ce geste (`paiements.restore`) est vérifié par
 * l'appelant : la corbeille à l'écran, le CLI en ligne de commande.
 */
class RestaurerPaiement
{
    /**
     * @return array{inscription: bool, etudiant: bool}
     */
    public function execute(ESBTPPaiement $paiement, ?int $auteurId): array
    {
        $inscription = ESBTPInscription::withTrashed()->find($paiement->inscription_id);
        $etudiant = $inscription ? ESBTPEtudiant::withTrashed()->find($inscription->etudiant_id) : null;
        $cascade = ['inscription' => false, 'etudiant' => false];

        DB::transaction(function () use ($paiement, $inscription, $etudiant, &$cascade) {
            if ($etudiant && $etudiant->trashed()) {
                $etudiant->restore();
                $cascade['etudiant'] = true;
            }
            if ($inscription && $inscription->trashed()) {
                $inscription->restore();
                $cascade['inscription'] = true;
            }
            // Une ligne vivante ne porte pas de motif de suppression : la trace
            // reste au journal d'audit, pas sur le versement restauré.
            $paiement->forceFill(['deleted_by' => null, 'motif_suppression' => null]);
            $paiement->restore();
        });

        Log::info('Paiement restauré', [
            'paiement_id' => $paiement->id,
            'cascade' => $cascade,
            'restored_by' => $auteurId,
        ]);

        return $cascade;
    }
}
