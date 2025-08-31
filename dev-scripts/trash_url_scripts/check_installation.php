<?php
/**
 * Script de vérification de l'état d'installation de Smart School
 * 
 * Ce script vérifie si l'application est déjà installée et fournit des informations sur l'état actuel.
 * 
 * @author Claude 3.7 Sonnet
 * @version 1.0
 */

// Chemin vers le fichier d'installation
$installedFilePath = __DIR__ . '/../storage/app/installed';

// Chemin vers le fichier .env
$envFilePath = __DIR__ . '/../.env';

echo "=================================================================\n";
echo "      VÉRIFICATION DE L'INSTALLATION SMART SCHOOL                \n";
echo "=================================================================\n\n";

// Vérifier si le fichier d'installation existe
if (file_exists($installedFilePath)) {
    $installDate = file_get_contents($installedFilePath);
    echo "✅ L'application est installée (date d'installation: $installDate)\n\n";
} else {
    echo "❌ L'application n'est pas encore installée\n\n";
    
    // Vérifier si le fichier .env existe
    if (file_exists($envFilePath)) {
        echo "✅ Le fichier .env existe\n";
        
        // Lire le fichier .env pour vérifier la configuration de la base de données
        $envContent = file_get_contents($envFilePath);
        
        // Extraire les informations de la base de données
        preg_match('/DB_CONNECTION=(.*)/', $envContent, $dbConnection);
        preg_match('/DB_HOST=(.*)/', $envContent, $dbHost);
        preg_match('/DB_PORT=(.*)/', $envContent, $dbPort);
        preg_match('/DB_DATABASE=(.*)/', $envContent, $dbName);
        preg_match('/DB_USERNAME=(.*)/', $envContent, $dbUsername);
        
        echo "\nConfiguration de la base de données :\n";
        echo "- Type de connexion : " . ($dbConnection[1] ?? 'Non configuré') . "\n";
        echo "- Hôte : " . ($dbHost[1] ?? 'Non configuré') . "\n";
        echo "- Port : " . ($dbPort[1] ?? 'Non configuré') . "\n";
        echo "- Base de données : " . ($dbName[1] ?? 'Non configuré') . "\n";
        echo "- Utilisateur : " . ($dbUsername[1] ?? 'Non configuré') . "\n";
        
        // Vérifier la connexion à la base de données
        if (isset($dbConnection[1]) && isset($dbHost[1]) && isset($dbPort[1]) && isset($dbName[1]) && isset($dbUsername[1])) {
            echo "\nTentative de connexion à la base de données...\n";
            
            try {
                // Récupérer le mot de passe de la base de données
                preg_match('/DB_PASSWORD=(.*)/', $envContent, $dbPassword);
                $password = $dbPassword[1] ?? '';
                
                // Tenter de se connecter à la base de données
                $dsn = "{$dbConnection[1]}:host={$dbHost[1]};port={$dbPort[1]};dbname={$dbName[1]}";
                $pdo = new PDO($dsn, $dbUsername[1], $password);
                
                echo "✅ Connexion à la base de données réussie\n";
                
                // Vérifier si les tables existent
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $tableCount = count($tables);
                
                echo "- Nombre de tables : $tableCount\n";
                
                if ($tableCount > 0) {
                    echo "- Tables principales : " . implode(', ', array_slice($tables, 0, 5)) . (count($tables) > 5 ? '...' : '') . "\n";
                    
                    // Vérifier si la table des utilisateurs existe et contient des données
                    if (in_array('users', $tables)) {
                        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                        echo "- Nombre d'utilisateurs : $userCount\n";
                        
                        if ($userCount > 0) {
                            // Vérifier s'il y a des administrateurs
                            $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                            echo "- Nombre d'administrateurs : $adminCount\n";
                            
                            if ($adminCount > 0) {
                                echo "\n⚠️ L'application semble être installée mais le fichier d'installation est manquant.\n";
                                echo "   Vous pouvez créer ce fichier manuellement pour marquer l'application comme installée.\n";
                            }
                        }
                    }
                } else {
                    echo "❌ Aucune table n'existe dans la base de données\n";
                    echo "   Vous devez exécuter les migrations pour créer les tables nécessaires.\n";
                }
            } catch (PDOException $e) {
                echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "❌ Le fichier .env n'existe pas\n";
        echo "   Vous devez créer ce fichier avant de pouvoir installer l'application.\n";
    }
    
    // Afficher l'URL d'installation
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = "$protocol://$host";
    $setupUrl = "$baseUrl/smart_school_new/setup";
    
    echo "\nPour installer l'application, accédez à l'URL suivante :\n";
    echo "🔗 $setupUrl\n";
}

echo "\n=================================================================\n";
echo "Pour toute assistance, consultez la documentation ou contactez le support.\n";
echo "=================================================================\n"; 