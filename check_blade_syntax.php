<?php
/**
 * Script pour vérifier la syntaxe du fichier Blade dashboard-avance
 * et identifier les erreurs de crochets/parenthèses
 */

$filePath = 'resources/views/esbtp/comptabilite/dashboard-avance.blade.php';

if (!file_exists($filePath)) {
    die("Le fichier $filePath n'existe pas\n");
}

echo "=== VÉRIFICATION DE LA SYNTAXE BLADE ===\n\n";

$content = file_get_contents($filePath);
$lines = explode("\n", $content);

echo "Nombre total de lignes: " . count($lines) . "\n\n";

// Recherche des structures @json problématiques
echo "1. Recherche des structures @json...\n";
$jsonStructures = [];
$currentJson = null;
$bracketStack = [];

foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;

    // Détecter le début d'une structure @json
    if (preg_match('/@json\s*\(/', $line, $matches, PREG_OFFSET_CAPTURE)) {
        echo "Ligne $lineNumber: Début @json détecté\n";
        $currentJson = [
            'start_line' => $lineNumber,
            'start_content' => trim($line),
            'brackets' => 0,
            'parentheses' => 0
        ];
    }

    if ($currentJson !== null) {
        // Compter les crochets et parenthèses sur cette ligne
        $openBrackets = substr_count($line, '[');
        $closeBrackets = substr_count($line, ']');
        $openParens = substr_count($line, '(');
        $closeParens = substr_count($line, ')');

        $currentJson['brackets'] += ($openBrackets - $closeBrackets);
        $currentJson['parentheses'] += ($openParens - $closeParens);

        // Vérifier si la structure @json se termine
        if (strpos($line, '),') !== false || preg_match('/\)\s*$/', $line)) {
            $currentJson['end_line'] = $lineNumber;
            $currentJson['end_content'] = trim($line);

            echo "  Fin @json ligne $lineNumber\n";
            echo "  Crochets: {$currentJson['brackets']} | Parenthèses: {$currentJson['parentheses']}\n";

            if ($currentJson['brackets'] !== 0) {
                echo "  ❌ ERREUR: Crochets non équilibrés!\n";
            }
            if ($currentJson['parentheses'] !== 0) {
                echo "  ❌ ERREUR: Parenthèses non équilibrées!\n";
            }

            $jsonStructures[] = $currentJson;
            $currentJson = null;
        }
    }
}

// Si on a une structure @json non fermée
if ($currentJson !== null) {
    echo "❌ ERREUR: Structure @json non fermée commencée ligne {$currentJson['start_line']}\n";
}

echo "\n2. Recherche de patterns problématiques...\n";

// Rechercher des patterns spécifiques qui peuvent causer des erreurs
$problematicPatterns = [
    '/\[\s*[^}\]]*\?\s*$/' => 'Condition ternaire non fermée avec crochet',
    '/collect\([^)]*\?\s*$/' => 'Fonction collect non fermée',
    '/@json\([^)]*\[.*$/' => 'Structure @json avec crochet non fermé'
];

foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;

    foreach ($problematicPatterns as $pattern => $description) {
        if (preg_match($pattern, $line)) {
            echo "Ligne $lineNumber: $description\n";
            echo "  Contenu: " . trim($line) . "\n";
        }
    }
}

echo "\n3. Recherche des lignes avec conditions ternaires complexes...\n";

foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;

    // Lignes avec des conditions ternaires dans @json
    if (strpos($line, '@json') !== false && strpos($line, '?') !== false) {
        echo "Ligne $lineNumber: Condition ternaire dans @json\n";
        echo "  Contenu: " . trim($line) . "\n";

        // Vérifier l'équilibre des crochets sur cette ligne
        $openBrackets = substr_count($line, '[');
        $closeBrackets = substr_count($line, ']');
        $openParens = substr_count($line, '(');
        $closeParens = substr_count($line, ')');

        if ($openBrackets !== $closeBrackets) {
            echo "  ❌ Crochets non équilibrés: $openBrackets ouvert(s), $closeBrackets fermé(s)\n";
        }
        if ($openParens !== $closeParens) {
            echo "  ❌ Parenthèses non équilibrées: $openParens ouvert(s), $closeParens fermé(s)\n";
        }
    }
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";
?>
