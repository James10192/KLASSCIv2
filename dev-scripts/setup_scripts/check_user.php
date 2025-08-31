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
    
    // Récupérer l'utilisateur avec l'ID 9
    $stmt = $pdo->query("SELECT * FROM users WHERE id = 9");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Utilisateur trouvé:\n";
        echo "ID: {$user['id']}\n";
        echo "Nom: {$user['name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Rôle: {$user['role']}\n";
        echo "Actif: " . ($user['is_active'] ? 'Oui' : 'Non') . "\n";
    } else {
        echo "Aucun utilisateur trouvé avec l'ID 9.\n";
    }
    
    // Récupérer tous les utilisateurs
    $stmt = $pdo->query("SELECT id, name, email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nListe de tous les utilisateurs:\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Nom: {$user['name']}, Email: {$user['email']}, Rôle: {$user['role']}\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la connexion à la base de données: " . $e->getMessage() . "\n");
} 