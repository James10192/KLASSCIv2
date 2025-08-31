# Project Memory

## Recent Changes

### Fixed jQuery and Modal Issues in Class Selector (2024-03-26)

-   Fixed "$ is not defined" error by moving jQuery to the main layout file
-   Properly defined ouvrirSelecteurClasse and related functions in global scope
-   Removed duplicate jQuery loading from create.blade.php
-   Ensured proper script loading order (jQuery before Bootstrap)
-   Fixed modal functionality in class selector

### Fixed jQuery undefined error in create.blade.php (2024-03-26)

-   Fixed "$ is not defined" error in class selector modal
-   Moved jQuery script loading to the correct location
-   Properly scoped the ouvrirSelecteurClasse function within document.ready
-   Ensured jQuery is loaded before any jQuery-dependent code executes

### Added Comprehensive Logging System (2024-03-26)

-   Added new logging channels for debugging, routes, and SQL queries
-   Implemented RouteDebugMiddleware for detailed route logging
-   Created QueryDebugServiceProvider for SQL query logging
-   Added DebugHelper class for centralized debugging functions
-   Configured daily log rotation with 7-day retention
-   Added script for log monitoring and analysis

### Fixed emploi-temps destroy route issue (2024-03-26)

-   Removed duplicate destroy route definition that was causing conflicts
-   Ensured the resource route has the correct middleware for all actions
-   This resolves the "Route [esbtp.emploi-temps.destroy] not defined" error

### Fixed undefined variable issue in teacher attendance report

-   Added missing `$enseignants` variable in ESBTPTeacherAttendanceController report method
-   Added missing `$stats` array for attendance statistics
-   This resolves the error "Undefined variable $enseignants" in the attendance report view

### Fixed date formatting issue in teacher attendance report

-   Added proper date casting in ESBTPTeacherAttendance model for all date fields
-   Updated report view to handle both Carbon instances and string dates
-   Added validation for date fields in the controller
-   Improved date range filtering in the attendance query
-   This resolves the error "Call to a member function format() on string" in the attendance report view

## Tasks Status

### Completed

-   Fixed namespace issue in ESBTPTeacherAttendanceController
-   Fixed date formatting in attendance report view
-   Added proper date casting in ESBTPTeacherAttendance model
-   Fixed undefined variables in attendance report

### In Progress

-   Implementing QR code support for attendance tracking
-   Adding real-time updates for attendance status

### Pending

-   Export functionality for attendance reports
-   Automated notifications for attendance validation
-   Substitute teacher support

# Système d'émargement des enseignants - Mise à jour de sécurité

## Modifications apportées le [DATE]

### Problème résolu

-   Correction du NullPointerException dans le TeacherAttendanceController
-   Ajout de vérifications de sécurité pour l'accès aux fonctionnalités d'émargement
-   Protection contre les accès non autorisés

### Changements effectués

1. Méthode index()

    - Ajout de vérification de l'existence de l'enseignant
    - Redirection vers le dashboard avec message d'erreur si nécessaire
    - Ajout des statistiques d'émargement
    - Ajout des derniers émargements

2. Méthode sign()

    - Vérification de l'existence de l'enseignant
    - Validation des données du formulaire
    - Gestion des erreurs améliorée
    - Ajout de la géolocalisation

3. Méthode history()
    - Protection contre les accès non autorisés
    - Filtrage par mois et année
    - Calcul des statistiques optimisé

### Points d'attention

-   Les utilisateurs sans profil enseignant sont redirigés vers le dashboard
-   Les messages d'erreur sont plus explicites
-   La géolocalisation est maintenant requise pour l'émargement

### Prochaines étapes

-   Implémenter la validation des émargements par l'administration
-   Ajouter des rapports détaillés
-   Optimiser les requêtes de base de données

# Project Memory: ESBTP

## Fonctionnalités Implémentées

### Système d'Émargement des Enseignants

-   Système sécurisé permettant aux enseignants de confirmer leur présence aux cours
-   Utilisation d'un code quotidien généré par l'administration
-   Géolocalisation pour vérifier la présence sur site
-   Interface dédiée pour les enseignants et les administrateurs

#### Composants du Système

1. **Génération de Code Quotidien**

    - Code alphanumérique de 6 caractères
    - Validité de 24 heures (configurable)
    - Généré par le superAdmin ou le secrétariat
    - Stocké dans la table `esbtp_daily_codes`

2. **Process d'Émargement**

    - Interface dédiée pour les enseignants
    - Liste des cours du jour basée sur l'emploi du temps
    - Saisie du code d'émargement
    - Vérification de la géolocalisation
    - Enregistrement des informations de connexion
    - Stocké dans la table `esbtp_teacher_attendances`

3. **Sécurité**

    - Code valide uniquement pour la journée en cours
    - Usage unique par enseignant et par cours
    - Vérification de la géolocalisation
    - Blocage après 3 tentatives incorrectes
    - Journalisation des tentatives

4. **Interface Administrateur**

    - Génération des codes quotidiens
    - Suivi en temps réel des émargements
    - Rapports détaillés par enseignant/cours/période
    - Export des données
    - Visualisation des emplacements sur carte

5. **Interface Enseignant**
    - Vue de l'emploi du temps du jour
    - Statut des émargements
    - Historique personnel
    - Saisie sécurisée du code

### Base de Données

1. **Table esbtp_daily_codes**

    - id (PK)
    - code (varchar, unique)
    - expiration (datetime)
    - is_active (boolean)
    - generated_by (FK -> users)
    - created_at, updated_at

2. **Table esbtp_teacher_attendances**
    - id (PK)
    - teacher_id (FK -> users)
    - course_id (FK -> esbtp_matieres)
    - daily_code_id (FK -> esbtp_daily_codes)
    - signed_at (datetime)
    - status (enum: present, late, absent)
    - latitude, longitude (decimal)
    - accuracy (decimal)
    - ip_address (varchar)
    - device_info (varchar)
    - created_at, updated_at

### Routes

-   GET /esbtp/teacher-attendance (index) - Vue enseignant
-   GET /esbtp/teacher-attendance/history (history) - Historique enseignant
-   POST /esbtp/teacher-attendance/sign (sign) - Émargement
-   GET /esbtp/teacher-attendance/report (report) - Rapport admin
-   POST /esbtp/teacher-attendance/generate-code (generateDailyCode) - Génération code

### Contrôleurs

-   TeacherAttendanceController
    -   index() : Vue principale enseignant
    -   history() : Historique des émargements
    -   sign() : Traitement de l'émargement
    -   report() : Rapport administrateur
    -   generateDailyCode() : Génération du code

### Vues

1. **resources/views/esbtp/teacher-attendance/index.blade.php**

    - Liste des cours du jour
    - Formulaire d'émargement
    - Statut des émargements
    - Géolocalisation

2. **resources/views/esbtp/teacher-attendance/report.blade.php**
    - Tableau de bord administrateur
    - Filtres et recherche
    - Génération de code
    - Export des données

### Corrections et Améliorations

1. **Namespace ESBTPEmploiTempsController**
    - Déplacé vers App\Http\Controllers\ESBTP
    - Mise à jour des routes correspondantes
    - Correction des imports

### Sécurité

-   Middleware d'authentification sur toutes les routes
-   Middleware de rôle pour les accès spécifiques
-   Validation des données côté serveur
-   Protection contre les tentatives multiples
-   Vérification de la géolocalisation

### Notes Techniques

1. **Géolocalisation**

    - Utilisation de l'API navigator.geolocation
    - Précision minimale requise : 100m
    - Stockage des coordonnées et de la précision

2. **Sécurité du Code**

    - Génération aléatoire sécurisée
    - Expiration automatique
    - Validation côté serveur
    - Protection contre la réutilisation

3. **Performance**
    - Indexation des colonnes clés
    - Pagination des résultats
    - Mise en cache des données statiques

### TODO

1. **Améliorations Futures**

    - Notifications push pour les rappels
    - Export automatique des rapports
    - Interface mobile dédiée
    - Intégration avec le système de paie

2. **Maintenance**
    - Nettoyage périodique des anciens codes
    - Archivage des données historiques
    - Optimisation des requêtes

## Gestion des Séances de Cours

### Modèles

-   `ESBTPTeacher`: Gestion des enseignants

    -   Attributs: name, email, phone, address, gender, joining_date, status
    -   Relations: seancesCours (hasMany ESBTPSeanceCours)

-   `ESBTPSeanceCours`: Gestion des séances de cours
    -   Attributs: emploi_temps_id, teacher_id, matiere_id, jour, heure_debut, heure_fin
    -   Relations:
        -   teacher (belongsTo ESBTPTeacher)
        -   matiere (belongsTo ESBTPMatiere)
        -   emploiTemps (belongsTo ESBTPEmploiTemps)

### Fonctionnalités

-   Création de séances de cours avec sélection d'enseignant
-   Validation des horaires et des contraintes
-   Intégration avec l'emploi du temps

### Routes

-   GET /esbtp/seances-cours/create : Formulaire de création
-   POST /esbtp/seances-cours : Enregistrement d'une nouvelle séance

## Gestion des Enseignants

### Modèle ESBTPTeacher

-   Table: `teachers`
-   Relations:
    -   `user`: belongsTo User (via employee_id)
    -   `seancesCours`: hasMany ESBTPSeanceCours
-   Accesseurs:
    -   `full_name`: Concatène firstname et lastname de l'utilisateur associé

### Structure de la base de données

-   Table `teachers`:
    -   employee_id: Clé étrangère vers users
    -   phone, address, gender, joining_date, status
-   Table `users`:
    -   firstname, lastname: Nom de l'enseignant
    -   email, password: Informations d'authentification

### Fonctionnalités

-   Affichage du nom complet des enseignants (prénom + nom)
-   Tri des enseignants par nom complet
-   Intégration avec le système d'authentification

## Teacher Management System

### Database Structure

-   `teachers` table with proper relationships to `users` table through `user_id`
-   Extended fields for academic information:
    -   employee_id (unique identifier)
    -   department and laboratory relationships
    -   specialties (JSON array)
    -   grade and status
    -   teaching hours tracking
    -   office location and hours
    -   research interests and publications
    -   website and availability

### Key Features

1. Teacher Profile Management

    - Personal information (name, email, etc.)
    - Academic credentials and status
    - Department and laboratory affiliations
    - Teaching load tracking
    - Research interests and specialties

2. Access Control

    - Role-based access (superAdmin, enseignant)
    - Proper middleware implementation
    - Secure password management

3. Data Integrity
    - Transaction support for all operations
    - Proper validation rules
    - Soft delete implementation

### Implementation Details

1. Models

    - ESBTPTeacher with proper relationships
    - JSON casting for array fields
    - Accessor for full name

2. Controllers

    - ESBTPEnseignantController for CRUD operations
    - Proper validation and error handling
    - Transaction support

3. Views
    - Organized sections for different types of information
    - Bootstrap 5 styling
    - Select2 for enhanced dropdowns
    - Bootstrap Tags Input for specialties and research interests

### Routes

-   Protected by authentication and role middleware
-   Resource routes for teacher management
-   Additional routes for specific actions (promote/demote)
