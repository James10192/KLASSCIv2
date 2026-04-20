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
            Log::warning('[SSO] GROUP_SSO_SHARED_SECRET is not configured — cross-app SSO disabled');
            return;
        }

        if (strlen($secret) < 32) {
            Log::critical('[SSO] GROUP_SSO_SHARED_SECRET is too short (' . strlen($secret) . ' chars, 32 required) — SSO tokens will be rejected');
            return;
        }
    }
}
