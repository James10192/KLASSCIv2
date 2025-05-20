<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CRÉATION D'UNE SÉANCE DE COURS POUR L'ENSEIGNANT ===\n\n";

// 1. Récupérer l'enseignant
$teacher = \App\Models\User::where('email', 'enseignant@test.com')->first();
if (!$teacher) {
    echo "❌ Enseignant introuvable.\n";
    exit(1);
}

// 2. Récupérer ou créer des données essentielles
$classe = \App\Models\ESBTPClasse::first();
$matiere = \App\Models\ESBTPMatiere::first();
$annee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

if (!$classe || !$matiere || !$annee) {
    echo "❌ Données manquantes pour créer un cours.\n";
    exit(1);
}

// 3. Créer une séance de cours
try {
    $seance = new \App\Models\ESBTPSeanceCours();
    $seance->classe_id = $classe->id;
    $seance->matiere_id = $matiere->id;
    $seance->enseignant = $teacher->name;
    $seance->jour = 1; // Lundi
    $seance->heure_debut = '08:00:00';
    $seance->heure_fin = '10:00:00';
    $seance->salle = 'Salle A101';
    $seance->description = 'Cours test';
    $seance->annee_universitaire_id = $annee->id;
    $seance->is_active = true;
    $seance->type_seance = 'cours';
    $seance->save();
    
    echo "✅ Séance de cours créée avec succès !\n";
    echo "ID: " . $seance->id . "\n";
    echo "Enseignant: " . $teacher->name . "\n";
    echo "Jour: Lundi, 08:00 - 10:00\n";
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
} 