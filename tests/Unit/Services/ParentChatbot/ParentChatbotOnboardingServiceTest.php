<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Models\ESBTPParent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCodeIssuance;
use App\Models\ParentChatbotOnboardingBatch;
use App\Models\ParentChatbotOnboardingItem;
use App\Services\ParentChatbot\ParentChatbotLinkCodeDeliveryService;
use App\Services\ParentChatbot\ParentChatbotOnboardingService;
use App\Services\ParentChatbot\ParentChatbotPhoneNormalizer;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ParentChatbotOnboardingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.tenant_code', 'tenant-a');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        config()->set('services.mailpulse.parent_chatbot_link_template_name', 'klassci_parent_link_code');
        config()->set('services.mailpulse.parent_chatbot_service_secret', str_repeat('s', 32));
        config()->set('services.mailpulse.parent_chatbot_code_pepper', str_repeat('p', 32));
        config()->set('services.mailpulse.parent_chatbot_phone_hash_key', str_repeat('h', 32));
        $this->createTables();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_snapshots_only_eligible_parents_and_excludes_active_or_stopped_links(): void
    {
        $eligible = $this->parentWithPupil('0700000001');
        $revoked = $this->parentWithPupil('0700000002');
        $active = $this->parentWithPupil('0700000003');
        $stopped = $this->parentWithPupil('0700000004');
        ESBTPParent::create(['telephone' => '0700000005']);
        $this->parentWithPupil(null);

        ParentChatbotLink::create(['parent_id' => $revoked->id, 'phone_hash' => str_repeat('a', 64), 'status' => ParentChatbotLink::STATUS_REVOKED]);
        ParentChatbotLink::create(['parent_id' => $active->id, 'phone_hash' => str_repeat('b', 64), 'status' => ParentChatbotLink::STATUS_ACTIVE]);
        ParentChatbotLink::create(['parent_id' => $stopped->id, 'phone_hash' => str_repeat('c', 64), 'status' => ParentChatbotLink::STATUS_STOPPED]);

        $batch = $this->service()->start(12);
        $parentIds = $batch->items()->pluck('parent_id')->sort()->values()->all();

        $this->assertSame(ParentChatbotOnboardingBatch::STATUS_PROCESSING, $batch->status);
        $this->assertSame(2, $batch->total_count);
        $this->assertSame([$eligible->id, $revoked->id], $parentIds);
        $this->assertSame("klassci-tenant-a-parent-onboarding-{$batch->id}-{$eligible->id}", $batch->items()->where('parent_id', $eligible->id)->value('request_id'));
    }

    public function test_it_rejects_a_second_start_while_a_batch_is_processing(): void
    {
        $this->parentWithPupil('0700000001');
        $service = $this->service();
        $service->start(12);

        $this->expectException(RuntimeException::class);
        $service->start(42);
    }

    /** @dataProvider invalidStartConfiguration */
    public function test_it_rejects_invalid_mailpulse_configuration_before_creating_a_snapshot(string $key, mixed $value): void
    {
        $this->parentWithPupil('0700000001');
        config()->set("services.mailpulse.{$key}", $value);

        try {
            $this->service()->start(12);
            $this->fail('Le démarrage doit être bloqué par la configuration MailPulse.');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, ParentChatbotOnboardingBatch::query()->count());
        $this->assertSame(0, ParentChatbotOnboardingItem::query()->count());
    }

    public static function invalidStartConfiguration(): array
    {
        return [
            'workflows disabled' => ['real_workflows_enabled', false],
            'missing link template' => ['parent_chatbot_link_template_name', ''],
            'short service secret' => ['parent_chatbot_service_secret', str_repeat('s', 31)],
            'short code pepper' => ['parent_chatbot_code_pepper', str_repeat('p', 31)],
            'short phone hash key' => ['parent_chatbot_phone_hash_key', str_repeat('h', 31)],
        ];
    }

    public function test_it_claims_an_expired_lease_and_maps_delivery_outcomes(): void
    {
        $accepted = $this->parentWithPupil('0700000001');
        $awaiting = $this->parentWithPupil('0700000002');
        $failed = $this->parentWithPupil('0700000003');
        $blocked = $this->parentWithPupil('0700000004');
        $delivery = Mockery::mock(ParentChatbotLinkCodeDeliveryService::class);
        $delivery->shouldReceive('issueAndDeliver')->times(4)->andReturnUsing(function (ESBTPParent $parent, ?int $actorId, string $requestId) use ($accepted, $awaiting, $failed, $blocked) {
            $status = match ($parent->id) {
                $accepted->id => ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED,
                $awaiting->id => ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION,
                $failed->id => ParentChatbotLinkCodeIssuance::STATUS_FAILED,
                default => ParentChatbotLinkCodeIssuance::STATUS_BLOCKED,
            };
            $issuance = ParentChatbotLinkCodeIssuance::create([
                'parent_id' => $parent->id,
                'actor_id' => $actorId,
                'request_id' => $requestId,
                'status' => $status,
                'error_code' => $status === ParentChatbotLinkCodeIssuance::STATUS_FAILED ? 'dispatch_failed' : ($status === ParentChatbotLinkCodeIssuance::STATUS_BLOCKED ? 'link_stopped' : null),
            ]);

            if ($parent->id === $blocked->id) {
                throw new InvalidArgumentException('Blocked');
            }

            return $issuance;
        });
        $service = $this->service($delivery);
        $batch = $service->start(12);
        $expired = $batch->items()->where('parent_id', $accepted->id)->firstOrFail();
        $expired->update([
            'status' => ParentChatbotOnboardingItem::STATUS_PROCESSING,
            'attempt_count' => 1,
            'lease_token' => 'expired-lease',
            'lease_expires_at' => now()->subMinute(),
        ]);

        $result = $service->process(25);
        $batch = $batch->fresh();

        $this->assertSame(['claimed' => 4, 'synced' => 0], $result);
        $this->assertSame(ParentChatbotOnboardingBatch::STATUS_PROCESSING, $batch->status);
        $this->assertSame(1, $batch->pending_count);
        $this->assertSame(1, $batch->accepted_count);
        $this->assertSame(1, $batch->failed_count);
        $this->assertSame(1, $batch->skipped_count);
        $this->assertSame(2, $expired->fresh()->attempt_count);
        $this->assertSame(ParentChatbotOnboardingItem::STATUS_AWAITING, $batch->items()->where('parent_id', $awaiting->id)->value('status'));
    }

    public function test_cancelling_skips_unclaimed_items_without_overwriting_a_submitted_claim(): void
    {
        $first = $this->parentWithPupil('0700000001');
        $second = $this->parentWithPupil('0700000002');
        $delivery = Mockery::mock(ParentChatbotLinkCodeDeliveryService::class);
        $delivery->shouldNotReceive('issueAndDeliver');
        $service = $this->service($delivery);
        $batch = $service->start(12);
        $batch->items()->where('parent_id', $first->id)->update([
            'status' => ParentChatbotOnboardingItem::STATUS_PROCESSING,
            'lease_token' => 'provider-submitted',
            'lease_expires_at' => now()->addMinute(),
        ]);

        $batch = $service->cancel($batch);
        $service->process(25);

        $this->assertSame(ParentChatbotOnboardingBatch::STATUS_CANCELLED, $batch->status);
        $this->assertSame(ParentChatbotOnboardingItem::STATUS_PROCESSING, $batch->items()->where('parent_id', $first->id)->value('status'));
        $this->assertSame(ParentChatbotOnboardingItem::STATUS_SKIPPED, $batch->items()->where('parent_id', $second->id)->value('status'));
        $this->assertSame('batch_cancelled', $batch->items()->where('parent_id', $second->id)->value('error_code'));
    }

    public function test_it_settles_an_expired_processing_item_after_cancellation_without_submitting_it(): void
    {
        $parent = $this->parentWithPupil('0700000001');
        $delivery = Mockery::mock(ParentChatbotLinkCodeDeliveryService::class);
        $delivery->shouldNotReceive('issueAndDeliver');
        $service = $this->service($delivery);
        $batch = $service->start(12);
        $batch->items()->where('parent_id', $parent->id)->update([
            'status' => ParentChatbotOnboardingItem::STATUS_PROCESSING,
            'lease_token' => 'expired-claim',
            'lease_expires_at' => now()->subMinute(),
        ]);

        $service->cancel($batch);
        $result = $service->process(25);
        $item = $batch->items()->sole();

        $this->assertSame(['claimed' => 0, 'synced' => 1], $result);
        $this->assertSame(ParentChatbotOnboardingItem::STATUS_SKIPPED, $item->status);
        $this->assertSame('batch_cancelled', $item->error_code);
        $this->assertNull($item->lease_token);
    }

    public function test_it_maps_an_existing_issuance_when_settling_an_expired_cancelled_claim(): void
    {
        $parent = $this->parentWithPupil('0700000001');
        $delivery = Mockery::mock(ParentChatbotLinkCodeDeliveryService::class);
        $delivery->shouldNotReceive('issueAndDeliver');
        $service = $this->service($delivery);
        $batch = $service->start(12);
        $item = $batch->items()->where('parent_id', $parent->id)->sole();
        $item->update([
            'status' => ParentChatbotOnboardingItem::STATUS_PROCESSING,
            'lease_token' => 'expired-claim',
            'lease_expires_at' => now()->subMinute(),
        ]);
        $issuance = ParentChatbotLinkCodeIssuance::create([
            'parent_id' => $parent->id,
            'request_id' => $item->request_id,
            'status' => ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED,
        ]);

        $service->cancel($batch);
        $service->process(25);
        $item = $item->fresh();

        $this->assertSame(ParentChatbotOnboardingItem::STATUS_ACCEPTED, $item->status);
        $this->assertSame($issuance->id, $item->issuance_id);
        $this->assertNull($item->error_code);
    }

    public function test_unexpected_activation_error_without_an_issuance_releases_the_item_for_retry(): void
    {
        $parent = $this->parentWithPupil('0700000001');
        $delivery = Mockery::mock(ParentChatbotLinkCodeDeliveryService::class);
        $delivery->shouldReceive('issueAndDeliver')->once()->andThrow(new RuntimeException('Temporary database failure'));
        $service = $this->service($delivery);
        $batch = $service->start(12);

        $service->process(1);
        $item = $batch->items()->where('parent_id', $parent->id)->sole();

        $this->assertSame(ParentChatbotOnboardingItem::STATUS_PENDING, $item->status);
        $this->assertSame('activation_retry_pending', $item->error_code);
        $this->assertNull($item->lease_token);
        $this->assertNull($item->lease_expires_at);
        $this->assertSame(1, $item->attempt_count);
    }

    private function service(?ParentChatbotLinkCodeDeliveryService $delivery = null): ParentChatbotOnboardingService
    {
        $delivery ??= Mockery::mock(ParentChatbotLinkCodeDeliveryService::class);

        return new ParentChatbotOnboardingService(
            $delivery,
            new MailPulseWorkflowPolicy(new \App\Services\ParentChatbot\ParentChatbotPublicationPolicy),
            new ParentChatbotPhoneNormalizer,
        );
    }

    private function parentWithPupil(?string $telephone): ESBTPParent
    {
        $parent = ESBTPParent::create(['telephone' => $telephone]);
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

    private function createTables(): void
    {
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
        Schema::create('parent_chatbot_link_code_issuances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('request_id', 100)->unique();
            $table->string('status', 32);
            $table->string('error_code', 64)->nullable();
            $table->timestamps();
        });
        Schema::create('parent_chatbot_onboarding_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('status', 24);
            $table->unsignedTinyInteger('processing_slot')->nullable()->unique();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('accepted_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('parent_chatbot_onboarding_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('issuance_id')->nullable();
            $table->string('request_id', 100)->unique();
            $table->string('status', 24);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('lease_token', 64)->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamps();
        });
    }
}
