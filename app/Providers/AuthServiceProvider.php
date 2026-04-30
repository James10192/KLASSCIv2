<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPNote;
use App\Models\ESBTPInscription;
use App\Models\ESBTPBulletin;
use App\Policies\ESBTPSeanceCoursPolicy;
use App\Policies\ESBTPMatierePolicy;
use App\Policies\ESBTPPaiementPolicy;
use App\Policies\ESBTPNotePolicy;
use App\Policies\ESBTPInscriptionPolicy;
use App\Policies\ESBTPBulletinPolicy;

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

        Gate::before(function ($user, $ability) {
            return $user && $user->hasRole('superAdmin') ? true : null;
        });

        Gate::define('manage-users', function ($user) {
            return $user && $user->hasPermissionTo('manage-users');
        });
    }
}
