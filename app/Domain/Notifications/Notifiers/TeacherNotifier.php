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
}
