<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class AdministrateurInstancePermissionsTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PermissionRegistry();
    }

    public function test_le_role_existe_est_visible_et_n_est_pas_un_joker(): void
    {
        $roles = $this->registry->roles()->keys()->all();
        $this->assertContains('administrateurInstance', $roles);

        $visible = $this->registry->rolesVisibleInUi()->keys()->all();
        $this->assertContains('administrateurInstance', $visible);
        $this->assertNotContains('serviceTechnique', $visible);

        $defaults = $this->registry->defaultPermissionsFor('administrateurInstance');
        $this->assertNotContains('*', $defaults);
        $this->assertNotSame($this->registry->all()->count(), count($defaults));
    }

    public function test_il_a_reglages_comptes_personnel_audit_sans_paywall_ni_caisse(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('administrateurInstance');

        $this->assertContains('system.manage', $defaults);
        $this->assertContains('users.manage', $defaults);
        $this->assertContains('personnel.manage', $defaults);
        $this->assertContains('security.audit.view', $defaults);

        $this->assertNotContains('paywall.manage', $defaults);
        $this->assertNotContains('paiements.validate', $defaults);
        $this->assertNotContains('lmd.jury.publish', $defaults);
        $this->assertNotContains('sod.bypass', $defaults);
    }

    public function test_il_gere_la_scolarite_pas_adc(): void
    {
        $cibles = $this->registry->manageableRoles('administrateurInstance');
        $this->assertContains('secretaire', $cibles);
        $this->assertNotContains('serviceTechnique', $cibles);
        $this->assertNotContains('superAdmin', $cibles);
    }

    public function test_le_joker_superadmin_est_un_drapeau_d_instance(): void
    {
        $this->assertTrue(config('permissions.superadmin_gate_before'));
        config(['permissions.superadmin_gate_before' => false]);
        $this->assertFalse(config('permissions.superadmin_gate_before'));
    }
}
