<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

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

// Check if the permission exists in the database
$permission = DB::table('permissions')->where('name', 'edit_timetables')->first();
if ($permission) {
    echo "Permission 'edit_timetables' exists in the database: Yes\n";
    echo "Permission ID: " . $permission->id . "\n";
} else {
    echo "Permission 'edit_timetables' does not exist in the database!\n";
}

// Check if the user has the permission in the database
$userPermission = DB::table('model_has_permissions')
    ->where('permission_id', $permission->id)
    ->where('model_id', $user->id)
    ->where('model_type', get_class($user))
    ->first();

if ($userPermission) {
    echo "User has 'edit_timetables' permission in the database: Yes\n";
} else {
    echo "User does not have 'edit_timetables' permission in the database!\n";

    // Check if the user has the permission through a role
    $rolePermission = DB::table('role_has_permissions')
        ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
        ->where('role_has_permissions.permission_id', $permission->id)
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', get_class($user))
        ->first();

    if ($rolePermission) {
        echo "User has 'edit_timetables' permission through a role: Yes\n";
    } else {
        echo "User does not have 'edit_timetables' permission through a role!\n";
    }
}

// Check the route definition
$routes = Route::getRoutes();
$editRoute = null;

foreach ($routes as $route) {
    if ($route->getName() === 'esbtp.emploi-temps.edit') {
        $editRoute = $route;
        break;
    }
}

if ($editRoute) {
    echo "Route 'esbtp.emploi-temps.edit' found: Yes\n";
    echo "Route URI: " . $editRoute->uri() . "\n";
    echo "Route Methods: " . implode(', ', $editRoute->methods()) . "\n";
    echo "Route Middleware: " . implode(', ', $editRoute->middleware()) . "\n";
} else {
    echo "Route 'esbtp.emploi-temps.edit' not found!\n";
}

// Check the middleware implementation
$permissionMiddleware = new Spatie\Permission\Middleware\PermissionMiddleware();
$reflector = new ReflectionClass($permissionMiddleware);
$handleMethod = $reflector->getMethod('handle');
echo "PermissionMiddleware handle method exists: " . ($handleMethod ? 'Yes' : 'No') . "\n";

// Check if the user can access the route
echo "Checking if user can access the route...\n";

// Create a request for the edit route
$request = Illuminate\Http\Request::create('/esbtp/emploi-temps/1/edit', 'GET');

// Set the user as authenticated
Auth::login($user);

// Check if the user is authenticated
echo "User is authenticated: " . (Auth::check() ? 'Yes' : 'No') . "\n";

// Try to execute the route
try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();

    echo "Response status code: $statusCode\n";

    if ($statusCode === 403) {
        echo "Access denied! The route is forbidden.\n";
    } elseif ($statusCode === 200) {
        echo "Access granted! The route is accessible.\n";
    } else {
        echo "Unexpected status code.\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Simple script to test if the route is accessible
$url = 'http://localhost:8000/test-emploi-temps-show';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status code: " . $httpcode . "\n";

if ($httpcode == 200) {
    echo "Success! The route is accessible.\n";

    // Check if the response contains the error message
    if (strpos($response, 'Undefined variable $timeSlots') !== false) {
        echo "Error: Undefined variable \$timeSlots still exists\n";
    } else {
        echo "Success: No error about undefined variable \$timeSlots\n";
    }
} else {
    echo "Error: Failed to access the route. Status code: " . $httpcode . "\n";
}
