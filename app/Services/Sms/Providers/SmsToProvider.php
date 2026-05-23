<?php

namespace App\Services\Sms\Providers;

use App\Domain\Notifications\Contracts\SmsProviderInterface;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider SMS SMS.to — fallback global (Phase 9).
 *
 * Doc : https://sms.to/api
 * Pricing CI 2026 : ~7-8 FCFA/SMS.
 */
class SmsToProvider implements SmsProviderInterface
{
    private const API_URL = 'https://api.sms.to/sms/send';

    public function name(): string
    {
        return 'smsto';
    }

    public function isAvailable(): bool
    {
        return ! empty(SettingsHelper::get('sms.smsto.api_key'));
    }

    public function costPerMessageFcfa(): float
    {
        return (float) SettingsHelper::get('sms.smsto.cost_fcfa', 7.5);
    }

    public function send(string $phoneNumber, string $message): array
    {
        $apiKey = SettingsHelper::get('sms.smsto.api_key');
        $senderId = SettingsHelper::get('sms.smsto.sender_id', 'KLASSCI');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post(self::API_URL, [
                    'message' => $message,
                    'to' => $phoneNumber,
                    'sender_id' => $senderId,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                    'cost_fcfa' => $this->costPerMessageFcfa(),
                ];
            }

            return ['success' => false, 'error' => 'SMS.to HTTP ' . $response->status()];
        } catch (\Throwable $e) {
            Log::error('[sms-smsto] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
