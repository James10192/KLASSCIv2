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
 * Les cases de l'onglet LMD des paramètres : cochées, elles s'enregistrent à 1 ;
 * décochées, à 0.
 *
 * Elles postaient `setting_<clé>`, que le contrôleur ne lit pas pour une case.
 * La pondération ne s'enregistrait donc jamais : « Paramètres mis à jour »
 * s'affichait, et la case revenait décochée. Les deux compensations, elles,
 * passaient par la boucle générique, qui sait écrire 1 mais pas lire une case
 * absente : une fois cochées, elles ne repassaient jamais à 0.
 */
class PonderationCcExamenEnregistrementTest extends TestCase
{
    use RefreshDatabase;

    public static function cases(): array
    {
        return [
            'pondération' => [LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE, '0'],
            'compensation inter-UE' => [LmdAcademicRuleProfile::REGLAGE_COMPENSATION_INTER_UE, '1'],
            'compensation intra-UE' => [LmdAcademicRuleProfile::REGLAGE_COMPENSATION_INTRA_UE, '1'],
        ];
    }

    private function superAdmin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * La case poste son nom nu : un préfixe `setting_` n'est lu que pour le parcours d'inscription.
     *
     * @dataProvider cases
     */
    public function test_la_case_de_la_page_poste_le_nom_que_lit_le_controleur(string $cle): void
    {
        $vue = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));

        $this->assertStringContainsString('name="'.$cle.'"', $vue);
        $this->assertStringNotContainsString('name="setting_'.$cle.'"', $vue);
    }

    /** @dataProvider cases */
    public function test_cocher_puis_decocher_la_case_s_enregistre(string $cle, string $initial): void
    {
        Setting::updateOrCreate(['key' => $cle], ['value' => $initial, 'type' => 'boolean', 'group' => 'lmd', 'is_required' => false]);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('esbtp.settings.update'), [
            'settings_save_display' => '1',
            $cle => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('1', Setting::where('key', $cle)->value('value'));

        // Case décochée : le navigateur ne l'envoie pas.
        $this->actingAs($admin)->put(route('esbtp.settings.update'), ['settings_save_display' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::where('key', $cle)->value('value'));
    }
}
