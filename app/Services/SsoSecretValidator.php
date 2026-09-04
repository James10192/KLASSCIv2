<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Validates GROUP_SSO_SHARED_SECRET presence and length at app boot to prevent
 * silent SSO failures in production (widgets generating disabled buttons because
 * config is missing, with no loud error).
 *
 * Called from AppServiceProvider::boot(). Logs critical if misconfigured.
 * In production we log but don't throw (SSO is optional — tenant app should still
 * function for direct logins). In local we log warning.
 */
class SsoSecretValidator
{
    public static function validate(): void
    {
        $secret = config('services.group_sso.secret') ?: env('GROUP_SSO_SHARED_SECRET');

        if (empty($secret)) {
            // validate() est appelee depuis AppServiceProvider::boot(), donc au
            // demarrage de CHAQUE requete. En PHP-FPM chaque requete est un processus :
            // l'avertissement s'ecrivait des milliers de fois par jour sur les
            // instances sans SSO, pour une information qui ne change jamais.
            self::avertirUneFoisParHeure('secret-absent', '[SSO] GROUP_SSO_SHARED_SECRET is not configured — cross-app SSO disabled');

            return;
        }

        if (strlen($secret) < 32) {
            Log::critical('[SSO] GROUP_SSO_SHARED_SECRET is too short (' . strlen($secret) . ' chars, 32 required) — SSO tokens will be rejected');
            return;
        }
    }

    /**
     * Un defaut de configuration se signale, il ne se martele pas.
     */
    private static function avertirUneFoisParHeure(string $cle, string $message): void
    {
        if (\Illuminate\Support\Facades\Cache::add('sso-avertissement-'.$cle, true, 3600)) {
            Log::warning($message);
        }
    }
}
