<?php

namespace App\Providers;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPSeanceCours;
use App\Policies\AbsenceJustificationPolicy;
use App\Policies\ESBTPBulletinPolicy;
use App\Policies\ESBTPInscriptionPolicy;
use App\Policies\ESBTPMatierePolicy;
use App\Policies\ESBTPNotePolicy;
use App\Policies\ESBTPPaiementPolicy;
use App\Policies\ESBTPSeanceCoursPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ESBTPSeanceCours::class => ESBTPSeanceCoursPolicy::class,
        ESBTPMatiere::class => ESBTPMatierePolicy::class,
        ESBTPPaiement::class => ESBTPPaiementPolicy::class,
        ESBTPNote::class => ESBTPNotePolicy::class,
        ESBTPInscription::class => ESBTPInscriptionPolicy::class,
        ESBTPBulletin::class => ESBTPBulletinPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // superAdmin court-circuite tous les checks (Lot 0).
        Gate::before(function ($user, $ability) {
            return $user && $user->hasRole('superAdmin') ? true : null;
        });

        // `users.manage` : exposé en Gate explicite pour éviter toute ambiguïté
        // entre les routes qui consomment Gate::* et le résolveur Spatie.
        Gate::define('users.manage', function ($user) {
            return $user && $user->hasPermissionTo('users.manage');
        });

        // W5 : workflow justification d'absence (3 abilities sur ESBTPAttendance).
        // ESBTPAttendance n'a pas de Policy dédiée (CRUD vit ailleurs), donc on
        // route ces abilities explicitement vers AbsenceJustificationPolicy.
        Gate::define('submit', function ($user, ESBTPAttendance $absence) {
            return app(AbsenceJustificationPolicy::class)->submit($user, $absence);
        });
        Gate::define('process', function ($user, ESBTPAttendance $absence) {
            return app(AbsenceJustificationPolicy::class)->process($user, $absence);
        });
        Gate::define('viewDocument', function ($user, ESBTPAttendance $absence) {
            return app(AbsenceJustificationPolicy::class)->viewDocument($user, $absence);
        });

        // Lot 5 : la matrice "qui peut gérer qui" et l'assignation de rôle
        // passent par UserManagementPolicy. Les anciens Gate::define
        // 'manage-user' / 'assign-role' ont été retirés (jamais appelés).
    }
}
