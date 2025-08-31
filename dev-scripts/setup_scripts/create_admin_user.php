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
    
    // Vérifier s'il existe déjà un administrateur
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount > 0) {
        echo "Un administrateur existe déjà dans la base de données.\n";
        
        // Afficher les administrateurs existants
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Liste des administrateurs:\n";
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']}, Nom: {$admin['name']}, Email: {$admin['email']}\n";
        }
    } else {
        // Créer un administrateur par défaut
        $name = "Administrateur";
        $email = "admin@esbtp.edu";
        $password = password_hash("admin123", PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, 'admin', 1, NOW(), NOW(), NOW())");
        $stmt->execute([$name, $email, $password]);
        
        $adminId = $pdo->lastInsertId();
        
        echo "Administrateur créé avec succès:\n";
        echo "ID: {$adminId}\n";
        echo "Nom: {$name}\n";
        echo "Email: {$email}\n";
        echo "Mot de passe: admin123\n";
        echo "\nVeuillez changer ce mot de passe après votre première connexion.\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la création de l'administrateur: " . $e->getMessage() . "\n");
} 