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
    private const COMMANDS_URL = 'https://mailpulse.test/api/v1/external-applications/klassci/commands';

    private const SECRET = 'external-command-secret-xxxxxxxxxxxxxxxxxxxxxxxx';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.tenant_code', 'tenant-a');
    }

    public function test_it_submits_text_replies_on_the_external_application_rail(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => $this->acceptedResponse('op-1')]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::PublishedGrades, 'evt-1');

        $this->assertTrue($outcome->isAccepted());
        $this->assertSame('op-1', $outcome->commandId);
        $this->assertFalse($outcome->reconciliationRequired);
        Http::assertSent(fn (Request $request) => $request->url() === self::COMMANDS_URL
            && $request['operation_key'] === ParentChatbotDispatcher::OPERATION_REPLY
            && $request['channel'] === 'whatsapp'
            && $request['recipient'] === ['type' => 'phone', 'value' => '+2250707123456']
            && $request['content'] === ['type' => 'text', 'text' => 'Sensitive data']
            && str_starts_with((string) $request['metadata']['idempotency_key'], 'klassci-tenant-a-parent-chatbot-'));
    }

    public function test_it_signs_the_exact_bytes_it_sends(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => $this->acceptedResponse('op-2')]);

        app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Bonjour', ParentChatbotIntent::Link, 'evt-2');

        Http::assertSent(function (Request $request): bool {
            $timestamp = $request->header('x-external-timestamp')[0];
            $expected = 'v1:ck_test=' . hash_hmac('sha256', $timestamp . '.' . $request->body(), self::SECRET);

            return $request->hasHeader('x-external-organization-id', 'org_test')
                && ctype_digit($timestamp)
                && $request->header('x-external-signature')[0] === $expected;
        });
    }

    public function test_it_sends_templates_without_leaking_the_local_template_name(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => $this->acceptedResponse('op-3')]);

        app(ParentChatbotDispatcher::class)->dispatchTemplate(
            '+2250707123456',
            'klassci_parent_link_code',
            'fr',
            ['ABCDEF1234567890'],
            ParentChatbotIntent::Link,
            'event-link-1',
            'request-link-1',
        );

        Http::assertSent(fn (Request $request) => $request['operation_key'] === ParentChatbotDispatcher::OPERATION_LINK_CODE
            && $request['content'] === ['type' => 'template', 'locale' => 'fr', 'parameters' => ['ABCDEF1234567890']]
            && $request['metadata']['idempotency_key'] === 'klassci-tenant-a-request-link-1'
            && $request->header('X-KLASSCI-Request-Id')[0] === 'klassci-tenant-a-request-link-1');
    }

    public function test_it_dead_letters_a_reply_rejected_by_a_closed_service_window(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => Http::response([
            'accepted' => false,
            'operation_id' => 'op-closed',
            'dispatch_state' => 'rejected',
            'rejection_code' => 'whatsapp_service_window_closed',
            'reconciliation_required' => false,
        ], 422)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Trop tard', ParentChatbotIntent::Link, 'event-closed');

        $this->assertTrue($outcome->isDeadLettered());
        $this->assertFalse($outcome->isAccepted());
        $this->assertFalse($outcome->isPendingReconciliation());
        $this->assertFalse($outcome->reconciliationRequired);
    }

    public function test_it_dead_letters_a_rejection_whose_code_was_lost_on_replay(): void
    {
        // MailPulse replays an already rejected command without its original
        // rejection_code. Keying the dead-letter on the code would send those
        // events back into the retry loop the dead-letter exists to stop.
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => Http::response([
            'accepted' => false,
            'operation_id' => 'op-rejected',
            'dispatch_state' => 'rejected',
            'rejection_code' => 'provider_rejected',
            'reconciliation_required' => false,
        ], 422)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Refuse', ParentChatbotIntent::Link, 'event-rejected');

        $this->assertTrue($outcome->isDeadLettered());
        $this->assertFalse($outcome->isAccepted());
        $this->assertFalse($outcome->isPendingReconciliation());
    }

    public function test_it_preserves_pending_dispatch_as_pending_reconciliation(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => Http::response([
            'accepted' => false,
            'operation_id' => 'op-unknown',
            'dispatch_state' => 'pending_reconciliation',
            'reconciliation_required' => true,
        ], 202)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::Link, 'event-unknown');

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertSame('op-unknown', $outcome->commandId);
        $this->assertTrue($outcome->reconciliationRequired);
    }

    public function test_it_marks_a_connection_exception_as_submission_unknown(): void
    {
        $this->configureDispatcher();
        Http::fake(function (): void {
            throw new ConnectionException('Connection timed out.');
        });

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::Link, 'event-timeout');

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertNotNull($outcome->commandId);
    }

    /**
     * @dataProvider ambiguousSubmissionStatuses
     */
    public function test_it_marks_ambiguous_http_responses_as_submission_unknown(int $status): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => Http::response([], $status)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::Link, 'event-http-'.$status);

        $this->assertTrue($outcome->isPendingReconciliation());
        $this->assertTrue($outcome->reconciliationRequired);
    }

    public static function ambiguousSubmissionStatuses(): array
    {
        return [[408], [429], [500]];
    }

    public function test_it_keeps_definitive_client_errors_as_failed(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => Http::response([], 400)]);

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::Link, 'event-bad-request');

        $this->assertFalse($outcome->isAccepted());
        $this->assertFalse($outcome->isPendingReconciliation());
        $this->assertFalse($outcome->isDeadLettered());
    }

    public function test_it_refuses_a_recipient_it_cannot_express_in_e164(): void
    {
        $this->configureDispatcher();
        Http::fake();

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('0707123456', 'Sensitive data', ParentChatbotIntent::Link, 'event-local-number');

        $this->assertFalse($outcome->isAccepted());
        Http::assertNothingSent();
    }

    public function test_it_bounds_a_reply_to_the_whatsapp_text_limit(): void
    {
        $this->configureDispatcher();
        Http::fake([self::COMMANDS_URL => $this->acceptedResponse('op-long')]);

        app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', str_repeat('a', 5000), ParentChatbotIntent::Link, 'event-long');

        Http::assertSent(fn (Request $request) => mb_strlen($request['content']['text']) === 4096
            && str_ends_with($request['content']['text'], '…'));
    }

    public function test_it_refuses_to_send_when_the_external_application_is_not_provisioned(): void
    {
        $this->configureDispatcher();
        config()->set('services.mailpulse.external_command_secret', '');
        Http::fake();

        $outcome = app(ParentChatbotDispatcher::class)->dispatch('+2250707123456', 'Sensitive data', ParentChatbotIntent::Link, 'event-unconfigured');

        $this->assertFalse($outcome->isAccepted());
        Http::assertNothingSent();
    }

    private function acceptedResponse(string $operationId): \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            'accepted' => true,
            'operation_id' => $operationId,
            'dispatch_state' => 'accepted',
            'reconciliation_required' => false,
        ], 202);
    }

    private function configureDispatcher(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.timeout', 20);
        config()->set('services.mailpulse.external_application_key', 'klassci');
        config()->set('services.mailpulse.external_organization_id', 'org_test');
        config()->set('services.mailpulse.external_command_key_id', 'ck_test');
        config()->set('services.mailpulse.external_command_secret', self::SECRET);
    }
}
