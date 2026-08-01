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

    public function test_it_uses_the_dedicated_mailpulse_dispatch_endpoint(): void
    {
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.parent_chatbot_service_secret', str_repeat('s', 32));
        config()->set('services.mailpulse.parent_chatbot_dispatch_endpoint', '/api/v1/parent-chatbot/dispatch');
        config()->set('services.mailpulse.timeout', 20);
        Http::fake(['mailpulse.test/api/v1/parent-chatbot/dispatch' => Http::response([
            'command_id' => 'out-1',
            'dispatch_state' => 'accepted',
            'reconciliation_required' => false,
        ], 202)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::PublishedGrades, 'evt-1');

        $this->assertTrue($outcome->isAccepted());
        $this->assertSame('out-1', $outcome->commandId);
        $this->assertFalse($outcome->reconciliationRequired);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/parent-chatbot/dispatch'
            && ! $request->hasHeader('Authorization')
            && $request->hasHeader('X-KLASSCI-Service-Timestamp')
            && $request->hasHeader('Idempotency-Key')
            && hash_equals(
                hash_hmac('sha256', implode('', (array) $request->header('X-KLASSCI-Service-Timestamp')).'.'.$request->body(), str_repeat('s', 32)),
                implode('', (array) $request->header('X-KLASSCI-Service-Signature'))
            )
            && json_decode($request->body(), true)['channel'] === 'whatsapp'
            && json_decode($request->body(), true)['metadata']['source'] === 'klassci_parent_chatbot'
            && json_decode($request->body(), true)['metadata']['intent'] === 'published_grades');
    }

    public function test_it_uses_a_supplied_request_id_for_mailpulse_idempotency(): void
    {
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.parent_chatbot_service_secret', str_repeat('s', 32));
        config()->set('services.mailpulse.parent_chatbot_dispatch_endpoint', '/api/v1/parent-chatbot/dispatch');
        Http::fake(['mailpulse.test/api/v1/parent-chatbot/dispatch' => Http::response([
            'command_id' => 'out-2',
            'dispatch_state' => 'accepted',
            'reconciliation_required' => false,
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
            && json_decode($request->body(), true)['metadata']['event_id'] === 'event-link-1'
            && json_decode($request->body(), true)['metadata']['tenant_code'] === 'tenant-a'
            && json_decode($request->body(), true)['content']['type'] === 'template'
            && json_decode($request->body(), true)['content']['template_name'] === 'klassci_parent_link_code'
            && json_decode($request->body(), true)['content']['parameters'] === ['ABCDEF1234567890']);
    }

    public function test_it_preserves_a_submission_unknown_as_pending_reconciliation(): void
    {
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.parent_chatbot_service_secret', str_repeat('s', 32));
        config()->set('services.mailpulse.parent_chatbot_dispatch_endpoint', '/api/v1/parent-chatbot/dispatch');
        Http::fake(['mailpulse.test/api/v1/parent-chatbot/dispatch' => Http::response([
            'command_id' => 'out-unknown',
            'dispatch_state' => 'pending_reconciliation',
            'reconciliation_required' => true,
        ], 202)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch(
            '+2250707123456',
            'Sensitive data',
            ParentChatbotIntent::Link,
            'event-unknown',
        );

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertSame('out-unknown', $outcome->commandId);
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
        $this->assertNull($outcome->commandId);
        $this->assertTrue($outcome->reconciliationRequired);
    }

    /**
     * @dataProvider ambiguousSubmissionStatuses
     */
    public function test_it_marks_ambiguous_http_responses_as_submission_unknown(int $status): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/parent-chatbot/dispatch' => Http::response([], $status)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch(
            '+2250707123456',
            'Sensitive data',
            ParentChatbotIntent::Link,
            'event-http-'.$status,
        );

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertNull($outcome->commandId);
        $this->assertTrue($outcome->reconciliationRequired);
    }

    public static function ambiguousSubmissionStatuses(): array
    {
        return [[408], [429], [500]];
    }

    public function test_it_keeps_definitive_client_errors_as_failed(): void
    {
        $this->configureDispatcher();
        Http::fake(['mailpulse.test/api/v1/parent-chatbot/dispatch' => Http::response([], 400)]);

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
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.parent_chatbot_service_secret', str_repeat('s', 32));
        config()->set('services.mailpulse.parent_chatbot_dispatch_endpoint', '/api/v1/parent-chatbot/dispatch');
        config()->set('services.mailpulse.timeout', 20);
    }
}
