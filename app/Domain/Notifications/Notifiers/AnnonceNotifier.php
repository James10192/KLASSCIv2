<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\ESBTPAnnonce;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;

/**
 * Notifier du domaine "Annonces".
 *
 * Strangler fig — Phase 8a shell : délègue au NotificationService legacy.
 *
 * Méthodes publiques :
 * - annonceCreee($annonce, ?$sentBy) — notif étudiants destinataires (selon type)
 * - annonceAdminsCreee($annonce, ?$createdBy) — notif admins (audit broadcast)
 */
class AnnonceNotifier extends AbstractNotifier
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
        return 'annonce';
    }

    public function annonceCreee(ESBTPAnnonce $annonce, ?User $sentBy = null): void
    {
        $this->legacy->notifyNewAnnouncement($annonce, $sentBy);
    }

    public function annonceAdminsCreee(ESBTPAnnonce $annonce, ?User $createdBy = null): void
    {
        $this->legacy->notifyAdminsNewAnnouncement($annonce, $createdBy);
    }
}
