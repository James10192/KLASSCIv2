<?php
// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== Adding Comptabilite Permissions to Secretaire Role ===\n\n";

// Get the secretaire role
$secretaireRole = Role::where('name', 'secretaire')->first();

if (!$secretaireRole) {
    echo "Error: Secretaire role not found!\n";
    exit(1);
}

echo "Found secretaire role (ID: {$secretaireRole->id})\n";

// Define all permissions related to the comptabilite module
$comptabilitePermissions = [
    'access_comptabilite_module',
    'view_paiements',
    'create_paiements',
    'edit_paiements',
    'delete_paiements',
    'validate_paiements',
    'view_frais_scolarite',
    'view_depenses',
    'view_bourses',
    'view_rapports_financiers',
    'view_comptabilite_dashboard',
];

// Create permissions if they don't exist and assign them to the secretaire role
$assignedCount = 0;
$createdCount = 0;

foreach ($comptabilitePermissions as $permName) {
    $permission = Permission::where('name', $permName)->first();
    
    if (!$permission) {
        echo "Creating permission: $permName\n";
        $permission = Permission::create(['name' => $permName]);
        $createdCount++;
    }
    
    if (!$secretaireRole->hasPermissionTo($permName)) {
        echo "Assigning permission '$permName' to secretaire role\n";
        $secretaireRole->givePermissionTo($permName);
        $assignedCount++;
    } else {
        echo "Permission '$permName' already assigned to secretaire role\n";
    }
}

// Also add an alias for permissions with underscores to handle any naming inconsistencies
$aliasPermissions = [
    'access_comptabilite' => 'access_comptabilite_module',
    'view_comptabilite' => 'access_comptabilite_module',
    'view_payments' => 'view_paiements',
    'create_payment' => 'create_paiements',
    'edit_payment' => 'edit_paiements',
    'delete_payment' => 'delete_paiements',
    'validate_payment' => 'validate_paiements',
];

foreach ($aliasPermissions as $aliasName => $originalName) {
    $permission = Permission::where('name', $aliasName)->first();
    
    if (!$permission) {
        echo "Creating alias permission: $aliasName\n";
        $permission = Permission::create(['name' => $aliasName]);
        $createdCount++;
    }
    
    if (!$secretaireRole->hasPermissionTo($aliasName)) {
        echo "Assigning alias permission '$aliasName' to secretaire role\n";
        $secretaireRole->givePermissionTo($aliasName);
        $assignedCount++;
    }
}

echo "\nResults:\n";
echo "- $createdCount permissions created\n";
echo "- $assignedCount permissions assigned to secretaire role\n";

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "Permission cache cleared\n";

echo "\n=== Completed ===\n";
echo "You may now try accessing the comptabilite features as a secretaire user.\n"; 