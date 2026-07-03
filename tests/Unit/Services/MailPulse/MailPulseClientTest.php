<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseClient;
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
        $this->assertSame('Confirmez le vrai endpoint/methode MailPulse pour ce canal.', $result->action);
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
}
