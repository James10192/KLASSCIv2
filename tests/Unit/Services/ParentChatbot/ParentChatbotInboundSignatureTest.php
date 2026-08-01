<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Services\ParentChatbot\ParentChatbotInboundSignature;
use Illuminate\Http\Request;
use Tests\TestCase;

class ParentChatbotInboundSignatureTest extends TestCase
{
    private const SECRET = 'parent-chatbot-test-secret-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.mailpulse.parent_chatbot_webhook_secret', self::SECRET);
        config()->set('services.mailpulse.parent_chatbot_signature_ttl', 300);
    }

    public function test_it_accepts_a_current_hmac_signed_event(): void
    {
        $request = $this->signedRequest('{"event_id":"evt-1"}', time());

        $this->assertTrue(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_a_tampered_payload(): void
    {
        $timestamp = time();
        $request = $this->signedRequest('{"event_id":"evt-2"}', $timestamp);
        $request->setMethod('POST');
        $request->initialize([], [], [], [], [], [], '{"event_id":"changed"}');
        $request->headers->set('X-MailPulse-Timestamp', (string) $timestamp);
        $request->headers->set('X-MailPulse-Signature', hash_hmac('sha256', $timestamp . '.{"event_id":"evt-2"}', self::SECRET));

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_an_expired_event_even_with_a_valid_signature(): void
    {
        $request = $this->signedRequest('{"event_id":"evt-3"}', time() - 301);

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    private function signedRequest(string $content, int $timestamp): Request
    {
        $request = Request::create('/api/v1/integrations/mailpulse/parent-chatbot/inbound', 'POST', [], [], [], [], $content);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-MailPulse-Timestamp', (string) $timestamp);
        $request->headers->set('X-MailPulse-Signature', hash_hmac('sha256', $timestamp . '.' . $content, self::SECRET));

        return $request;
    }
}
