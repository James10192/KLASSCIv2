<?php

namespace Tests\Feature\Mobile;

use App\Enums\CashSessionStatus;
use App\Helpers\InstallationHelper;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\ESBTPCashSessionController;
use App\Models\ESBTPCashSession;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ma caisse + clôture dans le shell mobile (issue #963, lot 2a — caissier,
 * maquette S['caissier:caisse']).
 *
 * Sous 992px : app bar « Ma caisse », héro « Attendu en caisse (espèces) »,
 * comptage par coupure avec total en direct, écart avant de clôturer, barre
 * d'action → feuille de confirmation → POST JSON. Le DOM de bureau reste rendu
 * dans .m-only-desktop. Sans shell, rien de mobile n'est rendu.
 */
class MaCaisseMobileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['module.caisse.access', 'cash_session.manage', 'paiements.create'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_l_ecran_mobile_porte_le_hero_le_comptage_par_coupure_et_la_feuille_de_cloture(): void
    {
        $caissier = $this->caissier(['paiements.create']);
        ESBTPPaiement::factory()->create([
            'created_by' => $caissier->id,
            'montant' => 25000,
            'mode_paiement' => 'espèces',
            'status' => 'validé',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($caissier)->get(route('esbtp.caisse.ma-caisse'));

        $response->assertOk();
        $response->assertSee('has-m-shell m-profile-caissier', false);
        $response->assertSee('m-only-mobile m-screen mcm-screen', false);
        $response->assertSee('m-only-desktop', false);
        $response->assertSee('Attendu en caisse (espèces)');
        // Les coupures viennent du contrôleur (constante documentée), pas de la vue.
        $response->assertSee($this->fragmentJson(['coupures' => ESBTPCashSessionController::COUPURES_FCFA]), false);
        $response->assertSee($this->fragmentJson(['attendu' => 25000.0]), false);
        $response->assertSee($this->fragmentJson(['verrouillee' => false]), false);
        // Barre d'action + feuille de confirmation : la permission est là.
        $response->assertSee('Clôturer et générer le bordereau');
        $response->assertSee('data-m-sheet="mcm-cloturer"', false);
        $response->assertSee('window.mcmCaisse', false);
        $response->assertDontSee('window.location.reload()', false);
    }

    public function test_une_journee_deja_cloturee_rend_le_resume_et_pas_le_comptage(): void
    {
        $caissier = $this->caissier();
        ESBTPCashSession::query()->create([
            'cashier_user_id' => $caissier->id,
            'business_date' => now()->toDateString(),
            'status' => CashSessionStatus::CLOSED,
            'opened_at' => now()->subHours(3),
            'closed_at' => now(),
            'counted_amount' => 15000,
            'expected_amount' => 15000,
            'variance' => 0,
        ]);

        $response = $this->actingAs($caissier)->get(route('esbtp.caisse.ma-caisse'));

        $response->assertOk();
        $response->assertSee($this->fragmentJson(['verrouillee' => true]), false);
        $response->assertSee($this->fragmentJson(['variance' => 0.0]), false);
        $response->assertSee('Voir le bordereau');
    }

    public function test_la_cloture_en_json_verrouille_la_caisse_et_renvoie_le_resume(): void
    {
        $caissier = $this->caissier();
        ESBTPPaiement::factory()->create([
            'created_by' => $caissier->id,
            'montant' => 10000,
            'mode_paiement' => 'espèces',
            'status' => 'validé',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($caissier)->postJson(route('esbtp.caisse.cloturer'), [
            'counted_amount' => 8000,
            'notes' => 'Billet de 2 000 rendu par erreur.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('session.status', CashSessionStatus::CLOSED->value);
        // Les montants sont comparés en valeur : json_encode peut rendre 8000 ou 8000.0.
        $this->assertEqualsWithDelta(8000.0, (float) $response->json('session.counted_amount'), 0.001);
        $this->assertEqualsWithDelta(10000.0, (float) $response->json('session.expected_amount'), 0.001);
        $this->assertEqualsWithDelta(-2000.0, (float) $response->json('session.variance'), 0.001);
        $response->assertJsonPath('bordereau_url', route('esbtp.caisse.bordereau', ['preview' => 1]));
        $this->assertStringContainsString('manquant', $response->json('message'));

        $this->assertTrue(
            ESBTPCashSession::query()->where('cashier_user_id', $caissier->id)->first()->isLocked()
        );
    }

    public function test_une_seconde_cloture_en_json_repond_409_sans_toucher_la_session(): void
    {
        $caissier = $this->caissier();
        $this->actingAs($caissier)->postJson(route('esbtp.caisse.cloturer'), ['counted_amount' => 0])->assertOk();

        $response = $this->actingAs($caissier)->postJson(route('esbtp.caisse.cloturer'), ['counted_amount' => 500]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
        $this->assertSame(
            '0.00',
            (string) ESBTPCashSession::query()->where('cashier_user_id', $caissier->id)->first()->counted_amount
        );
    }

    public function test_un_montant_compte_invalide_en_json_repond_422(): void
    {
        $caissier = $this->caissier();

        $this->actingAs($caissier)
            ->postJson(route('esbtp.caisse.cloturer'), ['counted_amount' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['counted_amount']);
    }

    public function test_la_cloture_classique_redirige_toujours(): void
    {
        $caissier = $this->caissier();

        $response = $this->actingAs($caissier)->post(route('esbtp.caisse.cloturer'), ['counted_amount' => 0]);

        $response->assertRedirect(route('esbtp.caisse.ma-caisse'));
        $response->assertSessionHas('success');
    }

    public function test_sans_shell_mobile_rien_de_mobile_n_est_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();
        $caissier = $this->caissier();

        $response = $this->actingAs($caissier)->get(route('esbtp.caisse.ma-caisse'));

        $response->assertOk();
        $response->assertDontSee('m-only-mobile m-screen mcm-screen', false);
        $response->assertDontSee('window.mcmCaisse', false);
        $response->assertSee('Registre du jour');
    }

    /**
     * Fragment JSON tel que Js::from le sérialise dans x-data (mêmes drapeaux HEX).
     */
    private function fragmentJson(array $valeur): string
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

        return trim((string) json_encode($valeur, $flags), '{}');
    }

    private function caissier(array $permissions = []): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['module.caisse.access', 'cash_session.manage'], $permissions));

        return $user;
    }
}
