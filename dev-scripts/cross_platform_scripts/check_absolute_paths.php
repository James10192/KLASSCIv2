<?php
/**
 * Script de vérification des chemins absolus
 * 
 * Ce script parcourt tous les fichiers PHP du projet et recherche les chemins absolus
 * qui pourraient causer des problèmes de compatibilité entre Windows et Linux.
 */

// Afficher un message de bienvenue
echo "\n";
echo "=================================================================\n";
echo "      Vérification des chemins absolus dans le code\n";
echo "=================================================================\n";
echo "\n";

// Définir les motifs de recherche pour les chemins absolus
$patterns = [
    // Chemins Linux
    '/\/opt\/lampp\/htdocs/',
    '/\/var\/www\/html/',
    '/\/home\/[a-zA-Z0-9]+\/public_html/',
    
    // Chemins Windows
    '/C:\\\\wamp64\\\\www/',
    '/C:\\\\xampp\\\\htdocs/',
    '/C:\\\\inetpub\\\\wwwroot/',
    
    // Autres motifs courants
    '/require(_once)?\s*\(\s*[\'"]\//',
    '/include(_once)?\s*\(\s*[\'"]\//',
];

// Fonction pour parcourir récursivement un répertoire
function scanDirectory($dir, $patterns) {
    $results = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'vendor' || $file === 'node_modules') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $results = array_merge($results, scanDirectory($path, $patterns));
        } else {
            // Vérifier uniquement les fichiers PHP
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path);
                
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $matches = [];
                        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
                        
                        foreach ($matches[0] as $match) {
                            // Trouver le numéro de ligne
                            $lines = explode("\n", substr($content, 0, $match[1]));
                            $lineNumber = count($lines);
                            
                            // Extraire la ligne complète
                            $contentLines = explode("\n", $content);
                            $line = $contentLines[$lineNumber - 1];
                            
                            $results[] = [
                                'file' => $path,
                                'line' => $lineNumber,
                                'match' => $match[0],
                                'context' => trim($line)
                            ];
                        }
                    }
                }
            }
        }
    }
    
    return $results;
}

// Parcourir le répertoire du projet
$projectDir = __DIR__ . '/..';
$results = scanDirectory($projectDir, $patterns);

// Afficher les résultats
if (empty($results)) {
    echo "✅ Aucun chemin absolu trouvé dans le code.\n";
} else {
    echo "❌ " . count($results) . " chemins absolus trouvés dans le code :\n\n";
    
    foreach ($results as $result) {
        echo "Fichier : " . str_replace($projectDir . '/', '', $result['file']) . "\n";
        echo "Ligne   : " . $result['line'] . "\n";
        echo "Trouvé  : " . $result['match'] . "\n";
        echo "Contexte: " . $result['context'] . "\n";
        echo "-------------------------------------------------------------------\n";
    }
    
    echo "\nConseil : Remplacez les chemins absolus par des chemins relatifs en utilisant __DIR__.\n";
    echo "Exemple : require __DIR__ . '/../vendor/autoload.php';\n";
}

echo "\n";
echo "=================================================================\n";
echo "      Vérification terminée !\n";
echo "=================================================================\n";
echo "\n"; 