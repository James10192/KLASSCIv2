<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\UserManagementService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class UserManagementServiceTest extends TestCase
{
    private UserManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserManagementService(new PermissionRegistry());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Crée un mock de User avec les rôles donnés.
     */
    private function userWithRoles(array $roleNames, int $id = 1): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;

        $rolesCollection = new Collection(array_map(function ($name) {
            $role = new \stdClass();
            $role->name = $name;
            return $role;
        }, $roleNames));

        $user->shouldReceive('getAttribute')->with('roles')->andReturn($rolesCollection);
        $user->roles = $rolesCollection;

        $user->shouldReceive('hasAnyRole')->andReturnUsing(function ($needles) use ($roleNames) {
            return ! empty(array_intersect((array) $needles, $roleNames));
        });

        return $user;
    }

    public function test_secretaire_can_manage_enseignant(): void
    {
        $secretaire = $this->userWithRoles(['secretaire'], 1);
        $enseignant = $this->userWithRoles(['enseignant'], 2);

        $this->assertTrue($this->service->canManage($secretaire, $enseignant));
    }

    public function test_secretaire_cannot_manage_super_admin(): void
    {
        $secretaire = $this->userWithRoles(['secretaire'], 1);
        $superAdmin = $this->userWithRoles(['superAdmin'], 2);

        $this->assertFalse($this->service->canManage($secretaire, $superAdmin));
    }

    public function test_secretaire_cannot_manage_comptable(): void
    {
        $secretaire = $this->userWithRoles(['secretaire'], 1);
        $comptable = $this->userWithRoles(['comptable'], 2);

        $this->assertFalse($this->service->canManage($secretaire, $comptable));
    }

    public function test_user_cannot_manage_themselves(): void
    {
        $secretaire = $this->userWithRoles(['secretaire'], 5);
        $sameUser = $this->userWithRoles(['enseignant'], 5);

        $this->assertFalse($this->service->canManage($secretaire, $sameUser));
    }

    public function test_etudiant_cannot_manage_anyone(): void
    {
        $etudiant = $this->userWithRoles(['etudiant'], 1);
        $autre = $this->userWithRoles(['enseignant'], 2);

        $this->assertFalse($this->service->canManage($etudiant, $autre));
    }

    public function test_caissier_can_only_manage_etudiant(): void
    {
        $caissier = $this->userWithRoles(['caissier'], 1);
        $etudiant = $this->userWithRoles(['etudiant'], 2);
        $enseignant = $this->userWithRoles(['enseignant'], 3);

        $this->assertTrue($this->service->canManage($caissier, $etudiant));
        $this->assertFalse($this->service->canManage($caissier, $enseignant));
    }

    public function test_coordinateur_manages_enseignant_and_etudiant(): void
    {
        $coordinateur = $this->userWithRoles(['coordinateur'], 1);
        $enseignant = $this->userWithRoles(['enseignant'], 2);
        $etudiant = $this->userWithRoles(['etudiant'], 3);

        $this->assertTrue($this->service->canManage($coordinateur, $enseignant));
        $this->assertTrue($this->service->canManage($coordinateur, $etudiant));
    }

    public function test_can_assign_role_respects_matrix(): void
    {
        $secretaire = $this->userWithRoles(['secretaire'], 1);

        $this->assertTrue($this->service->canAssignRole($secretaire, 'enseignant'));
        $this->assertTrue($this->service->canAssignRole($secretaire, 'etudiant'));
        $this->assertFalse($this->service->canAssignRole($secretaire, 'superAdmin'));
        $this->assertFalse($this->service->canAssignRole($secretaire, 'comptable'));
    }

    public function test_manageable_roles_for_secretaire(): void
    {
        $secretaire = $this->userWithRoles(['secretaire'], 1);
        $manageable = $this->service->manageableRolesFor($secretaire);

        $this->assertContains('enseignant', $manageable);
        $this->assertContains('etudiant', $manageable);
        $this->assertContains('caissier', $manageable);
        $this->assertNotContains('superAdmin', $manageable);
    }

    public function test_user_with_multiple_roles_gets_union_of_manageable(): void
    {
        $multiRole = $this->userWithRoles(['secretaire', 'caissier'], 1);
        $manageable = $this->service->manageableRolesFor($multiRole);

        // Doit contenir l'union : enseignant, etudiant, caissier (de secretaire)
        // + etudiant (de caissier) → unique
        $this->assertContains('enseignant', $manageable);
        $this->assertContains('etudiant', $manageable);
        $this->assertContains('caissier', $manageable);
    }
}
