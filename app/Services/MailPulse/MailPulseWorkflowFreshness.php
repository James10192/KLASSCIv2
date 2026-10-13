<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPPaiement;

/**
 * Re-check mutable business facts immediately before external dispatch,
 * including the reconciler's retries. An old job is never authority to send.
 */
final class MailPulseWorkflowFreshness
{
    public function isCurrent(string $event, int $studentId, array $payload): bool
    {
        if (in_array($event, ['payment_received', 'payment_rejected', 'fee_reminder'], true)) {
            $id = $payload['metadata']['paiement_id'] ?? null;
            if (! is_numeric($id) || (int) $id <= 0) {
                return false;
            }

            $status = $event === 'payment_received' ? 'validé'
                : ($event === 'payment_rejected' ? 'rejeté' : 'en_attente');

            $query = ESBTPPaiement::query()->whereKey((int) $id)
                ->where('status', $status)
                ->whereHas('inscription', fn ($q) => $q->where('etudiant_id', $studentId));

            if ($event === 'payment_received') {
                $query->whereNotNull('date_validation');
            }

            return $query->exists();
        }

        if (in_array($event, ['absence_reported', 'low_attendance'], true)) {
            $metadata = $payload['metadata'] ?? [];
            $seance = $metadata['attendance_session_id'] ?? null;
            $date = $metadata['attendance_date'] ?? null;

            // Unknown session cannot be validated safely after a roll-call
            // rewrite; fail closed instead of notifying a potentially
            // corrected 'present' as 'absent'.
            if (! is_numeric($seance) || (int) $seance <= 0
                || ! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return false;
            }

            $latest = ESBTPAttendance::query()
                ->where('etudiant_id', $studentId)
                ->where('seance_cours_id', (int) $seance)
                ->whereDate('date', $date)
                ->orderByDesc('id')
                ->first();

            return $latest?->statut === 'absent';
        }

        return true;
    }
}
