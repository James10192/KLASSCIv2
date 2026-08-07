<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use App\Models\ESBTPParent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCode;
use App\Models\ParentChatbotInboundEvent;
use App\Services\ParentChatbot\ParentChatbotDispatcher;
use App\Services\ParentChatbot\ParentChatbotDispatchOutcome;
use App\Services\ParentChatbot\ParentChatbotLinkService;
use App\Services\ParentChatbot\ParentChatbotPhoneNormalizer;
use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use App\Services\ParentChatbot\ParentChatbotReportCardAccess;
use App\Services\ParentChatbot\ParentChatbotResponder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ParentChatbotResponderTest extends TestCase
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
        config()->set('services.mailpulse.parent_chatbot_code_pepper', str_repeat('c', 32));
        config()->set('services.mailpulse.parent_chatbot_phone_hash_key', str_repeat('p', 32));

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_parents', function (Blueprint $table): void {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
            $table->string('telephone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
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
            $table->integer('absence_threshold')->nullable();
            $table->decimal('grade_threshold', 4, 1)->nullable();
            $table->integer('attendance_rate_threshold')->nullable();
            $table->string('reminder_frequency')->nullable();
            $table->string('preferred_language')->nullable();
            $table->timestamp('last_notification_sent_at')->nullable();
            $table->integer('notifications_sent_count')->default(0);
            $table->timestamps();
        });
        Schema::create('parent_chatbot_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->char('phone_hash', 64);
            $table->unsignedBigInteger('selected_student_id')->nullable();
            $table->string('status', 20);
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
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
        Schema::create('parent_chatbot_inbound_events', function (Blueprint $table): void {
            $table->id();
            $table->string('source_event_id', 100)->unique();
            $table->char('payload_hash', 64);
            $table->string('outcome', 40)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_token', 64)->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_expires_at')->nullable();
            $table->text('response_ciphertext')->nullable();
            $table->timestamp('response_recorded_at')->nullable();
            $table->timestamps();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->string('periode')->nullable();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->decimal('note_assiduite', 5, 2)->nullable();
            $table->decimal('absences_justifiees', 6, 2)->default(0);
            $table->decimal('absences_non_justifiees', 6, 2)->default(0);
            $table->decimal('total_absences', 6, 2)->default(0);
            $table->integer('rang')->nullable();
            $table->integer('effectif_classe')->nullable();
            $table->string('decision_conseil')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('signature_directeur')->default(false);
            $table->boolean('signature_responsable')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
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
        Schema::create('esbtp_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('seance_cours_id')->nullable();
            $table->string('statut')->nullable();
            $table->string('call_type')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_stop_suppresses_messaging_without_changing_parent_preferences(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        $parent->getOrCreateNotificationPreferences()->update([
            'preferred_channels' => ['app', 'email', 'whatsapp', 'sms'],
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with($phone, Mockery::type('string'), ParentChatbotIntent::Stop, 'evt-stop', 'klassci-parent-inbound-evt-stop')
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-stop'));

        $outcome = $this->respond($dispatcher, $phone, 'STOP', 'evt-stop');

        $this->assertSame(ParentChatbotIntent::Stopped->value, $outcome);
        $this->assertSame(ParentChatbotLink::STATUS_STOPPED, ParentChatbotLink::firstOrFail()->status);
        $this->assertSame(['app', 'email', 'whatsapp', 'sms'], $parent->fresh()->getOrCreateNotificationPreferences()->preferred_channels);
    }

    public function test_oui_does_not_restore_a_channel_removed_after_stop(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        $parent->getOrCreateNotificationPreferences()->update([
            'preferred_channels' => ['app', 'email', 'whatsapp', 'sms'],
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-stop'));
        $this->respond($dispatcher, $phone, 'STOP', 'evt-stop');
        $parent->fresh()->getOrCreateNotificationPreferences()->update([
            'preferred_channels' => ['app', 'email', 'sms'],
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with($phone, Mockery::type('string'), ParentChatbotIntent::Start, 'evt-oui', 'klassci-parent-inbound-evt-oui')
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-start'));

        $outcome = $this->respond($dispatcher, $phone, 'OUI', 'evt-oui');

        $this->assertSame(ParentChatbotIntent::Start->value, $outcome);
        $this->assertSame(['app', 'email', 'sms'], $parent->fresh()->getOrCreateNotificationPreferences()->preferred_channels);
    }

    public function test_non_suppresses_messaging_like_stop(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with($phone, Mockery::type('string'), ParentChatbotIntent::Stop, 'evt-non', 'klassci-parent-inbound-evt-non')
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-non'));

        $outcome = $this->respond($dispatcher, $phone, 'NON', 'evt-non');

        $this->assertSame(ParentChatbotIntent::Stopped->value, $outcome);
        $this->assertSame(ParentChatbotLink::STATUS_STOPPED, ParentChatbotLink::firstOrFail()->status);
    }

    public function test_retried_stop_keeps_preferences_unchanged(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        $parent->getOrCreateNotificationPreferences()->update([
            'preferred_channels' => ['app', 'email', 'whatsapp', 'sms'],
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::pendingReconciliation('out-stop'));

        try {
            $this->respond($dispatcher, $phone, 'STOP', 'evt-stop');
            $this->fail('The first STOP dispatch should require reconciliation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Parent chatbot response requires MailPulse reconciliation.', $exception->getMessage());
        }

        $this->assertSame(['app', 'email', 'whatsapp', 'sms'], $parent->fresh()->getOrCreateNotificationPreferences()->preferred_channels);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-stop-retry'));

        $this->assertSame(
            ParentChatbotIntent::Stopped->value,
            $this->respond($dispatcher, $phone, 'STOP', 'evt-stop-retry')
        );

        $this->assertSame(['app', 'email', 'whatsapp', 'sms'], $parent->fresh()->getOrCreateNotificationPreferences()->preferred_channels);
    }

    public function test_stop_stops_every_authorized_link_for_an_ambiguous_phone_number(): void
    {
        [, , $phone] = $this->parentWithActiveLink();
        $secondParent = ESBTPParent::create(['nom' => 'KONE', 'prenoms' => 'Mariam', 'telephone' => $phone]);
        $secondStudentId = DB::table('esbtp_etudiants')->insertGetId([
            'nom' => 'KONE',
            'prenoms' => 'Moussa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('esbtp_etudiant_parent')->insert([
            'parent_id' => $secondParent->id,
            'etudiant_id' => $secondStudentId,
            'is_tuteur' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $phones = new ParentChatbotPhoneNormalizer;
        ParentChatbotLink::create([
            'parent_id' => $secondParent->id,
            'phone_hash' => $phones->hash($phone),
            'selected_student_id' => $secondStudentId,
            'status' => ParentChatbotLink::STATUS_ACTIVE,
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with($phone, Mockery::type('string'), ParentChatbotIntent::Stop, 'evt-stop-ambiguous', 'klassci-parent-inbound-evt-stop-ambiguous')
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-stop-ambiguous'));

        $this->assertSame(
            ParentChatbotIntent::Stopped->value,
            $this->respond($dispatcher, $phone, 'STOP', 'evt-stop-ambiguous'),
        );
        $this->assertSame(2, ParentChatbotLink::query()
            ->where('status', ParentChatbotLink::STATUS_STOPPED)
            ->count());
    }

    public function test_lier_records_the_exact_response_before_its_link_effect_is_committed(): void
    {
        $phone = '+2250707123456';
        $parent = ESBTPParent::create(['nom' => 'DIALLO', 'prenoms' => 'Awa', 'telephone' => $phone]);
        $studentId = DB::table('esbtp_etudiants')->insertGetId([
            'nom' => 'DIALLO',
            'prenoms' => 'Habib',
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

        $phones = new ParentChatbotPhoneNormalizer;
        $code = (new ParentChatbotLinkService($phones))->issueCode($parent);
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-lier-replay',
            'payload_hash' => hash('sha256', 'evt-lier-replay'),
            'received_at' => now(),
            'processing_token' => 'lier-token',
            'processing_started_at' => now(),
            'processing_expires_at' => now()->addMinute(),
        ]);
        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $response = $this->responder($dispatcher)->prepareInboundResponse(
            $event,
            'lier-token',
            $phone,
            'LIER '.$code,
        );

        $this->assertSame('linked', $response['outcome']);
        $this->assertTrue($event->fresh()->hasRecordedResponse());
        $this->assertNotNull(ParentChatbotLinkCode::sole()->consumed_at);
        $this->assertSame(1, ParentChatbotLink::query()->count());

        $event->release('lier-token');
        $replayed = ParentChatbotInboundEvent::claim('evt-lier-replay', hash('sha256', 'evt-lier-replay'))->event;

        $this->assertSame(
            $response,
            $this->responder($dispatcher)->prepareInboundResponse($replayed, (string) $replayed->processing_token, $phone, 'LIER '.$code),
        );
    }

    public function test_lier_rolls_back_the_link_and_code_consumption_when_response_recording_crashes(): void
    {
        $phone = '+2250707123456';
        $parent = ESBTPParent::create(['nom' => 'DIALLO', 'prenoms' => 'Awa', 'telephone' => $phone]);
        $studentId = DB::table('esbtp_etudiants')->insertGetId([
            'nom' => 'DIALLO',
            'prenoms' => 'Habib',
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

        $phones = new ParentChatbotPhoneNormalizer;
        $links = new ParentChatbotLinkService($phones);
        $code = $links->issueCode($parent);
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-lier-crash',
            'payload_hash' => hash('sha256', 'evt-lier-crash'),
            'received_at' => now(),
            'processing_token' => 'lier-crash-token',
            'processing_started_at' => now(),
            'processing_expires_at' => now()->addMinute(),
        ]);

        try {
            $links->linkAndRecordInboundResponse(
                $event,
                'lier-crash-token',
                $phone,
                $code,
                'klassci-parent-inbound-evt-lier-crash',
                fn (): array => throw new RuntimeException('Simulated crash before response persistence.'),
            );
            $this->fail('The simulated crash must abort LIER.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated crash before response persistence.', $exception->getMessage());
        }

        $this->assertNull(ParentChatbotLinkCode::sole()->consumed_at);
        $this->assertSame(0, ParentChatbotLink::query()->count());
        $this->assertFalse($event->fresh()->hasRecordedResponse());
    }

    public function test_bulletin_reply_pairs_the_published_summary_with_a_temporary_signed_link(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        DB::table('esbtp_bulletins')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => 1,
            'annee_universitaire_id' => 1,
            'periode' => 'semestre1',
            'moyenne_generale' => 12.5,
            'rang' => 3,
            'effectif_classe' => 25,
            'decision_conseil' => 'Admis',
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn (
                string $recipient,
                string $reply,
                ParentChatbotIntent $intent,
                string $eventId,
                string $requestId
            ): bool => $recipient === $phone
                && $intent === ParentChatbotIntent::PublishedReportCard
                && $eventId === 'evt-bulletin'
                && $requestId === 'klassci-parent-inbound-evt-bulletin'
                && str_contains($reply, 'moyenne 12,5/20')
                && str_contains($reply, 'rang 3/25')
                && str_contains($reply, '/api/v1/parent-chatbot/report-cards/')
                && str_contains($reply, 'signature=')
                && str_contains($reply, 'expires='))
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-bulletin'));

        $this->assertSame(
            ParentChatbotIntent::PublishedReportCard->value,
            $this->respond($dispatcher, $phone, 'BULLETIN', 'evt-bulletin')
        );
    }

    public function test_absence_reply_uses_persisted_published_bulletin_snapshot(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        DB::table('esbtp_bulletins')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => 1,
            'annee_universitaire_id' => 1,
            'periode' => 'semestre1',
            'absences_justifiees' => 1.25,
            'absences_non_justifiees' => 2.75,
            'total_absences' => 4.0,
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn (
                string $recipient,
                string $reply,
                ParentChatbotIntent $intent,
                string $eventId,
                string $requestId
            ): bool => $recipient === $phone
                && $intent === ParentChatbotIntent::Absences
                && $eventId === 'evt-absences'
                && $requestId === 'klassci-parent-inbound-evt-absences'
                && str_contains($reply, '4,00 au total')
                && str_contains($reply, '1,25 justifiée')
                && str_contains($reply, '2,75 non justifiée'))
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-absences'));

        $this->assertSame(
            ParentChatbotIntent::Absences->value,
            $this->respond($dispatcher, $phone, 'ABSENCES', 'evt-absences')
        );
    }

    public function test_attendance_reply_ignores_raw_attendance_without_published_bulletin(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        DB::table('esbtp_attendances')->insert([
            'etudiant_id' => $studentId,
            'seance_cours_id' => 10,
            'statut' => 'absent',
            'call_type' => 'start',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn (
                string $recipient,
                string $reply,
                ParentChatbotIntent $intent,
                string $eventId,
                string $requestId
            ): bool => $recipient === $phone
                && $intent === ParentChatbotIntent::AttendanceRate
                && $eventId === 'evt-attendance-unpublished'
                && $requestId === 'klassci-parent-inbound-evt-attendance-unpublished'
                && str_contains($reply, 'Aucune assiduité publiée')
                && ! str_contains($reply, '0 %')
                && ! str_contains($reply, 'absent'))
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-attendance-unpublished'));

        $this->assertSame(
            ParentChatbotIntent::AttendanceRate->value,
            $this->respond($dispatcher, $phone, 'ASSIDUITE', 'evt-attendance-unpublished')
        );
    }

    public function test_attendance_reply_uses_only_published_bulletin_assiduity_note(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();
        DB::table('esbtp_bulletins')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => 1,
            'annee_universitaire_id' => 1,
            'periode' => 'semestre1',
            'note_assiduite' => 18.5,
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn (
                string $recipient,
                string $reply,
                ParentChatbotIntent $intent,
                string $eventId,
                string $requestId
            ): bool => $recipient === $phone
                && $intent === ParentChatbotIntent::AttendanceRate
                && $eventId === 'evt-attendance-published'
                && $requestId === 'klassci-parent-inbound-evt-attendance-published'
                && str_contains($reply, 'note d\'assiduité 18,5/20'))
            ->andReturn(ParentChatbotDispatchOutcome::accepted('out-attendance-published'));

        $this->assertSame(
            ParentChatbotIntent::AttendanceRate->value,
            $this->respond($dispatcher, $phone, 'ASSIDUITE', 'evt-attendance-published')
        );
    }

    public function test_pending_reconciliation_is_not_treated_as_success(): void
    {
        [$parent, $studentId, $phone] = $this->parentWithActiveLink();

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andReturn(ParentChatbotDispatchOutcome::pendingReconciliation('out-unknown'));

        $this->expectException(RuntimeException::class);

        $this->respond($dispatcher, $phone, 'AIDE', 'evt-unknown');
    }

    public function test_recorded_grade_response_is_discarded_when_the_grade_is_depublished_before_dispatch(): void
    {
        [, $studentId] = $this->parentWithActiveLink();
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'is_published' => true,
            'notes_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $noteId = DB::table('esbtp_notes')->insertGetId([
            'etudiant_id' => $studentId,
            'evaluation_id' => $evaluationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $event = $this->recordedAcademicEvent('evt-grade-depublished', ParentChatbotIntent::PublishedGrades, [
            'type' => 'grades', 'student_id' => $studentId, 'resource_ids' => [$noteId],
        ]);
        DB::table('esbtp_evaluations')->where('id', $evaluationId)->update(['is_published' => false]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $this->assertSame('academic_content_unpublished', $this->responder($dispatcher)->dispatchRecordedResponse(
            $event,
            (string) $event->processing_token,
        ));
        $this->assertNull($event->fresh()->response_ciphertext);
    }

    public function test_replayed_report_card_response_is_revalidated_after_signatures_are_removed(): void
    {
        [, $studentId, $phone] = $this->parentWithActiveLink();
        $bulletinId = DB::table('esbtp_bulletins')->insertGetId([
            'etudiant_id' => $studentId,
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $event = $this->recordedAcademicEvent('evt-bulletin-replay', ParentChatbotIntent::PublishedReportCard, [
            'type' => 'report_card', 'student_id' => $studentId, 'resource_ids' => [$bulletinId],
        ]);
        $event->release((string) $event->processing_token);
        $replayed = ParentChatbotInboundEvent::claim('evt-bulletin-replay', hash('sha256', 'evt-bulletin-replay'))->event;
        DB::table('esbtp_bulletins')->where('id', $bulletinId)->update(['signature_directeur' => false]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');
        $response = $this->responder($dispatcher)->prepareInboundResponse($replayed, (string) $replayed->processing_token, $phone, 'BULLETIN');

        $this->assertSame('report_card', $response['disclosure']['type']);
        $this->assertSame('academic_content_unpublished', $this->responder($dispatcher)->dispatchRecordedResponse(
            $replayed,
            (string) $replayed->processing_token,
        ));
        $this->assertNull($replayed->fresh()->response_ciphertext);
    }

    public function test_stop_after_response_recording_discards_a_non_stop_response(): void
    {
        [, , $phone] = $this->parentWithActiveLink();
        $event = $this->recordedLinkedEvent('evt-stopped-after-record', ParentChatbotIntent::Help);
        ParentChatbotLink::firstOrFail()->update(['status' => ParentChatbotLink::STATUS_STOPPED]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $this->assertSame('link_not_authorized', $this->responder($dispatcher)->dispatchRecordedResponse(
            $event,
            (string) $event->processing_token,
        ));
        $this->assertNull($event->fresh()->response_ciphertext);
    }

    public function test_revoked_link_after_response_recording_discards_the_response(): void
    {
        [, , $phone] = $this->parentWithActiveLink();
        $event = $this->recordedLinkedEvent('evt-revoked-after-record', ParentChatbotIntent::Help);
        ParentChatbotLink::firstOrFail()->update(['status' => ParentChatbotLink::STATUS_REVOKED]);

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $this->assertSame('link_not_authorized', $this->responder($dispatcher)->dispatchRecordedResponse(
            $event,
            (string) $event->processing_token,
        ));
        $this->assertNull($event->fresh()->response_ciphertext);
    }

    public function test_lost_pupil_after_response_recording_discards_the_response(): void
    {
        [$parent, $studentId] = $this->parentWithActiveLink();
        $event = $this->recordedLinkedEvent('evt-lost-pupil-after-record', ParentChatbotIntent::Help);
        DB::table('esbtp_etudiant_parent')
            ->where('parent_id', $parent->id)
            ->where('etudiant_id', $studentId)
            ->delete();

        $dispatcher = Mockery::mock(ParentChatbotDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $this->assertSame('link_not_authorized', $this->responder($dispatcher)->dispatchRecordedResponse(
            $event,
            (string) $event->processing_token,
        ));
        $this->assertNull($event->fresh()->response_ciphertext);
    }

    private function responder(ParentChatbotDispatcher $dispatcher): ParentChatbotResponder
    {
        $phones = new ParentChatbotPhoneNormalizer;
        $publicationPolicy = new ParentChatbotPublicationPolicy;

        return new ParentChatbotResponder(
            new ParentChatbotLinkService($phones),
            $phones,
            $dispatcher,
            $publicationPolicy,
            new ParentChatbotReportCardAccess($publicationPolicy),
        );
    }

    private function respond(ParentChatbotDispatcher $dispatcher, string $phone, string $message, string $eventId): string
    {
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => $eventId,
            'payload_hash' => hash('sha256', $phone . "\n" . $message),
            'received_at' => now(),
            'processing_token' => 'test-token-' . $eventId,
            'processing_started_at' => now(),
            'processing_expires_at' => now()->addMinute(),
        ]);
        $response = $this->responder($dispatcher)->prepareInboundResponse($event, (string) $event->processing_token, $phone, $message);
        $idempotencyKey = 'klassci-parent-inbound-' . $eventId;

        $this->assertTrue($event->recordResponse(
            (string) $event->processing_token,
            $response['phone'],
            $response['intent'],
            $response['outcome'],
            $response['reply'],
            $idempotencyKey,
            $response['should_dispatch'],
            $response['disclosure'],
            $response['authorization_claim'],
        ));

        return $this->responder($dispatcher)->dispatchRecordedResponse($event, (string) $event->processing_token);
    }

    private function recordedAcademicEvent(string $eventId, ParentChatbotIntent $intent, array $disclosure): ParentChatbotInboundEvent
    {
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => $eventId,
            'payload_hash' => hash('sha256', $eventId),
            'received_at' => now(),
            'processing_token' => 'test-token-' . $eventId,
            'processing_started_at' => now(),
            'processing_expires_at' => now()->addMinute(),
        ]);
        $this->assertTrue($event->recordResponse(
            (string) $event->processing_token,
            '+2250707123456',
            $intent->value,
            $intent->value,
            'Contenu académique',
            'klassci-parent-inbound-' . $eventId,
            true,
            $disclosure,
            $this->authorizationClaim(),
        ));

        return $event;
    }

    private function recordedLinkedEvent(string $eventId, ParentChatbotIntent $intent): ParentChatbotInboundEvent
    {
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => $eventId,
            'payload_hash' => hash('sha256', $eventId),
            'received_at' => now(),
            'processing_token' => 'test-token-'.$eventId,
            'processing_started_at' => now(),
            'processing_expires_at' => now()->addMinute(),
        ]);
        $this->assertTrue($event->recordResponse(
            (string) $event->processing_token,
            '+2250707123456',
            $intent->value,
            $intent->value,
            'Réponse liée.',
            'klassci-parent-inbound-'.$eventId,
            true,
            null,
            $this->authorizationClaim(),
        ));

        return $event;
    }

    private function authorizationClaim(): array
    {
        $phones = new ParentChatbotPhoneNormalizer;

        return (new ParentChatbotLinkService($phones))
            ->dispatchAuthorizationClaim(ParentChatbotLink::firstOrFail());
    }

    /** @return array{ESBTPParent, int, string} */
    private function parentWithActiveLink(): array
    {
        $phone = '+2250707123456';
        $parent = ESBTPParent::create(['nom' => 'DIALLO', 'prenoms' => 'Awa', 'telephone' => $phone]);
        $studentId = DB::table('esbtp_etudiants')->insertGetId([
            'nom' => 'DIALLO',
            'prenoms' => 'Habib',
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

        $phones = new ParentChatbotPhoneNormalizer;
        ParentChatbotLink::create([
            'parent_id' => $parent->id,
            'phone_hash' => $phones->hash($phone),
            'selected_student_id' => $studentId,
            'status' => ParentChatbotLink::STATUS_ACTIVE,
        ]);

        return [$parent, $studentId, $phone];
    }
}
