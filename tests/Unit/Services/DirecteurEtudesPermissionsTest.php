<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class DirecteurEtudesPermissionsTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PermissionRegistry();
    }

    public function test_role_is_canonical_and_visible(): void
    {
        $this->assertTrue($this->registry->roles()->has('directeurEtudes'));
        $this->assertContains('directeurEtudes', $this->registry->rolesVisibleInUi()->keys()->all());

        $meta = $this->registry->roleMeta('directeurEtudes');
        $this->assertSame('Directeur des études', $meta['label']);
        $this->assertSame('Pédagogie', $meta['group']);
    }

    public function test_defaults_cover_academic_identity_without_finance_or_system(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('directeurEtudes');

        $this->assertContains('identity.direct_studies', $defaults);
        $this->assertContains('identity.coordinate', $defaults);
        $this->assertContains('notes.view', $defaults);
        $this->assertContains('lmd.jury.preside', $defaults);
        $this->assertContains('academic_pilotage.view', $defaults);
        $this->assertContains('personnel.view', $defaults);
        $this->assertContains('coordinateurs.view', $defaults);

        $this->assertNotContains('admin.access', $defaults);
        $this->assertNotContains('identity.school_manager', $defaults);
        $this->assertNotContains('users.manage', $defaults);
        $this->assertNotContains('personnel.manage', $defaults);
        $this->assertNotContains('system.manage', $defaults);
        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('frais.view', $defaults);
        $this->assertNotContains('comptabilite.access', $defaults);
        $this->assertNotContains('module.comptabilite.access', $defaults);
        $this->assertNotContains('module.caisse.access', $defaults);
        $this->assertNotContains('secretaires.view', $defaults);
        $this->assertNotContains('comptables.view', $defaults);
        $this->assertNotContains('*', $defaults);
    }

    public function test_role_management_lets_superadmin_and_de_manage_the_expected_roles(): void
    {
        $this->assertContains('directeurEtudes', $this->registry->manageableRoles('superAdmin'));
        $this->assertContains('directeurEtudes', $this->registry->manageableRoles('serviceTechnique'));
        $this->assertSame(['coordinateur', 'enseignant', 'etudiant'], $this->registry->manageableRoles('directeurEtudes'));
    }
}
