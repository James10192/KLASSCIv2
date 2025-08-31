<?php
/**
 * Script principal de configuration multi-plateforme
 * 
 * Ce script sert de point d'entrée pour configurer l'application
 * et assurer sa compatibilité entre Windows et Linux.
 */

// Afficher un message de bienvenue
echo "\n";
echo "=================================================================\n";
echo "      Configuration Smart School pour ESBTP\n";
echo "=================================================================\n";
echo "\n";

// Détecter le système d'exploitation
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
echo "Système d'exploitation détecté : " . ($isWindows ? "Windows" : "Linux") . "\n\n";

// Menu principal
echo "Que souhaitez-vous faire ?\n\n";
echo "1. Configurer l'environnement et la base de données\n";
echo "2. Vérifier les chemins absolus dans le code\n";
echo "3. Exécuter les migrations et les seeders\n";
echo "4. Quitter\n\n";

echo "Votre choix (1-4) : ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));

switch ($choice) {
    case '1':
        // Exécuter le script de configuration
        echo "\nLancement de la configuration de l'environnement...\n";
        include __DIR__ . '/setup_cross_platform.php';
        break;
        
    case '2':
        // Exécuter le script de vérification des chemins absolus
        echo "\nLancement de la vérification des chemins absolus...\n";
        include __DIR__ . '/check_absolute_paths.php';
        break;
        
    case '3':
        // Exécuter les migrations et les seeders
        echo "\nExécution des migrations...\n";
        system(($isWindows ? 'php ' : 'php ') . __DIR__ . '/../artisan migrate --force');
        
        echo "\nExécution des seeders...\n";
        system(($isWindows ? 'php ' : 'php ') . __DIR__ . '/../artisan db:seed --force');
        
        echo "\n✅ Migrations et seeders exécutés avec succès.\n";
        break;
        
    case '4':
        echo "\nAu revoir !\n";
        break;
        
    default:
        echo "\nChoix invalide. Veuillez réessayer.\n";
        break;
}

echo "\n";
echo "=================================================================\n";
echo "      Opération terminée !\n";
echo "=================================================================\n";
echo "\n"; 