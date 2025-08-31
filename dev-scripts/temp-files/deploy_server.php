<?php

/**
 * Script de déploiement complet pour le serveur
 * 
 * Usage: php deploy_server.php
 */

echo "=== SCRIPT DE DÉPLOIEMENT ESBTP ===\n\n";

$commands = [
    "Mise à jour des permissions" => [
        "description" => "Exécuter le script de réparation des permissions",
        "command" => "php fix_permissions.php"
    ],
    "Cache Laravel" => [
        "description" => "Nettoyer les caches",
        "commands" => [
            "php artisan config:clear",
            "php artisan cache:clear", 
            "php artisan route:clear",
            "php artisan view:clear"
        ]
    ],
    "Storage link" => [
        "description" => "Créer le lien symbolique storage",
        "command" => "php artisan storage:link"
    ],
    "Optimisation" => [
        "description" => "Optimiser pour la production",
        "commands" => [
            "php artisan config:cache",
            "php artisan route:cache"
        ]
    ],
    "Permissions fichiers" => [
        "description" => "Définir les bonnes permissions",
        "commands" => [
            "chmod -R 755 storage/",
            "chmod -R 755 bootstrap/cache/",
            "chmod -R 644 .env"
        ]
    ]
];

function executeCommand($command, $description = null) {
    if ($description) {
        echo "➤ $description\n";
    }
    echo "  Exécution: $command\n";
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "  ✅ Succès\n";
        if (!empty($output)) {
            foreach ($output as $line) {
                echo "    $line\n";
            }
        }
    } else {
        echo "  ❌ Échec (code: $returnCode)\n";
        foreach ($output as $line) {
            echo "    $line\n";
        }
    }
    echo "\n";
    return $returnCode === 0;
}

try {
    foreach ($commands as $title => $config) {
        echo "=== $title ===\n";
        
        if (isset($config['command'])) {
            // Commande unique
            executeCommand($config['command'], $config['description']);
        } elseif (isset($config['commands'])) {
            // Multiples commandes
            echo $config['description'] . "\n";
            foreach ($config['commands'] as $cmd) {
                executeCommand($cmd);
            }
        }
    }
    
    echo "=== VÉRIFICATIONS FINALES ===\n";
    
    // Vérifier storage link
    $storageLinkExists = is_link('public/storage') && file_exists('public/storage');
    echo "Storage link: " . ($storageLinkExists ? "✅ OK" : "❌ NOK") . "\n";
    
    // Vérifier permissions
    $storageWritable = is_writable('storage');
    echo "Storage writable: " . ($storageWritable ? "✅ OK" : "❌ NOK") . "\n";
    
    $cacheWritable = is_writable('bootstrap/cache');
    echo "Cache writable: " . ($cacheWritable ? "✅ OK" : "❌ NOK") . "\n";
    
    echo "\n🎉 DÉPLOIEMENT TERMINÉ !\n";
    echo "\nVérifiez maintenant votre application sur:\n";
    echo "- Interface de création emploi du temps avec planification\n";
    echo "- Interface en cards pour la liste des emplois du temps\n";
    echo "- Accès aux pages planning-general pour superadmin\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "\nConsultez les logs pour plus de détails.\n";
}