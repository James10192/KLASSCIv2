<?php

namespace App\Services\WhatsApp;

/**
 * Masquage PII dans logs/audit (Phase 18 Plan v4 — sécurité).
 *
 * Standard ARTCI + Meta TOS : ne JAMAIS persister en clair dans les logs
 * des téléphones complets, emails complets, tokens, ou contenus de messages.
 *
 * Convention KLASSCI :
 *  - Téléphones : `+225 07 ** ** ** 56` (masquer 6 chiffres centraux, garder préfixe + 2 derniers)
 *  - Emails : `m***@example.com` (masquer local-part sauf 1ère lettre)
 *  - Tokens : `***REDACTED***` (jamais affichés)
 *  - Contenus messages WhatsApp : tronqués à 30 chars + "..." (preview seulement)
 *
 * Usage typique dans les services WhatsApp :
 *   Log::info('Sending WhatsApp message', [
 *       'to' => PiiMasker::phone($phoneNumber),
 *       'preview' => PiiMasker::messagePreview($body),
 *   ]);
 */
class PiiMasker
{
    /**
     * Masque un numéro de téléphone international.
     *
     * Exemples :
     *  - "+2250707123456" → "+225 07 ** ** ** 56"
     *  - "0707123456"     → "07 ** ** ** 56"
     *  - null / invalid   → "***MASKED***"
     */
    public static function phone(?string $phone): string
    {
        if (empty($phone)) {
            return '***MASKED***';
        }

        // Nettoyer (garder chiffres + leading +)
        $clean = preg_replace('/[^0-9+]/', '', $phone);

        if (strlen($clean) < 8) {
            return '***MASKED***';
        }

        // Cas +225XXXXXXXXXX (CI) — format lisible avec espaces
        if (str_starts_with($clean, '+225') && strlen($clean) === 14) {
            $prefix = substr($clean, 0, 4); // +225
            $first2 = substr($clean, 4, 2); // 07
            $last2 = substr($clean, -2);    // 56
            return "{$prefix} {$first2} ** ** ** {$last2}";
        }

        // Cas générique : garder 4 premiers + 2 derniers, masquer le reste
        $prefix = substr($clean, 0, 4);
        $last2 = substr($clean, -2);
        $masked = str_repeat('*', max(0, strlen($clean) - 6));
        return "{$prefix}{$masked}{$last2}";
    }

    /**
     * Masque un email.
     *
     * Exemples :
     *  - "marcel@example.com" → "m***@example.com"
     *  - "ab@x.fr"            → "a***@x.fr"
     *  - "invalid"            → "***MASKED***"
     */
    public static function email(?string $email): string
    {
        if (empty($email) || ! str_contains($email, '@')) {
            return '***MASKED***';
        }

        [$local, $domain] = explode('@', $email, 2);

        if (strlen($local) === 0) {
            return '***MASKED***';
        }

        return mb_substr($local, 0, 1) . '***@' . $domain;
    }

    /**
     * Masque un token (toujours redacted complet).
     */
    public static function token(?string $token): string
    {
        return '***REDACTED***';
    }

    /**
     * Tronque le contenu d'un message à 30 chars + "..." pour preview log.
     * NE PERSISTE JAMAIS le contenu intégral d'un message WhatsApp/SMS dans
     * les logs — respect RGPD / loi 2013-450 CI sur la protection des données.
     */
    public static function messagePreview(?string $message): string
    {
        if (empty($message)) {
            return '(empty)';
        }

        $clean = trim($message);
        if (mb_strlen($clean) <= 30) {
            return $clean;
        }

        return mb_substr($clean, 0, 30) . '...';
    }

    /**
     * Masque un nom complet (utilisé dans audit log lifecycle credentials).
     * Garde initiales : "Marcel DJEDJE-LI PATRICK" → "M. D-L. P."
     */
    public static function name(?string $name): string
    {
        if (empty($name)) {
            return '***MASKED***';
        }

        $parts = preg_split('/\s+/', trim($name));
        $initials = array_map(
            fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)) . '.',
            $parts
        );

        return implode(' ', $initials);
    }

    /**
     * Helper bulk : applique le masquage à un array de context log.
     *
     * Mappe par clé :
     *  - 'phone', 'phone_number', 'to', 'from' → phone()
     *  - 'email', 'admin_email' → email()
     *  - 'access_token', 'token', 'api_token' → token()
     *  - 'message', 'body', 'content' → messagePreview()
     *  - 'name', 'admin_name' → name()
     */
    public static function maskContext(array $context): array
    {
        $phoneKeys = ['phone', 'phone_number', 'to', 'from', 'recipient'];
        $emailKeys = ['email', 'admin_email', 'recipient_email'];
        $tokenKeys = ['access_token', 'token', 'api_token', 'authorization', 'bearer'];
        $messageKeys = ['message', 'body', 'content', 'text'];
        $nameKeys = ['name', 'admin_name', 'full_name'];

        return collect($context)->map(function ($value, $key) use ($phoneKeys, $emailKeys, $tokenKeys, $messageKeys, $nameKeys) {
            if (! is_string($value)) {
                return $value;
            }

            $keyLower = mb_strtolower((string) $key);

            if (in_array($keyLower, $phoneKeys, true)) return self::phone($value);
            if (in_array($keyLower, $emailKeys, true)) return self::email($value);
            if (in_array($keyLower, $tokenKeys, true)) return self::token($value);
            if (in_array($keyLower, $messageKeys, true)) return self::messagePreview($value);
            if (in_array($keyLower, $nameKeys, true)) return self::name($value);

            return $value;
        })->all();
    }
}
