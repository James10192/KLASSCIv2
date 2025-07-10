<?php
/**
 * Script d'analyse détaillée des erreurs ParseError dans les fichiers Blade
 * Spécialement conçu pour identifier l'erreur "Unclosed '[' does not match ')'"
 */

require_once 'vendor/autoload.php';

echo "🔍 ANALYSE DÉTAILLÉE DES ERREURS BLADE\n";
echo "====================================\n\n";

$fichier = 'resources/views/esbtp/comptabilite/dashboard-avance.blade.php';

if (!file_exists($fichier)) {
    echo "❌ Fichier non trouvé: $fichier\n";
    exit(1);
}

$contenu = file_get_contents($fichier);
$lignes = explode("\n", $contenu);

echo "📊 Analyse de " . count($lignes) . " lignes...\n\n";

// Patterns problématiques connus
$patterns_dangereux = [
    // Crochets et parenthèses mal équilibrés
    '/\@json\s*\([^)]*\[/',
    '/\@json\s*\([^)]*\)[^,;}\s]*\[/',
    '/\[.*?\)\s*(?!,|\s*\]|\s*$)/',
    '/\([^)]*\[[^]]*\)[^,;}\s]*/',

    // Directives @json avec problèmes de syntaxe
    '/\@json\s*\([^)]*\([^)]*\)[^)]*\)/',
    '/\@json\([^)]*?\?\s*[^:]*?:.*?\)[^,;}\s]*\[/',

    // Expressions ternaires complexes dans @json
    '/\@json\([^)]*\?\s*[^:]*:[^)]*\)\s*[^,;}\s]/',
];

$erreurs_trouvees = [];

foreach ($lignes as $numero => $ligne) {
    $ligne_numero = $numero + 1;
    $ligne_clean = trim($ligne);

    if (empty($ligne_clean) || strpos($ligne_clean, '//') === 0) {
        continue;
    }

    // Vérifier chaque pattern dangereux
    foreach ($patterns_dangereux as $pattern) {
        if (preg_match($pattern, $ligne)) {
            $erreurs_trouvees[] = [
                'ligne' => $ligne_numero,
                'contenu' => $ligne_clean,
                'pattern' => $pattern,
                'type' => 'Pattern dangereux détecté'
            ];
        }
    }

    // Analyse spécifique des directives @json
    if (preg_match('/\@json\s*\(/', $ligne)) {
        echo "🔎 Ligne $ligne_numero - Directive @json trouvée:\n";
        echo "   " . $ligne_clean . "\n";

        // Compter les parenthèses et crochets
        $parens_ouvertes = substr_count($ligne, '(');
        $parens_fermees = substr_count($ligne, ')');
        $crochets_ouverts = substr_count($ligne, '[');
        $crochets_fermes = substr_count($ligne, ']');

        echo "   📊 Parenthèses: $parens_ouvertes ouvertes, $parens_fermees fermées\n";
        echo "   📊 Crochets: $crochets_ouverts ouverts, $crochets_fermes fermés\n";

        if ($parens_ouvertes != $parens_fermees) {
            $erreurs_trouvees[] = [
                'ligne' => $ligne_numero,
                'contenu' => $ligne_clean,
                'type' => 'Parenthèses non équilibrées',
                'details' => "$parens_ouvertes ouvertes vs $parens_fermees fermées"
            ];
            echo "   ❌ ERREUR: Parenthèses non équilibrées!\n";
        }

        if ($crochets_ouverts != $crochets_fermes) {
            $erreurs_trouvées[] = [
                'ligne' => $ligne_numero,
                'contenu' => $ligne_clean,
                'type' => 'Crochets non équilibrés',
                'details' => "$crochets_ouverts ouverts vs $crochets_fermes fermés"
            ];
            echo "   ❌ ERREUR: Crochets non équilibrés!\n";
        }

        // Vérifier la syntaxe de la directive @json
        if (preg_match('/\@json\s*\((.*)\)/s', $ligne, $matches)) {
            $contenu_json = $matches[1];

            // Vérifier les conditions ternaires malformées
            if (preg_match('/\?\s*[^:]*$/', $contenu_json)) {
                $erreurs_trouvees[] = [
                    'ligne' => $ligne_numero,
                    'contenu' => $ligne_clean,
                    'type' => 'Condition ternaire incomplète',
                    'details' => 'Opérateur ? sans : correspondant'
                ];
                echo "   ❌ ERREUR: Condition ternaire incomplète!\n";
            }

            // Vérifier les virgules à la fin
            if (preg_match('/\),\s*$/', $ligne) && !preg_match('/\],\s*$/', $ligne)) {
                echo "   ⚠️  ATTENTION: Virgule après directive @json\n";
            }
        }

        echo "\n";
    }

    // Vérifier les structures JavaScript complexes
    if (preg_match('/(data|labels|datasets):\s*\@json/', $ligne)) {
        echo "🔎 Ligne $ligne_numero - Structure JavaScript avec @json:\n";
        echo "   " . $ligne_clean . "\n";

        // Analyser la structure complète
        $contexte_js = '';
        for ($i = max(0, $numero - 2); $i <= min(count($lignes) - 1, $numero + 2); $i++) {
            $contexte_js .= trim($lignes[$i]) . "\n";
        }

        // Vérifier les accolades JavaScript
        $accolades_ouvertes = substr_count($contexte_js, '{');
        $accolades_fermees = substr_count($contexte_js, '}');

        if ($accolades_ouvertes != $accolades_fermees) {
            echo "   ⚠️  ATTENTION: Déséquilibre des accolades JavaScript dans le contexte\n";
        }

        echo "\n";
    }
}

// Résumé des erreurs
echo "\n📋 RÉSUMÉ DES ERREURS TROUVÉES\n";
echo "=============================\n";

if (empty($erreurs_trouvees)) {
    echo "✅ Aucune erreur de syntaxe évidente détectée par l'analyse statique.\n";
    echo "   L'erreur pourrait être due à:\n";
    echo "   - Des expressions PHP complexes dans @json\n";
    echo "   - Des interactions entre directives Blade\n";
    echo "   - Des problèmes de contexte multi-lignes\n\n";
} else {
    foreach ($erreurs_trouvees as $erreur) {
        echo "❌ Ligne {$erreur['ligne']}: {$erreur['type']}\n";
        echo "   Contenu: {$erreur['contenu']}\n";
        if (isset($erreur['details'])) {
            echo "   Détails: {$erreur['details']}\n";
        }
        echo "\n";
    }
}

// Test final avec Bootstrap Laravel
echo "🧪 TEST FINAL - Rendu de la vue\n";
echo "==============================\n";

try {
    // Bootstrap Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    // Tenter de compiler la vue
    $blade = app('view');
    $compiled = $blade->make('esbtp.comptabilite.dashboard-avance');

    echo "✅ La vue se compile sans erreur ParseError!\n";
    echo "   Le problème peut être lié aux données passées à la vue.\n";

} catch (Facade\Ignition\Exceptions\ViewException $e) {
    echo "❌ ERREUR DE VUE CONFIRMÉE:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . "\n";
    echo "   Ligne: " . $e->getLine() . "\n";

    // Extraire des informations plus détaillées
    $message = $e->getMessage();
    if (preg_match('/line (\d+)/', $message, $matches)) {
        $ligne_erreur = $matches[1];
        echo "   Ligne d'erreur extraite: $ligne_erreur\n";

        if (isset($lignes[$ligne_erreur - 1])) {
            echo "   Contenu de la ligne: " . trim($lignes[$ligne_erreur - 1]) . "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERREUR GÉNÉRALE:\n";
    echo "   " . $e->getMessage() . "\n";
}

echo "\n🎯 RECOMMANDATIONS\n";
echo "=================\n";
echo "1. Vérifiez les directives @json aux lignes identifiées\n";
echo "2. Simplifiez les expressions ternaires complexes\n";
echo "3. Assurez-vous que toutes les parenthèses et crochets sont équilibrés\n";
echo "4. Testez en isolant chaque directive @json une par une\n";
echo "5. Utilisez php artisan view:clear pour vider le cache\n";
