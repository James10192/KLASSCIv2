<?php

/**
 * Script pour exécuter les migrations
 * 
 * Ce script va exécuter toutes les migrations sans supprimer les tables existantes
 */

// Chemin vers l'application Laravel
$laravelPath = __DIR__ . '/../';

// Changer le répertoire de travail
chdir($laravelPath);

echo "=== EXÉCUTION DES MIGRATIONS ===\n";

// Exécuter toutes les migrations
echo "\n--- Exécution des migrations ---\n";
system('php artisan migrate');

echo "\n=== MIGRATIONS TERMINÉES ===\n";
echo "Les migrations ont été exécutées avec succès!\n"; 