<?php

/**
 * Script pour créer le lien symbolique storage sur le serveur
 * 
 * Usage: php create_storage_link.php
 */

echo "=== Création du lien symbolique storage ===\n";

try {
    // Chemins relatifs à la racine du projet Laravel
    $storagePath = realpath(__DIR__ . '/storage/app/public');
    $publicPath = __DIR__ . '/public/storage';

    echo "Chemin storage: $storagePath\n";
    echo "Chemin public: $publicPath\n";

    // Vérifier que le dossier storage/app/public existe
    if (!is_dir($storagePath)) {
        echo "❌ Le dossier storage/app/public n'existe pas!\n";
        echo "Création du dossier...\n";
        mkdir(__DIR__ . '/storage/app/public', 0755, true);
        $storagePath = realpath(__DIR__ . '/storage/app/public');
        echo "✅ Dossier créé\n";
    }

    // Supprimer le lien existant s'il existe
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            echo "🔄 Suppression de l'ancien lien symbolique...\n";
            unlink($publicPath);
        } else {
            echo "⚠️ Un dossier/fichier 'storage' existe déjà dans public/\n";
            echo "Sauvegarde en storage_backup...\n";
            rename($publicPath, $publicPath . '_backup');
        }
    }

    // Créer le lien symbolique
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows - Utiliser mklink avec des chemins absolus
        $storageAbsolute = str_replace('/', '\\', $storagePath);
        $publicAbsolute = str_replace('/', '\\', $publicPath);
        
        echo "Création du lien symbolique sur Windows...\n";
        echo "Source: $storageAbsolute\n";
        echo "Destination: $publicAbsolute\n";
        
        $command = 'mklink /D "' . $publicAbsolute . '" "' . $storageAbsolute . '"';
        echo "Commande: $command\n";
        
        // Exécuter la commande
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅ Lien symbolique Windows créé avec succès!\n";
        } else {
            echo "❌ Erreur lors de la création du lien sur Windows\n";
            echo "Output: " . implode("\n", $output) . "\n";
            throw new Exception("Échec mklink (code $returnCode)");
        }
    } else {
        // Linux/Unix
        $result = symlink($storagePath, $publicPath);
        if ($result) {
            echo "✅ Lien symbolique Unix créé avec succès!\n";
        } else {
            throw new Exception("Échec de la création du lien symbolique Unix");
        }
    }

    // Vérifier que le lien fonctionne
    if (is_link($publicPath) && file_exists($publicPath)) {
        echo "✅ Lien symbolique vérifié et fonctionnel!\n";
        echo "Target: " . readlink($publicPath) . "\n";
    } elseif (is_dir($publicPath)) {
        echo "✅ Dossier storage accessible!\n";
    } else {
        echo "❌ Le lien symbolique ne semble pas fonctionner\n";
    }

    echo "\n=== Test d'accès ===\n";
    echo "Vous pouvez maintenant accéder aux fichiers storage via:\n";
    echo "http://localhost:8000/storage/nom-du-fichier\n";
    echo "ou\n";
    echo "http://votre-domaine.com/storage/nom-du-fichier\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    echo "\n=== Solutions alternatives ===\n";
    echo "1. Utilisez la commande Artisan Laravel:\n";
    echo "   php artisan storage:link\n\n";
    
    echo "2. Commandes manuelles:\n";
    if (PHP_OS_FAMILY === 'Windows') {
        echo "   Sur Windows (Invite de commandes en tant qu'administrateur):\n";
        $storageWin = str_replace('/', '\\', realpath(__DIR__ . '/storage/app/public'));
        $publicWin = str_replace('/', '\\', __DIR__ . '/public/storage');
        echo "   mklink /D \"$publicWin\" \"$storageWin\"\n\n";
    } else {
        echo "   Sur Linux/Unix:\n";
        echo "   ln -s " . realpath(__DIR__ . '/storage/app/public') . " " . __DIR__ . "/public/storage\n\n";
    }
    
    echo "3. Configuration alternative:\n";
    echo "   - Configurez votre serveur web pour servir les fichiers storage\n";
    echo "   - Ou copiez manuellement les fichiers dans public/storage\n";
}

echo "\n=== Fin du script ===\n";