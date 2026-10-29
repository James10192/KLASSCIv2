<?php

namespace App\Services\ParentChatbot;

use App\Models\ESBTPParent;
use App\Models\ParentChatbotInboundEvent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCode;
use App\Models\ParentChatbotLinkCodeIssuance;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ParentChatbotLinkService
{
    public function __construct(private ParentChatbotPhoneNormalizer $phones) {}

    /**
     * The caller is responsible for delivering this code through an approved school workflow.
     * Only its keyed digest is persisted.
     */
    public function issueCode(ESBTPParent $parent): string
    {
        return $this->issueCodeWithRecord($parent)['code'];
    }

    /** @return array{code: string, linkCode: ParentChatbotLinkCode} */
    public function issueCodeWithRecord(ESBTPParent $parent): array
    {
        if ($this->registeredPhone($parent) === null || ! $parent->pupilles()->exists()) {
            throw new InvalidArgumentException('Le parent doit avoir un téléphone tuteur enregistré.');
        }

        return DB::transaction(function () use ($parent): array {
            ParentChatbotLinkCode::where('parent_id', $parent->id)
                ->whereNull('consumed_at')
                ->update(['expires_at' => now()]);

            do {
                $code = strtoupper(bin2hex(random_bytes(8)));
                $hash = $this->codeHash($code);
            } while (ParentChatbotLinkCode::where('code_hash', $hash)->exists());

            $linkCode = ParentChatbotLinkCode::create([
                'parent_id' => $parent->id,
                'code_hash' => $hash,
                'expires_at' => now()->addMinutes((int) config('services.mailpulse.parent_chatbot_link_code_ttl', 15)),
            ]);

            return ['code' => $code, 'linkCode' => $linkCode];
        });
    }

    /** @return array{link: ?ParentChatbotLink, reason: string} */
    public function link(string $phone, string $code): array
    {
        $normalizedPhone = $this->phones->normalize($phone);
        if ($normalizedPhone === null) {
            return ['link' => null, 'reason' => 'invalid_phone'];
        }

        return DB::transaction(fn (): array => $this->linkWithinTransaction($normalizedPhone, $code));
    }

    /**
     * Performs LIER and stores its replayable inbound response in the same
     * transaction. A failed response write must never leave a consumed code.
     *
     * @param Closure(array{link: ?ParentChatbotLink, reason: string}, ?string): array{phone: string, intent: string, outcome: string, reply: string, should_dispatch: bool, disclosure: ?array, authorization_claim: ?array} $responseForResult
     * @return array{phone: string, intent: string, outcome: string, reply: string, idempotency_key: string, should_dispatch: bool, disclosure: ?array, authorization_claim: ?array}
     */
    public function linkAndRecordInboundResponse(
        ParentChatbotInboundEvent $event,
        string $token,
        string $phone,
        string $code,
        string $idempotencyKey,
        Closure $responseForResult,
    ): array {
        return DB::transaction(function () use ($event, $token, $phone, $code, $idempotencyKey, $responseForResult): array {
            $lockedEvent = ParentChatbotInboundEvent::query()
                ->whereKey($event->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedEvent->hasRecordedResponse()) {
                return $lockedEvent->recordedResponse();
            }

            if ($lockedEvent->processed_at !== null
                || ! is_string($lockedEvent->processing_token)
                || ! hash_equals($lockedEvent->processing_token, $token)) {
                throw new RuntimeException('The parent chatbot inbound event lease is no longer held.');
            }

            $normalizedPhone = $this->phones->normalize($phone);
            $result = $normalizedPhone === null
                ? ['link' => null, 'reason' => 'invalid_phone']
                : $this->linkWithinTransaction($normalizedPhone, $code);
            $response = $responseForResult($result, $normalizedPhone);

            if (! $lockedEvent->recordResponse(
                $token,
                $response['phone'],
                $response['intent'],
                $response['outcome'],
                $response['reply'],
                $idempotencyKey,
                $response['should_dispatch'],
                $response['disclosure'] ?? null,
                $response['authorization_claim'] ?? null,
            )) {
                throw new RuntimeException('The parent chatbot LIER response could not be persisted.');
            }

            return $lockedEvent->fresh()->recordedResponse();
        }, 3);
    }

    /**
     * Activation without a code: the parent answers the school's invitation
     * from the number the school registered. The code-based flow already
     * re-checked that the message came from that same number, so the number is
     * what carried the proof of possession all along.
     *
     * @return array{link: ?ParentChatbotLink, reason: string}
     */
    public function linkByRegisteredPhone(string $phone): array
    {
        $normalizedPhone = $this->phones->normalize($phone);
        if ($normalizedPhone === null) {
            return ['link' => null, 'reason' => 'invalid_phone'];
        }

        return DB::transaction(fn (): array => $this->linkRegisteredPhoneWithinTransaction($normalizedPhone));
    }

    /** @return array{link: ?ParentChatbotLink, reason: string} */
    private function linkRegisteredPhoneWithinTransaction(string $normalizedPhone): array
    {
        $parents = $this->parentsRegisteredWith($normalizedPhone);

        // Two tutors sharing a number cannot be told apart from a bare "OUI".
        if ($parents->count() !== 1) {
            return ['link' => null, 'reason' => $parents->isEmpty() ? 'phone_not_registered_tuteur' : 'ambiguous_link'];
        }

        $parent = $parents->first();
        if (! $parent->pupilles()->exists()) {
            return ['link' => null, 'reason' => 'phone_not_registered_tuteur'];
        }

        // The school decides who joins: an invitation must have been issued.
        if (! ParentChatbotLinkCodeIssuance::where('parent_id', $parent->id)->exists()) {
            return ['link' => null, 'reason' => 'not_invited'];
        }

        return $this->activateLink($parent, $normalizedPhone);
    }

    /**
     * Resolves candidates on the stored digits, then compares the normalized
     * forms: the column keeps whatever the secretariat typed.
     *
     * @return \Illuminate\Support\Collection<int, ESBTPParent>
     */
    private function parentsRegisteredWith(string $normalizedPhone): \Illuminate\Support\Collection
    {
        $significantDigits = substr(preg_replace('/\D/', '', $normalizedPhone) ?? '', -8);
        if ($significantDigits === '') {
            return collect();
        }

        return ESBTPParent::query()
            ->whereNotNull('telephone')
            ->where('telephone', 'like', '%'.$significantDigits)
            ->get()
            ->filter(fn (ESBTPParent $parent): bool => hash_equals(
                (string) $this->registeredPhone($parent),
                $normalizedPhone,
            ))
            ->values();
    }

    /** @return array{link: ?ParentChatbotLink, reason: string} */
    private function activateLink(ESBTPParent $parent, string $normalizedPhone): array
    {
        $phoneHash = $this->phones->hash($normalizedPhone);
        $link = ParentChatbotLink::where('parent_id', $parent->id)
            ->where('phone_hash', $phoneHash)
            ->lockForUpdate()
            ->first();

        if ($link?->status === ParentChatbotLink::STATUS_REVOKED) {
            return ['link' => null, 'reason' => 'revoked'];
        }

        $students = $parent->pupilles()->select('esbtp_etudiants.id')->get();
        $link ??= new ParentChatbotLink(['parent_id' => $parent->id, 'phone_hash' => $phoneHash]);
        $link->fill([
            'status' => ParentChatbotLink::STATUS_ACTIVE,
            'stopped_at' => null,
            'last_inbound_at' => now(),
            'selected_student_id' => $students->count() === 1 ? $students->first()->id : null,
        ]);
        $link->save();

        return ['link' => $link, 'reason' => 'linked'];
    }

    /** @return array{link: ?ParentChatbotLink, reason: string} */
    private function linkWithinTransaction(string $normalizedPhone, string $code): array
    {
            $linkCode = ParentChatbotLinkCode::where('code_hash', $this->codeHash(strtoupper($code)))
                ->lockForUpdate()
                ->first();

            if (! $linkCode || ! $linkCode->isUsable()) {
                return ['link' => null, 'reason' => 'invalid_code'];
            }

            $parent = ESBTPParent::find($linkCode->parent_id);
            if (! $parent || ! hash_equals((string) $this->registeredPhone($parent), $normalizedPhone) || ! $parent->pupilles()->exists()) {
                return ['link' => null, 'reason' => 'phone_not_registered_tuteur'];
            }

            $result = $this->activateLink($parent, $normalizedPhone);
            if ($result['link'] !== null) {
                $linkCode->update(['consumed_at' => now()]);
            }

            return $result;
    }

    public function stop(ParentChatbotLink $link): bool
    {
        return DB::transaction(function () use ($link): bool {
            $link = ParentChatbotLink::query()
                ->whereKey($link->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($link->status === ParentChatbotLink::STATUS_REVOKED) {
                return false;
            }

            if ($link->status === ParentChatbotLink::STATUS_STOPPED) {
                $link->update(['last_inbound_at' => now()]);
                return true;
            }

            $link->update([
                'status' => ParentChatbotLink::STATUS_STOPPED,
                'stopped_at' => now(),
                'last_inbound_at' => now(),
            ]);

            return true;
        });
    }

    public function stopAllForPhone(string $normalizedPhone): bool
    {
        $phoneHash = $this->phones->hash($normalizedPhone);

        return DB::transaction(function () use ($phoneHash): bool {
            $links = ParentChatbotLink::query()
                ->where('phone_hash', $phoneHash)
                ->lockForUpdate()
                ->get();
            $stopped = false;

            foreach ($links as $link) {
                if ($link->status === ParentChatbotLink::STATUS_REVOKED) {
                    continue;
                }

                $parent = ESBTPParent::query()
                    ->whereKey($link->parent_id)
                    ->lockForUpdate()
                    ->first();
                $registeredPhone = $parent ? $this->registeredPhone($parent) : null;
                $authorized = $parent !== null
                    && $registeredPhone !== null
                    && hash_equals($phoneHash, $this->phones->hash($registeredPhone))
                    && $parent->pupilles()->lockForUpdate()->exists();

                if (! $authorized) {
                    $link->update([
                        'status' => ParentChatbotLink::STATUS_REVOKED,
                        'revoked_at' => now(),
                        'selected_student_id' => null,
                    ]);

                    continue;
                }

                $link->update([
                    'status' => ParentChatbotLink::STATUS_STOPPED,
                    'stopped_at' => now(),
                    'last_inbound_at' => now(),
                ]);
                $stopped = true;
            }

            return $stopped;
        }, 3);
    }

    public function start(ParentChatbotLink $link): bool
    {
        return DB::transaction(function () use ($link): bool {
            $link = ParentChatbotLink::query()
                ->whereKey($link->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($link->status === ParentChatbotLink::STATUS_REVOKED) {
                return false;
            }

            $link->update([
                'status' => ParentChatbotLink::STATUS_ACTIVE,
                'stopped_at' => null,
                'last_inbound_at' => now(),
            ]);

            return true;
        });
    }

    public function revoke(ParentChatbotLink $link): void
    {
        $link->update([
            'status' => ParentChatbotLink::STATUS_REVOKED,
            'revoked_at' => now(),
            'selected_student_id' => null,
        ]);
    }

    public function isAuthorized(ParentChatbotLink $link, string $normalizedPhone): bool
    {
        if ($link->status === ParentChatbotLink::STATUS_REVOKED) {
            return false;
        }

        $parent = ESBTPParent::find($link->parent_id);
        $registeredPhone = $parent ? $this->registeredPhone($parent) : null;
        $isAuthorized = $parent !== null
            && $registeredPhone !== null
            && hash_equals($link->phone_hash, $this->phones->hash($normalizedPhone))
            && hash_equals($link->phone_hash, $this->phones->hash($registeredPhone))
            && $parent->pupilles()->exists();

        if (! $isAuthorized) {
            $this->revoke($link);
        }

        return $isAuthorized;
    }

    /**
     * Revalidates the authorization captured with a response while holding the
     * link, parent and selected-pupil rows until the caller has submitted it.
     *
     * @return array{authorization_eligible: bool, result: mixed}
     */
    public function submitIfStillDispatchAuthorized(
        ?array $claim,
        string $recipientPhone,
        bool $isStopAcknowledgement,
        Closure $submit,
    ): array {
        if ($isStopAcknowledgement) {
            return DB::transaction(fn (): array => [
                'authorization_eligible' => true,
                'result' => $submit(),
            ], 3);
        }

        $authorization = $this->normalizedDispatchAuthorization($claim);
        $normalizedRecipient = $this->phones->normalize($recipientPhone);
        if ($authorization === null || $normalizedRecipient === null
            || ! hash_equals($authorization['phone_hash'], $this->phones->hash($normalizedRecipient))) {
            return ['authorization_eligible' => false, 'result' => null];
        }

        return DB::transaction(function () use ($authorization, $submit): array {
            $link = ParentChatbotLink::query()
                ->whereKey($authorization['link_id'])
                ->lockForUpdate()
                ->first();
            if (! $link
                || $link->status !== ParentChatbotLink::STATUS_ACTIVE
                || (int) $link->parent_id !== $authorization['parent_id']
                || ! hash_equals($link->phone_hash, $authorization['phone_hash'])
                || $this->selectedStudentChanged($link, $authorization['student_id'])) {
                return ['authorization_eligible' => false, 'result' => null];
            }

            $parent = ESBTPParent::query()
                ->whereKey($authorization['parent_id'])
                ->lockForUpdate()
                ->first();
            $registeredPhone = $parent ? $this->registeredPhone($parent) : null;
            if ($parent === null || $registeredPhone === null
                || ! hash_equals($authorization['phone_hash'], $this->phones->hash($registeredPhone))) {
                return ['authorization_eligible' => false, 'result' => null];
            }

            if (! $this->parentStillOwnsSelectedStudent($parent, $authorization['student_id'])) {
                return ['authorization_eligible' => false, 'result' => null];
            }

            return ['authorization_eligible' => true, 'result' => $submit()];
        }, 3);
    }

    /** @return array{link_id: int, phone_hash: string, parent_id: int, student_id: ?int}|null */
    public function dispatchAuthorizationClaim(ParentChatbotLink $link): array
    {
        return [
            'link_id' => (int) $link->getKey(),
            'phone_hash' => $link->phone_hash,
            'parent_id' => (int) $link->parent_id,
            'student_id' => $link->selected_student_id === null ? null : (int) $link->selected_student_id,
        ];
    }

    /** @return array{link_id: int, phone_hash: string, parent_id: int, student_id: ?int}|null */
    private function normalizedDispatchAuthorization(?array $claim): ?array
    {
        if (! is_array($claim)
            || ! is_string($claim['phone_hash'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/D', $claim['phone_hash']) !== 1) {
            return null;
        }

        $linkId = $this->positiveInteger($claim['link_id'] ?? null);
        $parentId = $this->positiveInteger($claim['parent_id'] ?? null);
        $studentId = $claim['student_id'] ?? null;
        if ($linkId === null || $parentId === null || ($studentId !== null && ($studentId = $this->positiveInteger($studentId)) === null)) {
            return null;
        }

        return [
            'link_id' => $linkId,
            'phone_hash' => $claim['phone_hash'],
            'parent_id' => $parentId,
            'student_id' => $studentId,
        ];
    }

    private function positiveInteger(mixed $value): ?int
    {
        return (is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)
            ? (int) $value
            : null;
    }

    private function selectedStudentChanged(ParentChatbotLink $link, ?int $studentId): bool
    {
        return ($link->selected_student_id === null ? null : (int) $link->selected_student_id) !== $studentId;
    }

    private function parentStillOwnsSelectedStudent(ESBTPParent $parent, ?int $studentId): bool
    {
        if ($studentId === null) {
            return $parent->pupilles()->lockForUpdate()->exists();
        }

        return $parent->pupilles()
            ->whereKey($studentId)
            ->lockForUpdate()
            ->exists();
    }

    private function registeredPhone(ESBTPParent $parent): ?string
    {
        return $this->phones->normalize((string) $parent->telephone);
    }

    private function codeHash(string $code): string
    {
        try {
            return hash_hmac('sha256', $code, ParentChatbotSecurityConfig::codePepper());
        } catch (\LogicException $exception) {
            throw new RuntimeException('MAILPULSE_PARENT_CHATBOT_CODE_PEPPER must be at least 32 characters.', previous: $exception);
        }
    }
}
