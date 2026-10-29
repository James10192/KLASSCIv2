<?php
// Ce script vérifie les migrations dans la base de données

// Connexion à la base de données
$host = 'localhost';
$dbname = 'smart_school_db';
$username = 'root';
$password = '';

try {
    // Connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n\n";
    
    // Vérifier les migrations
    $stmt = $pdo->query("SELECT * FROM migrations ORDER BY batch, migration");
    $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Liste des migrations dans la base de données :\n";
    echo "--------------------------------------------\n";
    echo str_pad("ID", 5) . str_pad("Migration", 65) . str_pad("Batch", 10) . "\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($migrations as $migration) {
        echo str_pad($migration['id'], 5) . str_pad($migration['migration'], 65) . str_pad($migration['batch'], 10) . "\n";
    }
    
    echo "\n";
    
    // Vérifier si la table 'messages' existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "La table 'messages' " . ($tableExists ? "existe" : "n'existe pas") . " dans la base de données.\n";
    
    if ($tableExists) {
        // Afficher la structure de la table 'messages'
        $stmt = $pdo->query("DESCRIBE messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nStructure de la table 'messages' :\n";
        echo "--------------------------------\n";
        echo str_pad("Champ", 20) . str_pad("Type", 30) . str_pad("Null", 10) . str_pad("Clé", 10) . str_pad("Défaut", 10) . "\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($columns as $column) {
            echo str_pad($column['Field'], 20) . str_pad($column['Type'], 30) . str_pad($column['Null'], 10) . str_pad($column['Key'], 10) . str_pad($column['Default'] ?? 'NULL', 10) . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
}
?> 