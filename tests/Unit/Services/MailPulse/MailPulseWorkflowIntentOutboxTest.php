<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseWorkflowIntent;
use App\Services\MailPulse\MailPulseWorkflowIntentOutbox;
use App\Services\MailPulse\MailPulseWorkflowIntentRunner;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use App\Services\MailPulse\MailPulseWorkflowQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Queue;
use App\Jobs\MailPulse\DispatchMailPulseDurableWorkflowJob;
use Mockery;
use Tests\TestCase;

final class MailPulseWorkflowIntentOutboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        config()->set('services.mailpulse.workflow_intents_outbox_enabled', true);

        Schema::create('mailpulse_workflow_intents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('event', 80);
            $table->longText('payload_ciphertext');
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function enabledPolicy(): MailPulseWorkflowPolicy
    {
        $policy = Mockery::mock(MailPulseWorkflowPolicy::class);
        $policy->shouldReceive('realWorkflowsEnabled')->andReturn(true);
        return $policy;
    }

    public function test_workflow_push_persists_intent_before_the_optional_worker_runs(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        // A genuine async driver with Queue::fake intercepts the accelerator.
        config()->set('queue.default', 'database');
        Queue::fake();

        app(MailPulseWorkflowQueue::class)->push(MailPulseWorkflowIntent::paymentReceived(122));

        $row = DB::table('mailpulse_workflow_intents')->first();
        $this->assertNotNull($row);
        $this->assertSame('queued', $row->status);
        Queue::assertPushed(DispatchMailPulseDurableWorkflowJob::class,
            fn (DispatchMailPulseDurableWorkflowJob $job): bool => $job->intentId === $row->id);
    }

    public function test_sync_queue_only_persists_and_never_blocks_http_on_provider(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        config()->set('queue.default', 'sync');
        Queue::fake();

        app(MailPulseWorkflowQueue::class)->push(MailPulseWorkflowIntent::paymentReceived(124));

        $this->assertSame('queued', DB::table('mailpulse_workflow_intents')->first()?->status);
        Queue::assertNotPushed(DispatchMailPulseDurableWorkflowJob::class);
    }

    public function test_a_failed_queue_connection_does_not_erase_the_persisted_intent(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        config()->set('queue.default', 'missing-mailpulse-queue');

        app(MailPulseWorkflowQueue::class)->push(MailPulseWorkflowIntent::paymentReceived(123));

        $this->assertSame('queued', DB::table('mailpulse_workflow_intents')->first()?->status);
    }

    public function test_intent_is_encrypted_before_queue_and_cron_can_process_it(): void
    {
        $outbox = app(MailPulseWorkflowIntentOutbox::class);
        $id = $outbox->record(MailPulseWorkflowIntent::paymentReceived(999));
        $stored = DB::table('mailpulse_workflow_intents')->find($id);

        $this->assertSame('queued', $stored->status);
        $this->assertStringNotContainsString('payment_received', $stored->payload_ciphertext);
        $this->assertSame('payment_received', json_decode(
            Crypt::decryptString($stored->payload_ciphertext), true)['event']);

        $runner = Mockery::mock(MailPulseWorkflowIntentRunner::class);
        $runner->shouldReceive('run')->once()->withArgs(
            fn (MailPulseWorkflowIntent $intent): bool =>
                $intent->event === MailPulseWorkflowIntent::PAYMENT_RECEIVED
                && $intent->payload['paiement_id'] === 999
        );

        $this->assertSame([$id], $outbox->dueIds(5));
        $this->assertTrue($outbox->process($id, $runner, $this->enabledPolicy()));
        $this->assertFalse($outbox->process($id, $runner, $this->enabledPolicy()));
        $this->assertSame('processed', DB::table('mailpulse_workflow_intents')->find($id)->status);
        $this->assertSame(1, (int) DB::table('mailpulse_workflow_intents')->find($id)->attempts);
        $this->assertSame([], $outbox->dueIds(5));
    }

    public function test_transaction_rollback_removes_intent_with_business_transaction(): void
    {
        DB::beginTransaction();
        $id = app(MailPulseWorkflowIntentOutbox::class)
            ->record(MailPulseWorkflowIntent::paymentRejected(41));
        $this->assertNotNull(DB::table('mailpulse_workflow_intents')->find($id));
        DB::rollBack();

        $this->assertNull(DB::table('mailpulse_workflow_intents')->find($id));
    }

    public function test_runner_failure_is_visible_and_retried_without_losing_payload(): void
    {
        $outbox = app(MailPulseWorkflowIntentOutbox::class);
        $id = $outbox->record(MailPulseWorkflowIntent::gradePublished(51));

        $broken = Mockery::mock(MailPulseWorkflowIntentRunner::class);
        $broken->shouldReceive('run')->once()->andThrow(new \RuntimeException('Private provider details'));
        $this->assertFalse($outbox->process($id, $broken, $this->enabledPolicy()));
        $after = DB::table('mailpulse_workflow_intents')->find($id);

        $this->assertSame('retry', $after->status);
        $this->assertSame(1, (int) $after->attempts);
        $this->assertSame('RuntimeException', $after->last_error);
        $this->assertStringNotContainsString('Private', $after->last_error);
        $this->assertSame([], $outbox->dueIds(5));

        DB::table('mailpulse_workflow_intents')->where('id', $id)
            ->update(['next_attempt_at' => now()->subMinute()]);
        $recovered = Mockery::mock(MailPulseWorkflowIntentRunner::class);
        $recovered->shouldReceive('run')->once();

        $this->assertTrue($outbox->process($id, $recovered, $this->enabledPolicy()));
        $this->assertSame('processed', DB::table('mailpulse_workflow_intents')->find($id)->status);
    }

    public function test_a_stale_processing_lease_is_reclaimable_after_worker_crash(): void
    {
        $outbox = app(MailPulseWorkflowIntentOutbox::class);
        $id = $outbox->record(MailPulseWorkflowIntent::bulletinPublished(88));
        DB::table('mailpulse_workflow_intents')->where('id', $id)->update([
            'status' => 'processing', 'attempts' => 1,
            'lease_until' => now()->subMinute(),
        ]);
        $runner = Mockery::mock(MailPulseWorkflowIntentRunner::class);
        $runner->shouldReceive('run')->once();

        $this->assertSame([$id], $outbox->dueIds(5));
        $this->assertTrue($outbox->process($id, $runner, $this->enabledPolicy()));
        $this->assertSame(2, (int) DB::table('mailpulse_workflow_intents')->find($id)->attempts);
    }

    public function test_disabling_the_workflows_retains_unprocessed_intents_for_later(): void
    {
        $outbox = app(MailPulseWorkflowIntentOutbox::class);
        $id = $outbox->record(MailPulseWorkflowIntent::paymentReceived(17));
        $policy = Mockery::mock(MailPulseWorkflowPolicy::class);
        $policy->shouldReceive('realWorkflowsEnabled')->andReturn(false);
        $runner = Mockery::mock(MailPulseWorkflowIntentRunner::class);
        $runner->shouldNotReceive('run');

        $this->assertFalse($outbox->process($id, $runner, $policy));
        $this->assertSame('queued', DB::table('mailpulse_workflow_intents')->find($id)->status);
    }

    public function test_unknown_event_cannot_be_restored(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MailPulseWorkflowIntent::restore('unknown_event', []);
    }
}
