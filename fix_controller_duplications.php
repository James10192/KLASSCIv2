<?php

echo "=== CORRECTION DES DUPLICATIONS DANS ESBTPBulletinController ===\n";

$controllerFile = 'app/Http/Controllers/ESBTPBulletinController.php';

if (!file_exists($controllerFile)) {
    echo "❌ Fichier contrôleur non trouvé: $controllerFile\n";
    exit(1);
}

// Lire le fichier
$lines = file($controllerFile);
$totalLines = count($lines);

echo "📁 Fichier: $controllerFile\n";
echo "📊 Nombre de lignes: $totalLines\n";

// Supprimer les lignes de la méthode calculerNoteAssiduite dupliquée (lignes 4068-4082)
$linesToRemove = range(4067, 4081); // Index 0-based

$newLines = [];
foreach ($lines as $index => $line) {
    if (!in_array($index, $linesToRemove)) {
        $newLines[] = $line;
    }
}

// Écrire le fichier corrigé
file_put_contents($controllerFile, implode('', $newLines));

$newTotalLines = count($newLines);
$removedLines = $totalLines - $newTotalLines;

echo "✅ Lignes supprimées: $removedLines\n";
echo "📊 Nouvelles lignes: $newTotalLines\n";

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

echo "\n=== CORRECTION TERMINÉE ===\n";
