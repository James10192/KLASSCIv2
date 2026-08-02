<?php

namespace Tests\Unit\Services\MailPulse;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPPaiement;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseParentNotificationLog;
use App\Services\MailPulse\MailPulseWorkflowNotificationService;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class MailPulseWorkflowOccurrenceIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.tenant_code', 'tenant-a');
    }

    public function test_pending_payment_does_not_enter_the_received_payment_workflow(): void
    {
        $client = Mockery::mock(MailPulseClient::class);
        $logs = Mockery::mock(MailPulseParentNotificationLog::class);
        $client->shouldNotReceive('createOrUpdateContact');
        $logs->shouldNotReceive('requestId');

        $payment = new ESBTPPaiement(['status' => 'en_attente']);

        $this->service($client, $logs)->notifyPaymentReceived($payment);

        $this->assertTrue(true);
    }

    public function test_validation_occurrence_is_stable_after_unrelated_payment_updates(): void
    {
        $payment = new ESBTPPaiement([
            'status' => 'validé',
            'date_validation' => '2026-08-01 10:30:00',
            'validateur_id' => 7,
            'montant' => 10000,
        ]);
        $payment->id = 21;
        $method = new ReflectionMethod(MailPulseWorkflowNotificationService::class, 'paymentValidationOccurrence');

        $first = $method->invoke($this->service(), $payment);
        $payment->montant = 20000;
        $payment->updated_at = now()->addHour();

        $this->assertSame($first, $method->invoke($this->service(), $payment));
    }

    public function test_transient_attendance_occurrence_uses_session_student_and_schedule(): void
    {
        $method = new ReflectionMethod(MailPulseWorkflowNotificationService::class, 'attendanceOccurrence');
        $first = $this->transientAttendance(11);
        $second = $this->transientAttendance(12);

        $this->assertSame(
            $method->invoke($this->service(), $first),
            $method->invoke($this->service(), $this->transientAttendance(11)),
        );
        $this->assertNotSame(
            $method->invoke($this->service(), $first),
            $method->invoke($this->service(), $second),
        );
    }

    private function transientAttendance(int $sessionId): ESBTPAttendance
    {
        return new ESBTPAttendance([
            'seance_cours_id' => $sessionId,
            'etudiant_id' => 42,
            'date' => '2026-08-01',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
        ]);
    }

    private function service(
        ?MailPulseClient $client = null,
        ?MailPulseParentNotificationLog $logs = null,
    ): MailPulseWorkflowNotificationService {
        return new MailPulseWorkflowNotificationService(
            $client ?? Mockery::mock(MailPulseClient::class),
            $logs ?? Mockery::mock(MailPulseParentNotificationLog::class),
            Mockery::mock(MailPulseWorkflowPolicy::class),
            new ParentChatbotPublicationPolicy,
        );
    }
}
