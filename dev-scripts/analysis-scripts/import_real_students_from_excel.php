#!/usr/bin/env php
<?php
/**
 * Script pour importer les 2451 vrais étudiants depuis le fichier Excel
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel pour accéder aux modèles
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Fonction pour extraire filière depuis le nom de classe
function extractFiliereFromClasse($libelle_classe) {
    $classe_upper = strtoupper($libelle_classe);
    
    if (strpos($classe_upper, 'BATIMENT') !== false || strpos($classe_upper, 'BÂTIMENT') !== false) {
        return 'BATIMENT';
    } elseif (strpos($classe_upper, 'TRAVAUX PUBLICS') !== false || strpos($classe_upper, 'TP') !== false) {
        return 'TRAVAUX_PUBLICS';
    } elseif (strpos($classe_upper, 'GÉOMÈTRE') !== false || strpos($classe_upper, 'TOPOGRAPHE') !== false) {
        return 'GEOMETRE_TOPOGRAPHE';
    } elseif (strpos($classe_upper, 'TRANSPORT') !== false || strpos($classe_upper, 'INFRASTRUCTURE') !== false) {
        return 'TRANSPORT';
    } elseif (strpos($classe_upper, 'ARCHITECTURE') !== false) {
        return 'ARCHITECTURE';
    }
    return 'AUTRES';
}

// Fonction pour séparer nom et prénoms
function separerNomPrenoms($nom_complet) {
    $parts = explode(' ', trim($nom_complet));
    if (count($parts) <= 2) {
        return [
            'nom' => $parts[0] ?? '',
            'prenoms' => $parts[1] ?? ''
        ];
    }
    
    // Prendre les 2 premiers mots comme nom, le reste comme prénoms
    $nom = implode(' ', array_slice($parts, 0, 2));
    $prenoms = implode(' ', array_slice($parts, 2));
    
    return [
        'nom' => $nom,
        'prenoms' => $prenoms
    ];
}

try {
    $excelFile = 'DATA/LISTE ETUIANTS2425 OKKK.xlsx';
    
    if (!file_exists($excelFile)) {
        throw new Exception("Fichier Excel non trouvé: {$excelFile}");
    }
    
    echo "📊 Lecture du fichier Excel avec PhpSpreadsheet...\n";
    
    // Charger le fichier Excel
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    
    echo "✅ Fichier chargé : {$highestRow} lignes, colonnes jusqu'à {$highestColumn}\n";
    
    // Lire les en-têtes (ligne 1)
    $headers = [];
    for ($col = 'A'; $col <= $highestColumn; ++$col) {
        $headers[$col] = $worksheet->getCell($col . '1')->getFormattedValue();
    }
    
    echo "📋 En-têtes détectées:\n";
    foreach ($headers as $col => $header) {
        echo "  {$col}: {$header}\n";
    }
    
    // Créer la correspondance colonnes
    $colonnes = [
        'MAT' => null,
        'NOMP' => null,
        'Datenais_El' => null,
        'Lieunais_El' => null, 
        'Genre_El' => null,
        'Code_Nte' => null,
        'Contact' => null,
        'Libelle_classe' => null,
        'Code_niveau' => null,
        'Redoublant' => null,
        'Affecter' => null
    ];
    
    // Mapper les colonnes automatiquement
    foreach ($headers as $col => $header) {
        if (isset($colonnes[$header])) {
            $colonnes[$header] = $col;
        }
    }
    
    echo "\n📍 Mapping des colonnes:\n";
    foreach ($colonnes as $champ => $col) {
        if ($col) {
            echo "  ✅ {$champ} -> Colonne {$col}\n";
        } else {
            echo "  ❌ {$champ} -> NON TROUVÉ\n";
        }
    }
    
    // Vérifier les colonnes essentielles
    if (!$colonnes['MAT'] || !$colonnes['NOMP'] || !$colonnes['Libelle_classe']) {
        throw new Exception("Colonnes essentielles manquantes (MAT, NOMP, Libelle_classe)");
    }
    
    echo "\n🎯 Import des étudiants réels...\n";
    
    // Récupérer les correspondances DB
    $filieres = DB::table('esbtp_filieres')->pluck('id', 'nom')->toArray();
    $niveaux = DB::table('esbtp_niveau_etudes')->pluck('id', 'libelle')->toArray();
    $classes = DB::table('esbtp_classes')->pluck('id', 'libelle')->toArray();
    $anneeUniv = DB::table('esbtp_annee_universitaires')->where('est_actif', true)->first();
    
    if (!$anneeUniv) {
        throw new Exception("Aucune année universitaire active trouvée");
    }
    
    $compteurs = [
        'total_lignes' => 0,
        'etudiants_crees' => 0,
        'erreurs' => 0,
        'classes_manquantes' => []
    ];
    
    // Parcourir toutes les lignes de données (à partir de la ligne 2)
    for ($row = 2; $row <= $highestRow; $row++) {
        $compteurs['total_lignes']++;
        
        try {
            // Extraire les données de la ligne
            $donnees = [];
            foreach ($colonnes as $champ => $col) {
                if ($col) {
                    $valeur = $worksheet->getCell($col . $row)->getFormattedValue();
                    $donnees[$champ] = trim($valeur);
                }
            }
            
            // Vérifier les données essentielles
            if (empty($donnees['MAT']) || empty($donnees['NOMP']) || empty($donnees['Libelle_classe'])) {
                echo "⚠️ Ligne {$row}: Données essentielles manquantes\n";
                $compteurs['erreurs']++;
                continue;
            }
            
            $libelle_classe = $donnees['Libelle_classe'];
            $filiere_nom = extractFiliereFromClasse($libelle_classe);
            
            // Vérifier si la classe existe
            if (!isset($classes[$libelle_classe])) {
                if (!in_array($libelle_classe, $compteurs['classes_manquantes'])) {
                    $compteurs['classes_manquantes'][] = $libelle_classe;
                    echo "❌ Classe manquante: {$libelle_classe}\n";
                }
                $compteurs['erreurs']++;
                continue;
            }
            
            // Séparer nom et prénoms
            $nomPrenoms = separerNomPrenoms($donnees['NOMP']);
            
            // Préparer les données étudiant
            $etudiantData = [
                'matricule' => $donnees['MAT'],
                'nom' => $nomPrenoms['nom'],
                'prenoms' => $nomPrenoms['prenoms'],
                'sexe' => $donnees['Genre_El'] === 'M' ? 'M' : 'F',
                'nationalite' => $donnees['Code_Nte'] ?? 'IV',
                'classe_id' => $classes[$libelle_classe],
                'annee_universitaire_id' => $anneeUniv->id,
                'is_redoublant' => !empty($donnees['Redoublant']) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Gérer la date de naissance
            if (!empty($donnees['Datenais_El'])) {
                try {
                    $date = DateTime::createFromFormat('Y-m-d', $donnees['Datenais_El']);
                    if (!$date) {
                        $date = DateTime::createFromFormat('d/m/Y', $donnees['Datenais_El']);
                    }
                    if ($date) {
                        $etudiantData['date_naissance'] = $date->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    // Ignorer les erreurs de date
                }
            }
            
            // Autres champs optionnels
            if (!empty($donnees['Lieunais_El'])) {
                $etudiantData['lieu_naissance'] = $donnees['Lieunais_El'];
            }
            
            if (!empty($donnees['Contact'])) {
                $etudiantData['telephone'] = $donnees['Contact'];
            }
            
            // Générer email basé sur matricule
            $etudiantData['email'] = strtolower($donnees['MAT']) . '@esbtp.edu.ci';
            
            // Insérer l'étudiant
            DB::table('esbtp_etudiants')->insert($etudiantData);
            $compteurs['etudiants_crees']++;
            
            if ($compteurs['etudiants_crees'] % 100 == 0) {
                echo "✅ {$compteurs['etudiants_crees']} étudiants importés...\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur ligne {$row}: " . $e->getMessage() . "\n";
            $compteurs['erreurs']++;
        }
    }
    
    echo "\n=== RÉSULTATS D'IMPORT ===\n";
    echo "📊 Total lignes traitées: {$compteurs['total_lignes']}\n";
    echo "✅ Étudiants créés: {$compteurs['etudiants_crees']}\n";
    echo "❌ Erreurs: {$compteurs['erreurs']}\n";
    
    if (!empty($compteurs['classes_manquantes'])) {
        echo "\n⚠️ Classes manquantes ({} classes):\n" . count($compteurs['classes_manquantes']);
        foreach (array_slice($compteurs['classes_manquantes'], 0, 10) as $classe) {
            echo "  - {$classe}\n";
        }
        if (count($compteurs['classes_manquantes']) > 10) {
            echo "  ... et " . (count($compteurs['classes_manquantes']) - 10) . " autres\n";
        }
    }
    
    echo "\n🎉 Import terminé avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}