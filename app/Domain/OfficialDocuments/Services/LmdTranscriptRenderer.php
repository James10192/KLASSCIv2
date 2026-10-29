<?php

namespace App\Domain\OfficialDocuments\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Rendu PDF du releve de notes, a partir du seul instantane.
 *
 * Memes options que JuryPvRenderer : pas de ressource distante, pas de PHP dans
 * le gabarit. Un document officiel ne doit dependre d'aucun reseau au moment ou
 * on le regenere.
 */
class LmdTranscriptRenderer
{
    /**
     * Le gabarit vient de l'INSTANTANE, jamais du reglage courant.
     *
     * Une ecole qui bascule sur le modele officiel ne doit pas voir changer la
     * mise en page des releves qu'elle a deja emis et signes. Un instantane
     * anterieur au choix ne porte pas la cle : il retombe sur le gabarit
     * historique, qui est bien celui avec lequel il a ete imprime.
     */
    public function render(array $snapshot, string $verificationCode): string
    {
        $gabarit = ($snapshot['issuance']['template_version'] ?? null) === LmdTranscriptSnapshotBuilder::MODELE_MESRS
            ? 'pdf.lmd-releve-notes-mesrs'
            : 'pdf.lmd-releve-notes';

        return Pdf::loadView($gabarit, compact('snapshot', 'verificationCode'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
            ])->output();
    }
}
