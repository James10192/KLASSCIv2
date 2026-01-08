<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPMatiere;
use App\Policies\ESBTPSeanceCoursPolicy;
use App\Policies\ESBTPMatierePolicy;

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
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage-users', function ($user) {
            return $user && $user->hasRole('superAdmin');
        });
    }
}
