<?php
/**
 * Script de correction des liens codés en dur dans les vues
 * 
 * Ce script parcourt tous les fichiers de vue (.blade.php) et remplace les liens
 * codés en dur (comme href="/login") par des liens générés par les fonctions
 * route() ou url() de Laravel.
 */

// Charger l'environnement Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "=== Script de correction des liens codés en dur ===\n\n";

// Fonction pour parcourir récursivement un répertoire
function scanDirectory($dir, &$results = []) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path, $results);
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $results[] = $path;
        }
    }
    
    return $results;
}

// Récupérer tous les fichiers de vue
$viewsPath = __DIR__ . '/../resources/views';
$files = scanDirectory($viewsPath);

echo "Analyse de " . count($files) . " fichiers...\n\n";

// Patterns à rechercher et remplacer
$patterns = [
    // Liens de navigation
    '~href\s*=\s*["\']/(login|register|home|dashboard)["\']~' => function($matches) {
        $route = $matches[1];
        return 'href="{{ route(\'' . $route . '\') }}"';
    },
    
    // Liens d'action
    '~href\s*=\s*["\']/(users|roles|permissions)/([^"\']+)["\']~' => function($matches) {
        $resource = $matches[1];
        $action = $matches[2];
        
        // Déterminer le nom de la route en fonction de l'action
        if ($action === 'create') {
            return 'href="{{ route(\'' . rtrim($resource, 's') . '.create\') }}"';
        } else if (is_numeric($action)) {
            return 'href="{{ route(\'' . rtrim($resource, 's') . '.show\', ' . $action . ') }}"';
        } else if (preg_match('/(\d+)\/edit/', $action, $idMatches)) {
            return 'href="{{ route(\'' . rtrim($resource, 's') . '.edit\', ' . $idMatches[1] . ') }}"';
        } else {
            // Si on ne peut pas déterminer la route, utiliser url()
            return 'href="{{ url(\'/' . $resource . '/' . $action . '\') }}"';
        }
    },
    
    // Liens d'assets (CSS, JS, images)
    '~(src|href)\s*=\s*["\']/(css|js|images|assets)/([^"\']+)["\']~' => function($matches) {
        return $matches[1] . '="{{ asset(\'' . $matches[2] . '/' . $matches[3] . '\') }}"';
    },
    
    // Formulaires avec action codée en dur
    '~<form[^>]*action\s*=\s*["\']/(login|register|logout|password/email|password/reset)["\'][^>]*>~' => function($matches) {
        $route = str_replace('/', '.', $matches[1]);
        return str_replace('action="/' . $matches[1] . '"', 'action="{{ route(\'' . $route . '\') }}"', $matches[0]);
    }
];

$totalFixed = 0;
$filesFixed = 0;

// Parcourir chaque fichier et appliquer les corrections
foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $fixedInFile = 0;
    
    // Appliquer chaque pattern
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace_callback($pattern, $replacement, $content, -1, $count);
        $fixedInFile += $count;
    }
    
    // Si des modifications ont été apportées, sauvegarder le fichier
    if ($fixedInFile > 0) {
        file_put_contents($file, $content);
        $filesFixed++;
        $totalFixed += $fixedInFile;
        
        $relativeFile = str_replace(__DIR__ . '/../', '', $file);
        echo "✅ Corrigé $fixedInFile liens dans $relativeFile\n";
    }
}

echo "\n=== Résumé ===\n";
echo "Total de fichiers analysés: " . count($files) . "\n";
echo "Fichiers modifiés: $filesFixed\n";
echo "Liens corrigés: $totalFixed\n";

if ($totalFixed > 0) {
    echo "\nLes liens codés en dur ont été remplacés par des fonctions route() ou asset().\n";
    echo "Cela permettra à votre application de fonctionner correctement, quelle que soit l'URL de base.\n";
} else {
    echo "\nAucun lien codé en dur n'a été trouvé. Votre application utilise déjà les bonnes pratiques pour les liens.\n";
}

echo "\n=== Terminé ===\n"; 