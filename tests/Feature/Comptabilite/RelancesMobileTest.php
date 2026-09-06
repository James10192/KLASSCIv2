<?php

namespace Tests\Feature\Comptabilite;

use App\Jobs\EnvoyerRelanceJob;
use App\Models\ESBTPInscription;
use App\Models\ESBTPRelance;
use App\Models\Setting;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Relances en mobile (issue #963, lot 2a, maquette S['comptable:relances']) :
 * la liste devient un historique de campagnes par canal, la fiche d'une relance
 * se renvoie en JSON, et le dossier d'un étudiant montre les relances
 * réellement enregistrées. Chaque action reste derrière sa permission.
 */
class RelancesMobileTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'comptabilite.access',
        'comptabilite.dashboard.view',
        'comptabilite.relances.send',
        'comptabilite.recouvrement.access',
        'comptabilite.config.manage',
        'paiements.create',
    ];

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        // comptabilite.access sans module.caisse.access : le profil mobile
        // résolu est « comptable » (cascade de MobileProfileResolver).
        $this->comptable = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->comptable->givePermissionTo(['comptabilite.access', 'comptabilite.dashboard.view']);

        $this->reglerShell('1');
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    /**
     * Active ou coupe le shell mobile. Écrit la ligne de réglage directement :
     * SettingsHelper::set() passe le groupe là où Setting::set() attend un
     * identifiant d'utilisateur, ce qu'une base stricte refuse.
     */
    private function reglerShell(string $valeur): void
    {
        Setting::where('key', MobileProfileResolver::REGLAGE_ACTIF)->update(['value' => $valeur]);
        Cache::forget('setting_' . MobileProfileResolver::REGLAGE_ACTIF);
        MobileProfileResolver::oublier();
    }

    /**
     * Une URL telle que Js::from() la sérialise dans un attribut x-data :
     * la page rendue contient JSON.parse('…') où chaque barre oblique est
     * précédée de trois barres inverses.
     */
    private function urlJs(string $url): string
    {
        return str_replace('/', '\\\\\/', $url);
    }

    private function inscription(): ESBTPInscription
    {
        return ESBTPInscription::factory()->create(['created_by' => $this->comptable->id]);
    }

    private function relance(ESBTPInscription $inscription, array $attributs = []): ESBTPRelance
    {
        return ESBTPRelance::create(array_merge([
            'etudiant_id' => $inscription->etudiant_id,
            'inscription_id' => $inscription->id,
            'type' => 'sms',
            'niveau' => 1,
            'template_utilise' => 'relance_niveau_1',
            'date_envoi' => now()->subHour(),
            'statut' => ESBTPRelance::STATUT_ENVOYEE,
        ], $attributs));
    }

    /* ------------------------------------------------------------------ liste */

    public function test_la_liste_mobile_est_rendue_a_cote_du_dom_de_bureau(): void
    {
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.index'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            ->assertSee('m-only-mobile m-screen rlm-screen', false)
            // Le DOM de bureau reste là, simplement caché sous 992px.
            ->assertSee('dashboard-acasi m-only-desktop', false)
            ->assertSee('rl-hero', false)
            // Repères du mois, jamais un taux d'ouverture inventé.
            ->assertSee('Envoyées ce mois')
            ->assertSee('Envois confirmés')
            ->assertSee('Planifiées')
            ->assertSee('data-m-sheet="rlm-groupe"', false)
            ->assertSee("if (typeof window.rlmRelances !== 'function')", false)
            // Sans comptabilite.relances.send : ni « + » ni feuille de planification.
            ->assertDontSee('aria-label="Planifier des relances"', false)
            ->assertDontSee('data-m-sheet="rlm-planifier"', false)
            // Sans comptabilite.config.manage : pas de lien vers la configuration dans l'app bar.
            ->assertDontSee('aria-label="Configuration des relances"', false);
    }

    public function test_le_bouton_planifier_suit_la_permission_d_envoi(): void
    {
        $this->comptable->givePermissionTo(['comptabilite.relances.send', 'comptabilite.config.manage']);

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.index'));

        $reponse->assertOk()
            ->assertSee('aria-label="Planifier des relances"', false)
            ->assertSee('data-m-sheet="rlm-planifier"', false)
            ->assertSee($this->urlJs(route('esbtp.comptabilite.relances.planifier')), false)
            ->assertSee('aria-label="Configuration des relances"', false);
    }

    public function test_les_relances_creees_ensemble_forment_une_campagne(): void
    {
        $quand = now()->subDay()->setTime(9, 0);
        foreach (range(1, 3) as $i) {
            $this->relance($this->inscription(), ['date_envoi' => $quand]);
        }
        $seule = $this->relance($this->inscription(), [
            'type' => 'email',
            'date_envoi' => now()->subDays(2)->setTime(8, 0),
            'statut' => ESBTPRelance::STATUT_ECHEC,
        ]);

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.index'));

        $reponse->assertOk()
            ->assertSee('1er rappel · 3 destinataires')
            ->assertSee('1er rappel · ' . $seule->etudiant->nom_complet)
            ->assertSee('À renvoyer')
            ->assertSee(route('esbtp.comptabilite.relances.show', $seule->id), false);
    }

    public function test_reglage_coupe_seul_le_dom_de_bureau_est_rendu(): void
    {
        $this->reglerShell('0');

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.index'));

        $reponse->assertOk()
            ->assertDontSee('has-m-shell', false)
            ->assertDontSee('rlm-screen', false)
            ->assertDontSee('m-only-desktop', false)
            ->assertSee('rl-hero', false);
    }

    /* ------------------------------------------------------------------ fiche */

    public function test_la_fiche_mobile_propose_le_renvoi_seulement_avec_la_permission(): void
    {
        $relance = $this->relance($this->inscription(), ['statut' => ESBTPRelance::STATUT_ECHEC]);

        $sans = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.show', $relance->id));
        $sans->assertOk()
            ->assertSee('m-only-mobile m-screen rsm-screen', false)
            ->assertSee('container-fluid m-only-desktop', false)
            ->assertSee('À renvoyer')
            ->assertSee(route('esbtp.comptabilite.relances.etudiant', $relance->inscription_id), false)
            ->assertDontSee('Renvoyer la relance');

        $this->comptable->givePermissionTo('comptabilite.relances.send');

        $avec = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.show', $relance->id));
        $avec->assertOk()
            ->assertSee('Renvoyer la relance')
            ->assertSee($this->urlJs(route('esbtp.comptabilite.relances.renvoyer', $relance->id)), false)
            ->assertSee("if (typeof window.rsmRelance !== 'function')", false);
    }

    public function test_une_relance_envoyee_ne_propose_pas_le_renvoi(): void
    {
        $this->comptable->givePermissionTo('comptabilite.relances.send');
        $relance = $this->relance($this->inscription());

        $this->actingAs($this->comptable)
            ->get(route('esbtp.comptabilite.relances.show', $relance->id))
            ->assertOk()
            ->assertDontSee('Renvoyer la relance');
    }

    public function test_le_renvoi_repond_en_json_et_met_le_job_en_file(): void
    {
        Queue::fake();
        $this->comptable->givePermissionTo('comptabilite.relances.send');
        $relance = $this->relance($this->inscription(), ['statut' => ESBTPRelance::STATUT_ECHEC]);

        $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.relances.renvoyer', $relance->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(EnvoyerRelanceJob::class);
    }

    public function test_le_renvoi_est_refuse_sans_la_permission(): void
    {
        $relance = $this->relance($this->inscription(), ['statut' => ESBTPRelance::STATUT_ECHEC]);

        $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.relances.renvoyer', $relance->id))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------- dossier */

    public function test_le_dossier_mobile_montre_les_relances_enregistrees_et_les_actions_permises(): void
    {
        $inscription = $this->inscription();
        $this->relance($inscription, ['canal' => 'whatsapp_deeplink', 'type' => 'recouvrement', 'template_utilise' => 'recouvrement_default']);

        $sans = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.etudiant', $inscription));
        $sans->assertOk()
            ->assertSee('m-only-mobile m-screen rem-screen', false)
            ->assertSee('dashboard-acasi m-only-desktop', false)
            ->assertSee('Reste dû')
            ->assertSee('Recouvrement · WhatsApp')
            ->assertSee("if (typeof window.remEtudiant !== 'function')", false)
            ->assertDontSee('data-m-sheet="rem-relancer"', false)
            ->assertDontSee(route('esbtp.paiements.create', ['inscription_id' => $inscription->id, 'etudiant_id' => $inscription->etudiant_id]), false);

        $this->comptable->givePermissionTo(['comptabilite.relances.send', 'paiements.create']);

        $avec = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.relances.etudiant', $inscription));
        $avec->assertOk()
            ->assertSee('data-m-sheet="rem-relancer"', false)
            ->assertSee($this->urlJs(route('esbtp.comptabilite.recouvrement.mark-done')), false)
            ->assertSee($this->urlJs(route('esbtp.comptabilite.recouvrement.log-intent')), false);
    }
}
