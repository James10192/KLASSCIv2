<?php

/**
 * Script pour exécuter les migrations et les seeders
 * 
 * Ce script va exécuter toutes les migrations sans supprimer les tables existantes,
 * puis remplir la base de données avec les données de test
 */

// Chemin vers l'application Laravel
$laravelPath = __DIR__ . '/../';

// Changer le répertoire de travail
chdir($laravelPath);

echo "=== EXÉCUTION DES MIGRATIONS ET SEEDERS ===\n";

// Exécuter toutes les migrations
echo "\n--- Exécution des migrations ---\n";
system('php artisan migrate');

// Remplir la base de données avec les données de test
echo "\n--- Remplissage de la base de données ---\n";
system('php artisan db:seed');

echo "\n=== MIGRATIONS ET SEEDERS TERMINÉS ===\n";
echo "Les migrations et les seeders ont été exécutés avec succès!\n"; 