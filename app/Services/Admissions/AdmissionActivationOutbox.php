<?php

namespace App\Services\Admissions;

use App\Models\AdmissionActivationDispatch;
use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enregistre l'intention d'envoi dans la transaction métier AVANT l'appel réseau.
 * Un worker dédié expédie ensuite le même message avec la même Idempotency-Key.
 * Seul le payload chiffré, jamais le lien en clair, survit à la requête.
 */
final class AdmissionActivationOutbox
{
    public function enqueue(ESBTPCandidatureWorkflow $workflow, string $channel, string $url): bool
    {
        if (! in_array($channel, ['email', 'whatsapp'], true)
            || ! $workflow->activation_token_hash
            || ! $workflow->activation_token_expires_at
            || $workflow->activation_token_expires_at->isPast()
            || $workflow->accessActivated()) {
            return false;
        }

        $version = ManagedInscriptionWorkflow::linkVersion($workflow);
        // Même clé que le notifier MailPulse : une ligne logique, une requête réseau.
        // Le hash du lien ne permet pas de reconstruire le secret.
        $prefix = $channel === 'email' ? 'admission-activation-email-' : 'admission-activation-';
        $id = $prefix.$workflow->id.'-'.substr(hash('sha256', $url), 0, 16);
        $payload = Crypt::encryptString(json_encode(['url' => $url], JSON_THROW_ON_ERROR));

        AdmissionActivationDispatch::query()->firstOrCreate(
            ['request_id' => $id],
            [
                'workflow_id' => $workflow->id,
                'channel' => $channel,
                'status' => 'queued',
                'token_version' => $version,
                'encrypted_payload' => $payload,
                'attempt_count' => 0,
                'next_attempt_at' => now(),
                'expires_at' => $workflow->activation_token_expires_at,
            ]
        );

        return true;
    }

    /**
     * Le worker prend possession de chaque événement atomiquement.
     * Un process tué libère implicitement sa réservation après 2 minutes.
     * Chaque tentative conserve la même Idempotency-Key fournisseur.
     */
    public function process(int $limit, AdmissionActivationNotifier $notifier, ManagedInscriptionWorkflow $managed): array
    {
        $counts = ['accepted' => 0, 'retried' => 0, 'expired' => 0, 'failed' => 0];

        $ids = AdmissionActivationDispatch::query()
            ->where(fn ($q) => $q->where('status', 'queued')
                ->orWhere(fn ($q) => $q->where('status', 'processing')->where('locked_until', '<', now())))
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')
            ->limit(max(1, min(100, $limit)))
            ->pluck('id');

        foreach ($ids as $id) {
            $claimed = AdmissionActivationDispatch::query()
                ->whereKey($id)
                ->where(fn ($q) => $q->where('status', 'queued')
                    ->orWhere(fn ($q) => $q->where('status', 'processing')->where('locked_until', '<', now())))
                ->where('next_attempt_at', '<=', now())
                ->update([
                    'status' => 'processing',
                    'attempt_count' => DB::raw('attempt_count + 1'),
                    'locked_until' => now()->addMinutes(2),
                ]);

            if ($claimed !== 1) {
                continue;
            }

            $entry = AdmissionActivationDispatch::query()->find($id);
            if (! $entry) {
                continue;
            }

            $workflow = ESBTPCandidatureWorkflow::query()
                ->with(['candidature', 'etudiant.user'])->find($entry->workflow_id);
            $valid = $workflow && ! $workflow->accessActivated()
                && $workflow->activation_token_hash
                && $workflow->activation_token_expires_at?->isFuture()
                && $entry->expires_at?->isFuture()
                && hash_equals((string) $entry->token_version, ManagedInscriptionWorkflow::linkVersion($workflow))
                && $managed->activationMilestoneReached($workflow);

            if (! $valid) {
                $entry->forceFill([
                    'status' => 'expired', 'encrypted_payload' => null,
                    'locked_until' => null, 'next_attempt_at' => null,
                ])->save();
                $counts['expired']++;
                continue;
            }

            try {
                $payload = json_decode(Crypt::decryptString((string) $entry->encrypted_payload), true, 4, JSON_THROW_ON_ERROR);
                $url = $payload['url'] ?? null;
                if (! is_string($url) || ! str_starts_with($url, url('/').'/')) {
                    throw new \UnexpectedValueException('invalid_activation_payload');
                }

                // Dernier contrôle de contact : un canal devenu non autorisé n'est plus utilisé.
                $allowed = $entry->channel === 'email'
                    ? $notifier->emailUsable($workflow)
                    : $notifier->whatsappUsable($workflow);
                if (! $allowed) {
                    $entry->forceFill([
                        'status' => 'failed', 'error_code' => 'contact_unverified',
                        'encrypted_payload' => null, 'locked_until' => null,
                        'next_attempt_at' => null,
                    ])->save();
                    $counts['failed']++;
                    continue;
                }

                $sent = $entry->channel === 'email'
                    ? $notifier->sendEmail($workflow, $url)
                    : $notifier->sendWhatsAppLink($workflow, $url);

                // Le notifier a déjà enregistré la réponse fournisseur dans la même
                // ligne request_id : ne pas transformer un pending en « livré ».
                $entry->refresh();
                if ($sent && $entry->status === 'accepted') {
                    $entry->forceFill([
                        'encrypted_payload' => null, 'locked_until' => null,
                        'next_attempt_at' => null,
                    ])->save();
                    $counts['accepted']++;
                } else {
                    $this->retryOrFail($entry);
                    $counts[$entry->fresh()->status === 'queued' ? 'retried' : 'failed']++;
                }
            } catch (\Throwable $error) {
                Log::warning('Admission activation outbox transport unavailable', [
                    'dispatch_id' => $entry->id,
                    'exception' => $error::class,
                ]);
                $this->retryOrFail($entry);
                $counts[$entry->fresh()->status === 'queued' ? 'retried' : 'failed']++;
            }
        }

        return $counts;
    }

    private function retryOrFail(AdmissionActivationDispatch $entry): void
    {
        $tryAgain = $entry->attempt_count < 5 && $entry->expires_at?->isFuture();
        $entry->forceFill([
            'status' => $tryAgain ? 'queued' : 'failed',
            'next_attempt_at' => $tryAgain ? now()->addSeconds(min(1800, 30 * (2 ** ($entry->attempt_count - 1)))) : null,
            'locked_until' => null,
            'encrypted_payload' => $tryAgain ? $entry->encrypted_payload : null,
            'error_code' => $tryAgain ? $entry->error_code : ($entry->error_code ?: 'max_attempts_reached'),
        ])->save();
    }
}
