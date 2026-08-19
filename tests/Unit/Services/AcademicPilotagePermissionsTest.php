<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class AcademicPilotagePermissionsTest extends TestCase
{
    private const PERMISSIONS = [
        'module.academic_pilotage.access',
        'academic_pilotage.view',
        'academic_pilotage.view_all',
        'academic_pilotage.configure',
        'academic_sheets.view',
        'academic_sheets.view_own',
        'academic_sheets.create',
        'academic_sheets.submit',
        'academic_sheets.receive',
        'academic_sheets.enter',
        'academic_sheets.control',
        'academic_sheets.validate',
        'academic_sheets.assign',
        'academic_alerts.view',
        'academic_alerts.view_own',
        'academic_alerts.acknowledge',
        'academic_alerts.resolve',
        'academic_health.view',
        'academic_health.view_own',
        'academic_health.recalculate',
        'bulletins.generate_incomplete',
    ];

    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new PermissionRegistry;
    }

    public function test_it_registers_canonical_permissions_with_french_metadata(): void
    {
        $expected = [
            'module.academic_pilotage.access' => ['Module : Pilotage académique', 'Modules', 'fa-chart-line'],
            'academic_pilotage.view' => ['Voir le pilotage académique', 'Pilotage académique', 'fa-chart-line'],
            'academic_pilotage.view_all' => ['Voir le pilotage académique de toutes les filières', 'Pilotage académique', 'fa-layer-group'],
            'academic_pilotage.configure' => ['Configurer le pilotage académique', 'Pilotage académique', 'fa-sliders-h'],
            'academic_sheets.view' => ['Voir toutes les fiches académiques', 'Fiches académiques', 'fa-clipboard-list'],
            'academic_sheets.view_own' => ['Voir les fiches académiques de son périmètre', 'Fiches académiques', 'fa-clipboard'],
            'academic_sheets.create' => ['Créer une fiche académique', 'Fiches académiques', 'fa-plus'],
            'academic_sheets.submit' => ['Soumettre une fiche académique', 'Fiches académiques', 'fa-paper-plane'],
            'academic_sheets.receive' => ['Réceptionner une fiche académique', 'Fiches académiques', 'fa-inbox'],
            'academic_sheets.enter' => ['Saisir les notes d\'une fiche académique', 'Fiches académiques', 'fa-keyboard'],
            'academic_sheets.control' => ['Contrôler une fiche académique', 'Fiches académiques', 'fa-clipboard-check'],
            'academic_sheets.validate' => ['Valider une fiche académique', 'Fiches académiques', 'fa-check-double'],
            'academic_sheets.assign' => ['Affecter une fiche académique', 'Fiches académiques', 'fa-user-check'],
            'academic_alerts.view' => ['Voir les alertes académiques', 'Alertes académiques', 'fa-bell'],
            'academic_alerts.view_own' => ['Voir les alertes académiques de son périmètre', 'Alertes académiques', 'fa-bell'],
            'academic_alerts.acknowledge' => ['Prendre en charge une alerte académique', 'Alertes académiques', 'fa-eye'],
            'academic_alerts.resolve' => ['Résoudre une alerte académique', 'Alertes académiques', 'fa-check-circle'],
            'academic_health.view' => ['Voir les indicateurs de santé académique', 'Santé académique', 'fa-heartbeat'],
            'academic_health.view_own' => ['Voir la santé académique de son périmètre', 'Santé académique', 'fa-heartbeat'],
            'academic_health.recalculate' => ['Recalculer les indicateurs de santé académique', 'Santé académique', 'fa-sync-alt'],
            'bulletins.generate_incomplete' => ['Générer un bulletin avec des notes incomplètes', 'Bulletins', 'fa-exclamation-triangle'],
        ];

        foreach ($expected as $permission => [$label, $group, $icon]) {
            $this->assertTrue($this->registry->isCanonical($permission), "$permission doit être canonique.");
            $this->assertSame(
                compact('label', 'group', 'icon'),
                $this->registry->permissionMeta($permission),
                "$permission doit exposer ses métadonnées françaises."
            );
        }
    }

    public function test_secretary_and_coordinator_receive_normal_pilotage_permissions(): void
    {
        $expected = [
            'module.academic_pilotage.access',
            'academic_pilotage.view',
            'academic_pilotage.view_all',
            'academic_sheets.view',
            'academic_sheets.create',
            'academic_sheets.submit',
            'academic_sheets.receive',
            'academic_sheets.enter',
            'academic_sheets.control',
            'academic_sheets.validate',
            'academic_sheets.assign',
            'academic_alerts.view',
            'academic_alerts.acknowledge',
            'academic_alerts.resolve',
            'academic_health.view',
            'academic_health.recalculate',
        ];

        $this->assertSame($expected, $this->pilotageDefaultsFor('secretaire'));
        $this->assertSame($expected, $this->pilotageDefaultsFor('coordinateur'));
        $this->assertSame([
            'module.academic_pilotage.access',
            'academic_pilotage.view',
            'academic_pilotage.view_all',
            'academic_sheets.view',
            'academic_alerts.view',
            'academic_alerts.acknowledge',
            'academic_health.view',
        ], $this->pilotageDefaultsFor('directeurEtudes'));
    }

    public function test_teacher_receives_limited_pilotage_permissions(): void
    {
        $this->assertSame([
            'module.academic_pilotage.access',
            'academic_pilotage.view',
            'academic_sheets.view_own',
            'academic_sheets.create',
            'academic_sheets.submit',
            'academic_sheets.enter',
            'academic_alerts.view_own',
            'academic_health.view_own',
        ], $this->pilotageDefaultsFor('enseignant'));

        $teacherPermissions = $this->registry->defaultPermissionsFor('enseignant');
        $this->assertNotContains('academic_sheets.view', $teacherPermissions);
        $this->assertNotContains('academic_alerts.view', $teacherPermissions);
        $this->assertNotContains('academic_health.view', $teacherPermissions);
    }

    public function test_incomplete_bulletin_generation_remains_restricted_to_wildcard_roles(): void
    {
        $restrictedPermission = 'bulletins.generate_incomplete';

        $this->assertContains($restrictedPermission, $this->registry->defaultPermissionsFor('superAdmin'));
        $this->assertContains($restrictedPermission, $this->registry->defaultPermissionsFor('serviceTechnique'));

        $nonWildcardRoles = $this->registry->roles()->keys()
            ->reject(fn (string $role) => in_array($role, ['superAdmin', 'serviceTechnique'], true));

        foreach ($nonWildcardRoles as $role) {
            $this->assertNotContains(
                $restrictedPermission,
                $this->registry->defaultPermissionsFor($role),
                "$role ne doit pas générer de bulletin incomplet."
            );
        }
    }

    public function test_it_does_not_add_an_educator_role(): void
    {
        $this->assertFalse($this->registry->roles()->has('educator'));
        $this->assertSame([], $this->registry->defaultPermissionsFor('educator'));
    }

    private function pilotageDefaultsFor(string $role): array
    {
        return array_values(array_intersect(
            self::PERMISSIONS,
            $this->registry->defaultPermissionsFor($role)
        ));
    }
}
