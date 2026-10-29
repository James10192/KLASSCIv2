# Module ESBTP-YAKRO

## Description
Ce module gère les inscriptions, les filières, les niveaux d'études, les années universitaires et les salles de classe pour l'ESBTP-YAKRO.

## Structure du module

### Contrôleurs
- `ESBTPFiliereController` : Gestion des filières et options
- `ESBTPNiveauEtudeController` : Gestion des niveaux d'études
- `ESBTPAnneeUniversitaireController` : Gestion des années universitaires
- `ESBTPSalleController` : Gestion des salles de classe
- `ESBTPInscriptionController` : Gestion des inscriptions des étudiants

### Modèles
- `ESBTPFiliere` : Filières et options
- `ESBTPNiveauEtude` : Niveaux d'études
- `ESBTPAnneeUniversitaire` : Années universitaires
- `ESBTPSalle` : Salles de classe
- `ESBTPInscription` : Inscriptions des étudiants

### Tables de base de données
- `e_s_b_t_p_filieres` : Filières et options
- `e_s_b_t_p_niveau_etudes` : Niveaux d'études
- `e_s_b_t_p_annee_universitaires` : Années universitaires
- `e_s_b_t_p_salles` : Salles de classe
- `e_s_b_t_p_inscriptions` : Inscriptions des étudiants

### Routes
Toutes les routes du module sont préfixées par `esbtp` et sont définies dans le fichier `routes/esbtp.php`.

## Fonctionnalités principales

### Gestion des filières
- Création, modification et suppression de filières
- Gestion des options de filières (une filière peut être une option d'une autre filière)
- Activation/désactivation des filières

### Gestion des niveaux d'études
- Création, modification et suppression de niveaux d'études
- Différents types de diplômes (BTS, Licence, Master, etc.)
- Années d'études (1ère année, 2ème année, etc.)
- Activation/désactivation des niveaux d'études

### Gestion des années universitaires
- Création, modification et suppression d'années universitaires
- Définition d'une année universitaire comme année en cours
- Activation/désactivation des années universitaires

### Gestion des salles de classe
- Création, modification et suppression de salles de classe
- Types de salles (Amphithéâtre, Salle de cours, Laboratoire, etc.)
- Localisation des salles (bâtiment, étage)
- Capacité d'accueil
- Activation/désactivation des salles

### Gestion des inscriptions
- Inscription des étudiants
- Association à une filière, un niveau d'études, une année universitaire
- Attribution d'une salle de classe

## Installation et configuration
1. Assurez-vous que les migrations sont exécutées :
   ```
   php artisan migrate
   ```
2. Le module est automatiquement chargé par le `RouteServiceProvider`

## Utilisation
Accédez au module depuis la barre latérale de l'application en cliquant sur "ESBTP-YAKRO".

## Développements futurs
- Gestion des emplois du temps
- Gestion des notes et relevés
- Gestion des stages et projets
- Statistiques et rapports 