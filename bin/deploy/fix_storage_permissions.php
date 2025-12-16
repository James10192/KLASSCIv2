<?php

/**
 * Script de correction des permissions storage pour le serveur distant
 *
 * Ce script corrige les permissions des dossiers et fichiers storage
 * pour permettre au serveur web d'accéder aux photos et documents.
 *
 * Usage: php fix_storage_permissions.php
 */

echo "=== CORRECTION DES PERMISSIONS STORAGE ===\n\n";

try {
    // Remonter de 2 niveaux : bin/deploy/ → bin/ → racine
    $baseDir = dirname(__DIR__, 2);
    $storagePath = $baseDir . '/storage/app/public';

    echo "📁 Chemin storage: $storagePath\n\n";

    // Vérifier que le dossier existe
    if (!is_dir($storagePath)) {
        throw new Exception("Le dossier storage/app/public n'existe pas !");
    }

    // 1. Corriger les permissions des DOSSIERS (755)
    echo "🔧 CORRECTION DES PERMISSIONS DES DOSSIERS (755)...\n";

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $dirCount = 0;
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            $path = $item->getPathname();
            $currentPerms = substr(sprintf('%o', fileperms($path)), -3);

            if ($currentPerms !== '755') {
                if (chmod($path, 0755)) {
                    echo "✅ $path ($currentPerms → 755)\n";
                    $dirCount++;
                } else {
                    echo "⚠️  Échec: $path\n";
                }
            }
        }
    }

    // Dossier racine storage/app/public
    if (chmod($storagePath, 0755)) {
        echo "✅ Dossier racine: $storagePath (→ 755)\n";
        $dirCount++;
    }

    echo "\n📊 Dossiers modifiés: $dirCount\n\n";

    // 2. Corriger les permissions des FICHIERS (644)
    echo "🔧 CORRECTION DES PERMISSIONS DES FICHIERS (644)...\n";

    $fileCount = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $path = $item->getPathname();
            $currentPerms = substr(sprintf('%o', fileperms($path)), -3);

            if ($currentPerms !== '644') {
                if (chmod($path, 0644)) {
                    echo "✅ " . basename($path) . " ($currentPerms → 644)\n";
                    $fileCount++;
                }
            }
        }
    }

    echo "\n📊 Fichiers modifiés: $fileCount\n\n";

    // 3. Vérifier les permissions finales du dossier photos/etudiants
    echo "🔍 VÉRIFICATION DES PERMISSIONS...\n";

    $photosDir = $storagePath . '/photos/etudiants';
    if (is_dir($photosDir)) {
        $perms = substr(sprintf('%o', fileperms($photosDir)), -3);
        echo "Photos étudiants: $perms " . ($perms === '755' ? '✅' : '❌') . "\n";

        // Lister quelques fichiers pour vérification
        $files = glob($photosDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        if (count($files) > 0) {
            echo "\nExemples de fichiers:\n";
            foreach (array_slice($files, 0, 3) as $file) {
                $filePerms = substr(sprintf('%o', fileperms($file)), -3);
                $filename = basename($file);
                echo "  • $filename: $filePerms " . ($filePerms === '644' ? '✅' : '❌') . "\n";
            }
            echo "  Total: " . count($files) . " photos trouvées\n";
        } else {
            echo "⚠️  Aucune photo trouvée dans le dossier\n";
        }
    }

    // 4. Vérifier le lien symbolique
    echo "\n🔗 VÉRIFICATION DU LIEN SYMBOLIQUE...\n";

    $publicStoragePath = $baseDir . '/public/storage';
    if (is_link($publicStoragePath)) {
        $target = readlink($publicStoragePath);
        echo "✅ Lien symbolique existe\n";
        echo "   Source: $publicStoragePath\n";
        echo "   Target: $target\n";

        if (file_exists($publicStoragePath)) {
            echo "✅ Lien fonctionnel (target accessible)\n";
        } else {
            echo "❌ Lien cassé (target inaccessible)\n";
        }
    } else {
        echo "❌ Lien symbolique manquant\n";
        echo "   Exécutez: php create_storage_link.php\n";
    }

    // 5. Test d'accès HTTP (si possible)
    echo "\n🌐 URLS DE TEST (remplacez localhost par votre domaine):\n";

    $testFiles = glob($photosDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    if (count($testFiles) > 0) {
        $testFile = basename($testFiles[0]);
        echo "• http://votre-domaine.com/storage/photos/etudiants/$testFile\n";
        echo "• Ou pour le tenant presentation:\n";
        echo "  https://presentation.klassci.com/storage/photos/etudiants/$testFile\n";
    }

    // 6. Résumé final
    echo "\n✅ CORRECTION TERMINÉE AVEC SUCCÈS\n\n";

    echo "📋 RÉSUMÉ:\n";
    echo "• Dossiers corrigés: $dirCount (permissions 755)\n";
    echo "• Fichiers corrigés: $fileCount (permissions 644)\n";
    echo "• Les photos devraient maintenant être accessibles via le navigateur\n\n";

    echo "🔧 COMMANDES UTILES:\n";
    echo "• Vérifier les permissions: ls -la storage/app/public/photos/etudiants/\n";
    echo "• Relancer si nécessaire: php fix_storage_permissions.php\n";
    echo "• Vérifier le lien: ls -la public/storage\n\n";

    echo "⚠️  IMPORTANT:\n";
    echo "Si les photos ne s'affichent toujours pas après cette correction,\n";
    echo "vérifiez également:\n";
    echo "1. La configuration Apache/Nginx (.htaccess, FollowSymLinks)\n";
    echo "2. Les règles de sécurité du serveur (SELinux, open_basedir)\n";
    echo "3. Les logs du serveur web pour identifier l'erreur exacte\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";

    echo "\n🆘 SOLUTION MANUELLE:\n";
    echo "Exécutez ces commandes dans le terminal SSH:\n\n";
    echo "cd " . ($baseDir ?? '/path/to/project') . "\n";
    echo "chmod -R 755 storage/app/public\n";
    echo "find storage/app/public -type f -exec chmod 644 {} \\;\n";
    echo "ls -la storage/app/public/photos/etudiants/\n";
}

echo "\n=== FIN DU SCRIPT ===\n";
