<?php

/**
 * Script pour mettre à jour les chemins dans les scripts
 * 
 * Ce script met à jour les chemins dans les scripts run_seeders.php et clean_admin_structures.php
 * pour qu'ils fonctionnent correctement depuis le dossier scripts
 */

// Chemin vers le dossier scripts
$scriptsPath = __DIR__;

// Fichiers à mettre à jour
$files = [
    $scriptsPath . '/run_seeders.php',
    $scriptsPath . '/clean_admin_structures.php'
];

echo "=================================================================\n";
echo "      MISE À JOUR DES CHEMINS DANS LES SCRIPTS                    \n";
echo "=================================================================\n\n";

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "Mise à jour du fichier " . basename($file) . "...\n";
        
        // Lire le contenu du fichier
        $content = file_get_contents($file);
        
        // Remplacer les chemins
        $content = str_replace('$basePath = __DIR__;', '$basePath = dirname(__DIR__);', $content);
        
        // Écrire le contenu mis à jour
        file_put_contents($file, $content);
        
        echo "Terminé.\n";
    } else {
        echo "Le fichier " . basename($file) . " n'existe pas.\n";
    }
}

echo "\n=================================================================\n";
echo "      MISE À JOUR TERMINÉE AVEC SUCCÈS                            \n";
echo "=================================================================\n\n";

echo "Les chemins dans les scripts ont été mis à jour avec succès.\n";
echo "Vous pouvez maintenant exécuter les scripts depuis le dossier scripts.\n\n"; 