<?php
// Test final du comptage pour comprendre le 4/4 vs 4/6

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST COMPTAGE FINAL ===\n";

$filiereId = 2;  // BTS1 BATIMENT
$niveauId = 1;   // Première année BTS

// Tester pour différentes années
$annees = [6, 5, 4, null]; // null = toutes années

foreach ($annees as $anneeId) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "ANNÉE ID: " . ($anneeId ?? 'TOUTES') . "\n";
    echo str_repeat("=", 50) . "\n";
    
    // Récupérer les planifications comme dans le contrôleur
    $planifications = \App\Models\ESBTPPlanificationAcademique::where('filiere_id', $filiereId)
        ->where('niveau_etude_id', $niveauId);
        
    if ($anneeId) {
        $planifications->where('annee_universitaire_id', $anneeId);
    }
    
    $planifications = $planifications->with('matiere')->get();
    
    // Filtrer les planifications valides (comme dans le contrôleur)
    $planificationsValides = $planifications->filter(function($planification) use ($filiereId, $niveauId) {
        if (!$planification->matiere) {
            return false;
        }
        
        return \App\Models\ESBTPMatiere::where('id', $planification->matiere->id)
            ->where('is_active', true)
            ->whereHas('filieres', function($query) use ($filiereId) {
                $query->where('esbtp_filieres.id', $filiereId);
            })
            ->whereHas('niveaux', function($query) use ($niveauId) {
                $query->where('esbtp_niveau_etudes.id', $niveauId);
            })
            ->exists();
    });
    
    // Compter les matières liées à cette combinaison (comme dans le contrôleur)
    $matieresLieesCount = \App\Models\ESBTPMatiere::where('is_active', true)
        ->whereHas('filieres', function($query) use ($filiereId) {
            $query->where('esbtp_filieres.id', $filiereId);
        })
        ->whereHas('niveaux', function($query) use ($niveauId) {
            $query->where('esbtp_niveau_etudes.id', $niveauId);
        })
        ->count();
    
    $totalMatieres = $matieresLieesCount;
    $matieresConfigurees = $planificationsValides->where('volume_horaire_total', '>', 0)->count();
    $totalHeures = $planificationsValides->sum('volume_horaire_total');
    
    echo "📊 Statistiques:\n";
    echo "   - Total matières liées: $totalMatieres\n";
    echo "   - Matières configurées: $matieresConfigurees\n";
    echo "   - Total heures: $totalHeures" . "h\n";
    echo "   - Affichage carte: $matieresConfigurees/$totalMatieres\n";
    
    echo "\n📋 Détail des planifications:\n";
    foreach ($planificationsValides as $planif) {
        $matiereName = $planif->matiere ? $planif->matiere->name : 'INCONNUE';
        echo "   - {$matiereName}: {$planif->volume_horaire_total}h\n";
    }
    
    if ($matieresConfigurees == $totalMatieres) {
        echo "\n✅ Toutes les matières sont configurées!\n";
    } else {
        echo "\n⚠️  " . ($totalMatieres - $matieresConfigurees) . " matière(s) non configurée(s)\n";
    }
}

echo "\n🎯 CONCLUSION:\n";
echo "   Si vous voyez 4/4 au lieu de 4/6, c'est probablement parce que\n";
echo "   l'année sélectionnée dans l'interface ne correspond pas à l'année 6.\n";
echo "   Ou alors il y a des matières qui ne sont plus liées à cette combinaison.\n";