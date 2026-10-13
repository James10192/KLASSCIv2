<?php

namespace App\Services\MailPulse;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Admission control BEFORE reaching MailPulse / Evolution / Baileys.
 *
 * These numbers are local operational safety budgets, NOT "safe" WhatsApp
 * sending limits. MailPulse/Evolution must enforce its own shared queue limit.
 * Uses a shared cache lock for atomic reservation across Laravel workers.
 */
final class MailPulseWhatsAppThrottle
{
    public function reserve(array $message, string $apiKey, ?string $requestId): ?MailPulseResult
    {
        if (! (bool) config('services.mailpulse.whatsapp_pacing_enabled', false)) {
            return null;
        }

        $recipient = trim((string) ($message['recipient']['value'] ?? ''));
        if ($recipient === '' || $apiKey === '') {
            // Normal payload / credentials validation remains the client's job.
            return null;
        }

        $scope = substr(hash('sha256', (string) config('services.mailpulse.base_url').'|'.$apiKey), 0, 24);
        $destination = substr(hash('sha256', strtolower($recipient)), 0, 24);
        $prefix = "klassci:mailpulse:wa:{$scope}";
        $lockKey = "{$prefix}:reservation";
        $perMinute = max(1, min(60, (int) config('services.mailpulse.whatsapp_per_minute', 6)));
        $perHour = max($perMinute, min(1000, (int) config('services.mailpulse.whatsapp_per_hour', 120)));
        $perRecipientHour = max(1, min(60, (int) config('services.mailpulse.whatsapp_per_recipient_hour', 6)));
        $spacing = max(0, min(60, (int) config('services.mailpulse.whatsapp_min_spacing_seconds', 8)));

        try {
            // Never send if the shared cache is unavailable or another worker
            // is reserving the same WhatsApp sender. No busy-wait or sleep.
            $lock = Cache::lock($lockKey, 10);
            if (! $lock->get()) {
                return $this->defer($requestId, 3);
            }

            try {
                $minuteKey = "{$prefix}:minute";
                $hourKey = "{$prefix}:hour";
                $recipientKey = "{$prefix}:recipient:{$destination}:hour";
                $nextKey = "{$prefix}:next_at";
                $delay = max(
                    RateLimiter::tooManyAttempts($minuteKey, $perMinute) ? RateLimiter::availableIn($minuteKey) : 0,
                    RateLimiter::tooManyAttempts($hourKey, $perHour) ? RateLimiter::availableIn($hourKey) : 0,
                    RateLimiter::tooManyAttempts($recipientKey, $perRecipientHour) ? RateLimiter::availableIn($recipientKey) : 0,
                    max(0, (int) Cache::get($nextKey, 0) - now()->timestamp)
                );

                if ($delay > 0) {
                    return $this->defer($requestId, $delay);
                }

                RateLimiter::hit($minuteKey, 60);
                RateLimiter::hit($hourKey, 3600);
                RateLimiter::hit($recipientKey, 3600);
                if ($spacing > 0) {
                    Cache::put($nextKey, now()->timestamp + $spacing, $spacing + 5);
                }

                return null;
            } finally {
                $lock->release();
            }
        } catch (Throwable $exception) {
            // Fail closed: a cache outage must never cause unbounded sends.
            Log::warning('WhatsApp pacing unavailable: outbound traffic deferred', [
                'exception' => $exception::class,
            ]);

            return new MailPulseResult(
                false, 'whatsapp_pacing_unavailable', 503, $requestId, null,
                'whatsapp_pacing_unavailable', 'Le limiteur WhatsApp est momentanément indisponible.',
                'Vérifiez le cache partagé et reprenez les messages depuis l’outbox.', null, false, 60
            );
        }
    }

    private function defer(?string $requestId, int $seconds): MailPulseResult
    {
        return new MailPulseResult(
            false, 'whatsapp_pacing', 429, $requestId, null,
            'whatsapp_pacing', 'Envoi WhatsApp différé pour protéger le canal.',
            'Le message sera repris depuis la file de notifications.', null, false,
            max(1, $seconds)
        );
    }
}
