# Installation System Documentation

Ce dossier contient les vues pour le système d'installation de l'application Smart School.

## Structure des fichiers

- `layout.blade.php` - Le layout principal utilisé par toutes les vues d'installation
- `welcome.blade.php` - Page d'accueil de l'installation avec vérification des prérequis
- `database.blade.php` - Configuration de la base de données
- `migration.blade.php` - Exécution des migrations pour créer les tables
- `admin.blade.php` - Création du compte administrateur et configuration de l'école
- `complete.blade.php` - Page de confirmation d'installation terminée

## Processus d'installation

Le processus d'installation se déroule en 5 étapes:

1. **Bienvenue et vérification des prérequis**
   - Vérification de la version PHP
   - Vérification des extensions PHP requises
   - Vérification des permissions des dossiers

2. **Configuration de la base de données**
   - Saisie des informations de connexion à la base de données
   - Test de la connexion
   - Enregistrement des paramètres dans le fichier .env

3. **Migrations**
   - Création des tables dans la base de données
   - Exécution des seeders pour les données initiales

4. **Création de l'administrateur**
   - Création du compte administrateur principal
   - Configuration des informations de l'école

5. **Finalisation**
   - Récapitulatif de l'installation
   - Finalisation et redirection vers le tableau de bord

## Contrôleur d'installation

Le contrôleur `InstallController` gère toutes les routes d'installation et se trouve dans `app/Http/Controllers/InstallController.php`.

## Middleware

Le middleware `CheckInstalled` vérifie si l'application est déjà installée et redirige l'utilisateur en conséquence. Il se trouve dans `app/Http/Middleware/CheckInstalled.php`.

## Routes d'installation

Les routes d'installation sont définies dans `routes/web.php` et sont préfixées par `/install`.

```php
// Installation Routes
Route::group(['prefix' => 'install', 'middleware' => ['web']], function () {
    Route::get('/', [InstallController::class, 'index'])->name('install.index');
    Route::get('/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/database', [InstallController::class, 'setupDatabase'])->name('install.setup-database');
    Route::get('/migration', [InstallController::class, 'migration'])->name('install.migration');
    Route::post('/migration', [InstallController::class, 'runMigration'])->name('install.run-migration');
    Route::get('/admin', [InstallController::class, 'admin'])->name('install.admin');
    Route::post('/admin', [InstallController::class, 'createAdmin'])->name('install.create-admin');
    Route::get('/complete', [InstallController::class, 'complete'])->name('install.complete');
    Route::post('/finalize', [InstallController::class, 'finalize'])->name('install.finalize');
});
```

## Personnalisation

Pour personnaliser l'apparence du système d'installation, vous pouvez modifier le fichier `layout.blade.php` qui contient les styles et la structure principale.

## Dépannage

Si vous rencontrez des problèmes lors de l'installation:

1. Vérifiez les logs dans `storage/logs/laravel.log`
2. Assurez-vous que les permissions des dossiers sont correctes
3. Vérifiez que la base de données existe et que les identifiants sont corrects
4. Assurez-vous que toutes les extensions PHP requises sont installées 