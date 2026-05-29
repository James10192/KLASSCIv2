<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Carbon\Carbon;

/**
 * Notifier du domaine "Système / Académique".
 *
 * Strangler fig — Phase 8a shell : délègue au NotificationService legacy.
 *
 * Méthodes publiques (entités académiques créées + system maintenance) :
 * - nouvelleClasse, nouvelleFiliere, nouveauNiveau, nouvelleMatiere, nouvelleEvaluation
 * - bienvenueNouveauUser($user, $role) — onboarding compte
 * - maintenancePrevue($message, $scheduledAt)
 * - alertesCriticalCoordinateur($alerts, $date) — alertes dashboard coordinateur
 * - nettoyerAnciennesNotifications() — purge 30/90 jours
 * - getStatistiques() — KPIs
 */
class SystemNotifier extends AbstractNotifier
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
        return 'system';
    }

    public function nouvelleClasse($classe, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewClasse($classe, $createdBy);
    }

    public function nouvelleFiliere($filiere, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewFiliere($filiere, $createdBy);
    }

    public function nouveauNiveau($niveau, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewNiveauEtude($niveau, $createdBy);
    }

    public function nouvelleMatiere($matiere, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewMatiere($matiere, $createdBy);
    }

    public function nouvelleEvaluation($evaluation, ?User $createdBy = null): void
    {
        $this->legacy->notifyNewEvaluation($evaluation, $createdBy);
    }

    public function bienvenueNouveauUser(User $user, string $role): void
    {
        $this->legacy->notifyWelcomeNewUser($user, $role);
    }

    public function maintenancePrevue(string $message, Carbon $scheduledAt): void
    {
        $this->legacy->notifySystemMaintenance($message, $scheduledAt);
    }

    public function alertesCriticalCoordinateur(array $alerts, Carbon $date): void
    {
        $this->legacy->notifyCoordinateurCriticalAlerts($alerts, $date);
    }

    public function nettoyerAnciennes(): int
    {
        return $this->legacy->cleanupOldNotifications();
    }

    public function getStatistiques(): array
    {
        return $this->legacy->getNotificationStats();
    }
}
