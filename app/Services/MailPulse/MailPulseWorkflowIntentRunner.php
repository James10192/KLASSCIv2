<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\Log;

/**
 * Turns a queued MailPulseWorkflowIntent back into a workflow notification.
 *
 * Runs inside the queue worker: it reloads the aggregate from the database so
 * a stale intent never emits a message based on outdated data, then delegates
 * to the workflow services which own consent, channels and the outbox.
 */
class MailPulseWorkflowIntentRunner
{
    public function __construct(
        private MailPulseWorkflowNotificationService $workflow,
        private MailPulseExtraWorkflowNotifications $extraWorkflow,
        private MailPulseWorkflowPolicy $workflowPolicy,
    ) {}

    public function run(MailPulseWorkflowIntent $intent): void
    {
        // The kill switch is re-evaluated worker side: an operator may have
        // disabled the real workflows between the web request and the retry.
        if (! $this->workflowPolicy->realWorkflowsEnabled()) {
            return;
        }

        match ($intent->event) {
            MailPulseWorkflowIntent::PAYMENT_RECEIVED => $this->onPaiement(
                $intent,
                fn (ESBTPPaiement $paiement) => $this->workflow->notifyPaymentReceived($paiement)
            ),
            MailPulseWorkflowIntent::PAYMENT_REJECTED => $this->onPaiement(
                $intent,
                fn (ESBTPPaiement $paiement) => $this->extraWorkflow->notifyPaymentRejected($paiement)
            ),
            MailPulseWorkflowIntent::FEE_REMINDER => $this->onPaiement(
                $intent,
                fn (ESBTPPaiement $paiement) => $this->workflow->notifyFeeReminder(
                    $paiement,
                    (int) ($intent->payload['days_pending'] ?? 0),
                    (int) ($intent->payload['reminder_count'] ?? 0),
                )
            ),
            MailPulseWorkflowIntent::ABSENCE_REPORTED => $this->onAttendance(
                $intent,
                fn ($attendance) => $this->workflow->notifyAbsenceReported($attendance)
            ),
            MailPulseWorkflowIntent::LOW_ATTENDANCE => $this->onAttendance(
                $intent,
                fn ($attendance) => $this->extraWorkflow->notifyLowAttendance(
                    $attendance,
                    (float) ($intent->payload['attendance_rate'] ?? 0),
                    (int) ($intent->payload['threshold'] ?? 0),
                )
            ),
            MailPulseWorkflowIntent::GRADE_PUBLISHED => $this->onModel(
                ESBTPNote::find($intent->payload['note_id'] ?? null),
                $intent,
                fn (ESBTPNote $note) => $this->workflow->notifyGradePublished($note)
            ),
            MailPulseWorkflowIntent::BULLETIN_PUBLISHED => $this->onModel(
                ESBTPBulletin::find($intent->payload['bulletin_id'] ?? null),
                $intent,
                fn (ESBTPBulletin $bulletin) => $this->workflow->notifyBulletinPublished($bulletin)
            ),
            MailPulseWorkflowIntent::LOW_GRADES => $this->onModel(
                ESBTPBulletin::find($intent->payload['bulletin_id'] ?? null),
                $intent,
                fn (ESBTPBulletin $bulletin) => $this->extraWorkflow->notifyLowGrades($bulletin)
            ),
            MailPulseWorkflowIntent::ENROLLMENT_CREATED => $this->onInscription(
                $intent,
                fn (ESBTPInscription $inscription) => $this->extraWorkflow->notifyEnrollmentCreated($inscription)
            ),
            MailPulseWorkflowIntent::REENROLLMENT_CREATED => $this->onInscription(
                $intent,
                fn (ESBTPInscription $inscription) => $this->extraWorkflow->notifyReEnrollmentCreated(
                    $inscription,
                    (string) ($intent->payload['decision'] ?? 'passage'),
                    (float) ($intent->payload['reliquat_amount'] ?? 0),
                )
            ),
            default => $this->onUnknownEvent($intent),
        };
    }

    private function onPaiement(MailPulseWorkflowIntent $intent, callable $handler): void
    {
        $this->onModel(ESBTPPaiement::find($intent->payload['paiement_id'] ?? null), $intent, $handler);
    }

    private function onInscription(MailPulseWorkflowIntent $intent, callable $handler): void
    {
        $this->onModel(ESBTPInscription::find($intent->payload['inscription_id'] ?? null), $intent, $handler);
    }

    private function onAttendance(MailPulseWorkflowIntent $intent, callable $handler): void
    {
        $this->onModel($intent->attendance(), $intent, $handler);
    }

    private function onModel(mixed $model, MailPulseWorkflowIntent $intent, callable $handler): void
    {
        if ($model === null) {
            Log::info('MailPulse workflow intent dropped, subject no longer exists', [
                'event' => $intent->event,
                'payload' => $intent->payload,
            ]);

            return;
        }

        $handler($model);
    }

    private function onUnknownEvent(MailPulseWorkflowIntent $intent): void
    {
        Log::warning('MailPulse workflow intent ignored, unknown event', ['event' => $intent->event]);
    }
}
