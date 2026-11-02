<?php

namespace App\Services\Annonces;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\DB;

/**
 * Diagnostic sans effets de bord. Une erreur fournisseur ne prouve pas à elle
 * seule qu'un renvoi serait sûr (bounce, suppression ou soumission inconnue).
 * La relance réelle reste bloquée tant que le journal d'essais et la nouvelle
 * référence idempotente par essai ne sont pas opérationnels.
 */
final class DiagnosticRelanceAnnonce
{
    /** @return array<string, int|bool|string> */
    public function pour(ESBTPAnnonce $annonce): array
    {
        $etat = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->getKey())
            ->select('status', DB::raw('COUNT(*) AS total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $motifs = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->getKey())
            ->where('status', 'failed')
            ->select('error_code', DB::raw('COUNT(*) AS total'))
            ->groupBy('error_code')
            ->get();

        $rebonds = 0;
        $supprimees = 0;
        $autres = 0;
        foreach ($motifs as $motif) {
            $code = strtolower((string) $motif->error_code);
            if (str_contains($code, 'bounc')) {
                $rebonds += (int) $motif->total;
            } elseif (str_contains($code, 'suppress') || str_contains($code, 'complain')) {
                $supprimees += (int) $motif->total;
            } else {
                $autres += (int) $motif->total;
            }
        }

        return [
            'total' => (int) $etat->sum(),
            'en_cours' => (int) ($etat['pending'] ?? 0)
                + (int) ($etat['processing'] ?? 0)
                + (int) ($etat['retrying'] ?? 0),
            'a_rapprocher' => (int) ($etat['awaiting_dispatch'] ?? 0)
                + (int) ($etat['submitted'] ?? 0)
                + (int) ($etat['delayed'] ?? 0),
            'delivres' => (int) ($etat['delivered'] ?? 0),
            'echoues' => $rebonds + $supprimees + $autres,
            'rebonds' => $rebonds,
            'supprimees' => $supprimees,
            'autres_echecs' => $autres,
            'relance_autorisee' => false,
            'motif_blocage' => 'Le journal immuable des essais et la validation de non-soumission par MailPulse sont requis avant de proposer une réémission. Un rebond ou une suppression ne peut pas être relancé automatiquement.',
        ];
    }
}
