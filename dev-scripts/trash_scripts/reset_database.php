<?php

/**
 * Script pour réinitialiser la base de données
 * 
 * Ce script va:
 * 1. Supprimer toutes les tables existantes
 * 2. Exécuter toutes les migrations
 * 3. Remplir la base de données avec les données de test
 */

// Chemin vers l'application Laravel (chemin absolu)
$laravelPath = 'C:/wamp64/www/smart_school_new';

// Vérifier si le répertoire existe
if (!is_dir($laravelPath)) {
    echo "ERREUR: Le répertoire du projet n'existe pas: {$laravelPath}\n";
    echo "Veuillez modifier le chemin dans le script.\n";
    exit(1);
}

// Changer le répertoire de travail
chdir($laravelPath);

// Vérifier si le fichier artisan existe
if (!file_exists($laravelPath . '/artisan')) {
    echo "ERREUR: Le fichier artisan n'a pas été trouvé dans: {$laravelPath}\n";
    echo "Veuillez vérifier le chemin du projet.\n";
    exit(1);
}

echo "=== RÉINITIALISATION DE LA BASE DE DONNÉES ===\n";
echo "Répertoire du projet: {$laravelPath}\n";

// Supprimer toutes les tables
echo "\n--- Suppression de toutes les tables ---\n";
system('php artisan db:wipe');

// Exécuter toutes les migrations
echo "\n--- Exécution des migrations ---\n";
system('php artisan migrate');

// Remplir la base de données avec les données de test
echo "\n--- Remplissage de la base de données ---\n";
system('php artisan db:seed');

echo "\n=== RÉINITIALISATION TERMINÉE ===\n";
echo "La base de données a été réinitialisée avec succès!\n"; 