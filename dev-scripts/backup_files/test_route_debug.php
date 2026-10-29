<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the superAdmin user
$user = App\Models\User::where('email', 'admin@esbtp.ci')->first();

if (!$user) {
    echo "SuperAdmin user not found!\n";
    exit;
}

echo "Testing with user ID: " . $user->id . "\n";
echo "User roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
echo "User has 'edit_timetables' permission: " . ($user->hasPermissionTo('edit_timetables') ? 'Yes' : 'No') . "\n";

// Check if the emploi_temp with ID 1 exists
$emploiTemp = App\Models\ESBTPEmploiTemps::find(1);
if ($emploiTemp) {
    echo "Emploi temps with ID 1 exists: Yes\n";
    echo "Emploi temps details:\n";
    echo "- Title: " . $emploiTemp->title . "\n";
    echo "- Class ID: " . $emploiTemp->classe_id . "\n";
} else {
    echo "Emploi temps with ID 1 does not exist!\n";
}

// Create a request for the edit route
$request = Illuminate\Http\Request::create('/esbtp/emploi-temps/1/edit', 'GET');

// Set the user as authenticated
$app['auth']->guard()->setUser($user);

// Check if the user is authenticated
echo "User is authenticated: " . (auth()->check() ? 'Yes' : 'No') . "\n";

// Get the route
$routes = app('router')->getRoutes();
$route = $routes->match($request);

if (!$route) {
    echo "Route not found!\n";
    exit;
}

echo "Route found: " . $route->getName() . "\n";

// Get the middleware for the route
$middleware = $route->gatherMiddleware();
echo "Route middleware: " . implode(', ', $middleware) . "\n";

// Test each middleware
foreach ($middleware as $mw) {
    echo "Testing middleware: $mw\n";

    // Test role middleware
    if (strpos($mw, 'role:') === 0) {
        $roles = substr($mw, 5); // Remove 'role:' prefix
        $rolesList = explode('|', $roles);

        echo "  Required roles: " . implode(', ', $rolesList) . "\n";

        $hasRole = false;
        foreach ($rolesList as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                echo "  User has role '$role': Yes\n";
            } else {
                echo "  User has role '$role': No\n";
            }
        }

        echo "  Role middleware check: " . ($hasRole ? 'PASS' : 'FAIL') . "\n";
    }

    // Test permission middleware
    if (strpos($mw, 'permission:') === 0) {
        $permissions = substr($mw, 11); // Remove 'permission:' prefix
        $permissionsList = explode('|', $permissions);

        echo "  Required permissions: " . implode(', ', $permissionsList) . "\n";

        $hasPermission = false;
        foreach ($permissionsList as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $hasPermission = true;
                echo "  User has permission '$permission': Yes\n";
            } else {
                echo "  User has permission '$permission': No\n";
            }
        }

        echo "  Permission middleware check: " . ($hasPermission ? 'PASS' : 'FAIL') . "\n";
    }
}

// Try to execute the route
try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();

    echo "Response status code: $statusCode\n";

    if ($statusCode === 403) {
        echo "Access denied! The route is forbidden.\n";

        // Try to get the response content
        $content = $response->getContent();
        echo "Response content: " . substr($content, 0, 500) . "...\n";
    } elseif ($statusCode === 200) {
        echo "Access granted! The route is accessible.\n";
    } else {
        echo "Unexpected status code.\n";

        // Try to get the response content
        $content = $response->getContent();
        echo "Response content: " . substr($content, 0, 500) . "...\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "Done!\n";
