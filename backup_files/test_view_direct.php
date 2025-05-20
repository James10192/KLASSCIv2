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

// Get the current timetable
$emploiTemps = App\Models\ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
    ->where('is_current', true)
    ->first();

if (!$emploiTemps) {
    echo "No current timetable found for class ID {$inscription->classe_id}.\n";

    // Create a test timetable
    $emploiTemps = new App\Models\ESBTPEmploiTemps();
    $emploiTemps->title = "Test Timetable for {$inscription->classe->name}";
    $emploiTemps->classe_id = $inscription->classe_id;
    $emploiTemps->semestre = 1;
    $emploiTemps->date_debut = now();
    $emploiTemps->date_fin = now()->addMonths(3);
    $emploiTemps->is_current = true;
    $emploiTemps->save();

    echo "Created test timetable (ID: {$emploiTemps->id}) and set as current.\n";
}
else {
    echo "Found current timetable (ID: {$emploiTemps->id}) for class {$inscription->classe->name}\n";
}

// Get the seances
$seances = $emploiTemps->seances()
    ->orderBy('jour_semaine')
    ->orderBy('heure_debut')
    ->get()
    ->groupBy('jour_semaine');

echo "Timetable has " . $seances->sum(function($jour) { return $jour->count(); }) . " sessions\n";

// Try rendering the view directly
echo "Trying to render the view directly...\n";
try {
    // Set the authenticated user
    Illuminate\Support\Facades\Auth::login($user);

    $view = view('etudiants.emploi-temps', compact('etudiant', 'emploiTemps', 'seances', 'inscription'));
    $content = $view->render();
    echo "View rendered successfully!\n";
    echo "View content length: " . strlen($content) . " characters\n";
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "Test completed.\n";
