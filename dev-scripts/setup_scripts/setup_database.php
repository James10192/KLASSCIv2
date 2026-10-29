<?php

/**
 * Script de configuration de la base de données pour Smart School
 * 
 * Ce script exécute toutes les étapes nécessaires pour configurer la base de données:
 * 1. Création de la base de données
 * 2. Création des tables
 * 3. Création d'un utilisateur administrateur
 */

// Fonction pour exécuter un script PHP et afficher son résultat
function runScript($scriptName) {
    echo "\n========== Exécution de $scriptName ==========\n";
    
    // Exécuter le script et capturer sa sortie
    ob_start();
    include $scriptName;
    $output = ob_get_clean();
    
    echo $output;
    echo "\n========== Fin de $scriptName ==========\n";
}

// Vérifier si la base de données existe, sinon la créer
runScript('create_db.php');

// Créer les tables de base
runScript('execute_sql.php');

// Ajouter les colonnes nécessaires à la table users si elles n'existent pas
try {
    // Récupérer les informations de connexion depuis le fichier .env
    $envFile = __DIR__ . '/.env';
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $dbConfig = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'username' => 'root',
        'password' => '',
        'database' => 'smart_school_db'
    ];

    // Parcourir les lignes du fichier .env pour trouver les informations de connexion
    foreach ($lines as $line) {
        if (strpos($line, 'DB_HOST=') === 0) {
            $dbConfig['host'] = substr($line, 8);
        } elseif (strpos($line, 'DB_PORT=') === 0) {
            $dbConfig['port'] = substr($line, 8);
        } elseif (strpos($line, 'DB_USERNAME=') === 0) {
            $dbConfig['username'] = substr($line, 12);
        } elseif (strpos($line, 'DB_PASSWORD=') === 0) {
            $dbConfig['password'] = substr($line, 12);
        } elseif (strpos($line, 'DB_DATABASE=') === 0) {
            $dbConfig['database'] = substr($line, 12);
        }
    }

    // Créer la connexion à MySQL
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    
    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la colonne 'role' existe dans la table users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = $stmt->rowCount() > 0;
    
    if (!$roleExists) {
        echo "\n========== Ajout des colonnes manquantes à la table users ==========\n";
        runScript('add_role_column.php');
    } else {
        echo "\nLes colonnes nécessaires existent déjà dans la table users.\n";
    }
} catch (PDOException $e) {
    echo "\nErreur lors de la vérification des colonnes: " . $e->getMessage() . "\n";
}

// Créer un utilisateur administrateur si nécessaire
runScript('create_admin_user.php');

echo "\n========== Configuration de la base de données terminée ==========\n";
echo "Vous pouvez maintenant accéder à l'application en utilisant les identifiants administrateur.\n";
echo "URL: http://localhost:8000 ou l'URL configurée dans votre environnement\n"; 