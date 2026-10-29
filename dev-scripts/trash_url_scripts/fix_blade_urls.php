<?php
/**
 * Script de correction des URLs dans les vues Blade
 * 
 * Ce script vérifie et corrige les problèmes d'URL dans les vues Blade
 * de l'application ESBTP.
 */

// Fonction pour afficher un message
function displayMessage($message, $type = 'info') {
    $colors = [
        'info' => 'blue',
        'success' => 'green',
        'warning' => 'orange',
        'error' => 'red'
    ];
    
    $color = isset($colors[$type]) ? $colors[$type] : 'black';
    echo "<p style='color: {$color};'>{$message}</p>";
}

// Fonction pour scanner récursivement un répertoire
function scanDirectory($dir, &$results = []) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path, $results);
        } else {
            // Ne traiter que les fichiers .blade.php
            if (strpos($path, '.blade.php') !== false) {
                $results[] = $path;
            }
        }
    }
    
    return $results;
}

// Fonction pour vérifier et corriger les URLs dans un fichier
function checkAndFixUrls($file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $modified = false;
    
    // Modèles de recherche et de remplacement
    $patterns = [
        // URLs codées en dur
        '~(href|src)=["\']https?://localhost/smart_school_new/public/([^"\']*)["\']~' => '$1="{{ url(\'$2\') }}"',
        '~(href|src)=["\']https?://127.0.0.1:8000/smart_school_new/public/([^"\']*)["\']~' => '$1="{{ url(\'$2\') }}"',
        '~(href|src)=["\']https?://localhost/smart_school_new/([^"\']*)["\']~' => '$1="{{ url(\'$2\') }}"',
        '~(href|src)=["\']https?://127.0.0.1:8000/([^"\']*)["\']~' => '$1="{{ url(\'$2\') }}"',
        
        // Chemins relatifs sans fonction asset() ou url()
        '~(href|src)=["\'](?!/|https?:|{{ asset|\.\.|#)([^"\']*)["\']~' => '$1="{{ asset(\'$2\') }}"',
        
        // Chemins commençant par /smart_school_new/public
        '~(href|src)=["\'](?!/smart_school_new/public/|{{ asset|\.\.|#)(/smart_school_new/public/[^"\']*)["\']~' => '$1="{{ url(\'$2\') }}"',
        
        // Routes codées en dur
        '~(action|href)=["\'](?!/|https?:|{{ route|\.\.|#)([^"\']*)["\']~' => '$1="{{ route(\'$2\') }}"',
    ];
    
    // Appliquer les modèles de recherche et de remplacement
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content) {
            $content = $newContent;
            $modified = true;
        }
    }
    
    // Si le contenu a été modifié, enregistrer le fichier
    if ($modified) {
        if (file_put_contents($file, $content)) {
            return [
                'status' => 'modified',
                'message' => "Le fichier a été modifié."
            ];
        } else {
            return [
                'status' => 'error',
                'message' => "Erreur lors de l'enregistrement du fichier."
            ];
        }
    } else {
        return [
            'status' => 'unchanged',
            'message' => "Aucune modification nécessaire."
        ];
    }
}

echo "<h1>Correction des URLs dans les vues Blade</h1>";

// Chemin de base du projet
$basePath = __DIR__ . '/../';

// Chemin des vues
$viewsPath = $basePath . 'resources/views';

// Vérifier si le répertoire des vues existe
if (!is_dir($viewsPath)) {
    displayMessage("Le répertoire des vues n'existe pas!", 'error');
    exit;
}

// Scanner le répertoire des vues
$bladeFiles = [];
scanDirectory($viewsPath, $bladeFiles);

// Afficher le nombre de fichiers trouvés
displayMessage("Nombre de fichiers Blade trouvés: " . count($bladeFiles), 'info');

// Traiter les fichiers
echo "<h2>Traitement des fichiers</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Fichier</th><th>Statut</th><th>Message</th></tr>";

$stats = [
    'modified' => 0,
    'unchanged' => 0,
    'error' => 0
];

foreach ($bladeFiles as $file) {
    // Obtenir le chemin relatif
    $relativePath = str_replace($basePath, '', $file);
    
    // Vérifier et corriger les URLs
    $result = checkAndFixUrls($file);
    
    // Mettre à jour les statistiques
    $stats[$result['status']]++;
    
    // Afficher le résultat
    echo "<tr>";
    echo "<td>{$relativePath}</td>";
    
    $statusColors = [
        'modified' => 'green',
        'unchanged' => 'blue',
        'error' => 'red'
    ];
    
    $statusColor = isset($statusColors[$result['status']]) ? $statusColors[$result['status']] : 'black';
    
    echo "<td style='color: {$statusColor};'>{$result['status']}</td>";
    echo "<td>{$result['message']}</td>";
    echo "</tr>";
}

echo "</table>";

// Afficher les statistiques
echo "<h2>Statistiques</h2>";
echo "<ul>";
echo "<li>Fichiers modifiés: {$stats['modified']}</li>";
echo "<li>Fichiers inchangés: {$stats['unchanged']}</li>";
echo "<li>Erreurs: {$stats['error']}</li>";
echo "</ul>";

// Instructions finales
echo "<h2>Instructions finales</h2>";
echo "<ol>";
echo "<li>Redémarrez le serveur Laravel si vous l'utilisez.</li>";
echo "<li>Videz le cache de votre navigateur.</li>";
echo "<li>Effacez le cache des vues Laravel avec la commande <code>php artisan view:clear</code>.</li>";
echo "<li>Accédez à l'application pour vérifier que les URLs fonctionnent correctement.</li>";
echo "</ol>";

// Bouton pour effacer le cache Laravel
echo "<form method='post'>";
echo "<input type='hidden' name='clear_cache' value='1'>";
echo "<button type='submit' style='background-color: #1e88e5; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>Effacer le cache Laravel</button>";
echo "</form>";

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
    // Simuler l'exécution des commandes Artisan
    echo "<h3>Effacement du cache...</h3>";
    echo "<pre>";
    
    // Commandes à exécuter
    $commands = [
        'php artisan cache:clear',
        'php artisan config:clear',
        'php artisan route:clear',
        'php artisan view:clear'
    ];
    
    // Exécuter les commandes
    foreach ($commands as $command) {
        echo "Exécution de: {$command}\n";
        
        // Changer de répertoire pour être à la racine du projet
        chdir($basePath);
        
        // Exécuter la commande
        $output = [];
        exec($command, $output, $returnCode);
        
        // Afficher le résultat
        echo implode("\n", $output) . "\n";
        
        if ($returnCode === 0) {
            echo "Commande exécutée avec succès.\n";
        } else {
            echo "Erreur lors de l'exécution de la commande.\n";
        }
        
        echo "\n";
    }
    
    echo "</pre>";
    
    displayMessage("Le cache a été effacé avec succès.", 'success');
} 