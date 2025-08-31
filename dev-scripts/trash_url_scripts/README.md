# Scripts d'installation et de maintenance pour Smart School

Ce dossier contient des scripts utilitaires pour l'installation, la configuration et la maintenance de l'application Smart School.

## Scripts disponibles

### 1. Installation et configuration

- **setup_helper.php** : Affiche l'URL d'installation et fournit des instructions pour configurer l'application via l'interface web.
- **check_installation.php** : Vérifie l'état d'installation de l'application et fournit des informations sur la configuration actuelle.
- **mark_as_installed.php** : Marque manuellement l'application comme installée (utile si l'application est déjà configurée mais que le fichier d'installation est manquant).
- **run_migrations.php** : Exécute les migrations et les seeders de la base de données sans passer par l'interface web.
- **create_admin_user.php** : Crée un utilisateur administrateur dans la base de données.

### 2. Traduction et localisation

- **translation_helper.php** : Utilitaire pour gérer les traductions de l'application (affiche les langues disponibles et permet de changer la langue par défaut).
- **find_credentials.php** : Script pour trouver les identifiants de connexion des utilisateurs dans la base de données.

## Comment utiliser ces scripts

### Via la ligne de commande

1. Ouvrez un terminal
2. Naviguez vers le dossier racine de l'application
3. Exécutez le script souhaité avec PHP

Exemple :
```bash
cd /chemin/vers/smart_school_new
php trash_url_scripts/check_installation.php
```

### Via un navigateur web

Certains scripts peuvent également être exécutés via un navigateur web :

1. Accédez à l'URL du script dans votre navigateur
2. Suivez les instructions affichées

Exemple :
```
http://localhost/smart_school_new/trash_url_scripts/setup_helper.php
```

## Procédure d'installation recommandée

1. Vérifiez l'état actuel de l'installation avec `check_installation.php`
2. Si l'application n'est pas installée, utilisez l'une des méthodes suivantes :
   - **Méthode 1 (recommandée)** : Accédez à l'URL d'installation fournie par `setup_helper.php` et suivez les instructions dans l'interface web.
   - **Méthode 2 (avancée)** : Configurez manuellement le fichier `.env`, puis exécutez `run_migrations.php` suivi de `create_admin_user.php`.
3. Une fois l'installation terminée, vérifiez à nouveau avec `check_installation.php` pour confirmer que tout est correctement configuré.

## Résolution des problèmes courants

- **Erreur de connexion à la base de données** : Vérifiez les informations de connexion dans le fichier `.env`.
- **Tables manquantes** : Exécutez `run_migrations.php` pour créer les tables nécessaires.
- **Impossible de se connecter** : Utilisez `create_admin_user.php` pour créer un nouvel utilisateur administrateur.
- **Application non marquée comme installée** : Exécutez `mark_as_installed.php` pour créer le fichier d'installation.

## Sécurité

⚠️ **Attention** : Ces scripts contiennent des fonctionnalités sensibles qui peuvent affecter la sécurité et l'intégrité de votre application. Il est recommandé de :

1. Limiter l'accès à ces scripts aux administrateurs système uniquement
2. Supprimer ou déplacer ces scripts dans un emplacement sécurisé après l'installation
3. Ne jamais exposer ces scripts sur un serveur de production accessible au public

## Support

Pour toute assistance supplémentaire, veuillez consulter la documentation officielle ou contacter le support technique. 