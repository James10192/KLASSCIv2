<?php

namespace Tests\Feature\Comptabilite;

use App\Helpers\SettingsHelper;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Configuration des relances (issue #963, lot 2a) : page premium de bureau
 * (dashboard-header, namespace rlc-*) et écran mobile m-* rendus côte à côte,
 * modèles relus depuis les réglages, enregistrement des paramètres en JSON sans
 * rechargement, aperçu sans nom d'établissement écrit dans le code.
 */
class RelancesConfigurationMobileTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'comptabilite.access',
        'comptabilite.relances.send',
    ];

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

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

    private function poserReglageRelance(string $cle, string $valeur): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'relances.' . $cle],
            ['value' => $valeur, 'group' => 'relances', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_la_page_rend_le_bureau_premium_et_l_ecran_mobile(): void
    {
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.config'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            // Bureau : en-tête de formulaire standard (pas de hero), cartes rlc-*.
            ->assertSee('dashboard-header rlc-header', false)
            ->assertSee('m-only-desktop', false)
            ->assertSee('rlc-tabs', false)
            // Mobile : app bar, trois segments, feuilles d'édition et d'aperçu.
            ->assertSee('m-only-mobile m-screen rlcm-screen', false)
            ->assertSee('Configuration des relances')
            ->assertSee('Modèles')
            ->assertSee('Envoi')
            ->assertSee('Planification')
            ->assertSee('data-m-sheet="rlc-edit"', false)
            ->assertSee('data-m-sheet="rlc-apercu"', false)
            // Planification en pas-à-pas, sur les routes réelles.
            ->assertSee(route('esbtp.comptabilite.relances.preview.segmentation'), false)
            ->assertSee(route('esbtp.comptabilite.relances.planifier.avancees'), false)
            // Fabriques Alpine exposées sous garde.
            ->assertSee("if (typeof window.rlcConfig !== 'function')", false)
            ->assertSee("if (typeof window.rlcPlanif !== 'function')", false);
    }

    public function test_reglage_coupe_seul_le_dom_de_bureau_est_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.config'));

        $reponse->assertOk()
            ->assertDontSee('has-m-shell', false)
            ->assertDontSee('rlcm-screen', false)
            ->assertSee('rlc-tabs', false);
    }

    public function test_sans_le_droit_d_envoyer_rien_ne_s_enregistre_ni_ne_se_planifie(): void
    {
        $this->comptable->revokePermissionTo('comptabilite.relances.send');

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.config'));

        $reponse->assertOk()
            ->assertSee('Lecture seule')
            ->assertDontSee('Enregistrer les paramètres')
            ->assertDontSee('Enregistrer les modèles')
            ->assertDontSee(route('esbtp.comptabilite.relances.planifier.avancees'), false);
    }

    public function test_les_modeles_enregistres_sont_relus_avec_leur_sujet(): void
    {
        $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.relances.config.templates'), [
                'type' => 'email',
                'templates' => [
                    ['niveau' => 2, 'contenu' => 'Bonjour {prenom}, votre dette est de {montant_dette}.', 'sujet' => 'Deuxième rappel'],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(
            'Deuxième rappel',
            DB::table('settings')->where('key', 'relances.template_email_sujet_niveau_2')->value('value')
        );

        $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.config'))
            ->assertOk()
            ->assertSee('Deuxième rappel', false)
            ->assertSee('votre dette est de {montant_dette}', false);
    }

    public function test_l_enregistrement_des_parametres_repond_en_json_et_persiste(): void
    {
        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.relances.config.parametres'), [
                'delai_niveau_1' => 15,
                'delai_niveau_2' => 30,
                'delai_niveau_3' => 60,
                'montant_minimum' => 0,
                'heure_envoi' => '08:30',
                'relances_automatiques' => true,
            ]);

        $reponse->assertOk()->assertJson(['success' => true]);

        $this->assertSame('15', DB::table('settings')->where('key', 'relances.delai_niveau_1')->value('value'));
        $this->assertSame('0', DB::table('settings')->where('key', 'relances.montant_minimum')->value('value'));
        $this->assertSame('1', DB::table('settings')->where('key', 'relances.relances_automatiques')->value('value'));
        $this->assertSame('08:30', DB::table('settings')->where('key', 'relances.heure_envoi')->value('value'));
    }

    public function test_un_delai_hors_bornes_renvoie_les_erreurs_par_champ(): void
    {
        $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.relances.config.parametres'), [
                'delai_niveau_1' => 0,
                'delai_niveau_2' => 30,
                'delai_niveau_3' => 60,
                'montant_minimum' => 1000,
                'heure_envoi' => '08:30',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delai_niveau_1']);
    }

    public function test_le_formulaire_classique_redirige_toujours(): void
    {
        $this->actingAs($this->comptable)
            ->post(route('esbtp.comptabilite.relances.config.parametres'), [
                'delai_niveau_1' => 15,
                'delai_niveau_2' => 30,
                'delai_niveau_3' => 60,
                'montant_minimum' => 1000,
                'heure_envoi' => '08:30',
            ])
            ->assertRedirect(route('esbtp.comptabilite.relances.config'));
    }

    public function test_l_apercu_porte_le_nom_de_l_etablissement_des_reglages(): void
    {
        SettingsHelper::set('school_name', 'Institut Démo des Tests');

        $reponse = $this->actingAs($this->comptable)
            ->post(route('esbtp.comptabilite.relances.config.preview'), [
                'type' => 'courrier',
                'niveau' => 1,
                'contenu' => 'Rappel de {nom_ecole} pour {nom_complet}.',
            ]);

        $reponse->assertOk()
            ->assertSee('rlc-pv rlc-pv--courrier', false)
            ->assertSee('Institut Démo des Tests')
            ->assertSee('KOUAME Jean Pierre')
            ->assertDontSee('École Supérieure du Bâtiment');
    }

    public function test_les_delais_affiches_viennent_des_reglages(): void
    {
        $this->poserReglageRelance('delai_niveau_1', '21');
        $this->poserReglageRelance('delai_niveau_2', '42');
        $this->poserReglageRelance('delai_niveau_3', '84');

        $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.config'))
            ->assertOk()
            ->assertSee('21 j')
            ->assertSee('42 j')
            ->assertSee('84 j');
    }
}
