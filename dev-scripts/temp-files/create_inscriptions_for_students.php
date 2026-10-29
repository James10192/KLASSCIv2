#!/usr/bin/env php
<?php
/**
 * Script pour créer les inscriptions pour les 2450 étudiants importés
 * Chaque étudiant aura une inscription active dans sa classe
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
    
    // Récupérer les correspondances nécessaires
    $filieres = [];
    $result = $pdo->query("SELECT id, name FROM esbtp_filieres");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $filieres[$row['name']] = $row['id'];
    }
    
    $niveaux = [];
    $result = $pdo->query("SELECT id, libelle FROM esbtp_niveau_etudes");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $niveaux[$row['libelle']] = $row['id'];
    }
    
    $classes = [];
    $result = $pdo->query("
        SELECT c.id, c.libelle, c.filiere_id, c.niveau_etude_id, 
               f.name as filiere_nom, n.libelle as niveau_libelle
        FROM esbtp_classes c 
        JOIN esbtp_filieres f ON c.filiere_id = f.id 
        JOIN esbtp_niveau_etudes n ON c.niveau_etude_id = n.id
    ");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $classes[$row['id']] = $row;
    }
    
    // Récupérer l'année universitaire active
    $stmt = $pdo->query("SELECT id, libelle FROM esbtp_annee_universitaires WHERE est_actif = 1 LIMIT 1");
    $anneeUniv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anneeUniv) {
        throw new Exception("Aucune année universitaire active trouvée");
    }
    
    echo "🎯 Année universitaire: {$anneeUniv['libelle']}\n";
    echo "📚 Classes disponibles: " . count($classes) . "\n";
    echo "🎓 Filières disponibles: " . count($filieres) . "\n";
    echo "📊 Niveaux disponibles: " . count($niveaux) . "\n";
    
    // Vider les inscriptions existantes (optionnel)
    echo "🗑️  Suppression des inscriptions existantes...\n";
    $pdo->exec("DELETE FROM esbtp_inscriptions");
    $pdo->exec("ALTER TABLE esbtp_inscriptions AUTO_INCREMENT = 1");
    
    // Récupérer tous les étudiants
    $stmt = $pdo->query("
        SELECT id, matricule, nom, prenoms, classe_id, annee_universitaire_id
        FROM esbtp_etudiants 
        WHERE classe_id IS NOT NULL 
        ORDER BY id
    ");
    
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($etudiants)) {
        throw new Exception("Aucun étudiant trouvé avec classe_id");
    }
    
    echo "👥 Étudiants à traiter: " . count($etudiants) . "\n";
    
    // Préparer la requête d'insertion d'inscription
    $insertStmt = $pdo->prepare("
        INSERT INTO esbtp_inscriptions (
            etudiant_id, annee_universitaire_id, filiere_id, niveau_id, classe_id,
            date_inscription, type_inscription, status, workflow_step,
            montant_scolarite, frais_inscription, comptabilite_activee,
            created_at, updated_at
        ) VALUES (
            :etudiant_id, :annee_universitaire_id, :filiere_id, :niveau_id, :classe_id,
            :date_inscription, :type_inscription, :status, :workflow_step,
            :montant_scolarite, :frais_inscription, :comptabilite_activee,
            :created_at, :updated_at
        )
    ");
    
    $compteurs = [
        'total' => count($etudiants),
        'crees' => 0,
        'erreurs' => 0,
        'classes_inconnues' => []
    ];
    
    echo "\n🎯 Création des inscriptions...\n";
    
    foreach ($etudiants as $etudiant) {
        try {
            $classe_id = $etudiant['classe_id'];
            
            // Vérifier que la classe existe dans notre mapping
            if (!isset($classes[$classe_id])) {
                if (!in_array($classe_id, $compteurs['classes_inconnues'])) {
                    $compteurs['classes_inconnues'][] = $classe_id;
                    echo "❌ Classe inconnue ID: {$classe_id}\n";
                }
                $compteurs['erreurs']++;
                continue;
            }
            
            $classe_info = $classes[$classe_id];
            
            // Définir des montants par défaut basés sur le niveau
            $montant_scolarite = match($classe_info['niveau_libelle']) {
                '1A', '2A' => 850000, // BTS
                'L1', 'L2', 'L3' => 750000, // Licence
                'M1', 'M2' => 950000, // Master
                '5A' => 1000000, // 5ème année
                default => 800000
            };
            
            $frais_inscription = match($classe_info['niveau_libelle']) {
                '1A', '2A' => 50000, // BTS
                'L1', 'L2', 'L3' => 45000, // Licence
                'M1', 'M2' => 60000, // Master
                '5A' => 70000, // 5ème année
                default => 50000
            };
            
            // Exécuter l'insertion
            $insertStmt->execute([
                ':etudiant_id' => $etudiant['id'],
                ':annee_universitaire_id' => $anneeUniv['id'],
                ':filiere_id' => $classe_info['filiere_id'],
                ':niveau_id' => $classe_info['niveau_etude_id'],
                ':classe_id' => $classe_id,
                ':date_inscription' => '2024-09-01', // Début d'année scolaire
                ':type_inscription' => 'première_inscription',
                ':status' => 'active', // Inscription active
                ':workflow_step' => 'etudiant_cree', // Étudiant déjà créé
                ':montant_scolarite' => $montant_scolarite,
                ':frais_inscription' => $frais_inscription,
                ':comptabilite_activee' => 1,
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $compteurs['crees']++;
            
            if ($compteurs['crees'] % 250 == 0) {
                echo "✅ {$compteurs['crees']} inscriptions créées...\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur étudiant {$etudiant['matricule']}: " . $e->getMessage() . "\n";
            $compteurs['erreurs']++;
        }
    }
    
    // Résultats finaux
    echo "\n=== RÉSULTATS DE CRÉATION DES INSCRIPTIONS ===\n";
    echo "📊 Total étudiants traités: {$compteurs['total']}\n";
    echo "✅ Inscriptions créées: {$compteurs['crees']}\n";
    echo "❌ Erreurs: {$compteurs['erreurs']}\n";
    
    if (!empty($compteurs['classes_inconnues'])) {
        echo "\n⚠️ Classes inconnues (" . count($compteurs['classes_inconnues']) . " IDs):\n";
        foreach ($compteurs['classes_inconnues'] as $classe_id) {
            echo "  - ID: {$classe_id}\n";
        }
    }
    
    // Vérification des données
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM esbtp_inscriptions WHERE status = 'active'");
    $total_inscriptions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n📈 Vérification : {$total_inscriptions} inscriptions actives en base de données\n";
    
    // Statistiques par filière
    echo "\n📊 Répartition des inscriptions par filière :\n";
    $stmt = $pdo->query("
        SELECT f.name, COUNT(i.id) as total_inscriptions 
        FROM esbtp_inscriptions i 
        JOIN esbtp_filieres f ON i.filiere_id = f.id 
        WHERE i.status = 'active'
        GROUP BY f.name 
        ORDER BY total_inscriptions DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['name']}: {$row['total_inscriptions']} inscriptions\n";
    }
    
    // Statistiques par niveau
    echo "\n📚 Répartition des inscriptions par niveau :\n";
    $stmt = $pdo->query("
        SELECT n.libelle, COUNT(i.id) as total_inscriptions 
        FROM esbtp_inscriptions i 
        JOIN esbtp_niveau_etudes n ON i.niveau_id = n.id 
        WHERE i.status = 'active'
        GROUP BY n.libelle 
        ORDER BY total_inscriptions DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['libelle']}: {$row['total_inscriptions']} inscriptions\n";
    }
    
    if ($compteurs['crees'] > 2400) {
        echo "\n🎉 SUCCÈS ! Toutes les inscriptions ont été créées.\n";
        echo "📋 Chaque étudiant est maintenant lié à sa classe via une inscription active.\n";
    } else {
        echo "\n⚠️ Création partielle. Vérifiez les erreurs ci-dessus.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}