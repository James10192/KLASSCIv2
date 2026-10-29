<?php
// Test final - appel direct de la méthode du contrôleur

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== TEST FINAL - APPEL DIRECT CONTRÔLEUR ===\n";
echo "Simulation exacte de l'appel AJAX du modal\n\n";

// Créer une fake request
$request = new \Illuminate\Http\Request();
$request->replace([
    'filiere_id' => 2,  // BTS1 BATIMENT
    'niveau_id' => 1,   // Première année BTS
    'annee_id' => 6     // Année avec données
]);

echo "Paramètres de test:\n";
echo "- filiere_id: " . $request->input('filiere_id') . "\n";
echo "- niveau_id: " . $request->input('niveau_id') . "\n"; 
echo "- annee_id: " . $request->input('annee_id') . "\n\n";

// Simuler l'authentification (bypass middleware)
try {
    // Instancier le contrôleur avec le service requis
    $service = app(\App\Services\PlanningConfigurationService::class);
    $controller = new \App\Http\Controllers\ESBTPPlanningGeneralController($service);
    
    echo "✅ Contrôleur instancié\n";
    
    // Appeler la méthode directement
    $response = $controller->getMatieresPourConfiguration($request);
    
    echo "✅ Méthode appelée avec succès\n";
    
    // Récupérer les données de réponse
    $responseData = $response->getData();
    
    if ($responseData->success) {
        echo "✅ Réponse success = true\n";
        
        $html = $responseData->html;
        echo "✅ HTML généré: " . strlen($html) . " caractères\n\n";
        
        // Analyser le HTML pour trouver les champs pré-remplis
        echo "=== ANALYSE FINALE DU HTML ===\n";
        
        $foundPreFilled = [];
        $foundEmpty = [];
        
        // Pattern amélioré pour capturer les inputs
        if (preg_match_all('/<input[^>]*name="volumes\[(\d+)\]"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $matiereId = $match[1];
                $value = $match[2];
                
                if ($value !== '0' && $value !== '') {
                    $foundPreFilled[] = ['id' => $matiereId, 'value' => $value];
                } else {
                    $foundEmpty[] = ['id' => $matiereId, 'value' => $value];
                }
            }
            
            echo "✅ Champs PRÉ-REMPLIS trouvés: " . count($foundPreFilled) . "\n";
            foreach ($foundPreFilled as $field) {
                echo "  - Matière ID {$field['id']}: {$field['value']}h\n";
            }
            
            echo "\n❌ Champs VIDES trouvés: " . count($foundEmpty) . "\n";
            foreach ($foundEmpty as $field) {
                echo "  - Matière ID {$field['id']}: {$field['value']}h\n";
            }
            
            echo "\n🎯 CONCLUSION FINALE:\n";
            if (count($foundPreFilled) > 0) {
                echo "✅ Le modal fonctionne parfaitement !\n";
                echo "   Il y a " . count($foundPreFilled) . " matières avec volumes pré-remplis.\n";
                echo "   Si vous ne les voyez pas dans votre navigateur :\n";
                echo "   1. Vérifiez que vous regardez BTS1 BATIMENT - Première année BTS\n";
                echo "   2. Vérifiez que l'année sélectionnée contient des données\n";
                echo "   3. Videz le cache navigateur\n";
                echo "   4. Vérifiez la console JavaScript pour d'éventuelles erreurs\n";
            } else {
                echo "❌ Problème: Aucun champ pré-rempli détecté.\n";
                echo "   Cela signifie que l'année/combinaison sélectionnée n'a pas de données.\n";
            }
            
        } else {
            echo "❌ Aucun input de volume trouvé dans le HTML!\n";
            echo "\nHTML pour déboguer (1000 premiers chars):\n";
            echo substr($html, 0, 1000) . "\n";
        }
        
    } else {
        echo "❌ Erreur API: " . ($responseData->message ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}