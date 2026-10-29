# Gestion des Filières et Niveaux d'Études ESBTP

Ce module permet de gérer les filières, les niveaux d'études et les inscriptions des étudiants à l'ESBTP.

## Modèles créés

1. **ESBTPFiliere** : Gestion des filières (Génie Civil, Mine-Géologie-Pétrole) et leurs options
2. **ESBTPNiveauEtude** : Gestion des niveaux d'études (BTS1, BTS2, etc.)
3. **ESBTPAnneeUniversitaire** : Gestion des années universitaires (2023-2024, 2024-2025, etc.)
4. **ESBTPInscription** : Gestion des inscriptions des étudiants (lien entre étudiant, filière, niveau d'études et année universitaire)

## Installation

### Exécution des migrations et seeders

Vous pouvez utiliser les scripts fournis pour exécuter les migrations et les seeders :

- **Windows (Batch)** : Exécutez `run_migrations_seeders.bat`
- **Windows (PowerShell)** : Exécutez `run_migrations_seeders.ps1`

Ou exécutez manuellement les commandes suivantes :

```bash
php artisan migrate
php artisan db:seed --class=ESBTPFiliereSeeder
php artisan db:seed --class=ESBTPNiveauEtudeSeeder
php artisan db:seed --class=ESBTPAnneeUniversitaireSeeder
php artisan optimize:clear
```

### Ajout des éléments de menu dans la sidebar

Ajoutez le code HTML fourni dans le fichier `sidebar_menu.txt` à la section ESBTP-YAKRO du fichier `resources/views/layouts/app.blade.php`.

## Utilisation des modèles

### Filières

```php
// Récupérer toutes les filières principales
$filieres = ESBTPFiliere::whereNull('parent_id')->get();

// Récupérer toutes les options d'une filière
$genieCivil = ESBTPFiliere::where('code', 'GC')->first();
$options = $genieCivil->options;

// Vérifier si une filière est une option
$batiment = ESBTPFiliere::where('code', 'GC-BAT')->first();
if ($batiment->isOption()) {
    $filiereParente = $batiment->parent;
}
```

### Niveaux d'études

```php
// Récupérer tous les niveaux d'études
$niveaux = ESBTPNiveauEtude::all();

// Récupérer les niveaux d'études BTS
$niveauxBTS = ESBTPNiveauEtude::where('type', 'BTS')->get();
```

### Années universitaires

```php
// Récupérer l'année universitaire en cours
$anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

// Définir une année universitaire comme l'année en cours
$annee = ESBTPAnneeUniversitaire::find(2);
$annee->setAsCurrent();
```

### Inscriptions

```php
// Inscrire un étudiant
$inscription = ESBTPInscription::create([
    'student_id' => 1,
    'filiere_id' => 2,
    'niveau_etude_id' => 1,
    'annee_universitaire_id' => 2,
    'inscription_date' => now(),
    'status' => ESBTPInscription::STATUS_ACTIVE,
]);

// Récupérer les inscriptions d'un étudiant
$etudiant = Student::find(1);
$inscriptions = $etudiant->esbtpInscriptions;

// Vérifier si un étudiant est inscrit à une filière
if ($etudiant->isInscritFiliere(2)) {
    // L'étudiant est inscrit à la filière avec l'ID 2
}

// Vérifier si un étudiant est inscrit à un niveau d'études pour une année universitaire spécifique
if ($etudiant->isInscritNiveauEtude(1, 2)) {
    // L'étudiant est inscrit au niveau d'études avec l'ID 1 pour l'année universitaire avec l'ID 2
}
```

## Structure des données

### Filières

- **Génie Civil** (filière principale)
  - Option Bâtiment
  - Option Travaux Publics
  - Option Géomètre-Topographe
  - Option Urbanisme
- **Mine - Géologie - Pétrole** (filière principale)

### Niveaux d'études

- **BTS**
  - Première année BTS
  - Deuxième année BTS 