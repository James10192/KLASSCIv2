<?php

/**
 * Script pour créer le lien symbolique storage sur le serveur
 * 
 * Usage: php create_storage_link.php
 */

echo "=== Création du lien symbolique storage ===\n";

try {
    // Chemins
    $storagePath = __DIR__ . '/storage/app/public';
    $publicPath = __DIR__ . '/public/storage';

    echo "Chemin storage: $storagePath\n";
    echo "Chemin public: $publicPath\n";

    // Vérifier que le dossier storage/app/public existe
    if (!is_dir($storagePath)) {
        echo "❌ Le dossier storage/app/public n'existe pas!\n";
        echo "Création du dossier...\n";
        mkdir($storagePath, 0755, true);
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
        // Windows
        $command = 'mklink /D "' . str_replace('/', '\\', $publicPath) . '" "' . str_replace('/', '\\', $storagePath) . '"';
        echo "Commande Windows: $command\n";
        $result = shell_exec($command);
        echo "Résultat: $result\n";
    } else {
        // Linux/Unix
        $result = symlink($storagePath, $publicPath);
        if ($result) {
            echo "✅ Lien symbolique créé avec succès!\n";
        } else {
            throw new Exception("Échec de la création du lien symbolique");
        }
    }

    // Vérifier que le lien fonctionne
    if (is_link($publicPath) && file_exists($publicPath)) {
        echo "✅ Lien symbolique vérifié et fonctionnel!\n";
        echo "Target: " . readlink($publicPath) . "\n";
    } else {
        echo "❌ Le lien symbolique ne semble pas fonctionner\n";
    }

    echo "\n=== Test d'accès ===\n";
    echo "Vous pouvez maintenant accéder aux fichiers storage via:\n";
    echo "http://votre-domaine.com/storage/nom-du-fichier\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    echo "\n=== Solution alternative ===\n";
    echo "Si le script ne fonctionne pas, utilisez ces commandes manuellement:\n\n";
    
    if (PHP_OS_FAMILY === 'Windows') {
        echo "Sur Windows (en tant qu'administrateur):\n";
        echo 'mklink /D "' . str_replace('/', '\\', $publicPath) . '" "' . str_replace('/', '\\', $storagePath) . '"' . "\n\n";
    } else {
        echo "Sur Linux/Unix:\n";
        echo "ln -s $storagePath $publicPath\n\n";
    }
    
    echo "Ou utilisez la commande Artisan:\n";
    echo "php artisan storage:link\n";
}

echo "\n=== Fin du script ===\n";