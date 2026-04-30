#!/usr/bin/env php
<?php

/**
 * Script de réparation/synchronisation des permissions et rôles.
 *
 * Source de vérité : config/permissions.php (lu via PermissionRegistry).
 *
 * Comportement :
 * - Crée toutes les permissions canoniques + leurs aliases (rétrocompat)
 * - Crée tous les rôles canoniques
 * - Synchronise les permissions par défaut de chaque rôle (canon + aliases)
 *   UNIQUEMENT si le rôle n'a aucune permission assignée (préserve les
 *   configurations live des tenants en prod)
 * - Détecte les utilisateurs sans rôle et leur attribue un rôle par défaut
 *
 * Usage : php bin/deploy/fix_permissions.php
 */

define('PROJECT_ROOT', dirname(__DIR__, 2));

if (! file_exists(PROJECT_ROOT.'/artisan')) {
    exit("❌ ERREUR: PROJECT_ROOT incorrecte. Artisan non trouvé.\n");
}

require PROJECT_ROOT.'/vendor/autoload.php';

$app = require_once PROJECT_ROOT.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use App\Services\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "=== Synchronisation rôles & permissions (registry-driven) ===\n";

try {
    /** @var PermissionRegistry $registry */
    $registry = app(PermissionRegistry::class);

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "✅ Cache Spatie réinitialisé\n\n";

    /*
     * 1. Créer toutes les permissions (canoniques + aliases pour rétrocompat)
     */
    $allNames = $registry->allNames();
    echo "📋 Création des permissions ({$allNames->count()} total)...\n";
    foreach ($allNames as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    echo "  ✓ {$allNames->count()} permissions créées/vérifiées\n\n";

    /*
     * 2. Créer tous les rôles
     */
    $roles = $registry->roles();
    echo "👥 Création des rôles ({$roles->count()} total)...\n";
    foreach ($roles as $name => $meta) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        echo "  ✓ {$name} ({$meta['label']})\n";
    }
    echo "\n";

    /*
     * 3. Synchroniser les permissions par défaut (UNIQUEMENT si rôle vide)
     *    Préserve les configurations live des tenants
     */
    echo "🔐 Attribution des permissions par défaut...\n";
    foreach ($roles->keys() as $roleName) {
        $role = Role::findByName($roleName);
        $existingCount = $role->permissions->count();

        if ($existingCount > 0) {
            echo "  ⤷ {$roleName} : {$existingCount} permissions existantes (préservé)\n";
            continue;
        }

        // Récupère défauts canoniques + leurs aliases (pour @can('view_students') legacy)
        $canonicals = $registry->defaultPermissionsFor($roleName);
        $expanded = [];
        foreach ($canonicals as $canonical) {
            $expanded[] = $canonical;
            foreach ($registry->aliasesOf($canonical) as $alias) {
                $expanded[] = $alias;
            }
        }
        $expanded = array_values(array_unique($expanded));

        $role->syncPermissions($expanded);
        echo "  ✓ {$roleName} : ".count($expanded)." permissions assignées\n";
    }
    echo "\n";

    /*
     * 4. Utilisateurs sans rôle : audit (signalement uniquement, pas d'attribution
     *    automatique par email — supprimé Lot 5, sécurité)
     */
    echo "🔍 Vérification des utilisateurs sans rôle...\n";
    $usersWithoutRole = User::doesntHave('roles')->get();

    if ($usersWithoutRole->isNotEmpty()) {
        echo "  ⚠️  {$usersWithoutRole->count()} utilisateur(s) sans rôle détecté(s) :\n";
        foreach ($usersWithoutRole as $user) {
            echo "    • {$user->name} ({$user->email}) — à assigner manuellement via /esbtp/personnel\n";
        }
        echo "  💡 L'attribution doit se faire explicitement via l'interface admin (Lot 5).\n";
    } else {
        echo "  ✓ Tous les utilisateurs ont un rôle\n";
    }

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    echo "\n=== Récapitulatif ===\n";
    echo "✅ {$allNames->count()} permissions (canoniques + aliases)\n";
    echo '✅ '.$roles->count()." rôles\n";
    echo '✅ '.$usersWithoutRole->count()." utilisateurs sans rôle traités\n";
    echo "✅ Cache des permissions réinitialisé\n";
    echo "\n💡 Pour auditer la cohérence du système, lancez :\n";
    echo "   php artisan permissions:audit\n";

} catch (Exception $e) {
    echo '❌ ERREUR : '.$e->getMessage()."\n";
    echo 'Stack trace : '.$e->getTraceAsString()."\n";
    exit(1);
}

echo "\n=== Fin du script ===\n";
