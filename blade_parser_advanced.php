<?php
/**
 * Script avancé d'analyse des erreurs Blade
 * Détection spécialisée pour les erreurs "Unclosed '[' does not match ')'"
 */

require_once 'vendor/autoload.php';

class BladeAdvancedParser {
    private $file_path;
    private $content;
    private $lines;
    private $errors = [];

    public function __construct($file_path) {
        $this->file_path = $file_path;
        $this->content = file_get_contents($file_path);
        $this->lines = explode("\n", $this->content);
    }

    public function analyzeFile() {
        echo "🔍 ANALYSE AVANCÉE DU FICHIER BLADE\n";
        echo "================================\n";
        echo "Fichier: {$this->file_path}\n\n";

        $this->checkJsonDirectives();
        $this->checkBracketBalance();
        $this->checkUseDirectives();
        $this->checkComplexExpressions();
        $this->checkNestedStructures();

        $this->reportErrors();

        return empty($this->errors);
    }

    private function checkJsonDirectives() {
        echo "🧪 Vérification des directives @json...\n";

        foreach ($this->lines as $lineNum => $line) {
            $realLineNum = $lineNum + 1;

            // Chercher les directives @json
            if (preg_match_all('/@json\s*\([^)]*\)/s', $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $jsonDirective = $match[0];
                    $this->analyzeJsonDirective($jsonDirective, $realLineNum);
                }
            }

            // Chercher les @json qui s'étendent sur plusieurs lignes
            if (strpos($line, '@json(') !== false && !$this->hasMatchingParenthesis($line, '@json(')) {
                $multiLineJson = $this->extractMultiLineJson($lineNum);
                if ($multiLineJson) {
                    $this->analyzeJsonDirective($multiLineJson['content'], $realLineNum, $multiLineJson['endLine']);
                }
            }
        }
    }

    private function analyzeJsonDirective($directive, $startLine, $endLine = null) {
        $lineInfo = $endLine ? "lignes {$startLine}-{$endLine}" : "ligne {$startLine}";

        // Compter les parenthèses et crochets
        $openParen = substr_count($directive, '(');
        $closeParen = substr_count($directive, ')');
        $openBracket = substr_count($directive, '[');
        $closeBracket = substr_count($directive, ']');
        $openBrace = substr_count($directive, '{');
        $closeBrace = substr_count($directive, '}');

        if ($openParen !== $closeParen) {
            $this->addError("Parenthèses non équilibrées dans @json ({$lineInfo})", $startLine, 'critical');
            echo "  ❌ Parenthèses: {$openParen} ouvertes, {$closeParen} fermées\n";
        }

        if ($openBracket !== $closeBracket) {
            $this->addError("Crochets non équilibrés dans @json ({$lineInfo})", $startLine, 'critical');
            echo "  ❌ Crochets: {$openBracket} ouverts, {$closeBracket} fermés\n";
        }

        if ($openBrace !== $closeBrace) {
            $this->addError("Accolades non équilibrées dans @json ({$lineInfo})", $startLine, 'warning');
            echo "  ⚠️  Accolades: {$openBrace} ouvertes, {$closeBrace} fermées\n";
        }

        // Vérifier la complexité de l'expression
        if (substr_count($directive, '?') > 0 && substr_count($directive, ':') > 0) {
            if (strlen($directive) > 200) {
                $this->addError("Expression ternaire trop complexe dans @json ({$lineInfo})", $startLine, 'warning');
                echo "  ⚠️  Expression ternaire complexe détectée\n";
            }
        }

        // Vérifier les chaînes de méthodes longues
        if (preg_match('/->.*->.*->/', $directive)) {
            $this->addError("Chaîne de méthodes complexe dans @json ({$lineInfo})", $startLine, 'info');
            echo "  ℹ️  Chaîne de méthodes complexe détectée\n";
        }
    }

    private function checkBracketBalance() {
        echo "\n🔗 Vérification de l'équilibrage global...\n";

        $stack = [];
        $bracketMap = [
            '(' => ')',
            '[' => ']',
            '{' => '}'
        ];

        foreach ($this->lines as $lineNum => $line) {
            $realLineNum = $lineNum + 1;

            for ($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];

                if (isset($bracketMap[$char])) {
                    // Caractère d'ouverture
                    $stack[] = [
                        'char' => $char,
                        'expected' => $bracketMap[$char],
                        'line' => $realLineNum,
                        'pos' => $i
                    ];
                } elseif (in_array($char, array_values($bracketMap))) {
                    // Caractère de fermeture
                    if (empty($stack)) {
                        $this->addError("Caractère de fermeture '{$char}' sans ouverture correspondante", $realLineNum, 'critical');
                        echo "  ❌ Ligne {$realLineNum}: '{$char}' sans ouverture\n";
                    } else {
                        $last = array_pop($stack);
                        if ($last['expected'] !== $char) {
                            $this->addError("Mauvais caractère de fermeture: attendu '{$last['expected']}', trouvé '{$char}'", $realLineNum, 'critical');
                            echo "  ❌ Ligne {$realLineNum}: attendu '{$last['expected']}', trouvé '{$char}'\n";
                        }
                    }
                }
            }
        }

        // Vérifier les caractères non fermés
        foreach ($stack as $unclosed) {
            $this->addError("Caractère '{$unclosed['char']}' non fermé", $unclosed['line'], 'critical');
            echo "  ❌ Ligne {$unclosed['line']}: '{$unclosed['char']}' non fermé\n";
        }
    }

    private function checkUseDirectives() {
        echo "\n📝 Vérification des directives @use...\n";

        foreach ($this->lines as $lineNum => $line) {
            $realLineNum = $lineNum + 1;

            if (preg_match('/@use\s*\(\s*["\']([^"\']*)["\']/', $line, $matches)) {
                $useStatement = $matches[1];

                // Vérifier si commence par un backslash
                if (strpos($useStatement, '\\') === 0) {
                    // Corriger selon le fix de Laravel
                    $corrected = ltrim($useStatement, '\\');
                    echo "  ⚠️  Ligne {$realLineNum}: @use avec backslash initial détecté\n";
                    echo "     Original: @use('{$useStatement}')\n";
                    echo "     Corrigé:  @use('{$corrected}')\n";

                    $this->addError("Directive @use avec backslash initial non supportée", $realLineNum, 'warning');
                }
            }
        }
    }

    private function checkComplexExpressions() {
        echo "\n🧮 Vérification des expressions complexes...\n";

        foreach ($this->lines as $lineNum => $line) {
            $realLineNum = $lineNum + 1;

            // Expressions ternaires imbriquées
            if (preg_match_all('/\?[^:]*\?[^:]*:/', $line, $matches)) {
                $this->addError("Expression ternaire imbriquée détectée", $realLineNum, 'warning');
                echo "  ⚠️  Ligne {$realLineNum}: Expression ternaire imbriquée\n";
            }

            // Fonctions anonymes dans Blade
            if (preg_match('/function\s*\([^)]*\)\s*{/', $line)) {
                $this->addError("Fonction anonyme dans template Blade", $realLineNum, 'info');
                echo "  ℹ️  Ligne {$realLineNum}: Fonction anonyme détectée\n";
            }

            // Chaînes très longues
            if (strlen(trim($line)) > 300) {
                $this->addError("Ligne très longue ({strlen(trim($line))} caractères)", $realLineNum, 'info');
                echo "  ℹ️  Ligne {$realLineNum}: Ligne très longue\n";
            }
        }
    }

    private function checkNestedStructures() {
        echo "\n🏗️  Vérification des structures imbriquées...\n";

        $depth = 0;
        $maxDepth = 0;

        foreach ($this->lines as $lineNum => $line) {
            $realLineNum = $lineNum + 1;

            // Compter la profondeur d'imbrication
            $opens = substr_count($line, '(') + substr_count($line, '[') + substr_count($line, '{');
            $closes = substr_count($line, ')') + substr_count($line, ']') + substr_count($line, '}');

            $depth += $opens - $closes;
            $maxDepth = max($maxDepth, $depth);

            if ($depth > 10) {
                $this->addError("Imbrication très profonde (niveau {$depth})", $realLineNum, 'warning');
                echo "  ⚠️  Ligne {$realLineNum}: Imbrication profondeur {$depth}\n";
            }
        }

        echo "  📊 Profondeur maximale d'imbrication: {$maxDepth}\n";
    }

    private function hasMatchingParenthesis($line, $directive) {
        $pos = strpos($line, $directive);
        if ($pos === false) return true;

        $openCount = substr_count($line, '(', $pos);
        $closeCount = substr_count($line, ')', $pos);

        return $openCount === $closeCount;
    }

    private function extractMultiLineJson($startLineNum) {
        $content = '';
        $openParens = 0;
        $endLine = $startLineNum;

        for ($i = $startLineNum; $i < count($this->lines); $i++) {
            $line = $this->lines[$i];
            $content .= $line . "\n";

            $openParens += substr_count($line, '(');
            $openParens -= substr_count($line, ')');

            if ($openParens <= 0) {
                $endLine = $i + 1;
                break;
            }

            // Limiter à 10 lignes pour éviter les boucles infinies
            if ($i - $startLineNum > 10) break;
        }

        return [
            'content' => trim($content),
            'endLine' => $endLine
        ];
    }

    private function addError($message, $line, $severity) {
        $this->errors[] = [
            'message' => $message,
            'line' => $line,
            'severity' => $severity
        ];
    }

    private function reportErrors() {
        echo "\n📋 RAPPORT D'ERREURS\n";
        echo "==================\n";

        if (empty($this->errors)) {
            echo "✅ Aucune erreur détectée!\n";
            return;
        }

        $critical = array_filter($this->errors, fn($e) => $e['severity'] === 'critical');
        $warnings = array_filter($this->errors, fn($e) => $e['severity'] === 'warning');
        $info = array_filter($this->errors, fn($e) => $e['severity'] === 'info');

        echo "📊 Statistiques:\n";
        echo "  - Erreurs critiques: " . count($critical) . "\n";
        echo "  - Avertissements: " . count($warnings) . "\n";
        echo "  - Informations: " . count($info) . "\n\n";

        echo "🚨 ERREURS CRITIQUES:\n";
        foreach ($critical as $error) {
            echo "  ❌ Ligne {$error['line']}: {$error['message']}\n";
        }

        if (!empty($warnings)) {
            echo "\n⚠️  AVERTISSEMENTS:\n";
            foreach ($warnings as $error) {
                echo "  ⚠️  Ligne {$error['line']}: {$error['message']}\n";
            }
        }

        if (!empty($info)) {
            echo "\nℹ️  INFORMATIONS:\n";
            foreach ($info as $error) {
                echo "  ℹ️  Ligne {$error['line']}: {$error['message']}\n";
            }
        }

        echo "\n💡 RECOMMANDATIONS:\n";
        if (!empty($critical)) {
            echo "  1. Corrigez d'abord les erreurs critiques\n";
            echo "  2. Vérifiez l'équilibrage des parenthèses/crochets\n";
            echo "  3. Simplifiez les expressions @json complexes\n";
        }
        echo "  4. Exécutez: php artisan view:clear\n";
        echo "  5. Testez avec: php test_view.php\n";
    }
}

// Utilisation
if ($argc < 2) {
    echo "Usage: php blade_parser_advanced.php <fichier_blade>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "❌ Fichier non trouvé: {$filePath}\n";
    exit(1);
}

$parser = new BladeAdvancedParser($filePath);
$success = $parser->analyzeFile();

echo "\n" . str_repeat("=", 50) . "\n";
echo $success ? "✅ ANALYSE TERMINÉE AVEC SUCCÈS" : "❌ ERREURS DÉTECTÉES";
echo "\n" . str_repeat("=", 50) . "\n";

exit($success ? 0 : 1);
