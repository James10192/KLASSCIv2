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
 * Analytics prédictifs en mobile (issue #963, lot 2a, maquette
 * S['comptable:analytics']) : l'écran m-* est rendu à côté du DOM de bureau
 * pour une personne profilée « comptable », chaque bouton d'action reste
 * sous sa permission, le tirer-pour-rafraîchir relit un état `mobile` dans
 * le JSON de /refresh, et le formulaire de paramètres s'enregistre en JSON
 * sans rechargement.
 */
class AnalyticsMobileTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'comptabilite.access',
        'comptabilite.dashboard.view',
        'comptabilite.analytics.view',
        'comptabilite.analytics.refresh',
        'comptabilite.analytics.run_now',
        'comptabilite.analytics.configure',
        'comptabilite.recouvrement.access',
    ];

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();

        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe :
        // sans lui, toutes les pages repondent 302 vers /install.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // comptabilite.access sans module.caisse.access : le profil mobile
        // résolu est « comptable » (cascade de MobileProfileResolver).
        $this->comptable = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->comptable->givePermissionTo(self::PERMISSIONS);

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
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.analytics.index'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            ->assertSee('m-only-desktop', false)
            ->assertSee('m-only-mobile m-screen anm-screen', false)
            // App bar : titre, retour vers le tableau de bord, recalcul et export sous leurs permissions.
            ->assertSee('Analytics')
            ->assertSee(route('esbtp.comptabilite.dashboard'), false)
            ->assertSee('aria-label="Recalculer les prédictions"', false)
            ->assertSee('aria-label="Exporter"', false)
            // Héro : la prévision porte son mois et son niveau de confiance (jamais un chiffre nu).
            ->assertSee('Prévision d’encaissement', false)
            ->assertSee('confidence_label', false)
            // Risque : trois tranches lues dans metadata.buckets, pas dans taux_risque_pct.
            ->assertSee('etat.risk.buckets.bas', false)
            ->assertSee('etat.risk.buckets.moyen', false)
            ->assertSee('etat.risk.buckets.haut', false)
            ->assertDontSee('taux_risque_pct', false)
            // Bandeaux d'avertissement : couverture des échéanciers et saturation du score.
            ->assertSee('etat.coverage.is_low', false)
            ->assertSee('etat.risk.is_saturated', false)
            // « Pourquoi » : les raisons de chaque prédicteur.
            ->assertSee('Pourquoi ces chiffres')
            // Feuilles : fiche étudiant + exports (mêmes routes que le bureau) + paramètres.
            ->assertSee('data-m-sheet="anm-fiche"', false)
            ->assertSee('data-m-sheet="anm-exports"', false)
            ->assertSee(route('esbtp.comptabilite.analytics.export-excel'), false)
            ->assertSee(route('esbtp.comptabilite.analytics.settings'), false)
            // La fabrique Alpine est exposée sous garde, et le DOM de bureau reste là.
            ->assertSee("if (typeof window.anMobile !== 'function')", false)
            ->assertSee('an-hero', false);
    }

    public function test_reglage_coupe_seul_le_dom_de_bureau_est_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.analytics.index'));

        $reponse->assertOk()
            ->assertDontSee('has-m-shell', false)
            ->assertDontSee('anm-screen', false)
            ->assertDontSee('data-m-sheet="anm-fiche"', false)
            ->assertSee('an-hero', false);
    }

    public function test_sans_permission_de_recalcul_le_bouton_n_existe_pas(): void
    {
        $this->comptable->revokePermissionTo('comptabilite.analytics.run_now');

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.analytics.index'));

        $reponse->assertOk()
            ->assertSee('anm-screen', false)
            ->assertDontSee('aria-label="Recalculer les prédictions"', false)
            // La fabrique Alpine de BUREAU (pre-existante) porte l'URL en dur dans son
            // <script> : on verifie donc la configuration de l'ecran mobile, qui est
            // ce que cet ecran controle.
            ->assertSee('\u0022runNow\u0022:null', false);
    }

    public function test_le_rafraichissement_json_porte_l_etat_mobile(): void
    {
        $reponse = $this->actingAs($this->comptable)->getJson(route('esbtp.comptabilite.analytics.refresh'));

        $reponse->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'cash_flow',
                'default_risk',
                'mobile' => [
                    'devise',
                    'cash_flow' => ['available', 'confidence', 'confidence_label', 'explanation'],
                    'risk' => ['available', 'total', 'buckets' => ['bas', 'moyen', 'haut'], 'pct', 'top', 'is_saturated', 'explanation'],
                    'coverage' => ['is_low', 'minimum_pct'],
                    'echeancier' => ['fallback'],
                    'anomalies' => ['critical', 'warning'],
                    'never_computed',
                ],
            ]);

        // Une prédiction indisponible garde un libellé de confiance et une raison lisible.
        $mobile = $reponse->json('mobile');
        $this->assertContains($mobile['cash_flow']['confidence_label'], ['Indicatif', 'Fiable', 'Très fiable']);
        if (! $mobile['risk']['available']) {
            $this->assertNotEmpty($mobile['risk']['reason']);
        }
    }

    public function test_les_parametres_mobiles_sont_rendus_sur_le_meme_etat_que_le_bureau(): void
    {
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.analytics.settings'));

        $reponse->assertOk()
            ->assertSee('m-only-desktop', false)
            ->assertSee('m-only-mobile m-screen asm-screen', false)
            ->assertSee(route('esbtp.comptabilite.analytics.index'), false)
            // Trois segments, pas d'onglets ; champs m-field, bascule m-opt, barre d'action collante.
            ->assertSee('Risque')
            ->assertSee('Anomalies')
            ->assertSee('Message')
            ->assertSee('id="asm-dr-threshold_high"', false)
            ->assertSee('id="asm-an-z_critical"', false)
            ->assertSee('form.anomaly.notifications_enabled', false)
            ->assertSee('form="asm-form"', false)
            // Le seuil affiché vient des réglages, pas d'un littéral.
            ->assertSee('form.default_risk.threshold_high', false)
            // Une seule fabrique, exposée sous garde, partagée par les deux rendus.
            ->assertSee("if (typeof window.settingsPage !== 'function')", false)
            ->assertSee('as-hero', false);
    }

    public function test_l_enregistrement_des_parametres_repond_en_json(): void
    {
        $charge = $this->chargeValide();
        $charge['default_risk']['threshold_high'] = 0.71;
        $charge['anomaly']['notifications_enabled'] = '0';

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.analytics.settings.update'), $charge);

        $reponse->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('settings.default_risk.threshold_high', 0.71)
            ->assertJsonPath('settings.anomaly.notifications_enabled', false);

        $this->assertSame('0.71', (string) SettingsHelper::get('analytics.default_risk.threshold_high'));
        $this->assertSame('0', (string) SettingsHelper::get('analytics.anomaly.notifications_enabled'));
    }

    public function test_un_seuil_hors_bornes_renvoie_les_erreurs_par_champ(): void
    {
        $charge = $this->chargeValide();
        $charge['default_risk']['threshold_high'] = 1.5;

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.analytics.settings.update'), $charge);

        $reponse->assertStatus(422)
            ->assertJsonValidationErrors(['default_risk.threshold_high']);
    }

    public function test_le_formulaire_de_bureau_redirige_toujours(): void
    {
        $reponse = $this->actingAs($this->comptable)
            ->post(route('esbtp.comptabilite.analytics.settings.update'), $this->chargeValide());

        $reponse->assertRedirect(route('esbtp.comptabilite.analytics.settings'))
            ->assertSessionHas('success');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function chargeValide(): array
    {
        $reglages = SettingsHelper::getAnalyticsSettings();

        return [
            'default_risk' => $reglages['default_risk'],
            'anomaly' => array_merge($reglages['anomaly'], [
                'notifications_enabled' => $reglages['anomaly']['notifications_enabled'] ? '1' : '0',
            ]),
            'recouvrement' => $reglages['recouvrement'],
        ];
    }
}
