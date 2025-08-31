# Configuration Multi-Plateforme pour Smart School

Ce guide vous aidera à configurer l'application Smart School pour qu'elle fonctionne correctement sur Windows et Linux.

## Problème

Lorsque vous travaillez en collaboration sur un projet avec Git, il peut y avoir des problèmes de compatibilité entre différents systèmes d'exploitation, notamment :

- Chemins de fichiers différents (Windows utilise des backslashes `\`, Linux utilise des slashes `/`)
- Configurations de base de données différentes
- Chemins absolus codés en dur dans le code

## Solution

Nous avons mis en place plusieurs mécanismes pour assurer la compatibilité entre Windows et Linux :

1. **Chemins relatifs** : Tous les chemins dans l'application utilisent maintenant des chemins relatifs avec `__DIR__` au lieu de chemins absolus.
2. **Script de configuration** : Un script PHP qui détecte automatiquement le système d'exploitation et configure l'application en conséquence.
3. **Seeders de base de données** : Des seeders pour créer rapidement les données nécessaires au fonctionnement de l'application.

## Comment utiliser cette configuration

### 1. Cloner le dépôt Git

```bash
# Sur Windows (avec WAMP)
git clone <url-du-depot> C:/wamp64/www/smart_school_new

# Sur Linux (avec XAMPP)
git clone <url-du-depot> /opt/lampp/htdocs/smart_school_new
```

### 2. Installer les dépendances

```bash
cd smart_school_new
composer install
```

### 3. Configurer l'environnement

Copiez le fichier `.env.example` en `.env` et configurez-le selon votre environnement :

```bash
cp .env.example .env
```

Modifiez les paramètres de base de données dans le fichier `.env` :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_school_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Exécuter le script de configuration multi-plateforme

```bash
# Sur Windows
php cross_platform_scripts/setup_cross_platform.php

# Sur Linux
php cross_platform_scripts/setup_cross_platform.php
```

Ce script va :
- Détecter votre système d'exploitation
- Vérifier si les dépendances sont installées
- Vérifier si le fichier .env existe
- Tester la connexion à la base de données
- Créer la base de données si elle n'existe pas
- Exécuter les migrations et les seeders si vous le souhaitez

### 5. Démarrer l'application

```bash
php artisan serve
```

## Comptes utilisateurs créés par les seeders

Les seeders créent automatiquement les comptes suivants :

| Rôle        | Email                   | Mot de passe |
|-------------|-------------------------|--------------|
| Super Admin | superadmin@example.com  | password     |
| Admin       | admin@example.com       | password     |
| Enseignant  | teacher@example.com     | password     |
| Parent      | parent@example.com      | password     |
| Étudiant    | student@example.com     | password     |

## Résolution des problèmes courants

### Erreur de chemin d'autoload

Si vous obtenez une erreur comme celle-ci :
```
Failed opening required '/opt/lampp/htdocs/ESBTP-yAKROv2/vendor/autoload.php'
```

C'est que le chemin d'autoload est codé en dur. Vérifiez le fichier `public/index.php` et assurez-vous qu'il utilise un chemin relatif :

```php
require __DIR__.'/../vendor/autoload.php';
```

### Erreur de connexion à la base de données

Assurez-vous que :
1. Le serveur MySQL est en cours d'exécution
2. Les informations de connexion dans le fichier .env sont correctes
3. La base de données existe

Vous pouvez utiliser le script `setup_cross_platform.php` pour vérifier et corriger ces problèmes.

## Collaboration entre Windows et Linux

Lorsque vous travaillez en collaboration avec Git entre Windows et Linux, suivez ces bonnes pratiques :

1. **Évitez les chemins absolus** : N'utilisez jamais de chemins absolus dans votre code.
2. **Utilisez des chemins relatifs** : Utilisez toujours `__DIR__` ou des chemins relatifs.
3. **Testez sur les deux plateformes** : Si possible, testez vos modifications sur les deux systèmes d'exploitation avant de les pousser.
4. **Utilisez le script de configuration** : Exécutez le script `setup_cross_platform.php` après chaque pull important. 