<?php

/**
 * Script d'aide pour la traduction - Smart School
 * 
 * Ce script permet de basculer facilement entre les langues disponibles
 * dans l'application Laravel.
 * 
 * Utilisation:
 * php translation_helper.php [langue]
 * 
 * Exemple:
 * php translation_helper.php fr  # Pour passer en français
 * php translation_helper.php en  # Pour passer en anglais
 * 
 * Si aucune langue n'est spécifiée, le script affiche la langue actuelle.
 */

// Vérifier si le script est exécuté dans le bon répertoire
if (!file_exists('../config/app.php')) {
    echo "Erreur: Ce script doit être exécuté depuis le dossier trash_url_scripts.\n";
    echo "Exemple: cd trash_url_scripts && php translation_helper.php fr\n";
    exit(1);
}

// Fonction pour lire la configuration actuelle
function getCurrentLocale() {
    $appConfig = file_get_contents('../config/app.php');
    if (preg_match("/'locale'\s*=>\s*'([a-z]{2})'/", $appConfig, $matches)) {
        return $matches[1];
    }
    return 'inconnu';
}

// Fonction pour changer la langue
function changeLocale($newLocale) {
    // Vérifier si la langue est disponible
    if (!is_dir("../resources/lang/$newLocale")) {
        echo "Erreur: La langue '$newLocale' n'est pas disponible.\n";
        echo "Langues disponibles:\n";
        foreach (glob("../resources/lang/*", GLOB_ONLYDIR) as $dir) {
            echo "- " . basename($dir) . "\n";
        }
        return false;
    }

    // Lire le fichier de configuration
    $appConfig = file_get_contents('../config/app.php');
    
    // Remplacer la langue
    $newConfig = preg_replace(
        "/'locale'\s*=>\s*'[a-z]{2}'/", 
        "'locale' => '$newLocale'", 
        $appConfig
    );
    
    // Écrire le fichier modifié
    if (file_put_contents('../config/app.php', $newConfig)) {
        echo "La langue a été changée en: $newLocale\n";
        echo "N'oubliez pas de vider les caches avec: php artisan optimize:clear\n";
        return true;
    } else {
        echo "Erreur: Impossible d'écrire dans le fichier config/app.php\n";
        return false;
    }
}

// Traitement principal
if ($argc > 1) {
    // Une langue a été spécifiée
    $newLocale = strtolower($argv[1]);
    if (strlen($newLocale) !== 2) {
        echo "Erreur: Le code de langue doit être composé de 2 lettres (ex: fr, en).\n";
        exit(1);
    }
    
    changeLocale($newLocale);
} else {
    // Aucune langue spécifiée, afficher la langue actuelle
    $currentLocale = getCurrentLocale();
    echo "Langue actuelle: $currentLocale\n";
    echo "Pour changer de langue, utilisez: php translation_helper.php [langue]\n";
    echo "Langues disponibles:\n";
    foreach (glob("../resources/lang/*", GLOB_ONLYDIR) as $dir) {
        echo "- " . basename($dir) . "\n";
    }
} 