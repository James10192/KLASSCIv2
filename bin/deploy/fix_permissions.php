#!/usr/bin/env php
<?php

/**
 * Script de réparation/synchronisation des permissions et rôles.
 *
 * Source de vérité : config/permissions.php (lu via PermissionRegistry).
 *
 * Délègue à App\Services\PermissionSyncService — partagé avec
 * App\Http\Controllers\API\CLI\CLIPermissionController::sync (klassci-cli)
 * pour éviter le drift entre les deux entrypoints.
 *
 * Comportement (cf. PermissionSyncService::run) :
 * - Crée toutes les permissions canoniques + leurs aliases (rétrocompat)
 * - Crée tous les rôles canoniques
 * - Synchronise les permissions par défaut UNIQUEMENT si le rôle est vide
 *   (préserve les configurations live des tenants en prod)
 * - Healing canoniques pour les rôles ayant des aliases legacy
 * - Audit : signale les utilisateurs sans rôle (pas d'attribution auto)
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
use App\Services\PermissionSyncService;

echo "=== Synchronisation rôles & permissions (registry-driven) ===\n";

try {
    /** @var PermissionRegistry $registry */
    $registry = app(PermissionRegistry::class);

    /** @var PermissionSyncService $sync */
    $sync = app(PermissionSyncService::class);

    $result = $sync->run();

    echo "✅ Cache Spatie réinitialisé\n";
    echo "📋 {$result['permissions_count']} permissions créées/vérifiées\n";

    $roles = $registry->roles();
    foreach ($roles as $name => $meta) {
        echo "  ✓ rôle {$name} ({$meta['label']})\n";
    }
    echo "\n";

    foreach ($result['roles_with_defaults_assigned'] as $assigned) {
        echo "  ✓ {$assigned['role']} : {$assigned['permissions_count']} permissions assignées (rôle vide)\n";
    }
    foreach ($result['roles_preserved'] as $preserved) {
        echo "  ⤷ {$preserved} : permissions existantes (préservé)\n";
    }
    echo "\n";

    foreach ($result['aliases_healed'] as $healed) {
        echo "  ✓ {$healed['role']} : {$healed['canonicals_added']} canoniques ajoutés (alias → canon)\n";
    }
    echo "\n";

    /*
     * Audit utilisateurs sans rôle (signalement uniquement, pas d'attribution
     * automatique par email — supprimé Lot 5, sécurité)
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

    echo "\n=== Récapitulatif ===\n";
    echo "✅ {$result['permissions_count']} permissions (canoniques + aliases)\n";
    echo "✅ {$result['roles_count']} rôles\n";
    echo "✅ {$usersWithoutRole->count()} utilisateurs sans rôle traités\n";
    echo "✅ Cache des permissions réinitialisé\n";
    echo "\n💡 Pour auditer la cohérence du système, lancez :\n";
    echo "   php artisan permissions:audit\n";

} catch (Exception $e) {
    echo '❌ ERREUR : '.$e->getMessage()."\n";
    echo 'Stack trace : '.$e->getTraceAsString()."\n";
    exit(1);
}

echo "\n=== Fin du script ===\n";
