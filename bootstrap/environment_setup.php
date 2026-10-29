<?php

/**
 * Environment Setup Script
 * 
 * Ce script détecte automatiquement si l'application s'exécute sur Windows ou Linux
 * et configure les chemins appropriés pour assurer la compatibilité entre les deux systèmes.
 */

// Détection du système d'exploitation
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// Configuration des chemins spécifiques à l'OS si nécessaire
if ($isWindows) {
    // Configuration Windows
    // Exemple: ajuster les chemins de stockage si nécessaire
    // putenv("WINDOWS_SPECIFIC_PATH=C:/wamp64/www/smart_school_new");
} else {
    // Configuration Linux
    // Exemple: ajuster les chemins de stockage si nécessaire
    // putenv("LINUX_SPECIFIC_PATH=/opt/lampp/htdocs/ESBTP-yAKROv2");
}

// Vérification de la base de données
// Note: Cette partie sera exécutée après le chargement complet de Laravel
// donc nous n'avons pas besoin de notre propre fonction env()
// La vérification de la base de données sera gérée par les migrations Laravel 