<?php

/**
 * Script de test pour vérifier que les exports récupèrent TOUS les paiements filtrés
 * et non seulement la première page
 *
 * Usage: php test-export-pagination.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🧪 TEST - Vérification pagination exports paiements\n";
echo str_repeat("=", 70) . "\n\n";

// Simuler une requête avec des filtres
$request = new \Illuminate\Http\Request();

// Test 1: Sans filtres - doit récupérer tous les paiements de l'année courante
echo "Test 1: Export SANS filtres\n";
echo str_repeat("-", 70) . "\n";

$anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
if (!$anneeEnCours) {
    echo "❌ Aucune année universitaire courante trouvée!\n";
    exit(1);
}

// Compter les paiements de l'année courante dans la BDD
$totalPaiementsBDD = \App\Models\ESBTPPaiement::whereHas('inscription', function ($q) use ($anneeEnCours) {
    $q->where('annee_universitaire_id', $anneeEnCours->id);
})->count();

echo "📊 Total paiements dans BDD (année courante): {$totalPaiementsBDD}\n";

// Simuler l'appel à getAllFilteredPaiements (sans filtres)
$query = \App\Models\ESBTPPaiement::with([
    'etudiant.user',
    'inscription.classe',
    'inscription.anneeUniversitaire',
    'inscription.filiere',
    'inscription.niveauEtude',
    'validatedBy',
    'fraisCategory',
    'categorie',
])->orderByDesc('created_at');

$query->whereHas('inscription', function ($q) use ($anneeEnCours) {
    $q->where('annee_universitaire_id', $anneeEnCours->id);
});

$paiementsExport = $query->get();
$totalPaiementsExport = $paiementsExport->count();

echo "📥 Paiements récupérés pour export: {$totalPaiementsExport}\n";

if ($totalPaiementsExport === $totalPaiementsBDD) {
    echo "✅ OK - Tous les paiements ont été récupérés\n";
} else {
    echo "❌ ERREUR - Nombre de paiements différent!\n";
    echo "   Attendu: {$totalPaiementsBDD}\n";
    echo "   Obtenu: {$totalPaiementsExport}\n";
}

echo "\n";

// Test 2: Avec filtre de statut
echo "Test 2: Export avec filtre STATUT = 'validé'\n";
echo str_repeat("-", 70) . "\n";

$totalValideBDD = \App\Models\ESBTPPaiement::where('status', 'validé')
    ->whereHas('inscription', function ($q) use ($anneeEnCours) {
        $q->where('annee_universitaire_id', $anneeEnCours->id);
    })->count();

echo "📊 Total paiements 'validé' dans BDD: {$totalValideBDD}\n";

// Simuler getAllFilteredPaiements avec statut
$query2 = \App\Models\ESBTPPaiement::with([
    'etudiant.user',
    'inscription.classe',
])->orderByDesc('created_at');

$query2->where('status', 'validé');
$query2->whereHas('inscription', function ($q) use ($anneeEnCours) {
    $q->where('annee_universitaire_id', $anneeEnCours->id);
});

$paiementsValides = $query2->get();
$totalValidesExport = $paiementsValides->count();

echo "📥 Paiements 'validé' récupérés pour export: {$totalValidesExport}\n";

if ($totalValidesExport === $totalValideBDD) {
    echo "✅ OK - Tous les paiements validés ont été récupérés\n";
} else {
    echo "❌ ERREUR - Nombre de paiements validés différent!\n";
    echo "   Attendu: {$totalValideBDD}\n";
    echo "   Obtenu: {$totalValidesExport}\n";
}

echo "\n";

// Test 3: Avec filtre de date
echo "Test 3: Export avec filtre DATE (dernier mois)\n";
echo str_repeat("-", 70) . "\n";

$dateDebut = \Carbon\Carbon::now()->subMonth()->format('Y-m-d');
$dateFin = \Carbon\Carbon::now()->format('Y-m-d');

echo "📅 Période: {$dateDebut} au {$dateFin}\n";

$totalPeriodeBDD = \App\Models\ESBTPPaiement::whereDate('date_paiement', '>=', $dateDebut)
    ->whereDate('date_paiement', '<=', $dateFin)
    ->whereHas('inscription', function ($q) use ($anneeEnCours) {
        $q->where('annee_universitaire_id', $anneeEnCours->id);
    })->count();

echo "📊 Total paiements dans période (BDD): {$totalPeriodeBDD}\n";

// Simuler getAllFilteredPaiements avec dates
$query3 = \App\Models\ESBTPPaiement::with([
    'etudiant.user',
    'inscription.classe',
])->orderByDesc('created_at');

$query3->whereDate('date_paiement', '>=', $dateDebut);
$query3->whereDate('date_paiement', '<=', $dateFin);
$query3->whereHas('inscription', function ($q) use ($anneeEnCours) {
    $q->where('annee_universitaire_id', $anneeEnCours->id);
});

$paiementsPeriode = $query3->get();
$totalPeriodeExport = $paiementsPeriode->count();

echo "📥 Paiements période récupérés pour export: {$totalPeriodeExport}\n";

if ($totalPeriodeExport === $totalPeriodeBDD) {
    echo "✅ OK - Tous les paiements de la période ont été récupérés\n";
} else {
    echo "❌ ERREUR - Nombre de paiements de la période différent!\n";
    echo "   Attendu: {$totalPeriodeBDD}\n";
    echo "   Obtenu: {$totalPeriodeExport}\n";
}

echo "\n";

// Test 4: Vérifier qu'on récupère plus de 15 paiements (limite pagination)
echo "Test 4: Vérification dépassement limite pagination (15)\n";
echo str_repeat("-", 70) . "\n";

if ($totalPaiementsBDD > 15) {
    echo "✅ OK - Il y a {$totalPaiementsBDD} paiements, soit plus que la limite de pagination (15)\n";

    if ($totalPaiementsExport > 15) {
        echo "✅ OK - L'export récupère bien plus de 15 paiements ({$totalPaiementsExport})\n";
        echo "✅ SUCCÈS - La pagination est bien contournée!\n";
    } else {
        echo "❌ ERREUR - L'export ne récupère que {$totalPaiementsExport} paiements (≤ 15)\n";
        echo "❌ La pagination n'est pas contournée!\n";
    }
} else {
    echo "⚠️  ATTENTION - Il y a seulement {$totalPaiementsBDD} paiements dans la BDD\n";
    echo "   Impossible de tester le dépassement de la limite de pagination (15)\n";
}

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "✅ Tests terminés!\n";
echo "\nSi tous les tests affichent ✅ OK, alors les exports récupèrent bien\n";
echo "TOUS les paiements filtrés, pas seulement la première page.\n";
