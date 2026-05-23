<?php

namespace App\Services\Sms\Providers;

use App\Domain\Notifications\Contracts\SmsProviderInterface;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider SMS Beem Africa — populaire francophone Afrique de l'Ouest.
 *
 * Documentation : https://docs.beem.africa/v3/sms
 * Pricing CI 2026 : ~5-7 FCFA/SMS bulk.
 *
 * Settings tenant requis :
 *   - sms.beem.api_key
 *   - sms.beem.secret_key
 *   - sms.beem.sender_id (max 11 chars, ex: "KLASSCI")
 */
class BeemSmsProvider implements SmsProviderInterface
{
    private const API_URL = 'https://apisms.beem.africa/v1/send';

    public function name(): string
    {
        return 'beem';
    }

    public function isAvailable(): bool
    {
        return ! empty(SettingsHelper::get('sms.beem.api_key'))
            && ! empty(SettingsHelper::get('sms.beem.secret_key'));
    }

    public function costPerMessageFcfa(): float
    {
        return (float) SettingsHelper::get('sms.beem.cost_fcfa', 6.0);
    }

    public function send(string $phoneNumber, string $message): array
    {
        $apiKey = SettingsHelper::get('sms.beem.api_key');
        $secretKey = SettingsHelper::get('sms.beem.secret_key');
        $senderId = SettingsHelper::get('sms.beem.sender_id', 'KLASSCI');

        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

        try {
            $response = Http::withBasicAuth($apiKey, $secretKey)
                ->timeout(10)
                ->post(self::API_URL, [
                    'source_addr' => $senderId,
                    'schedule_time' => '',
                    'encoding' => 0,
                    'message' => $message,
                    'recipients' => [
                        ['recipient_id' => 1, 'dest_addr' => $cleanPhone],
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                return [
                    'success' => true,
                    'message_id' => $body['request_id'] ?? null,
                    'cost_fcfa' => $this->costPerMessageFcfa(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Beem HTTP ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('[sms-beem] Exception envoi', [
                'error' => $e->getMessage(),
                'phone' => $cleanPhone,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
