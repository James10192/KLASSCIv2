<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;

/**
 * Notifier du domaine "Absences / Présences".
 *
 * Strangler fig — Phase 8a shell : délègue au NotificationService legacy.
 *
 * Méthodes publiques :
 * - nouvelleAbsence($attendance, $etudiant) — notif étudiant + parents avec stats mensuelles
 * - justificationSoumise($absence, $etudiant) — notif admins (justification à valider)
 * - justificationApprouvee($absence, $etudiant, ?$approvedBy)
 * - justificationRejetee($absence, $etudiant, ?$reason, ?$rejectedBy)
 */
class AbsenceNotifier extends AbstractNotifier
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
        return 'absence';
    }

    public function nouvelleAbsence(ESBTPAttendance $absence, ESBTPEtudiant $etudiant): void
    {
        $this->legacy->notifyNewAbsence($absence, $etudiant);
        $this->legacy->notifyParentsAbsence($absence);
    }

    public function justificationSoumise(ESBTPAttendance $absence, ESBTPEtudiant $etudiant): void
    {
        $this->legacy->notifyAbsenceJustificationSubmitted($absence, $etudiant);
    }

    public function justificationApprouvee(
        ESBTPAttendance $absence,
        ESBTPEtudiant $etudiant,
        ?User $approvedBy = null,
    ): void {
        $this->legacy->notifyAbsenceJustificationApproved($absence, $etudiant, $approvedBy);
    }

    public function justificationRejetee(
        ESBTPAttendance $absence,
        ESBTPEtudiant $etudiant,
        ?string $reason = null,
        ?User $rejectedBy = null,
    ): void {
        $this->legacy->notifyAbsenceJustificationRejected($absence, $etudiant, $reason, $rejectedBy);
    }
}
