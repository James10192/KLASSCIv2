<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import all the events
use App\Events\PaiementRecu;
use App\Events\DepenseApprouvee;
use App\Events\BonApprouve;
use App\Events\SeuilAtteint;
use App\Events\RelanceEnvoyee;
use App\Events\KPIsCalcules;
use App\Events\TeacherAttendanceValidated;
use App\Events\WorkflowStepCompleted;

// Import all the listeners
use App\Listeners\EnvoyerNotificationPaiement;
use App\Listeners\NotifyWorkflowNextStepActors;
use App\Listeners\MettreAJourKPIs;
use App\Listeners\NotifierBonApprouve;
use App\Listeners\GererSeuilAtteint;
use App\Listeners\TraiterRelanceEnvoyee;
use App\Listeners\MettreAJourDashboard;
use App\Listeners\UpdatePlanificationHours;
use App\Listeners\AuditPermissionChange;

// Audit infrastructure
use App\Models\Setting;
use App\Models\User;
use App\Observers\SettingObserver;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Événements comptabilité ESBTP
        PaiementRecu::class => [
            EnvoyerNotificationPaiement::class,
            MettreAJourKPIs::class,
        ],

        DepenseApprouvee::class => [
            // Listeners pour les dépenses approuvées peuvent être ajoutés ici
        ],

        BonApprouve::class => [
            NotifierBonApprouve::class,
        ],

        SeuilAtteint::class => [
            GererSeuilAtteint::class,
        ],

        RelanceEnvoyee::class => [
            TraiterRelanceEnvoyee::class,
        ],

        KPIsCalcules::class => [
            MettreAJourDashboard::class,
        ],

        // Événements émargement enseignants
        TeacherAttendanceValidated::class => [
            UpdatePlanificationHours::class,
        ],

        // Workflow inscription → notifs event-driven (issue #298)
        WorkflowStepCompleted::class => [
            NotifyWorkflowNextStepActors::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // ─── Audit infrastructure ──────────────────────────────────────────
        // Observer custom pour les Settings (KV pairs hétéroclites,
        // ne passe pas par le trait Auditable).
        Setting::observe(SettingObserver::class);

        // Listener pour les changements rôles/permissions Spatie.
        // Spatie 5.x ne dispatche pas d'events natifs RoleAttached/Detached,
        // on s'appuie sur les Eloquent pivot events.
        $audit = app(AuditPermissionChange::class);

        Event::listen('eloquent.pivotAttached: ' . User::class, function (...$args) use ($audit) {
            // Eloquent passe (model, relation, ids, attrs)
            $audit->handlePivotAttached($args[0] ?? null, array_slice($args, 1));
        });

        Event::listen('eloquent.pivotDetached: ' . User::class, function (...$args) use ($audit) {
            $audit->handlePivotDetached($args[0] ?? null, array_slice($args, 1));
        });

        // CRUD direct sur Role et Permission (création/renommage/suppression).
        Role::saved(function ($role) use ($audit) {
            $audit->handleRoleSaved($role);
        });
        Role::deleted(function ($role) use ($audit) {
            $audit->handleRoleDeleted($role);
        });
        Permission::saved(function ($permission) use ($audit) {
            $audit->handlePermissionSaved($permission);
        });
        Permission::deleted(function ($permission) use ($audit) {
            $audit->handlePermissionDeleted($permission);
        });
    }
}
