<?php

// Script simple pour lire un fichier Excel avec Laravel Excel existant
require_once 'vendor/autoload.php';

// Créer une application Laravel minimale
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $filePath = __DIR__ . '/DATA/LISTE ETUIANTS2425 OKKK.xlsx';
    
    if (!file_exists($filePath)) {
        echo "Fichier non trouvé: $filePath" . PHP_EOL;
        exit(1);
    }
    
    echo "Fichier trouvé: $filePath" . PHP_EOL;
    echo "Taille du fichier: " . round(filesize($filePath) / 1024, 2) . " KB" . PHP_EOL;
    
    // Utiliser Excel avec la classe Import personnalisée
    $import = new class implements \Maatwebsite\Excel\Concerns\ToArray {
        public function array(array $array): array
        {
            return $array;
        }
    };
    
    $data = \Maatwebsite\Excel\Facades\Excel::toArray($import, $filePath);
    
    if (empty($data) || empty($data[0])) {
        echo "Aucune donnée trouvée dans le fichier" . PHP_EOL;
        exit(1);
    }
    
    $sheet = $data[0];
    $totalRows = count($sheet);
    
    echo "Total des lignes: $totalRows" . PHP_EOL;
    
    // Analyser les premières lignes pour comprendre la structure
    echo PHP_EOL . "=== STRUCTURE DU FICHIER ===" . PHP_EOL;
    
    // En-têtes (première ligne non vide)
    $headers = null;
    for ($i = 0; $i < min(5, $totalRows); $i++) {
        $row = array_filter($sheet[$i], function($cell) {
            return $cell !== null && trim($cell) !== '';
        });
        
        if (!empty($row) && $headers === null) {
            $headers = $row;
            echo "En-têtes trouvés à la ligne " . ($i + 1) . ":" . PHP_EOL;
            foreach ($headers as $index => $header) {
                echo "  Colonne $index: '$header'" . PHP_EOL;
            }
            break;
        }
    }
    
    // Quelques exemples de données
    echo PHP_EOL . "=== EXEMPLES DE DONNÉES ===" . PHP_EOL;
    $dataStartRow = $headers ? 1 : 0; // Commence après les en-têtes
    $exampleCount = 0;
    
    for ($i = $dataStartRow; $i < $totalRows && $exampleCount < 10; $i++) {
        $row = $sheet[$i];
        $cleanRow = array_filter($row, function($cell) {
            return $cell !== null && trim($cell) !== '';
        });
        
        if (!empty($cleanRow)) {
            $exampleCount++;
            echo "Ligne " . ($i + 1) . ": ";
            
            // Associer avec les en-têtes si possible
            if ($headers) {
                $associatedData = [];
                foreach ($headers as $colIndex => $headerName) {
                    if (isset($row[$colIndex]) && $row[$colIndex] !== null && trim($row[$colIndex]) !== '') {
                        $associatedData[$headerName] = $row[$colIndex];
                    }
                }
                echo json_encode($associatedData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            } else {
                echo json_encode($cleanRow, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            }
        }
    }
    
    // Statistiques
    echo PHP_EOL . "=== STATISTIQUES ===" . PHP_EOL;
    echo "Lignes avec données: " . $exampleCount . PHP_EOL;
    
    if ($headers) {
        echo "Colonnes détectées: " . count($headers) . PHP_EOL;
        
        // Analyser le contenu pour deviner les types de données
        echo PHP_EOL . "=== ANALYSE DES COLONNES ===" . PHP_EOL;
        foreach ($headers as $colIndex => $headerName) {
            $sampleValues = [];
            for ($i = $dataStartRow; $i < min($totalRows, $dataStartRow + 20); $i++) {
                if (isset($sheet[$i][$colIndex]) && $sheet[$i][$colIndex] !== null && trim($sheet[$i][$colIndex]) !== '') {
                    $sampleValues[] = $sheet[$i][$colIndex];
                }
            }
            
            if (!empty($sampleValues)) {
                echo "Colonne '$headerName':" . PHP_EOL;
                echo "  - Échantillon: " . json_encode(array_slice($sampleValues, 0, 3), JSON_UNESCAPED_UNICODE) . PHP_EOL;
                
                // Deviner le type
                $isNumeric = array_reduce($sampleValues, function($carry, $value) {
                    return $carry && is_numeric($value);
                }, true);
                
                $isDate = array_reduce($sampleValues, function($carry, $value) {
                    return $carry && (preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $value) || preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $value));
                }, true);
                
                $type = $isDate ? 'DATE' : ($isNumeric ? 'NUMERIC' : 'TEXT');
                echo "  - Type probable: $type" . PHP_EOL;
            }
        }
    }
    
} catch (Exception $e) {
    echo "Erreur lors de la lecture du fichier:" . PHP_EOL;
    echo "Message: " . $e->getMessage() . PHP_EOL;
    echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . PHP_EOL;
}