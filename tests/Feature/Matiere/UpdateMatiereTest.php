<?php

namespace Tests\Feature\Matiere;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateMatiereTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin.access', 'matieres.edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        InstallationHelper::flushCachedStatus();
    }

    public function test_updating_a_subject_name_no_longer_500s(): void
    {
        $this->actingAsSubjectManager();
        $matiere = ESBTPMatiere::factory()->create([
            'name' => 'Informatiqe',
            'code' => 'INF101',
        ]);

        // Correction de l'orthographe : « Informatiqe » -> « Informatique ».
        $response = $this->put(route('esbtp.matieres.update', $matiere), [
            'name' => 'Informatique',
            'code' => 'INF101',
            'coefficient' => 2,
            'type_formation' => 'technologique_professionnelle',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('esbtp.matieres.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('esbtp_matieres', [
            'id' => $matiere->id,
            'name' => 'Informatique',
        ]);
    }

    private function actingAsSubjectManager(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('superAdmin');
        $user->givePermissionTo(['admin.access', 'matieres.edit']);
        $this->actingAs($user);

        return $user;
    }
}
