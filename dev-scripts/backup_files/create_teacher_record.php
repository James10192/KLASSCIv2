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
    
    // Trouver l'ID de l'utilisateur enseignant
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'teacher' AND email = 'teacher@smartschool.com'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Utilisateur enseignant non trouvé. Exécutez d'abord create_admin_user.php\n");
    }
    
    $userId = $user['id'];
    
    // Vérifier si une matière existe, sinon en créer une
    $stmt = $pdo->query("SELECT id FROM subjects LIMIT 1");
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $subjectId = null;
    if (!$subject) {
        // Créer des matières
        $subjects = [
            'Mathématiques',
            'Français',
            'Histoire-Géographie',
            'Sciences',
            'Anglais'
        ];
        
        $subjectIds = [];
        foreach ($subjects as $subjectName) {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
            $stmt->execute([$subjectName]);
            $subjectIds[] = $pdo->lastInsertId();
        }
        
        echo "Matières créées avec les IDs: " . implode(', ', $subjectIds) . "\n";
        $subjectId = $subjectIds[0]; // Utiliser la première matière
    } else {
        $subjectId = $subject['id'];
        echo "Matière existante utilisée avec l'ID: $subjectId\n";
    }
    
    // Créer un enregistrement enseignant
    $stmt = $pdo->prepare("
        INSERT INTO teachers (
            user_id, 
            employee_id, 
            first_name, 
            last_name, 
            gender, 
            date_of_birth, 
            address, 
            qualification, 
            experience, 
            joining_date, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $userId,
        'EMP'.date('Y').'001',
        'Teacher',
        'User',
        'Male',
        date('Y-m-d', strtotime('-30 years')),
        '456 Faculty Avenue, City',
        'Master en Éducation',
        '5 ans',
        date('Y-m-d', strtotime('-2 years'))
    ]);
    
    $teacherId = $pdo->lastInsertId();
    echo "Enregistrement enseignant créé avec l'ID: $teacherId pour l'utilisateur ID: $userId\n";
    
    // Associer l'enseignant à la matière
    $stmt = $pdo->prepare("
        INSERT INTO teacher_subjects (
            teacher_id, 
            subject_id, 
            created_at, 
            updated_at
        ) VALUES (?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([$teacherId, $subjectId]);
    echo "Enseignant associé à la matière ID: $subjectId\n";
    
    // Vérifier l'enregistrement enseignant créé
    $stmt = $pdo->prepare("
        SELECT t.*, u.email, u.role, s.name as subject_name
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        JOIN subjects s ON ts.subject_id = s.id
        WHERE t.id = ?
    ");
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nDétails de l'enseignant créé:\n";
    echo "ID: {$teacher['id']}\n";
    echo "Utilisateur ID: {$teacher['user_id']}\n";
    echo "Email: {$teacher['email']}\n";
    echo "Rôle: {$teacher['role']}\n";
    echo "ID Employé: {$teacher['employee_id']}\n";
    echo "Nom: {$teacher['first_name']} {$teacher['last_name']}\n";
    echo "Genre: {$teacher['gender']}\n";
    echo "Date de naissance: {$teacher['date_of_birth']}\n";
    echo "Adresse: {$teacher['address']}\n";
    echo "Qualification: {$teacher['qualification']}\n";
    echo "Expérience: {$teacher['experience']}\n";
    echo "Date d'embauche: {$teacher['joining_date']}\n";
    echo "Matière enseignée: {$teacher['subject_name']}\n";
    
} catch (PDOException $e) {
    die("Erreur lors de la création de l'enregistrement enseignant: " . $e->getMessage() . "\n");
} 