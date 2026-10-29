<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseWorkflowDisclosure;
use PHPUnit\Framework\TestCase;

final class MailPulseWorkflowDisclosureTest extends TestCase
{
    public function test_low_grades_reuses_report_card_publication_guard(): void
    {
        $claim = (new MailPulseWorkflowDisclosure())->forEvent(
            'low_grades', 12, ['metadata' => ['bulletin_id' => 87]]
        );

        $this->assertSame([
            'type' => 'report_card',
            'student_id' => 12,
            'resource_ids' => [87],
        ], $claim);
    }

    public function test_unpublished_academic_payload_without_identity_fails_closed(): void
    {
        $claims = new MailPulseWorkflowDisclosure();

        $this->assertSame([], $claims->forEvent('low_grades', 12, []));
        $this->assertSame([
            'type' => 'report_card',
            'student_id' => 12,
            'resource_ids' => [null],
        ], $claims->forEvent('low_grades', 12, ['metadata' => []]));
    }

    public function test_unrestricted_non_academic_events_do_not_get_false_publication_claim(): void
    {
        $this->assertNull((new MailPulseWorkflowDisclosure())->forEvent('payment_received', 12, []));
    }

    public function test_grade_and_bulletin_events_keep_their_existing_publication_claims(): void
    {
        $guard = new MailPulseWorkflowDisclosure();
        $this->assertSame('grades', $guard->forEvent('grade_published', 9, ['metadata' => ['note_id' => 77]])['type']);
        $this->assertSame('report_card', $guard->forEvent('bulletin_published', 9, ['metadata' => ['bulletin_id' => 67]])['type']);
    }
}
