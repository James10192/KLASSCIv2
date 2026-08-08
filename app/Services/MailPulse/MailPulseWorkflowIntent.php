<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPAttendance;

/**
 * Serializable description of a parent notification that must reach MailPulse.
 *
 * The intent only carries scalars so it survives the queue boundary without
 * relying on Eloquent serialization: attendance rows are frequently built in
 * memory (no primary key) and could never be restored by SerializesModels.
 *
 * The attendance snapshot deliberately drops the row identity. Several roll
 * call paths delete and recreate the final attendance rows, so keying the
 * outbox on the row id would emit a duplicate parent message on every resubmit.
 * The session coordinates (seance, student, date, slot) stay stable instead.
 */
final class MailPulseWorkflowIntent
{
    public const PAYMENT_RECEIVED = 'payment_received';
    public const PAYMENT_REJECTED = 'payment_rejected';
    public const FEE_REMINDER = 'fee_reminder';
    public const ABSENCE_REPORTED = 'absence_reported';
    public const LOW_ATTENDANCE = 'low_attendance';
    public const GRADE_PUBLISHED = 'grade_published';
    public const BULLETIN_PUBLISHED = 'bulletin_published';
    public const LOW_GRADES = 'low_grades';
    public const ENROLLMENT_CREATED = 'enrollment_created';
    public const REENROLLMENT_CREATED = 'reenrollment_created';

    /** @param array<string, mixed> $payload */
    private function __construct(
        public readonly string $event,
        public readonly array $payload,
    ) {}

    public static function paymentReceived(int $paiementId): self
    {
        return new self(self::PAYMENT_RECEIVED, ['paiement_id' => $paiementId]);
    }

    public static function paymentRejected(int $paiementId): self
    {
        return new self(self::PAYMENT_REJECTED, ['paiement_id' => $paiementId]);
    }

    public static function feeReminder(int $paiementId, int $daysPending, int $reminderCount): self
    {
        return new self(self::FEE_REMINDER, [
            'paiement_id' => $paiementId,
            'days_pending' => $daysPending,
            'reminder_count' => $reminderCount,
        ]);
    }

    public static function absenceReported(ESBTPAttendance $attendance): self
    {
        return new self(self::ABSENCE_REPORTED, ['attendance' => self::snapshot($attendance)]);
    }

    public static function lowAttendance(ESBTPAttendance $attendance, float $attendanceRate, int $threshold): self
    {
        return new self(self::LOW_ATTENDANCE, [
            'attendance' => self::snapshot($attendance),
            'attendance_rate' => $attendanceRate,
            'threshold' => $threshold,
        ]);
    }

    public static function gradePublished(int $noteId): self
    {
        return new self(self::GRADE_PUBLISHED, ['note_id' => $noteId]);
    }

    public static function bulletinPublished(int $bulletinId): self
    {
        return new self(self::BULLETIN_PUBLISHED, ['bulletin_id' => $bulletinId]);
    }

    public static function lowGrades(int $bulletinId): self
    {
        return new self(self::LOW_GRADES, ['bulletin_id' => $bulletinId]);
    }

    public static function enrollmentCreated(int $inscriptionId): self
    {
        return new self(self::ENROLLMENT_CREATED, ['inscription_id' => $inscriptionId]);
    }

    public static function reEnrollmentCreated(int $inscriptionId, string $decision, float $reliquatAmount): self
    {
        return new self(self::REENROLLMENT_CREATED, [
            'inscription_id' => $inscriptionId,
            'decision' => $decision,
            'reliquat_amount' => $reliquatAmount,
        ]);
    }

    /** @return array<string, mixed> */
    public function attendanceSnapshot(): array
    {
        $snapshot = $this->payload['attendance'] ?? [];

        return is_array($snapshot) ? $snapshot : [];
    }

    /**
     * Rebuild the in-memory attendance the workflow service expects. The model
     * stays unsaved on purpose so the outbox falls back to session identity.
     */
    public function attendance(): ?ESBTPAttendance
    {
        $snapshot = $this->attendanceSnapshot();
        if (! isset($snapshot['etudiant_id'])) {
            return null;
        }

        $attendance = new ESBTPAttendance();
        foreach ($snapshot as $key => $value) {
            $attendance->setAttribute($key, $value);
        }

        return $attendance;
    }

    /** @return array<string, mixed> */
    private static function snapshot(ESBTPAttendance $attendance): array
    {
        $date = $attendance->getAttribute('date');

        return [
            'etudiant_id' => $attendance->getAttribute('etudiant_id'),
            'seance_cours_id' => $attendance->getAttribute('seance_cours_id'),
            'matiere_id' => $attendance->getAttribute('matiere_id'),
            'classe_id' => $attendance->getAttribute('classe_id'),
            'date' => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date,
            'heure_debut' => self::timeToString($attendance->getAttribute('heure_debut')),
            'heure_fin' => self::timeToString($attendance->getAttribute('heure_fin')),
            'statut' => $attendance->getAttribute('statut'),
            'type_activite' => $attendance->getAttribute('type_activite'),
            'commentaire' => $attendance->getAttribute('commentaire'),
        ];
    }

    private static function timeToString(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return $value === null ? null : (string) $value;
    }
}
