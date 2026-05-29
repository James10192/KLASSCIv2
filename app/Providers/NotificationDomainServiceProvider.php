<?php

namespace App\Providers;

use App\Domain\Notifications\MultiChannelDispatcher;
use App\Domain\Notifications\Notifiers\AbsenceNotifier;
use App\Domain\Notifications\Notifiers\AnnonceNotifier;
use App\Domain\Notifications\Notifiers\BulletinNotifier;
use App\Domain\Notifications\Notifiers\InscriptionNotifier;
use App\Domain\Notifications\Notifiers\PaiementNotifier;
use App\Domain\Notifications\Notifiers\RelanceNotifier;
use App\Domain\Notifications\Notifiers\SystemNotifier;
use App\Domain\Notifications\Notifiers\TeacherNotifier;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider pour le domaine Notifications (strangler fig de NotificationService).
 *
 * Stratégie strangler fig :
 *  - Phase 8a : architecture + 8 notifiers (RelanceNotifier complet, autres en shell délégation)
 *  - Phase 8b : migration des callers + extraction code réel depuis NotificationService legacy
 *  - Phase 8c : suppression du NotificationService god-class
 *
 * @see App\Domain\Notifications\AbstractNotifier
 * @see App\Domain\Notifications\Contracts\NotifierInterface
 * @see .claude/rules/no-god-code.md
 */
class NotificationDomainServiceProvider extends ServiceProvider
{
    public array $singletons = [
        MultiChannelDispatcher::class => MultiChannelDispatcher::class,
        RelanceNotifier::class => RelanceNotifier::class,
        InscriptionNotifier::class => InscriptionNotifier::class,
        PaiementNotifier::class => PaiementNotifier::class,
        AbsenceNotifier::class => AbsenceNotifier::class,
        BulletinNotifier::class => BulletinNotifier::class,
        AnnonceNotifier::class => AnnonceNotifier::class,
        SystemNotifier::class => SystemNotifier::class,
        TeacherNotifier::class => TeacherNotifier::class,
    ];

    public function register(): void
    {
        // Tag tous les notifiers pour découverte automatique (dashboards, stats, etc.).
        $this->app->tag([
            RelanceNotifier::class,
            InscriptionNotifier::class,
            PaiementNotifier::class,
            AbsenceNotifier::class,
            BulletinNotifier::class,
            AnnonceNotifier::class,
            SystemNotifier::class,
            TeacherNotifier::class,
        ], 'notifications.notifiers');
    }

    public function boot(): void
    {
        // Boot hooks (config, events, etc.) à venir avec les phases ultérieures.
    }
}
