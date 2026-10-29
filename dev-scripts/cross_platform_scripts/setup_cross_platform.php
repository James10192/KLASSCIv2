<?php
/**
 * Script de configuration multi-plateforme
 * 
 * Ce script aide à configurer l'application pour qu'elle fonctionne correctement
 * sur Windows et Linux. Il vérifie l'environnement, crée la base de données si nécessaire,
 * exécute les migrations et les seeders.
 */

// Afficher un message de bienvenue
echo "\n";
echo "=================================================================\n";
echo "      Configuration multi-plateforme pour Smart School\n";
echo "=================================================================\n";
echo "\n";

// Détecter le système d'exploitation
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
echo "Système d'exploitation détecté : " . ($isWindows ? "Windows" : "Linux") . "\n\n";

// Vérifier si l'application Laravel est installée
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "❌ Les dépendances Composer ne sont pas installées.\n";
    echo "   Exécutez 'composer install' pour installer les dépendances.\n\n";
    exit(1);
} else {
    echo "✅ Les dépendances Composer sont installées.\n";
}

// Vérifier si le fichier .env existe
if (!file_exists(__DIR__ . '/../.env')) {
    echo "❌ Le fichier .env n'existe pas.\n";
    echo "   Copiez le fichier .env.example en .env et configurez-le.\n\n";
    exit(1);
} else {
    echo "✅ Le fichier .env existe.\n";
}

// Charger l'autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Afficher les informations de connexion à la base de données
echo "\nInformations de connexion à la base de données :\n";
echo "- Hôte : " . $_ENV['DB_HOST'] . "\n";
echo "- Port : " . $_ENV['DB_PORT'] . "\n";
echo "- Base de données : " . $_ENV['DB_DATABASE'] . "\n";
echo "- Utilisateur : " . $_ENV['DB_USERNAME'] . "\n";
echo "- Mot de passe : " . (empty($_ENV['DB_PASSWORD']) ? "(vide)" : "(défini)") . "\n\n";

// Tester la connexion à la base de données
try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']}";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion au serveur MySQL réussie.\n";
    
    // Vérifier si la base de données existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$_ENV['DB_DATABASE']}'");
    $dbExists = $stmt->fetchColumn();
    
    if (!$dbExists) {
        echo "❌ La base de données '{$_ENV['DB_DATABASE']}' n'existe pas.\n";
        echo "   Voulez-vous la créer ? (o/n) : ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        if (strtolower($line) === 'o' || strtolower($line) === 'y') {
            $pdo->exec("CREATE DATABASE `{$_ENV['DB_DATABASE']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✅ Base de données '{$_ENV['DB_DATABASE']}' créée avec succès.\n";
        } else {
            echo "❌ Création de la base de données annulée.\n";
            exit(1);
        }
    } else {
        echo "✅ La base de données '{$_ENV['DB_DATABASE']}' existe.\n";
    }
} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
    exit(1);
}

// Exécuter les migrations et les seeders
echo "\nVoulez-vous exécuter les migrations et les seeders ? (o/n) : ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) === 'o' || strtolower($line) === 'y') {
    // Exécuter les commandes Artisan
    echo "\nExécution des migrations...\n";
    system(($isWindows ? 'php ' : 'php ') . __DIR__ . '/../artisan migrate --force');
    
    echo "\nExécution des seeders...\n";
    system(($isWindows ? 'php ' : 'php ') . __DIR__ . '/../artisan db:seed --force');
    
    echo "\n✅ Migrations et seeders exécutés avec succès.\n";
} else {
    echo "❌ Exécution des migrations et seeders annulée.\n";
}

// Afficher un message de fin
echo "\n";
echo "=================================================================\n";
echo "      Configuration terminée !\n";
echo "=================================================================\n";
echo "\n";
echo "Vous pouvez maintenant démarrer l'application avec :\n";
echo ($isWindows ? "php artisan serve" : "php artisan serve") . "\n\n";
echo "Comptes créés (si les seeders ont été exécutés) :\n";
echo "- Super Admin : superadmin@example.com / password\n";
echo "- Admin : admin@example.com / password\n";
echo "- Enseignant : teacher@example.com / password\n";
echo "- Parent : parent@example.com / password\n";
echo "- Étudiant : student@example.com / password\n";
echo "\n"; 