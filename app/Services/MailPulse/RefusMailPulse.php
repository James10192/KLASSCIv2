<?php

namespace App\Services\MailPulse;

/**
 * Les familles de refus du transport MailPulse, partagees par tous les
 * appelants (convocations, verifications, synchronisation) : une seule liste,
 * pour que tous s'arretent sur les memes pannes.
 */
final class RefusMailPulse
{
    /**
     * Refus qui tiennent a la CONFIGURATION de l'instance, pas au destinataire :
     * ils frapperont tous les appels suivants a l'identique.
     */
    public const CONFIGURATION = [
        'disabled', 'missing_api_key', 'auth_failed',
        'endpoint_not_found', 'endpoint_not_supported', 'invalid_dispatch_contract',
    ];

    /** Pannes passageres du service : reessayer plus tard, pas maintenant. */
    public const PASSAGERS = [
        'connection_failed', 'request_timeout', 'rate_limited', 'provider_unavailable',
    ];

    public static function bloquant(?string $code): bool
    {
        return in_array($code, self::CONFIGURATION, true) || in_array($code, self::PASSAGERS, true);
    }
}
