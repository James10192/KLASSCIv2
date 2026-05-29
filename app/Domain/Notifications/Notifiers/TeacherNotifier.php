<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Carbon\Carbon;

/**
 * Notifier du domaine "Enseignants" (émargement, codes attendance).
 *
 * Strangler fig — Phase 8a shell : délègue au NotificationService legacy.
 *
 * Méthodes publiques :
 * - codeAttendanceGenere($teacher, $code, $className, $expiresAt) — notif teacher
 * - adminsEmargementSigne($teacher, $className) — notif admins post-émargement
 */
class TeacherNotifier extends AbstractNotifier
{
    public function __construct(
        WhatsAppService $whatsappService,
        SmsService $smsService,
        protected NotificationService $legacy,
    ) {
        parent::__construct($whatsappService, $smsService);
    }

    public function domain(): string
    {
        return 'teacher';
    }

    public function codeAttendanceGenere(
        User $teacher,
        string $code,
        string $className,
        Carbon $expiresAt,
    ): void {
        $this->legacy->notifyTeacherAttendanceCodeGenerated($teacher, $code, $className, $expiresAt);
    }

    public function adminsEmargementSigne(User $teacher, string $className): void
    {
        $this->legacy->notifyAdminsTeacherAttendanceSigned($teacher, $className);
    }

    /**
     * Coordinateurs notifiés qu'un teacher a signé l'émargement d'une séance.
     * Différent de adminsEmargementSigne — passe l'objet $seanceCours pour contexte.
     */
    public function coordinateurEmargementSigne(User $teacher, $seanceCours): void
    {
        $this->legacy->notifyCoordinateurTeacherAttendanceSigned($teacher, $seanceCours);
    }

    /**
     * Coordinateurs notifiés qu'un teacher a complété l'appel des étudiants d'une séance.
     */
    public function coordinateurAppelEffectue(User $teacher, $seanceCours, array $attendanceData): void
    {
        $this->legacy->notifyCoordinateurStudentRollCallCompleted($teacher, $seanceCours, $attendanceData);
    }

    /**
     * Coordinateurs notifiés qu'un teacher a clos un cours (avec notes optionnelles).
     */
    public function coordinateurCoursClos(User $teacher, $seanceCours, ?string $notes = null): void
    {
        $this->legacy->notifyCoordinateurCourseClosed($teacher, $seanceCours, $notes);
    }

    /**
     * Étudiants absents notifiés (auto par teacher après appel).
     * @param array $absentStudents Liste d'objets ESBTPEtudiant ou IDs
     */
    public function etudiantsAbsenceConstatee(array $absentStudents, $seanceCours, User $teacher): void
    {
        $this->legacy->notifyStudentsAbsence($absentStudents, $seanceCours, $teacher);
    }
}
