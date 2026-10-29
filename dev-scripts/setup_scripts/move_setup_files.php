<?php
/**
 * Script pour déplacer les fichiers de configuration dans un dossier dédié
 * 
 * Ce script déplace tous les fichiers de configuration et d'installation
 * dans un dossier 'setup_scripts' pour garder le projet organisé.
 */

// Définir le dossier de destination
$destinationFolder = __DIR__ . '/setup_scripts';

// Créer le dossier s'il n'existe pas
if (!file_exists($destinationFolder)) {
    mkdir($destinationFolder, 0755, true);
    echo "Dossier 'setup_scripts' créé.\n";
}

// Liste des fichiers à déplacer
$filesToMove = [
    'check_db.php',
    'check_user.php',
    'check_users_table.php',
    'check_teachers_table.php',
    'add_role_column.php',
    'create_admin_user.php',
    'setup_database.php',
    'create_db.php',
    'execute_sql.php'
];

// Déplacer chaque fichier
foreach ($filesToMove as $file) {
    $sourcePath = __DIR__ . '/' . $file;
    $destinationPath = $destinationFolder . '/' . $file;
    
    if (file_exists($sourcePath)) {
        // Copier le fichier
        if (copy($sourcePath, $destinationPath)) {
            // Supprimer l'original après la copie
            unlink($sourcePath);
            echo "Fichier '$file' déplacé avec succès.\n";
        } else {
            echo "Erreur lors du déplacement du fichier '$file'.\n";
        }
    } else {
        echo "Fichier '$file' non trouvé, ignoré.\n";
    }
}

// Créer un fichier README dans le dossier
$readmePath = $destinationFolder . '/README.md';
$readmeContent = <<<EOT
# Scripts de configuration

Ce dossier contient les scripts utilisés pour la configuration initiale de l'application.

## Liste des scripts

- `check_db.php` - Vérifie la connexion à la base de données
- `check_user.php` - Vérifie les utilisateurs existants
- `check_users_table.php` - Vérifie la structure de la table des utilisateurs
- `check_teachers_table.php` - Vérifie la structure de la table des enseignants
- `add_role_column.php` - Ajoute la colonne 'role' à la table des utilisateurs
- `create_admin_user.php` - Crée un utilisateur administrateur
- `setup_database.php` - Script principal pour configurer la base de données
- `create_db.php` - Crée la base de données si elle n'existe pas
- `execute_sql.php` - Exécute les fichiers SQL pour créer les tables

## Utilisation

Ces scripts sont utilisés lors de la première installation de l'application.
Ils peuvent également être utilisés pour diagnostiquer des problèmes de base de données.

**Note:** Ces scripts ne doivent pas être accessibles publiquement en production.
EOT;

file_put_contents($readmePath, $readmeContent);
echo "Fichier README.md créé dans le dossier 'setup_scripts'.\n";

echo "\nOpération terminée. Tous les fichiers de configuration ont été déplacés dans le dossier 'setup_scripts'.\n"; 