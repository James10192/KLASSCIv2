<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\ESBTPCustomRoleController;
use App\Services\PermissionRegistry;
use Tests\TestCase;

/**
 * Lot 8 — Tests de la logique critique sécurité du contrôleur ESBTPCustomRoleController.
 *
 * Sans DB : on cible :
 * - le filtrage "permissions accordables" (un acteur ne peut donner que ce qu'il possède)
 * - la matrice manageableRolesForActor (Lot 5)
 * - le test des helpers privés via Reflection
 */
class CustomRoleCrudTest extends TestCase
{
    public function test_controller_class_exists_and_has_crud_methods(): void
    {
        $this->assertTrue(class_exists(ESBTPCustomRoleController::class));

        $methods = ['index', 'create', 'store', 'edit', 'update', 'destroy', 'assignUsersForm', 'assignUsers', 'detachUser'];
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(ESBTPCustomRoleController::class, $method),
                "ESBTPCustomRoleController doit exposer la méthode publique {$method}()."
            );
        }
    }

    public function test_grantable_permissions_helper_is_private(): void
    {
        // L'helper grantablePermissionsForActor() doit rester privé pour ne pas
        // être exposé en tant que route accidentellement.
        $ref = new \ReflectionClass(ESBTPCustomRoleController::class);
        $method = $ref->getMethod('grantablePermissionsForActor');
        $this->assertTrue($method->isPrivate(), 'grantablePermissionsForActor() doit être private.');

        $method = $ref->getMethod('manageableRolesForActor');
        $this->assertTrue($method->isPrivate(), 'manageableRolesForActor() doit être private.');

        $method = $ref->getMethod('customRolesQuery');
        $this->assertTrue($method->isPrivate(), 'customRolesQuery() doit être private.');
    }

    public function test_controller_uses_permission_registry(): void
    {
        $ref = new \ReflectionClass(ESBTPCustomRoleController::class);

        // store / update / destroy / assignUsers acceptent PermissionRegistry pour DI
        foreach (['store', 'update', 'destroy', 'assignUsers', 'create', 'edit'] as $method) {
            $m = $ref->getMethod($method);
            $params = $m->getParameters();
            $hasRegistry = false;
            foreach ($params as $p) {
                $type = $p->getType();
                if ($type && method_exists($type, 'getName') && $type->getName() === PermissionRegistry::class) {
                    $hasRegistry = true;
                    break;
                }
            }
            $this->assertTrue($hasRegistry, "{$method}() doit injecter PermissionRegistry via type-hint pour la testabilité.");
        }
    }

    public function test_registry_provides_role_is_custom_helper(): void
    {
        $registry = new PermissionRegistry();
        $this->assertTrue(method_exists($registry, 'roleIsCustom'));
        // Sans DB, l'appel doit retourner false sans plantage (graceful)
        $this->assertFalse($registry->roleIsCustom('inexistant'));
    }

    public function test_registry_clear_cache_is_callable(): void
    {
        $registry = new PermissionRegistry();
        $this->assertTrue(method_exists($registry, 'clearCache'));
        $registry->clearCache();
        $this->assertTrue(true); // ne doit pas plantage
    }

    public function test_registry_role_meta_returns_is_custom_field(): void
    {
        $registry = new PermissionRegistry();

        // Pour un rôle système classique → is_custom doit être présent et false
        $meta = $registry->roleMeta('superAdmin');
        $this->assertNotNull($meta);
        $this->assertArrayHasKey('is_custom', $meta);
        $this->assertFalse($meta['is_custom']);
    }

    public function test_registry_visible_in_ui_does_not_include_service_technique_by_default(): void
    {
        $registry = new PermissionRegistry();
        $visible = $registry->rolesVisibleInUi()->keys()->all();

        $this->assertNotContains('serviceTechnique', $visible);
    }

    public function test_controller_validates_name_pattern(): void
    {
        // On vérifie que le source contient bien la regex de validation snake_case ASCII
        // pour le nom interne (slug).
        $source = file_get_contents((new \ReflectionClass(ESBTPCustomRoleController::class))->getFileName());

        $this->assertStringContainsString("'regex:/^[a-z][a-z0-9_]*\$/'", $source,
            'Le contrôleur doit valider le nom du rôle en snake_case ASCII.');
        $this->assertStringContainsString("Rule::unique('roles', 'name')", $source,
            'Le nom du rôle doit être unique en DB.');
    }

    public function test_controller_blocks_modification_of_system_roles(): void
    {
        $source = file_get_contents((new \ReflectionClass(ESBTPCustomRoleController::class))->getFileName());

        // update() / destroy() doivent vérifier is_custom
        $this->assertStringContainsString('is_custom', $source);
        // Il doit y avoir un message qui empêche modification système
        $this->assertStringContainsString('rôles système', $source);
    }

    public function test_controller_restricts_grantable_permissions_to_actor_capability(): void
    {
        $source = file_get_contents((new \ReflectionClass(ESBTPCustomRoleController::class))->getFileName());

        // Il doit y avoir un appel actor->can($perm) ou équivalent
        $this->assertStringContainsString('$actor->can($p->name)', $source,
            'Le contrôleur doit filtrer les permissions accordables par celles que l\'acteur possède.');
    }

    public function test_controller_blocks_destroy_when_users_attached(): void
    {
        $source = file_get_contents((new \ReflectionClass(ESBTPCustomRoleController::class))->getFileName());

        // destroy() doit refuser si users_count > 0
        $this->assertStringContainsString('$usersCount = $roleModel->users()->count()', $source);
        $this->assertStringContainsString('Détachez-les d\'abord', $source);
    }

    public function test_controller_clears_caches_after_mutations(): void
    {
        $source = file_get_contents((new \ReflectionClass(ESBTPCustomRoleController::class))->getFileName());

        // Cache Spatie + registry doit être vidé
        $this->assertStringContainsString('PermissionRegistrar::class', $source);
        $this->assertStringContainsString('forgetCachedPermissions()', $source);
        $this->assertStringContainsString('$registry->clearCache()', $source);
    }
}
