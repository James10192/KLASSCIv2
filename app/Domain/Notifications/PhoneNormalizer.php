<?php

namespace App\Domain\Notifications;

/**
 * Normalise les numéros de téléphone ivoiriens vers le format E.164 (+225XXXXXXXXXX).
 *
 * Préfixes mobiles CI (2026) : 01/02/03 (Moov), 05/06 (Orange), 07/08/09 (MTN, Wave).
 * Tout numéro non conforme retourne null (pas d'envoi raté plus tard).
 *
 * Pure compute — pas de dépendance Laravel.
 */
final class PhoneNormalizer
{
    private const COUNTRY_CODE = '225';
    private const E164_PREFIX = '+225';
    private const MOBILE_PREFIX_REGEX = '/^(01|02|03|05|06|07|08|09)/';
    private const NATIONAL_LENGTH = 10;
    private const FULL_LENGTH = 13;

    /**
     * Tente de normaliser un numéro brut. Retourne null si invalide.
     */
    public static function toE164(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '00' . self::COUNTRY_CODE)) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === self::FULL_LENGTH && str_starts_with($digits, self::COUNTRY_CODE)) {
            $national = substr($digits, 3);
        } elseif (strlen($digits) === self::NATIONAL_LENGTH) {
            $national = $digits;
        } else {
            return null;
        }

        if (!preg_match(self::MOBILE_PREFIX_REGEX, $national)) {
            return null;
        }

        return self::E164_PREFIX . $national;
    }

    /**
     * Format E.164 sans le `+` (utile pour wa.me/22507XXXXXXXX).
     */
    public static function toWhatsAppId(?string $raw): ?string
    {
        $e164 = self::toE164($raw);
        return $e164 === null ? null : substr($e164, 1);
    }

    public static function isValid(?string $raw): bool
    {
        return self::toE164($raw) !== null;
    }
}
