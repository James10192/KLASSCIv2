<?php

namespace Tests\Feature\Mobile;

use App\Helpers\InstallationHelper;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Navbar du layout dans le shell mobile (issue #963, lot 1).
 *
 * Sous 768px, pour une personne profilée, la navbar de bureau devient une app
 * bar : menu, logo de l'école, cloche, avatar. L'avatar ouvre la feuille
 * m-navbar-plus qui reprend ce que la barre ne garde pas (recherche, actions
 * rapides, messages, profil, déconnexion). La barre d'onglets porte une
 * pastille unique sous l'onglet actif. Sans shell (réglage coupé, ou personne
 * sans profil), le DOM de bureau est rendu tel quel et rien de tout cela
 * n'apparaît.
 */
class NavbarMobileShellTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['identity.student', 'notes.view_own', 'attendances.view_own', 'annonces.view', 'system.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
        MobileProfileResolver::oublier();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_la_navbar_porte_l_avatar_qui_ouvre_la_feuille_et_la_pastille_des_onglets(): void
    {
        $user = $this->etudiantInscrit();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('has-m-shell m-profile-etudiant', false);

        // App bar : les trois menus de bureau sont marqués (masqués par CSS), l'avatar mobile est là.
        $this->assertSame(3, substr_count($response->getContent(), 'data-mnb="desktop"'));
        $response->assertSee('id="mnb-avatar"', false);
        $response->assertSee("detail: { id: 'm-navbar-plus' }", false);

        // Feuille m-navbar-plus : recherche, actions rapides adoptées, entrées du profil étudiant.
        $response->assertSee('data-m-sheet="m-navbar-plus"', false);
        $response->assertSee('id="m-navbar-plus-actions"', false);
        $response->assertSee(route('search.results'), false);
        $response->assertSee('Annonces');
        $response->assertSee('Préférences');
        // Sans system.manage : pas de lien « Paramètres » (ni bouton grisé, ni 403).
        $response->assertDontSee('>Paramètres<', false);

        // Barre d'onglets : une seule pastille, posée sous l'onglet actif (Accueil = index 0).
        $this->assertSame(1, substr_count($response->getContent(), 'm-bottomnav-pill'));
        $response->assertSee('--m-pill-i: 0;', false);
    }

    public function test_reglage_coupe_le_dom_de_bureau_est_rendu_tel_quel(): void
    {
        $user = $this->etudiantInscrit();
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('has-m-shell', false);
        $response->assertDontSee('mnb-avatar', false);
        $response->assertDontSee('m-navbar-plus', false);
        $response->assertDontSee('m-bottomnav-pill', false);
        // Les menus de bureau restent en place, avec leur marqueur inerte.
        $response->assertSee('id="profileDropdown"', false);
        $response->assertSee('id="quick-actions-list"', false);
    }

    private function etudiantInscrit(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo(['identity.student', 'notes.view_own', 'attendances.view_own', 'annonces.view']);

        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $etudiant = ESBTPEtudiant::factory()->create(['user_id' => $user->id]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
        ]);

        return $user;
    }
}
