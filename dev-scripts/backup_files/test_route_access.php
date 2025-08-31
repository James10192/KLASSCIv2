<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get the student user
$user = App\Models\User::where('role', 'etudiant')->first();

if (!$user) {
    echo "No student user found.\n";
    exit;
}

echo "Testing with student user: {$user->name} (ID: {$user->id})\n";

// Get the ESBTPEtudiant record
$etudiant = App\Models\ESBTPEtudiant::where('user_id', $user->id)->first();

if (!$etudiant) {
    echo "No ESBTPEtudiant record found for user ID {$user->id}.\n";

    // Try with a different user
    $user = App\Models\User::where('role', 'etudiant')->where('id', '!=', $user->id)->first();

    if (!$user) {
        echo "No other student user found.\n";
        exit;
    }

    echo "Trying with a different student user: {$user->name} (ID: {$user->id})\n";

    $etudiant = App\Models\ESBTPEtudiant::where('user_id', $user->id)->first();

    if (!$etudiant) {
        echo "No ESBTPEtudiant record found for user ID {$user->id}.\n";
        exit;
    }
}

echo "Found ESBTPEtudiant record: {$etudiant->prenoms} {$etudiant->nom} (ID: {$etudiant->id})\n";

// Get the active inscription
$inscription = $etudiant->inscriptions()
    ->where('status', 'active')
    ->whereHas('anneeUniversitaire', function($query) {
        $query->where('is_current', true);
    })
    ->first();

if (!$inscription) {
    echo "No active inscription found for student ID {$etudiant->id}.\n";
    exit;
}

echo "Found active inscription (ID: {$inscription->id}) for class: {$inscription->classe->name}\n";

// Log in as the student
Illuminate\Support\Facades\Auth::login($user);

// Check if the user has the required permission
echo "Checking if the user has the required permission...\n";
echo "User has 'view own timetable' permission: " . ($user->hasPermissionTo('view own timetable') ? 'Yes' : 'No') . "\n";
echo "User has 'view_timetables' permission: " . ($user->hasPermissionTo('view_timetables') ? 'Yes' : 'No') . "\n";

// Check if the route exists
$routeName = 'esbtp.mon-emploi-temps.index';
$routeExists = Illuminate\Support\Facades\Route::has($routeName);
echo "Route '{$routeName}' exists: " . ($routeExists ? 'Yes' : 'No') . "\n";

// Get the URL for the route
if ($routeExists) {
    try {
        $url = route($routeName);
        echo "URL for route '{$routeName}': {$url}\n";
    } catch (Exception $e) {
        echo "Error generating URL for route '{$routeName}': {$e->getMessage()}\n";
    }
}

// Try to access the route
echo "Trying to access the route...\n";
try {
    $request = Illuminate\Http\Request::create('/esbtp/mon-emploi-temps', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    $response = $app->make('Illuminate\Contracts\Http\Kernel')->handle($request);

    $statusCode = $response->getStatusCode();
    echo "Response status code: {$statusCode}\n";

    if ($statusCode === 200) {
        echo "Route is accessible!\n";
    } else if ($statusCode === 302) {
        echo "Route is redirecting. Redirect URL: " . $response->headers->get('Location') . "\n";
    } else {
        echo "Route is not accessible. Status code: {$statusCode}\n";
    }
} catch (Exception $e) {
    echo "Exception caught: {$e->getMessage()}\n";
}

echo "Test completed.\n";
