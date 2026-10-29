<?php

/**
 * Script pour vérifier et créer un enregistrement étudiant pour l'utilisateur administrateur
 * 
 * Ce script vérifie si l'utilisateur administrateur a un enregistrement étudiant correspondant
 * et en crée un s'il n'existe pas.
 */

// Charger les variables d'environnement depuis le fichier .env
$dotenv = file_get_contents(__DIR__ . '/.env');
$lines = explode("\n", $dotenv);
$env = [];

foreach ($lines as $line) {
    if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
        continue;
    }
    
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

// Récupérer les informations de connexion à la base de données
$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? '3306';
$database = $env['DB_DATABASE'] ?? 'smart_school';
$username = $env['DB_USERNAME'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

try {
    // Connexion à la base de données avec PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Connexion à la base de données réussie.\n";
    
    // Vérifier si la table classes existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'classes'");
    $classesTableExists = $stmt->rowCount() > 0;
    
    if (!$classesTableExists) {
        echo "La table 'classes' n'existe pas. Création de la table...\n";
        
        // Créer la table classes
        $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "Table 'classes' créée avec succès.\n";
    }
    
    // Vérifier si des classes existent, sinon en créer une
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
    $classCount = $stmt->fetch();
    
    if ($classCount['count'] == 0) {
        echo "Aucune classe n'existe. Création d'une classe par défaut...\n";
        
        // Insérer une classe par défaut
        $stmt = $pdo->prepare("INSERT INTO classes (name, description) VALUES (?, ?)");
        $stmt->execute(['Classe par défaut', 'Classe par défaut pour les nouveaux étudiants']);
        
        echo "Classe par défaut créée avec succès.\n";
    }
    
    // Vérifier si la table sections existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'sections'");
    $sectionsTableExists = $stmt->rowCount() > 0;
    
    if (!$sectionsTableExists) {
        echo "La table 'sections' n'existe pas. Création de la table...\n";
        
        // Créer la table sections
        $pdo->exec("CREATE TABLE IF NOT EXISTS sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            class_id INT NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "Table 'sections' créée avec succès.\n";
    }
    
    // Vérifier si des sections existent, sinon en créer une
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sections");
    $sectionCount = $stmt->fetch();
    
    if ($sectionCount['count'] == 0) {
        echo "Aucune section n'existe. Création d'une section par défaut...\n";
        
        // Récupérer l'ID de la classe par défaut
        $stmt = $pdo->query("SELECT id FROM classes ORDER BY id LIMIT 1");
        $class = $stmt->fetch();
        $classId = $class['id'];
        
        // Insérer une section par défaut
        $stmt = $pdo->prepare("INSERT INTO sections (name, class_id, description) VALUES (?, ?, ?)");
        $stmt->execute(['Section A', $classId, 'Section par défaut pour les nouveaux étudiants']);
        
        echo "Section par défaut créée avec succès.\n";
    }
    
    // Vérifier si la table students existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
    $studentsTableExists = $stmt->rowCount() > 0;
    
    if (!$studentsTableExists) {
        echo "La table 'students' n'existe pas. Création de la table...\n";
        
        // Créer la table students
        $pdo->exec("CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            admission_no VARCHAR(50) NOT NULL,
            roll_no VARCHAR(50) NULL,
            class_id INT NULL,
            section_id INT NULL,
            session_id INT NULL,
            father_name VARCHAR(100) NULL,
            mother_name VARCHAR(100) NULL,
            date_of_birth DATE NULL,
            gender ENUM('male', 'female', 'other') NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            state VARCHAR(100) NULL,
            country VARCHAR(100) NULL,
            pincode VARCHAR(20) NULL,
            religion VARCHAR(50) NULL,
            admission_date DATE NULL,
            blood_group VARCHAR(10) NULL,
            height VARCHAR(10) NULL,
            weight VARCHAR(10) NULL,
            guardian_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (user_id),
            UNIQUE KEY (admission_no)
        )");
        
        echo "Table 'students' créée avec succès.\n";
    }
    
    // Récupérer l'utilisateur administrateur
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "Aucun utilisateur administrateur trouvé.\n";
        exit(1);
    }
    
    echo "Utilisateur administrateur trouvé: ID {$admin['id']}, Nom: {$admin['name']}, Email: {$admin['email']}\n";
    
    // Vérifier si l'administrateur a déjà un enregistrement étudiant
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$admin['id']]);
    $studentExists = $stmt->fetch();
    
    if ($studentExists) {
        echo "L'administrateur a déjà un enregistrement étudiant: ID {$studentExists['id']}, Admission No: {$studentExists['admission_no']}\n";
    } else {
        echo "L'administrateur n'a pas d'enregistrement étudiant. Création d'un enregistrement...\n";
        
        // Générer un numéro d'admission unique
        $admissionNo = 'ADM-' . date('Y') . '-' . str_pad($admin['id'], 4, '0', STR_PAD_LEFT);
        
        // Récupérer l'ID de la classe et de la section par défaut
        $stmt = $pdo->query("SELECT id FROM classes ORDER BY id LIMIT 1");
        $class = $stmt->fetch();
        $classId = $class['id'];
        
        $stmt = $pdo->query("SELECT id FROM sections ORDER BY id LIMIT 1");
        $section = $stmt->fetch();
        $sectionId = $section['id'];
        
        // Créer un enregistrement étudiant pour l'administrateur
        $stmt = $pdo->prepare("INSERT INTO students (
            user_id, admission_no, roll_no, class_id, section_id, 
            gender, admission_date, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())");
        
        $stmt->execute([
            $admin['id'],
            $admissionNo,
            'R-' . str_pad($admin['id'], 4, '0', STR_PAD_LEFT),
            $classId,
            $sectionId,
            'male' // Valeur par défaut, à modifier si nécessaire
        ]);
        
        echo "Enregistrement étudiant créé avec succès pour l'administrateur.\n";
        echo "Admission No: $admissionNo\n";
    }
    
    echo "Opération terminée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 