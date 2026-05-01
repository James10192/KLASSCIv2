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
        //
    }
}
