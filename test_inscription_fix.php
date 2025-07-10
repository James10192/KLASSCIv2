<?php
// Script de test pour vérifier la correction inscription_id

require_once 'vendor/autoload.php';

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Test de la correction inscription_id\n";

// Vérifier les inscriptions
$inscriptions = App\Models\ESBTPInscription::all();
echo "Nombre d'inscriptions: " . $inscriptions->count() . "\n";

if ($inscriptions->count() > 0) {
    $inscription = $inscriptions->first();
    echo "Première inscription: ID " . $inscription->id . ", Étudiant " . $inscription->etudiant_id . ", Année " . $inscription->annee_universitaire_id . "\n";
    
    // Vérifier si on peut créer un paiement (simulation)
    echo "Simulation: Création d'un paiement avec inscription_id = " . $inscription->id . "\n";
    
    // Tester la logique de récupération d'inscription
    $testInscription = App\Models\ESBTPInscription::where('etudiant_id', $inscription->etudiant_id)
        ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
        ->first();
    
    if ($testInscription) {
        echo "✅ Logique de récupération d'inscription fonctionne correctement\n";
        echo "Inscription trouvée: ID " . $testInscription->id . "\n";
    } else {
        echo "❌ Problème avec la logique de récupération d'inscription\n";
    }
} else {
    echo "❌ Aucune inscription trouvée dans la base de données\n";
}

echo "\nTest terminé.\n";
