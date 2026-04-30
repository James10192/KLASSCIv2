<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PermissionRegistry();
    }

    public function test_registry_has_canonical_roles(): void
    {
        $roles = $this->registry->roles()->keys()->all();
        $this->assertContains('superAdmin', $roles);
        $this->assertContains('secretaire', $roles);
        $this->assertContains('comptable', $roles);
        $this->assertContains('caissier', $roles);
        $this->assertContains('coordinateur', $roles);
        $this->assertContains('enseignant', $roles);
        $this->assertContains('etudiant', $roles);
        $this->assertContains('serviceTechnique', $roles);
    }

    public function test_parent_role_is_removed(): void
    {
        $roles = $this->registry->roles()->keys()->all();
        $this->assertNotContains('parent', $roles);
    }

    public function test_service_technique_is_hidden_from_ui(): void
    {
        $visible = $this->registry->rolesVisibleInUi()->keys()->all();
        $this->assertNotContains('serviceTechnique', $visible);
        $this->assertContains('superAdmin', $visible);
        $this->assertContains('caissier', $visible);
    }

    public function test_roles_have_french_labels(): void
    {
        $secretaire = $this->registry->roleMeta('secretaire');
        $this->assertNotNull($secretaire);
        $this->assertEquals('Secrétaire', $secretaire['label']);
        $this->assertNotEmpty($secretaire['description']);
    }

    public function test_canonicalize_resolves_legacy_aliases(): void
    {
        $this->assertEquals('students.view', $this->registry->canonicalize('view_students'));
        $this->assertEquals('inscriptions.edit', $this->registry->canonicalize('edit inscriptions'));
        $this->assertEquals('cycles.view', $this->registry->canonicalize('view cycles'));
        $this->assertEquals('users.manage', $this->registry->canonicalize('manage-users'));
        $this->assertEquals('admin.access', $this->registry->canonicalize('access_admin'));
        $this->assertEquals('classes.create', $this->registry->canonicalize('create_classe'));
    }

    public function test_canonicalize_returns_canonical_unchanged(): void
    {
        $this->assertEquals('students.view', $this->registry->canonicalize('students.view'));
        $this->assertEquals('module.caisse.access', $this->registry->canonicalize('module.caisse.access'));
    }

    public function test_canonicalize_returns_unknown_unchanged(): void
    {
        $this->assertEquals('totally.unknown.permission', $this->registry->canonicalize('totally.unknown.permission'));
    }

    public function test_aliases_of_returns_legacy_names(): void
    {
        $aliases = $this->registry->aliasesOf('students.view');
        $this->assertContains('view_students', $aliases);

        $aliases = $this->registry->aliasesOf('cycles.view');
        $this->assertContains('view cycles', $aliases);
    }

    public function test_default_permissions_for_super_admin_returns_all(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('superAdmin');
        $allCount = $this->registry->all()->count();
        $this->assertCount($allCount, $defaults);
    }

    public function test_default_permissions_for_etudiant_is_minimal(): void
    {
        $defaults = $this->registry->defaultPermissionsFor('etudiant');
        $this->assertContains('notes.view_own', $defaults);
        $this->assertContains('bulletins.view_own', $defaults);
        $this->assertNotContains('students.create', $defaults);
        $this->assertNotContains('users.manage', $defaults);
    }

    public function test_manageable_roles_returns_correct_matrix(): void
    {
        // Secrétaire peut gérer enseignant, étudiant, caissier
        $manageable = $this->registry->manageableRoles('secretaire');
        $this->assertContains('enseignant', $manageable);
        $this->assertContains('etudiant', $manageable);
        $this->assertContains('caissier', $manageable);
        $this->assertNotContains('superAdmin', $manageable);
        $this->assertNotContains('comptable', $manageable);

        // Étudiant ne peut gérer personne
        $this->assertEmpty($this->registry->manageableRoles('etudiant'));

        // Comptable ne peut gérer personne
        $this->assertEmpty($this->registry->manageableRoles('comptable'));

        // Caissier peut gérer étudiant (pour pré-inscription)
        $this->assertEquals(['etudiant'], $this->registry->manageableRoles('caissier'));
    }

    public function test_deprecated_permissions_are_marked(): void
    {
        $this->assertTrue($this->registry->isDeprecated('view_frais_scolarite'));
        $this->assertTrue($this->registry->isDeprecated('admin'));
        $this->assertTrue($this->registry->isDeprecated('teacher'));
        $this->assertFalse($this->registry->isDeprecated('students.view'));
        $this->assertFalse($this->registry->isDeprecated('module.caisse.access'));
    }

    public function test_all_names_includes_canonicals_and_aliases(): void
    {
        $allNames = $this->registry->allNames();
        $canonicalCount = $this->registry->all()->count();

        // Doit avoir plus de noms que de canoniques (à cause des aliases)
        $this->assertGreaterThan($canonicalCount, $allNames->count());

        // Doit contenir les canoniques
        $this->assertTrue($allNames->contains('students.view'));
        // Doit contenir les aliases
        $this->assertTrue($allNames->contains('view_students'));
    }

    public function test_module_caisse_access_exists_for_caissier(): void
    {
        $caissierDefaults = $this->registry->defaultPermissionsFor('caissier');
        $this->assertContains('module.caisse.access', $caissierDefaults);
    }
}
