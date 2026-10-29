<?php

/**
 * Script pour exécuter tous les seeders des structures administratives
 * 
 * Ce script exécute tous les seeders nécessaires pour initialiser la base de données
 * avec les données des structures administratives (UFRs, Formations, Parcours, etc.)
 */

// Vérifier si nous sommes dans un projet Laravel
$basePath = dirname(__DIR__);
if (!file_exists($basePath . '/artisan')) {
    die("Erreur : Ce script doit être exécuté à la racine d'un projet Laravel.\n");
}

// Afficher un message de bienvenue
echo "\n";
echo "=================================================================\n";
echo "      INITIALISATION DES DONNÉES ADMINISTRATIVES\n";
echo "=================================================================\n";
echo "\n";

// Liste des seeders à exécuter
$seeders = [
    'UFRsSeeder',
    'FormationsSeeder',
    'ParcoursSeeder',
    'UniteEnseignementSeeder',
    'ElementConstitutifSeeder',
    'ClassroomSeeder',
    'CourseSessionSeeder',
    'EvaluationSeeder',
    'DocumentSeeder'
];

echo "Les seeders suivants seront exécutés :\n";
foreach ($seeders as $seeder) {
    echo "- $seeder\n";
}
echo "\n";

// Demander confirmation
echo "Voulez-vous continuer ? (o/n) : ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) !== 'o' && strtolower($line) !== 'oui') {
    echo "Opération annulée.\n";
    exit;
}

// Exécuter chaque seeder
foreach ($seeders as $seeder) {
    echo "\nExécution du seeder $seeder...\n";
    system('php ' . $basePath . '/artisan db:seed --class=' . $seeder . ' --force');
    echo "Seeder $seeder exécuté avec succès.\n";
}

echo "\n";
echo "=================================================================\n";
echo "      INITIALISATION TERMINÉE AVEC SUCCÈS\n";
echo "=================================================================\n";
echo "\n";

echo "Les données des structures administratives ont été initialisées avec succès.\n";
echo "Vous pouvez maintenant accéder à l'application pour explorer les données.\n\n"; 