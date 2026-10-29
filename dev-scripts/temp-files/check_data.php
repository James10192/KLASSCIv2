<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\DB;

echo "=== Vérification des données ESBTPAnneeUniversitaire ===\n";

// Test 1: Comptage direct en base
$countDB = DB::table('esbtp_annee_universitaires')->count();
echo "Nombre d'enregistrements en base: {$countDB}\n";

// Test 2: Comptage via Eloquent
$countEloquent = ESBTPAnneeUniversitaire::count();
echo "Nombre d'enregistrements via Eloquent: {$countEloquent}\n";

// Test 3: Récupération et vérification
echo "\n=== Récupération des données ===\n";
try {
    $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
    echo "Collection récupérée avec succès, taille: " . $annees->count() . "\n";
    
    foreach ($annees as $index => $annee) {
        if (is_null($annee)) {
            echo "PROBLÈME: Élément {$index} est NULL!\n";
        } else {
            echo "OK: Élément {$index} - ID: {$annee->id}, Name: " . ($annee->name ?? 'NULL') . ", is_current: " . ($annee->is_current ?? 'NULL') . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERREUR lors de la récupération: " . $e->getMessage() . "\n";
}

// Test 4: Vérification des données is_current
echo "\n=== Vérification is_current ===\n";
$nullCurrent = DB::table('esbtp_annee_universitaires')->whereNull('is_current')->count();
echo "Enregistrements avec is_current NULL: {$nullCurrent}\n";

$trueCurrent = DB::table('esbtp_annee_universitaires')->where('is_current', true)->count();
echo "Enregistrements avec is_current TRUE: {$trueCurrent}\n";

$falseCurrent = DB::table('esbtp_annee_universitaires')->where('is_current', false)->count();
echo "Enregistrements avec is_current FALSE: {$falseCurrent}\n";

echo "\n=== Fin de la vérification ===\n";