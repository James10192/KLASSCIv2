<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Services\LMD\LmdAcademicRuleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La case « Appliquer la pondération à la moyenne des ECUE » (onglet LMD) :
 * cochée, elle doit s'enregistrer à 1 ; décochée, à 0. Elle postait
 * `setting_lmd_ponderation_cc_examen`, que le contrôleur ne lit pas pour une
 * case : « Paramètres mis à jour » s'affichait et la case restait décochée.
 */
class PonderationCcExamenEnregistrementTest extends TestCase
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

    /** La case poste son nom nu : un préfixe `setting_` n'est lu que pour le parcours d'inscription. */
    public function test_la_case_de_la_page_poste_le_nom_que_lit_le_controleur(): void
    {
        $vue = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));

        $this->assertStringContainsString('name="'.LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE.'"', $vue);
    }

    public function test_cocher_puis_decocher_la_case_s_enregistre(): void
    {
        $cle = LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE;
        Setting::updateOrCreate(['key' => $cle], ['value' => '0', 'type' => 'boolean', 'group' => 'lmd', 'is_required' => false]);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('esbtp.settings.update'), [
            'settings_save_display' => '1',
            $cle => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('1', Setting::where('key', $cle)->value('value'));

        $this->actingAs($admin)->put(route('esbtp.settings.update'), ['settings_save_display' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::where('key', $cle)->value('value'));
    }
}
