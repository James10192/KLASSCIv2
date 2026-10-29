<?php
// Test d'un scénario complet : modal -> modification -> sauvegarde -> rechargement

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SCÉNARIO COMPLET DE TEST ===\n\n";

$filiereId = 2;
$niveauId = 1;
$anneeId = 6;

// ÉTAPE 1: Charger le modal
echo "📋 ÉTAPE 1: Chargement du modal\n";
echo str_repeat("-", 40) . "\n";

$request = new \Illuminate\Http\Request();
$request->replace(['filiere_id' => $filiereId, 'niveau_id' => $niveauId, 'annee_id' => $anneeId]);

$service = app(\App\Services\PlanningConfigurationService::class);
$controller = new \App\Http\Controllers\ESBTPPlanningGeneralController($service);
$response = $controller->getMatieresPourConfiguration($request);
$responseData = $response->getData();

if (!$responseData->success) {
    echo "❌ Erreur chargement modal\n";
    exit;
}

echo "✅ Modal chargé avec succès\n";

// Extraire les valeurs actuelles du modal
$currentVolumes = [];
$currentTeachers = [];

// Extraire volumes
if (preg_match_all('/name="volumes\[(\d+)\]"[^>]*value="([^"]*)"/', $responseData->html, $volumeMatches, PREG_SET_ORDER)) {
    foreach ($volumeMatches as $match) {
        $currentVolumes[$match[1]] = (int)$match[2];
    }
}

// Extraire professeurs sélectionnés
if (preg_match_all('/name="teachers\[(\d+)\]\[\]"[^>]*>(.*?)<\/select>/s', $responseData->html, $teacherMatches, PREG_SET_ORDER)) {
    foreach ($teacherMatches as $match) {
        $matiereId = $match[1];
        $selectContent = $match[2];
        
        preg_match_all('/<option[^>]*value="(\d+)"[^>]*selected/', $selectContent, $selectedMatches);
        $currentTeachers[$matiereId] = $selectedMatches[1];
    }
}

echo "État initial:\n";
echo "  Volumes: " . json_encode($currentVolumes) . "\n";
echo "  Professeurs: " . json_encode($currentTeachers) . "\n\n";

// ÉTAPE 2: Simuler des modifications utilisateur
echo "📝 ÉTAPE 2: Simulation des modifications utilisateur\n";
echo str_repeat("-", 40) . "\n";

$modifications = [
    'volumes' => [
        8 => 12,  // Résistance des Matériaux : 10h -> 12h
        9 => 15,  // Mécanique des Sols : 15h -> 15h (pas de changement)
        10 => 8,  // Topographie : 10h -> 8h
        33 => 5   // Architecture : 0h -> 5h (nouveau)
    ],
    'teachers' => [
        8 => ['2'],        // Changer pour prof 2 seulement
        9 => ['1', '2'],   // Ajouter prof 2
        10 => ['1'],       // Assigner prof 1
        33 => ['2']        // Assigner prof 2 à la nouvelle matière
    ]
];

echo "Modifications à appliquer:\n";
echo "  Volumes: " . json_encode($modifications['volumes']) . "\n";
echo "  Professeurs: " . json_encode($modifications['teachers']) . "\n\n";

// ÉTAPE 3: Simuler la sauvegarde
echo "💾 ÉTAPE 3: Sauvegarde des modifications\n";
echo str_repeat("-", 40) . "\n";

$saveRequest = new \Illuminate\Http\Request();
$saveRequest->merge([
    'filiere_id' => $filiereId,
    'niveau_id' => $niveauId,
    'annee_id' => $anneeId,
    'volumes' => $modifications['volumes'],
    'teachers' => $modifications['teachers']
]);

try {
    $saveResponse = $controller->saveVolumeConfiguration($saveRequest);
    $saveResponseData = $saveResponse->getData();
    
    if ($saveResponseData->success) {
        echo "✅ Sauvegarde réussie: " . $saveResponseData->message . "\n\n";
    } else {
        echo "❌ Erreur sauvegarde: " . $saveResponseData->message . "\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Exception sauvegarde: " . $e->getMessage() . "\n\n";
    exit;
}

// ÉTAPE 4: Recharger le modal pour vérifier persistance
echo "🔄 ÉTAPE 4: Rechargement du modal (simulation refresh)\n";
echo str_repeat("-", 40) . "\n";

$reloadRequest = new \Illuminate\Http\Request();
$reloadRequest->replace(['filiere_id' => $filiereId, 'niveau_id' => $niveauId, 'annee_id' => $anneeId]);

$reloadResponse = $controller->getMatieresPourConfiguration($reloadRequest);
$reloadResponseData = $reloadResponse->getData();

if (!$reloadResponseData->success) {
    echo "❌ Erreur rechargement modal\n";
    exit;
}

echo "✅ Modal rechargé\n";

// Extraire les nouvelles valeurs
$newVolumes = [];
$newTeachers = [];

if (preg_match_all('/name="volumes\[(\d+)\]"[^>]*value="([^"]*)"/', $reloadResponseData->html, $volumeMatches, PREG_SET_ORDER)) {
    foreach ($volumeMatches as $match) {
        $newVolumes[$match[1]] = (int)$match[2];
    }
}

if (preg_match_all('/name="teachers\[(\d+)\]\[\]"[^>]*>(.*?)<\/select>/s', $reloadResponseData->html, $teacherMatches, PREG_SET_ORDER)) {
    foreach ($teacherMatches as $match) {
        $matiereId = $match[1];
        $selectContent = $match[2];
        
        preg_match_all('/<option[^>]*value="(\d+)"[^>]*selected/', $selectContent, $selectedMatches);
        $newTeachers[$matiereId] = $selectedMatches[1];
    }
}

echo "État après sauvegarde:\n";
echo "  Volumes: " . json_encode($newVolumes) . "\n";
echo "  Professeurs: " . json_encode($newTeachers) . "\n\n";

// ÉTAPE 5: Vérification des différences
echo "🔍 ÉTAPE 5: Vérification de la persistance\n";
echo str_repeat("-", 40) . "\n";

$volumesOK = true;
$teachersOK = true;

foreach ($modifications['volumes'] as $matiereId => $expectedVolume) {
    $actualVolume = $newVolumes[$matiereId] ?? 0;
    if ($actualVolume != $expectedVolume) {
        echo "❌ Volume matière $matiereId : attendu $expectedVolume, trouvé $actualVolume\n";
        $volumesOK = false;
    }
}

foreach ($modifications['teachers'] as $matiereId => $expectedTeachers) {
    $actualTeachers = $newTeachers[$matiereId] ?? [];
    sort($expectedTeachers);
    sort($actualTeachers);
    
    if (array_diff($expectedTeachers, $actualTeachers) || array_diff($actualTeachers, $expectedTeachers)) {
        echo "❌ Teachers matière $matiereId : attendu [" . implode(',', $expectedTeachers) . "], trouvé [" . implode(',', $actualTeachers) . "]\n";
        $teachersOK = false;
    }
}

if ($volumesOK && $teachersOK) {
    echo "🎉 SUCCÈS COMPLET ! Toutes les modifications ont été sauvegardées et persistées correctement.\n";
} else {
    if (!$volumesOK) echo "⚠️  Problème avec la persistance des volumes\n";
    if (!$teachersOK) echo "⚠️  Problème avec la persistance des assignations professeurs\n";
}

echo "\n=== FIN DU SCÉNARIO COMPLET ===\n";