#!/usr/bin/env php
<?php

// Autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Vérification des routes pour 'attendances' ===\n\n";

// Récupérer toutes les routes de l'application
$routes = app('router')->getRoutes()->getRoutes();

// Parcourir les routes pour trouver celles liées à 'attendances'
foreach ($routes as $route) {
    $uri = $route->uri();
    $action = $route->getAction();
    
    // Rechercher toutes les routes contenant "attendances" dans l'URI ou dans l'action
    if (stripos($uri, 'attendance') !== false || 
        (isset($action['controller']) && stripos($action['controller'], 'attendance') !== false)) {
        
        // Afficher les informations de la route
        echo "Route: {$uri}\n";
        echo "Nom: " . ($action['as'] ?? 'Non nommée') . "\n";
        echo "Méthode: " . implode('|', $route->methods()) . "\n";
        echo "Contrôleur: " . ($action['controller'] ?? 'Closure') . "\n";
        
        // Afficher les middlewares
        if (isset($action['middleware'])) {
            echo "Middlewares:\n";
            foreach ((array)$action['middleware'] as $middleware) {
                echo "- {$middleware}\n";
                
                // Vérifier si un middleware de permission est appliqué
                if (strpos($middleware, 'permission:') === 0) {
                    $permissions = substr($middleware, strlen('permission:'));
                    echo "  Permissions requises: {$permissions}\n";
                }
            }
        }
        
        echo "\n";
    }
}

echo "=== Vérification terminée ===\n"; 