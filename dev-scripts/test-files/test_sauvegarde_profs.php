<?php
// Test de la sauvegarde des assignations de professeurs

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST SAUVEGARDE ASSIGNATIONS PROFESSEURS ===\n\n";

// Simuler une requête de sauvegarde
$requestData = [
    'filiere_id' => 2,
    'niveau_id' => 1, 
    'annee_id' => 6,
    'volumes' => [
        8 => 10,  // Résistance des Matériaux (existant)
        9 => 15,  // Mécanique des Sols (modification)
        10 => 10, // Topographie (existant)
        11 => 0   // Construction Métallique (suppression)
    ],
    'teachers' => [
        8 => ['1', '2'], // Assigner 2 professeurs à Résistance des Matériaux
        9 => ['1'],      // Un seul professeur à Mécanique des Sols
        10 => [],        // Aucun professeur à Topographie
        11 => []         // Aucun professeur (sera supprimé anyway)
    ]
];

echo "📊 DONNÉES DE TEST:\n";
echo "Volumes: " . json_encode($requestData['volumes']) . "\n";
echo "Teachers: " . json_encode($requestData['teachers']) . "\n\n";

try {
    // Créer une fake request
    $request = new \Illuminate\Http\Request();
    $request->merge($requestData);

    echo "=== AVANT SAUVEGARDE ===\n";
    
    // État actuel des planifications
    $planificationsAvant = \App\Models\ESBTPPlanificationAcademique::where('filiere_id', 2)
        ->where('niveau_etude_id', 1)
        ->where('annee_universitaire_id', 6)
        ->with('matiere')
        ->get();
        
    echo "Planifications existantes: " . $planificationsAvant->count() . "\n";
    foreach ($planificationsAvant as $planif) {
        $assignedTeachers = DB::table('esbtp_planification_teachers')
            ->where('planification_id', $planif->id)
            ->pluck('teacher_id')
            ->toArray();
        
        $matiereName = $planif->matiere ? $planif->matiere->name : 'INCONNUE';
        echo "  - {$matiereName} (ID {$planif->matiere_id}): {$planif->volume_horaire_total}h, Profs: [" . implode(',', $assignedTeachers) . "]\n";
    }
    
    echo "\n=== SIMULATION SAUVEGARDE ===\n";
    
    // Appeler le contrôleur
    $service = app(\App\Services\PlanningConfigurationService::class);
    $controller = new \App\Http\Controllers\ESBTPPlanningGeneralController($service);
    
    $response = $controller->saveVolumeConfiguration($request);
    $responseData = $response->getData();
    
    echo "Réponse success: " . ($responseData->success ? 'true' : 'false') . "\n";
    echo "Message: " . $responseData->message . "\n";
    
    if (!$responseData->success) {
        echo "❌ ERREUR lors de la sauvegarde!\n";
        exit;
    }
    
    echo "\n=== APRÈS SAUVEGARDE ===\n";
    
    // Vérifier l'état après sauvegarde
    $planificationsApres = \App\Models\ESBTPPlanificationAcademique::where('filiere_id', 2)
        ->where('niveau_etude_id', 1)
        ->where('annee_universitaire_id', 6)
        ->with('matiere')
        ->get();
        
    echo "Planifications après: " . $planificationsApres->count() . "\n";
    foreach ($planificationsApres as $planif) {
        $assignedTeachers = DB::table('esbtp_planification_teachers')
            ->where('planification_id', $planif->id)
            ->pluck('teacher_id')
            ->toArray();
        
        $matiereName = $planif->matiere ? $planif->matiere->name : 'INCONNUE';
        echo "  - {$matiereName} (ID {$planif->matiere_id}): {$planif->volume_horaire_total}h, Profs: [" . implode(',', $assignedTeachers) . "]\n";
    }
    
    echo "\n=== VÉRIFICATION ATTENDUE VS RÉELLE ===\n";
    
    foreach ($requestData['volumes'] as $matiereId => $volume) {
        $matiere = \App\Models\ESBTPMatiere::find($matiereId);
        $matiereName = $matiere ? $matiere->name : "ID $matiereId";
        
        echo "\n🏷️  {$matiereName}:\n";
        echo "   Volume attendu: {$volume}h\n";
        echo "   Professeurs attendus: [" . implode(',', $requestData['teachers'][$matiereId] ?? []) . "]\n";
        
        $planificationReelle = $planificationsApres->where('matiere_id', $matiereId)->first();
        
        if ($volume > 0) {
            if ($planificationReelle) {
                echo "   ✅ Volume réel: {$planificationReelle->volume_horaire_total}h\n";
                
                $profsReels = DB::table('esbtp_planification_teachers')
                    ->where('planification_id', $planificationReelle->id)
                    ->pluck('teacher_id')
                    ->toArray();
                    
                echo "   👥 Professeurs réels: [" . implode(',', $profsReels) . "]\n";
                
                $attendus = $requestData['teachers'][$matiereId] ?? [];
                if (array_diff($attendus, $profsReels) || array_diff($profsReels, $attendus)) {
                    echo "   ❌ DIFFÉRENCE entre attendus et réels!\n";
                } else {
                    echo "   ✅ Professeurs corrects\n";
                }
            } else {
                echo "   ❌ Planification manquante!\n";
            }
        } else {
            if ($planificationReelle) {
                echo "   ❌ Planification devrait être supprimée!\n";
            } else {
                echo "   ✅ Planification correctement supprimée\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}