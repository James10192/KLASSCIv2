<?php
// Script to diagnose and fix secretaire role permissions

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== ESBTP-yAKRO Secretaire Role Diagnostics ===\n\n";

// 1. Check the routes to see which controller is handling the secretaire dashboard
echo "Checking routes...\n";
$routes = Route::getRoutes();
foreach ($routes as $route) {
    if (str_contains($route->uri, 'secretaire') || 
        (isset($route->action['as']) && str_contains($route->action['as'], 'secretaire'))) {
        echo "Route: " . $route->uri . " (name: " . ($route->action['as'] ?? 'unnamed') . ")\n";
        echo "  Method: " . implode('|', $route->methods) . "\n";
        echo "  Controller: " . ($route->action['controller'] ?? 'Closure') . "\n";
        echo "  Middleware: " . implode(', ', $route->action['middleware'] ?? []) . "\n\n";
    }
}

// 2. Check if the secretaire role exists and what permissions it has
echo "Checking secretaire role...\n";
$secretaireRole = Role::where('name', 'secretaire')->first();
if ($secretaireRole) {
    echo "  Role 'secretaire' exists (ID: {$secretaireRole->id})\n";
    
    $permissions = $secretaireRole->permissions;
    echo "  Permissions (" . count($permissions) . "):\n";
    foreach ($permissions as $permission) {
        echo "    - {$permission->name}\n";
    }
    
    // Check for missing key permissions that might be needed
    $requiredPermissions = [
        'view_classes', 'view classes',
        'create_students', 'create students',
        'view_students', 'view students',
        'generate_bulletin', 'generate bulletin',
        'view_bulletins', 'view bulletins',
        'create_attendance', 'create attendance',
        'view_attendances', 'view attendances',
        'view_timetables', 'view timetables',
        'create_timetable', 'create timetable',
        'send_messages', 'send messages'
    ];
    
    $missingPermissions = [];
    foreach ($requiredPermissions as $perm) {
        if (!$permissions->contains('name', $perm)) {
            $missingPermissions[] = $perm;
        }
    }
    
    if (count($missingPermissions) > 0) {
        echo "\n  Missing permissions that might be needed:\n";
        foreach ($missingPermissions as $perm) {
            echo "    - {$perm}\n";
        }
    }
} else {
    echo "  Error: Role 'secretaire' does not exist!\n";
}

// 3. Check users with the secretaire role
echo "\nChecking users with secretaire role...\n";
$secretaireUsers = User::role('secretaire')->get();
if ($secretaireUsers->count() > 0) {
    echo "  Found " . $secretaireUsers->count() . " users with the secretaire role:\n";
    foreach ($secretaireUsers as $user) {
        echo "    - {$user->name} (ID: {$user->id}, Email: {$user->email})\n";
    }
} else {
    echo "  No users found with the secretaire role!\n";
}

// 4. Check for the test user
echo "\nChecking test secretaire user...\n";
$testUser = User::where('email', 'secretaire@esbtp.ci')->first();
if ($testUser) {
    echo "  Test user exists (ID: {$testUser->id}, Name: {$testUser->name})\n";
    echo "  Roles: " . implode(", ", $testUser->getRoleNames()->toArray()) . "\n";
    
    if (!$testUser->hasRole('secretaire')) {
        echo "  Test user does not have the secretaire role! Fixing this now...\n";
        $testUser->assignRole('secretaire');
        echo "  Secretaire role assigned.\n";
    }
} else {
    echo "  Test user secretaire@esbtp.ci not found!\n";
}

// 5. Check for missing permissions and add them if needed
echo "\nChecking for missing permissions...\n";
$allPermissions = Permission::pluck('name')->toArray();
echo "  Total permissions in system: " . count($allPermissions) . "\n";

// Define permissions the secretaire role should have based on your requirements
$secretairePermissions = [
    'view filieres',
    'view classes',
    'create students',
    'view students',
    'view exams',
    'view matieres',
    'create grades',
    'view grades',
    'generate bulletin',
    'view bulletins',
    'create timetable',
    'view timetables',
    'send messages',
    'create attendance',
    'view attendances',
    'inscriptions.view',
    'inscriptions.create',
    'inscriptions.edit',
    'inscriptions.validate'
];

// Find permissions that should exist but don't
$neededPermissions = [];
foreach ($secretairePermissions as $perm) {
    if (!in_array($perm, $allPermissions)) {
        $neededPermissions[] = $perm;
    }
}

if (count($neededPermissions) > 0) {
    echo "  Creating missing permissions:\n";
    foreach ($neededPermissions as $perm) {
        echo "    - Creating: {$perm}\n";
        Permission::create(['name' => $perm]);
    }
    
    // Refresh the secretaire role to include these permissions
    if ($secretaireRole) {
        $secretaireRole->givePermissionTo($neededPermissions);
        echo "  Assigned new permissions to secretaire role.\n";
    }
} else {
    echo "  All required permissions exist in the system.\n";
}

// Make sure the secretaire role has all the permissions it needs
if ($secretaireRole) {
    $missingRolePermissions = [];
    foreach ($secretairePermissions as $perm) {
        if (!$secretaireRole->hasPermissionTo($perm)) {
            $missingRolePermissions[] = $perm;
        }
    }
    
    if (count($missingRolePermissions) > 0) {
        echo "\n  Fixing missing permissions for secretaire role:\n";
        foreach ($missingRolePermissions as $perm) {
            echo "    - Adding permission: {$perm}\n";
        }
        $secretaireRole->givePermissionTo($missingRolePermissions);
        echo "  Permissions have been assigned.\n";
    } else {
        echo "\n  The secretaire role has all required permissions.\n";
    }
}

echo "\n=== Diagnosis Complete ===\n";
echo "Run this script with PHP CLI: php fix-secretaire-permissions.php\n";
echo "After running this script, try accessing the secretaire dashboard again.\n"; 