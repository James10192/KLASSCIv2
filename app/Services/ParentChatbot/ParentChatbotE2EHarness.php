<?php

namespace App\Services\ParentChatbot;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ParentChatbotInboundEvent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCode;
use App\Models\ParentChatbotLinkCodeIssuance;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ParentChatbotE2EHarness
{
    private const PARENT_NOM = 'CODEX-E2E';
    private const LABEL_PATTERN = '/^codex-e2e-[a-z0-9][a-z0-9-]{5,59}$/';

    public function __construct(
        private ParentChatbotLinkService $links,
        private ParentChatbotPhoneNormalizer $phones,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareFixture(string $label, string $phone, int $studentId): array
    {
        $this->assertValidLabel($label);
        $normalizedPhone = $this->authorizedTestPhone($phone);

        return DB::transaction(function () use ($label, $normalizedPhone, $studentId): array {
            $student = ESBTPEtudiant::query()->find($studentId);
            if (! $student) {
                throw ValidationException::withMessages([
                    'student_id' => 'Etudiant introuvable pour cette fixture E2E.',
                ]);
            }

            $existing = ESBTPParent::withTrashed()
                ->where('nom', self::PARENT_NOM)
                ->where('prenoms', $label)
                ->exists();
            if ($existing) {
                throw ValidationException::withMessages([
                    'label' => 'Une fixture E2E existe deja pour ce label. Lancez le cleanup avant de la recreer.',
                ]);
            }

            $phoneHash = $this->phones->hash($normalizedPhone);
            $existingTestLink = ParentChatbotLink::query()
                ->where('phone_hash', $phoneHash)
                ->whereHas('parent', fn ($query) => $query->where('nom', self::PARENT_NOM))
                ->exists();
            if ($existingTestLink) {
                throw ValidationException::withMessages([
                    'phone' => 'Un lien chatbot E2E existe deja pour ce numero. Nettoyez son label avant de recreer une fixture.',
                ]);
            }

            $parent = ESBTPParent::create([
                'nom' => self::PARENT_NOM,
                'prenoms' => $label,
                'telephone' => $normalizedPhone,
                'email' => $label.'@example.invalid',
                'profession' => 'Fixture E2E MailPulse',
                'adresse' => 'Fixture E2E reversible',
            ]);
            $parent->etudiants()->attach($student->id, [
                'relation' => 'tuteur',
                'is_tuteur' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $code = $this->links->issueCode($parent);

            return [
                'ok' => true,
                'label' => $label,
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'normalized_phone' => $normalizedPhone,
                'lier_command' => 'LIER '.$code,
            ];
        }, 3);
    }

    /**
     * @return array<string, mixed>
     */
    public function invokeSignedInbound(
        string $label,
        string $eventId,
        string $phone,
        string $message,
        ?string $baseUrl = null,
    ): array {
        $this->assertValidLabel($label);
        if (! str_starts_with($eventId, $label.'-')) {
            throw ValidationException::withMessages([
                'event_id' => 'event_id doit commencer par le label E2E suivi de "-".',
            ]);
        }

        $normalizedPhone = $this->authorizedTestPhone($phone);
        try {
            $secret = ParentChatbotSecurityConfig::webhookSecret();
        } catch (\LogicException) {
            throw ValidationException::withMessages([
                'webhook_secret' => 'MAILPULSE_PARENT_CHATBOT_WEBHOOK_SECRET doit etre configure.',
            ]);
        }

        $payload = [
            'event_id' => $eventId,
            'sender' => ['phone' => $normalizedPhone],
            'message' => ['text' => $message],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.mailpulse.timeout', 20))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-MailPulse-Timestamp' => $timestamp,
                    'X-MailPulse-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($this->inboundUrl($baseUrl));
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'label' => $label,
                'event_id' => $eventId,
                'http_status' => null,
                'error' => 'connection_failed',
            ];
        }

        return [
            'ok' => $response->status() === 202,
            'label' => $label,
            'event_id' => $eventId,
            'http_status' => $response->status(),
            'response' => $response->json(),
            'event' => $this->inboundEventSummary($eventId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cleanupFixture(string $label): array
    {
        $this->assertValidLabel($label);

        return DB::transaction(function () use ($label): array {
            $parents = ESBTPParent::withTrashed()
                ->where('nom', self::PARENT_NOM)
                ->where('prenoms', $label)
                ->get();
            $parentIds = $parents->modelKeys();

            $deleted = [
                'inbound_events' => ParentChatbotInboundEvent::query()
                    ->where('source_event_id', 'like', $label.'-%')
                    ->delete(),
                'link_code_issuances' => 0,
                'link_codes' => 0,
                'links' => 0,
                'pivot_rows' => 0,
                'parents' => 0,
            ];

            if ($parentIds !== []) {
                $deleted['link_code_issuances'] = ParentChatbotLinkCodeIssuance::query()
                    ->whereIn('parent_id', $parentIds)
                    ->delete();
                $deleted['link_codes'] = ParentChatbotLinkCode::query()
                    ->whereIn('parent_id', $parentIds)
                    ->delete();
                $deleted['links'] = ParentChatbotLink::query()
                    ->whereIn('parent_id', $parentIds)
                    ->delete();
                $deleted['pivot_rows'] = DB::table('esbtp_etudiant_parent')
                    ->whereIn('parent_id', $parentIds)
                    ->delete();

                foreach ($parents as $parent) {
                    $deleted['parents'] += $parent->forceDelete() ? 1 : 0;
                }
            }

            return [
                'ok' => true,
                'label' => $label,
                'deleted' => $deleted,
            ];
        }, 3);
    }

    /**
     * @return array<string, mixed>
     */
    private function inboundEventSummary(string $eventId): ?array
    {
        try {
            $event = ParentChatbotInboundEvent::query()
                ->where('source_event_id', $eventId)
                ->first();
        } catch (\Throwable) {
            return null;
        }

        if (! $event) {
            return null;
        }

        return [
            'id' => $event->id,
            'source_event_id' => $event->source_event_id,
            'outcome' => $event->outcome,
            'processed_at' => optional($event->processed_at)->toISOString(),
            'has_recorded_response' => $event->hasRecordedResponse(),
        ];
    }

    private function authorizedTestPhone(string $phone): string
    {
        $normalizedPhone = $this->phones->normalize($phone);
        if ($normalizedPhone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Numero de telephone ivoirien invalide.',
            ]);
        }

        if (! in_array($normalizedPhone, $this->activeTestPhones(), true)) {
            throw ValidationException::withMessages([
                'phone' => 'Le numero doit etre declare dans les destinataires de test MailPulse actifs.',
            ]);
        }

        return $normalizedPhone;
    }

    private function inboundUrl(?string $baseUrl): string
    {
        $root = $baseUrl !== null && trim($baseUrl) !== ''
            ? rtrim($baseUrl, '/')
            : rtrim(url(''), '/');

        return $root.'/api/v1/integrations/mailpulse/parent-chatbot/inbound';
    }

    /**
     * @return array<int, string>
     */
    private function activeTestPhones(): array
    {
        $phones = [];

        foreach ($this->plainPhoneList((string) config('services.mailpulse.test_notification_phones', '')) as $phone) {
            $phones[$phone] = $phone;
        }
        $configPhone = PhoneNormalizer::toE164((string) config('services.mailpulse.test_notification_phone', ''));
        if ($configPhone !== null) {
            $phones[$configPhone] = $configPhone;
        }

        $recipients = $this->jsonRecipients((string) $this->settingValue('mailpulse_test_phone_recipients', ''));

        foreach ($recipients as $recipient) {
            $phone = PhoneNormalizer::toE164((string) ($recipient['value'] ?? ''));
            if (($recipient['enabled'] ?? true) && $phone !== null) {
                $phones[$phone] = $phone;
            }
        }

        if ($phones === []) {
            foreach ($this->plainPhoneList((string) $this->settingValue('mailpulse_test_phones', '')) as $phone) {
                $phones[$phone] = $phone;
            }
            $legacyPhone = PhoneNormalizer::toE164((string) $this->settingValue('mailpulse_test_phone', ''));
            if ($legacyPhone !== null) {
                $phones[$legacyPhone] = $legacyPhone;
            }
        }

        return array_values($phones);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonRecipients(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($item): bool => is_array($item)));
    }

    /**
     * @return array<int, string>
     */
    private function plainPhoneList(string $list): array
    {
        $phones = [];
        foreach (preg_split('/[\r\n,;]+/', $list) ?: [] as $item) {
            $phone = PhoneNormalizer::toE164($item);
            if ($phone !== null) {
                $phones[] = $phone;
            }
        }

        return $phones;
    }

    private function assertValidLabel(string $label): void
    {
        if (preg_match(self::LABEL_PATTERN, $label) !== 1) {
            throw ValidationException::withMessages([
                'label' => 'Le label doit commencer par codex-e2e- et rester strictement borne.',
            ]);
        }
    }

    private function settingValue(string $key, string $default): string
    {
        try {
            return (string) Setting::get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}
