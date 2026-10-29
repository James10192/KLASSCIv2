<?php

/**
 * Redirect to the public directory
 *
 * Ce fichier redirige toutes les requêtes vers le répertoire public où
 * l'application Laravel est servie. Il gère également les chemins relatifs
 * pour assurer que les redirections fonctionnent correctement.
 */

// Définir le chemin de base du projet
$basePath = '/ESBTP-yAKRO';

// Obtenir l'URI actuelle
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Déterminer si nous sommes en mode serveur de développement ou en production
$isDevelopmentServer = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '8000');

if ($isDevelopmentServer) {
    // En mode développement (php artisan serve), on redirige simplement vers /public
    header('Location: public' . $uri);
} else {
    // En mode production (Apache/Nginx), on s'assure que le chemin est correct
    // Supprimer le nom du projet de l'URI s'il existe
    $uri = preg_replace('#^' . $basePath . '#', '', $uri);

    // Rediriger vers le répertoire public
    header('Location: ' . $basePath . '/public' . $uri);
}

exit;