<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La page Paramètres ne met à jour que les réglages déjà en base. Sur une
 * instance qui n'a jamais eu la ligne bulletin_show_student_phone, décocher
 * la case n'aurait donc rien enregistré, sans la moindre erreur.
 */
class BulletinTelephoneEtudiantEnregistrementTest extends TestCase
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

    public function test_decocher_la_case_sur_une_instance_sans_la_ligne_enregistre_zero(): void
    {
        $this->assertNull(Setting::where('key', 'bulletin_show_student_phone')->first());

        // Case décochée : le navigateur ne l'envoie pas, settings_save_display signale le formulaire complet.
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), ['settings_save_display' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertSame('0', Setting::where('key', 'bulletin_show_student_phone')->value('value'));
    }

    public function test_cocher_la_case_enregistre_un(): void
    {
        Setting::updateOrCreate(
            ['key' => 'bulletin_show_student_phone'],
            ['value' => '0', 'type' => 'string', 'group' => 'bulletin', 'is_required' => false],
        );

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'bulletin_show_student_phone' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('1', Setting::where('key', 'bulletin_show_student_phone')->value('value'));
    }
}
