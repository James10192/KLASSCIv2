<?php

namespace App\Providers;

use App\Domain\Notifications\Notifiers\RelanceNotifier;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider pour le domaine Notifications (strangler fig de NotificationService).
 *
 * Enregistre les notifiers métier extraits du NotificationService god-class.
 * Chaque notifier est résolu en singleton via le container Laravel pour
 * permettre injection de dépendances + remplacement en test.
 *
 * Stratégie strangler fig :
 *  - Phase 8a (en cours) : architecture + RelanceNotifier extrait
 *  - Phase 8b : migration des callers (controllers, jobs, commands)
 *  - Phase 8c : suppression du NotificationService god-class
 *
 * Provider à enregistrer dans bootstrap/providers.php (Laravel 12).
 *
 * @see App\Domain\Notifications\AbstractNotifier
 * @see App\Domain\Notifications\Contracts\NotifierInterface
 * @see .claude/rules/no-god-code.md
 */
class NotificationDomainServiceProvider extends ServiceProvider
{
    /**
     * Liste des notifiers extraits du NotificationService god-class.
     *
     * Au fur et à mesure de l'extraction (Phase 8a → 8b → 8c), ajouter les
     * notifiers concrets ici : InscriptionNotifier, PaiementNotifier,
     * AbsenceNotifier, BulletinNotifier, AnnonceNotifier, SystemNotifier,
     * TeacherNotifier, ChatbotNotifier (Phase 10).
     */
    public array $singletons = [
        RelanceNotifier::class => RelanceNotifier::class,
    ];

    public function register(): void
    {
        // Bindings additionnels si nécessaire (ex: tags pour découverte automatique).
        $this->app->tag([
            RelanceNotifier::class,
        ], 'notifications.notifiers');
    }

    public function boot(): void
    {
        // Boot hooks (config, events, etc.) à venir avec les phases ultérieures.
    }
}
