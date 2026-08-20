<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les bascules scolarité portent des clés pointées (scolarite.split_roles,
 * caisse.pre_inscription.enabled...). PHP remplace le point par un underscore
 * dans $_POST et Laravel voit dans le point un accès imbriqué : lues avec
 * $request->boolean(), elles renvoyaient toujours false, si bien que chaque
 * enregistrement de la page Paramètres les remettait à 0 en silence.
 */
class DottedCheckboxSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const DOTTED_KEYS = [
        'scolarite.split_roles',
        'documents.print_requires_approval',
        'caisse.pre_inscription.enabled',
        'inscriptions.split_role',
    ];

    private function superAdmin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function seedDottedSettings(string $value): void
    {
        foreach (self::DOTTED_KEYS as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'boolean', 'group' => 'scolarite', 'is_required' => false],
            );
        }
    }

    public function test_une_bascule_pointee_cochee_est_bien_conservee(): void
    {
        $this->seedDottedSettings('0');

        // PHP livre la clé avec des underscores : c'est la forme réellement reçue.
        $payload = ['settings_save_display' => '1'];
        foreach (self::DOTTED_KEYS as $key) {
            $payload[str_replace('.', '_', $key)] = '1';
        }

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $payload)
            ->assertSessionHasNoErrors();

        foreach (self::DOTTED_KEYS as $key) {
            $this->assertSame(
                '1',
                Setting::where('key', $key)->value('value'),
                "Le réglage {$key} aurait dû rester activé après enregistrement."
            );
        }
    }

    public function test_une_bascule_pointee_decochee_est_bien_desactivee(): void
    {
        $this->seedDottedSettings('1');

        // Aucune case transmise : le formulaire signale l'absence via settings_save_display.
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), ['settings_save_display' => '1'])
            ->assertSessionHasNoErrors();

        foreach (self::DOTTED_KEYS as $key) {
            $this->assertSame(
                '0',
                Setting::where('key', $key)->value('value'),
                "Le réglage {$key} aurait dû être désactivé."
            );
        }
    }
}
