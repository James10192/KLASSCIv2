<?php

namespace App\Services\Annonces;

use App\Services\Emails\AnalyseurEmail;
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

    /** Domaine fabrique par KLASSCI, d'apres la liste centrale `DomainesSuspects`. */
    public static function estFactice(string $email): bool
    {
        $arobase = strrpos($email, '@');

        return $arobase !== false && app(AnalyseurEmail::class)
            ->estFactice(mb_strtolower(trim(substr($email, $arobase + 1))));
    }

    public static function estBloquee(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || self::estFactice($email)) {
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
