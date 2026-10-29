<?php

/**
 * Script to check expense categories in the database
 */

// Define the application path
define('LARAVEL_START', microtime(true));

// Load application configuration
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Boot the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Run database queries
$db = $app->make('db');
$categories = $db->table('esbtp_categories_depenses')
    ->select('id', 'nom', 'code', 'description', 'est_actif')
    ->orderBy('id')
    ->get();

echo "=== Catégories de dépenses dans la base de données ===\n\n";
echo "Total: " . count($categories) . " catégories\n\n";

// Display categories
foreach ($categories as $category) {
    echo "ID: {$category->id}\n";
    echo "Nom: {$category->nom}\n";
    echo "Code: {$category->code}\n";
    echo "Description: " . substr($category->description, 0, 60) . (strlen($category->description) > 60 ? "..." : "") . "\n";
    echo "Statut: " . ($category->est_actif ? "Actif" : "Inactif") . "\n";
    echo "-----------------------------------\n";
}

echo "\nVérification terminée.\n"; 