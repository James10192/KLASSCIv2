<?php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    exit;
}

echo "Found ESBTPEtudiant record: {$etudiant->prenoms} {$etudiant->nom} (ID: {$etudiant->id})\n";

// Log in as the student
Illuminate\Support\Facades\Auth::login($user);

// Try calling the controller method directly
echo "Calling controller method directly...\n";
try {
    $controller = new App\Http\Controllers\ESBTPEmploiTempsController();
    $result = $controller->studentTimetable();
    echo "Controller method executed successfully\n";

    if ($result instanceof Illuminate\View\View) {
        echo "Controller returned a view: " . $result->getName() . "\n";
        echo "View data keys: " . implode(', ', array_keys($result->getData())) . "\n";
    } elseif ($result instanceof Illuminate\Http\RedirectResponse) {
        echo "Controller returned a redirect to: " . $result->getTargetUrl() . "\n";
    } else {
        echo "Controller returned something other than a view: " . get_class($result) . "\n";
    }
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "Test completed.\n";
