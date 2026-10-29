<?php

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\WorkflowEmailAuthority;
use PHPUnit\Framework\TestCase;

final class WorkflowEmailAuthorityTest extends TestCase
{
    public function test_laravel_family_email_is_authoritative_for_existing_transactions(): void
    {
        $authority = new WorkflowEmailAuthority();

        foreach ([
            'enrollment_created',
            'reenrollment_created',
            'payment_received',
            'payment_rejected',
            'absence_reported',
            'low_attendance',
            'low_grades',
        ] as $event) {
            $this->assertFalse($authority->shouldSendViaMailPulse($event), $event);
        }
    }

    public function test_mailpulse_remains_authoritative_for_other_workflows(): void
    {
        $authority = new WorkflowEmailAuthority();

        foreach ([
            'grade_published',
            'bulletin_published',
            'fee_reminder',
            'timetable_changed',
        ] as $event) {
            $this->assertTrue($authority->shouldSendViaMailPulse($event), $event);
        }
    }
}
