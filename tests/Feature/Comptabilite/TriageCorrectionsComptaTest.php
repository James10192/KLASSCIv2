<?php

namespace Tests\Feature\Comptabilite;

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Corrections issues du triage des bugs remontés par le lot mobile comptable
 * (issue #963) :
 *  - le tableau de bord comptable exige comptabilite.dashboard.view, comme le
 *    lien de la barre latérale et les autres routes du même groupe ;
 *  - la page de planification avancée des relances a une route GET ;
 *  - SettingsHelper::set() transmet l'auteur, plus le groupe, à Setting::set() ;
 *  - le moteur d'envoi lit d'abord les modèles enregistrés par l'école.
 */
class TriageCorrectionsComptaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['comptabilite.access', 'comptabilite.dashboard.view', 'comptabilite.relances.send'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    private function utilisateur(array $permissions): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_le_tableau_de_bord_est_refuse_sans_comptabilite_dashboard_view(): void
    {
        $this->actingAs($this->utilisateur(['comptabilite.access']))
            ->get(route('esbtp.comptabilite.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->utilisateur(['comptabilite.access']))
            ->getJson(route('esbtp.comptabilite.dashboard.data'))
            ->assertForbidden();
    }

    public function test_le_tableau_de_bord_est_servi_avec_la_permission(): void
    {
        $this->actingAs($this->utilisateur(['comptabilite.access', 'comptabilite.dashboard.view']))
            ->get(route('esbtp.comptabilite.dashboard'))
            ->assertOk();
    }

    public function test_la_planification_avancee_a_une_route_gardee_par_relances_send(): void
    {
        $this->actingAs($this->utilisateur(['comptabilite.access']))
            ->get(route('esbtp.comptabilite.relances.planification-avancee'))
            ->assertForbidden();

        $this->actingAs($this->utilisateur(['comptabilite.access', 'comptabilite.relances.send']))
            ->get(route('esbtp.comptabilite.relances.planification-avancee'))
            ->assertOk()
            ->assertSee(route('esbtp.comptabilite.relances.planifier.avancees'), false)
            ->assertSee(route('esbtp.comptabilite.relances.preview.segmentation'), false);
    }

    public function test_settings_helper_set_ecrit_l_auteur_et_non_le_groupe(): void
    {
        $user = $this->utilisateur(['comptabilite.access']);
        Setting::setOrCreate('triage.cle_de_test', 'avant', 'general', 'string');

        $this->actingAs($user);
        $this->assertTrue(SettingsHelper::set('triage.cle_de_test', 'apres'));

        $ligne = Setting::where('key', 'triage.cle_de_test')->first();
        $this->assertSame('apres', $ligne->value);
        $this->assertSame($user->id, (int) $ligne->updated_by);
    }

    public function test_le_moteur_d_envoi_lit_le_modele_enregistre_par_l_ecole(): void
    {
        Setting::setOrCreate('relances.template_sms_niveau_1', '{acronyme} : bonjour {prenom}, reste {montant_dette}.', 'relances', 'text');
        Setting::setOrCreate('relances.template_email_niveau_2', '   ', 'relances', 'text'); // vide → repli

        $service = new NotificationService();
        $sms = new \ReflectionMethod($service, 'getTemplateSMS');
        $sms->setAccessible(true);
        $email = new \ReflectionMethod($service, 'getTemplateEmail');
        $email->setAccessible(true);

        $this->assertSame('{acronyme} : bonjour {prenom}, reste {montant_dette}.', $sms->invoke($service, 1));
        // Un modèle blanc ne remplace pas le texte par défaut.
        $this->assertStringContainsString('DEUXIÈME RAPPEL', $email->invoke($service, 2));
    }
}
