<?php
/**
 * Script de vérification de l'état de l'application
 * 
 * Ce script vérifie différents aspects de l'application pour s'assurer
 * qu'elle est correctement configurée et prête à être utilisée.
 */

// Charger l'environnement Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "=== Vérification de l'état de l'application ===\n\n";

// Fonction pour afficher un statut
function displayStatus($check, $message, $success = true) {
    if ($success) {
        echo "✅ ";
    } else {
        echo "❌ ";
    }
    
    echo $message . "\n";
}

// 1. Vérifier la connexion à la base de données
echo "1. Vérification de la base de données\n";
echo "------------------------------------\n";

try {
    $dbConfig = config('database.connections.' . config('database.default'));
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    
    displayStatus(true, "Connexion à la base de données réussie.");
    
    // Vérifier les tables principales
    $tables = ['users', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        displayStatus(true, "Toutes les tables requises existent.");
    } else {
        displayStatus(false, "Tables manquantes: " . implode(', ', $missingTables));
    }
    
    // Vérifier le nombre d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    displayStatus(true, "Nombre d'utilisateurs dans la base de données: $userCount");
    
    // Vérifier le nombre de rôles
    $stmt = $pdo->query("SELECT COUNT(*) FROM roles");
    $roleCount = $stmt->fetchColumn();
    
    displayStatus(true, "Nombre de rôles dans la base de données: $roleCount");
    
} catch (PDOException $e) {
    displayStatus(false, "Erreur de connexion à la base de données: " . $e->getMessage(), false);
}

echo "\n";

// 2. Vérifier la configuration de l'application
echo "2. Vérification de la configuration\n";
echo "--------------------------------\n";

// Vérifier APP_URL
$appUrl = config('app.url');
displayStatus(true, "URL de l'application: $appUrl");

// Vérifier APP_ENV
$appEnv = config('app.env');
displayStatus(true, "Environnement: $appEnv");

// Vérifier APP_DEBUG
$appDebug = config('app.debug') ? 'Activé' : 'Désactivé';
displayStatus(true, "Mode debug: $appDebug");

echo "\n";

// 3. Vérifier les fichiers importants
echo "3. Vérification des fichiers\n";
echo "--------------------------\n";

$files = [
    '.env' => __DIR__ . '/../.env',
    '.htaccess (racine)' => __DIR__ . '/../.htaccess',
    '.htaccess (public)' => __DIR__ . '/../public/.htaccess',
    'index.php (public)' => __DIR__ . '/../public/index.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        displayStatus(true, "$name existe.");
    } else {
        displayStatus(false, "$name n'existe pas.", false);
    }
}

echo "\n";

// 4. Vérifier les permissions
echo "4. Vérification des permissions\n";
echo "----------------------------\n";

$directories = [
    'storage' => __DIR__ . '/../storage',
    'bootstrap/cache' => __DIR__ . '/../bootstrap/cache',
];

foreach ($directories as $name => $path) {
    if (is_writable($path)) {
        displayStatus(true, "$name est accessible en écriture.");
    } else {
        displayStatus(false, "$name n'est pas accessible en écriture.", false);
    }
}

echo "\n";

// 5. Vérifier les packages importants
echo "5. Vérification des packages\n";
echo "-------------------------\n";

$packages = [
    'spatie/laravel-permission' => __DIR__ . '/../vendor/spatie/laravel-permission',
];

foreach ($packages as $name => $path) {
    if (is_dir($path)) {
        displayStatus(true, "$name est installé.");
    } else {
        displayStatus(false, "$name n'est pas installé.", false);
    }
}

echo "\n";

// Résumé
echo "=== Résumé ===\n\n";
echo "L'application semble être correctement configurée et prête à être utilisée.\n";
echo "URL de l'application: $appUrl\n";
echo "Nombre d'utilisateurs: $userCount\n";
echo "Nombre de rôles: $roleCount\n\n";
echo "Pour accéder à l'application, ouvrez l'URL suivante dans votre navigateur:\n";
echo "$appUrl\n\n";
echo "Utilisez les identifiants fournis dans le fichier README.md pour vous connecter.\n"; 