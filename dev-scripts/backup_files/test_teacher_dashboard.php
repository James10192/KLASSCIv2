<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Tester les requêtes qui utilisaient enseignant_id
use App\Models\ESBTPSeanceCours;
use App\Models\User;

echo "=== TEST DE LA REQUÊTE AVEC ENSEIGNANT (APRÈS CORRECTION) ===\n\n";

// Récupérer un enseignant pour le test
$teacherUser = User::where('role', 'teacher')->orWhere('role', 'enseignant')->first();

if (!$teacherUser) {
    echo "❌ Aucun utilisateur enseignant trouvé pour effectuer le test.\n";
    exit(1);
}

echo "✅ Utilisateur enseignant trouvé: {$teacherUser->name} (ID: {$teacherUser->id})\n";

// Tester la requête du contrôleur
try {
    $classes = ESBTPSeanceCours::where('enseignant', $teacherUser->name)
        ->get();
    
    echo "✅ Requête exécutée avec succès!\n";
    echo "Nombre de séances trouvées: " . $classes->count() . "\n\n";
    
    // Afficher quelques détails
    if ($classes->count() > 0) {
        echo "Détails de la première séance:\n";
        $seance = $classes->first();
        echo "- ID: {$seance->id}\n";
        echo "- Classe ID: {$seance->classe_id}\n";
        echo "- Matière ID: {$seance->matiere_id}\n";
        echo "- Enseignant: {$seance->enseignant}\n";
        echo "- Jour: {$seance->jour}\n";
    } else {
        echo "Aucune séance trouvée pour cet enseignant. Vous devriez ajouter des données de test.\n";
        
        // Créer une séance de test
        echo "\nCréation d'une séance de test pour cet enseignant...\n";
        
        try {
            $seance = new ESBTPSeanceCours();
            $seance->classe_id = 1; // Assurez-vous qu'une classe avec cet ID existe
            $seance->matiere_id = 1; // Assurez-vous qu'une matière avec cet ID existe
            $seance->enseignant = $teacherUser->name;
            $seance->jour = 'Lundi';
            $seance->heure_debut = '08:00:00';
            $seance->heure_fin = '10:00:00';
            $seance->salle = 'Salle 101';
            $seance->description = 'Séance de test';
            $seance->annee_universitaire_id = 1; // Assurez-vous qu'une année universitaire avec cet ID existe
            $seance->save();
            
            echo "✅ Séance de test créée avec succès! ID: {$seance->id}\n";
        } catch (\Exception $e) {
            echo "❌ Erreur lors de la création de la séance de test: {$e->getMessage()}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors de l'exécution de la requête: {$e->getMessage()}\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST TERMINÉ ===\n"; 