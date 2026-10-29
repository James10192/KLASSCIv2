<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check if the permission exists
$permission = Spatie\Permission\Models\Permission::where('name', 'edit_timetables')->first();
echo "Permission 'edit_timetables' exists: " . ($permission ? 'Yes' : 'No') . PHP_EOL;

if ($permission) {
    echo "Permission ID: " . $permission->id . PHP_EOL;
}

// Check if the superAdmin user has the permission
$user = App\Models\User::where('role', 'superAdmin')->first();
if ($user) {
    echo "SuperAdmin user found with ID: " . $user->id . PHP_EOL;
    echo "User has 'edit_timetables' permission: " . ($user->hasPermissionTo('edit_timetables') ? 'Yes' : 'No') . PHP_EOL;

    // List all permissions the user has
    echo "User permissions: " . PHP_EOL;
    foreach ($user->getAllPermissions() as $perm) {
        echo "- " . $perm->name . PHP_EOL;
    }

    // List all roles the user has
    echo "User roles: " . PHP_EOL;
    foreach ($user->getRoleNames() as $role) {
        echo "- " . $role . PHP_EOL;
    }
} else {
    echo "No superAdmin user found" . PHP_EOL;
}

echo "Done!" . PHP_EOL;
