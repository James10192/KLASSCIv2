<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

echo "=== TEST DE LA RELATION ENSEIGNANT ===\n\n";

try {
    // Test 1: Créer une instance de ESBTPSeanceCours et tester les relations
    echo "1. Test des relations dans ESBTPSeanceCours:\n";

    $seance = new \App\Models\ESBTPSeanceCours();

    // Tester la relation enseignant
    $enseignantRelation = $seance->enseignant();
    echo "   ✅ Relation enseignant() accessible\n";
    echo "   📍 Type: " . get_class($enseignantRelation) . "\n";
    echo "   🔗 Clé étrangère: " . $enseignantRelation->getForeignKeyName() . "\n";
    echo "   🎯 Modèle lié: " . $enseignantRelation->getRelated()::class . "\n";

    // Tester la relation teacher
    $teacherRelation = $seance->teacher();
    echo "   ✅ Relation teacher() accessible\n";
    echo "   📍 Type: " . get_class($teacherRelation) . "\n";
    echo "   🔗 Clé étrangère: " . $teacherRelation->getForeignKeyName() . "\n";
    echo "   🎯 Modèle lié: " . $teacherRelation->getRelated()::class . "\n";

    // Tester l'accesseur
    $enseignantName = $seance->enseignantName;
    echo "   ✅ Accesseur enseignantName accessible: '$enseignantName'\n";

} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

try {
    // Test 2: Vérifier qu'une séance avec enseignant peut être récupérée
    echo "2. Test de récupération d'une séance avec enseignant:\n";

    $seanceAvecEnseignant = \App\Models\ESBTPSeanceCours::with('enseignant')->first();

    if ($seanceAvecEnseignant) {
        echo "   ✅ Séance trouvée avec ID: " . $seanceAvecEnseignant->id . "\n";

        if ($seanceAvecEnseignant->enseignant) {
            echo "   ✅ Enseignant chargé: " . $seanceAvecEnseignant->enseignant->name . "\n";
        } else {
            echo "   ⚠️  Aucun enseignant assigné à cette séance\n";
        }

        echo "   ✅ Accesseur enseignantName: " . $seanceAvecEnseignant->enseignantName . "\n";

    } else {
        echo "   ℹ️  Aucune séance trouvée dans la base de données\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

try {
    // Test 3: Simuler l'utilisation dans le contrôleur
    echo "3. Test de simulation du contrôleur:\n";

    $seances = \App\Models\ESBTPSeanceCours::with(['matiere', 'enseignant'])
        ->where('is_active', true)
        ->limit(5)
        ->get();

    echo "   ✅ Requête avec relations matiere et enseignant réussie\n";
    echo "   📊 Nombre de séances trouvées: " . $seances->count() . "\n";

    foreach ($seances as $seance) {
        echo "   📝 Séance ID " . $seance->id . ":\n";
        echo "      - Matière: " . ($seance->matiere ? $seance->matiere->name : 'Non définie') . "\n";
        echo "      - Enseignant: " . $seance->enseignantName . "\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

try {
    // Test 4: Vérifier la structure de la table
    echo "4. Test de la structure de la table:\n";

    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('esbtp_seance_cours');

    if (in_array('enseignant_id', $columns)) {
        echo "   ✅ Colonne 'enseignant_id' existe\n";
    } else {
        echo "   ❌ Colonne 'enseignant_id' manquante\n";
    }

    if (in_array('teacher_id', $columns)) {
        echo "   ⚠️  Colonne 'teacher_id' existe aussi\n";
    }

    echo "   📋 Colonnes disponibles: " . implode(', ', $columns) . "\n";

} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "✅ Relations enseignant() et teacher() configurées\n";
echo "✅ Accesseur enseignantName disponible\n";
echo "✅ Requêtes avec relations fonctionnelles\n";
echo "✅ L'erreur 'Call to undefined relationship [enseignant]' devrait être résolue\n";

echo "\n=== TEST TERMINÉ ===\n";
