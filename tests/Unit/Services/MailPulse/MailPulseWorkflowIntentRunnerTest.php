<?php

namespace Tests\Unit\Services\MailPulse;

use App\Models\ESBTPAttendance;
use App\Services\MailPulse\MailPulseExtraWorkflowNotifications;
use App\Services\MailPulse\MailPulseWorkflowIntent;
use App\Services\MailPulse\MailPulseWorkflowIntentRunner;
use App\Services\MailPulse\MailPulseWorkflowNotificationService;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use Mockery;
use Tests\TestCase;

class MailPulseWorkflowIntentRunnerTest extends TestCase
{
    private function runner(
        bool $enabled,
        ?MailPulseWorkflowNotificationService $workflow = null,
        ?MailPulseExtraWorkflowNotifications $extra = null
    ): MailPulseWorkflowIntentRunner {
        $policy = Mockery::mock(MailPulseWorkflowPolicy::class);
        $policy->shouldReceive('realWorkflowsEnabled')->andReturn($enabled);

        return new MailPulseWorkflowIntentRunner(
            $workflow ?? Mockery::mock(MailPulseWorkflowNotificationService::class),
            $extra ?? Mockery::mock(MailPulseExtraWorkflowNotifications::class),
            $policy,
        );
    }

    private function attendance(): ESBTPAttendance
    {
        $attendance = new ESBTPAttendance();
        $attendance->setAttribute('etudiant_id', 42);
        $attendance->setAttribute('seance_cours_id', 7);
        $attendance->setAttribute('date', '2026-05-12');
        $attendance->setAttribute('heure_debut', '08:00:00');
        $attendance->setAttribute('heure_fin', '10:00:00');
        $attendance->setAttribute('statut', 'absent');

        return $attendance;
    }

    public function test_the_kill_switch_is_re_evaluated_worker_side(): void
    {
        $workflow = Mockery::mock(MailPulseWorkflowNotificationService::class);
        $workflow->shouldNotReceive('notifyAbsenceReported');

        $this->runner(false, $workflow)->run(
            MailPulseWorkflowIntent::absenceReported($this->attendance())
        );

        $this->assertTrue(true, 'No emission when the real workflows are disabled.');
    }

    public function test_it_rebuilds_the_attendance_and_routes_the_absence_event(): void
    {
        $workflow = Mockery::mock(MailPulseWorkflowNotificationService::class);
        $workflow->shouldReceive('notifyAbsenceReported')
            ->once()
            ->withArgs(fn (ESBTPAttendance $attendance): bool => $attendance->getAttribute('etudiant_id') === 42
                && $attendance->getAttribute('seance_cours_id') === 7
                && $attendance->exists === false);

        $this->runner(true, $workflow)->run(
            MailPulseWorkflowIntent::absenceReported($this->attendance())
        );
    }

    public function test_it_routes_the_low_attendance_event_with_its_rate_and_threshold(): void
    {
        $extra = Mockery::mock(MailPulseExtraWorkflowNotifications::class);
        $extra->shouldReceive('notifyLowAttendance')
            ->once()
            ->withArgs(fn (ESBTPAttendance $attendance, float $rate, int $threshold): bool => $rate === 62.5 && $threshold === 80);

        $this->runner(true, null, $extra)->run(
            MailPulseWorkflowIntent::lowAttendance($this->attendance(), 62.5, 80)
        );
    }

    public function test_a_deleted_subject_is_dropped_instead_of_failing_the_job(): void
    {
        $extra = Mockery::mock(MailPulseExtraWorkflowNotifications::class);
        $extra->shouldNotReceive('notifyEnrollmentCreated');

        // Inscription id 0 never resolves, the runner must swallow it silently.
        $this->runner(true, null, $extra)->run(
            MailPulseWorkflowIntent::enrollmentCreated(0)
        );

        $this->assertTrue(true, 'A vanished aggregate must not fail the job.');
    }
}
