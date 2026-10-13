<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPAttendance;

/**
 * Verify the final roll-call rather than trusting a queued snapshot.
 * If a newer attendance changed ABSENT -> PRESENT, do not alert the parent.
 */
final class MailPulseAbsenceValidity
{
    public function stillAbsent(string $event, int $studentId, array $payload): bool
    {
        if (! in_array($event, ['absence_reported', 'low_attendance'], true)) {
            return true;
        }

        $metadata = $payload['metadata'] ?? [];
        $session = $metadata['attendance_session_id'] ?? null;
        $date = $metadata['attendance_date'] ?? null;
        if (! is_numeric($session) || (int) $session < 1
            || ! is_string($date)
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        // Same source of truth as the attendance index: newest merged roll-call,
        // or start roll-call only if not yet merged. Soft-deleted history ignored.
        $current = ESBTPAttendance::query()
            ->finalOnly()
            ->where('etudiant_id', $studentId)
            ->where('seance_cours_id', (int) $session)
            ->whereDate('date', $date)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        return $current?->statut === 'absent';
    }
}
