<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Liste des permissions à ajouter
$permissions = [
    // Permissions d'inscription
    'inscriptions.view',
    'inscriptions.create',
    'inscriptions.edit',
    'inscriptions.delete',
    'inscriptions.validate',
    'edit inscriptions',
    'valider inscriptions',
    'annuler inscriptions',
    'delete inscriptions',
    
    // Permissions de paiement
    'create-paiements',
    'edit-paiements',
    'validate-paiements',
    
    // Permissions avec espaces au lieu de underscores
    'view students',
    'view users',
    'view filieres',
    'view formations',
    'view niveaux etudes',
    'view classes',
    'view matieres',
    'view exams',
    'view bulletins',
    'view timetables',
    'view attendances',
    'receive messages',
    'view children bulletins',
    'view children attendances'
];

// Trouver le rôle superAdmin
$role = Role::findByName('superAdmin');

if (!$role) {
    echo "Rôle superAdmin non trouvé.\n";
    exit(1);
}

// Créer et ajouter les permissions
foreach ($permissions as $permissionName) {
    $permission = Permission::firstOrCreate(['name' => $permissionName]);
    if (!$role->hasPermissionTo($permissionName)) {
        $role->givePermissionTo($permissionName);
        echo "Permission '{$permissionName}' ajoutée au rôle superAdmin.\n";
    } else {
        echo "Le rôle superAdmin a déjà la permission '{$permissionName}'.\n";
    }
}

echo "Toutes les permissions ont été ajoutées au rôle superAdmin.\n"; 