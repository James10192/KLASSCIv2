<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class CommunicationRolePermissionsTest extends TestCase
{
    public function test_charge_communication_is_visible_without_finance_or_system(): void
    {
        $registry = new PermissionRegistry();
        $this->assertTrue($registry->roles()->has('chargeCommunication'));
        $this->assertSame('Chargé de communication', $registry->roleMeta('chargeCommunication')['label']);

        $defaults = $registry->defaultPermissionsFor('chargeCommunication');
        $this->assertContains('identity.communicate', $defaults);
        $this->assertContains('annonces.create', $defaults);
        $this->assertContains('messages.send', $defaults);
        $this->assertContains('mailpulse.send', $defaults);
        $this->assertContains('students.view', $defaults);
        $this->assertNotContains('system.manage', $defaults);
        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('lmd.planning.edit', $defaults);
        $this->assertNotContains('teachers.view', $defaults);
        $this->assertNotContains('*', $defaults);
    }
}
