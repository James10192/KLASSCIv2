<?php

namespace App\Services\Admissions;

use App\Models\AdmissionActivationDispatch;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\MailPulse\MailPulseResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Piste d'audit des tentatives d'invitation (et NON preuve de livraison).
 *
 * Ne conserve jamais le contenu du message, l'URL d'activation, le jeton,
 * le mot de passe, les coordonnées ou le texte libre fourni par MailPulse.
 * Réutilise la clé d'idempotence déjà remise au fournisseur.
 */
final class AdmissionActivationDispatchLog
{
    public function record(
        ESBTPCandidatureWorkflow $workflow,
        string $channel,
        string $requestId,
        ?MailPulseResult $result = null,
    ): void {
        if (! in_array($channel, ['email', 'whatsapp'], true) || $requestId === '') {
            return;
        }

        $state = $result?->dispatchState;
        $status = $result?->status === 'dry_run'
            ? 'simulated'
            : ($result?->isDispatchAccepted() === true
            ? 'accepted'
            : (in_array($state, ['pending', 'pending_reconciliation'], true) ? 'pending' : 'failed'));

        try {
            AdmissionActivationDispatch::query()->updateOrCreate(
                ['request_id' => $requestId],
                [
                    'workflow_id' => $workflow->id,
                    'channel' => $channel,
                    'status' => $status,
                    'provider_message_id' => $result?->id ? substr($result->id, 0, 191) : null,
                    'provider_dispatch_state' => $state && preg_match('/^[a-z_]+$/', $state)
                        ? substr($state, 0, 32) : null,
                    'http_status' => $result?->httpStatus,
                    'error_code' => $result === null ? 'transport_exception'
                        : ($result->isDispatchAccepted() ? null : self::safeError($result->errorCode ?? $result->status)),
                ]
            );
        } catch (\Throwable $exception) {
            // L'indisponibilité du monitoring ne bloque JAMAIS l'inscription
            // et n'engendre pas un deuxième envoi du lien sécurisé.
            Log::warning('Admission activation dispatch journal unavailable', [
                'workflow_id' => $workflow->id,
                'channel' => $channel,
                'exception' => $exception::class,
            ]);
        }
    }

    /** @return Collection<int, AdmissionActivationDispatch> */
    public function recent(int $workflowId): Collection
    {
        try {
            return AdmissionActivationDispatch::query()
                ->where('workflow_id', $workflowId)
                ->orderByDesc('id')
                ->limit(8)
                ->get(['id', 'workflow_id', 'channel', 'status', 'provider_dispatch_state', 'http_status', 'error_code', 'created_at']);
        } catch (\Throwable $exception) {
            Log::warning('Admission activation dispatch history unavailable', [
                'workflow_id' => $workflowId,
                'exception' => $exception::class,
            ]);

            return collect();
        }
    }

    /** @return array{available:bool,counts:array<string,int>,entries:Collection} */
    public function overview(): array
    {
        $empty = [
            'total' => 0, 'accepted' => 0, 'pending' => 0,
            'failed' => 0, 'simulated' => 0,
        ];
        $unavailable = ['available' => false, 'counts' => $empty, 'entries' => collect()];

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('admission_activation_dispatches')) {
                return $unavailable;
            }

            $base = AdmissionActivationDispatch::query()
                ->where('created_at', '>=', now()->subDays(7));

            $groups = (clone $base)->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')->all();

            $counts = [
                'total' => array_sum(array_map('intval', $groups)),
                'accepted' => (int) ($groups['accepted'] ?? 0),
                'pending' => (int) ($groups['pending'] ?? 0)
                    + (int) ($groups['queued'] ?? 0)
                    + (int) ($groups['processing'] ?? 0),
                'failed' => (int) ($groups['failed'] ?? 0),
                'simulated' => (int) ($groups['simulated'] ?? 0),
            ];

            $entries = (clone $base)->orderByDesc('id')->limit(20)
                ->get(['id', 'channel', 'status', 'created_at']);

            return ['available' => true, 'counts' => $counts, 'entries' => $entries];
        } catch (\Throwable $exception) {
            Log::warning('Admission activation dispatch overview unavailable', [
                'exception' => $exception::class,
            ]);

            return $unavailable;
        }
    }

    public static function label(string $status): string
    {
        return match ($status) {
            'accepted' => 'Accepté par MailPulse — remise non confirmée',
            'pending' => 'En attente du fournisseur',
            'queued' => 'En file sécurisée',
            'processing' => 'En cours de remise au fournisseur',
            'expired' => 'Lien expiré, envoi annulé',
            'simulated' => 'Simulation — aucun envoi réel',
            'failed' => 'Échec enregistré',
            default => 'État inconnu',
        };
    }

    private static function safeError(?string $error): ?string
    {
        if (! $error) {
            return null;
        }

        return substr(preg_replace('/[^a-z0-9_\-]/i', '', $error) ?? '', 0, 80);
    }
}
