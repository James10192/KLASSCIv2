<?php
/**
 * Script pour générer toutes les images thématiques de KLASSCI
 */

echo "=== KLASSCI - Génération des Ressources Visuelles ===\n\n";

// Définir le répertoire racine
$rootDir = __DIR__ . '/..';
$scriptsDir = __DIR__;

// Créer les répertoires nécessaires
$directories = [
    $rootDir . '/images',
    $rootDir . '/icons',
    $rootDir . '/placeholders'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Répertoire créé: " . basename($dir) . "\n";
    }
}

echo "\n1. Génération des images thématiques\n";
echo "-------------------------------------\n";

// Forcer la régénération des images thématiques
$_GET['force'] = true;

// Inclure le script de génération d'images thématiques
include_once($scriptsDir . '/generate-thematic-images.php');

echo "\n=== Terminé ===\n";
echo "Toutes les ressources visuelles ont été générées avec succès.\n\n"; 