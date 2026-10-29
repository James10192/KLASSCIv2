<?php

namespace Tests\Feature\Settings;

use App\Domain\Bulletins\EtatDesResultats as Etat;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les seuils qui colorent /esbtp/resultats se règlent dans les paramètres.
 * Un rouge au-dessus du vert est refusé à l'enregistrement, au lieu d'être
 * remplacé en silence par les valeurs par défaut à la lecture.
 */
class SeuilsDesResultatsEnregistrementTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_migration_seme_les_trois_seuils(): void
    {
        $this->assertSame('12', Setting::where('key', Etat::REGLAGE_MOYENNE_SATISFAISANTE)->value('value'));
        $this->assertSame('70', Setting::where('key', Etat::REGLAGE_REUSSITE_SATISFAISANTE)->value('value'));
        $this->assertSame('50', Setting::where('key', Etat::REGLAGE_REUSSITE_ALERTE)->value('value'));
    }

    public function test_des_seuils_coherents_sont_enregistres(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), [
                Etat::REGLAGE_MOYENNE_SATISFAISANTE => '13',
                Etat::REGLAGE_REUSSITE_SATISFAISANTE => '80',
                Etat::REGLAGE_REUSSITE_ALERTE => '40',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertSame('13', Setting::where('key', Etat::REGLAGE_MOYENNE_SATISFAISANTE)->value('value'));
        $this->assertSame('80', Setting::where('key', Etat::REGLAGE_REUSSITE_SATISFAISANTE)->value('value'));
        $this->assertSame('40', Setting::where('key', Etat::REGLAGE_REUSSITE_ALERTE)->value('value'));
    }

    public function test_un_rouge_au_dessus_du_vert_est_refuse(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), [
                Etat::REGLAGE_REUSSITE_SATISFAISANTE => '60',
                Etat::REGLAGE_REUSSITE_ALERTE => '80',
            ])
            ->assertSessionHas('error');

        $this->assertSame('70', Setting::where('key', Etat::REGLAGE_REUSSITE_SATISFAISANTE)->value('value'));
        $this->assertSame('50', Setting::where('key', Etat::REGLAGE_REUSSITE_ALERTE)->value('value'));
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
}
