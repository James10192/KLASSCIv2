<?php
/**
 * Script de suppression des planifications orphelines
 * Usage: php delete_orphaned_planifications.php [--dry-run] [--backup]
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPMatiere;

// Options de la ligne de commande
$dryRun = in_array('--dry-run', $argv);
$backup = in_array('--backup', $argv);

echo "=== NETTOYAGE DES PLANIFICATIONS ORPHELINES ===" . PHP_EOL;
echo "Options: " . ($dryRun ? "DRY-RUN " : "") . ($backup ? "BACKUP " : "") . PHP_EOL;
echo str_repeat("=", 50) . PHP_EOL;

// Backup si demandé
if ($backup && !$dryRun) {
    $backupFile = 'planifications_backup_' . date('Y-m-d_H-i-s') . '.sql';
    echo "📥 Création du backup: {$backupFile}" . PHP_EOL;
    shell_exec("mysqldump -u " . env('DB_USERNAME') . " -p" . env('DB_PASSWORD') . " " . env('DB_DATABASE') . " esbtp_planifications_academiques > {$backupFile}");
    echo "✅ Backup créé avec succès" . PHP_EOL . PHP_EOL;
}

// Récupérer toutes les planifications
$allPlanifications = ESBTPPlanificationAcademique::with('matiere')->get();
$orphanedPlanifications = [];
$validPlanifications = [];
$totalOrphanedHours = 0;

echo "📊 Analyse des planifications..." . PHP_EOL;

foreach ($allPlanifications as $planif) {
    if (!$planif->matiere) {
        // Matière supprimée
        $orphanedPlanifications[] = $planif;
        $totalOrphanedHours += $planif->volume_horaire_total;
        continue;
    }

    // Vérifier si la matière est liée à cette combinaison
    $isLinked = ESBTPMatiere::where('id', $planif->matiere->id)
        ->where('is_active', true)
        ->whereHas('filieres', function($query) use ($planif) {
            $query->where('esbtp_filieres.id', $planif->filiere_id);
        })
        ->whereHas('niveaux', function($query) use ($planif) {
            $query->where('esbtp_niveau_etudes.id', $planif->niveau_etude_id);
        })
        ->exists();

    if ($isLinked) {
        $validPlanifications[] = $planif;
    } else {
        $orphanedPlanifications[] = $planif;
        $totalOrphanedHours += $planif->volume_horaire_total;
    }
}

echo "📈 Résultats de l'analyse:" . PHP_EOL;
echo "  • Total planifications: " . $allPlanifications->count() . PHP_EOL;
echo "  • Planifications valides: " . count($validPlanifications) . PHP_EOL;
echo "  • Planifications orphelines: " . count($orphanedPlanifications) . " ({$totalOrphanedHours}h)" . PHP_EOL;
echo PHP_EOL;

if (count($orphanedPlanifications) === 0) {
    echo "✅ Aucune planification orpheline trouvée !" . PHP_EOL;
    exit(0);
}

// Afficher les détails des planifications orphelines
echo "🗑️  Planifications orphelines détectées:" . PHP_EOL;
$groupedByCombo = [];
foreach ($orphanedPlanifications as $planif) {
    $comboKey = "F{$planif->filiere_id}_N{$planif->niveau_etude_id}";
    if (!isset($groupedByCombo[$comboKey])) {
        $groupedByCombo[$comboKey] = [];
    }
    $groupedByCombo[$comboKey][] = $planif;
}

foreach ($groupedByCombo as $combo => $planifs) {
    $first = $planifs[0];
    $filiere = $first->filiere->name ?? "Filière ID {$first->filiere_id}";
    $niveau = $first->niveauEtude->name ?? "Niveau ID {$first->niveau_etude_id}";
    
    echo "  📋 {$filiere} + {$niveau}:" . PHP_EOL;
    foreach ($planifs as $planif) {
        $matiereName = $planif->matiere->name ?? "MATIÈRE SUPPRIMÉE";
        echo "    - {$matiereName}: {$planif->volume_horaire_total}h (ID: {$planif->id})" . PHP_EOL;
    }
}

if ($dryRun) {
    echo PHP_EOL . "🔍 MODE DRY-RUN: Aucune suppression effectuée" . PHP_EOL;
    echo "Pour supprimer réellement, relancez sans --dry-run" . PHP_EOL;
    exit(0);
}

// Confirmation
echo PHP_EOL . "⚠️  ATTENTION: Cette action va supprimer " . count($orphanedPlanifications) . " planifications de façon DÉFINITIVE !" . PHP_EOL;
echo "Voulez-vous continuer ? (tapez 'OUI' en majuscules): ";
$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'OUI') {
    echo "❌ Suppression annulée" . PHP_EOL;
    exit(0);
}

// Suppression
echo "🗑️  Suppression en cours..." . PHP_EOL;
$deletedCount = 0;
foreach ($orphanedPlanifications as $planif) {
    try {
        $planif->delete();
        $deletedCount++;
    } catch (Exception $e) {
        echo "❌ Erreur lors de la suppression de la planification {$planif->id}: " . $e->getMessage() . PHP_EOL;
    }
}

echo "✅ Suppression terminée !" . PHP_EOL;
echo "   • {$deletedCount} planifications supprimées" . PHP_EOL;
echo "   • {$totalOrphanedHours}h de volumes fantômes éliminées" . PHP_EOL;
echo PHP_EOL . "🎯 Les statistiques des cartes sont maintenant correctes !" . PHP_EOL;
?>