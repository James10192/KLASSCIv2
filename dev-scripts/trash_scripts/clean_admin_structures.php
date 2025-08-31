<?php

/**
 * Script pour nettoyer les données des structures administratives
 * 
 * Ce script supprime toutes les données créées par les seeders des structures administratives
 * (UFRs, Formations, Parcours, etc.)
 */

// Vérifier si nous sommes dans un projet Laravel
$basePath = dirname(__DIR__);
if (!file_exists($basePath . '/artisan')) {
    die("Erreur : Ce script doit être exécuté à la racine d'un projet Laravel.\n");
}

// Afficher un message de bienvenue
echo "\n";
echo "=================================================================\n";
echo "      NETTOYAGE DES DONNÉES ADMINISTRATIVES\n";
echo "=================================================================\n";
echo "\n";

// Afficher un avertissement
echo "ATTENTION : Ce script va supprimer définitivement les données suivantes :\n";
echo "- Documents\n";
echo "- Évaluations\n";
echo "- Sessions de cours\n";
echo "- Salles de classe\n";
echo "- Éléments Constitutifs\n";
echo "- Unités d'Enseignement\n";
echo "- Parcours\n";
echo "- Formations\n";
echo "- UFRs\n\n";
echo "Cette opération est IRRÉVERSIBLE. Assurez-vous d'avoir une sauvegarde de vos données.\n\n";

// Demander confirmation
echo "Êtes-vous sûr de vouloir continuer ? (o/n) : ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) !== 'o' && strtolower($line) !== 'oui') {
    echo "Opération annulée.\n";
    exit;
}

// Connexion à la base de données
try {
    // Récupérer les informations de connexion depuis le fichier .env
    $envFile = file_get_contents($basePath . '/.env');
    preg_match('/DB_HOST=(.*)/', $envFile, $matches);
    $host = trim($matches[1] ?? 'localhost');
    
    preg_match('/DB_DATABASE=(.*)/', $envFile, $matches);
    $database = trim($matches[1] ?? 'smart_school_db');
    
    preg_match('/DB_USERNAME=(.*)/', $envFile, $matches);
    $username = trim($matches[1] ?? 'root');
    
    preg_match('/DB_PASSWORD=(.*)/', $envFile, $matches);
    $password = trim($matches[1] ?? '');
    
    // Connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n\n";
    
    // Désactiver les contraintes de clé étrangère
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Liste des tables à vider dans l'ordre inverse de création
    $tables = [
        'documents',
        'evaluation_supervisor',
        'grades',
        'evaluations',
        'course_sessions',
        'classrooms',
        'teacher_element_constitutif',
        'element_constitutifs',
        'unite_enseignement_parcours',
        'unite_enseignements',
        'parcours',
        'formations',
        'ufrs'
    ];
    
    // Vider chaque table
    foreach ($tables as $table) {
        echo "Suppression des données de la table '$table'...\n";
        $pdo->exec("TRUNCATE TABLE $table");
        echo "Table '$table' vidée avec succès.\n";
    }
    
    // Réactiver les contraintes de clé étrangère
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n";
    echo "=================================================================\n";
    echo "      NETTOYAGE TERMINÉ AVEC SUCCÈS\n";
    echo "=================================================================\n";
    echo "\n";
    
    echo "Toutes les données des structures administratives ont été supprimées avec succès.\n";
    echo "La base de données est maintenant propre.\n\n";
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
    exit;
} 