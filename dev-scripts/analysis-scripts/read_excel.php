<?php

require_once 'vendor/autoload.php';

use Maatwebsite\Excel\Facades\Excel;

// Créer une application Laravel minimale pour utiliser Excel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $path = __DIR__ . '/DATA/LISTE ETUIANTS2425 OKKK.xlsx';
    echo "Chemin du fichier: " . $path . PHP_EOL;
    echo "Le fichier existe: " . (file_exists($path) ? 'OUI' : 'NON') . PHP_EOL;
    
    if (file_exists($path)) {
        // Utiliser la façade Excel
        $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $path);
        
        echo "Nombre de feuilles: " . count($data) . PHP_EOL;
        
        if (count($data) > 0) {
            echo "Premières lignes de la première feuille:" . PHP_EOL;
            $sheet = $data[0];
            $totalRows = count($sheet);
            echo "Total des lignes: " . $totalRows . PHP_EOL;
            
            // Afficher les 15 premières lignes
            for ($i = 0; $i < min(15, $totalRows); $i++) {
                $row = $sheet[$i];
                // Nettoyer les valeurs nulles
                $cleanRow = array_filter($row, function($value) {
                    return $value !== null && $value !== '';
                });
                
                if (!empty($cleanRow)) {
                    echo "Ligne " . ($i + 1) . ": " . json_encode($cleanRow, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                }
            }
            
            // Analyser la structure des colonnes
            if ($totalRows > 1) {
                echo PHP_EOL . "=== ANALYSE DE LA STRUCTURE ===" . PHP_EOL;
                $headers = $sheet[0]; // Première ligne comme en-têtes
                echo "En-têtes détectés: " . json_encode(array_filter($headers), JSON_UNESCAPED_UNICODE) . PHP_EOL;
                
                // Exemple de quelques lignes de données
                echo PHP_EOL . "Exemples de données:" . PHP_EOL;
                for ($i = 1; $i < min(6, $totalRows); $i++) {
                    $row = $sheet[$i];
                    $cleanRow = array_filter($row);
                    if (!empty($cleanRow)) {
                        echo "Étudiant " . $i . ": " . json_encode($cleanRow, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    }
                }
            }
        }
    } else {
        echo "Fichier non trouvé!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . PHP_EOL;
    echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . PHP_EOL;
}