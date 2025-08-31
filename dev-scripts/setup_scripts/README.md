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