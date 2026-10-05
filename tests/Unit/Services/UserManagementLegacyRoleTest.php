<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\UserManagementService;
use Tests\TestCase;

class UserManagementLegacyRoleTest extends TestCase
{
    public function test_directeur_etudes_can_manage_teacher_legacy_role(): void
    {
        $actor = new User();
        $actor->id = 1001;
        $actor->setRelation('roles', collect([(object) ['name' => 'directeurEtudes']]));

        $target = new User();
        $target->id = 1002;
        $target->setRelation('roles', collect([(object) ['name' => 'teacher']]));

        $service = new UserManagementService(new PermissionRegistry());

        $this->assertTrue($service->canManage($actor, $target));
    }

    public function test_legacy_admin_actor_uses_super_admin_management_matrix(): void
    {
        $actor = new User();
        $actor->id = 2001;
        $actor->setRelation('roles', collect([(object) ['name' => 'admin']]));

        $target = new User();
        $target->id = 2002;
        $target->setRelation('roles', collect([(object) ['name' => 'teacher']]));

        $service = new UserManagementService(new PermissionRegistry());

        $this->assertTrue($service->canManage($actor, $target));
    }
}
