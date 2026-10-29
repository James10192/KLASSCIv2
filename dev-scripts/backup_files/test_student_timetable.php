<?php

// Bootstrap the Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get a student user
$user = App\Models\User::role('etudiant')->first();

if (!$user) {
    var_dump("No student user found. Please create a student user first.");
    exit(1);
}

var_dump("Testing with student user: {$user->name} (ID: {$user->id})");

// Check if the student has an associated ESBTPEtudiant record
$etudiant = App\Models\ESBTPEtudiant::where('user_id', $user->id)->first();

if (!$etudiant) {
    var_dump("No ESBTPEtudiant record found for user {$user->name}. Please create an ESBTPEtudiant record for this user.");
    exit(1);
}

var_dump("Found ESBTPEtudiant record: {$etudiant->nom} {$etudiant->prenoms} (ID: {$etudiant->id})");

// Check if the student has an active inscription
$inscription = $etudiant->inscriptions()
    ->where('status', 'active')
    ->whereHas('anneeUniversitaire', function($query) {
        $query->where('is_current', true);
    })
    ->first();

if (!$inscription) {
    var_dump("No active inscription found for student {$etudiant->nom} {$etudiant->prenoms}. Please create an active inscription for this student.");
    exit(1);
}

var_dump("Found active inscription (ID: {$inscription->id}) for class: {$inscription->classe->name}");

// Check if there's a timetable for the student's class
$emploiTemps = App\Models\ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
    ->where('id', 3) // Use the timetable with sessions
    ->first();

if (!$emploiTemps) {
    var_dump("No current timetable found for class {$inscription->classe->name}. Creating a test timetable...");

    // Create a timetable for testing
    $emploiTemps = new App\Models\ESBTPEmploiTemps();
    $emploiTemps->titre = "Emploi du temps test pour {$inscription->classe->name}";
    $emploiTemps->classe_id = $inscription->classe_id;
    $emploiTemps->semestre = "1";
    $emploiTemps->date_debut = now();
    $emploiTemps->date_fin = now()->addMonths(6);
    $emploiTemps->is_active = true;
    $emploiTemps->is_current = true;
    $emploiTemps->created_by = 1; // Assuming user ID 1 is an admin
    $emploiTemps->save();

    var_dump("Test timetable created (ID: {$emploiTemps->id})");

    // Set this timetable as current
    App\Models\ESBTPEmploiTemps::setAsCurrent($emploiTemps->id);

    var_dump("Test timetable set as current");
}
else {
    var_dump("Found current timetable (ID: {$emploiTemps->id}) for class {$inscription->classe->name}");
}

// Check if the timetable has sessions
$seances = $emploiTemps->seances()->count();

var_dump("Timetable has {$seances} sessions");

// Simulate a request to the student timetable page
var_dump("Simulating a request to the student timetable page...");

// Log in as the student
Auth::login($user);

// Check if the user has the required permission
var_dump("Checking if the user has the required permission...");
var_dump("User has 'view own timetable' permission: " . ($user->hasPermissionTo('view own timetable') ? 'Yes' : 'No'));
var_dump("User has 'view_timetables' permission: " . ($user->hasPermissionTo('view_timetables') ? 'Yes' : 'No'));

// Try calling the controller method directly
var_dump("Calling controller method directly...");
try {
    $controller = new \App\Http\Controllers\ESBTPEmploiTempsController();
    $result = $controller->studentTimetable();
    var_dump("Controller method executed successfully");

    if ($result instanceof \Illuminate\View\View) {
        var_dump("Controller returned a view: " . $result->getName());
        var_dump("View data keys: " . implode(', ', array_keys($result->getData())));
    } else {
        var_dump("Controller returned something other than a view: " . get_class($result));
    }
} catch (\Exception $e) {
    var_dump("Exception caught: " . $e->getMessage());
    var_dump("File: " . $e->getFile() . " on line " . $e->getLine());
}

// Create a request to the student timetable page
$request = Request::create('/esbtp/mon-emploi-temps', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Dispatch the request
try {
    $response = app()->handle($request);

    // Check the response
    $statusCode = $response->getStatusCode();
    var_dump("Response status code: {$statusCode}");

    // Debug view resolution
    var_dump("Checking view resolution...");
    $viewExists = view()->exists('etudiants.emploi-temps');
    var_dump("View 'etudiants.emploi-temps' exists: " . ($viewExists ? 'true' : 'false'));

    if ($statusCode === 200) {
        var_dump("Success! The student timetable page is accessible.");

        // Check if the response contains the timetable data
        $content = $response->getContent();
        if (strpos($content, 'Emploi du temps -') !== false) {
            var_dump("The timetable is displayed correctly.");
        } else {
            var_dump("The timetable is not displayed correctly. Please check the view file.");
        }
    } else {
        var_dump("Error! The student timetable page is not accessible. Status code: {$statusCode}");
    }
} catch (\Exception $e) {
    var_dump("Exception caught: " . $e->getMessage());
    var_dump("File: " . $e->getFile() . " on line " . $e->getLine());
}

var_dump("Test completed.");
