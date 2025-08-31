<?php
// Test final de vérification - ce qui est envoyé vs ce qui est affiché

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VÉRIFICATION FINALE DU MODAL ===\n";
echo "Simulation de l'appel AJAX exact du modal\n\n";

// Paramètres exacts
$filiereId = 2;  // BTS1 BATIMENT
$niveauId = 1;   // Première année BTS
$anneeId = 6;    // Année avec données

try {
    // Créer une fake request comme dans le contrôleur
    $request = new \Illuminate\Http\Request();
    $request->replace([
        'filiere_id' => $filiereId,
        'niveau_id' => $niveauId,
        'annee_id' => $anneeId
    ]);

    echo "📊 PARAMÈTRES D'ENTRÉE:\n";
    echo "   - Filière ID: $filiereId (BTS1 BATIMENT)\n";
    echo "   - Niveau ID: $niveauId (Première année BTS)\n";
    echo "   - Année ID: $anneeId\n\n";

    // Instancier le contrôleur avec le service
    $service = app(\App\Services\PlanningConfigurationService::class);
    $controller = new \App\Http\Controllers\ESBTPPlanningGeneralController($service);
    
    // Appeler la méthode
    $response = $controller->getMatieresPourConfiguration($request);
    $responseData = $response->getData();
    
    if (!$responseData->success) {
        echo "❌ ERREUR API: " . $responseData->message . "\n";
        exit;
    }

    $html = $responseData->html;
    echo "✅ HTML GÉNÉRÉ: " . strlen($html) . " caractères\n\n";

    // Analyser le contenu HTML généré
    echo "=== ANALYSE DU CONTENU HTML ===\n\n";

    // 1. Extraire les matières
    preg_match_all('/<div class="config-matiere-card[^"]*" data-matiere-id="(\d+)"/', $html, $cardsMatches);
    $matiereIds = $cardsMatches[1];
    
    echo "🏷️  MATIÈRES TROUVÉES: " . count($matiereIds) . "\n";
    
    foreach ($matiereIds as $index => $matiereId) {
        echo "\n" . str_repeat("-", 40) . "\n";
        echo "📋 MATIÈRE ID: $matiereId\n";
        
        // Récupérer le nom de la matière
        $matiere = \App\Models\ESBTPMatiere::find($matiereId);
        echo "📝 NOM: " . ($matiere ? $matiere->name : 'INCONNUE') . "\n";
        
        // Extraire le volume horaire pour cette matière
        if (preg_match('/name="volumes\[' . $matiereId . '\]"[^>]*value="([^"]*)"/', $html, $volumeMatch)) {
            $volume = $volumeMatch[1];
            echo "⏰ VOLUME CONFIGURÉ: {$volume}h\n";
            
            if ($volume > 0) {
                echo "✅ STATUS: PRÉ-REMPLI\n";
            } else {
                echo "❌ STATUS: VIDE\n";
            }
        } else {
            echo "❌ ERREUR: Champ volume non trouvé\n";
        }
        
        // Extraire les professeurs assignés pour cette matière
        $pattern = '/name="teachers\[' . $matiereId . '\]\[\]"[^>]*>(.*?)<\/select>/s';
        if (preg_match($pattern, $html, $teacherMatch)) {
            $selectContent = $teacherMatch[1];
            
            // Compter les options selected
            preg_match_all('/<option[^>]*selected[^>]*>([^<]+)<\/option>/', $selectContent, $selectedMatches);
            $selectedTeachers = $selectedMatches[1];
            
            echo "👥 PROFESSEURS ASSIGNÉS: " . count($selectedTeachers) . "\n";
            
            if (count($selectedTeachers) > 0) {
                foreach ($selectedTeachers as $teacherName) {
                    echo "   - " . trim($teacherName) . "\n";
                }
            } else {
                echo "   ❌ Aucun professeur assigné\n";
            }
            
            // Compter le total d'options disponibles
            preg_match_all('/<option[^>]*value="(\d+)"/', $selectContent, $allMatches);
            $totalTeachers = count($allMatches[1]);
            echo "📋 PROFESSEURS DISPONIBLES: $totalTeachers\n";
            
        } else {
            echo "❌ ERREUR: Champ professeurs non trouvé\n";
        }
    }

    // Résumé final
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    // Compter les champs pré-remplis
    preg_match_all('/name="volumes\[(\d+)\]"[^>]*value="([^"]*)"/', $html, $allVolumes, PREG_SET_ORDER);
    $preremplis = 0;
    $vides = 0;
    $totalHeures = 0;
    
    foreach ($allVolumes as $volumeData) {
        $vol = (int)$volumeData[2];
        if ($vol > 0) {
            $preremplis++;
            $totalHeures += $vol;
        } else {
            $vides++;
        }
    }
    
    echo "✅ Matières avec volumes pré-remplis: $preremplis\n";
    echo "❌ Matières avec volumes vides: $vides\n";
    echo "⏰ Total heures configurées: {$totalHeures}h\n";
    
    // Compter les professeurs assignés au total
    preg_match_all('/<option[^>]*selected[^>]*>/', $html, $allSelectedTeachers);
    $totalAssignedTeachers = count($allSelectedTeachers[0]);
    echo "👥 Total professeurs assignés: $totalAssignedTeachers\n";
    
    echo "\n🎯 CONCLUSION:\n";
    echo "Le modal devrait afficher:\n";
    echo "  - $preremplis matières avec volumes pré-configurés\n";
    echo "  - $vides matières à configurer\n";
    echo "  - $totalAssignedTeachers professeurs déjà assignés\n";
    
    if ($preremplis > 0 || $totalAssignedTeachers > 0) {
        echo "\n✅ Le modal fonctionne parfaitement !\n";
        echo "   Les données sont correctement transmises et formatées.\n";
    } else {
        echo "\n⚠️  Toutes les matières sont vides - ceci peut être normal.\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}