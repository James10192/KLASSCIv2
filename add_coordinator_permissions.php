#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== Ajout des permissions manquantes pour coordinateur ===\n";

// Réinitialiser les caches des permissions
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

try {
    $coordinateur = Role::where('name', 'coordinateur')->first();
    if (!$coordinateur) {
        echo "❌ Rôle coordinateur non trouvé\n";
        exit(1);
    }

    $missingPermissions = [
        'manage-planning',
        'view-all-timetables', 
        'view_inscriptions',
        'view_notes',
        'create_notes',
        'edit_notes',
        'view_annonces',
        'create_annonces',
        'edit_annonces',
        'personnel.unified.view',
        'personnel.unified.index'
    ];

    foreach ($missingPermissions as $permissionName) {
        $permission = Permission::where('name', $permissionName)->first();
        
        if ($permission) {
            if (!$coordinateur->hasPermissionTo($permission)) {
                $coordinateur->givePermissionTo($permission);
                echo "✅ Permission '$permissionName' ajoutée au coordinateur\n";
            } else {
                echo "ℹ️ Permission '$permissionName' déjà assignée\n";
            }
        } else {
            echo "⚠️ Permission '$permissionName' non trouvée dans la base\n";
        }
    }

    echo "\nVérification finale - Permissions du coordinateur:\n";
    $coordinateurPermissions = $coordinateur->getAllPermissions();
    
    foreach ($missingPermissions as $perm) {
        $hasPermission = $coordinateurPermissions->where('name', $perm)->count() > 0;
        echo ($hasPermission ? "✅" : "❌") . " $perm\n";
    }

    // Clear caches
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('permission:cache-reset');
    
    echo "\n✅ Terminé avec succès!\n";

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== Fin du script ===\n";