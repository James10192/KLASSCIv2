<?php

namespace App\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use App\Models\ESBTPParent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCodeIssuance;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use App\Services\MailPulse\MailPulseTenantContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ParentChatbotLinkCodeDeliveryService
{
    private const DELIVERY_LEASE_SECONDS = 300;
    private const MAX_DELIVERY_ATTEMPTS = 5;
    public function __construct(
        private ParentChatbotLinkService $links,
        private ParentChatbotPhoneNormalizer $phones,
        private ParentChatbotDispatcher $dispatcher,
        private MailPulseWorkflowPolicy $workflowPolicy,
    ) {}

    public function issueAndDeliver(
        ESBTPParent $parent,
        ?int $actorId = null,
        ?string $requestId = null,
    ): ParentChatbotLinkCodeIssuance {
        $requestId ??= MailPulseTenantContext::scopedIdentifier('parent-link-'.(string) Str::uuid());
        $plan = $this->prepareIssuance($parent, $actorId, $requestId);

        if ($plan['block'] !== null) {
            throw $this->blockedIssuanceException($plan['block']);
        }

        $issuance = $plan['issuance'];
        $claimed = $this->claimAttempt($issuance);
        if (! $claimed) {
            return $issuance->fresh() ?? $issuance;
        }

        return $this->dispatchAndComplete($claimed);
    }

    public function reconcilePending(int $limit = 50): int
    {
        $limit = max(1, min(200, $limit));
        $now = now();
        $processed = 0;

        ParentChatbotLinkCodeIssuance::query()
            ->whereIn('status', [
                ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
            ])
            ->whereNotNull('delivery_payload')
            ->where(function ($query) use ($now): void {
                $query->where('delivery_payload_expires_at', '<=', $now)
                    ->orWhere('retry_expires_at', '<=', $now)
                    ->orWhere('attempt_count', '>=', self::MAX_DELIVERY_ATTEMPTS);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('delivery_lease_expires_at')
                    ->orWhere('delivery_lease_expires_at', '<=', $now);
            })
            ->oldest('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (ParentChatbotLinkCodeIssuance $issuance) use (&$processed): void {
                if ($this->settleExpiredOrExhaustedIssuance($issuance)) {
                    $processed++;
                }
            });

        ParentChatbotLinkCodeIssuance::query()
            ->whereIn('status', [
                ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
            ])
            ->whereNotNull('delivery_payload')
            ->where('delivery_payload_expires_at', '>', $now)
            ->where('attempt_count', '<', self::MAX_DELIVERY_ATTEMPTS)
            ->where(fn ($query) => $query->whereNull('retry_expires_at')->orWhere('retry_expires_at', '>', $now))
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now))
            ->where(function ($query) use ($now): void {
                $query->whereNull('delivery_lease_expires_at')
                    ->orWhere('delivery_lease_expires_at', '<=', $now);
            })
            ->oldest('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (ParentChatbotLinkCodeIssuance $issuance) use (&$processed): void {
                $this->reconcileIssuance($issuance);
                $processed++;
            });

        return $processed;
    }

    /**
     * @return array{issuance: ParentChatbotLinkCodeIssuance, block: ?string}
     */
    private function prepareIssuance(ESBTPParent $parent, ?int $actorId, string $requestId): array
    {
        return DB::transaction(function () use ($parent, $actorId, $requestId): array {
            $parent = ESBTPParent::query()->whereKey($parent->id)->lockForUpdate()->firstOrFail();
            $existing = $this->existingIssuance($parent, $requestId);

            if ($existing) {
                return ['issuance' => $existing, 'block' => null];
            }

            $block = $this->blockReason($parent);
            if ($block !== null) {
                return $this->blockedIssuance($parent->id, $actorId, $requestId, $block);
            }

            $phone = $this->phones->normalize((string) $parent->telephone);
            if ($phone === null || ! $parent->pupilles()->exists()) {
                return $this->blockedIssuance($parent->id, $actorId, $requestId, 'invalid_parent_contact');
            }

            $liveIssuance = $this->liveIssuance($parent);

            if ($liveIssuance) {
                return ['issuance' => $liveIssuance, 'block' => null];
            }

            $issued = $this->links->issueCodeWithRecord($parent);
            $issuance = ParentChatbotLinkCodeIssuance::create([
                'parent_id' => $parent->id,
                'actor_id' => $actorId,
                'parent_chatbot_link_code_id' => $issued['linkCode']->id,
                'request_id' => $requestId,
                'status' => ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                'delivery_payload' => $this->encryptDeliveryPayload($phone, $issued['code']),
                'delivery_payload_expires_at' => $issued['linkCode']->expires_at,
                'next_attempt_at' => now(),
                'retry_expires_at' => $issued['linkCode']->expires_at,
            ]);

            return ['issuance' => $issuance, 'block' => null];
        });
    }

    private function claimAttempt(ParentChatbotLinkCodeIssuance $issuance): ?ParentChatbotLinkCodeIssuance
    {
        $token = (string) Str::uuid();
        $now = now();
        $claimed = ParentChatbotLinkCodeIssuance::query()
            ->whereKey($issuance->id)
            ->whereIn('status', [
                ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
            ])
            ->whereNotNull('delivery_payload')
            ->where('delivery_payload_expires_at', '>', $now)
            ->where('attempt_count', '<', self::MAX_DELIVERY_ATTEMPTS)
            ->where(fn ($query) => $query->whereNull('retry_expires_at')->orWhere('retry_expires_at', '>', $now))
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now))
            ->where(function ($query) use ($now): void {
                $query->whereNull('delivery_lease_expires_at')
                    ->orWhere('delivery_lease_expires_at', '<=', $now);
            })
            ->update([
                'attempt_count' => DB::raw('attempt_count + 1'),
                'attempted_at' => $now,
                'delivery_token' => $token,
                'delivery_started_at' => $now,
                'delivery_lease_expires_at' => $now->copy()->addSeconds(self::DELIVERY_LEASE_SECONDS),
                'next_attempt_at' => null,
                'updated_at' => $now,
            ]);

        if ($claimed !== 1) {
            return null;
        }

        return ParentChatbotLinkCodeIssuance::query()
            ->whereKey($issuance->id)
            ->where('delivery_token', $token)
            ->first();
    }

    private function dispatchAndComplete(ParentChatbotLinkCodeIssuance $issuance): ParentChatbotLinkCodeIssuance
    {
        $payload = $this->decryptDeliveryPayload($issuance);
        if ($payload === null) {
            $this->failDelivery($issuance, 'delivery_payload_unavailable');
            return $issuance->fresh() ?? $issuance;
        }

        $templateName = (string) config('services.mailpulse.parent_chatbot_link_template_name', '');
        $languageCode = (string) config('services.mailpulse.parent_chatbot_link_template_language', 'fr');
        if ($templateName === '' || $languageCode === '') {
            $this->failDelivery($issuance, 'link_template_not_configured');
            return $issuance->fresh() ?? $issuance;
        }

        try {
            $delivery = $this->dispatchTemplateIfAllowed($issuance, $payload, $templateName, $languageCode);
            if ($delivery['block'] !== null) {
                $this->failDelivery($issuance, $delivery['block']);

                return $issuance->fresh() ?? $issuance;
            }

            $outcome = $delivery['outcome'];
        } catch (ConnectionException) {
            $outcome = ParentChatbotDispatchOutcome::pendingReconciliation();
        } catch (\Throwable) {
            $outcome = ParentChatbotDispatchOutcome::failed();
            $errorCode = 'dispatch_exception';
        }

        if ($outcome->isAccepted()) {
            $this->completeDelivery($issuance, ParentChatbotLinkCodeDeliveryState::terminal(
                ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED,
                null,
                $outcome->commandId,
            ));
        } elseif ($outcome->isPendingReconciliation()) {
            $this->scheduleReconciliation($issuance, $outcome);
        } else {
            $this->completeDelivery($issuance, ParentChatbotLinkCodeDeliveryState::terminal(
                ParentChatbotLinkCodeIssuance::STATUS_FAILED,
                $errorCode ?? 'dispatch_failed',
                $issuance->provider_command_id,
            ));
        }

        return $issuance->fresh() ?? $issuance;
    }

    private function dispatchTemplateIfAllowed(
        ParentChatbotLinkCodeIssuance $issuance,
        array $payload,
        string $templateName,
        string $languageCode,
    ): array {
        return DB::transaction(function () use ($issuance, $payload, $templateName, $languageCode): array {
            ParentChatbotLink::query()
                ->where('parent_id', $issuance->parent_id)
                ->lockForUpdate()
                ->get(['id']);

            if (! $this->workflowPolicy->realWorkflowsEnabled()) {
                return ['outcome' => ParentChatbotDispatchOutcome::failed(), 'block' => 'workflows_disabled'];
            }

            $stopped = ParentChatbotLink::query()
                ->where('parent_id', $issuance->parent_id)
                ->where('status', ParentChatbotLink::STATUS_STOPPED)
                ->exists();
            if ($stopped) {
                return ['outcome' => ParentChatbotDispatchOutcome::failed(), 'block' => 'link_stopped'];
            }

            return [
                'outcome' => $this->dispatcher->dispatchTemplate(
                    $payload['phone'],
                    $templateName,
                    $languageCode,
                    [$payload['code']],
                    ParentChatbotIntent::Link,
                    $issuance->request_id,
                    $issuance->request_id,
                ),
                'block' => null,
            ];
        }, 3);
    }

    private function reconcileIssuance(ParentChatbotLinkCodeIssuance $issuance): ParentChatbotLinkCodeIssuance
    {
        $claimed = $this->claimAttempt($issuance);
        if (! $claimed) {
            return $issuance->fresh() ?? $issuance;
        }

        if ($this->isReplayBlocked($claimed)) {
            return $claimed->fresh() ?? $claimed;
        }

        return $this->dispatchAndComplete($claimed);
    }

    private function scheduleReconciliation(
        ParentChatbotLinkCodeIssuance $issuance,
        ParentChatbotDispatchOutcome $outcome,
    ): void
    {
        $now = now();
        if ($issuance->delivery_payload_expires_at?->lessThanOrEqualTo($now)
            || $issuance->retry_expires_at?->lessThanOrEqualTo($now)) {
            $this->completeDelivery($issuance, ParentChatbotLinkCodeDeliveryState::manual(
                $issuance,
                'submission_unknown_expired',
                $outcome->commandId,
            ));

            return;
        }

        if ($issuance->attempt_count >= self::MAX_DELIVERY_ATTEMPTS) {
            $this->completeDelivery($issuance, ParentChatbotLinkCodeDeliveryState::manual(
                $issuance,
                'submission_unknown_retry_exhausted',
                $outcome->commandId,
            ));

            return;
        }

        $this->completeDelivery($issuance, [
            'status' => ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
            'error_code' => 'submission_unknown',
            'provider_command_id' => $outcome->commandId ?? $issuance->provider_command_id,
            'next_attempt_at' => $now->copy()->addSeconds(ParentChatbotLinkCodeDeliveryState::backoffSeconds($issuance->attempt_count)),
            'delivery_token' => null,
            'delivery_started_at' => null,
            'delivery_lease_expires_at' => null,
        ]);
    }

    private function completeDelivery(ParentChatbotLinkCodeIssuance $issuance, array $attributes): bool
    {
        return ParentChatbotLinkCodeIssuance::query()
            ->whereKey($issuance->id)
            ->where('delivery_token', $issuance->delivery_token)
            ->whereIn('status', [
                ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
            ])
            ->update($attributes + ['updated_at' => now()]) === 1;
    }

    private function isReplayBlocked(ParentChatbotLinkCodeIssuance $issuance): bool
    {
        if (! $this->workflowPolicy->realWorkflowsEnabled()) {
            $this->failDelivery($issuance, 'workflows_disabled');
            return true;
        }

        $blocked = ParentChatbotLink::query()
            ->where('parent_id', $issuance->parent_id)
            ->where('status', ParentChatbotLink::STATUS_STOPPED)
            ->exists();

        if ($blocked) {
            $this->failDelivery($issuance, 'link_stopped');
            return true;
        }

        return false;
    }

    private function existingIssuance(ESBTPParent $parent, string $requestId): ?ParentChatbotLinkCodeIssuance
    {
        $issuance = ParentChatbotLinkCodeIssuance::query()->where('request_id', $requestId)->lockForUpdate()->first();

        if ($issuance && (int) $issuance->parent_id !== (int) $parent->id) {
            throw new InvalidArgumentException('Identifiant de demande de liaison invalide.');
        }

        return $issuance;
    }

    private function blockReason(ESBTPParent $parent): ?string
    {
        if (! $this->workflowPolicy->realWorkflowsEnabled()) {
            return 'workflows_disabled';
        }

        $blocked = ParentChatbotLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', ParentChatbotLink::STATUS_STOPPED)
            ->lockForUpdate()
            ->exists();

        return $blocked ? 'link_stopped' : null;
    }

    private function liveIssuance(ESBTPParent $parent): ?ParentChatbotLinkCodeIssuance
    {
        return ParentChatbotLinkCodeIssuance::query()
            ->where('parent_id', $parent->id)
            ->whereIn('status', [
                ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
                ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED,
                ParentChatbotLinkCodeIssuance::STATUS_MANUAL_RECONCILIATION,
            ])
            ->whereHas('linkCode', fn ($query) => $query->whereNull('consumed_at')->where('expires_at', '>', now()))
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    private function blockedIssuance(int $parentId, ?int $actorId, string $requestId, string $errorCode): array
    {
        return [
            'issuance' => ParentChatbotLinkCodeIssuance::create([
                'parent_id' => $parentId,
                'actor_id' => $actorId,
                'request_id' => $requestId,
                'status' => ParentChatbotLinkCodeIssuance::STATUS_BLOCKED,
                'failed_at' => now(),
                'error_code' => $errorCode,
            ]),
            'block' => $errorCode,
        ];
    }

    private function blockedIssuanceException(string $errorCode): \Throwable
    {
        return match ($errorCode) {
            'workflows_disabled' => new RuntimeException('Les workflows parents MailPulse ne sont pas activés.'),
            'link_stopped' => new InvalidArgumentException('Ce numéro a arrêté le chatbot.'),
            default => new InvalidArgumentException('Le tuteur doit avoir un téléphone enregistré et au moins un pupille.'),
        };
    }

    private function encryptDeliveryPayload(string $phone, string $code): string
    {
        return Crypt::encryptString(json_encode([
            'phone' => $phone,
            'code' => $code,
        ], JSON_THROW_ON_ERROR));
    }

    private function decryptDeliveryPayload(ParentChatbotLinkCodeIssuance $issuance): ?array
    {
        try {
            $decoded = json_decode(Crypt::decryptString((string) $issuance->delivery_payload), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded)
            && is_string($decoded['phone'] ?? null)
            && is_string($decoded['code'] ?? null)
            ? ['phone' => $decoded['phone'], 'code' => $decoded['code']]
            : null;
    }

    private function failDelivery(ParentChatbotLinkCodeIssuance $issuance, string $errorCode): void
    {
        $this->completeDelivery($issuance, ParentChatbotLinkCodeDeliveryState::terminal(
            ParentChatbotLinkCodeIssuance::STATUS_FAILED,
            $errorCode,
            $issuance->provider_command_id,
        ));
    }

    private function failIssuance(ParentChatbotLinkCodeIssuance $issuance, string $errorCode): bool
    {
        $now = now();

        return ParentChatbotLinkCodeIssuance::query()
            ->whereKey($issuance->id)
            ->whereIn('status', [
                ParentChatbotLinkCodeIssuance::STATUS_PENDING,
                ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
            ])
            ->where(function ($query) use ($now): void {
                $query->whereNull('delivery_lease_expires_at')
                    ->orWhere('delivery_lease_expires_at', '<=', $now);
            })
            ->update(ParentChatbotLinkCodeDeliveryState::terminal(
                ParentChatbotLinkCodeIssuance::STATUS_FAILED,
                $errorCode,
            ) + ['updated_at' => $now]) === 1;
    }

    private function settleExpiredOrExhaustedIssuance(ParentChatbotLinkCodeIssuance $issuance): bool
    {
        $now = now();
        $expired = $issuance->delivery_payload_expires_at?->lessThanOrEqualTo($now)
            || $issuance->retry_expires_at?->lessThanOrEqualTo($now);

        if ($issuance->status !== ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION) {
            return $this->failIssuance($issuance, $expired ? 'link_code_expired' : 'link_code_retry_exhausted');
        }

        return ParentChatbotLinkCodeIssuance::query()
            ->whereKey($issuance->id)
            ->where('status', ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION)
            ->where(fn ($query) => $query->whereNull('delivery_lease_expires_at')->orWhere('delivery_lease_expires_at', '<=', $now))
            ->update(ParentChatbotLinkCodeDeliveryState::manual(
                $issuance,
                $expired ? 'submission_unknown_expired' : 'submission_unknown_retry_exhausted',
            ) + ['updated_at' => $now]) === 1;
    }
}
