<?php

/**
 * Script pour vérifier et ajouter les colonnes manquantes à la table users
 * 
 * Ce script vérifie si les colonnes 'role', 'is_active', 'profile_image' et 'phone'
 * existent dans la table users et les ajoute si elles n'existent pas.
 */

// Charger les variables d'environnement depuis le fichier .env
$dotenv = file_get_contents(__DIR__ . '/.env');
$lines = explode("\n", $dotenv);
$env = [];

foreach ($lines as $line) {
    if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
        continue;
    }
    
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim($value);
}

// Récupérer les informations de connexion à la base de données
$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? '3306';
$database = $env['DB_DATABASE'] ?? 'smart_school';
$username = $env['DB_USERNAME'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

try {
    // Connexion à la base de données avec PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Connexion à la base de données réussie.\n";
    
    // Vérifier si la table users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "La table 'users' n'existe pas. Veuillez exécuter les migrations d'abord.\n";
        exit(1);
    }
    
    // Récupérer les colonnes existantes de la table users
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Colonnes existantes dans la table users: " . implode(', ', $columns) . "\n";
    
    // Vérifier et ajouter la colonne 'role' si elle n'existe pas
    if (!in_array('role', $columns)) {
        echo "Ajout de la colonne 'role'...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'teacher', 'student', 'parent') DEFAULT 'student' AFTER password");
        echo "Colonne 'role' ajoutée avec succès.\n";
    } else {
        echo "La colonne 'role' existe déjà.\n";
    }
    
    // Vérifier et ajouter la colonne 'is_active' si elle n'existe pas
    if (!in_array('is_active', $columns)) {
        echo "Ajout de la colonne 'is_active'...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER role");
        echo "Colonne 'is_active' ajoutée avec succès.\n";
    } else {
        echo "La colonne 'is_active' existe déjà.\n";
    }
    
    // Vérifier et ajouter la colonne 'profile_image' si elle n'existe pas
    if (!in_array('profile_image', $columns)) {
        echo "Ajout de la colonne 'profile_image'...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER is_active");
        echo "Colonne 'profile_image' ajoutée avec succès.\n";
    } else {
        echo "La colonne 'profile_image' existe déjà.\n";
    }
    
    // Vérifier et ajouter la colonne 'phone' si elle n'existe pas
    if (!in_array('phone', $columns)) {
        echo "Ajout de la colonne 'phone'...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER profile_image");
        echo "Colonne 'phone' ajoutée avec succès.\n";
    } else {
        echo "La colonne 'phone' existe déjà.\n";
    }
    
    echo "Vérification terminée. Toutes les colonnes nécessaires sont présentes dans la table users.\n";
    
    // Vérifier si un administrateur existe déjà
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
    $adminExists = $stmt->rowCount() > 0;
    
    if ($adminExists) {
        echo "Un administrateur existe déjà dans la base de données.\n";
        $admin = $stmt->fetch();
        echo "ID: {$admin['id']}, Nom: {$admin['name']}, Email: {$admin['email']}\n";
    } else {
        echo "Aucun administrateur n'existe dans la base de données.\n";
        echo "Création d'un administrateur par défaut...\n";
        
        // Créer un administrateur par défaut
        $name = "Administrateur";
        $email = "admin@esbtp.edu";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active, created_at, updated_at) VALUES (?, ?, ?, 'admin', 1, NOW(), NOW())");
        $stmt->execute([$name, $email, $password]);
        
        echo "Administrateur créé avec succès.\n";
        echo "Email: admin@esbtp.edu\n";
        echo "Mot de passe: admin123\n";
        echo "N'oubliez pas de changer ce mot de passe après votre première connexion!\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
    exit(1);
} 