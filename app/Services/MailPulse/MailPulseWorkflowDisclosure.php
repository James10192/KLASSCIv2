<?php

namespace App\Services\MailPulse;

/**
 * Per-event privacy claim, revalidated under the publication lock at dispatch
 * time AND on every retry. Invalid academic metadata MUST fail closed, never
 * fall through to the unrestricted (null) non-academic claim.
 */
final class MailPulseWorkflowDisclosure
{
    public function forEvent(string $event, int $studentId, array $payload): ?array
    {
        if (! in_array($event, ['grade_published', 'bulletin_published', 'low_grades'], true)) {
            return null;
        }

        $metadata = $payload['metadata'] ?? null;
        if (! is_array($metadata)) {
            return [];
        }

        $isGrade = $event === 'grade_published';
        $resourceId = $isGrade
            ? ($metadata['note_id'] ?? null)
            : ($metadata['bulletin_id'] ?? null);

        return [
            'type' => $isGrade ? 'grades' : 'report_card',
            'student_id' => $studentId,
            'resource_ids' => [$resourceId],
        ];
    }
}
