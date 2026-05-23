<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;

/**
 * Notifier du domaine "Bulletins / Notes".
 *
 * Strangler fig — Phase 8a shell : délègue au NotificationService legacy.
 *
 * Méthodes publiques :
 * - bulletinPublie($bulletin) — notif parents avec moyenne + rang + mention
 * - alerteNotesFaibles($bulletin) — alerte si moyenne < seuil ou matières en échec
 * - notePubliee($note, ?$createdBy) — notif étudiant pour nouvelle note publiée
 */
class BulletinNotifier extends AbstractNotifier
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
        return 'bulletin';
    }

    public function bulletinPublie(ESBTPBulletin $bulletin): void
    {
        $this->legacy->notifyParentsBulletinPublished($bulletin);
    }

    public function alerteNotesFaibles(ESBTPBulletin $bulletin): void
    {
        $this->legacy->notifyParentsLowGrades($bulletin);
    }

    public function notePubliee(ESBTPNote $note, ?User $createdBy = null): void
    {
        $this->legacy->notifyStudentNoteAdded($note, $createdBy);
    }
}
