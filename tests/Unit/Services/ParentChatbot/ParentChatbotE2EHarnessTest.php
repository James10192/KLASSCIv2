<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Services\ParentChatbot\ParentChatbotE2EHarness;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParentChatbotE2EHarnessTest extends TestCase
{
    /** @test */
    public function it_rejects_parent_chatbot_e2e_phones_that_are_not_active_test_recipients(): void
    {
        $this->configureSecrets();
        $this->allowTestPhone('+2250700000000');

        $this->expectException(ValidationException::class);

        app(ParentChatbotE2EHarness::class)->invokeSignedInbound(
            'codex-e2e-chatbot-a2',
            'codex-e2e-chatbot-a2-notes',
            '+2250500000000',
            'NOTES',
        );
    }

    /** @test */
    public function it_sends_the_inbound_test_through_the_signed_public_webhook(): void
    {
        $this->configureSecrets();
        $this->allowTestPhone('+2250700000000');
        $secret = str_repeat('w', 32);
        config()->set('services.mailpulse.parent_chatbot_webhook_secret', $secret);

        Http::fake([
            url('/api/v1/integrations/mailpulse/parent-chatbot/inbound') => Http::response(['accepted' => true], 202),
        ]);

        $result = app(ParentChatbotE2EHarness::class)->invokeSignedInbound(
            'codex-e2e-chatbot-a3',
            'codex-e2e-chatbot-a3-notes',
            '+2250700000000',
            'NOTES',
        );

        $this->assertTrue($result['ok']);
        $this->assertSame(202, $result['http_status']);
        Http::assertSent(function (Request $request) use ($secret): bool {
            $timestamp = $request->header('X-MailPulse-Timestamp')[0] ?? null;
            $signature = $request->header('X-MailPulse-Signature')[0] ?? null;

            return $request->url() === url('/api/v1/integrations/mailpulse/parent-chatbot/inbound')
                && is_string($timestamp)
                && is_string($signature)
                && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->body(), $secret), $signature);
        });
    }

    private function configureSecrets(): void
    {
        config()->set('services.mailpulse.parent_chatbot_code_pepper', str_repeat('c', 32));
        config()->set('services.mailpulse.parent_chatbot_phone_hash_key', str_repeat('p', 32));
        config()->set('services.mailpulse.parent_chatbot_webhook_secret', str_repeat('w', 32));
    }

    private function allowTestPhone(string $phone): void
    {
        config()->set('services.mailpulse.test_notification_phone', $phone);
    }
}
