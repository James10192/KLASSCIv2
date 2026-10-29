<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailPulseClientTest extends TestCase
{
    /** @test */
    public function it_maps_method_not_allowed_to_actionable_status(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([], 405),
        ]);

        $result = app(MailPulseClient::class)->sendEmailMessage([
            'channel' => 'email',
            'recipient' => [
                'type' => 'email',
                'value' => 'parent@example.com',
            ],
            'content' => [
                'type' => 'text',
                'text' => 'Test',
            ],
        ]);

        $this->assertFalse($result->ok);
        $this->assertSame('endpoint_not_supported', $result->status);
        $this->assertSame(405, $result->httpStatus);
        $this->assertSame('Confirmez le vrai endpoint/méthode MailPulse pour ce canal.', $result->action);
    }

    /** @test */
    public function it_never_calls_mailpulse_without_api_key(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', '');

        Http::fake();

        $result = app(MailPulseClient::class)->createOrUpdateContact([
            'email' => 'parent@example.com',
        ]);

        $this->assertFalse($result->ok);
        $this->assertSame('missing_api_key', $result->status);
        Http::assertNothingSent();
    }

    /** @test */
    public function it_maps_array_provider_error_without_crashing(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'error' => [
                    'code' => 'TEMPLATE_REQUIRED',
                    'message' => 'WhatsApp template required',
                ],
            ], 422),
        ]);

        $result = app(MailPulseClient::class)->sendWhatsAppMessage([
            'channel' => 'whatsapp',
            'recipient' => [
                'type' => 'phone',
                'value' => '+2250707123456',
            ],
            'content' => [
                'type' => 'text',
                'text' => 'Test',
            ],
        ]);

        $this->assertFalse($result->ok);
        $this->assertSame('template_required', $result->status);
        $this->assertSame(422, $result->httpStatus);
    }

    /** @test */
    public function it_submits_an_sms_intent_with_the_supplied_idempotency_request_id(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'pending', 'sms_fallback_eligible' => false],
                'message' => ['id' => 'sms-123', 'status' => 'queued'],
            ], 202),
        ]);

        $requestId = 'klassci-sms-0ac9593b4b4f4ef7f7e3a89b5da66ac1';
        $result = app(MailPulseClient::class)->sendSmsMessage([
            'channel' => 'sms',
            'recipient' => ['type' => 'phone', 'value' => '+2250707123456'],
            'content' => ['type' => 'text', 'text' => 'Votre notification KLASSCI.'],
        ], $requestId);

        $this->assertTrue($result->ok);
        $this->assertSame($requestId, $result->requestId);
        $this->assertSame('sms-123', $result->id);
        Http::assertSent(fn (Request $request) => $request->header('Idempotency-Key')[0] === $requestId
            && $request->header('X-KLASSCI-Request-Id')[0] === $requestId
            && $request['channel'] === 'sms'
            && $request['recipient']['value'] === '+2250707123456');
    }

    /** @test */
    public function it_reuses_the_same_idempotency_key_for_a_notification_retry(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'pending', 'sms_fallback_eligible' => false],
                'message' => ['id' => 'sms-123', 'status' => 'queued'],
            ], 202),
        ]);

        $requestId = 'klassci-sms-0ac9593b4b4f4ef7f7e3a89b5da66ac1';
        $payload = [
            'channel' => 'sms',
            'recipient' => ['type' => 'phone', 'value' => '+2250707123456'],
            'content' => ['type' => 'text', 'text' => 'Votre notification KLASSCI.'],
        ];

        $first = app(MailPulseClient::class)->sendSmsMessage($payload, $requestId);
        $retry = app(MailPulseClient::class)->sendSmsMessage($payload, $requestId);

        $this->assertTrue($first->ok);
        $this->assertTrue($retry->ok);
        $this->assertSame($first->id, $retry->id);
        $this->assertSame($requestId, $retry->requestId);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->header('Idempotency-Key')[0] === $requestId
            && $request->header('X-KLASSCI-Request-Id')[0] === $requestId
            && $request->data() === $payload);
    }

    /** @test */
    public function it_classifies_a_non_activated_whatsapp_recipient_for_sms_fallback(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'failed', 'sms_fallback_eligible' => true],
                'code' => 'recipient_not_activated',
                'message' => [
                    'id' => 'whatsapp-123',
                    'status' => 'failed',
                    'error_code' => 'recipient_not_activated',
                    'error_message' => 'Recipient is not activated on WhatsApp.',
                ],
            ], 422),
        ]);

        $result = app(MailPulseClient::class)->sendWhatsAppMessage([
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => '+2250707123456'],
            'content' => ['type' => 'text', 'text' => 'Votre notification KLASSCI.'],
        ]);

        $this->assertFalse($result->ok);
        $this->assertSame('whatsapp_not_activated', $result->status);
        $this->assertSame('failed', $result->dispatchState);
        $this->assertTrue($result->smsFallbackEligible);
    }

    /** @test */
    public function it_does_not_infer_whatsapp_delivery_or_sms_fallback_from_http_202(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'pending', 'sms_fallback_eligible' => false],
                'message' => ['id' => 'whatsapp-456', 'status' => 'queued'],
            ], 202),
        ]);

        $result = app(MailPulseClient::class)->sendWhatsAppMessage([
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => '+2250707123456'],
            'content' => ['type' => 'text', 'text' => 'Votre notification KLASSCI.'],
        ]);

        $this->assertTrue($result->ok);
        $this->assertSame('queued', $result->status);
        $this->assertSame('pending', $result->dispatchState);
        $this->assertFalse($result->isDispatchAccepted());
        $this->assertFalse($result->smsFallbackEligible);
    }
}
