<?php

namespace App\Services;

use App\Support\CodeInstance;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
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
 * une fois `config:cache` lancé en production. Le code d'établissement passe
 * par CodeInstance, qui traite la valeur de repli « default » comme une absence.
 *
 * Deux échecs sont journalisés au lieu de passer en silence :
 *  - configuration absente : l'appel est sauté, avertissement au plus une fois
 *    par jour et par instance (Cache::add, partagé entre processus) ;
 *  - réponse du master en erreur (401 jeton, 404 code…) : avertissement à
 *    chaque échec, avec le statut HTTP.
 */
class GroupCacheInvalidator
{
    private const CLE_AVERTISSEMENT = 'group_cache_invalidator.configuration_manquante';

    public function invalidate(string $trigger = 'unknown'): void
    {
        $masterUrl = config('services.master.api_url');
        $tenantToken = config('services.master.api_token');

        $manquants = array_keys(array_filter([
            'services.master.api_url (MASTER_API_URL)' => ! $masterUrl,
            'services.master.api_token (MASTER_API_TOKEN)' => ! $tenantToken,
            'app.tenant_code (TENANT_CODE)' => CodeInstance::resoudre() === null,
        ]));

        if ($manquants !== []) {
            if (Cache::add(self::CLE_AVERTISSEMENT, true, now()->addDay())) {
                Log::warning('GroupCacheInvalidator : invalidation du cache groupe ignorée, configuration master absente.', [
                    'manquants' => $manquants,
                    'trigger' => $trigger,
                ]);
            }

            return;
        }

        // Le master connaît le code en minuscules (esbtp-yakro) : CodeInstance le
        // met en majuscules pour les pièces officielles, il ne sert donc qu'au contrôle.
        $tenantCode = strtolower(trim((string) config('app.tenant_code')));
        $url = rtrim($masterUrl, '/') . "/tenants/{$tenantCode}/cache/invalidate";

        dispatch(function () use ($url, $tenantToken, $trigger) {
            try {
                Http::withToken($tenantToken)
                    ->connectTimeout(2)
                    ->timeout(3)
                    ->acceptJson()
                    ->post($url, ['trigger' => $trigger])
                    ->throw();
            } catch (\Exception $e) {
                Log::warning("GroupCacheInvalidator failed (after response): {$e->getMessage()}", [
                    'url' => $url,
                    'status' => $e instanceof RequestException ? $e->response->status() : null,
                    'trigger' => $trigger,
                ]);
            }
        })->afterResponse();
    }
}
