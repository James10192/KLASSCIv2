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
    
    // Récupérer les données nécessaires
    $stmt = $pdo->query("SELECT id FROM classes LIMIT 1");
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        die("Aucune classe trouvée. Exécutez d'abord create_student_record.php\n");
    }
    
    $classId = $class['id'];
    
    $stmt = $pdo->prepare("SELECT id FROM sections WHERE class_id = ? LIMIT 1");
    $stmt->execute([$classId]);
    $section = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$section) {
        die("Aucune section trouvée. Exécutez d'abord create_student_record.php\n");
    }
    
    $sectionId = $section['id'];
    
    $stmt = $pdo->query("SELECT id FROM subjects");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($subjects) === 0) {
        die("Aucune matière trouvée. Exécutez d'abord create_teacher_record.php\n");
    }
    
    $stmt = $pdo->query("SELECT id FROM teachers LIMIT 1");
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        die("Aucun enseignant trouvé. Exécutez d'abord create_teacher_record.php\n");
    }
    
    $teacherId = $teacher['id'];
    
    // Définir les jours de la semaine
    $daysOfWeek = [1, 2, 3, 4, 5]; // Lundi à Vendredi
    
    // Définir les créneaux horaires
    $timeSlots = [
        ['08:00:00', '09:00:00'],
        ['09:00:00', '10:00:00'],
        ['10:15:00', '11:15:00'],
        ['11:15:00', '12:15:00'],
        ['13:30:00', '14:30:00'],
        ['14:30:00', '15:30:00']
    ];
    
    // Définir les salles
    $rooms = ['A101', 'A102', 'B201', 'B202', 'C301', 'C302'];
    
    // Créer des entrées d'emploi du temps
    $stmt = $pdo->prepare("
        INSERT INTO timetables (
            class_id,
            section_id,
            subject_id,
            teacher_id,
            day_of_week,
            start_time,
            end_time,
            room_number,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $timetableEntries = [];
    
    // Pour chaque jour de la semaine
    foreach ($daysOfWeek as $day) {
        // Pour chaque créneau horaire
        foreach ($timeSlots as $index => $timeSlot) {
            // Choisir une matière aléatoire
            $subjectIndex = $index % count($subjects);
            $subjectId = $subjects[$subjectIndex]['id'];
            
            // Choisir une salle aléatoire
            $roomIndex = ($index + $day) % count($rooms);
            $room = $rooms[$roomIndex];
            
            // Insérer l'entrée d'emploi du temps
            $stmt->execute([
                $classId,
                $sectionId,
                $subjectId,
                $teacherId,
                $day,
                $timeSlot[0],
                $timeSlot[1],
                $room
            ]);
            
            $timetableId = $pdo->lastInsertId();
            $timetableEntries[] = $timetableId;
            
            echo "Entrée d'emploi du temps créée avec l'ID: $timetableId pour le jour $day, de {$timeSlot[0]} à {$timeSlot[1]}, salle $room\n";
        }
    }
    
    echo "\nTotal des entrées d'emploi du temps créées: " . count($timetableEntries) . "\n";
    
    // Vérifier quelques entrées d'emploi du temps
    $stmt = $pdo->prepare("
        SELECT t.*, 
               c.name as class_name, 
               sec.name as section_name, 
               sub.name as subject_name, 
               CONCAT(tea.first_name, ' ', tea.last_name) as teacher_name
        FROM timetables t
        JOIN classes c ON t.class_id = c.id
        JOIN sections sec ON t.section_id = sec.id
        JOIN subjects sub ON t.subject_id = sub.id
        JOIN teachers tea ON t.teacher_id = tea.id
        WHERE t.id IN (" . implode(',', array_slice($timetableEntries, 0, 5)) . ")
    ");
    
    $stmt->execute();
    $timetables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nExemples d'entrées d'emploi du temps:\n";
    foreach ($timetables as $timetable) {
        $dayNames = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $dayName = $dayNames[$timetable['day_of_week']];
        
        echo "ID: {$timetable['id']}\n";
        echo "Classe: {$timetable['class_name']}\n";
        echo "Section: {$timetable['section_name']}\n";
        echo "Matière: {$timetable['subject_name']}\n";
        echo "Enseignant: {$timetable['teacher_name']}\n";
        echo "Jour: $dayName\n";
        echo "Horaire: {$timetable['start_time']} - {$timetable['end_time']}\n";
        echo "Salle: {$timetable['room_number']}\n\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la création des entrées d'emploi du temps: " . $e->getMessage() . "\n");
} 