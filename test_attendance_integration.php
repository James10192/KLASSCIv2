<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test simulation émargement -> planification ===" . PHP_EOL;

// 1. Récupérer une planification existante
$planification = App\Models\ESBTPPlanificationAcademique::first();
if (!$planification) {
    echo "❌ Aucune planification trouvée" . PHP_EOL;
    exit(1);
}

echo "📋 Planification sélectionnée - ID: {$planification->id}" . PHP_EOL;
echo "  - Matière ID: {$planification->matiere_id}" . PHP_EOL;
echo "  - Volume horaire: {$planification->volume_horaire_total}h" . PHP_EOL;
echo "  - Heures effectuées avant: {$planification->heures_effectuees}h" . PHP_EOL;
echo "  - Enseignant principal ID: {$planification->enseignant_principal_id}" . PHP_EOL;

// 2. Récupérer une séance existante liée à cette planification
$seance = App\Models\ESBTPSeanceCours::where('matiere_id', $planification->matiere_id)->first();
if (!$seance) {
    echo "❌ Aucune séance trouvée pour cette matière" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "🎓 Séance trouvée - ID: {$seance->id}" . PHP_EOL;
echo "  - Date: {$seance->date_seance}" . PHP_EOL;
echo "  - Heure début: {$seance->heure_debut}" . PHP_EOL;
echo "  - Heure fin: {$seance->heure_fin}" . PHP_EOL;
echo "  - Enseignant ID: {$seance->teacher_id}" . PHP_EOL;

// Corriger le teacher_id si null
if (!$seance->teacher_id) {
    $seance->teacher_id = $planification->enseignant_principal_id;
    $seance->save();
    echo "  ✅ Teacher ID corrigé: {$seance->teacher_id}" . PHP_EOL;
}

// 3. Créer un émargement test
$attendance = new App\Models\ESBTPTeacherAttendance();
$attendance->teacher_id = $seance->teacher_id ?: $planification->enseignant_principal_id;
$attendance->course_id = $seance->id;
$attendance->date = $seance->date_seance;
$attendance->status = 'pending';
$attendance->attempts = 0;
$attendance->save();

echo PHP_EOL . "✅ Émargement créé - ID: {$attendance->id}" . PHP_EOL;

// 4. Simuler la validation de l'émargement
echo PHP_EOL . "🔄 Simulation validation émargement..." . PHP_EOL;
$attendance->markAsValidated();

echo "✅ Émargement validé !" . PHP_EOL;

// 5. Vérifier que les heures ont été mises à jour
$planification->refresh();
echo PHP_EOL . "📊 Résultats après validation:" . PHP_EOL;
echo "  - Heures effectuées après: {$planification->heures_effectuees}h" . PHP_EOL;
echo "  - Dernière MAJ: {$planification->derniere_mise_a_jour_heures}" . PHP_EOL;

if ($planification->heures_effectuees > 0) {
    echo "🎉 SUCCESS: Le système fonctionne !" . PHP_EOL;
} else {
    echo "⚠️  ATTENTION: Les heures n'ont pas été mises à jour" . PHP_EOL;
}

// 6. Nettoyer - supprimer l'émargement test
$attendance->delete();
echo PHP_EOL . "🧹 Nettoyage: émargement test supprimé" . PHP_EOL;