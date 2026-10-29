<?php
// Test direct du modal pour déboguer les valeurs affichées

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST MODAL CONFIGURATION VOLUMES ===\n";
echo "URL testée: POST /esbtp/planning-general/get-matieres-configuration\n\n";

// Simuler la requête AJAX exacte
$filiereId = 2;  // BTS1 BATIMENT
$niveauId = 1;   // Première année BTS
$anneeId = 6;    // Année avec données

echo "Paramètres:\n";
echo "- filiere_id: $filiereId\n";
echo "- niveau_id: $niveauId\n";
echo "- annee_id: $anneeId\n\n";

try {
    // Simuler le contrôleur
    $controller = new \ReflectionClass('App\Http\Controllers\ESBTPPlanningGeneralController');
    
    // Récupérer les matières liées (logique du contrôleur)
    $matieresLiees = \App\Models\ESBTPMatiere::where('is_active', true)
        ->whereHas('filieres', function($query) use ($filiereId) {
            $query->where('esbtp_filieres.id', $filiereId);
        })
        ->whereHas('niveaux', function($query) use ($niveauId) {
            $query->where('esbtp_niveau_etudes.id', $niveauId);
        })
        ->orderBy('name')
        ->get();

    if ($matieresLiees->isEmpty()) {
        echo "❌ ERREUR: Aucune matière liée trouvée!\n";
        exit;
    }

    echo "✅ Matières liées trouvées: " . $matieresLiees->count() . "\n";
    foreach ($matieresLiees as $matiere) {
        echo "- ID: {$matiere->id}, Nom: {$matiere->name}\n";
    }

    // Récupérer planifications existantes (logique du contrôleur)
    $planificationsExistantes = \App\Models\ESBTPPlanificationAcademique::where('filiere_id', $filiereId)
        ->where('niveau_etude_id', $niveauId)
        ->where('annee_universitaire_id', $anneeId)
        ->with('matiere')
        ->get()
        ->keyBy('matiere_id');

    echo "\n✅ Planifications récupérées: " . $planificationsExistantes->count() . "\n";
    
    // Simuler la génération HTML
    echo "\n=== SIMULATION GÉNÉRATION HTML ===\n";
    
    $champsPreremplis = 0;
    $champsVides = 0;
    
    foreach ($matieresLiees as $matiere) {
        $planificationExistante = $planificationsExistantes->get($matiere->id);
        $volumeActuel = $planificationExistante ? $planificationExistante->volume_horaire_total : 0;
        
        echo "\n🏷️  {$matiere->name} (ID {$matiere->id}):\n";
        echo "   Volume actuel: {$volumeActuel}h\n";
        echo "   HTML généré: <input name=\"volumes[{$matiere->id}]\" value=\"{$volumeActuel}\">\n";
        
        if ($volumeActuel > 0) {
            echo "   ✅ CHAMP PRÉ-REMPLI\n";
            $champsPreremplis++;
        } else {
            echo "   ❌ CHAMP VIDE\n";
            $champsVides++;
        }
        
        // Vérifier les professeurs assignés
        if ($planificationExistante) {
            $assignedTeachers = DB::table('esbtp_planification_teachers')
                ->where('planification_id', $planificationExistante->id)
                ->pluck('teacher_id')
                ->toArray();
            
            if (!empty($assignedTeachers)) {
                echo "   👥 Professeurs assignés: " . implode(', ', $assignedTeachers) . "\n";
            }
        }
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "📊 Total matières: " . $matieresLiees->count() . "\n";
    echo "✅ Champs pré-remplis: $champsPreremplis\n";
    echo "❌ Champs vides: $champsVides\n";
    
    if ($champsPreremplis > 0) {
        echo "\n🎯 CONCLUSION: Le modal DEVRAIT afficher $champsPreremplis matières avec des volumes pré-remplis.\n";
        echo "Si vous ne les voyez pas, le problème est côté JavaScript/rendu frontend.\n";
    } else {
        echo "\n⚠️  CONCLUSION: Toutes les matières devraient être vides. Ceci correspond à votre observation.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}