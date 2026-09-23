<?php

namespace Tests\Feature\Permissions;

use App\Domain\Permissions\AccesTemporaireRefuse;
use App\Domain\Permissions\AccesTemporaires;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Une permission ouverte pour un temps limite tient tant que sa date n'est pas
 * passee, et tombe d'elle-meme ensuite — sans tache planifiee.
 */
class AccesTemporairesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['notes.edit', 'paiements.view', 'identity.coordinate', 'identity.student', 'permissions.temporaires.manage', 'personnel.manage', 'users.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('superAdmin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): AccesTemporaires
    {
        // Le memo vit le temps d'une requete : on repart d'une instance neuve.
        app()->forgetInstance(AccesTemporaires::class);

        return app(AccesTemporaires::class);
    }

    private function peut(User $user, string $ability): bool
    {
        app()->forgetInstance(AccesTemporaires::class);

        return $user->fresh()->can($ability);
    }

    public function test_l_acces_ouvre_la_permission_puis_tombe_a_l_echeance(): void
    {
        $coord = User::factory()->create();
        $this->assertFalse($this->peut($coord, 'notes.edit'));

        $this->service()->accorder($coord, 'notes.edit', now(), now()->addDays(2), 'Correction des notes S2', $this->admin);
        $this->assertTrue($this->peut($coord, 'notes.edit'));

        Carbon::setTestNow(now()->addDays(2)->addMinute());
        $this->assertFalse($this->peut($coord, 'notes.edit'));
    }

    public function test_un_acces_retire_ne_donne_plus_rien(): void
    {
        $coord = User::factory()->create();
        $grant = $this->service()->accorder($coord, 'notes.edit', now(), now()->addDay(), 'Correction des notes S2', $this->admin);

        $this->service()->retirer($grant, $this->admin);

        $this->assertFalse($this->peut($coord, 'notes.edit'));
        $this->assertNotNull($grant->fresh()->revoked_at);
    }

    public function test_un_acces_a_venir_n_ouvre_rien_avant_son_debut(): void
    {
        $coord = User::factory()->create();
        $this->service()->accorder($coord, 'notes.edit', now()->addDay(), now()->addDays(3), 'Session de rattrapage', $this->admin);

        $this->assertFalse($this->peut($coord, 'notes.edit'));
        Carbon::setTestNow(now()->addDays(2));
        $this->assertTrue($this->peut($coord, 'notes.edit'));
    }

    public function test_on_ne_donne_pas_ce_qu_on_ne_detient_pas(): void
    {
        $auteur = User::factory()->create();
        $auteur->givePermissionTo('permissions.temporaires.manage');

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), now()->addDay(), 'Correction des notes S2', $auteur);
    }

    public function test_les_permissions_d_identite_ne_s_accordent_pas(): void
    {
        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'identity.coordinate', now(), now()->addDay(), 'Essai de promotion', $this->admin);
    }

    public function test_la_porte_financiere_lit_aussi_les_acces_temporaires(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->peut($user, 'finances.etudiants.voir'));

        $this->service()->accorder($user, 'paiements.view', now(), now()->addDay(), 'Audit ponctuel des paiements', $this->admin);

        $this->assertTrue($this->peut($user, 'finances.etudiants.voir'));
    }

    public function test_l_ecran_accorde_puis_retire_un_acces(): void
    {
        $coord = User::factory()->create();

        $reponse = $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $coord->id,
            'permission' => 'notes.edit',
            'fin' => now()->addDays(2)->format('Y-m-d H:i'),
            'motif' => 'Correction des notes du semestre 2',
        ]);
        $reponse->assertCreated()->assertJsonPath('acces.0.statut', 'active');

        $grant = TemporaryPermissionGrant::where('user_id', $coord->id)->firstOrFail();
        $this->actingAs($this->admin)->deleteJson(route('esbtp.acces-temporaires.destroy', $grant))
            ->assertOk()->assertJsonPath('acces.0.statut', 'retiree');
    }

    public function test_l_ecran_refuse_un_motif_trop_court_et_un_refus_metier(): void
    {
        $coord = User::factory()->create();

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $coord->id, 'permission' => 'notes.edit',
            'fin' => now()->addDay()->format('Y-m-d H:i'), 'motif' => 'court',
        ])->assertStatus(422)->assertJsonValidationErrors('motif');

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $coord->id, 'permission' => 'identity.coordinate',
            'fin' => now()->addDay()->format('Y-m-d H:i'), 'motif' => 'Essai de promotion interdit',
        ])->assertStatus(422);
    }

    public function test_l_ecran_est_ferme_sans_la_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('esbtp.acces-temporaires.index'))
            ->assertForbidden();
    }

    public function test_l_ecran_s_affiche_pour_l_administrateur(): void
    {
        $this->actingAs($this->admin)
            ->get(route('esbtp.acces-temporaires.index'))
            ->assertOk()
            ->assertSee('Accès temporaires');
    }

    public function test_ce_qui_modifie_comptes_roles_ou_reglages_ne_s_accorde_pas(): void
    {
        $service = $this->service();
        foreach (['personnel.manage', 'users.manage', 'settings.edit', 'system.manage', 'module.lmd.access', 'paywall.manage'] as $permission) {
            $this->assertFalse($service->estAccordable($permission), $permission);
        }
        $this->assertTrue($service->estAccordable('notes.edit'));
    }

    public function test_un_compte_etudiant_ne_recoit_rien(): void
    {
        $etudiant = User::factory()->create();
        $etudiant->givePermissionTo('identity.student');

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder($etudiant, 'paiements.view', now(), now()->addDay(), 'Essai sur un compte parent', $this->admin);
    }

    public function test_une_date_avec_decalage_est_enregistree_dans_le_fuseau_de_l_application(): void
    {
        config(['app.timezone' => 'UTC']);
        $fin = Carbon::parse(now('UTC')->addDays(2)->format('Y-m-d').'T10:00:00+01:00');

        $grant = $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), $fin, 'Correction des notes S2', $this->admin);

        $this->assertSame('09:00', $grant->fresh()->expires_at->format('H:i'));
    }

    public function test_deux_acces_qui_se_chevauchent_sont_refuses(): void
    {
        $coord = User::factory()->create();
        $this->service()->accorder($coord, 'notes.edit', now(), now()->addDays(3), 'Correction des notes S2', $this->admin);

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder($coord, 'notes.edit', now()->addDay(), now()->addDays(5), 'Deuxieme demande en double', $this->admin);
    }

    public function test_la_duree_maximale_est_tenue(): void
    {
        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), now()->addDays(AccesTemporaires::DUREE_MAX_JOURS_DEFAUT + 1), 'Beaucoup trop long', $this->admin);
    }

    public function test_un_acces_ne_se_programme_pas_au_dela_de_la_duree_maximale(): void
    {
        $debut = now()->addDays(AccesTemporaires::DUREE_MAX_JOURS_DEFAUT + 5);

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', $debut, $debut->copy()->addDay(), 'Programme bien trop loin', $this->admin);
    }

    public function test_sans_gerer_le_personnel_l_ecran_reste_ferme(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.temporaires.manage');

        $this->actingAs($user->fresh())->get(route('esbtp.acces-temporaires.index'))->assertForbidden();
    }
}
