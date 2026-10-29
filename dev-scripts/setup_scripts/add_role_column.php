<?php

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
try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    
    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ajouter la colonne role à la table users
    $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL DEFAULT 'student' AFTER password");
    echo "Colonne 'role' ajoutée à la table users.\n";
    
    // Ajouter la colonne is_active à la table users
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    echo "Colonne 'is_active' ajoutée à la table users.\n";
    
    // Ajouter la colonne profile_image à la table users
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER is_active");
    echo "Colonne 'profile_image' ajoutée à la table users.\n";
    
    // Ajouter la colonne phone à la table users
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER profile_image");
    echo "Colonne 'phone' ajoutée à la table users.\n";
    
    // Vérifier la structure mise à jour
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nStructure mise à jour de la table users:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la modification de la table: " . $e->getMessage() . "\n");
} 