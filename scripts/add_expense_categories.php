<?php

/**
 * Script to add expense categories directly to the database
 * 
 * Usage: php scripts/add_expense_categories.php
 */

// Define the application path
define('LARAVEL_START', microtime(true));

// Get the application paths
$app = require __DIR__ . '/../bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ESBTPCategorieDepense;
use Illuminate\Support\Facades\DB;

echo "Création des catégories de dépenses pour ESBTP...\n";

// Define the expense categories to create
$categories = [
    [
        'nom' => 'Salaires et charges sociales',
        'description' => 'Dépenses liées aux salaires, primes, indemnités et charges sociales du personnel',
        'code' => 'SALAIRES',
    ],
    [
        'nom' => 'Fournitures pédagogiques',
        'description' => 'Matériel pédagogique, manuels, supports de cours, etc.',
        'code' => 'PEDAGOG',
    ],
    [
        'nom' => 'Équipements informatiques',
        'description' => 'Ordinateurs, imprimantes, projecteurs, et autres matériels informatiques',
        'code' => 'EQUIP_INFO',
    ],
    [
        'nom' => 'Maintenance et réparations',
        'description' => 'Entretien des bâtiments, réparations diverses, maintenance des équipements',
        'code' => 'MAINT',
    ],
    [
        'nom' => 'Frais administratifs',
        'description' => 'Dépenses administratives générales',
        'code' => 'ADMIN',
    ],
    [
        'nom' => 'Factures d\'eau et électricité',
        'description' => 'Factures d\'eau, d\'électricité et autres services publics',
        'code' => 'UTIL',
    ],
    [
        'nom' => 'Services de nettoyage',
        'description' => 'Services de nettoyage et d\'entretien des locaux',
        'code' => 'NETTOYAGE',
    ],
    [
        'nom' => 'Sécurité',
        'description' => 'Services de sécurité, équipements de sécurité, etc.',
        'code' => 'SECURITE',
    ],
    [
        'nom' => 'Assurances',
        'description' => 'Assurances diverses (responsabilité civile, locaux, etc.)',
        'code' => 'ASSUR',
    ],
    [
        'nom' => 'Frais de communication',
        'description' => 'Téléphone, internet, frais postaux, etc.',
        'code' => 'COMM',
    ],
    [
        'nom' => 'Matériel de bureau',
        'description' => 'Fournitures de bureau, papeterie, etc.',
        'code' => 'BUREAU',
    ],
    [
        'nom' => 'Logiciels et licences',
        'description' => 'Logiciels, licences, abonnements à des services numériques',
        'code' => 'LOGICIEL',
    ],
    [
        'nom' => 'Frais de déplacement',
        'description' => 'Transports, missions, frais de déplacement du personnel',
        'code' => 'DEPLACE',
    ],
    [
        'nom' => 'Frais de formation du personnel',
        'description' => 'Formations, séminaires, conférences pour le personnel',
        'code' => 'FORMATION',
    ],
];

// Track statistics
$created = 0;
$existing = 0;

// Start a transaction
DB::beginTransaction();

try {
    // Create each category if it doesn't already exist
    foreach ($categories as $categoryData) {
        $existingCategory = ESBTPCategorieDepense::where('code', $categoryData['code'])->first();
        
        if ($existingCategory) {
            echo "Catégorie existante: " . $categoryData['nom'] . " (Code: " . $categoryData['code'] . ")\n";
            $existing++;
        } else {
            ESBTPCategorieDepense::create([
                'nom' => $categoryData['nom'],
                'code' => $categoryData['code'],
                'description' => $categoryData['description'],
                'est_actif' => true,
            ]);
            echo "Nouvelle catégorie créée: " . $categoryData['nom'] . " (Code: " . $categoryData['code'] . ")\n";
            $created++;
        }
    }
    
    // Commit the transaction
    DB::commit();
    
    echo "\nRésumé:\n";
    echo "- {$created} nouvelles catégories créées\n";
    echo "- {$existing} catégories déjà existantes\n";
    echo "Opération terminée avec succès!\n";
    
} catch (\Exception $e) {
    // Roll back the transaction in case of error
    DB::rollBack();
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 