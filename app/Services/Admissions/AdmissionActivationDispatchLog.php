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

    public static function label(string $status): string
    {
        return match ($status) {
            'accepted' => 'Accepté par MailPulse — remise non confirmée',
            'pending' => 'En attente du fournisseur',
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
