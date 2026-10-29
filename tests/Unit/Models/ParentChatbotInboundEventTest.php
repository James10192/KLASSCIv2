<?php

namespace Tests\Unit\Models;

use App\Models\ParentChatbotInboundEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentChatbotInboundEventTest extends TestCase
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

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('parent_chatbot_inbound_events', function (Blueprint $table) {
            $table->id();
            $table->string('source_event_id', 100)->unique();
            $table->char('payload_hash', 64);
            $table->string('outcome', 40)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_token', 64)->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_an_active_claim_marks_a_duplicate_worker_as_busy(): void
    {
        $hash = hash('sha256', 'payload');
        $first = ParentChatbotInboundEvent::claim('evt-active', $hash);

        $this->assertTrue($first->isClaimed());
        $this->assertNotEmpty($first->event->processing_token);

        $duplicate = ParentChatbotInboundEvent::claim('evt-active', $hash);

        $this->assertFalse($duplicate->isClaimed());
        $this->assertTrue($duplicate->isBusy());
        $this->assertGreaterThanOrEqual(1, $duplicate->retryAfterSeconds());
        $this->assertFalse($duplicate->hasPayloadConflict());
        $this->assertSame(1, ParentChatbotInboundEvent::query()->count());
    }

    public function test_a_reused_event_id_with_a_changed_payload_is_a_conflict(): void
    {
        ParentChatbotInboundEvent::claim('evt-conflict', hash('sha256', 'original'));

        $conflict = ParentChatbotInboundEvent::claim('evt-conflict', hash('sha256', 'changed'));

        $this->assertFalse($conflict->isClaimed());
        $this->assertTrue($conflict->hasPayloadConflict());
        $this->assertSame(1, ParentChatbotInboundEvent::query()->count());
    }

    public function test_a_processed_event_is_a_completed_duplicate(): void
    {
        $hash = hash('sha256', 'payload');
        $event = ParentChatbotInboundEvent::claim('evt-complete', $hash)->event;
        $this->assertTrue($event->complete((string) $event->processing_token, 'processed'));

        $duplicate = ParentChatbotInboundEvent::claim('evt-complete', $hash);

        $this->assertFalse($duplicate->isClaimed());
        $this->assertTrue($duplicate->isProcessedDuplicate());
        $this->assertFalse($duplicate->isBusy());
    }

    public function test_an_expired_claim_can_be_retried_with_a_new_token(): void
    {
        $hash = hash('sha256', 'payload');
        $first = ParentChatbotInboundEvent::claim('evt-expired', $hash)->event;
        ParentChatbotInboundEvent::query()->whereKey($first->id)->update([
            'processing_expires_at' => now()->subSecond(),
        ]);

        $retry = ParentChatbotInboundEvent::claim('evt-expired', $hash)->event;

        $this->assertNotNull($retry);
        $this->assertNotSame($first->processing_token, $retry->processing_token);
    }

    public function test_a_dispatch_failure_releases_the_claim_for_retry(): void
    {
        $hash = hash('sha256', 'payload');
        $first = ParentChatbotInboundEvent::claim('evt-failed', $hash)->event;
        $first->release((string) $first->processing_token);

        $retry = ParentChatbotInboundEvent::claim('evt-failed', $hash)->event;

        $this->assertNotNull($retry);
        $this->assertSame('dispatch_pending', $retry->outcome);
        $this->assertNotSame($first->processing_token, $retry->processing_token);
    }

    public function test_only_the_current_lease_owner_can_complete_processing(): void
    {
        $hash = hash('sha256', 'payload');
        $first = ParentChatbotInboundEvent::claim('evt-owned', $hash)->event;
        ParentChatbotInboundEvent::query()->whereKey($first->id)->update([
            'processing_expires_at' => now()->subSecond(),
        ]);
        $retry = ParentChatbotInboundEvent::claim('evt-owned', $hash)->event;

        $this->assertFalse($first->complete((string) $first->processing_token, 'old_owner'));
        $this->assertTrue($retry->complete((string) $retry->processing_token, 'processed'));
        $duplicate = ParentChatbotInboundEvent::claim('evt-owned', $hash);

        $this->assertFalse($duplicate->isClaimed());
        $this->assertTrue($duplicate->isProcessedDuplicate());
        $this->assertFalse($duplicate->hasPayloadConflict());
    }

    public function test_lier_outcome_fields_are_not_part_of_the_model_contract(): void
    {
        $event = new ParentChatbotInboundEvent;

        $this->assertNotContains('lier_command', $event->getFillable());
        $this->assertNotContains('lier_intent', $event->getFillable());
        $this->assertNotContains('lier_reply_ciphertext', $event->getFillable());
        $this->assertArrayNotHasKey('lier_recorded_at', $event->getCasts());
    }
}
