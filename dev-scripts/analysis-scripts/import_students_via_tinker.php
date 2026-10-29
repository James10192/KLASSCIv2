<?php
/**
 * Script d'import des étudiants via Artisan Tinker
 * À exécuter avec: php artisan tinker --execute="require 'import_students_via_tinker.php';"
 */

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPAnneeUniversitaire;

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
        echo "❌ Fichier Excel non trouvé: {$excelFile}\n";
        return;
    }
    
    echo "📊 Lecture du fichier Excel avec PhpSpreadsheet...\n";
    
    // Charger le fichier Excel
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    echo "✅ Fichier chargé : {$highestRow} lignes\n";
    
    // Récupérer les correspondances DB
    $classes = ESBTPClasse::pluck('id', 'libelle')->toArray();
    $anneeUniv = ESBTPAnneeUniversitaire::where('est_actif', true)->first();
    
    if (!$anneeUniv) {
        echo "❌ Aucune année universitaire active trouvée\n";
        return;
    }
    
    echo "🎯 Année universitaire: {$anneeUniv->libelle}\n";
    echo "📚 Classes disponibles: " . count($classes) . "\n";
    
    $compteurs = [
        'total_lignes' => 0,
        'etudiants_crees' => 0,
        'erreurs' => 0
    ];
    
    // Parcourir les lignes de données (à partir de la ligne 2)
    for ($row = 2; $row <= min(10, $highestRow); $row++) { // Test avec 10 lignes d'abord
        $compteurs['total_lignes']++;
        
        try {
            // Lire les colonnes importantes (positions connues du fichier Excel)
            $matricule = $worksheet->getCell('A' . $row)->getFormattedValue(); // MAT
            $nom_complet = $worksheet->getCell('B' . $row)->getFormattedValue(); // NOMP
            $date_naissance = $worksheet->getCell('D' . $row)->getFormattedValue(); // Datenais_El
            $lieu_naissance = $worksheet->getCell('E' . $row)->getFormattedValue(); // Lieunais_El
            $sexe = $worksheet->getCell('F' . $row)->getFormattedValue(); // Genre_El
            $nationalite = $worksheet->getCell('G' . $row)->getFormattedValue(); // Code_Nte
            $contact = $worksheet->getCell('H' . $row)->getFormattedValue(); // Contact
            $libelle_classe = $worksheet->getCell('J' . $row)->getFormattedValue(); // Libelle_classe
            $niveau = $worksheet->getCell('K' . $row)->getFormattedValue(); // Code_niveau
            $redoublant = $worksheet->getCell('N' . $row)->getFormattedValue(); // Redoublant
            
            // Vérifier les données essentielles
            if (empty($matricule) || empty($nom_complet) || empty($libelle_classe)) {
                echo "⚠️ Ligne {$row}: Données essentielles manquantes\n";
                $compteurs['erreurs']++;
                continue;
            }
            
            // Vérifier si la classe existe
            if (!isset($classes[$libelle_classe])) {
                echo "❌ Ligne {$row}: Classe manquante: {$libelle_classe}\n";
                $compteurs['erreurs']++;
                continue;
            }
            
            // Séparer nom et prénoms
            $nomPrenoms = separerNomPrenoms($nom_complet);
            
            // Créer l'étudiant
            $etudiant = new ESBTPEtudiant();
            $etudiant->matricule = $matricule;
            $etudiant->nom = $nomPrenoms['nom'];
            $etudiant->prenoms = $nomPrenoms['prenoms'];
            $etudiant->sexe = ($sexe === 'M') ? 'M' : 'F';
            $etudiant->nationalite = $nationalite ?: 'IV';
            $etudiant->classe_id = $classes[$libelle_classe];
            $etudiant->annee_universitaire_id = $anneeUniv->id;
            $etudiant->is_redoublant = !empty($redoublant) ? 1 : 0;
            $etudiant->email = strtolower($matricule) . '@esbtp.edu.ci';
            
            // Gérer la date de naissance
            if (!empty($date_naissance)) {
                try {
                    $date = \DateTime::createFromFormat('Y-m-d', $date_naissance);
                    if (!$date) {
                        $date = \DateTime::createFromFormat('d/m/Y', $date_naissance);
                    }
                    if ($date) {
                        $etudiant->date_naissance = $date->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    // Ignorer les erreurs de date
                }
            }
            
            // Autres champs optionnels
            if (!empty($lieu_naissance)) {
                $etudiant->lieu_naissance = $lieu_naissance;
            }
            
            if (!empty($contact)) {
                $etudiant->telephone = $contact;
            }
            
            $etudiant->save();
            $compteurs['etudiants_crees']++;
            
            echo "✅ Ligne {$row}: {$etudiant->matricule} - {$etudiant->nom} {$etudiant->prenoms} - {$libelle_classe}\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur ligne {$row}: " . $e->getMessage() . "\n";
            $compteurs['erreurs']++;
        }
    }
    
    echo "\n=== RÉSULTATS D'IMPORT TEST ===\n";
    echo "📊 Total lignes traitées: {$compteurs['total_lignes']}\n";
    echo "✅ Étudiants créés: {$compteurs['etudiants_crees']}\n";
    echo "❌ Erreurs: {$compteurs['erreurs']}\n";
    
    if ($compteurs['etudiants_crees'] > 0) {
        echo "\n🎉 Test réussi ! Prêt pour l'import complet.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
}