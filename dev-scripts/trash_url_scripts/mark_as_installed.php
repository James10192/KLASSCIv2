<?php
/**
 * Script pour marquer l'application Smart School comme installée
 * 
 * Ce script crée le fichier d'installation pour indiquer que l'application est installée.
 * Utile si l'application est déjà configurée mais que le fichier d'installation est manquant.
 * 
 * @author Claude 3.7 Sonnet
 * @version 1.0
 */

// Chemin vers le fichier d'installation
$installedFilePath = __DIR__ . '/../storage/app/installed';

echo "=================================================================\n";
echo "      MARQUAGE DE L'APPLICATION COMME INSTALLÉE                  \n";
echo "=================================================================\n\n";

// Vérifier si le fichier d'installation existe déjà
if (file_exists($installedFilePath)) {
    $installDate = file_get_contents($installedFilePath);
    echo "⚠️ L'application est déjà marquée comme installée (date d'installation: $installDate)\n";
    echo "   Aucune action n'est nécessaire.\n";
} else {
    // Vérifier si le répertoire storage/app existe
    $storageAppDir = __DIR__ . '/../storage/app';
    
    if (!is_dir($storageAppDir)) {
        // Créer le répertoire s'il n'existe pas
        if (!mkdir($storageAppDir, 0755, true)) {
            echo "❌ Erreur : Impossible de créer le répertoire storage/app\n";
            exit(1);
        }
    }
    
    // Créer le fichier d'installation avec la date et l'heure actuelles
    $currentDateTime = date('Y-m-d H:i:s');
    
    if (file_put_contents($installedFilePath, $currentDateTime)) {
        echo "✅ L'application a été marquée comme installée avec succès\n";
        echo "   Date d'installation : $currentDateTime\n";
    } else {
        echo "❌ Erreur : Impossible de créer le fichier d'installation\n";
        echo "   Vérifiez les permissions d'écriture dans le répertoire storage/app\n";
    }
}

echo "\n=================================================================\n";
echo "Pour toute assistance, consultez la documentation ou contactez le support.\n";
echo "=================================================================\n"; 