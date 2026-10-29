<?php
// Ce script marque la migration problématique comme terminée
// et exécute la migration pour la table 'messages'

// Connexion à la base de données
$host = 'localhost';
$dbname = 'smart_school_db';
$username = 'root';
$password = '';

try {
    // Connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n";
    
    // 1. Vérifier si la migration problématique est déjà marquée comme terminée
    $stmt = $pdo->prepare("SELECT * FROM migrations WHERE migration = '2025_02_27_001419_add_role_and_is_active_to_users_table'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // 2. Marquer la migration problématique comme terminée
        $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES ('2025_02_27_001419_add_role_and_is_active_to_users_table', 2)");
        $stmt->execute();
        echo "Migration '2025_02_27_001419_add_role_and_is_active_to_users_table' marquée comme terminée.\n";
    } else {
        echo "La migration '2025_02_27_001419_add_role_and_is_active_to_users_table' est déjà marquée comme terminée.\n";
    }
    
    // 3. Vérifier si la table 'messages' existe déjà
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'messages'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "La table 'messages' n'existe pas encore. Création de la table...\n";
        
        // 4. Créer la table 'messages'
        $sql = "CREATE TABLE `messages` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `sender_id` bigint(20) UNSIGNED NOT NULL,
            `recipient_id` bigint(20) UNSIGNED DEFAULT NULL,
            `subject` varchar(255) NOT NULL,
            `content` text NOT NULL,
            `recipient_type` varchar(255) DEFAULT NULL COMMENT 'Peut être: user, group, class, department, all',
            `recipient_group` varchar(255) DEFAULT NULL COMMENT 'ID du groupe ou de la classe si applicable',
            `is_read` tinyint(1) NOT NULL DEFAULT 0,
            `read_at` timestamp NULL DEFAULT NULL,
            `parent_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Pour les réponses',
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            `deleted_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `messages_sender_id_foreign` (`sender_id`),
            KEY `messages_recipient_id_foreign` (`recipient_id`),
            KEY `messages_parent_id_foreign` (`parent_id`),
            CONSTRAINT `messages_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
            CONSTRAINT `messages_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        
        // 5. Marquer la migration comme terminée
        $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES ('2025_02_26_210250_create_messages_table', 2)");
        $stmt->execute();
        
        echo "Table 'messages' créée avec succès et migration marquée comme terminée.\n";
    } else {
        echo "La table 'messages' existe déjà.\n";
    }
    
    // 6. Vérifier si la migration pour les absences est déjà marquée comme terminée
    $stmt = $pdo->prepare("SELECT * FROM migrations WHERE migration = '2025_02_26_212824_create_absence_justifications_table'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // 7. Marquer la migration pour les absences comme terminée
        $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES ('2025_02_26_212824_create_absence_justifications_table', 2)");
        $stmt->execute();
        echo "Migration '2025_02_26_212824_create_absence_justifications_table' marquée comme terminée.\n";
    } else {
        echo "La migration '2025_02_26_212824_create_absence_justifications_table' est déjà marquée comme terminée.\n";
    }
    
    echo "Toutes les migrations ont été traitées avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
}
?> 