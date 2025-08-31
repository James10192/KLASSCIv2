<?php

/**
 * Script pour trouver les identifiants de connexion - Smart School
 * 
 * Ce script permet de rechercher les identifiants de connexion dans la base de données
 * pour faciliter la connexion à l'application.
 * 
 * IMPORTANT: Ce script est fourni à des fins de développement uniquement.
 * Ne pas utiliser en production car il expose des informations sensibles.
 */

// Configuration de la base de données
// Ces valeurs doivent correspondre à celles de votre fichier .env
$db_host = 'localhost';
$db_name = 'smart_school_db'; // Remplacez par le nom de votre base de données
$db_user = 'root';         // Remplacez par votre nom d'utilisateur
$db_pass = '';             // Remplacez par votre mot de passe

// Fonction pour se connecter à la base de données
function connectDB($host, $dbname, $username, $password) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        echo "Erreur de connexion à la base de données: " . $e->getMessage();
        return null;
    }
}

// Fonction pour vérifier si une table existe
function tableExists($conn, $tableName) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Fonction pour trouver les utilisateurs
function findUsers($conn) {
    try {
        // Vérifier si la table users existe
        if (tableExists($conn, 'users')) {
            // Récupérer les colonnes de la table users
            $stmt = $conn->query("DESCRIBE users");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Construire la requête en fonction des colonnes disponibles
            $select = "SELECT id";
            if (in_array('name', $columns)) $select .= ", name";
            if (in_array('first_name', $columns)) $select .= ", first_name";
            if (in_array('last_name', $columns)) $select .= ", last_name";
            if (in_array('email', $columns)) $select .= ", email";
            if (in_array('username', $columns)) $select .= ", username";
            if (in_array('role', $columns)) $select .= ", role";
            if (in_array('created_at', $columns)) $select .= ", created_at";
            
            $query = "$select FROM users LIMIT 10";
            $stmt = $conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo "La table 'users' n'existe pas dans la base de données.\n";
            return [];
        }
    } catch(PDOException $e) {
        echo "Erreur lors de la recherche des utilisateurs: " . $e->getMessage();
        return [];
    }
}

// Fonction pour trouver les super admins
function findSuperAdmins($conn) {
    try {
        if (tableExists($conn, 'super_admins') && tableExists($conn, 'users')) {
            $query = "SELECT u.id, u.name, u.email, u.role, sa.employee_id 
                     FROM super_admins sa 
                     JOIN users u ON sa.user_id = u.id 
                     LIMIT 5";
            $stmt = $conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    } catch(PDOException $e) {
        echo "Erreur lors de la recherche des super admins: " . $e->getMessage();
        return [];
    }
}

// Fonction pour trouver les enseignants
function findTeachers($conn) {
    try {
        if (tableExists($conn, 'teachers') && tableExists($conn, 'users')) {
            $query = "SELECT u.id, u.name, u.email, u.role, t.employee_id 
                     FROM teachers t 
                     JOIN users u ON t.user_id = u.id 
                     LIMIT 5";
            $stmt = $conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    } catch(PDOException $e) {
        echo "Erreur lors de la recherche des enseignants: " . $e->getMessage();
        return [];
    }
}

// Fonction pour trouver les étudiants
function findStudents($conn) {
    try {
        if (tableExists($conn, 'students') && tableExists($conn, 'users')) {
            $query = "SELECT u.id, u.name, u.email, u.role, s.admission_no, s.roll_no 
                     FROM students s 
                     JOIN users u ON s.user_id = u.id 
                     LIMIT 5";
            $stmt = $conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    } catch(PDOException $e) {
        echo "Erreur lors de la recherche des étudiants: " . $e->getMessage();
        return [];
    }
}

// Fonction pour afficher les résultats
function displayResults($title, $users) {
    echo "\n=== $title ===\n";
    if (empty($users)) {
        echo "Aucun utilisateur trouvé.\n";
        return;
    }
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}";
        foreach ($user as $key => $value) {
            if ($key != 'id') {
                echo ", $key: $value";
            }
        }
        echo "\n";
    }
}

// Vérifier si le script est exécuté dans un navigateur
$is_browser = isset($_SERVER['HTTP_USER_AGENT']);

// Si exécuté dans un navigateur, afficher en HTML
if ($is_browser) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Recherche d'identifiants - Smart School</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            table { border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #f2f2f2; }
            td, th { padding: 8px; text-align: left; }
            .warning { color: red; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Recherche d'identifiants - Smart School</h1>
        <p class='warning'>ATTENTION: Ce script est fourni à des fins de développement uniquement. 
        Ne pas utiliser en production car il expose des informations sensibles.</p>";
}

// Connexion à la base de données
$conn = connectDB($db_host, $db_name, $db_user, $db_pass);

if ($conn) {
    // Recherche des utilisateurs
    $users = findUsers($conn);
    $superAdmins = findSuperAdmins($conn);
    $teachers = findTeachers($conn);
    $students = findStudents($conn);
    
    // Affichage des résultats
    displayResults("Utilisateurs", $users);
    displayResults("Super Administrateurs", $superAdmins);
    displayResults("Enseignants", $teachers);
    displayResults("Étudiants", $students);
    
    echo "\nNote: Les mots de passe sont généralement hachés et ne peuvent pas être utilisés directement.\n";
    echo "Pour vous connecter, essayez d'utiliser l'email d'un utilisateur avec le mot de passe 'password' ou '123456'.\n";
    echo "Vous pouvez également essayer les identifiants par défaut suivants:\n";
    echo "- Admin: admin@example.com / password\n";
    echo "- Enseignant: teacher@example.com / password\n";
    echo "- Étudiant: student@example.com / password\n";
    
    if ($is_browser) {
        echo "</body></html>";
    }
} 