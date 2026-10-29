# Scripts d'installation pour Smart School

Ce dossier contient des scripts utiles pour gérer l'installation de l'application Smart School.

## Scripts disponibles

### 1. Vérification de l'installation

```bash
php check_installation.php
```

Ce script vérifie si l'application est installée en contrôlant :
- L'existence du fichier d'installation (`storage/app/installed`)
- La présence d'un utilisateur administrateur dans la base de données
- Le nombre d'utilisateurs par rôle

### 2. Création d'un administrateur

```bash
php create_admin.php
```

Ce script crée un utilisateur administrateur avec les identifiants suivants :
- Email : admin@example.com
- Mot de passe : password
- Rôle : admin

Utile si l'application est marquée comme installée mais qu'il n'y a pas d'administrateur.

### 3. Réinitialisation de l'installation

```bash
php reset_installation.php
```

Ce script supprime le fichier d'installation (`storage/app/installed`) pour permettre de recommencer le processus d'installation. Les données existantes ne sont pas supprimées.

## Problèmes courants

### Redirection vers /login au lieu de /setup

Si vous êtes redirigé vers la page de connexion (`/login`) alors qu'il n'y a pas d'utilisateurs, c'est probablement parce que le fichier d'installation existe mais qu'il n'y a pas d'administrateur.

Solutions possibles :
1. Créer un administrateur avec le script `create_admin.php`
2. Réinitialiser l'installation avec le script `reset_installation.php`

### Erreur de connexion

Si vous ne pouvez pas vous connecter avec les identifiants créés, vérifiez :
1. Que l'utilisateur a bien le rôle "admin"
2. Que le mot de passe est correct
3. Que la base de données est correctement configurée

## Remarque

Ces scripts sont fournis à titre d'aide pour le développement et le débogage. Ils ne doivent pas être accessibles publiquement sur un serveur de production. 