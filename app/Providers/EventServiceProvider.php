<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import all the events
use App\Events\PaiementRecu;
use App\Events\SeuilAtteint;
use App\Events\RelanceEnvoyee;
use App\Events\KPIsCalcules;
use App\Events\TeacherAttendanceValidated;
use App\Events\WorkflowStepCompleted;

// Import all the listeners
use App\Listeners\EnvoyerNotificationPaiement;
use App\Listeners\NotifyWorkflowNextStepActors;
use App\Listeners\GererSeuilAtteint;
use App\Listeners\TraiterRelanceEnvoyee;
use App\Listeners\MettreAJourDashboard;
use App\Listeners\UpdatePlanificationHours;
use App\Listeners\AuditPermissionChange;

// Audit infrastructure
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\Setting;
use App\Models\User;
use App\Observers\ESBTPEvaluationLmdTeacherObserver;
use App\Observers\ESBTPPlanificationTeacherPoolObserver;
use App\Observers\ESBTPSeanceCoursLmdTeacherObserver;
use App\Observers\InvalideurRdv;
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

        PaiementRecu::class => [EnvoyerNotificationPaiement::class],
        SeuilAtteint::class => [GererSeuilAtteint::class],
        RelanceEnvoyee::class => [TraiterRelanceEnvoyee::class],
        KPIsCalcules::class => [MettreAJourDashboard::class],
        TeacherAttendanceValidated::class => [UpdatePlanificationHours::class],
        WorkflowStepCompleted::class => [NotifyWorkflowNextStepActors::class],
        \App\Domain\Comptabilite\Reconciliation\Events\ReconciliationClosed::class => [
            \App\Domain\Comptabilite\Reconciliation\Listeners\LockPaymentsAfterReconciliation::class,
        ],
    ];

    public function boot()
    {
        parent::boot();

        // En LMD, evaluations ET seances consomment la resolution du professeur
        // pour leur classe. Le planning garde le pool de professeurs possibles.
        ESBTPEvaluation::observe(ESBTPEvaluationLmdTeacherObserver::class);
        ESBTPSeanceCours::observe(ESBTPSeanceCoursLmdTeacherObserver::class);
        ESBTPPlanificationAcademique::observe(ESBTPPlanificationTeacherPoolObserver::class);

        Setting::observe(SettingObserver::class);
        ESBTPCandidature::observe(InvalideurRdv::class);

        $audit = app(AuditPermissionChange::class);

        Event::listen('eloquent.pivotAttached: ' . User::class, function (...$args) use ($audit) {
            $audit->handlePivotAttached($args[0] ?? null, array_slice($args, 1));
        });

        Event::listen('eloquent.pivotDetached: ' . User::class, function (...$args) use ($audit) {
            $audit->handlePivotDetached($args[0] ?? null, array_slice($args, 1));
        });

        Role::saved(function ($role) use ($audit) { $audit->handleRoleSaved($role); });
        Role::deleted(function ($role) use ($audit) { $audit->handleRoleDeleted($role); });
        Permission::saved(function ($permission) use ($audit) { $audit->handlePermissionSaved($permission); });
        Permission::deleted(function ($permission) use ($audit) { $audit->handlePermissionDeleted($permission); });
    }
}