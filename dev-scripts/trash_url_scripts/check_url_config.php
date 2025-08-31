<?php
/**
 * Script de vérification de la configuration des URLs
 * 
 * Ce script vérifie que la configuration des URLs est correcte et affiche
 * des informations utiles pour le débogage.
 */

echo "<h1>Vérification de la configuration des URLs</h1>";

// Informations sur le serveur
echo "<h2>Informations sur le serveur</h2>";
echo "<ul>";
echo "<li>Serveur: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Nom d'hôte: " . $_SERVER['HTTP_HOST'] . "</li>";
echo "<li>Port: " . $_SERVER['SERVER_PORT'] . "</li>";
echo "<li>URI: " . $_SERVER['REQUEST_URI'] . "</li>";
echo "<li>Chemin du script: " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";

// Vérification du fichier .env
echo "<h2>Vérification du fichier .env</h2>";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    preg_match('/APP_URL=(.*)/', $envContent, $matches);
    $appUrl = isset($matches[1]) ? $matches[1] : 'Non défini';
    echo "<p>APP_URL dans .env: " . $appUrl . "</p>";
    
    // Vérifier si l'URL est correcte
    $expectedUrl = 'http://127.0.0.1:8000';
    if ($appUrl === $expectedUrl) {
        echo "<p style='color: green;'>✓ L'URL de l'application est correctement configurée.</p>";
    } else {
        echo "<p style='color: red;'>✗ L'URL de l'application n'est pas correctement configurée. Elle devrait être: " . $expectedUrl . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Le fichier .env n'existe pas!</p>";
}

// Vérification des fichiers .htaccess
echo "<h2>Vérification des fichiers .htaccess</h2>";
$rootHtaccess = __DIR__ . '/.htaccess';
$publicHtaccess = __DIR__ . '/public/.htaccess';

if (file_exists($rootHtaccess)) {
    echo "<p style='color: green;'>✓ Le fichier .htaccess à la racine existe.</p>";
} else {
    echo "<p style='color: red;'>✗ Le fichier .htaccess à la racine n'existe pas!</p>";
}

if (file_exists($publicHtaccess)) {
    echo "<p style='color: green;'>✓ Le fichier .htaccess dans le dossier public existe.</p>";
} else {
    echo "<p style='color: red;'>✗ Le fichier .htaccess dans le dossier public n'existe pas!</p>";
}

// Vérification des URLs
echo "<h2>URLs de l'application</h2>";
echo "<ul>";
echo "<li>URL racine: <a href='/smart_school_new'>/smart_school_new</a></li>";
echo "<li>URL public: <a href='/smart_school_new/public'>/smart_school_new/public</a></li>";
echo "<li>URL artisan serve: <a href='http://127.0.0.1:8000'>http://127.0.0.1:8000</a></li>";
echo "</ul>";

// Instructions
echo "<h2>Instructions</h2>";
echo "<ol>";
echo "<li>Si vous utilisez WAMP/XAMPP, accédez à l'application via <a href='/smart_school_new'>/smart_school_new</a></li>";
echo "<li>Si vous utilisez le serveur de développement Laravel, exécutez <code>serve.bat</code> et accédez à <a href='http://127.0.0.1:8000'>http://127.0.0.1:8000</a></li>";
echo "</ol>";

// Conseils de débogage
echo "<h2>Conseils de débogage</h2>";
echo "<ul>";
echo "<li>Assurez-vous que le module mod_rewrite est activé dans Apache</li>";
echo "<li>Vérifiez que AllowOverride est défini sur All dans la configuration Apache</li>";
echo "<li>Essayez de vider le cache du navigateur</li>";
echo "<li>Vérifiez les logs d'erreur Apache pour plus d'informations</li>";
echo "</ul>"; 