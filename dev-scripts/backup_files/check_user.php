<?php

// Script to check user credentials and roles
// Run with: php check_user.php

// Initialize Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$username = 'ruben';

echo "=== USER CREDENTIALS CHECK ===\n\n";

// Check if user exists
try {
    $user = \App\Models\User::where('username', $username)
        ->orWhere('email', $username)
        ->first();

    if ($user) {
        echo "✅ User found:\n";
        echo "   ID: {$user->id}\n";
        echo "   Name: {$user->name}\n";
        echo "   Username: {$user->username}\n";
        echo "   Email: {$user->email}\n";
        
        // Check user roles
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            echo "   Roles: " . ($roles->count() > 0 ? implode(', ', $roles->toArray()) : 'No roles assigned') . "\n";
            
            // Check if user has superAdmin role
            $hasSuperAdminRole = $user->hasRole('superAdmin');
            echo "   Is SuperAdmin: " . ($hasSuperAdminRole ? 'Yes' : 'No') . "\n";
        } else {
            echo "   Roles: Unable to check (getRoleNames method not found)\n";
        }
        
        // Check password hash
        echo "   Password Hash: " . substr($user->password, 0, 25) . "...\n";
        
        // Test authentication
        $credentials = [
            'email' => $user->email,
            'password' => 'password' // Try with a common password
        ];
        
        if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
            echo "   ✅ Authentication successful with password 'password'\n";
        } else {
            echo "   ❌ Authentication failed with password 'password'\n";
        }
    } else {
        echo "❌ User '$username' not found in database\n";
        
        // List all users
        echo "\nExisting users:\n";
        $users = \App\Models\User::all();
        foreach ($users as $u) {
            echo "- {$u->name} (username: {$u->username}, email: {$u->email})\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING LOGIN FUNCTIONALITY ===\n";

// Check the Auth Controller or login routes
try {
    // Check login route
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $loginRouteFound = false;
    
    foreach ($routes as $route) {
        if ($route->uri == 'login' || $route->uri == 'auth/login') {
            $loginRouteFound = true;
            echo "✅ Login route found: " . $route->uri . " [" . implode('|', $route->methods) . "]\n";
            echo "   Controller: " . ($route->action['controller'] ?? 'Closure') . "\n";
        }
    }
    
    if (!$loginRouteFound) {
        echo "❌ No explicit login route found\n";
    }
    
    // Check dashboard redirect
    $dashboardRouteFound = false;
    foreach ($routes as $route) {
        if ($route->uri == 'dashboard' || strpos($route->uri, 'dashboard') !== false) {
            $dashboardRouteFound = true;
            echo "✅ Dashboard route found: " . $route->uri . " [" . implode('|', $route->methods) . "]\n";
            if (isset($route->action['controller'])) {
                echo "   Controller: " . $route->action['controller'] . "\n";
            } else if (isset($route->action['uses'])) {
                echo "   Uses: " . $route->action['uses'] . "\n";
            }
            
            // Check middleware
            if (isset($route->action['middleware'])) {
                echo "   Middleware: " . implode(', ', (array)$route->action['middleware']) . "\n";
            }
        }
    }
    
    if (!$dashboardRouteFound) {
        echo "❌ No dashboard route found\n";
    }
} catch (\Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "If the user doesn't exist or has incorrect roles:\n";
echo "1. Create the user or update roles using the reset_admin_account.php script\n";
echo "2. Check authentication configuration in config/auth.php\n";
echo "3. Check login controller for custom validation logic\n";
echo "4. Clear application caches: php artisan cache:clear\n"; 