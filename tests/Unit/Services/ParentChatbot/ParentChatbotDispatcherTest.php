<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use App\Services\ParentChatbot\ParentChatbotDispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentChatbotDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.tenant_code', 'tenant-a');
    }

    public function test_it_submits_text_replies_through_the_standard_mailpulse_messages_api(): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg-1', 'status' => 'sent'],
        ], 202)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::PublishedGrades, 'evt-1');

        $this->assertTrue($outcome->isAccepted());
        $this->assertSame('msg-1', $outcome->commandId);
        $this->assertFalse($outcome->reconciliationRequired);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/messages'
            && $request->hasHeader('Authorization', 'Bearer mp_test_'.str_repeat('a', 32))
            && $request->hasHeader('Idempotency-Key')
            && $request['channel'] === 'whatsapp'
            && $request['content']['type'] === 'text'
            && $request['content']['text'] === 'Sensitive data'
            && $request['metadata']['source'] === 'klassci_parent_chatbot'
            && $request['metadata']['intent'] === 'published_grades');
    }

    public function test_it_uses_a_supplied_request_id_for_mailpulse_idempotency(): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg-2', 'status' => 'sent'],
        ], 202)]);

        app(ParentChatbotDispatcher::class)->dispatchTemplate(
            '+2250707123456',
            'klassci_parent_link_code',
            'fr',
            ['ABCDEF1234567890'],
            ParentChatbotIntent::Link,
            'event-link-1',
            'request-link-1',
        );

        Http::assertSent(fn (Request $request) => $request->header('Idempotency-Key')[0] === 'klassci-tenant-a-request-link-1'
            && $request->header('X-KLASSCI-Request-Id')[0] === 'klassci-tenant-a-request-link-1'
            && $request['metadata']['event_id'] === 'event-link-1'
            && $request['metadata']['tenant_code'] === 'tenant-a'
            && $request['content']['type'] === 'template'
            && $request['content']['template_key'] === 'klassci_parent_link_code'
            && $request['content']['locale'] === 'fr'
            && $request['content']['variables'] === ['1' => 'ABCDEF1234567890']);
    }

    public function test_it_preserves_pending_dispatch_as_pending_reconciliation(): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'pending_reconciliation', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg-unknown', 'status' => 'queued'],
        ], 202)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch(
            '+2250707123456',
            'Sensitive data',
            ParentChatbotIntent::Link,
            'event-unknown',
        );

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertSame('msg-unknown', $outcome->commandId);
        $this->assertTrue($outcome->reconciliationRequired);
    }

    public function test_it_marks_a_connection_exception_as_submission_unknown(): void
    {
        $this->configureDispatcher();
        Http::fake(function (): void {
            throw new ConnectionException('Connection timed out.');
        });

        $outcome = app(ParentChatbotDispatcher::class)->dispatch(
            '+2250707123456',
            'Sensitive data',
            ParentChatbotIntent::Link,
            'event-timeout',
        );

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertNotNull($outcome->commandId);
        $this->assertTrue($outcome->reconciliationRequired);
    }

    /**
     * @dataProvider ambiguousSubmissionStatuses
     */
    public function test_it_marks_ambiguous_http_responses_as_submission_unknown(int $status): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([], $status)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch(
            '+2250707123456',
            'Sensitive data',
            ParentChatbotIntent::Link,
            'event-http-'.$status,
        );

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertNotNull($outcome->commandId);
        $this->assertTrue($outcome->reconciliationRequired);
    }

    public static function ambiguousSubmissionStatuses(): array
    {
        return [[408], [429], [500]];
    }

    public function test_it_keeps_definitive_client_errors_as_failed(): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([], 400)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch(
            '+2250707123456',
            'Sensitive data',
            ParentChatbotIntent::Link,
            'event-bad-request',
        );

        $this->assertFalse($outcome->isAccepted());
        $this->assertFalse($outcome->isPendingReconciliation());
        $this->assertFalse($outcome->reconciliationRequired);
    }

    private function configureDispatcher(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'mp_test_'.str_repeat('a', 32));
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.timeout', 20);
    }
}
