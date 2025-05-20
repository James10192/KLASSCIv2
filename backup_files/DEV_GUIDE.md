# Guide du Développeur ESBTP

## Architecture du système

L'application ESBTP suit une architecture MVC (Modèle-Vue-Contrôleur) basée sur Laravel. Les principales modifications incluent l'ajout de fonctionnalités de gestion des enseignants et l'intégration de la comptabilité au tableau de bord du super administrateur.

### Structure des fichiers

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── SuperAdminTeacherController.php  # Gestion des enseignants
│   │   └── ESBTPComptabiliteController.php  # Gestion de la comptabilité
│   ├── Middleware/
│   │   └── CheckRole.php                   # Vérification des rôles utilisateur
├── Models/
│   ├── Teacher.php                        # Modèle pour les enseignants
│   ├── Department.php                     # Modèle pour les départements
│   ├── Depense.php                        # Modèle pour les dépenses
│   └── User.php                           # Modèle utilisateur avec rôles
resources/
├── views/
│   ├── esbtp/
│   │   ├── teachers/                      # Vues pour la gestion des enseignants
│   │   │   ├── index.blade.php            # Liste des enseignants
│   │   │   ├── create.blade.php           # Création d'un enseignant
│   │   │   ├── edit.blade.php             # Modification d'un enseignant
│   │   │   └── show.blade.php             # Détails d'un enseignant
│   │   └── comptabilite/                  # Vues pour la comptabilité
│   ├── layouts/
│   │   └── app.blade.php                  # Layout principal avec la barre latérale
routes/
└── web.php                               # Définition des routes
```

## Rôles et Permissions

Le système utilise un système d'authentification basé sur les rôles avec trois rôles principaux :
- **superAdmin**: Accès complet à toutes les fonctionnalités
- **secretaire**: Accès limité à la gestion des étudiants et certaines fonctionnalités
- **etudiant**: Accès uniquement à son propre profil et informations connexes

### Mise en œuvre des rôles

Le middleware `CheckRole` vérifie le rôle de l'utilisateur connecté et restreint l'accès en conséquence. Les routes protégées spécifient le rôle requis.

## Ajout de la gestion des enseignants

Le `SuperAdminTeacherController` gère toutes les opérations CRUD pour les enseignants. Le modèle `Teacher` est relié au modèle `Department` via une relation many-to-one.

### Routes pour les enseignants

```php
Route::middleware(['auth', 'check_role:superAdmin'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::resource('teachers', 'SuperAdminTeacherController');
});
```

### Validation des données d'enseignants

Les règles de validation suivantes sont appliquées lors de la création/modification d'un enseignant :
- Nom, prénom, email et téléphone requis
- Email doit être unique
- Téléphone doit être unique et suivre un format valide

## Intégration de la comptabilité

Le module de comptabilité est accessible via le menu latéral pour les super administrateurs. Il permet la gestion des paiements, des dépenses et la génération de rapports financiers.

### Routes pour la comptabilité

```php
Route::middleware(['auth', 'check_role:superAdmin'])->prefix('comptabilite')->name('comptabilite.')->group(function () {
    Route::get('/', 'ESBTPComptabiliteController@index')->name('index');
    Route::resource('paiements', 'ESBTPPaiementController');
    Route::resource('depenses', 'ESBTPDepenseController');
    Route::get('/rapports', 'ESBTPComptabiliteController@rapports')->name('rapports');
});
```

## Scripts utilitaires

Plusieurs scripts PHP ont été créés pour faciliter les tests et la maintenance :

- **check_superadmin_access.php**: Vérifie l'accès du super administrateur aux différentes fonctionnalités
- **test_login.php**: Teste l'authentification avec le compte super administrateur
- **reset_superadmin_password.php**: Réinitialise le mot de passe du super administrateur
- **test_routes_access.php**: Vérifie l'accès aux routes protégées

### Exécution des scripts

Pour exécuter ces scripts, utilisez la commande Artisan Tinker :

```bash
php artisan tinker --execute="require('script_name.php');"
```

## Modification du menu latéral

Le menu latéral a été modifié pour inclure un lien vers la gestion des enseignants dans la section Administration pour les super administrateurs.

```php
@if(Auth::check() && Auth::user()->role === 'superAdmin')
    <li class="nav-item">
        <a class="nav-link" href="{{ route('esbtp.teachers.index') }}">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Gestion des Enseignants</span>
        </a>
    </li>
@endif
```

## Tests et dépannage

Pour tester le système, utilisez les scripts utilitaires mentionnés ci-dessus. En cas de problème d'authentification, vous pouvez réinitialiser le mot de passe du super administrateur avec le script `reset_superadmin_password.php`.

## Développements futurs

Pour étendre le système, envisagez :

1. Amélioration du système de gestion des enseignants avec un calendrier des cours
2. Développement d'un module de messagerie interne entre enseignants et administration
3. Mise en place d'un système d'évaluation des enseignants par les étudiants 