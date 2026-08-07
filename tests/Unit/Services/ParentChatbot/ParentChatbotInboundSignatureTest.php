<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Services\ParentChatbot\ParentChatbotInboundSignature;
use Illuminate\Http\Request;
use Tests\TestCase;

class ParentChatbotInboundSignatureTest extends TestCase
{
    private const SECRET = 'parent-chatbot-test-secret-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

    private const KEY_ID = 'fk_test';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.mailpulse.external_callback_key_id', self::KEY_ID);
        config()->set('services.mailpulse.external_callback_secret', self::SECRET);
        config()->set('services.mailpulse.parent_chatbot_signature_ttl', 300);
    }

    public function test_it_accepts_a_current_versioned_signature(): void
    {
        $request = $this->signedRequest('{"event_id":"evt-1"}', time());

        $this->assertTrue(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_a_tampered_payload(): void
    {
        $timestamp = time();
        $request = $this->signedRequest('{"event_id":"evt-2"}', $timestamp);
        $request->initialize([], [], [], [], [], [], '{"event_id":"changed"}');
        $request->setMethod('POST');
        $request->headers->set('x-external-timestamp', (string) $timestamp);
        $request->headers->set('x-external-signature', $this->signature($timestamp, '{"event_id":"evt-2"}'));

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_an_expired_event_even_with_a_valid_signature(): void
    {
        $request = $this->signedRequest('{"event_id":"evt-3"}', time() - 301);

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_a_signature_issued_for_another_key(): void
    {
        $timestamp = time();
        $content = '{"event_id":"evt-4"}';
        $request = $this->request($content);
        $request->headers->set('x-external-timestamp', (string) $timestamp);
        $request->headers->set('x-external-signature', 'v1:fk_other='.hash_hmac('sha256', $timestamp.'.'.$content, self::SECRET));

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_an_unversioned_legacy_signature(): void
    {
        $timestamp = time();
        $content = '{"event_id":"evt-5"}';
        $request = $this->request($content);
        $request->headers->set('x-external-timestamp', (string) $timestamp);
        $request->headers->set('x-external-signature', hash_hmac('sha256', $timestamp.'.'.$content, self::SECRET));

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($request));
    }

    public function test_it_rejects_a_request_without_a_configured_key_id(): void
    {
        config()->set('services.mailpulse.external_callback_key_id', '');

        $this->assertFalse(app(ParentChatbotInboundSignature::class)->isValid($this->signedRequest('{"event_id":"evt-6"}', time())));
    }

    private function signedRequest(string $content, int $timestamp): Request
    {
        $request = $this->request($content);
        $request->headers->set('x-external-timestamp', (string) $timestamp);
        $request->headers->set('x-external-signature', $this->signature($timestamp, $content));

        return $request;
    }

    private function request(string $content): Request
    {
        $request = Request::create('/api/v1/integrations/mailpulse/parent-chatbot/inbound', 'POST', [], [], [], [], $content);
        $request->headers->set('Content-Type', 'application/json');

        return $request;
    }

    private function signature(int $timestamp, string $content): string
    {
        return 'v1:'.self::KEY_ID.'='.hash_hmac('sha256', $timestamp.'.'.$content, self::SECRET);
    }
}
