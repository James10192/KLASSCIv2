<?php

/**
 * Script de correction des problèmes d'URL dans l'application Smart School
 * 
 * Ce script vérifie et corrige les configurations d'URL dans différents fichiers
 * pour s'assurer que l'application fonctionne correctement avec le chemin
 * http://localhost/smart_school_new
 */

// Charger l'environnement Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "=== Script de correction des problèmes d'URL ===\n\n";

// 1. Vérifier et corriger le fichier .env
$envPath = __DIR__ . '/../.env';
$envContent = file_get_contents($envPath);

// Vérifier si APP_URL est correctement configuré
if (strpos($envContent, 'APP_URL=http://localhost/smart_school_new') === false) {
    // Remplacer l'URL existante par la bonne URL
    $envContent = preg_replace('/APP_URL=.*/', 'APP_URL=http://localhost/smart_school_new', $envContent);
    file_put_contents($envPath, $envContent);
    echo "✅ Fichier .env mis à jour avec l'URL correcte\n";
} else {
    echo "✓ Fichier .env déjà correctement configuré\n";
}

// 2. Vérifier et corriger le fichier .htaccess à la racine
$htaccessPath = __DIR__ . '/../.htaccess';
$htaccessContent = file_get_contents($htaccessPath);

// Vérifier si la condition de réécriture est correcte
if (strpos($htaccessContent, "RewriteCond %{REQUEST_URI} !^/smart_school_new/public/") === false) {
    // Remplacer la condition existante par la bonne condition
    $htaccessContent = preg_replace('/RewriteCond %\{REQUEST_URI\} !.*/', 'RewriteCond %{REQUEST_URI} !^/smart_school_new/public/', $htaccessContent);
    file_put_contents($htaccessPath, $htaccessContent);
    echo "✅ Fichier .htaccess à la racine mis à jour avec la condition correcte\n";
} else {
    echo "✓ Fichier .htaccess à la racine déjà correctement configuré\n";
}

// 3. Vérifier et mettre à jour AppServiceProvider.php
$appServiceProviderPath = __DIR__ . '/../app/Providers/AppServiceProvider.php';
$appServiceProviderContent = file_get_contents($appServiceProviderPath);

// Vérifier si le forceRootUrl est déjà configuré
if (strpos($appServiceProviderContent, 'URL::forceRootUrl') === false) {
    // Ajouter la configuration URL::forceRootUrl
    $bootMethodPattern = '/public function boot\(\)\s*\{/';
    $bootMethodReplacement = "public function boot()\n    {\n        // Force l'URL de base pour l'application\n        if (app()->environment('local')) {\n            \$rootUrl = request()->getSchemeAndHttpHost();\n            \\Illuminate\\Support\\Facades\\URL::forceRootUrl(\$rootUrl . '/smart_school_new');\n            \\Illuminate\\Support\\Facades\\URL::forceScheme('http');\n        }\n";
    
    $appServiceProviderContent = preg_replace($bootMethodPattern, $bootMethodReplacement, $appServiceProviderContent);
    file_put_contents($appServiceProviderPath, $appServiceProviderContent);
    echo "✅ AppServiceProvider.php mis à jour avec la configuration d'URL correcte\n";
} else {
    echo "✓ AppServiceProvider.php déjà configuré pour les URL\n";
}

echo "\n=== Vérification terminée ===\n";
echo "Votre application devrait maintenant fonctionner correctement à l'adresse:\n";
echo "http://localhost/smart_school_new\n";
echo "\nSi vous rencontrez encore des problèmes, essayez de vider le cache avec:\n";
echo "php artisan config:clear\n";
echo "php artisan cache:clear\n";
echo "php artisan route:clear\n";
echo "php artisan view:clear\n"; 