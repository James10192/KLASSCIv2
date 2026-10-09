<?php

namespace App\Services\Familles;

use App\Models\ESBTPFamilyAccountInvitation;
use App\Models\ESBTPFamilyAccessGrant;
use App\Models\User;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InvitationResponsable
{
    public function __construct(private readonly AccesFamilial $access)
    {
    }

    /**
     * La vérification de l'e-mail a lieu au guichet et est attestée
     * explicitement par l'agent habilité. Le parent n'utilise jamais le
     * jeton de l'étudiant et aucun message n'est envoyé dans la transaction.
     */
    public function preparer(ESBTPFamilyAccessGrant $grant, User $agent, string $confirmedEmail): ESBTPFamilyAccountInvitation
    {
        return DB::transaction(function () use ($grant, $agent, $confirmedEmail) {
            $grant = ESBTPFamilyAccessGrant::query()->lockForUpdate()
                ->with(['parent', 'etudiant'])->findOrFail($grant->id);

            $parent = $grant->parent;
            $student = $grant->etudiant;
            $email = mb_strtolower(trim((string) $parent?->email));

            if (! $parent || ! $student || $email === ''
                || ! filter_var($email, FILTER_VALIDATE_EMAIL)
                || ! hash_equals($email, mb_strtolower(trim($confirmedEmail)))) {
                throw ValidationException::withMessages([
                    'confirmed_email' => "L'adresse du responsable n'a pas été vérifiée au guichet.",
                ]);
            }

            if (! $grant->verified_at || $grant->revoked_at || ! $grant->expires_at?->isFuture()
                || ! $parent->etudiants()->whereKey($student->id)->wherePivot('is_tuteur', true)->exists()
                || (($student->date_naissance === null || $student->date_naissance->age >= 18)
                    && ! $grant->student_consent_at)) {
                throw ValidationException::withMessages([
                    'grant' => 'Cette habilitation ne permet pas une invitation.',
                ]);
            }

            // L'adresse e-mail ne doit pas déjà appartenir à un autre compte :
            // jamais de liaison implicite entre parent, étudiant et enseignant.
            $existing = User::withTrashed()->where('email', $email)->first();
            $user = $parent->user_id ? User::find($parent->user_id) : null;
            if (($existing && (! $user || (int) $existing->id !== (int) $user->id))
                || ($user && ($user->trashed() || $user->hasRole('etudiant')
                    || mb_strtolower(trim((string) $user->email)) !== $email
                    || (int) $user->id === (int) $student->user_id))) {
                throw ValidationException::withMessages([
                    'confirmed_email' => 'Adresse déjà utilisée par un autre compte, ou compte étudiant partagé.',
                ]);
            }

            if (! $user) {
                $user = User::create([
                    'name' => trim($parent->nom.' '.$parent->prenoms),
                    'first_name' => $parent->prenoms,
                    'last_name' => $parent->nom,
                    'username' => 'famille-'.Str::lower(Str::random(20)),
                    'email' => $email,
                    'password' => Str::random(64),
                    'is_active' => false,
                    'must_change_password' => true,
                ]);
                $parent->forceFill(['user_id' => $user->id])->save();
            }

            if (! $this->access->peutConsulter($grant->fresh(['parent', 'etudiant']), $user)) {
                throw ValidationException::withMessages(['grant' => 'Le droit familial n’a pas pu être vérifié.']);
            }

            // Tout ancien lien inutilisé est révoqué immédiatement.
            ESBTPFamilyAccountInvitation::query()
                ->where('grant_id', $grant->id)->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'encrypted_url' => null,
                    'status' => 'cancelled', 'next_attempt_at' => null]);

            $token = Str::random(64);
            $hash = hash('sha256', $token);
            $url = route('esbtp.famille.invitation.form', ['token' => $token]);
            return ESBTPFamilyAccountInvitation::create([
                'grant_id' => $grant->id,
                'parent_id' => $parent->id,
                'user_id' => $user->id,
                'issued_by' => $agent->id,
                'token_hash' => $hash,
                'request_id' => 'family-invite-'.$grant->id.'-'.substr($hash, 0, 24),
                'recipient_hash' => hash_hmac('sha256', $email, (string) config('app.key')),
                'encrypted_url' => Crypt::encryptString($url),
                'status' => 'queued',
                'attempt_count' => 0,
                'next_attempt_at' => now(),
                'expires_at' => now()->addHours(48),
            ]);
        });
    }

    public function parJeton(string $token): ESBTPFamilyAccountInvitation
    {
        $invitation = ESBTPFamilyAccountInvitation::query()
            ->with(['grant.parent', 'grant.etudiant', 'user'])
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation || ! $invitation->grant || ! $invitation->user
            || ! $this->access->peutConsulter($invitation->grant, $invitation->user)) {
            abort(410, 'Cette invitation est expirée ou a été annulée.');
        }

        return $invitation;
    }

    public function activer(string $token, string $password): User
    {
        return DB::transaction(function () use ($token, $password) {
            $invite = ESBTPFamilyAccountInvitation::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()->first();

            if (! $invite || $invite->used_at || $invite->revoked_at
                || ! $invite->expires_at?->isFuture()) {
                throw ValidationException::withMessages(['token' => 'Cette invitation est invalide, expirée ou déjà utilisée.']);
            }

            $grant = ESBTPFamilyAccessGrant::with(['parent', 'etudiant'])->find($invite->grant_id);
            $user = User::query()->lockForUpdate()->find($invite->user_id);
            if (! $grant || ! $user || ! $this->access->peutConsulter($grant, $user)) {
                throw ValidationException::withMessages(['token' => 'Cette autorisation familiale n’est plus valable.']);
            }

            $user->forceFill([
                'password' => $password,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'first_login_at' => $user->first_login_at ?: now(),
            ])->save();

            $invite->forceFill([
                'used_at' => now(),
                'encrypted_url' => null,
                'status' => 'activated',
                'next_attempt_at' => null,
            ])->save();

            return $user;
        });
    }

    /** @return array<string, int> */
    public function traiter(int $limit, MailPulseClient $client): array
    {
        $counts = ['accepted' => 0, 'retried' => 0, 'failed' => 0, 'expired' => 0];
        $ids = ESBTPFamilyAccountInvitation::query()
            ->where(fn ($q) => $q->where('status', 'queued')
                ->orWhere(fn ($q) => $q->where('status', 'processing')
                    ->where('locked_until', '<', now())))
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')->limit(max(1, min(100, $limit)))->pluck('id');

        foreach ($ids as $id) {
            $got = ESBTPFamilyAccountInvitation::query()->whereKey($id)
                ->where(fn ($q) => $q->where('status', 'queued')
                    ->orWhere(fn ($q) => $q->where('status', 'processing')
                        ->where('locked_until', '<', now())))
                ->where('next_attempt_at', '<=', now())
                ->update([
                    'status' => 'processing', 'locked_until' => now()->addMinutes(2),
                    'attempt_count' => DB::raw('attempt_count + 1'),
                ]);
            if ($got !== 1) {
                continue;
            }

            $item = ESBTPFamilyAccountInvitation::with(['grant.parent', 'grant.etudiant', 'user'])->find($id);
            if (! $item) {
                continue;
            }

            if ($item->used_at || $item->revoked_at || ! $item->expires_at?->isFuture()
                || ! $item->grant || ! $item->user
                || ! $this->access->peutConsulter($item->grant, $item->user)
                || ! hash_equals($item->recipient_hash, hash_hmac(
                    'sha256', mb_strtolower(trim((string) $item->grant->parent->email)), (string) config('app.key')
                ))) {
                $item->forceFill(['status' => 'expired', 'encrypted_url' => null,
                    'next_attempt_at' => null, 'locked_until' => null])->save();
                $counts['expired']++;
                continue;
            }

            try {
                $url = Crypt::decryptString((string) $item->encrypted_url);
                if (! str_starts_with($url, url('/').'/')) {
                    throw new \UnexpectedValueException('wrong_invitation_url');
                }

                $parent = $item->grant->parent;
                $result = $client->sendEmailMessage([
                    'channel' => 'email',
                    'recipient' => ['type' => 'email', 'value' => $parent->email],
                    'content' => ['type' => 'text', 'text' => "Bonjour, votre établissement vous invite à créer votre propre compte responsable KLASSCI : {$url}\nCe lien est personnel et valable 48 heures. Ne le transférez pas."],
                    'metadata' => ['source' => 'klassci', 'workflow_event' => 'family_account_invitation',
                        'subject' => 'Votre espace responsable KLASSCI',
                        'email_html' => view('esbtp.emails.family-account-invitation', [
                            'url' => $url, 'parent' => $parent,
                        ])->render()],
                ], $item->request_id);

                if ($result->status === 'dry_run') {
                    $item->forceFill(['status' => 'simulated', 'encrypted_url' => null,
                        'locked_until' => null, 'next_attempt_at' => null])->save();
                    continue;
                }

                if ($result->isDispatchAccepted()) {
                    $item->forceFill([
                        'status' => 'accepted',
                        'provider_message_id' => $result->id,
                        'encrypted_url' => null,
                        'locked_until' => null, 'next_attempt_at' => null,
                    ])->save();
                    $counts['accepted']++;
                    continue;
                }

                $this->reprogrammer($item, $result->errorCode ?? $result->status,
                    $result->httpStatus === null || $result->httpStatus === 429
                    || $result->httpStatus >= 500);
            } catch (\Throwable $error) {
                Log::warning('Family invitation outbox dispatch unavailable', [
                    'invitation_id' => $item->id,
                    'exception' => $error::class,
                ]);
                $this->reprogrammer($item, 'transport_exception', true);
            }

            $counts[$item->fresh()->status === 'queued' ? 'retried' : 'failed']++;
        }

        return $counts;
    }

    private function reprogrammer(ESBTPFamilyAccountInvitation $invite, string $error, bool $retryable): void
    {
        $retry = $retryable && $invite->attempt_count < 5 && $invite->expires_at?->isFuture();
        $invite->forceFill([
            'status' => $retry ? 'queued' : 'failed',
            'error_code' => substr(preg_replace('/[^a-z0-9_-]/i', '', $error) ?: 'error', 0, 80),
            'next_attempt_at' => $retry ? now()->addSeconds(min(1800, 30 * (2 ** ($invite->attempt_count - 1)))) : null,
            'locked_until' => null,
            'encrypted_url' => $retry ? $invite->encrypted_url : null,
        ])->save();
    }
}
