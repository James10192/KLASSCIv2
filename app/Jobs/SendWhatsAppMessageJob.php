<?php

namespace App\Jobs;

use App\Services\WhatsApp\CircuitBreaker;
use App\Services\WhatsApp\PerTenantRateLimiter;
use App\Services\WhatsApp\RateLimitExceededException;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job queue pour envoi asynchrone de messages WhatsApp (Phase 14 Plan v4).
 *
 * Queue dédiée 'whatsapp' pour isoler la charge des autres notifications.
 * Retry exponentiel : 10s, 30s, 60s avec max 3 tentatives.
 *
 * Intègre rate limiter + circuit breaker (Phase 4 hardening) :
 *  - Skip envoi si circuit OPEN
 *  - Skip + delay si rate limit tier Meta atteint
 *  - Release back to queue si erreur transitoire
 *
 * Dispatch depuis MultiChannelDispatcher ou directement depuis controllers/services.
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 2;
    public int $timeout = 30;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $notificationType,
        public readonly array $data,
        public readonly ?int $parentNotificationLogId = null,
    ) {
        $this->onQueue('whatsapp');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        WhatsAppService $whatsapp,
        PerTenantRateLimiter $rateLimiter,
        CircuitBreaker $breaker,
    ): void {
        $tenantCode = config('app.tenant_code') ?? env('TENANT_CODE', 'unknown');

        // 1. Circuit breaker check
        if ($breaker->isOpen($tenantCode)) {
            Log::info('[wa-job] Skip — circuit breaker OPEN', [
                'tenant' => $tenantCode,
                'type' => $this->notificationType,
            ]);
            $this->release(300); // retry après reset window 5 min
            return;
        }

        // 2. Rate limiter check
        try {
            $rateLimiter->check($tenantCode);
        } catch (RateLimitExceededException $e) {
            Log::warning('[wa-job] Rate limit, release queue', [
                'tenant' => $tenantCode,
                'message' => $e->getMessage(),
            ]);
            $this->release(3600); // retry dans 1h
            return;
        }

        // 3. Envoi via WhatsAppService
        $result = match ($this->notificationType) {
            'inscription' => $whatsapp->sendInscriptionNotification($this->phoneNumber, $this->data),
            'paiement_valide' => $whatsapp->sendPaiementValideNotification($this->phoneNumber, $this->data),
            'paiement_rejete' => $whatsapp->sendPaiementRejeteNotification($this->phoneNumber, $this->data),
            'absence' => $whatsapp->sendAbsenceNotification($this->phoneNumber, $this->data),
            'bulletin_publie' => $whatsapp->sendBulletinPublishedNotification($this->phoneNumber, $this->data),
            'notes_faibles' => $whatsapp->sendLowGradesNotification($this->phoneNumber, $this->data),
            default => false,
        };

        if ($result) {
            $rateLimiter->increment($tenantCode);
            $breaker->recordSuccess($tenantCode);
            return;
        }

        $breaker->recordFailure($tenantCode, "Failed type={$this->notificationType}");
        throw new \RuntimeException("WhatsApp send failed for type {$this->notificationType}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[wa-job] Job échec définitif après retries', [
            'type' => $this->notificationType,
            'phone' => $this->phoneNumber,
            'error' => $exception->getMessage(),
        ]);
    }
}
