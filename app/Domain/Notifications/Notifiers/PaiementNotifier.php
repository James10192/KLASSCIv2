<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;

/**
 * Notifier du domaine "Paiements".
 *
 * Strangler fig — Phase 8a shell : délègue temporairement au NotificationService legacy.
 *
 * Méthodes publiques exposées :
 * - paiementCree($paiement) — notif création (admins + parents)
 * - paiementValide($paiement) — notif validation (parents avec KPIs financiers)
 * - paiementRejete($paiement) — notif rejet (parents avec motif)
 * - paiementRecu($paiement) — accusé réception email étudiant
 * - rappelPaiement($paiement) — relance rappel paiement en attente
 */
class PaiementNotifier extends AbstractNotifier
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
        return 'paiement';
    }

    public function paiementCree(ESBTPPaiement $paiement): void
    {
        $this->legacy->notifyPaiementCreated($paiement);
    }

    public function paiementValide(ESBTPPaiement $paiement, ?User $validatedBy = null): void
    {
        $validatedBy ??= auth()->user();
        if ($validatedBy instanceof User) {
            $this->legacy->notifyPaiementValide($paiement, $validatedBy);
        }
        $this->legacy->notifyParentsPaiementValide($paiement);
    }

    public function paiementRejete(ESBTPPaiement $paiement, ?User $rejectedBy = null, ?string $motif = null): void
    {
        $rejectedBy ??= auth()->user();
        if ($rejectedBy instanceof User) {
            $this->legacy->notifyPaiementRejete($paiement, $rejectedBy, $motif);
        }
        $this->legacy->notifyParentsPaiementRejete($paiement);
    }

    public function paiementRecu(ESBTPPaiement $paiement): array
    {
        return $this->legacy->notifierPaiementRecu($paiement);
    }

    public function rappelPaiement(ESBTPPaiement $paiement, int $daysPending = 0, int $reminderCount = 1): void
    {
        $this->legacy->sendPaiementReminder($paiement, $daysPending, $reminderCount);
    }
}
