<?php

namespace Tests\Feature\Settings;

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Models\User;
use App\Services\Planning\PlageHoraireJournee as Plage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlageHoraireCoursEnregistrementTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_ecole_enregistre_des_cours_du_soir(): void
    {
        $this->actingAs($this->admin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                str_replace('.', '_', Plage::CLE_DEBUT) => '8',
                str_replace('.', '_', Plage::CLE_FIN) => '22',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertSame('8', Setting::where('key', Plage::CLE_DEBUT)->value('value'));
        $this->assertSame('22', Setting::where('key', Plage::CLE_FIN)->value('value'));
        $this->assertSame([8, 22], [app(Plage::class)->debut(), app(Plage::class)->fin()]);
    }

    public function test_une_fin_avant_le_debut_est_refusee_sans_rien_ecrire(): void
    {
        SettingsHelper::setOrCreate(Plage::CLE_DEBUT, '7', 'academique');
        SettingsHelper::setOrCreate(Plage::CLE_FIN, '18', 'academique');

        $this->actingAs($this->admin())
            ->put(route('esbtp.settings.update'), [
                str_replace('.', '_', Plage::CLE_DEBUT) => '19',
                str_replace('.', '_', Plage::CLE_FIN) => '18',
            ])
            ->assertSessionHas('error');

        $this->assertSame('7', Setting::where('key', Plage::CLE_DEBUT)->value('value'));
        $this->assertSame('18', Setting::where('key', Plage::CLE_FIN)->value('value'));
    }

    public function test_enregistrer_un_autre_reglage_ne_touche_pas_la_plage(): void
    {
        SettingsHelper::setOrCreate(Plage::CLE_DEBUT, '8', 'academique');
        SettingsHelper::setOrCreate(Plage::CLE_FIN, '21', 'academique');

        $this->actingAs($this->admin())
            ->put(route('esbtp.settings.update'), ['settings_save_display' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertSame('8', Setting::where('key', Plage::CLE_DEBUT)->value('value'));
        $this->assertSame('21', Setting::where('key', Plage::CLE_FIN)->value('value'));
    }

    public function test_l_evaluation_propose_le_premier_creneau_de_l_ecole(): void
    {
        SettingsHelper::setOrCreate(Plage::CLE_DEBUT, '9', 'academique');
        SettingsHelper::setOrCreate(Plage::CLE_FIN, '20', 'academique');
        $this->withoutMiddleware([\App\Http\Middleware\PaywallMiddleware::class]);

        $this->actingAs($this->admin())
            ->get(route('esbtp.evaluations.create'))
            ->assertOk()
            ->assertSee('value="09:00"', false)
            ->assertSee('value="11:00"', false);
    }

    private function admin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->assignRole($role);

        return $user;
    }
}
