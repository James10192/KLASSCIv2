<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Services\Admissions\InscriptionWorkflowSettings as W;
use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le bloc « Workflow d'inscription » de /esbtp/settings poste des champs
 * `setting_inscriptions.workflow.*`. PHP y remplace les points par des
 * underscores : le chemin générique cherchait alors un réglage
 * `inscriptions_workflow_enabled`, inexistant, et l'école cochait la case
 * sans que rien ne soit jamais enregistré.
 */
class WorkflowInscriptionSettingsSaveTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** Le formulaire tel que PHP le livre : points remplacés par des underscores. */
    private function formulaire(array $valeurs): array
    {
        $payload = ['settings_save_display' => '1'];
        foreach ($valeurs as $cle => $valeur) {
            $payload['setting_'.str_replace('.', '_', $cle)] = $valeur;
        }

        return $payload;
    }

    private function ouvrirRendezVous(bool $ouvert): void
    {
        Setting::updateOrCreate(
            ['key' => RendezVousReglages::ENABLED],
            ['value' => $ouvert ? '1' : '0', 'type' => 'boolean', 'group' => 'inscriptions', 'is_required' => false],
        );
    }

    public function test_le_parcours_configure_a_l_ecran_est_enregistre(): void
    {
        $this->ouvrirRendezVous(true);
        app(W::class)->ensureDefaults();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->formulaire([
                W::ENABLED => '1',
                W::MODE => W::MODE_CAISSE_AVANT_PIECES,
                W::ACCOUNT_ACTIVATION_STEP => W::ACTIVATION_AFTER_DOCUMENTS,
                W::CLASS_CHOICE_ACTOR => W::CLASS_ACTOR_STUDENT,
                W::REQUIRE_RDV => '1',
                W::CLASS_CHOICE_ONCE => '1',
                W::NOTIFY_EMAIL => '1',
                W::NOTIFY_WHATSAPP => '0',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('1', Setting::where('key', W::ENABLED)->value('value'));
        $this->assertSame(W::MODE_CAISSE_AVANT_PIECES, Setting::where('key', W::MODE)->value('value'));
        $this->assertSame(W::ACTIVATION_AFTER_DOCUMENTS, Setting::where('key', W::ACCOUNT_ACTIVATION_STEP)->value('value'));
        $this->assertSame(W::CLASS_ACTOR_STUDENT, Setting::where('key', W::CLASS_CHOICE_ACTOR)->value('value'));
        $this->assertSame('0', Setting::where('key', W::NOTIFY_WHATSAPP)->value('value'));
        $this->assertTrue(app(W::class)->usesManagedWorkflow());
    }

    public function test_les_reglages_existent_meme_si_personne_n_a_encore_ouvert_l_ecran(): void
    {
        $this->ouvrirRendezVous(true);
        $this->assertNull(Setting::where('key', W::ENABLED)->first());

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->formulaire([W::ENABLED => '1', W::MODE => W::MODE_PIECES_AVANT_CAISSE]))
            ->assertSessionHasNoErrors();

        $this->assertSame('1', Setting::where('key', W::ENABLED)->value('value'));
        $this->assertSame(W::MODE_PIECES_AVANT_CAISSE, Setting::where('key', W::MODE)->value('value'));
    }

    public function test_un_choix_inconnu_est_refuse_sans_rien_ecrire(): void
    {
        $this->ouvrirRendezVous(true);
        app(W::class)->ensureDefaults();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->formulaire([W::ENABLED => '1', W::MODE => 'n_importe_quoi']));

        $this->assertSame('0', Setting::where('key', W::ENABLED)->value('value'));
        $this->assertSame(W::MODE_LEGACY, Setting::where('key', W::MODE)->value('value'));
    }

    public function test_exiger_un_rendez_vous_avec_la_prise_de_rendez_vous_fermee_est_refuse(): void
    {
        $this->ouvrirRendezVous(false);
        app(W::class)->ensureDefaults();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->formulaire([
                W::ENABLED => '1',
                W::MODE => W::MODE_CAISSE_AVANT_PIECES,
                W::REQUIRE_RDV => '1',
            ]));

        $this->assertSame('0', Setting::where('key', W::ENABLED)->value('value'));
    }

    public function test_une_sauvegarde_d_une_autre_section_ne_touche_pas_au_parcours(): void
    {
        $this->ouvrirRendezVous(true);
        app(W::class)->ensureDefaults();
        Setting::where('key', W::ENABLED)->update(['value' => '1']);
        Setting::where('key', W::MODE)->update(['value' => W::MODE_CAISSE_AVANT_PIECES]);

        // Un formulaire qui ne porte pas le bloc (pas de settings_save_display).
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), ['setting_school_name' => 'École test'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1', Setting::where('key', W::ENABLED)->value('value'));
        $this->assertSame(W::MODE_CAISSE_AVANT_PIECES, Setting::where('key', W::MODE)->value('value'));
    }

    public function test_un_etat_deja_incoherent_ne_bloque_pas_l_enregistrement_du_reste(): void
    {
        // Parcours actif exigeant un rendez-vous, prise de rendez-vous fermée
        // ailleurs : l'école enregistre autre chose, la page doit l'accepter.
        $this->ouvrirRendezVous(false);
        app(W::class)->ensureDefaults();
        Setting::where('key', W::ENABLED)->update(['value' => '1']);
        Setting::where('key', W::MODE)->update(['value' => W::MODE_CAISSE_AVANT_PIECES]);
        Setting::updateOrCreate(['key' => 'school_name'], ['value' => 'Avant', 'type' => 'string', 'group' => 'general', 'is_required' => false]);

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->formulaire([
                W::ENABLED => '1',
                W::MODE => W::MODE_CAISSE_AVANT_PIECES,
                W::ACCOUNT_ACTIVATION_STEP => W::ACTIVATION_AFTER_PAYMENT,
                W::CLASS_CHOICE_ACTOR => W::CLASS_ACTOR_ADMIN,
                W::REQUIRE_RDV => '1',
                W::CLASS_CHOICE_ONCE => '1',
                W::NOTIFY_EMAIL => '1',
                W::NOTIFY_WHATSAPP => '1',
            ]) + ['setting_school_name' => 'Après'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertSame('Après', Setting::where('key', 'school_name')->value('value'));
    }

    public function test_un_choix_poste_en_liste_est_refuse_sans_erreur_serveur(): void
    {
        $this->ouvrirRendezVous(true);
        app(W::class)->ensureDefaults();

        $reponse = $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), ['settings_save_display' => '1', 'setting_inscriptions_workflow_mode' => ['x']]);

        $this->assertLessThan(500, $reponse->getStatusCode());
        $this->assertSame(W::MODE_LEGACY, Setting::where('key', W::MODE)->value('value'));
    }

    public function test_la_page_rendez_vous_refuse_de_fermer_la_prise_sous_un_parcours_qui_l_exige(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.configure'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $gestionnaire = User::factory()->create();
        $gestionnaire->givePermissionTo(['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.configure']);

        $this->ouvrirRendezVous(true);
        app(W::class)->ensureDefaults();
        Setting::where('key', W::ENABLED)->update(['value' => '1']);
        Setting::where('key', W::MODE)->update(['value' => W::MODE_CAISSE_AVANT_PIECES]);

        // Case décochée : absente de la requête.
        $this->actingAs($gestionnaire)
            ->postJson(route('esbtp.rendez-vous.reglages'), ['inscriptions_rdv_duree_minutes' => '30'])
            ->assertStatus(422);
        $this->assertSame('1', Setting::where('key', RendezVousReglages::ENABLED)->value('value'));

        // Sans parcours qui l'exige, la fermeture reste possible.
        Setting::where('key', W::REQUIRE_RDV)->first()->update(['value' => '0']);
        $this->actingAs($gestionnaire)
            ->postJson(route('esbtp.rendez-vous.reglages'), ['inscriptions_rdv_duree_minutes' => '30'])
            ->assertOk();
        $this->assertSame('0', Setting::where('key', RendezVousReglages::ENABLED)->value('value'));
    }
}
