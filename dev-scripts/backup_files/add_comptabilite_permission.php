<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "=== AJOUT DES PERMISSIONS DU MODULE COMPTABILITÉ ===\n";

// Vérifier et créer la permission d'accès au module comptabilité
$accessPermission = Permission::firstOrCreate(['name' => 'access_comptabilite_module']);
echo "Permission 'access_comptabilite_module': " . ($accessPermission->wasRecentlyCreated ? 'Créée' : 'Existe déjà') . "\n";

// Liste des permissions à vérifier/créer
$comptabilitePermissions = [
    'view_paiements',
    'create_paiements',
    'edit_paiements',
    'delete_paiements',
    'view_frais_scolarite',
    'create_frais_scolarite',
    'edit_frais_scolarite',
    'delete_frais_scolarite',
    'view_depenses',
    'create_depenses',
    'edit_depenses',
    'delete_depenses',
    'view_salaires',
    'create_salaires',
    'edit_salaires',
    'delete_salaires',
    'view_bourses',
    'create_bourses',
    'edit_bourses',
    'delete_bourses',
    'view_reporting_financier',
    'export_reporting_financier'
];

// Vérifier et créer chaque permission
foreach ($comptabilitePermissions as $permName) {
    $permission = Permission::firstOrCreate(['name' => $permName]);
    echo "Permission '$permName': " . ($permission->wasRecentlyCreated ? 'Créée' : 'Existe déjà') . "\n";
}

// Trouver le rôle superAdmin
$superAdminRole = Role::where('name', 'superAdmin')->first();

if (!$superAdminRole) {
    echo "⚠️ Le rôle 'superAdmin' n'existe pas. Création du rôle...\n";
    $superAdminRole = Role::create(['name' => 'superAdmin']);
    echo "✅ Rôle 'superAdmin' créé avec succès.\n";
}

// Attribuer toutes les permissions au superAdmin
$allPermissions = Permission::all();
$superAdminRole->syncPermissions($allPermissions);

echo "✅ Toutes les permissions ont été attribuées au rôle 'superAdmin'.\n";

// Réinitialiser le cache des permissions
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "✅ Cache des permissions réinitialisé.\n";

echo "\nTerminé! Le module de comptabilité devrait maintenant être accessible pour les super administrateurs.\n"; 