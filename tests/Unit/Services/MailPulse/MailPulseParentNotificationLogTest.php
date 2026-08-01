<?php

namespace Tests\Unit\Services\MailPulse;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ParentNotificationLog;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseParentNotificationLog;
use App\Services\MailPulse\MailPulseResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MailPulseParentNotificationLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_parents', function (Blueprint $table): void {
            $table->id();
            $table->string('telephone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('parent_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->boolean('notify_inscriptions')->default(true);
            $table->boolean('notify_paiements')->default(true);
            $table->boolean('notify_absences')->default(true);
            $table->boolean('notify_notes')->default(true);
            $table->boolean('notify_bulletins')->default(true);
            $table->boolean('notify_annonces')->default(true);
            $table->json('preferred_channels')->nullable();
            $table->integer('notifications_sent_count')->default(0);
            $table->timestamps();
        });
        Schema::create('parent_chatbot_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('esbtp_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_published')->default(false);
            $table->boolean('notes_published')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('evaluation_id');
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->boolean('is_published')->default(false);
            $table->boolean('signature_directeur')->default(false);
            $table->boolean('signature_responsable')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('parent_notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->string('notification_type');
            $table->string('channel');
            $table->string('status')->default('pending');
            $table->string('request_id')->nullable()->unique();
            $table->string('recipient')->nullable();
            $table->string('message_preview')->nullable();
            $table->string('external_id')->nullable();
            $table->decimal('cost_fcfa', 10, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->text('retry_payload')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('retry_expires_at')->nullable();
            $table->string('dispatch_lease_token', 64)->nullable();
            $table->timestamp('dispatch_lease_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_stable_request_id_from_the_notification_identity(): void
    {
        $parent = new ESBTPParent(['id' => 15]);
        $parent->id = 15;
        $student = new ESBTPEtudiant(['id' => 42]);
        $student->id = 42;
        $service = app(MailPulseParentNotificationLog::class);

        $first = $service->requestId('payment_received', 'sms', $parent, $student, ['metadata' => ['receipt_number' => 'REC-001', 'paiement_id' => 21]]);
        $second = $service->requestId('payment_received', 'sms', $parent, $student, ['metadata' => ['paiement_id' => 21, 'receipt_number' => 'REC-001']]);

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^klassci-sms-[a-f0-9]{48}$/', $first);
    }

    /** @test */
    public function it_encrypts_the_durable_retry_payload(): void
    {
        $parent = new ESBTPParent();
        $parent->id = 15;
        $student = new ESBTPEtudiant();
        $student->id = 42;
        $payload = $this->payload();

        [$log, $skip] = app(MailPulseParentNotificationLog::class)->start('req-encrypted', 'payment_received', 'sms', $payload, $parent, $student, ['metadata' => ['paiement_id' => 21]]);

        $fresh = $log->fresh();
        $this->assertFalse($skip);
        $this->assertNotSame(json_encode($payload), $fresh->retry_payload);
        $this->assertSame($payload, json_decode(Crypt::decryptString($fresh->retry_payload), true));
        $this->assertArrayNotHasKey('mailpulse_retry_payload', $fresh->metadata);
        $this->assertStringStartsWith('sha256:', $fresh->recipient);
        $this->assertStringNotContainsString('+225', $fresh->recipient);
        $this->assertSame('workflow:payment_received', $fresh->message_preview);
        $this->assertStringNotContainsString('Bonjour', $fresh->message_preview);
    }

    /** @test */
    public function it_fails_closed_when_the_outbox_cannot_be_persisted(): void
    {
        Schema::drop('parent_notification_logs');
        $parent = new ESBTPParent();
        $parent->id = 15;
        $student = new ESBTPEtudiant();
        $student->id = 42;
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldNotReceive('sendSmsMessage');

        [$log, $skip] = app(MailPulseParentNotificationLog::class)->start('req-persistence-failure', 'payment_received', 'sms', $this->payload(), $parent, $student, []);
        $this->assertNull($log);
        $this->assertFalse($skip);
    }

    /** @test */
    public function it_keeps_an_encrypted_payload_for_transient_failures(): void
    {
        $service = app(MailPulseParentNotificationLog::class);
        foreach ([null, 408, 429, 503] as $httpStatus) {
            $log = $this->outboxLog('req-transient-' . ($httpStatus ?? 'timeout'));
            $status = $httpStatus === null ? 'connection_failed' : 'provider_unavailable';
            $service->finish($log, new MailPulseResult(false, $status, $httpStatus, $log->request_id), $log->request_id);

            $fresh = $log->fresh();
            $this->assertSame('pending', $fresh->status);
            $this->assertNotNull($fresh->retry_payload);
            $this->assertSame($this->payload(), json_decode(Crypt::decryptString($fresh->retry_payload), true));
            $this->assertTrue($fresh->next_attempt_at->isFuture());
        }
    }

    /** @test */
    public function it_deletes_the_payload_for_contractual_4xx_failures(): void
    {
        $log = $this->outboxLog('req-contractual-4xx');
        app(MailPulseParentNotificationLog::class)->finish($log, new MailPulseResult(false, 'template_required', 422, $log->request_id), $log->request_id);

        $fresh = $log->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertNull($fresh->retry_payload);
        $this->assertNull($fresh->next_attempt_at);
    }

    /** @test */
    public function it_prevents_a_second_worker_from_claiming_an_active_lease(): void
    {
        $log = $this->outboxLog('req-claimed', ['dispatch_lease_token' => 'other-worker', 'dispatch_lease_expires_at' => now()->addMinute()]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldNotReceive('sendSmsMessage');

        $processed = app(MailPulseParentNotificationLog::class)->retryPending($log, $client);

        $this->assertFalse($processed);
        $this->assertSame(1, $log->fresh()->attempt_count);
    }

    /** @test */
    public function it_cancels_a_phone_retry_when_chatbot_consent_was_stopped(): void
    {
        $this->createAllowedParent();
        DB::table('parent_chatbot_links')->insert([
            'parent_id' => 15, 'status' => 'stopped', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $log = $this->outboxLog('req-stopped-consent');
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldNotReceive('sendSmsMessage');

        $processed = app(MailPulseParentNotificationLog::class)->retryPending($log, $client);

        $fresh = $log->fresh();
        $this->assertTrue($processed);
        $this->assertSame('failed', $fresh->status);
        $this->assertSame('mailpulse_retry_suppressed_by_consent', $fresh->error_message);
        $this->assertNull($fresh->retry_payload);
    }

    /** @test */
    public function it_cancels_a_grade_retry_when_the_grade_is_depublished(): void
    {
        $this->createAllowedParent();
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'is_published' => true,
            'notes_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $noteId = DB::table('esbtp_notes')->insertGetId([
            'etudiant_id' => 42,
            'evaluation_id' => $evaluationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload = $this->payload();
        $payload['metadata'] = ['note_id' => $noteId];
        $log = $this->outboxLog('req-depublished-grade', [
            'metadata' => ['provider' => 'mailpulse', 'workflow_event' => 'grade_published'],
            'retry_payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
        ]);
        DB::table('esbtp_evaluations')->where('id', $evaluationId)->update(['notes_published' => false]);

        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldNotReceive('sendSmsMessage');

        $this->assertTrue(app(MailPulseParentNotificationLog::class)->retryPending($log, $client));
        $fresh = $log->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertSame('mailpulse_retry_suppressed_by_publication', $fresh->error_message);
    }

    /** @test */
    public function it_cancels_a_bulletin_retry_when_a_required_signature_is_removed(): void
    {
        $this->createAllowedParent();
        $bulletinId = DB::table('esbtp_bulletins')->insertGetId([
            'etudiant_id' => 42,
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload = $this->payload();
        $payload['metadata'] = ['bulletin_id' => $bulletinId];
        $log = $this->outboxLog('req-unsigned-bulletin', [
            'metadata' => ['provider' => 'mailpulse', 'workflow_event' => 'bulletin_published'],
            'retry_payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
        ]);

        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldNotReceive('sendSmsMessage');

        $this->assertTrue(app(MailPulseParentNotificationLog::class)->retryPending($log, $client));
        $fresh = $log->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertSame('mailpulse_retry_suppressed_by_publication', $fresh->error_message);
    }

    private function outboxLog(string $requestId, array $overrides = []): ParentNotificationLog
    {
        return ParentNotificationLog::create(array_merge([
            'parent_id' => 15,
            'etudiant_id' => 42,
            'notification_type' => 'paiement_valide',
            'channel' => 'sms',
            'status' => 'pending',
            'request_id' => $requestId,
            'metadata' => ['provider' => 'mailpulse', 'workflow_event' => 'payment_received'],
            'retry_payload' => Crypt::encryptString(json_encode($this->payload(), JSON_THROW_ON_ERROR)),
            'attempt_count' => 1,
            'next_attempt_at' => now()->subSecond(),
            'retry_expires_at' => now()->addHour(),
        ], $overrides));
    }

    private function createAllowedParent(): void
    {
        DB::table('esbtp_parents')->insert(['id' => 15, 'telephone' => '+2250707123456', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('parent_notification_preferences')->insert([
            'parent_id' => 15,
            'preferred_channels' => json_encode(['app', 'email', 'sms']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function payload(): array
    {
        return [
            'channel' => 'sms',
            'recipient' => ['type' => 'phone', 'value' => '+2250707123456'],
            'content' => ['type' => 'text', 'text' => 'Bonjour'],
        ];
    }
}
