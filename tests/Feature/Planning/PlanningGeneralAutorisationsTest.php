<?php

namespace Tests\Feature\Planning;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlanningGeneralAutorisationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\PaywallMiddleware::class);
    }

    /** @return array<string, array{string, string, array<string, int>}> */
    public function ecrituresDuPlanning(): array
    {
        return [
            'enregistrer' => ['POST', 'esbtp.planning-general.store-planification', []],
            'supprimer' => ['DELETE', 'esbtp.planning-general.destroy-planification', ['id' => 999999]],
            'valider' => ['POST', 'esbtp.planning-general.valider-planification', ['id' => 999999]],
            'configuration rapide' => ['POST', 'esbtp.planning-general.configure-rapide', []],
            'volumes' => ['POST', 'esbtp.planning-general.save-volume-configuration', []],
            'code emargement' => ['POST', 'esbtp.planning-general.generer-code-emargement', []],
        ];
    }

    /** @dataProvider ecrituresDuPlanning */
    public function test_une_personne_en_lecture_seule_ne_peut_pas_ecrire(string $methode, string $route, array $parametres): void
    {
        $this->actingAs($this->personneAvec(['identity.registrar', 'timetables.view_all']));

        $this->call($methode, route($route, $parametres), [], [], [], ['HTTP_ACCEPT' => 'application/json'])
            ->assertForbidden();
    }

    /** @dataProvider ecrituresDuPlanning */
    public function test_planning_manage_franchit_la_garde(string $methode, string $route, array $parametres): void
    {
        $this->actingAs($this->personneAvec(['identity.registrar', 'planning.manage']));

        $reponse = $this->call($methode, route($route, $parametres), [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertNotSame(403, $reponse->getStatusCode());
    }

    public function test_la_route_de_test_du_planning_n_existe_plus(): void
    {
        $this->assertFalse(Route::has('esbtp.planning-general.test'));
    }

    public function test_un_role_personnalise_qui_enseigne_est_propose_a_la_creation_d_evaluation(): void
    {
        $permission = Permission::findOrCreate('identity.teach', 'web');
        $role = Role::findOrCreate('vacataire_personnalise', 'web');
        $role->givePermissionTo($permission);
        $enseignant = User::withoutEvents(fn () => User::factory()->create());
        $enseignant->assignRole($role);
        $direct = User::withoutEvents(fn () => User::factory()->create());
        $direct->givePermissionTo($permission);
        $autre = User::withoutEvents(fn () => User::factory()->create());

        $ids = User::permission('identity.teach')->pluck('id');

        $this->assertTrue($ids->contains($enseignant->id));
        $this->assertTrue($ids->contains($direct->id));
        $this->assertFalse($ids->contains($autre->id));
    }

    /** @param string[] $permissions */
    private function personneAvec(array $permissions): User
    {
        Permission::findOrCreate('module.emploi_temps.access', 'web');
        foreach ($permissions as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->givePermissionTo(['module.emploi_temps.access', ...$permissions]);

        return $user->fresh();
    }
}
