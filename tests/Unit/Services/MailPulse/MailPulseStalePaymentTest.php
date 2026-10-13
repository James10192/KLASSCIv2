<?php

namespace Tests\Unit\Services\MailPulse;

use App\Models\ESBTPPaiement;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseExtraWorkflowNotifications;
use App\Services\MailPulse\MailPulseParentNotificationLog;
use App\Services\MailPulse\MailPulseWorkflowNotificationService;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use Mockery;
use Tests\TestCase;

class MailPulseStalePaymentTest extends TestCase
{
    public function test_no_overdue_reminder_when_payment_is_no_longer_pending(): void
    {
        $service = new MailPulseWorkflowNotificationService(
            Mockery::mock(MailPulseClient::class),
            Mockery::mock(MailPulseParentNotificationLog::class),
            Mockery::mock(MailPulseWorkflowPolicy::class),
            Mockery::mock(ParentChatbotPublicationPolicy::class)
        );

        foreach (['validé', 'rejeté', 'annulé'] as $status) {
            $paiement = new ESBTPPaiement();
            $paiement->status = $status;
            // No relation loads, API calls or parent notification may happen.
            $service->notifyFeeReminder($paiement, 4, 2);
        }
        $this->assertTrue(true);
    }

    public function test_no_rejection_notification_after_a_payment_is_revalidated_or_reset(): void
    {
        $workflow = Mockery::mock(MailPulseWorkflowNotificationService::class);
        $workflow->shouldNotReceive('notifyTutor');
        $extra = new MailPulseExtraWorkflowNotifications($workflow);

        foreach (['validé', 'en_attente', 'annulé'] as $status) {
            $paiement = new ESBTPPaiement();
            $paiement->status = $status;
            $extra->notifyPaymentRejected($paiement);
        }
        $this->assertTrue(true);
    }
}
