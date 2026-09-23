<?php

namespace Tests\Feature\Enseignants;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Creer un compte enseignant exige « Creer un enseignant ».
 *
 * Les routes enseignants ne demandaient qu'une identite (directeur des etudes,
 * scolarite…) et le module : un directeur des etudes ou un responsable
 * scolarite, sans la permission, creait des comptes par le formulaire classique
 * comme par la creation rapide des seances et du planning.
 */
class CreationEnseignantPermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['identity.direct_studies', 'module.enseignants.access', 'teachers.create'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();
    }

    private function directeur(bool $peutCreer): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_filter([
            'identity.direct_studies',
            'module.enseignants.access',
            $peutCreer ? 'teachers.create' : null,
        ]));

        return $user->fresh();
    }

    public function test_sans_la_permission_aucune_porte_ne_cree_d_enseignant(): void
    {
        $user = $this->directeur(false);

        $this->actingAs($user)->postJson(route('esbtp.enseignants.quick-create'), [
            'name' => 'Enseignant Fantome', 'specialization' => 'Maths',
        ])->assertForbidden()
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Créer un enseignant'));

        $this->actingAs($user)->post(route('esbtp.enseignants.store'), [
            'name' => 'Enseignant Fantome', 'specialization' => 'Maths',
        ])->assertForbidden();

        $this->actingAs($user)->get(route('esbtp.enseignants.create'))->assertForbidden();

        $this->assertDatabaseMissing('users', ['name' => 'Enseignant Fantome']);
    }

    public function test_avec_la_permission_la_creation_rapide_passe_a_la_validation(): void
    {
        // 422 et non 403 : la demande est autorisee, seul le contenu est refuse.
        $this->actingAs($this->directeur(true))
            ->postJson(route('esbtp.enseignants.quick-create'), [])
            ->assertStatus(422);
    }
}
