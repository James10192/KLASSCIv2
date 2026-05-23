<?php

namespace App\Domain\Notifications;

use App\Domain\Notifications\Contracts\NotifierInterface;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ParentNotificationLog;
use App\Models\ParentNotificationPreference;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base abstraite des notifiers du domaine Notifications.
 *
 * Fournit :
 * - Injection des services canaux (Mail, WhatsApp, SMS)
 * - Helper destinatairesFor() pour résoudre étudiants + parents + préférences
 * - Helper logDispatch() pour audit + tracking coûts dans parent_notification_logs
 * - Helper safeExecute() pour wrapper try/catch + logging des erreurs canal
 *
 * Les notifiers concrets héritent et exposent des méthodes d'événement métier.
 * Voir RelanceNotifier, InscriptionNotifier, PaiementNotifier, etc.
 */
abstract class AbstractNotifier implements NotifierInterface
{
    public function __construct(
        protected WhatsAppService $whatsappService,
        protected SmsService $smsService,
    ) {
    }

    /**
     * Récupère ou crée les préférences de notification d'un parent.
     */
    protected function preferencesFor(ESBTPParent $parent): ParentNotificationPreference
    {
        return $parent->getOrCreateNotificationPreferences();
    }

    /**
     * Résout les destinataires d'une notification pour un étudiant donné.
     *
     * Retourne une collection de destinataires avec leurs canaux préférés.
     * Respecte les préférences opt-in/opt-out de chaque parent.
     *
     * @return array<int, array{
     *     type: 'etudiant'|'parent',
     *     recipient: ESBTPEtudiant|ESBTPParent,
     *     user_id: int|null,
     *     phone: string|null,
     *     email: string|null,
     *     channels: array<int, string>,
     *     preferences: ParentNotificationPreference|null
     * }>
     */
    protected function destinatairesFor(ESBTPEtudiant $etudiant, string $notificationType): array
    {
        $destinataires = [];

        $etudiant->loadMissing(['parents', 'user']);

        // Cible 1 : étudiant (canal in-app via son compte User)
        if ($etudiant->user) {
            $destinataires[] = [
                'type' => 'etudiant',
                'recipient' => $etudiant,
                'user_id' => $etudiant->user->id,
                'phone' => $etudiant->telephone ?? null,
                'email' => $etudiant->email ?? null,
                'channels' => ['app'],
                'preferences' => null,
            ];
        }

        // Cible 2 : parents/tuteurs (canaux selon préférences chacun)
        foreach ($etudiant->parents as $parent) {
            $preferences = $this->preferencesFor($parent);

            if (! $preferences->isNotificationEnabled($notificationType)) {
                continue;
            }

            $destinataires[] = [
                'type' => 'parent',
                'recipient' => $parent,
                'user_id' => $etudiant->user?->id,
                'phone' => $parent->telephone,
                'email' => $parent->email,
                'channels' => $preferences->preferred_channels ?? ['app', 'email'],
                'preferences' => $preferences,
            ];
        }

        return $destinataires;
    }

    /**
     * Log un dispatch dans parent_notification_logs pour audit + tracking coûts.
     *
     * Coûts indicatifs (Rest of Africa 2026) :
     *  - app : 0 FCFA
     *  - email : 0 FCFA
     *  - whatsapp Utility : ~2.4 FCFA (0.0040 USD * 600 XOF/USD)
     *  - whatsapp Marketing : ~15 FCFA (0.025 USD)
     *  - sms : 6-8 FCFA selon provider
     */
    protected function logDispatch(
        ?int $parentId,
        ?int $etudiantId,
        string $notificationType,
        string $channel,
        string $status,
        string $recipient,
        ?string $externalId = null,
        float $costFcfa = 0.0,
        array $metadata = [],
    ): ?ParentNotificationLog {
        try {
            return ParentNotificationLog::create([
                'parent_id' => $parentId,
                'etudiant_id' => $etudiantId,
                'notification_type' => $notificationType,
                'channel' => $channel,
                'status' => $status,
                'recipient' => $recipient,
                'external_id' => $externalId,
                'cost_fcfa' => $costFcfa,
                'metadata' => $metadata,
                'sent_at' => in_array($status, ['sent', 'delivered', 'read'], true) ? now() : null,
                'failed_at' => $status === 'failed' ? now() : null,
            ]);
        } catch (Throwable $e) {
            // Ne JAMAIS bloquer une notification métier à cause d'un échec log.
            Log::error('Échec log notification dispatch', [
                'notifier' => $this->domain(),
                'channel' => $channel,
                'status' => $status,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Wrapper try/catch pour exécution sécurisée d'une opération canal.
     *
     * Loggue les erreurs sans propager - utile pour ne pas bloquer la notification
     * principale si un canal secondaire échoue.
     */
    protected function safeExecute(string $operation, callable $callback, array $context = []): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error("[{$this->domain()}] $operation failed", array_merge($context, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return null;
        }
    }
}
