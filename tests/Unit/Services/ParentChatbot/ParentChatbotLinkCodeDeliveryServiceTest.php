<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use App\Models\ESBTPParent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCode;
use App\Models\ParentChatbotLinkCodeIssuance;
use App\Models\Setting;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use App\Services\ParentChatbot\ParentChatbotDispatcher;
use App\Services\ParentChatbot\ParentChatbotDispatchOutcome;
use App\Services\ParentChatbot\ParentChatbotLinkCodeDeliveryService;
use App\Services\ParentChatbot\ParentChatbotLinkService;
use App\Services\ParentChatbot\ParentChatbotPhoneNormalizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ParentChatbotLinkCodeDeliveryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        config()->set('services.mailpulse.parent_chatbot_code_pepper', str_repeat('c', 32));
        config()->set('services.mailpulse.parent_chatbot_link_code_ttl', 15);
        config()->set('services.mailpulse.parent_chatbot_link_template_name', 'klassci_parent_link_code');
        config()->set('services.mailpulse.parent_chatbot_link_template_language', 'fr');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_parents', function (Blueprint $table): void {
            $table->id();
            $table->string('telephone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_etudiant_parent', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->boolean('is_tuteur')->default(false);
            $table->string('relation')->nullable();
            $table->timestamps();
        });
        Schema::create('parent_chatbot_link_codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->char('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('parent_chatbot_link_code_issuances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('parent_chatbot_link_code_id')->nullable();
            $table->string('request_id', 100)->unique();
            $table->string('provider_command_id', 100)->nullable();
            $table->string('status', 32);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('manual_reconciliation_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('delivery_payload')->nullable();
            $table->timestamp('delivery_payload_expires_at')->nullable();
            $table->uuid('delivery_token')->nullable();
            $table->timestamp('delivery_started_at')->nullable();
            $table->timestamp('delivery_lease_expires_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('retry_expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('parent_chatbot_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->char('phone_hash', 64);
            $table->string('status', 20);
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_records_a_pii_safe_accepted_issuance_with_a_stable_dispatch_id(): void
    {
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->withArgs(function (
                string $phone,
                string $templateName,
                string $languageCode,
                array $parameters,
                ParentChatbotIntent $intent,
                string $eventId,
                string $requestId
            ): bool {
                return $phone === '+2250707123456'
                    && $templateName === 'klassci_parent_link_code'
                    && $languageCode === 'fr'
                    && count($parameters) === 1
                    && preg_match('/^[A-F0-9]{16}$/', $parameters[0]) === 1
                    && $intent === ParentChatbotIntent::Link
                    && $eventId === 'req-link-1'
                    && $requestId === 'req-link-1';
            })
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-accepted'));

        $issuance = $this->service($dispatcher)->issueAndDeliver($parent, 42, 'req-link-1');

        $this->assertSame(42, $issuance->actor_id);
        $this->assertSame('req-link-1', $issuance->request_id);
        $this->assertNotNull($issuance->parent_chatbot_link_code_id);
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED, $issuance->status);
        $this->assertSame(1, $issuance->attempt_count);
        $this->assertNotNull($issuance->attempted_at);
        $this->assertNotNull($issuance->accepted_at);
        $this->assertNull($issuance->error_code);
        $this->assertSame(1, ParentChatbotLinkCode::query()->count());
        $this->assertNotContains('phone', Schema::getColumnListing('parent_chatbot_link_code_issuances'));
        $this->assertNotContains('message', Schema::getColumnListing('parent_chatbot_link_code_issuances'));
        $this->assertNotContains('code', Schema::getColumnListing('parent_chatbot_link_code_issuances'));
    }

    public function test_it_reuses_a_live_accepted_issuance_without_invalidating_or_resending_its_code(): void
    {
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')->once()->andReturn(ParentChatbotDispatchOutcome::accepted('out-accepted'));
        $service = $this->service($dispatcher);

        $first = $service->issueAndDeliver($parent, 10, 'req-link-first');
        $second = $service->issueAndDeliver($parent, 11, 'req-link-second');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED, $second->status);
        $this->assertSame(1, ParentChatbotLinkCodeIssuance::query()->count());
        $this->assertSame(1, ParentChatbotLinkCode::query()->count());
        $this->assertNull(ParentChatbotLinkCode::query()->firstOrFail()->consumed_at);
    }

    public function test_it_reuses_a_live_pending_issuance_without_sending_a_code_that_another_request_could_invalidate(): void
    {
        $parent = $this->parentWithPupil();
        $linkCode = ParentChatbotLinkCode::create([
            'parent_id' => $parent->id,
            'code_hash' => hash('sha256', 'pending-link-code'),
            'expires_at' => now()->addMinutes(15),
        ]);
        $pending = ParentChatbotLinkCodeIssuance::create([
            'parent_id' => $parent->id,
            'actor_id' => 10,
            'parent_chatbot_link_code_id' => $linkCode->id,
            'request_id' => 'req-link-pending',
            'status' => ParentChatbotLinkCodeIssuance::STATUS_PENDING,
        ]);
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');

        $reused = $this->service($dispatcher)->issueAndDeliver($parent, 11, 'req-link-concurrent');

        $this->assertSame($pending->id, $reused->id);
        $this->assertSame(0, $reused->attempt_count);
        $this->assertSame(1, ParentChatbotLinkCodeIssuance::query()->count());
        $this->assertSame(1, ParentChatbotLinkCode::query()->count());
    }

    public function test_it_persists_submission_unknown_as_pending_reconciliation(): void
    {
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::pendingReconciliation('out-unknown'));

        $issuance = $this->service($dispatcher)->issueAndDeliver($parent, 42, 'req-link-unknown');

        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION, $issuance->status);
        $this->assertSame(1, $issuance->attempt_count);
        $this->assertNotNull($issuance->attempted_at);
        $this->assertNull($issuance->accepted_at);
        $this->assertNull($issuance->failed_at);
        $this->assertSame('submission_unknown', $issuance->error_code);
        $this->assertSame('out-unknown', $issuance->provider_command_id);
        $this->assertNotNull($issuance->delivery_payload);
    }

    public function test_it_retries_a_pending_reconciliation_issuance_with_the_original_request_id(): void
    {
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::pendingReconciliation('out-unknown'));
        $service = $this->service($dispatcher);

        $first = $service->issueAndDeliver($parent, 42, 'req-link-retry');
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION, $first->status);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->withArgs(fn (
                string $phone,
                string $templateName,
                string $languageCode,
                array $parameters,
                ParentChatbotIntent $intent,
                string $eventId,
                string $requestId
            ): bool => $eventId === 'req-link-retry' && $requestId === 'req-link-retry')
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-accepted'));

        $first->update(['next_attempt_at' => now()->subSecond()]);
        $processed = $this->service($dispatcher)->reconcilePending();
        $retried = $first->fresh();

        $this->assertSame(1, $processed);
        $this->assertSame($first->id, $retried->id);
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED, $retried->status);
        $this->assertSame(2, $retried->attempt_count);
        $this->assertNull($retried->delivery_payload);
        $this->assertNull($retried->error_code);
        $this->assertSame('out-accepted', $retried->provider_command_id);
    }

    public function test_repeated_ambiguous_submissions_end_in_manual_reconciliation_without_false_failure(): void
    {
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $sequence = 0;
        $dispatcher->shouldReceive('dispatchTemplate')->times(5)->andReturnUsing(
            function () use (&$sequence): ParentChatbotDispatchOutcome {
                $sequence++;

                return ParentChatbotDispatchOutcome::pendingReconciliation('out-unknown-'.$sequence);
            }
        );
        $service = $this->service($dispatcher);
        $issuance = $service->issueAndDeliver($parent, 42, 'req-link-manual');

        for ($attempt = 2; $attempt <= 5; $attempt++) {
            $issuance->update(['next_attempt_at' => now()->subSecond()]);
            $service->reconcilePending();
            $issuance = $issuance->fresh();
        }

        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_MANUAL_RECONCILIATION, $issuance->status);
        $this->assertSame('submission_unknown_retry_exhausted', $issuance->error_code);
        $this->assertSame('out-unknown-5', $issuance->provider_command_id);
        $this->assertNotNull($issuance->manual_reconciliation_at);
        $this->assertNull($issuance->failed_at);
        $this->assertNull($issuance->delivery_payload);
    }

    public function test_reconciler_cancels_a_pending_issuance_when_the_parent_stopped_the_chatbot(): void
    {
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::pendingReconciliation('out-unknown'));
        $first = $this->service($dispatcher)->issueAndDeliver($parent, 42, 'req-link-stopped-retry');
        ParentChatbotLink::create([
            'parent_id' => $parent->id,
            'phone_hash' => str_repeat('b', 64),
            'status' => ParentChatbotLink::STATUS_STOPPED,
            'stopped_at' => now(),
        ]);
        $first->update(['next_attempt_at' => now()->subSecond()]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');
        $processed = $this->service($dispatcher)->reconcilePending();

        $this->assertSame(1, $processed);
        $first = $first->fresh();
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_FAILED, $first->status);
        $this->assertSame('link_stopped', $first->error_code);
        $this->assertNull($first->delivery_payload);
    }

    public function test_it_replaces_a_revoked_link_when_issuing_a_code_for_an_updated_phone(): void
    {
        $parent = $this->parentWithPupil();
        ParentChatbotLink::create([
            'parent_id' => $parent->id,
            'phone_hash' => str_repeat('d', 64),
            'status' => ParentChatbotLink::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-reissued'));

        $issuance = $this->service($dispatcher)->issueAndDeliver($parent, 42, 'req-link-revoked-phone');

        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED, $issuance->status);
        $this->assertSame(1, ParentChatbotLinkCode::query()->count());
    }

    public function test_it_clears_the_delivery_payload_after_a_definitive_dispatch_failure(): void
    {
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatchTemplate')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::failed());

        $issuance = $this->service($dispatcher)->issueAndDeliver($this->parentWithPupil(), 42, 'req-link-failed');

        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_FAILED, $issuance->status);
        $this->assertSame('dispatch_failed', $issuance->error_code);
        $this->assertNull($issuance->delivery_payload);
        $this->assertNull($issuance->delivery_payload_expires_at);
    }

    public function test_reconciler_fails_an_expired_pending_issuance_without_dispatching(): void
    {
        $parent = $this->parentWithPupil();
        $linkCode = ParentChatbotLinkCode::create([
            'parent_id' => $parent->id,
            'code_hash' => hash('sha256', 'expired-link-code'),
            'expires_at' => now()->subMinute(),
        ]);
        ParentChatbotLinkCodeIssuance::create([
            'parent_id' => $parent->id,
            'actor_id' => 42,
            'parent_chatbot_link_code_id' => $linkCode->id,
            'request_id' => 'req-link-expired',
            'status' => ParentChatbotLinkCodeIssuance::STATUS_PENDING,
            'delivery_payload' => 'encrypted-payload-not-used',
            'delivery_payload_expires_at' => now()->subMinute(),
        ]);
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');

        $processed = $this->service($dispatcher)->reconcilePending();
        $issuance = ParentChatbotLinkCodeIssuance::query()->sole();

        $this->assertSame(1, $processed);
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_FAILED, $issuance->status);
        $this->assertSame('link_code_expired', $issuance->error_code);
        $this->assertNull($issuance->delivery_payload);
    }

    public function test_it_fails_without_sending_when_the_link_template_is_not_configured(): void
    {
        config()->set('services.mailpulse.parent_chatbot_link_template_name', '');
        $parent = $this->parentWithPupil();
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');

        $issuance = $this->service($dispatcher)->issueAndDeliver($parent, 42, 'req-link-template-missing');

        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_FAILED, $issuance->status);
        $this->assertSame('link_template_not_configured', $issuance->error_code);
        $this->assertSame(1, $issuance->attempt_count);
        $this->assertNotNull($issuance->failed_at);
        $this->assertNull($issuance->delivery_payload);
    }

    public function test_it_persists_a_blocked_audit_record_when_a_parent_has_stopped_the_chatbot(): void
    {
        $parent = $this->parentWithPupil();
        ParentChatbotLink::create([
            'parent_id' => $parent->id,
            'phone_hash' => str_repeat('a', 64),
            'status' => ParentChatbotLink::STATUS_STOPPED,
            'stopped_at' => now(),
        ]);
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');

        try {
            $this->service($dispatcher)->issueAndDeliver($parent, 9, 'req-link-stopped');
            $this->fail('A stopped parent must not receive a link code.');
        } catch (InvalidArgumentException) {
        }

        $issuance = ParentChatbotLinkCodeIssuance::query()->sole();
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_BLOCKED, $issuance->status);
        $this->assertSame('link_stopped', $issuance->error_code);
        $this->assertNull($issuance->parent_chatbot_link_code_id);
    }

    public function test_it_persists_a_blocked_audit_record_when_real_workflows_are_disabled(): void
    {
        config()->set('services.mailpulse.real_workflows_enabled', false);
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');

        try {
            $this->service($dispatcher)->issueAndDeliver($this->parentWithPupil(), 7, 'req-link-disabled');
            $this->fail('Disabled workflows must reject code delivery.');
        } catch (RuntimeException) {
        }

        $issuance = ParentChatbotLinkCodeIssuance::query()->sole();
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_BLOCKED, $issuance->status);
        $this->assertSame('workflows_disabled', $issuance->error_code);
    }

    public function test_it_uses_admin_settings_to_block_real_workflows_even_when_config_is_enabled(): void
    {
        config()->set('services.mailpulse.real_workflows_enabled', true);
        Setting::create([
            'key' => 'mailpulse_real_workflows_enabled',
            'value' => '0',
            'type' => 'boolean',
            'is_active' => true,
        ]);
        Cache::forget('setting_mailpulse_real_workflows_enabled');
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchTemplate');

        try {
            $this->service($dispatcher)->issueAndDeliver($this->parentWithPupil(), 7, 'req-link-settings-disabled');
            $this->fail('Disabled admin settings must reject code delivery.');
        } catch (RuntimeException) {
        }

        $issuance = ParentChatbotLinkCodeIssuance::query()->sole();
        $this->assertSame(ParentChatbotLinkCodeIssuance::STATUS_BLOCKED, $issuance->status);
        $this->assertSame('workflows_disabled', $issuance->error_code);
    }

    private function service(ParentChatbotDispatcher $dispatcher): ParentChatbotLinkCodeDeliveryService
    {
        $phones = new ParentChatbotPhoneNormalizer;

        return new ParentChatbotLinkCodeDeliveryService(
            new ParentChatbotLinkService($phones),
            $phones,
            $dispatcher,
            new MailPulseWorkflowPolicy(new \App\Services\ParentChatbot\ParentChatbotPublicationPolicy),
        );
    }

    private function parentWithPupil(): ESBTPParent
    {
        $parent = ESBTPParent::create(['telephone' => '07 07 12 34 56']);
        $studentId = DB::table('esbtp_etudiants')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('esbtp_etudiant_parent')->insert([
            'parent_id' => $parent->id,
            'etudiant_id' => $studentId,
            'is_tuteur' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $parent;
    }
}
