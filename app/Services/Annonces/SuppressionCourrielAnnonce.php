<?php

namespace App\Services\Annonces;

use Illuminate\Support\Facades\DB;

/**
 * Blocage local conservateur pour les adresses ayant déjà produit un signal
 * terminal de rebond, de plainte ou de suppression sur cette instance.
 *
 * Ne remplace PAS la consultation des suppressions fournisseur : l'absence
 * d'antécédent dans KLASSCI n'est jamais une preuve d'acceptabilité.
 */
final class SuppressionCourrielAnnonce
{
    private const CODES = [
        'email_bounced', 'email_complained', 'email_suppressed',
        'bounced', 'complained', 'suppressed',
    ];

    public static function estBloquee(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return true;
        }

        return DB::table('esbtp_annonce_email_deliveries')
            ->where('email', $email)
            ->where('status', 'failed')
            ->whereIn('error_code', self::CODES)
            ->exists();
    }

    /** @return array<string, true> */
    public static function adressesBloquees(): array
    {
        $bloquees = [];
        DB::table('esbtp_annonce_email_deliveries')
            ->where('status', 'failed')
            ->whereIn('error_code', self::CODES)
            ->select('id', 'email')
            ->orderBy('id')
            ->chunkById(500, function ($lignes) use (&$bloquees): void {
                foreach ($lignes as $ligne) {
                    $bloquees[strtolower(trim((string) $ligne->email))] = true;
                }
            });

        return $bloquees;
    }
}
