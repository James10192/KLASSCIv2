<?php

// Charger l'environnement Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Récupérer les attendances
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\User;
use App\Models\Etudiant;
use Carbon\Carbon;

// Récupérer l'étudiant spécifique (remplacer par l'ID souhaité)
$etudiantId = 113; // À modifier selon l'étudiant choisi

// Récupérer toutes les présences avec statut absent pour cet étudiant
$attendances = Attendance::where('etudiant_id', $etudiantId)
    ->whereIn('status', ['absent', 'absence'])
    ->with(['courseSession', 'etudiant.user'])
    ->get();

echo "Nombre d'absences trouvées: " . $attendances->count() . "\n\n";

// Calculer le total des heures d'absence justifiées et non justifiées
$totalHeuresJustifiees = 0;
$totalHeuresNonJustifiees = 0;

foreach ($attendances as $attendance) {
    $session = $attendance->courseSession;
    
    if ($session) {
        $debut = Carbon::parse($session->start_time);
        $fin = Carbon::parse($session->end_time);
        $duree = $fin->diffInHours($debut);
        
        $etudiant = $attendance->etudiant;
        $user = $etudiant ? $etudiant->user : null;
        
        echo "Attendance ID: " . $attendance->id . "\n";
        echo "Étudiant: " . ($user ? $user->name : 'Non défini') . "\n";
        echo "Session ID: " . $session->id . "\n";
        echo "Date: " . $session->date . "\n";
        echo "Heure début: " . $session->start_time . "\n";
        echo "Heure fin: " . $session->end_time . "\n";
        echo "Durée: " . $duree . " heures\n";
        echo "Statut: " . $attendance->status . "\n";
        echo "Justifiée: " . ($attendance->justifie ? 'Oui' : 'Non') . "\n";
        
        if ($attendance->justifie) {
            $totalHeuresJustifiees += $duree;
        } else {
            $totalHeuresNonJustifiees += $duree;
        }
        
        echo "----------------------------------\n";
    } else {
        echo "Attendance ID: " . $attendance->id . " - Session non trouvée\n";
        echo "----------------------------------\n";
    }
}

echo "\nRésumé:\n";
echo "Total heures d'absence justifiées: " . $totalHeuresJustifiees . " heures\n";
echo "Total heures d'absence non justifiées: " . $totalHeuresNonJustifiees . " heures\n";
echo "Total général: " . ($totalHeuresJustifiees + $totalHeuresNonJustifiees) . " heures\n";