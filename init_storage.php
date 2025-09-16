<?php

/**
 * Script d'initialisation du système de stockage ESBTP
 *
 * Ce script configure complètement le système de stockage :
 * - Crée les dossiers nécessaires
 * - Configure le lien symbolique storage
 * - Ajoute des fichiers de protection
 *
 * Usage: php init_storage.php
 */

echo "=== INITIALISATION DU STOCKAGE ESBTP ===\n";

try {
    // Configuration des chemins
    $baseDir = __DIR__;
    $storagePublicPath = $baseDir . '/storage/app/public';
    $publicStoragePath = $baseDir . '/public/storage';

    echo "📁 Répertoire de base: $baseDir\n";
    echo "📁 Storage public: $storagePublicPath\n";
    echo "📁 Lien public: $publicStoragePath\n\n";

    // 1. Créer la structure de dossiers
    echo "🏗️  CRÉATION DE LA STRUCTURE DE DOSSIERS\n";

    $directories = [
        'photos/etudiants',
        'photos/enseignants',
        'photos/secretaires',
        'logos',
        'documents/bulletins',
        'documents/attestations',
        'documents/certificats',
        'documents/reçus',
        'annonces',
        'partenariats',
        'evenements',
        'uploads/temp'
    ];

    foreach ($directories as $dir) {
        $fullPath = $storagePublicPath . '/' . $dir;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
            echo "✅ Créé: $dir\n";
        } else {
            echo "📁 Existe déjà: $dir\n";
        }

        // Ajouter .gitignore pour préserver la structure mais ignorer le contenu
        $gitignoreFile = $fullPath . '/.gitignore';
        if (!file_exists($gitignoreFile)) {
            file_put_contents($gitignoreFile, "*\n!.gitignore\n");
            echo "📝 .gitignore ajouté dans $dir\n";
        }
    }

    // 2. Ajouter un fichier index.html pour sécurité
    echo "\n🔒 SÉCURISATION DES DOSSIERS\n";

    $indexContent = '<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
    <h1>Accès interdit</h1>
    <p>Vous n\'avez pas l\'autorisation d\'accéder à ce répertoire.</p>
</body>
</html>';

    foreach ($directories as $dir) {
        $fullPath = $storagePublicPath . '/' . $dir;
        $indexFile = $fullPath . '/index.html';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, $indexContent);
            echo "🔒 index.html ajouté dans $dir\n";
        }
    }

    // 3. Créer le lien symbolique
    echo "\n🔗 CRÉATION DU LIEN SYMBOLIQUE\n";

    // Vérifier que storage/app/public existe
    if (!is_dir($storagePublicPath)) {
        mkdir($storagePublicPath, 0755, true);
        echo "📁 Dossier storage/app/public créé\n";
    }

    // Supprimer le lien existant s'il existe
    if (file_exists($publicStoragePath)) {
        if (is_link($publicStoragePath)) {
            echo "🔄 Suppression de l'ancien lien symbolique...\n";
            unlink($publicStoragePath);
        } else {
            echo "⚠️  Un dossier/fichier 'storage' existe déjà dans public/\n";
            echo "Sauvegarde en storage_backup...\n";
            rename($publicStoragePath, $publicStoragePath . '_backup_' . date('Y-m-d_H-i-s'));
        }
    }

    // Créer le lien selon l'OS
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows - Utiliser mklink
        $storageAbsolute = str_replace('/', '\\', realpath($storagePublicPath));
        $publicAbsolute = str_replace('/', '\\', $publicStoragePath);

        echo "🪟 Création du lien symbolique Windows...\n";
        echo "Source: $storageAbsolute\n";
        echo "Destination: $publicAbsolute\n";

        $command = 'mklink /D "' . $publicAbsolute . '" "' . $storageAbsolute . '"';
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            echo "✅ Lien symbolique Windows créé avec succès!\n";
        } else {
            echo "❌ Erreur mklink. Essai avec la commande Artisan...\n";
            // Fallback: utiliser artisan storage:link
            exec('php artisan storage:link 2>&1', $artisanOutput, $artisanCode);
            if ($artisanCode === 0) {
                echo "✅ Lien créé avec Artisan!\n";
            } else {
                throw new Exception("Échec de création du lien symbolique");
            }
        }
    } else {
        // Linux/Unix
        $result = symlink(realpath($storagePublicPath), $publicStoragePath);
        if ($result) {
            echo "✅ Lien symbolique Unix créé avec succès!\n";
        } else {
            throw new Exception("Échec de la création du lien symbolique Unix");
        }
    }

    // 4. Vérifier le lien
    echo "\n🔍 VÉRIFICATION DU LIEN\n";

    if (is_link($publicStoragePath) && file_exists($publicStoragePath)) {
        echo "✅ Lien symbolique vérifié et fonctionnel!\n";
        echo "Target: " . readlink($publicStoragePath) . "\n";
    } elseif (is_dir($publicStoragePath)) {
        echo "✅ Dossier storage accessible!\n";
    } else {
        echo "❌ Le lien symbolique ne semble pas fonctionner\n";
    }

    // 5. Créer des images de test/placeholder
    echo "\n🖼️  CRÉATION D'IMAGES DE TEST\n";

    // Créer une image placeholder simple
    $placeholderSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="150" height="150" viewBox="0 0 150 150" xmlns="http://www.w3.org/2000/svg">
    <rect width="150" height="150" fill="#f8f9fa"/>
    <circle cx="75" cy="65" r="20" fill="#6c757d"/>
    <path d="M45 100 Q45 90 55 90 L95 90 Q105 90 105 100 L105 130 Q105 140 95 140 L55 140 Q45 140 45 130 Z" fill="#6c757d"/>
    <text x="75" y="125" text-anchor="middle" font-family="Arial" font-size="8" fill="#ffffff">PHOTO</text>
</svg>';

    $placeholderPath = $storagePublicPath . '/photos/placeholder.svg';
    if (!file_exists($placeholderPath)) {
        file_put_contents($placeholderPath, $placeholderSvg);
        echo "🖼️  Image placeholder créée\n";
    }

    // Logo par défaut pour l'établissement
    $logoSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="80" viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="80" fill="#0453cb" rx="5"/>
    <text x="100" y="30" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" font-weight="bold" fill="white">ESBTP</text>
    <text x="100" y="50" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#ccddff">École Supérieure</text>
    <text x="100" y="65" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#ccddff">Bâtiment et Travaux Publics</text>
</svg>';

    $logoPath = $storagePublicPath . '/logos/esbtp-logo.svg';
    if (!file_exists($logoPath)) {
        file_put_contents($logoPath, $logoSvg);
        echo "🏛️  Logo ESBTP par défaut créé\n";
    }

    // 6. Tester l'accès aux images
    echo "\n🧪 TEST D'ACCÈS AUX IMAGES\n";

    $testUrls = [
        '/storage/photos/placeholder.svg',
        '/storage/logos/esbtp-logo.svg'
    ];

    foreach ($testUrls as $url) {
        $fullPath = $baseDir . '/public' . $url;
        if (file_exists($fullPath)) {
            echo "✅ Accessible: http://localhost:8000$url\n";
        } else {
            echo "❌ Non accessible: $url\n";
        }
    }

    // 7. Résumé final
    echo "\n📋 RÉSUMÉ DE L'INITIALISATION\n";
    echo "✅ Structure de dossiers créée\n";
    echo "✅ Lien symbolique configuré\n";
    echo "✅ Sécurité ajoutée (index.html, .gitignore)\n";
    echo "✅ Images de test créées\n";

    echo "\n🌐 URLS D'ACCÈS:\n";
    echo "• Photos étudiants: http://localhost:8000/storage/photos/etudiants/\n";
    echo "• Logo établissement: http://localhost:8000/storage/logos/\n";
    echo "• Documents: http://localhost:8000/storage/documents/\n";

    echo "\n🔧 COMMANDES POUR LE SERVEUR DISTANT:\n";
    echo "1. Copier ce script sur le serveur\n";
    echo "2. Exécuter: php init_storage.php\n";
    echo "3. Vérifier les permissions: chmod -R 755 storage/\n";
    echo "4. Vérifier les permissions web: chown -R www-data:www-data storage/ (Linux)\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";

    echo "\n🆘 SOLUTIONS ALTERNATIVES:\n";
    echo "1. Commande Artisan Laravel:\n";
    echo "   php artisan storage:link\n\n";

    echo "2. Création manuelle des dossiers:\n";
    foreach ($directories ?? [] as $dir) {
        echo "   mkdir -p storage/app/public/$dir\n";
    }

    echo "\n3. Lien symbolique manuel:\n";
    if (PHP_OS_FAMILY === 'Windows') {
        echo "   mklink /D \"public\\storage\" \"storage\\app\\public\"\n";
    } else {
        echo "   ln -s ../storage/app/public public/storage\n";
    }
}

echo "\n=== INITIALISATION TERMINÉE ===\n";