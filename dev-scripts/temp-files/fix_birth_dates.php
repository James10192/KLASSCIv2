#!/usr/bin/env php
<?php
/**
 * Script pour corriger les dates de naissance des étudiants
 * à partir du fichier JSON des données extraites
 */

// Configuration de la base de données
$host = 'localhost';
$dbname = 'esbtp_migration_test';
$username = 'root';
$password = '';

try {
    // Connexion PDO directe
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📊 Connexion à la base de données réussie\n";
    
    // Charger les données JSON
    if (!file_exists('students_data.json')) {
        throw new Exception("Fichier students_data.json non trouvé");
    }
    
    $jsonData = file_get_contents('students_data.json');
    $data = json_decode($jsonData, true);
    
    if (!$data || !isset($data['etudiants'])) {
        throw new Exception("Données JSON invalides");
    }
    
    echo "✅ Données JSON chargées : " . count($data['etudiants']) . " étudiants\n";
    
    // Préparer la requête de mise à jour
    $updateStmt = $pdo->prepare("
        UPDATE esbtp_etudiants 
        SET date_naissance = :date_naissance, 
            updated_at = :updated_at 
        WHERE matricule = :matricule
    ");
    
    $compteurs = [
        'total' => count($data['etudiants']),
        'mis_a_jour' => 0,
        'avec_date' => 0,
        'sans_date' => 0,
        'erreurs' => 0
    ];
    
    echo "\n🎯 Correction des dates de naissance...\n";
    
    foreach ($data['etudiants'] as $index => $etudiant) {
        try {
            $matricule = $etudiant['matricule'];
            $date_naissance = null;
            
            // Vérifier si une date de naissance est disponible
            if (isset($etudiant['date_naissance']) && !empty($etudiant['date_naissance'])) {
                $dateString = $etudiant['date_naissance'];
                
                // Essayer différents formats de date
                $formats = [
                    'Y-m-d H:i:s',    // 2003-08-29 00:00:00
                    'Y-m-d',          // 2003-08-29
                    'd/m/Y',          // 29/08/2003
                    'd-m-Y',          // 29-08-2003
                    'm/d/Y',          // 08/29/2003
                ];
                
                foreach ($formats as $format) {
                    $date = DateTime::createFromFormat($format, $dateString);
                    if ($date && $date->format($format) === $dateString) {
                        // Vérifier que la date est réaliste (entre 1980 et 2010)
                        $year = (int)$date->format('Y');
                        if ($year >= 1980 && $year <= 2010) {
                            $date_naissance = $date->format('Y-m-d');
                            break;
                        }
                    }
                }
                
                if ($date_naissance) {
                    $compteurs['avec_date']++;
                } else {
                    echo "⚠️ Date invalide pour {$matricule}: {$dateString}\n";
                    $compteurs['sans_date']++;
                }
            } else {
                $compteurs['sans_date']++;
            }
            
            // Mettre à jour l'étudiant (même s'il n'y a pas de date)
            $updateStmt->execute([
                ':matricule' => $matricule,
                ':date_naissance' => $date_naissance,
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $compteurs['mis_a_jour']++;
            
            if ($compteurs['mis_a_jour'] % 250 == 0) {
                echo "✅ {$compteurs['mis_a_jour']} étudiants traités...\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur étudiant {$etudiant['matricule']}: " . $e->getMessage() . "\n";
            $compteurs['erreurs']++;
        }
    }
    
    // Résultats finaux
    echo "\n=== RÉSULTATS DE CORRECTION DES DATES ===\n";
    echo "📊 Total étudiants traités: {$compteurs['total']}\n";
    echo "✅ Étudiants mis à jour: {$compteurs['mis_a_jour']}\n";
    echo "📅 Avec date de naissance: {$compteurs['avec_date']}\n";
    echo "❓ Sans date de naissance: {$compteurs['sans_date']}\n";
    echo "❌ Erreurs: {$compteurs['erreurs']}\n";
    
    // Vérification finale
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM esbtp_etudiants WHERE date_naissance IS NOT NULL");
    $total_avec_dates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n📈 Vérification : {$total_avec_dates} étudiants ont maintenant une date de naissance\n";
    
    // Statistiques des âges
    echo "\n📊 Répartition par année de naissance :\n";
    $stmt = $pdo->query("
        SELECT YEAR(date_naissance) as annee_naissance, COUNT(*) as total 
        FROM esbtp_etudiants 
        WHERE date_naissance IS NOT NULL 
        GROUP BY YEAR(date_naissance) 
        ORDER BY annee_naissance
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $age_actuel = date('Y') - $row['annee_naissance'];
        echo "  {$row['annee_naissance']}: {$row['total']} étudiants (âge ~{$age_actuel} ans)\n";
    }
    
    // Âge moyen
    $stmt = $pdo->query("
        SELECT AVG(YEAR(CURDATE()) - YEAR(date_naissance)) as age_moyen 
        FROM esbtp_etudiants 
        WHERE date_naissance IS NOT NULL
    ");
    $age_moyen = $stmt->fetch(PDO::FETCH_ASSOC)['age_moyen'];
    if ($age_moyen) {
        echo "\n🎂 Âge moyen des étudiants: " . round($age_moyen, 1) . " ans\n";
    }
    
    if ($compteurs['avec_date'] > 2000) {
        echo "\n🎉 SUCCÈS ! Les dates de naissance ont été corrigées.\n";
    } else {
        echo "\n⚠️ Correction partielle. Vérifiez le format des dates dans l'Excel.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}