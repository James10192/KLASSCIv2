# Scripts de gestion de base de données

Ce dossier contient des scripts pour faciliter la gestion de la base de données du projet.

## Scripts disponibles

### 1. Réinitialisation complète de la base de données

Ces scripts suppriment toutes les tables, exécutent toutes les migrations et remplissent la base de données avec les données de test.

- **Windows (Batch)**: `reset_database.bat`
- **Windows (PowerShell)**: `reset_database.ps1`
- **PHP**: `reset_database.php`

### 2. Migration fraîche avec seed

Ces scripts exécutent une migration fraîche (suppression de toutes les tables et recréation) et remplissent la base de données avec les données de test.

- **Windows (Batch)**: `migrate_fresh.bat`
- **Windows (PowerShell)**: `migrate_fresh.ps1`

### 3. Exécution des migrations

Ces scripts exécutent toutes les migrations sans supprimer les tables existantes.

- **Windows (Batch)**: `run_migrations.bat`
- **Windows (PowerShell)**: `run_migrations.ps1`
- **PHP**: `run_migrations.php`

### 4. Exécution des migrations et des seeders

Ces scripts exécutent toutes les migrations sans supprimer les tables existantes, puis remplissent la base de données avec les données de test.

- **Windows (Batch)**: `run_migrations_and_seed.bat`
- **Windows (PowerShell)**: `run_migrations_and_seed.ps1`
- **PHP**: `run_migrations_and_seed.php`

### 5. Scripts directs (recommandés)

Ces scripts sont plus robustes et tentent de trouver automatiquement PHP et le projet Laravel.

- **Windows (Batch)**: 
  - `direct_migrate_fresh.bat` - Exécute directement la commande artisan
  - `direct_migrate_fresh_with_path.bat` - Utilise le chemin complet vers PHP
  - `simple_migrate.bat` - Script très simple qui essaie toutes les versions de PHP

- **Windows (PowerShell)**: 
  - `direct_migrate_fresh.ps1` - Script PowerShell robuste
  - `wamp_migrate.ps1` - Trouve automatiquement la dernière version de PHP dans WAMP

### 6. Utilitaires

Ces scripts vous aident à configurer et gérer les autres scripts.

- **Windows (Batch)**: `find_php_path.bat` - Trouve les chemins PHP disponibles sur votre système
- **Windows (PowerShell)**: 
  - `find_php_path.ps1` - Version PowerShell plus détaillée pour trouver PHP
  - `update_php_path.ps1` - Met à jour automatiquement tous les scripts avec le bon chemin PHP

## Comment utiliser ces scripts

### Problèmes de chemin PHP

Si vous rencontrez des erreurs liées au chemin PHP, suivez ces étapes :

1. Exécutez `find_php_path.ps1` pour trouver le chemin correct vers PHP sur votre système
2. Exécutez `update_php_path.ps1` pour mettre à jour automatiquement tous les scripts
3. Ou essayez directement `simple_migrate.bat` qui essaie toutes les versions de PHP

### Utilisation des scripts Batch (.bat)

1. Double-cliquez sur le fichier `.bat` dans l'explorateur Windows
2. Ou exécutez-le depuis une invite de commande: `.\trash_scripts\simple_migrate.bat`

### Utilisation des scripts PowerShell (.ps1)

1. Clic droit sur le fichier `.ps1` et sélectionnez "Exécuter avec PowerShell"
2. Ou exécutez-le depuis PowerShell: `.\trash_scripts\wamp_migrate.ps1`

### Utilisation du script PHP

1. Exécutez-le depuis une invite de commande: `php .\trash_scripts\reset_database.php`

## Remarque importante

Si vous rencontrez des problèmes avec les scripts, essayez d'abord les scripts directs (section 5), en particulier `simple_migrate.bat` ou `wamp_migrate.ps1` qui sont les plus robustes.

Si vous devez ajuster le chemin vers PHP, utilisez `update_php_path.ps1` qui mettra à jour automatiquement tous les scripts. 