<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\User;
use App\Support\ProfilActif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ChoixProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckInstalled::class, EnsureInstalled::class, PaywallMiddleware::class]);
        Role::findOrCreate('coordinateur', 'web');
        Role::findOrCreate('enseignant', 'web');
    }

    private function multiProfil(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole(['coordinateur', 'enseignant']);

        return $user;
    }

    public function test_connexion_multi_roles_impose_choix_et_garde_les_deux_roles(): void
    {
        $user = $this->multiProfil();

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect(route('profil.choisir'));

        $this->get('/dashboard')->assertRedirect(route('profil.choisir'));
        $this->get(route('profil.choisir'))->assertOk()
            ->assertSee('coordinateur')
            ->assertSee('enseignant');

        $this->post(route('profil.activer'), ['role' => 'enseignant'])
            ->assertSessionHas(ProfilActif::ROLE, 'enseignant')
            ->assertSessionHas(ProfilActif::UTILISATEUR, $user->id);

        $this->assertTrue($user->fresh()->hasAllRoles(['coordinateur', 'enseignant']));
    }

    public function test_compte_deja_connecte_peut_changer_de_profil(): void
    {
        $user = $this->multiProfil();
        $this->actingAs($user);
        $this->post(route('profil.activer'), ['role' => 'coordinateur'])
            ->assertSessionHas(ProfilActif::ROLE, 'coordinateur');
        $this->get(route('profil.choisir'))->assertOk()->assertSee('Profil actuel');
        $this->post(route('profil.activer'), ['role' => 'enseignant'])
            ->assertSessionHas(ProfilActif::ROLE, 'enseignant');
        $this->assertTrue($user->fresh()->hasAllRoles(['coordinateur', 'enseignant']));
    }

    public function test_profil_non_attribue_est_refuse_et_ne_change_pas_la_session(): void
    {
        $this->actingAs($this->multiProfil());
        $this->post(route('profil.activer'), ['role' => 'superAdmin'])
            ->assertSessionHasErrors('role');
        $this->assertNull(session(ProfilActif::ROLE));
    }

    public function test_ajax_multi_role_exige_choix_sans_500(): void
    {
        $this->actingAs($this->multiProfil());
        $this->getJson('/dashboard')
            ->assertStatus(409)
            ->assertJsonPath('redirect', route('profil.choisir'));
    }

    public function test_mono_role_na_pas_de_choix_obligatoire(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('enseignant');
        $this->actingAs($user)->get(route('profil.choisir'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_retrait_role_invalide_un_profil_en_session(): void
    {
        $user = $this->multiProfil();
        $this->actingAs($user);
        $this->post(route('profil.activer'), ['role' => 'coordinateur'])
            ->assertSessionHas(ProfilActif::ROLE, 'coordinateur');
        $user->removeRole('coordinateur');
        $this->assertNull(ProfilActif::courant(request()));
    }
}
