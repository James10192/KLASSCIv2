<?php

/**
 * Script de test pour vérifier que les classes s'affichent correctement dans les exports
 *
 * Usage: php test-export-classe.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 TEST - Affichage des classes dans les exports\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Vérifier les relations
echo "Test 1: Vérification des relations\n";
echo str_repeat("-", 60) . "\n";

$paiements = \App\Models\ESBTPPaiement::with(['etudiant', 'inscription.classe'])
    ->limit(5)
    ->get();

echo "Nombre de paiements testés: " . $paiements->count() . "\n\n";

foreach ($paiements as $index => $paiement) {
    $etudiant = $paiement->etudiant;
    $inscription = $paiement->inscription;
    $classe = $inscription ? $inscription->classe : null;

    echo "Paiement #" . ($index + 1) . ":\n";
    echo "  - ID: " . $paiement->id . "\n";
    echo "  - Étudiant: " . ($etudiant ? $etudiant->nom . " " . $etudiant->prenoms : "N/A") . "\n";
    echo "  - Inscription ID: " . ($inscription ? $inscription->id : "N/A") . "\n";
    echo "  - Classe ID: " . ($classe ? $classe->id : "N/A") . "\n";
    echo "  - Classe NAME: " . ($classe ? $classe->name : "N/A") . "\n";

    if (!$classe) {
        echo "  ⚠️  ATTENTION: Classe non trouvée!\n";
    } elseif (!$classe->name) {
        echo "  ⚠️  ATTENTION: Classe sans nom!\n";
    } else {
        echo "  ✅ OK\n";
    }
    echo "\n";
}

// Test 2: Simuler la méthode map() de PaiementsExport
echo "\nTest 2: Simulation de l'export Excel/CSV\n";
echo str_repeat("-", 60) . "\n";

$paiement = $paiements->first();
$etudiant = $paiement->etudiant;
$inscription = $paiement->inscription;

$exportData = [
    'Matricule' => $etudiant ? $etudiant->matricule : 'N/A',
    'Nom' => $etudiant ? $etudiant->nom : '',
    'Prénoms' => $etudiant ? $etudiant->prenoms : '',
    'Classe' => $inscription && $inscription->classe ? $inscription->classe->name : 'N/A',
    'Filière' => $inscription && $inscription->filiere ? $inscription->filiere->name : 'N/A',
    'Niveau' => $inscription && $inscription->niveauEtude ? $inscription->niveauEtude->name : 'N/A',
];

echo "Données qui seraient exportées:\n";
foreach ($exportData as $key => $value) {
    echo "  - $key: $value\n";
}

// Test 3: Vérifier les stats
echo "\n\nTest 3: Statistiques sur les paiements avec/sans classe\n";
echo str_repeat("-", 60) . "\n";

$totalPaiements = \App\Models\ESBTPPaiement::count();
$paiementsAvecInscription = \App\Models\ESBTPPaiement::whereNotNull('inscription_id')->count();
$paiementsAvecClasse = \App\Models\ESBTPPaiement::whereHas('inscription.classe')->count();

echo "Total paiements: $totalPaiements\n";
echo "Paiements avec inscription: $paiementsAvecInscription\n";
echo "Paiements avec classe: $paiementsAvecClasse\n";
echo "Paiements SANS classe: " . ($paiementsAvecInscription - $paiementsAvecClasse) . "\n";

if ($paiementsAvecInscription - $paiementsAvecClasse > 0) {
    echo "\n⚠️  ATTENTION: Certains paiements ont une inscription mais pas de classe!\n";
}

echo "\n✅ Tests terminés!\n";
echo "\nSi tous les tests montrent 'OK' ou les classes s'affichent correctement,\n";
echo "alors les exports PDF/Excel/CSV devraient maintenant afficher les classes.\n";
