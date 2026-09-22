<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fire-and-forget notification to adminKlassci master that a tenant state change
 * occurred (paiement validated, inscription created), so the group portal cache
 * refreshes immediately instead of waiting for the 2-5min TTL.
 *
 * Runs after the response is sent (dispatch::afterResponse) so the HTTP call —
 * up to 5s if master is slow — never adds latency to the user-facing request.
 *
 * Configuration lue exclusivement via config() : services.master.api_url,
 * services.master.api_token, app.tenant_code. Aucun env() ici — il rend null
 * une fois `config:cache` lancé en production. Si l'une manque, l'appel est
 * sauté et un avertissement est journalisé une fois par processus : un saut
 * muet laissait le cache du portail groupe périmé sans que personne le sache.
 */
class GroupCacheInvalidator
{
    private static bool $configurationManquanteSignalee = false;

    public function invalidate(string $trigger = 'unknown'): void
    {
        $masterUrl = config('services.master.api_url');
        $tenantToken = config('services.master.api_token');
        $tenantCode = config('app.tenant_code');

        $manquants = array_keys(array_filter([
            'services.master.api_url (MASTER_API_URL)' => ! $masterUrl,
            'services.master.api_token (MASTER_API_TOKEN)' => ! $tenantToken,
            'app.tenant_code (TENANT_CODE)' => ! $tenantCode,
        ]));

        if ($manquants !== []) {
            $this->signalerConfigurationManquante($manquants, $trigger);

            return;
        }

        $url = rtrim($masterUrl, '/') . "/tenants/{$tenantCode}/cache/invalidate";

        dispatch(function () use ($url, $tenantToken, $trigger) {
            try {
                Http::withToken($tenantToken)
                    ->connectTimeout(2)
                    ->timeout(3)
                    ->acceptJson()
                    ->post($url, ['trigger' => $trigger]);
            } catch (\Exception $e) {
                Log::warning("GroupCacheInvalidator failed (after response): {$e->getMessage()}");
            }
        })->afterResponse();
    }

    private function signalerConfigurationManquante(array $manquants, string $trigger): void
    {
        if (self::$configurationManquanteSignalee) {
            return;
        }
        self::$configurationManquanteSignalee = true;

        Log::warning('GroupCacheInvalidator : invalidation du cache groupe ignorée, configuration master absente.', [
            'manquants' => $manquants,
            'trigger' => $trigger,
        ]);
    }
}
