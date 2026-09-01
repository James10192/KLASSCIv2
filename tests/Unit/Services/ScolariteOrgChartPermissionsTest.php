<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class ScolariteOrgChartPermissionsTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PermissionRegistry();
    }

    public function test_new_scolarite_roles_are_canonical_and_visible(): void
    {
        foreach (['responsableScolarite', 'serviceScolarite', 'agentInscription'] as $role) {
            $this->assertTrue($this->registry->roles()->has($role), $role.' must exist');
            $this->assertContains($role, $this->registry->rolesVisibleInUi()->keys()->all());
        }

        $responsable = $this->registry->roleMeta('responsableScolarite');
        $this->assertSame('Responsable scolarite', $responsable['label']);
        $this->assertSame('Administration', $responsable['group']);

        $service = $this->registry->roleMeta('serviceScolarite');
        $this->assertSame('Service scolarite', $service['label']);
        $this->assertSame('Administration', $service['group']);

        $agent = $this->registry->roleMeta('agentInscription');
        $this->assertSame("Agent d'inscription", $agent['label']);
        $this->assertSame('Administration', $agent['group']);
    }

    public function test_new_permissions_are_canonical(): void
    {
        $expected = [
            'identity.registrar',
            'identity.registrar_clerk',
            'documents.view',
            'documents.approve',
            'documents.print',
            'notes.window.manage',
            'paiements.create.mobile_money',
            'finance.unpaid_count.view',
            'reports.academic.rentree',
            'reports.academic.trimestre',
            'reports.academic.annuel',
            'responsables_scolarite.view',
            'responsables_scolarite.create',
            'responsables_scolarite.edit',
            'responsables_scolarite.delete',
            'services_scolarite.view',
            'services_scolarite.create',
            'services_scolarite.edit',
            'services_scolarite.delete',
            'identity.enrollment_officer',
            'inscriptions.in_kind.mark',
            'agents_inscription.view',
            'agents_inscription.create',
            'agents_inscription.edit',
            'agents_inscription.delete',
        ];

        foreach ($expected as $permission) {
            $this->assertTrue($this->registry->isCanonical($permission), $permission.' must be canonical');
            $this->assertNotEmpty($this->registry->permissionMeta($permission)['label']);
        }
    }

    public function test_responsable_has_scolarite_without_finance_or_system(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('responsableScolarite');

        $this->assertContains('identity.registrar', $defaults);
        $this->assertContains('documents.approve', $defaults);
        $this->assertContains('notes.window.manage', $defaults);
        $this->assertContains('students.view', $defaults);
        $this->assertContains('inscriptions.validate', $defaults);
        $this->assertContains('notes.create', $defaults);
        $this->assertContains('bulletins.generate', $defaults);
        $this->assertContains('services_scolarite.view', $defaults);

        $this->assertNotContains('admin.access', $defaults);
        $this->assertNotContains('identity.school_manager', $defaults);
        $this->assertNotContains('identity.registrar_clerk', $defaults);
        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('paiements.create', $defaults);
        $this->assertNotContains('frais.view', $defaults);
        $this->assertNotContains('comptabilite.access', $defaults);
        $this->assertNotContains('module.comptabilite.access', $defaults);
        $this->assertNotContains('module.caisse.access', $defaults);
        $this->assertNotContains('users.manage', $defaults);
        $this->assertNotContains('system.manage', $defaults);
        $this->assertNotContains('*', $defaults);
    }

    public function test_service_is_read_plus_notes_and_print(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('serviceScolarite');

        $this->assertContains('identity.registrar_clerk', $defaults);
        $this->assertContains('documents.print', $defaults);
        $this->assertContains('students.view', $defaults);
        $this->assertContains('inscriptions.view', $defaults);
        $this->assertContains('notes.view', $defaults);
        $this->assertContains('notes.create', $defaults);
        $this->assertContains('notes.edit', $defaults);
        $this->assertContains('bulletins.view', $defaults);
        $this->assertContains('module.lmd.access', $defaults);

        $this->assertNotContains('inscriptions.create', $defaults);
        $this->assertNotContains('inscriptions.validate', $defaults);
        $this->assertNotContains('documents.approve', $defaults);
        $this->assertNotContains('notes.window.manage', $defaults);
        $this->assertNotContains('personnel.view', $defaults);
        $this->assertNotContains('admin.access', $defaults);
        $this->assertNotContains('identity.school_manager', $defaults);
        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('frais.view', $defaults);
        $this->assertNotContains('*', $defaults);
    }

    public function test_directeur_etudes_is_pilotage_without_write_or_amounts(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('directeurEtudes');

        $this->assertContains('identity.direct_studies', $defaults);
        $this->assertContains('notes.view', $defaults);
        $this->assertContains('inscriptions.view', $defaults);
        $this->assertContains('bulletins.view', $defaults);
        $this->assertContains('planning.edit', $defaults);
        $this->assertContains('timetables.create', $defaults);
        $this->assertContains('finance.unpaid_count.view', $defaults);
        $this->assertContains('reports.academic.rentree', $defaults);
        $this->assertContains('reports.academic.trimestre', $defaults);
        $this->assertContains('reports.academic.annuel', $defaults);
        $this->assertContains('lmd.jury.view', $defaults);

        $this->assertNotContains('inscriptions.create', $defaults);
        $this->assertNotContains('inscriptions.validate', $defaults);
        $this->assertNotContains('notes.create', $defaults);
        $this->assertNotContains('bulletins.generate', $defaults);
        $this->assertNotContains('bulletins.publish.bulk', $defaults);
        $this->assertNotContains('lmd.jury.preside', $defaults);
        $this->assertNotContains('lmd.jury.deliberate', $defaults);
        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('frais.view', $defaults);
        $this->assertNotContains('*', $defaults);
    }

    public function test_comptable_is_mobile_money_only_for_new_payments(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('comptable');

        $this->assertContains('paiements.create.mobile_money', $defaults);
        $this->assertContains('paiements.view', $defaults);
        $this->assertContains('comptabilite.journal.view', $defaults);
        $this->assertNotContains('paiements.create', $defaults);
    }

    public function test_caissier_keeps_full_payment_create(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('caissier');

        $this->assertContains('paiements.create', $defaults);
        $this->assertContains('paiements.view_own', $defaults);
        $this->assertNotContains('paiements.create.mobile_money', $defaults);
    }


    public function test_agent_inscription_has_enrollment_without_finance_or_system(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('agentInscription');

        $this->assertContains('identity.enrollment_officer', $defaults);
        $this->assertContains('students.view', $defaults);
        $this->assertContains('students.create', $defaults);
        $this->assertContains('students.edit', $defaults);
        $this->assertContains('inscriptions.view', $defaults);
        $this->assertContains('inscriptions.create', $defaults);
        $this->assertContains('inscriptions.edit', $defaults);
        $this->assertContains('inscriptions.validate', $defaults);
        $this->assertContains('inscriptions.in_kind.mark', $defaults);
        $this->assertContains('classes.view', $defaults);
        $this->assertContains('filieres.view', $defaults);
        $this->assertContains('niveaux.view', $defaults);

        $this->assertNotContains('annees.view', $defaults);
        $this->assertNotContains('inscriptions.cancel', $defaults);
        $this->assertNotContains('inscriptions.reject', $defaults);
        $this->assertNotContains('inscriptions.delete', $defaults);
        $this->assertNotContains('admin.access', $defaults);
        $this->assertNotContains('identity.school_manager', $defaults);
        $this->assertNotContains('identity.registrar', $defaults);
        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('frais.view', $defaults);
        $this->assertNotContains('notes.create', $defaults);
        $this->assertNotContains('personnel.view', $defaults);
        $this->assertNotContains('system.manage', $defaults);
        $this->assertNotContains('*', $defaults);
    }

    public function test_role_management_covers_the_new_scolarite_roles(): void
    {
        $this->assertContains('responsableScolarite', $this->registry->manageableRoles('superAdmin'));
        $this->assertContains('serviceScolarite', $this->registry->manageableRoles('superAdmin'));
        $this->assertContains('agentInscription', $this->registry->manageableRoles('superAdmin'));
        $this->assertContains('responsableScolarite', $this->registry->manageableRoles('serviceTechnique'));
        $this->assertContains('agentInscription', $this->registry->manageableRoles('serviceTechnique'));
        $this->assertSame(['serviceScolarite', 'enseignant', 'etudiant'], $this->registry->manageableRoles('responsableScolarite'));
        $this->assertSame([], $this->registry->manageableRoles('serviceScolarite'));
        $this->assertSame([], $this->registry->manageableRoles('agentInscription'));
    }
}