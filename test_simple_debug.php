<?php
// Test simple pour identifier le problème exact

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSTIC SIMPLE ===\n";

$filiereId = 2;  // BTS1 BATIMENT
$niveauId = 1;   // Première année BTS
$anneeId = 6;    // Année avec données

// Test 1: Planifications qui existent
$planificationsExistantes = \App\Models\ESBTPPlanificationAcademique::where('filiere_id', $filiereId)
    ->where('niveau_etude_id', $niveauId)
    ->where('annee_universitaire_id', $anneeId)
    ->with('matiere')
    ->get();

echo "✅ Planifications trouvées pour cette combinaison: " . $planificationsExistantes->count() . "\n";
foreach ($planificationsExistantes as $planif) {
    $matiereName = $planif->matiere ? $planif->matiere->name : 'MATIÈRE SUPPRIMÉE';
    echo "  - Matière: {$matiereName} (ID {$planif->matiere_id}) = {$planif->volume_horaire_total}h\n";
}

// Test 2: Matières liées à cette combinaison
$matieresLiees = \App\Models\ESBTPMatiere::where('is_active', true)
    ->whereHas('filieres', function($query) use ($filiereId) {
        $query->where('esbtp_filieres.id', $filiereId);
    })
    ->whereHas('niveaux', function($query) use ($niveauId) {
        $query->where('esbtp_niveau_etudes.id', $niveauId);
    })
    ->orderBy('name')
    ->get();

echo "\n✅ Matières liées à cette combinaison: " . $matieresLiees->count() . "\n";
foreach ($matieresLiees as $matiere) {
    echo "  - {$matiere->name} (ID {$matiere->id})\n";
}

// Test 3: Simuler le keyBy du contrôleur
$planificationsKeyBy = $planificationsExistantes->keyBy('matiere_id');
echo "\n=== SIMULATION DU PROBLÈME ===\n";

foreach ($matieresLiees as $matiere) {
    $planificationExistante = $planificationsKeyBy->get($matiere->id);
    $volumeActuel = $planificationExistante ? $planificationExistante->volume_horaire_total : 0;
    
    echo "📋 {$matiere->name} (ID {$matiere->id}):\n";
    if ($planificationExistante) {
        echo "   ✅ Planification trouvée: {$volumeActuel}h\n";
        echo "   📊 Input HTML: <input name=\"volumes[{$matiere->id}]\" value=\"{$volumeActuel}\">\n";
    } else {
        echo "   ❌ Aucune planification trouvée\n";
        echo "   📊 Input HTML: <input name=\"volumes[{$matiere->id}]\" value=\"0\">\n";
    }
    echo "\n";
}

echo "🎯 CONCLUSION: Si vous voyez des champs à 0h, c'est que les planifications\n";
echo "   ne correspondent pas exactement aux matières liées à cette combinaison.\n";
echo "   Soit les planifications ont des matiere_id différents, soit l'annee_id\n";
echo "   ne correspond pas.\n";