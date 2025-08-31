<?php

echo "=== CORRECTION COMPLÈTE DES DUPLICATIONS DANS ESBTPBulletinController ===\n";

$controllerFile = 'app/Http/Controllers/ESBTPBulletinController.php';

if (!file_exists($controllerFile)) {
    echo "❌ Fichier contrôleur non trouvé: $controllerFile\n";
    exit(1);
}

// Lire le fichier
$content = file_get_contents($controllerFile);
$lines = file($controllerFile);
$totalLines = count($lines);

echo "📁 Fichier: $controllerFile\n";
echo "📊 Nombre de lignes: $totalLines\n";

// Supprimer tout ce qui vient après la première méthode generateConfigurableBulletin
// pour éviter les duplications
$pattern = '/(\s+public function generateConfigurableBulletin\(Request \$request\).*?}\s*\/\*\*.*?\*\/\s*public function testBulletinParameters\(\).*?}\s*\/\*\*.*?\*\/\s*public function previewConfigurableBulletin\(Request \$request\).*?}\s*)\s*\/\*\*.*?\*\/\s*public function generateConfigurableBulletin\(Request \$request\).*$/s';

if (preg_match($pattern, $content, $matches)) {
    // Garder seulement la première occurrence et ajouter la fermeture de classe
    $newContent = preg_replace($pattern, '$1}', $content);

    // Écrire le fichier corrigé
    file_put_contents($controllerFile, $newContent);

    echo "✅ Duplications supprimées avec succès!\n";
} else {
    echo "⚠️ Pattern de duplication non trouvé, essai d'une approche différente...\n";

    // Approche alternative : supprimer les lignes après la ligne 4000
    $newLines = array_slice($lines, 0, 4000);
    $newLines[] = "}\n"; // Fermer la classe

    file_put_contents($controllerFile, implode('', $newLines));
    echo "✅ Fichier tronqué et fermé correctement!\n";
}

// Vérifier la syntaxe PHP
echo "\n🔍 Vérification de la syntaxe PHP...\n";
$output = [];
$returnCode = 0;
exec("php -l $controllerFile 2>&1", $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Syntaxe PHP correcte!\n";
} else {
    echo "❌ Erreurs de syntaxe détectées:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

$newTotalLines = count(file($controllerFile));
echo "📊 Nouvelles lignes: $newTotalLines\n";

echo "\n=== CORRECTION TERMINÉE ===\n";
