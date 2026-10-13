<?php

namespace App\Services\Notifications;

/**
 * Every workflow has ONE authoritative parent email path.
 *
 * Historical family mailables contain the full academic/payment summary and
 * reach the student as well as linked parents. MailPulse remains authoritative
 * for workflows without a Laravel family email, and for opted-in phone channels.
 */
final class WorkflowEmailAuthority
{
    private const FAMILY_MAILER_EVENTS = [
        'enrollment_created',
        'reenrollment_created',
        'payment_received',
        'payment_rejected',
        'absence_reported',
        'low_attendance', // The family absence summary already explains the threshold.
        'low_grades',
    ];

    public function shouldSendViaMailPulse(string $event): bool
    {
        return ! in_array($event, self::FAMILY_MAILER_EVENTS, true);
    }
}
