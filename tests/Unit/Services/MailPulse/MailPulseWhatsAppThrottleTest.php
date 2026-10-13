<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseClient;
use Illuminate\Http\Client\Request;
use RuntimeException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailPulseWhatsAppThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'whatsapp-pacing-test');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.whatsapp_pacing_enabled', true);
        config()->set('services.mailpulse.whatsapp_per_minute', 6);
        config()->set('services.mailpulse.whatsapp_per_hour', 100);
        config()->set('services.mailpulse.whatsapp_per_recipient_hour', 10);
        config()->set('services.mailpulse.whatsapp_min_spacing_seconds', 10);

        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
                'message' => ['id' => 'wamid.123', 'status' => 'sent'],
            ], 202),
        ]);
    }

    private function message(string $recipient = '+2250707123456'): array
    {
        return [
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => $recipient],
            'content' => ['type' => 'text', 'text' => 'Notification KLASSCI'],
        ];
    }

    public function test_pacing_defers_without_http_request_and_preserves_idempotency(): void
    {
        $client = app(MailPulseClient::class);

        $first = $client->sendWhatsAppMessage($this->message(), 'school-001');
        $second = $client->sendWhatsAppMessage($this->message(), 'school-002');

        $this->assertTrue($first->ok);
        $this->assertFalse($second->ok);
        $this->assertSame('whatsapp_pacing', $second->status);
        $this->assertSame(429, $second->httpStatus);
        $this->assertSame('school-002', $second->requestId);
        $this->assertGreaterThan(0, $second->retryAfter);
        $this->assertFalse($second->smsFallbackEligible);
        Http::assertSentCount(1);

        $this->travel(11)->seconds();

        $retry = $client->sendWhatsAppMessage($this->message(), 'school-002');
        $this->assertTrue($retry->ok);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool =>
            $request->header('Idempotency-Key')[0] === 'school-002'
        );
    }

    public function test_recipient_hourly_limit_applies_even_after_spacing(): void
    {
        config()->set('services.mailpulse.whatsapp_min_spacing_seconds', 0);
        config()->set('services.mailpulse.whatsapp_per_recipient_hour', 2);
        $client = app(MailPulseClient::class);

        $this->assertTrue($client->sendWhatsAppMessage($this->message(), 'one')->ok);
        $this->assertTrue($client->sendWhatsAppMessage($this->message(), 'two')->ok);
        $third = $client->sendWhatsAppMessage($this->message(), 'three');

        $this->assertSame('whatsapp_pacing', $third->status);
        $this->assertGreaterThan(0, $third->retryAfter);
        Http::assertSentCount(2);
    }

    public function test_per_minute_limit_is_shared_across_recipients(): void
    {
        config()->set('services.mailpulse.whatsapp_min_spacing_seconds', 0);
        config()->set('services.mailpulse.whatsapp_per_minute', 2);
        $client = app(MailPulseClient::class);

        $this->assertTrue($client->sendWhatsAppMessage($this->message('+2250707111111'), 'one')->ok);
        $this->assertTrue($client->sendWhatsAppMessage($this->message('+2250707222222'), 'two')->ok);
        $deferred = $client->sendWhatsAppMessage($this->message('+2250707333333'), 'three');

        $this->assertSame('whatsapp_pacing', $deferred->status);
        Http::assertSentCount(2);
    }

    public function test_cache_failure_fails_closed_instead_of_sending_whatsapp(): void
    {
        Cache::shouldReceive('lock')->once()->andThrow(new RuntimeException('cache down'));

        $result = app(MailPulseClient::class)->sendWhatsAppMessage($this->message(), 'request-cache-down');

        $this->assertFalse($result->ok);
        $this->assertSame('whatsapp_pacing_unavailable', $result->status);
        $this->assertSame(503, $result->httpStatus);
        $this->assertSame(60, $result->retryAfter);
        Http::assertNothingSent();
    }

    public function test_disabled_pacing_keeps_existing_mailpulse_behavior(): void
    {
        config()->set('services.mailpulse.whatsapp_pacing_enabled', false);
        $client = app(MailPulseClient::class);

        $this->assertTrue($client->sendWhatsAppMessage($this->message(), 'one')->ok);
        $this->assertTrue($client->sendWhatsAppMessage($this->message(), 'two')->ok);
        Http::assertSentCount(2);
    }
}
