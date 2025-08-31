<?php
/**
 * Script pour exécuter les migrations et les seeders de Smart School
 * 
 * Ce script permet d'exécuter les migrations et les seeders de la base de données
 * sans passer par l'interface web d'installation.
 * 
 * @author Claude 3.7 Sonnet
 * @version 1.0
 */

// Chemin vers le fichier artisan
$artisanPath = __DIR__ . '/../artisan';

// Vérifier si le fichier artisan existe
if (!file_exists($artisanPath)) {
    echo "❌ Erreur : Le fichier artisan n'existe pas\n";
    exit(1);
}

echo "=================================================================\n";
echo "      EXÉCUTION DES MIGRATIONS ET SEEDERS                        \n";
echo "=================================================================\n\n";

// Fonction pour exécuter une commande artisan
function runArtisanCommand($command) {
    global $artisanPath;
    
    echo "Exécution de la commande : php artisan $command\n";
    
    // Exécuter la commande
    $output = [];
    $returnVar = 0;
    exec("php " . escapeshellarg($artisanPath) . " $command 2>&1", $output, $returnVar);
    
    // Afficher la sortie
    echo implode("\n", $output) . "\n";
    
    return $returnVar === 0;
}

// Demander confirmation
echo "Cette opération va exécuter les migrations et les seeders de la base de données.\n";
echo "Assurez-vous que la base de données est configurée correctement dans le fichier .env.\n\n";
echo "Voulez-vous continuer ? (o/n) : ";
$confirmation = trim(fgets(STDIN));

if (strtolower($confirmation) !== 'o') {
    echo "\nOpération annulée.\n";
    exit(0);
}

echo "\n";

// Exécuter les migrations
echo "Étape 1/4 : Exécution des migrations...\n";
$migrateSuccess = runArtisanCommand("migrate --force");

if (!$migrateSuccess) {
    echo "❌ Erreur lors de l'exécution des migrations\n";
    echo "Voulez-vous continuer avec les seeders ? (o/n) : ";
    $continueConfirmation = trim(fgets(STDIN));
    
    if (strtolower($continueConfirmation) !== 'o') {
        echo "\nOpération interrompue.\n";
        exit(1);
    }
}

// Exécuter les seeders
echo "\nÉtape 2/4 : Exécution des seeders...\n";
$seedSuccess = runArtisanCommand("db:seed --force");

if (!$seedSuccess) {
    echo "❌ Erreur lors de l'exécution des seeders\n";
}

// Optimiser l'application
echo "\nÉtape 3/4 : Optimisation de l'application...\n";
runArtisanCommand("optimize:clear");

// Marquer l'application comme installée
echo "\nÉtape 4/4 : Marquage de l'application comme installée...\n";
$installedFilePath = __DIR__ . '/../storage/app/installed';
$storageAppDir = __DIR__ . '/../storage/app';

if (!is_dir($storageAppDir)) {
    mkdir($storageAppDir, 0755, true);
}

$currentDateTime = date('Y-m-d H:i:s');
if (file_put_contents($installedFilePath, $currentDateTime)) {
    echo "✅ L'application a été marquée comme installée\n";
} else {
    echo "❌ Erreur : Impossible de créer le fichier d'installation\n";
}

echo "\n=================================================================\n";
echo "Pour créer un utilisateur administrateur, exécutez le script create_admin_user.php\n";
echo "=================================================================\n"; 