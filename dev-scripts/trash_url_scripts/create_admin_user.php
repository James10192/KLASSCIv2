<?php
/**
 * Script pour créer un utilisateur administrateur dans Smart School
 * 
 * Ce script permet de créer un utilisateur administrateur dans la base de données
 * sans passer par l'interface web d'installation.
 * 
 * @author Claude 3.7 Sonnet
 * @version 1.0
 */

// Charger les variables d'environnement depuis le fichier .env
$envFilePath = __DIR__ . '/../.env';
$envVars = [];

if (file_exists($envFilePath)) {
    $envContent = file_get_contents($envFilePath);
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $envVars[$key] = $value;
    }
} else {
    echo "❌ Erreur : Le fichier .env n'existe pas\n";
    exit(1);
}

// Configuration de la base de données
$dbConnection = $envVars['DB_CONNECTION'] ?? 'mysql';
$dbHost = $envVars['DB_HOST'] ?? '127.0.0.1';
$dbPort = $envVars['DB_PORT'] ?? '3306';
$dbName = $envVars['DB_DATABASE'] ?? '';
$dbUsername = $envVars['DB_USERNAME'] ?? '';
$dbPassword = $envVars['DB_PASSWORD'] ?? '';

// Vérifier si les informations de la base de données sont disponibles
if (empty($dbName) || empty($dbUsername)) {
    echo "❌ Erreur : Informations de base de données manquantes dans le fichier .env\n";
    exit(1);
}

echo "=================================================================\n";
echo "      CRÉATION D'UN UTILISATEUR ADMINISTRATEUR                   \n";
echo "=================================================================\n\n";

// Demander les informations de l'administrateur
echo "Veuillez fournir les informations pour le nouvel administrateur :\n\n";

// Fonction pour lire l'entrée utilisateur
function readInput($prompt, $default = '') {
    echo $prompt . ($default ? " [$default]" : "") . ": ";
    $input = trim(fgets(STDIN));
    return empty($input) && $default !== '' ? $default : $input;
}

// Collecter les informations
$name = readInput("Nom complet");
$email = readInput("Adresse email");
$password = readInput("Mot de passe (min. 8 caractères)");

// Valider les entrées
if (empty($name) || empty($email) || empty($password)) {
    echo "❌ Erreur : Toutes les informations sont requises\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "❌ Erreur : Le mot de passe doit contenir au moins 8 caractères\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Erreur : L'adresse email n'est pas valide\n";
    exit(1);
}

// Se connecter à la base de données
try {
    $dsn = "{$dbConnection}:host={$dbHost};port={$dbPort};dbname={$dbName}";
    $pdo = new PDO($dsn, $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "\nConnexion à la base de données réussie\n";
    
    // Vérifier si la table users existe
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('users', $tables)) {
        echo "❌ Erreur : La table 'users' n'existe pas dans la base de données\n";
        echo "   Vous devez d'abord exécuter les migrations pour créer les tables nécessaires.\n";
        exit(1);
    }
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $emailExists = (bool) $stmt->fetchColumn();
    
    if ($emailExists) {
        echo "❌ Erreur : Un utilisateur avec cette adresse email existe déjà\n";
        exit(1);
    }
    
    // Vérifier la structure de la table users
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    // Vérifier si la colonne 'role' existe
    $hasRoleColumn = in_array('role', $columns);
    
    // Hacher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Préparer la requête d'insertion
    if ($hasRoleColumn) {
        $sql = "INSERT INTO users (name, email, password, role, created_at, updated_at) 
                VALUES (?, ?, ?, 'admin', NOW(), NOW())";
        $params = [$name, $email, $hashedPassword];
    } else {
        $sql = "INSERT INTO users (name, email, password, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())";
        $params = [$name, $email, $hashedPassword];
    }
    
    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $userId = $pdo->lastInsertId();
    
    echo "✅ Utilisateur administrateur créé avec succès (ID: $userId)\n";
    
    // Marquer l'application comme installée
    $installedFilePath = __DIR__ . '/../storage/app/installed';
    $storageAppDir = __DIR__ . '/../storage/app';
    
    if (!is_dir($storageAppDir)) {
        mkdir($storageAppDir, 0755, true);
    }
    
    $currentDateTime = date('Y-m-d H:i:s');
    file_put_contents($installedFilePath, $currentDateTime);
    
    echo "✅ L'application a été marquée comme installée\n";
    
    // Afficher les informations de connexion
    echo "\nInformations de connexion :\n";
    echo "- Email : $email\n";
    echo "- Mot de passe : $password\n";
    
    // Construire l'URL de connexion
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = "$protocol://$host";
    $loginUrl = "$baseUrl/smart_school_new/login";
    
    echo "\nVous pouvez maintenant vous connecter à l'application en utilisant l'URL suivante :\n";
    echo "🔗 $loginUrl\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "Pour toute assistance, consultez la documentation ou contactez le support.\n";
echo "=================================================================\n"; 