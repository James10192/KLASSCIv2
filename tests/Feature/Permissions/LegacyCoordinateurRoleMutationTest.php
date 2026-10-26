<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Services\Personnel\RoleAssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class LegacyCoordinateurRoleMutationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['personnel.manage', 'personnel.roles.assign', 'personnel.roles.remove'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Role::findOrCreate('coordinateur', 'web');
        Role::findOrCreate('enseignant', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_retrait_de_coordinateur_conserve_enseignant_et_compte_actif(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo(['personnel.manage', 'personnel.roles.remove']);
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['coordinateur', 'enseignant']);

        $result = app(RoleAssignmentService::class)->change(
            $actor, $target->id, 'remove', 'coordinateur',
            'Retrait motivé dans le test.'
        );

        $this->assertSame(['coordinateur', 'enseignant'], $result['before']);
        $this->assertSame(['enseignant'], $result['after']);
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_sans_droit_de_retrait_aucune_mutation_n_est_commise(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo('personnel.manage');
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['coordinateur', 'enseignant']);

        try {
            app(RoleAssignmentService::class)->change(
                $actor, $target->id, 'remove', 'coordinateur',
                'Tentative sans habilitation.'
            );
            $this->fail('La mutation doit être refusée.');
        } catch (AuthorizationException $e) {
            $this->assertTrue($target->fresh()->hasRole('coordinateur'));
            $this->assertTrue($target->fresh()->hasRole('enseignant'));
        }
    }
}
