<?php

// Bootstrap the Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check timetables
$timetables = App\Models\ESBTPEmploiTemps::all();
echo "Total timetables: " . $timetables->count() . "\n";

foreach ($timetables as $timetable) {
    echo "Timetable ID: " . $timetable->id . "\n";
    echo "Title: " . $timetable->titre . "\n";
    echo "Class: " . ($timetable->classe->name ?? 'Unknown') . "\n";
    echo "Is current: " . ($timetable->is_current ? 'Yes' : 'No') . "\n";
    echo "Sessions count: " . $timetable->seances()->count() . "\n";
    echo "-------------------\n";
}

// Check sessions
$sessions = App\Models\ESBTPSeanceCours::all();
echo "Total sessions: " . $sessions->count() . "\n";

if ($sessions->count() > 0) {
    echo "Sample sessions:\n";
    foreach ($sessions->take(5) as $session) {
        echo "Session ID: " . $session->id . "\n";
        echo "Timetable ID: " . $session->emploi_temps_id . "\n";
        echo "Day: " . $session->jour_semaine . " (" . $session->getJourSemaineTexteAttribute() . ")\n";
        echo "Time: " . $session->heure_debut . " - " . $session->heure_fin . "\n";
        echo "Subject: " . ($session->matiere->name ?? 'Unknown') . "\n";
        echo "-------------------\n";
    }
}

echo "Check completed.\n";
