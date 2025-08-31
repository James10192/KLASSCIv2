#!/usr/bin/env php
<?php
/**
 * Script direct pour importer les 2451 vrais étudiants depuis le JSON
 * Sans dépendre de Laravel pour éviter les problèmes de dépendances
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
    
    // Récupérer les correspondances DB
    $classes = [];
    $result = $pdo->query("SELECT id, libelle FROM esbtp_classes");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $classes[$row['libelle']] = $row['id'];
    }
    
    // Récupérer l'année universitaire active
    $stmt = $pdo->query("SELECT id, libelle FROM esbtp_annee_universitaires WHERE est_actif = 1 LIMIT 1");
    $anneeUniv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anneeUniv) {
        throw new Exception("Aucune année universitaire active trouvée");
    }
    
    echo "🎯 Année universitaire: {$anneeUniv['libelle']}\n";
    echo "📚 Classes disponibles: " . count($classes) . "\n";
    
    // Vider la table des étudiants existants (optionnel)
    echo "🗑️  Suppression des étudiants existants...\n";
    $pdo->exec("DELETE FROM esbtp_etudiants");
    $pdo->exec("ALTER TABLE esbtp_etudiants AUTO_INCREMENT = 1");
    
    // Préparer la requête d'insertion
    $insertStmt = $pdo->prepare("
        INSERT INTO esbtp_etudiants (
            matricule, nom, prenoms, sexe, nationalite, date_naissance, lieu_naissance, 
            telephone, email, classe_id, annee_universitaire_id, statut,
            created_at, updated_at
        ) VALUES (
            :matricule, :nom, :prenoms, :sexe, :nationalite, :date_naissance, :lieu_naissance,
            :telephone, :email, :classe_id, :annee_universitaire_id, :statut,
            :created_at, :updated_at
        )
    ");
    
    $compteurs = [
        'total' => count($data['etudiants']),
        'crees' => 0,
        'erreurs' => 0,
        'classes_manquantes' => []
    ];
    
    echo "\n🎯 Import des étudiants...\n";
    
    foreach ($data['etudiants'] as $index => $etudiant) {
        try {
            // Vérifier si la classe existe
            $libelle_classe = $etudiant['libelle_classe'];
            
            if (!isset($classes[$libelle_classe])) {
                if (!in_array($libelle_classe, $compteurs['classes_manquantes'])) {
                    $compteurs['classes_manquantes'][] = $libelle_classe;
                    echo "❌ Classe manquante: {$libelle_classe}\n";
                }
                $compteurs['erreurs']++;
                continue;
            }
            
            // Gérer la date de naissance
            $date_naissance = null;
            if (isset($etudiant['date_naissance']) && !empty($etudiant['date_naissance'])) {
                $dateString = $etudiant['date_naissance'];
                try {
                    $date = DateTime::createFromFormat('Y-m-d', $dateString);
                    if (!$date) {
                        $date = DateTime::createFromFormat('d/m/Y', $dateString);
                    }
                    if ($date) {
                        $date_naissance = $date->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    // Ignorer les erreurs de date
                }
            }
            
            // Déterminer le statut (actif par défaut, mais peut être personnalisé selon is_redoublant)
            $statut = 'actif'; // Tous les étudiants importés sont actifs
            
            // Exécuter l'insertion
            $insertStmt->execute([
                ':matricule' => $etudiant['matricule'],
                ':nom' => $etudiant['nom'],
                ':prenoms' => $etudiant['prenoms'],
                ':sexe' => $etudiant['sexe'],
                ':nationalite' => $etudiant['nationalite'],
                ':date_naissance' => $date_naissance,
                ':lieu_naissance' => $etudiant['lieu_naissance'] ?? null,
                ':telephone' => $etudiant['telephone'] ?? null,
                ':email' => $etudiant['email'],
                ':classe_id' => $classes[$libelle_classe],
                ':annee_universitaire_id' => $anneeUniv['id'],
                ':statut' => $statut,
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $compteurs['crees']++;
            
            if ($compteurs['crees'] % 250 == 0) {
                echo "✅ {$compteurs['crees']} étudiants importés...\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur étudiant " . ($index + 1) . ": " . $e->getMessage() . "\n";
            $compteurs['erreurs']++;
        }
    }
    
    // Résultats finaux
    echo "\n=== RÉSULTATS D'IMPORT ===\n";
    echo "📊 Total étudiants traités: {$compteurs['total']}\n";
    echo "✅ Étudiants créés: {$compteurs['crees']}\n";
    echo "❌ Erreurs: {$compteurs['erreurs']}\n";
    
    if (!empty($compteurs['classes_manquantes'])) {
        echo "\n⚠️ Classes manquantes (" . count($compteurs['classes_manquantes']) . "):\n";
        foreach (array_slice($compteurs['classes_manquantes'], 0, 10) as $classe) {
            echo "  - {$classe}\n";
        }
        if (count($compteurs['classes_manquantes']) > 10) {
            echo "  ... et " . (count($compteurs['classes_manquantes']) - 10) . " autres\n";
        }
    }
    
    // Vérification des données
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM esbtp_etudiants");
    $total_db = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n📈 Vérification : {$total_db} étudiants en base de données\n";
    
    // Statistiques par filière via les classes
    echo "\n📊 Répartition par classe (TOP 10) :\n";
    $stmt = $pdo->query("
        SELECT c.libelle, COUNT(e.id) as effectif 
        FROM esbtp_etudiants e 
        JOIN esbtp_classes c ON e.classe_id = c.id 
        GROUP BY c.libelle 
        ORDER BY effectif DESC 
        LIMIT 10
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['libelle']}: {$row['effectif']} étudiants\n";
    }
    
    if ($compteurs['crees'] > 2400) {
        echo "\n🎉 IMPORT RÉUSSI ! Tous les étudiants réels ont été importés.\n";
    } else {
        echo "\n⚠️ Import partiel. Vérifiez les erreurs ci-dessus.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}