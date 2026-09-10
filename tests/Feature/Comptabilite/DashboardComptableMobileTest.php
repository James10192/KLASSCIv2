<?php

namespace Tests\Feature\Comptabilite;

use App\Helpers\SettingsHelper;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tableau de bord comptable en mobile (issue #963, lot 2a, maquette
 * S['comptable:dash']) : l'écran m-* est rendu à côté du DOM de bureau pour une
 * personne profilée « comptable », les liens d'action suivent les permissions,
 * et l'endpoint JSON des filtres porte la série des encaissements récents que la
 * courbe SVG mobile trace.
 */
class DashboardComptableMobileTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS_BASE = ['comptabilite.access', 'comptabilite.dashboard.view'];

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();

        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe :
        // sans lui, toutes les pages repondent 302 vers /install.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        foreach ([...self::PERMISSIONS_BASE, 'comptabilite.recouvrement.access', 'paiements.validate', 'comptabilite.relances.send'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // comptabilite.access sans module.caisse.access : profil mobile « comptable ».
        $this->comptable = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->comptable->givePermissionTo(self::PERMISSIONS_BASE);

        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '1');
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_l_ecran_mobile_est_rendu_a_cote_du_dom_de_bureau(): void
    {
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.dashboard'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            ->assertSee('class="m-only-desktop" id="dm-bureau"', false)
            ->assertSee('m-only-mobile m-screen dm-screen', false)
            // App bar : « {école} · Finances », feuille de filtres, héro « Encaissé ».
            ->assertSee('· Finances')
            ->assertSee('aria-label="Filtrer par année, filière ou classe"', false)
            ->assertSee('data-m-sheet="dm-filtres"', false)
            ->assertSee('Encaissé')
            ->assertSee('Total frais dus')
            ->assertSee('Reste impayé')
            ->assertSee('Validés aujourd')
            ->assertSee('À faire aujourd')
            ->assertSee('derniers jours')
            // Fabrique Alpine exposée sous garde ; le DOM de bureau et son Chart.js restent là.
            ->assertSee("if (typeof window.dashComptaMobile !== 'function')", false)
            ->assertSee('dash-hero', false)
            ->assertSee('encaissementsChart', false);
    }

    public function test_les_actions_du_jour_suivent_les_permissions(): void
    {
        // Sans droit de recouvrement ni de validation : aucune de ces deux lignes.
        $sans = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.dashboard'));
        $sans->assertOk()
            ->assertDontSee(route('esbtp.comptabilite.recouvrement.index'), false)
            ->assertDontSee('File de recouvrement du jour')
            ->assertDontSee('Passer en revue, puis valider ou rejeter');

        $this->comptable->givePermissionTo(['comptabilite.recouvrement.access', 'paiements.validate']);
        $this->comptable->unsetRelation('permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $avec = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.dashboard'));
        $avec->assertOk()
            ->assertSee(route('esbtp.comptabilite.recouvrement.index'), false)
            ->assertSee('File de recouvrement du jour')
            ->assertSee('Passer en revue, puis valider ou rejeter');
    }

    public function test_reglage_coupe_seul_le_dom_de_bureau_est_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.dashboard'));

        $reponse->assertOk()
            ->assertDontSee('has-m-shell', false)
            ->assertDontSee('m-only-mobile m-screen dm-screen', false)
            ->assertDontSee('data-m-sheet="dm-filtres"', false)
            ->assertSee('dash-hero', false);
    }

    public function test_l_endpoint_json_porte_la_serie_des_encaissements_recents(): void
    {
        $reponse = $this->actingAs($this->comptable)->getJson(route('esbtp.comptabilite.dashboard.data'));

        $reponse->assertOk()
            ->assertJsonStructure([
                'totalDue', 'totalPaid', 'countToValidate', 'countValidatedToday',
                'serieRecente' => ['labels', 'data', 'total', 'jours'],
            ]);

        $serie = $reponse->json('serieRecente');
        $this->assertSame(30, $serie['jours']);
        $this->assertCount(30, $serie['labels']);
        $this->assertCount(30, $serie['data']);
        // Un jour sans encaissement vaut 0 : c'est une valeur, jamais un trou.
        foreach ($serie['data'] as $valeur) {
            $this->assertIsNumeric($valeur);
        }
        $this->assertEqualsWithDelta(array_sum($serie['data']), (float) $serie['total'], 0.001);
    }
}
