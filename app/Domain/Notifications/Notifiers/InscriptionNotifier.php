<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;

/**
 * Notifier du domaine "Inscription / Réinscription".
 *
 * Strangler fig — Phase 8a shell : délègue temporairement au NotificationService legacy.
 * La logique sera extraite en Phase 8b puis Phase 8c (suppression legacy).
 *
 * Méthodes publiques exposées (extraction du NotificationService) :
 * - inscriptionCreated($inscription, array $credentials) — notif parents avec identifiants
 * - reinscriptionCreated($inscription, ?string $decision, float $reliquatMontant)
 * - nouvelleInscription($inscription, ?User $createdBy) — notif non-étudiants (admin)
 * - nouvelleReinscription($inscription, ?User $createdBy)
 */
class InscriptionNotifier extends AbstractNotifier
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
        return 'inscription';
    }

    public function inscriptionCreated(ESBTPInscription $inscription, array $credentials): void
    {
        $this->legacy->notifyParentsInscriptionCreated($inscription, $credentials);
    }

    public function reinscriptionCreated(
        ESBTPInscription $inscription,
        ?string $decision = null,
        float $reliquatMontant = 0,
    ): void {
        $this->legacy->notifyParentsReinscriptionCreated($inscription, $decision, $reliquatMontant);
    }

    public function nouvelleInscription(ESBTPInscription $inscription, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewInscription($inscription, $createdBy);
    }

    public function nouvelleReinscription(ESBTPInscription $inscription, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewReinscription($inscription, $createdBy);
    }
}
