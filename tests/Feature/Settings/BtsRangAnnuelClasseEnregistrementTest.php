<?php

namespace Tests\Feature\Settings;

use App\Domain\BtsTroncCommun\BtsAnnualRankingPolicy as Politique;
use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BtsRangAnnuelClasseEnregistrementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_classe_actuelle_est_le_defaut_et_peut_etre_remplacee_pour_cette_ecole(): void
    {
        $this->assertSame(Politique::SPECIALISATION, app(Politique::class)->mode());

        $this->actingAs($this->admin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'setting_'.Politique::KEY => Politique::ORIGINE,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertSame(Politique::ORIGINE, Setting::where('key', Politique::KEY)->value('value'));
        $this->assertSame(Politique::ORIGINE, app(Politique::class)->mode());
    }

    public function test_enregistrer_un_autre_reglage_ne_remet_pas_la_classe_en_cours_a_origine(): void
    {
        SettingsHelper::setOrCreate(Politique::KEY, Politique::ORIGINE, 'tronc_commun');

        $this->actingAs($this->admin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
            ])->assertSessionHasNoErrors();

        $this->assertSame(Politique::ORIGINE, Setting::where('key', Politique::KEY)->value('value'));
    }

    public function test_reglage_inconnu_est_refuse_sans_modifier_la_valeur_precedente(): void
    {
        SettingsHelper::setOrCreate(Politique::KEY, Politique::SPECIALISATION, 'tronc_commun');

        $this->actingAs($this->admin())
            ->put(route('esbtp.settings.update'), [
                'setting_'.Politique::KEY => 'toutes_les_classes',
            ])
            ->assertSessionHas('error');

        $this->assertSame(Politique::SPECIALISATION, Setting::where('key', Politique::KEY)->value('value'));
    }
}
