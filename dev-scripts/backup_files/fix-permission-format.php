<?php
// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== Fixing Permission Format Inconsistencies ===\n\n";

// Get the secretaire role
$secretaireRole = Role::where('name', 'secretaire')->first();
if (!$secretaireRole) {
    echo "Error: Secretaire role not found!\n";
    exit(1);
}

// Permissions with both formats (space and underscore)
$permissionPairs = [
    ['view_classes', 'view classes'],
    ['create_classes', 'create classes'],
    ['edit_classes', 'edit classes'],
    ['delete_classes', 'delete classes'],
    
    ['view_students', 'view students'],
    ['create_students', 'create students'],
    ['edit_students', 'edit students'],
    ['delete_students', 'delete students'],
    
    ['view_exams', 'view exams'],
    ['create_exams', 'create exams'],
    ['edit_exams', 'edit exams'],
    ['delete_exams', 'delete exams'],
    
    ['view_matieres', 'view matieres'],
    ['create_matieres', 'create matieres'],
    ['edit_matieres', 'edit matieres'],
    ['delete_matieres', 'delete matieres'],
    
    ['view_grades', 'view grades'],
    ['create_grades', 'create grades'],
    ['edit_grades', 'edit grades'],
    ['delete_grades', 'delete grades'],
    
    ['view_bulletins', 'view bulletins'],
    ['generate_bulletin', 'generate bulletin'],
    ['edit_bulletins', 'edit bulletins'],
    ['delete_bulletins', 'delete bulletins'],
    
    ['view_timetables', 'view timetables'],
    ['create_timetable', 'create timetable'],
    ['edit_timetables', 'edit timetables'],
    ['delete_timetables', 'delete timetables'],
    
    ['send_messages', 'send messages'],
    ['receive_messages', 'receive messages'],
    
    ['view_attendances', 'view attendances'],
    ['create_attendance', 'create attendance'],
    ['edit_attendances', 'edit attendances'],
    ['delete_attendances', 'delete attendances'],
];

// For each pair, check if the permission exists and add it to the role if needed
foreach ($permissionPairs as $pair) {
    $underscoreFormat = $pair[0];
    $spaceFormat = $pair[1];
    
    // Check for the underscore format
    $underscorePermission = Permission::where('name', $underscoreFormat)->first();
    if ($underscorePermission && !$secretaireRole->hasPermissionTo($underscoreFormat)) {
        echo "Adding permission '$underscoreFormat' to secretaire role...\n";
        $secretaireRole->givePermissionTo($underscoreFormat);
    }
    
    // Check for the space format
    $spacePermission = Permission::where('name', $spaceFormat)->first();
    if ($spacePermission && !$secretaireRole->hasPermissionTo($spaceFormat)) {
        echo "Adding permission '$spaceFormat' to secretaire role...\n";
        $secretaireRole->givePermissionTo($spaceFormat);
    }
    
    // Create any missing formats that don't exist yet
    if (!$underscorePermission && !$spacePermission) {
        // If neither format exists, create the underscore format (more standard)
        echo "Creating new permission '$underscoreFormat'...\n";
        Permission::create(['name' => $underscoreFormat]);
        $secretaireRole->givePermissionTo($underscoreFormat);
    } else if (!$underscorePermission) {
        echo "Creating missing underscore format '$underscoreFormat'...\n";
        Permission::create(['name' => $underscoreFormat]);
        $secretaireRole->givePermissionTo($underscoreFormat);
    } else if (!$spacePermission) {
        echo "Creating missing space format '$spaceFormat'...\n";
        Permission::create(['name' => $spaceFormat]);
        $secretaireRole->givePermissionTo($spaceFormat);
    }
}

// Also add some extra permissions that might be needed for the routes in the dashboard
$extraPermissions = [
    'inscriptions.index',
    'inscriptions.create',
    'inscriptions.edit',
    'inscriptions.view',
    'timetables.view',
    'timetables.today',
    'etudiants.view',
    'attendances.index',
    'attendances.view',
    'bulletins.pending',
    'bulletins.view',
];

foreach ($extraPermissions as $permission) {
    $perm = Permission::where('name', $permission)->first();
    if (!$perm) {
        echo "Creating extra permission '$permission'...\n";
        $perm = Permission::create(['name' => $permission]);
    }
    
    if (!$secretaireRole->hasPermissionTo($permission)) {
        echo "Adding extra permission '$permission' to secretaire role...\n";
        $secretaireRole->givePermissionTo($permission);
    }
}

// Refresh permissions cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

echo "\nPermission fix complete! Try accessing the secretaire dashboard and its links now.\n";
echo "If you still encounter issues, run this script again and try clearing the Laravel cache with:\n";
echo "php artisan cache:clear\n";
echo "php artisan config:clear\n"; 