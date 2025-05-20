<?php

// Récupérer les informations de connexion depuis le fichier .env
$envFile = __DIR__ . '/.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$dbConfig = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'username' => 'root',
    'password' => '',
    'database' => 'smart_school_db'
];

// Parcourir les lignes du fichier .env pour trouver les informations de connexion
foreach ($lines as $line) {
    if (strpos($line, 'DB_HOST=') === 0) {
        $dbConfig['host'] = substr($line, 8);
    } elseif (strpos($line, 'DB_PORT=') === 0) {
        $dbConfig['port'] = substr($line, 8);
    } elseif (strpos($line, 'DB_USERNAME=') === 0) {
        $dbConfig['username'] = substr($line, 12);
    } elseif (strpos($line, 'DB_PASSWORD=') === 0) {
        $dbConfig['password'] = substr($line, 12);
    } elseif (strpos($line, 'DB_DATABASE=') === 0) {
        $dbConfig['database'] = substr($line, 12);
    }
}

// Créer la connexion à MySQL
try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    
    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Trouver l'ID de l'utilisateur parent
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'parent' AND email = 'parent@smartschool.com'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Utilisateur parent non trouvé. Exécutez d'abord create_admin_user.php\n");
    }
    
    $userId = $user['id'];
    
    // Trouver l'ID de l'étudiant
    $stmt = $pdo->prepare("SELECT id FROM students LIMIT 1");
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        die("Aucun étudiant trouvé. Exécutez d'abord create_student_record.php\n");
    }
    
    $studentId = $student['id'];
    
    // Créer un enregistrement parent
    $stmt = $pdo->prepare("
        INSERT INTO parents (
            user_id, 
            first_name, 
            last_name, 
            gender, 
            occupation, 
            address, 
            phone, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $userId,
        'Parent',
        'User',
        'Male',
        'Ingénieur',
        '789 Family Street, City',
        '0123456789'
    ]);
    
    $parentId = $pdo->lastInsertId();
    echo "Enregistrement parent créé avec l'ID: $parentId pour l'utilisateur ID: $userId\n";
    
    // Associer le parent à l'étudiant
    $stmt = $pdo->prepare("
        INSERT INTO parent_student (
            parent_id, 
            student_id, 
            relationship, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([$parentId, $studentId, 'Père']);
    echo "Parent associé à l'étudiant ID: $studentId\n";
    
    // Vérifier l'enregistrement parent créé
    $stmt = $pdo->prepare("
        SELECT p.*, u.email, u.role, s.first_name as student_first_name, s.last_name as student_last_name, ps.relationship
        FROM parents p
        JOIN users u ON p.user_id = u.id
        JOIN parent_student ps ON p.id = ps.parent_id
        JOIN students s ON ps.student_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nDétails du parent créé:\n";
    echo "ID: {$parent['id']}\n";
    echo "Utilisateur ID: {$parent['user_id']}\n";
    echo "Email: {$parent['email']}\n";
    echo "Rôle: {$parent['role']}\n";
    echo "Nom: {$parent['first_name']} {$parent['last_name']}\n";
    echo "Genre: {$parent['gender']}\n";
    echo "Profession: {$parent['occupation']}\n";
    echo "Adresse: {$parent['address']}\n";
    echo "Téléphone: {$parent['phone']}\n";
    echo "Relation avec l'étudiant: {$parent['relationship']}\n";
    echo "Étudiant associé: {$parent['student_first_name']} {$parent['student_last_name']}\n";
    
} catch (PDOException $e) {
    die("Erreur lors de la création de l'enregistrement parent: " . $e->getMessage() . "\n");
} 