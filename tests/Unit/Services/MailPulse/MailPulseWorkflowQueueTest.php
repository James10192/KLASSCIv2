<?php

namespace Tests\Unit\Services\MailPulse;

use App\Jobs\MailPulse\DispatchMailPulseParentNotificationJob;
use App\Models\ESBTPAttendance;
use App\Services\MailPulse\MailPulseWorkflowIntent;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use App\Services\MailPulse\MailPulseWorkflowQueue;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailPulseWorkflowQueueTest extends TestCase
{
    private function queue(): MailPulseWorkflowQueue
    {
        return new MailPulseWorkflowQueue(app(MailPulseWorkflowPolicy::class));
    }

    private function attendance(): ESBTPAttendance
    {
        $attendance = new ESBTPAttendance();
        $attendance->setAttribute('etudiant_id', 42);
        $attendance->setAttribute('seance_cours_id', 7);
        $attendance->setAttribute('matiere_id', 3);
        $attendance->setAttribute('date', '2026-05-12');
        $attendance->setAttribute('heure_debut', '08:00:00');
        $attendance->setAttribute('heure_fin', '10:00:00');
        $attendance->setAttribute('statut', 'absent');

        return $attendance;
    }

    public function test_it_queues_the_notification_instead_of_calling_mailpulse_inline(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        Queue::fake();

        $this->queue()->push(MailPulseWorkflowIntent::paymentReceived(1234));

        Queue::assertPushed(
            DispatchMailPulseParentNotificationJob::class,
            fn (DispatchMailPulseParentNotificationJob $job): bool => $job->intent->event === MailPulseWorkflowIntent::PAYMENT_RECEIVED
                && $job->intent->payload['paiement_id'] === 1234
        );
    }

    public function test_the_kill_switch_prevents_any_queueing(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', false);
        Queue::fake();

        $this->queue()->push(MailPulseWorkflowIntent::paymentReceived(1234));

        Queue::assertNothingPushed();
    }

    public function test_pushing_never_throws_back_into_the_web_request(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        config()->set('queue.default', 'mailpulse-missing-connection');

        $this->queue()->push(MailPulseWorkflowIntent::paymentReceived(1234));

        $this->assertTrue(true, 'A broken queue connection must not break the caller.');
    }

    public function test_the_absence_intent_survives_queue_serialization(): void
    {
        $intent = MailPulseWorkflowIntent::absenceReported($this->attendance());

        /** @var MailPulseWorkflowIntent $restored */
        $restored = unserialize(serialize($intent));

        $this->assertSame(MailPulseWorkflowIntent::ABSENCE_REPORTED, $restored->event);
        $attendance = $restored->attendance();
        $this->assertNotNull($attendance);
        $this->assertSame(42, $attendance->getAttribute('etudiant_id'));
        $this->assertSame(7, $attendance->getAttribute('seance_cours_id'));
        $this->assertSame('08:00:00', $attendance->getAttribute('heure_debut'));
    }

    public function test_the_absence_intent_drops_the_row_identity_so_recreated_rows_stay_idempotent(): void
    {
        $persisted = $this->attendance();
        $persisted->exists = true;
        $persisted->setAttribute('id', 999);

        $snapshot = MailPulseWorkflowIntent::absenceReported($persisted)->attendanceSnapshot();

        $this->assertArrayNotHasKey('id', $snapshot);
        $this->assertFalse(MailPulseWorkflowIntent::absenceReported($persisted)->attendance()->exists);
    }

    public function test_new_events_map_to_the_expected_parent_preferences(): void
    {
        $policy = app(MailPulseWorkflowPolicy::class);

        $this->assertSame('paiements', $policy->preferenceType(MailPulseWorkflowIntent::PAYMENT_REJECTED));
        $this->assertSame('absences', $policy->preferenceType(MailPulseWorkflowIntent::LOW_ATTENDANCE));
        $this->assertSame('notes', $policy->preferenceType(MailPulseWorkflowIntent::LOW_GRADES));
        $this->assertSame('inscriptions', $policy->preferenceType(MailPulseWorkflowIntent::ENROLLMENT_CREATED));
        $this->assertSame('inscriptions', $policy->preferenceType(MailPulseWorkflowIntent::REENROLLMENT_CREATED));
    }
}
