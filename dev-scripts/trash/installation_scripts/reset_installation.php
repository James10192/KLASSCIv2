<?php
/**
 * Script pour réinitialiser l'installation
 * 
 * Ce script supprime le fichier d'installation pour permettre de recommencer le processus d'installation
 */

// Charger l'environnement Laravel
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================================\n";
echo "      RÉINITIALISATION DE L'INSTALLATION SMART SCHOOL\n";
echo "=================================================================\n";

// Vérifier si le fichier d'installation existe
$installFile = storage_path('app/installed');
$fileExists = file_exists($installFile);

if ($fileExists) {
    $installDate = file_get_contents($installFile);
    echo "Fichier d'installation trouvé (date: $installDate)\n";
    
    // Demander confirmation
    echo "\n⚠️ ATTENTION: Cette opération va réinitialiser l'installation de l'application.\n";
    echo "Vous serez redirigé vers la page de configuration lors de votre prochaine visite.\n";
    echo "Les données existantes ne seront PAS supprimées.\n\n";
    
    echo "Voulez-vous continuer? (o/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) === 'o' || strtolower($line) === 'oui') {
        // Supprimer le fichier d'installation
        if (unlink($installFile)) {
            echo "\n✅ Fichier d'installation supprimé avec succès!\n";
            echo "Vous pouvez maintenant accéder à l'application pour recommencer l'installation.\n";
        } else {
            echo "\n❌ Erreur lors de la suppression du fichier d'installation.\n";
            echo "Vérifiez les permissions du dossier storage/app/.\n";
        }
    } else {
        echo "\nOpération annulée.\n";
    }
} else {
    echo "❌ Fichier d'installation non trouvé. L'application n'est pas marquée comme installée.\n";
}

echo "=================================================================\n"; 