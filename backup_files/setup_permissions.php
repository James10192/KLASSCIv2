<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Récupérer le rôle étudiant
$studentRole = Role::findByName('etudiant');

if (!$studentRole) {
    echo "Le rôle 'etudiant' n'existe pas.\n";
    exit(1);
}

// Supprimer les anciennes permissions avec underscore
$oldPermissions = [
    'view_own_profile',
    'view_own_exams',
    'view_own_grades',
    'view_own_bulletin',
    'view_own_timetable',
    'view_own_attendances',
    'receive_messages'
];

foreach ($oldPermissions as $permission) {
    $perm = Permission::where('name', $permission)->first();
    if ($perm) {
        $studentRole->revokePermissionTo($perm);
        $perm->delete();
        echo "Permission '$permission' supprimée\n";
    }
}

// Liste des permissions nécessaires (format standardisé)
$permissions = [
    'view own profile',
    'view own grades',
    'view own timetable',
    'view own bulletin',
    'view own attendances',
    'view own exams',
    'receive own messages'
];

// Créer et attribuer les permissions
foreach ($permissions as $permission) {
    $perm = Permission::firstOrCreate(['name' => $permission]);
    if (!$studentRole->hasPermissionTo($permission)) {
        $studentRole->givePermissionTo($permission);
        echo "Permission '$permission' ajoutée au rôle étudiant\n";
    } else {
        echo "Permission '$permission' existe déjà pour le rôle étudiant\n";
    }
}

echo "\nPermissions actuelles du rôle étudiant :\n";
foreach ($studentRole->permissions as $permission) {
    echo "- " . $permission->name . "\n";
}
