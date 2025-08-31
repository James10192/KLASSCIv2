# Smart School Scripts

This directory contains utility scripts to help with development and maintenance of the Smart School application.

## PowerShell Scripts (Recommended for Windows)

These scripts are designed to work with PowerShell, which is the default shell in Windows 10 and later.

### Main Application Script
- `start_app.ps1` - **NEW!** Interactive menu to manage all aspects of the application

### Server Management
- `serve.ps1` - Starts the PHP development server at http://localhost:8000

### Database Management
- `migrate.ps1` - Interactive menu for database migrations
- `seed_database.ps1` - **NEW!** Interactive menu for seeding the database with test data
- `check_database.ps1` - **NEW!** Checks database status, tables, and record counts

### Cache Management
- `clear_cache.ps1` - Clears all Laravel caches (config, application, route, view)

### Application Optimization
- `optimize.ps1` - Optimizes the application for production
- `storage_link.ps1` - Creates a symbolic link for storage

### Environment Management
- `check_env.ps1` - Checks the Laravel environment (PHP version, Laravel version, database connection, etc.)

### Project Completion
- `project_completion.ps1` - **NEW!** Helps with project completion tasks (cleanup, optimization, backup)

## How to Run PowerShell Scripts

To run these scripts, right-click on the script file and select "Run with PowerShell", or open PowerShell and run:

```powershell
cd C:\wamp64\www\smart_school_new\scripts
.\start_app.ps1
```

> **Note:** In PowerShell, use semicolons (`;`) instead of ampersands (`&&`) to chain commands:
> ```powershell
> cd C:\wamp64\www\smart_school_new ; php artisan serve
> ```

## Batch Scripts (Alternative)

These scripts are designed to work with the Windows Command Prompt.

- `serve.bat` - Starts the Laravel development server at http://127.0.0.1:8000
- `clear_cache.bat` - Clears all Laravel caches
- `migrate.bat` - Runs database migrations
- `migrate_seed.bat` - Runs database migrations with seed data
- `optimize.bat` - Optimizes the application for production
- `storage_link.bat` - Creates a symbolic link for storage
- `check_env.bat` - Checks the Laravel environment (PHP version, Laravel version, database connection, etc.)

To run these scripts, double-click on the script file, or open Command Prompt and run:

```cmd
cd C:\wamp64\www\smart_school_new\scripts
serve.bat
```

## Accessing the Application

You can access the application in several ways:

1. Using the PHP development server: http://localhost:8000
2. Using WAMP: http://localhost/smart_school_new/public
3. Using the redirect files: http://localhost/smart_school.php or http://localhost/smart_school.html

## Troubleshooting

If you encounter issues with the scripts:

1. Make sure PHP is in your system PATH
2. Check that you're running the scripts from the correct directory
3. Verify that your database configuration in `.env` is correct
4. Try running the commands manually to see detailed error messages

## Default Login Credentials

The application comes with several default user accounts for testing:

### Super Admin
- Email: super-admin@example.com
- Password: password123

### Admin
- Email: admin@example.com
- Password: password123

### Teacher
- Email: teacher@example.com
- Password: password123

### Student
- Email: student@example.com
- Password: password123

### Parent
- Email: parent@example.com
- Password: password123

## Liste des scripts

### 1. display_users.php

**Description :** Affiche la liste des utilisateurs avec leurs rôles.

**Utilisation :**
```bash
php scripts/display_users.php
```

**Résultat :** Affiche une liste de tous les utilisateurs avec leurs noms, emails, rôles et un mot de passe par défaut.

### 2. fix_url_config.php

**Description :** Corrige les problèmes de configuration d'URL dans l'application.

**Utilisation :**
```bash
php scripts/fix_url_config.php
```

**Résultat :** 
- Met à jour le fichier `.env` avec l'URL correcte
- Vérifie et corrige le fichier `.htaccess` à la racine
- Configure l'`AppServiceProvider` pour forcer l'URL de base correcte

### 3. fix_hardcoded_links.php

**Description :** Corrige les liens codés en dur dans les fichiers de vue.

**Utilisation :**
```bash
php scripts/fix_hardcoded_links.php
```

**Résultat :** Remplace les liens codés en dur (comme `href="/login"`) par des fonctions Laravel comme `route()` ou `asset()`.

### 4. clear_cache.php

**Description :** Vide tous les caches de l'application Laravel.

**Utilisation :**
```bash
php scripts/clear_cache.php
```

**Résultat :** Exécute les commandes Artisan suivantes :
- `php artisan config:clear` - Vide le cache de configuration
- `php artisan cache:clear` - Vide le cache de l'application
- `php artisan route:clear` - Vide le cache des routes
- `php artisan view:clear` - Vide le cache des vues
- `php artisan optimize:clear` - Vide tous les caches d'optimisation

### 5. clear_cache.bat

**Description :** Script batch Windows pour vider tous les caches de l'application Laravel.

**Utilisation :**
Double-cliquez sur le fichier `clear_cache.bat` dans l'explorateur Windows ou exécutez-le depuis une invite de commande :
```cmd
scripts\clear_cache.bat
```

**Résultat :** Exécute les mêmes commandes que `clear_cache.php` mais dans un environnement Windows.

### 6. check_app_status.php

**Description :** Vérifie l'état de l'application et sa configuration.

**Utilisation :**
```bash
php scripts/check_app_status.php
```

**Résultat :** Affiche un rapport détaillé sur l'état de l'application, incluant :
- Vérification de la connexion à la base de données
- Vérification des tables requises
- Nombre d'utilisateurs et de rôles
- Configuration de l'application (URL, environnement, mode debug)
- Vérification des fichiers importants
- Vérification des permissions des dossiers
- Vérification des packages installés

## Identifiants de connexion

Voici les identifiants pour se connecter à l'application avec différents rôles :

### Super Admin
- Email: super-admin@example.com
- Mot de passe: password123

### Admin
- Email: admin@example.com
- Mot de passe: password123

### Directeur
- Email: directeur@example.com
- Mot de passe: password123

### Enseignants
- Email: enseignant1@example.com
- Mot de passe: password123
- Email: enseignant2@example.com
- Mot de passe: password123

### Parents
- Email: parent1@example.com
- Mot de passe: password123
- Email: parent2@example.com
- Mot de passe: password123

### Secrétaire
- Email: secretaire@example.com
- Mot de passe: password123

### Comptable
- Email: comptable@example.com
- Mot de passe: password123

### Bibliothécaire
- Email: bibliothecaire@example.com
- Mot de passe: password123

### 1. run_seeders.php

Ce script permet d'exécuter tous les seeders pour initialiser la base de données avec des données de test pour les structures administratives.

**Utilisation :**
```bash
php scripts/run_seeders.php
```

**Seeders exécutés :**
- UFRsSeeder (Unités de Formation et de Recherche)
- FormationsSeeder (Formations)
- ParcoursSeeder (Parcours)
- UniteEnseignementSeeder (Unités d'Enseignement)
- ElementConstitutifSeeder (Éléments Constitutifs)
- ClassroomSeeder (Salles de classe)
- CourseSessionSeeder (Sessions de cours)
- EvaluationSeeder (Évaluations)
- DocumentSeeder (Documents)

### 2. clean_admin_structures.php

Ce script permet de nettoyer toutes les données des structures administratives de la base de données.

**Utilisation :**
```bash
php scripts/clean_admin_structures.php
```

**Tables nettoyées :**
- documents
- evaluations
- course_sessions
- classrooms
- element_constitutifs
- unite_enseignements
- parcours
- formations
- ufrs

## Précautions

- Assurez-vous d'avoir une sauvegarde de vos données avant d'exécuter le script de nettoyage.
- Ces scripts doivent être exécutés à la racine du projet Laravel.
- Le script de nettoyage demandera une confirmation avant de supprimer les données.

## Structure des données

La structure des données administratives suit le schéma suivant :

1. **UFR** (Unité de Formation et de Recherche)
   - Contient plusieurs **Formations**

2. **Formation**
   - Appartient à une **UFR**
   - Contient plusieurs **Parcours**

3. **Parcours**
   - Appartient à une **Formation**
   - Contient plusieurs **Unités d'Enseignement**

4. **Unité d'Enseignement (UE)**
   - Appartient à un **Parcours** (optionnel, peut être commun à plusieurs parcours)
   - Appartient à une **Formation**
   - Contient plusieurs **Éléments Constitutifs**

5. **Élément Constitutif (EC)**
   - Appartient à une **Unité d'Enseignement**
   - Peut avoir plusieurs **Sessions de Cours**
   - Peut avoir plusieurs **Évaluations**
   - Peut avoir plusieurs **Documents**

6. **Salle de Classe (Classroom)**
   - Utilisée pour les **Sessions de Cours**
   - Utilisée pour les **Évaluations**

7. **Session de Cours**
   - Liée à un **Élément Constitutif**
   - Se déroule dans une **Salle de Classe**

8. **Évaluation**
   - Liée à un **Élément Constitutif**
   - Peut se dérouler dans une **Salle de Classe**

9. **Document**
   - Lié à un **Élément Constitutif**

# Database Management Scripts

This folder contains scripts to help you manage your database for the Smart School application.

## Master Database Manager

The easiest way to manage your database is to use the master database manager script:

- `database_manager.bat` - Windows batch file
- `database_manager.ps1` - PowerShell script for Windows users

This script provides a menu to access all the database management scripts in one place.

## Available Scripts

### Database Reset Scripts

These scripts help you reset your database completely by:
1. Dropping all tables
2. Running all migrations
3. Seeding the database with initial data

- `reset_database.bat` - Windows batch file (easiest to use for Windows users)
- `reset_database.ps1` - PowerShell script for Windows users
- `reset_database.php` - PHP script (works on any platform with PHP)

### Database Status Check Scripts

These scripts help you check the status of your database:
1. Checking database connection
2. Listing migrations status
3. Counting records in key tables

- `check_database.bat` - Windows batch file
- `check_database.ps1` - PowerShell script for Windows users
- `check_database.php` - PHP script (works on any platform with PHP)

### Database Seeding Scripts

These scripts help you seed your database with initial data:
1. Choose which seeders to run
2. Run specific seeders or all seeders

- `seed_database.bat` - Windows batch file
- `seed_database.ps1` - PowerShell script for Windows users
- `seed_database.php` - PHP script (works on any platform with PHP)

## How to Use

### Using the Master Database Manager (Recommended)

1. Simply double-click on `database_manager.bat` in Windows Explorer
2. Select the operation you want to perform from the menu
3. Follow the on-screen instructions

### Using the Individual Batch Files (Windows)

1. Simply double-click on the `.bat` file in Windows Explorer
2. Follow the on-screen instructions
3. When prompted, enter the full path to your PHP executable (e.g., `C:\wamp64\bin\php\php8.1.31\php.exe`)

### Using the PowerShell Scripts (Windows)

1. Right-click on the `.ps1` file and select "Run with PowerShell"
2. If you get an execution policy error, you can run the batch file instead, or run this command in PowerShell:
   ```
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   ```
3. Follow the on-screen instructions

### Using the PHP Scripts (Any Platform)

1. Open a terminal or command prompt
2. Navigate to the project root directory
3. Run the script:
   ```
   php trash/database_scripts/script_name.php
   ```
4. Follow the on-screen instructions

## Warning

**The reset_database scripts will DELETE ALL DATA in your database!** Make sure you have backups of any important data before running them.

## Troubleshooting

If you encounter any issues:

1. Make sure you have the correct path to your PHP executable
2. Ensure your database server is running
3. Check that your database credentials in the `.env` file are correct
4. Make sure you have sufficient permissions to drop and create tables in your database

## Default Login Credentials

After seeding the database, you can use these default login credentials:

### Super Admin
- Email: super-admin@example.com
- Password: password123

### Admin
- Email: admin@example.com
- Password: password123

### Teacher
- Email: teacher@example.com
- Password: password123

### Student
- Email: student@example.com
- Password: password123

### Parent
- Email: parent@example.com
- Password: password123 