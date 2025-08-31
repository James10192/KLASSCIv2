<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Récupérer la planification
$planification = App\Models\ESBTPPlanificationAcademique::first();
echo "Planification ID: {$planification->id}, Matière ID: {$planification->matiere_id}" . PHP_EOL;

// Récupérer une classe pour cette planification
$classe = App\Models\ESBTPClasse::where('filiere_id', $planification->filiere_id)
    ->where('niveau_etude_id', $planification->niveau_etude_id)
    ->first();
    
if (!$classe) {
    echo "Aucune classe trouvée pour cette planification" . PHP_EOL;
    exit(1);
}

echo "Classe trouvée - ID: {$classe->id}, Nom: {$classe->name}" . PHP_EOL;

// Récupérer un emploi du temps pour cette classe
$emploiTemps = App\Models\ESBTPEmploiTemps::where('classe_id', $classe->id)->first();
if (!$emploiTemps) {
    echo "Aucun emploi du temps trouvé pour cette classe" . PHP_EOL;
    exit(1);
}

echo "Emploi du temps trouvé - ID: {$emploiTemps->id}" . PHP_EOL;

// Créer une séance de cours de test
$seance = new App\Models\ESBTPSeanceCours();
$seance->emploi_temps_id = $emploiTemps->id;
$seance->matiere_id = $planification->matiere_id;
$seance->classe_id = $classe->id;
$seance->teacher_id = $planification->enseignant_principal_id;
$seance->annee_universitaire_id = $planification->annee_universitaire_id;
$seance->date_seance = now()->toDateString();
$seance->jour = 1; // Lundi
$seance->heure_debut = '09:00:00';
$seance->heure_fin = '11:00:00';
$seance->salle = 'Salle Test';
$seance->type = 'course'; // Utiliser la constante TYPE_COURSE
$seance->save();

echo "✅ Séance créée - ID: {$seance->id}" . PHP_EOL;
echo "  - Durée: 2 heures (09:00-11:00)" . PHP_EOL;