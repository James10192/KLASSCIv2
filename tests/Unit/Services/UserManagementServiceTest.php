<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\UserManagementService;
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

    private function user(int $id, array $rights, array $roles = [], array $targetPermissions = []): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('can')->andReturnUsing(fn (string $name) => in_array($name, $rights, true));
        $user->shouldReceive('getRoleNames')->andReturn(collect($roles));
        $permissions = collect(array_map(fn (string $name) => (object) ['name' => $name], $targetPermissions));
        $user->shouldReceive('getAllPermissions')->andReturn($permissions);

        return $user;
    }

    public function test_le_nom_de_role_seul_ne_donne_aucun_droit_de_gestion(): void
    {
        $this->assertFalse($this->service->canManage(
            $this->user(1, [], ['secretaire']),
            $this->user(2, [], ['enseignant'])
        ));
    }

    public function test_permission_donne_acces_sans_role_quand_les_droits_de_cible_sont_couverts(): void
    {
        $this->assertTrue($this->service->canManage(
            $this->user(1, ['personnel.manage', 'notes.view']),
            $this->user(2, [], ['enseignant'], ['notes.view'])
        ));
    }

    public function test_on_ne_peut_pas_prendre_le_compte_de_quelqu_un_de_plus_privilegie(): void
    {
        $this->assertFalse($this->service->canManage(
            $this->user(1, ['personnel.manage']),
            $this->user(2, [], ['comptable'], ['system.manage'])
        ));
    }

    public function test_manage_all_est_une_permission_distincte_et_explicitement_attribuee(): void
    {
        $this->assertTrue($this->service->canManage(
            $this->user(1, ['users.manage', 'personnel.roles.manage_all']),
            $this->user(2, [], ['administrateurInstance'], ['system.manage'])
        ));
    }

    public function test_superadmin_et_technique_resten_proteges_meme_avec_tous_les_droits(): void
    {
        $actor = $this->user(1, ['users.manage', 'personnel.roles.manage_all']);
        $this->assertFalse($this->service->canManage($actor, $this->user(2, [], ['superAdmin'])));
        $this->assertFalse($this->service->canManage($actor, $this->user(3, [], ['serviceTechnique', 'enseignant'])));
    }

    public function test_compte_sans_role_gerable_par_permission(): void
    {
        $this->assertTrue($this->service->canManage(
            $this->user(1, ['personnel.manage']), $this->user(2, [])
        ));
    }

    public function test_auto_gestion_interdite(): void
    {
        $this->assertFalse($this->service->canManage(
            $this->user(5, ['personnel.manage', 'personnel.roles.manage_all']), $this->user(5, [])
        ));
    }

    public function test_superadmin_ne_peut_pas_etre_attribue_par_la_policy_generique(): void
    {
        $actor = $this->user(1, ['personnel.roles.assign', 'superadmin.accounts.create']);
        $this->assertFalse($this->service->canAssignRole($actor, 'superAdmin'));
        $this->assertFalse($this->service->canAssignRole($actor, 'serviceTechnique'));
    }

    public function test_attribution_de_role_exige_permission_specifique(): void
    {
        $actor = $this->user(1, ['users.manage']);
        $this->assertFalse($this->service->canAssignRole($actor, 'enseignant'));
        $this->assertSame([], $this->service->manageableRolesFor($actor));
    }
}
