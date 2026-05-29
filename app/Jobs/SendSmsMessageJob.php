<?php

namespace App\Jobs;

use App\Services\Sms\SmsDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job queue SMS (Phase 14 + 9 Plan v4) — équivalent SendWhatsAppMessageJob pour SMS.
 * Queue dédiée 'sms', retry exp 10s/30s/60s, cascade fallback providers via SmsDispatcher.
 */
class SendSmsMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
        public readonly ?int $parentNotificationLogId = null,
    ) {
        $this->onQueue('sms');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SmsDispatcher $dispatcher): void
    {
        $result = $dispatcher->send($this->phoneNumber, $this->message);

        if (! ($result['success'] ?? false)) {
            Log::warning('[sms-job] Send failed', [
                'phone' => $this->phoneNumber,
                'error' => $result['error'] ?? 'unknown',
                'attempted' => $result['attempted'] ?? [],
            ]);
            throw new \RuntimeException('SMS send failed: ' . ($result['error'] ?? 'all providers failed'));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[sms-job] Job échec définitif', [
            'phone' => $this->phoneNumber,
            'error' => $e->getMessage(),
        ]);
    }
}
