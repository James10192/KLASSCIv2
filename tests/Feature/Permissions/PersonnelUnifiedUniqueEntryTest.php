<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La seule page publique de gestion du personnel est /esbtp/personnel/unified.
 * Les anciennes routes métier ne doivent pas ressusciter un écran parallèle.
 */
class PersonnelUnifiedUniqueEntryTest extends TestCase
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
    }

    private function administrateur(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        foreach (['personnel.manage', 'personnel.view', 'users.manage', 'admin.access'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('superAdmin');
        $user->givePermissionTo(['personnel.manage', 'personnel.view', 'users.manage', 'admin.access']);

        return $user;
    }

    public function test_gestionnaire_avec_personnel_manage_sans_personnel_view_peut_ouvrir_unified(): void
    {
        Role::findOrCreate('roleTestPersonnelGestionSeule', 'web');
        Permission::findOrCreate('personnel.manage', 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('roleTestPersonnelGestionSeule');
        $user->givePermissionTo('personnel.manage');

        $this->actingAs($user)
            ->get(route('esbtp.personnel.unified.index'))
            ->assertOk();
    }

    public function test_un_coordinateur_habilite_peut_consulter_l_onglet_de_sa_propre_fonction(): void
    {
        Role::findOrCreate('coordinateur', 'web');
        foreach (['personnel.view', 'coordinateurs.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $acteur = User::factory()->create(['is_active' => true]);
        $acteur->assignRole('coordinateur');
        $acteur->givePermissionTo(['personnel.view', 'coordinateurs.view']);

        // L'identité coordinateur n'est jamais un motif pour masquer cet onglet.
        $this->actingAs($acteur)
            ->get(route('esbtp.personnel.unified.index'))
            ->assertOk()
            ->assertSee('data-tab="coordinateurs"', false);
    }

    public function test_lecteur_du_personnel_peut_consulter_sans_pouvoir_ecrire(): void
    {
        $role = Role::findOrCreate('roleTestPersonnelLectureSeule', 'web');
        Permission::findOrCreate('personnel.view', 'web');
        $role->givePermissionTo('personnel.view');

        $lecteur = User::factory()->create(['is_active' => true]);
        $lecteur->assignRole($role);

        $this->actingAs($lecteur)
            ->get(route('esbtp.personnel.unified.index'))
            ->assertOk();

        $this->actingAs($lecteur)
            ->get(route('esbtp.personnel.unified.stats'))
            ->assertOk();

        $this->actingAs($lecteur)
            ->postJson(route('esbtp.personnel.unified.store'), [
                'type' => 'secretaire',
                'name' => 'Ne doit pas passer',
            ])->assertForbidden();

        $this->actingAs($lecteur)
            ->deleteJson(route('esbtp.personnel.unified.destroy', [
                'type' => 'secretaire', 'id' => $lecteur->id,
            ]))->assertForbidden();
    }

    public function test_stats_repondent_zero_si_le_role_standard_n_existe_pas(): void
    {
        $this->actingAs($this->administrateur());

        $resultat = $this->getJson(route('esbtp.personnel.unified.stats'))
            ->assertOk();

        foreach (['directeurs_etudes', 'coordinateurs', 'secretaires'] as $groupe) {
            $this->assertIsArray($resultat->json($groupe));
            foreach (['total', 'actifs', 'inactifs', 'nouveau_ce_mois'] as $cle) {
                $this->assertIsInt($resultat->json("{$groupe}.{$cle}"));
            }
        }
    }

    public function test_anciennes_pages_personnel_et_roles_redirigent_vers_unified(): void
    {
        $this->actingAs($this->administrateur());

        $this->get(route('esbtp.personnel.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index'));

        $this->get(route('esbtp.roles.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index'));

        $this->get(route('esbtp.custom-roles.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index'));
    }

    public function test_page_technique_roles_redirige_vers_la_section_roles_unifiee(): void
    {
        Role::findOrCreate('serviceTechnique', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('serviceTechnique');
        $user->givePermissionTo(Permission::findOrCreate('system.technical_access', 'web'));
        $this->actingAs($user);

        $this->get(route('esbtp.roles-permissions.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index', ['section' => 'roles']));
    }

    public function test_creation_unifiee_refuse_un_role_sans_permission_d_affectation(): void
    {
        $acteur = User::factory()->create(['is_active' => true]);
        $acteur->givePermissionTo(Permission::findOrCreate('personnel.manage', 'web'));
        Role::findOrCreate('coordinateur', 'web');

        $this->actingAs($acteur)
            ->postJson(route('esbtp.personnel.unified.store'), [
                'type' => 'coordinateur',
                'name' => 'Agent Permission Absente',
                'email' => 'role-non-autorise@example.test',
            ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'role-non-autorise@example.test']);
    }

    public function test_creation_unifiee_attribue_role_et_audit_dans_la_meme_transaction(): void
    {
        $role = Role::findOrCreate('coordinateur', 'web');
        $acteur = User::factory()->create(['is_active' => true]);
        // La route historique coordinateurs.create exige aussi la porte admin.
        foreach (['personnel.manage', 'personnel.roles.assign', 'admin.access'] as $permission) {
            $acteur->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->actingAs($acteur)
            ->postJson(route('esbtp.personnel.unified.store'), [
                'type' => 'coordinateur',
                'name' => 'Agent Role Audite',
                'email' => 'role-audite@example.test',
            ])->assertOk()->assertJsonPath('success', true);

        $cible = User::query()->where('email', 'role-audite@example.test')->firstOrFail();
        $this->assertTrue($cible->hasRole($role->name));
        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $cible->id,
            'user_id' => $acteur->id,
            'tags' => 'permissions',
        ]);
    }

    public function test_anciens_endpoints_personnel_ne_proposent_plus_de_mutations(): void
    {
        foreach ([
            'esbtp.personnel.store',
            'esbtp.personnel.update',
            'esbtp.personnel.destroy',
            'esbtp.personnel.toggle-status',
            'esbtp.personnel.bulk-action',
        ] as $route) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($route), $route);
        }

        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('esbtp.personnel.unified.store')
        );
    }

    public function test_un_compte_inactif_avec_deux_roles_personnalises_figure_dans_les_deux_onglets(): void
    {
        $roleA = Role::findOrCreate('roleTestPersonnelAlpha', 'web');
        $roleA->is_custom = true;
        $roleA->label_fr = 'Métier Alpha';
        $roleA->save();

        $roleB = Role::findOrCreate('roleTestPersonnelBeta', 'web');
        $roleB->is_custom = true;
        $roleB->label_fr = 'Métier Beta';
        $roleB->save();

        $personne = User::factory()->create([
            'name' => 'Personnel Inactif Multi Role',
            'is_active' => false,
        ]);
        $personne->assignRole([$roleA, $roleB]);

        $this->actingAs($this->administrateur());
        $page = $this->get(route('esbtp.personnel.unified.index'));
        $page->assertOk()->assertSee('Métier Alpha')->assertSee('Métier Beta');

        // Le même compte doit figurer sur chacune des deux cartes de rôle.
        $this->assertGreaterThanOrEqual(2, substr_count($page->getContent(), $personne->email));
        $page->assertSee('Inactif');
    }

    public function test_kpi_total_ne_compte_pas_deux_fois_une_personne_multi_roles(): void
    {
        $this->actingAs($this->administrateur());
        $total = static function (string $html): int {
            $pattern = '~<div class="pu-hero-kpi-value">\\s*(\\d+)\\s*</div>\\s*<div class="pu-hero-kpi-label">Total</div>~';
            \PHPUnit\Framework\Assert::assertSame(1, preg_match($pattern, $html));
            preg_match($pattern, $html, $matches);
            return (int) $matches[1];
        };

        $avant = $total($this->get(route('esbtp.personnel.unified.index'))->assertOk()->getContent());
        Role::findOrCreate('secretaire', 'web');
        Role::findOrCreate('comptable', 'web');
        $personne = User::factory()->create(['is_active' => true]);
        $personne->assignRole(['secretaire', 'comptable']);
        $apres = $total($this->get(route('esbtp.personnel.unified.index'))->assertOk()->getContent());
        $this->assertSame($avant + 1, $apres);
    }

    public function test_api_de_liste_custom_roles_reste_json_sur_la_page_unifiee(): void
    {
        $this->actingAs($this->administrateur());
        $this->getJson(route('esbtp.custom-roles.index'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
