<?php

namespace App\Providers;

use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPParent;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use App\Policies\AbsenceJustificationPolicy;
use App\Policies\AcademicActorAssignmentPolicy;
use App\Policies\ESBTPBulletinPolicy;
use App\Policies\ESBTPInscriptionPolicy;
use App\Policies\ESBTPMatierePolicy;
use App\Policies\ESBTPNotePolicy;
use App\Policies\ESBTPParentPolicy;
use App\Policies\ESBTPPaiementPolicy;
use App\Policies\ESBTPSeanceCoursPolicy;
use App\Policies\GradeSheetDocumentPolicy;
use App\Policies\GradeSheetPolicy;
use App\Policies\UserManagementPolicy;
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
        ESBTPParent::class => ESBTPParentPolicy::class,
        ESBTPInscription::class => ESBTPInscriptionPolicy::class,
        ESBTPBulletin::class => ESBTPBulletinPolicy::class,
        GradeSheet::class => GradeSheetPolicy::class,
        GradeSheetDocument::class => GradeSheetDocumentPolicy::class,
        AcademicActorAssignment::class => AcademicActorAssignmentPolicy::class,
        User::class => UserManagementPolicy::class,
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
            if (! config('permissions.superadmin_gate_before', true)) {
                return null;
            }

            return $user && $user->hasRole('superAdmin') ? true : null;
        });

        Gate::after(function ($user, $ability, $result) {
            if ($result === true || ! $user) {
                return $result === true ? true : null;
            }

            return app(\App\Services\ScolariteClerkCapabilities::class)->grants($user, $ability) ?: null;
        });

        // Peut-on EMMENER cet utilisateur au formulaire d'inscription, ou lui
        // en montrer le lien ?
        //
        // Un Gate, et non un helper appele des deux cotes, parce que la vue
        // demandait deja `@can('inscriptions.create')` : une permission seule,
        // alors que la route exige aussi l'une des permissions d'identite. Un
        // agent d'inscription taille sur mesure par une ecole — ce que le
        // produit encourage — voyait donc le bouton « Creer l'inscription » et
        // recevait un 403 en cliquant. Le contrôleur avait été corrigé, la vue
        // non : la moitié posée rassurait sans protéger.
        Gate::define(
            'inscriptions.ouvrir-formulaire',
            static fn ($utilisateur) => \App\Support\PorteDeRoute::ouverte('esbtp.inscriptions.create', $utilisateur)
        );

        // Mêmes portes, pour le menu latéral. Deux entrées y montraient un lien
        // qui répond 403 : leurs routes n'ont pas de permission propre et héritent
        // de celle de leur groupe, que la barre ne connaissait pas.
        //
        // Recopier ces listes dans la vue aurait marché le jour même. « Planning
        // Général » le prouve : sa route est définie DEUX fois, et celle qui gagne
        // est dans un groupe qui pose une SECONDE permission — deux middlewares
        // valent « et », donc la moitié recopiée laissait passer un rôle que la
        // page refuse. `gatherMiddleware()` ramasse les deux, et la conjonction
        // est déjà traitée ici.
        foreach ([
            'esbtp.seances-cours.index',
            'esbtp.planning-general.index',
        ] as $porte) {
            Gate::define(
                "porte:{$porte}",
                static fn ($utilisateur) => \App\Support\PorteDeRoute::ouverte($porte, $utilisateur)
            );
        }

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
