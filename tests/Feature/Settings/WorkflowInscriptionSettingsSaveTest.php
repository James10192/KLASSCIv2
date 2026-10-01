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
}
