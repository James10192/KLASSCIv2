<?php
/**
 * Script pour vider le cache de l'application
 * 
 * Ce script exécute les commandes Artisan pour vider les différents caches
 * de l'application Laravel.
 */

// Définir le chemin de base
$basePath = dirname(__DIR__);

// Fonction pour exécuter une commande et afficher le résultat
function executeCommand($command) {
    echo "Exécution de: $command\n";
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "✅ Succès\n";
    } else {
        echo "❌ Échec (code: $returnVar)\n";
    }
    
    if (!empty($output)) {
        echo implode("\n", $output) . "\n";
    }
    
    echo "\n";
}

echo "=== Script de nettoyage du cache ===\n\n";

// Commandes à exécuter
$commands = [
    'php artisan config:clear',     // Vide le cache de configuration
    'php artisan cache:clear',      // Vide le cache de l'application
    'php artisan route:clear',      // Vide le cache des routes
    'php artisan view:clear',       // Vide le cache des vues
    'php artisan optimize:clear',   // Vide tous les caches d'optimisation
];

// Changer le répertoire de travail
chdir($basePath);

// Exécuter chaque commande
foreach ($commands as $command) {
    executeCommand($command);
}

echo "=== Nettoyage du cache terminé ===\n";
echo "L'application devrait maintenant utiliser les configurations les plus récentes.\n"; 