<?php

namespace App\Services\Sms\Providers;

use App\Domain\Notifications\Contracts\SmsProviderInterface;
use App\Helpers\SettingsHelper;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

/**
 * Orange CI SMS provider — wrapper de SmsService legacy (Phase 9 Plan v4).
 *
 * Réutilise SmsService existant (OAuth2 token + envoi via Orange Developer API)
 * en l'enveloppant dans le contract SmsProviderInterface pour intégration au
 * SmsDispatcher cascade.
 *
 * Settings tenant : sms.orange.cost_fcfa, ORANGE_CLIENT_ID, ORANGE_CLIENT_SECRET.
 * Pricing CI 2026 : ~6-7 FCFA/SMS bulk.
 */
class OrangeSmsProvider implements SmsProviderInterface
{
    public function __construct(protected SmsService $smsService)
    {
    }

    public function name(): string
    {
        return 'orange';
    }

    public function isAvailable(): bool
    {
        return ! empty(env('ORANGE_CLIENT_ID')) && ! empty(env('ORANGE_CLIENT_SECRET'));
    }

    public function costPerMessageFcfa(): float
    {
        return (float) SettingsHelper::get('sms.orange.cost_fcfa', 6.5);
    }

    public function send(string $phoneNumber, string $message): array
    {
        try {
            // Délégation au SmsService legacy (Phase 8b extraction future).
            // L'API SmsService::send retourne bool ou array selon implémentation —
            // on uniformise vers le contract SmsProviderInterface.
            $result = $this->smsService->send($phoneNumber, $message);

            if ($result === true || (is_array($result) && ($result['success'] ?? false))) {
                return [
                    'success' => true,
                    'message_id' => is_array($result) ? ($result['message_id'] ?? null) : null,
                    'cost_fcfa' => $this->costPerMessageFcfa(),
                ];
            }

            return ['success' => false, 'error' => 'Orange send returned falsy'];
        } catch (\Throwable $e) {
            Log::error('[sms-orange] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
