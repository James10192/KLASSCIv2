# Scripts pour l'application ESBTP

Ce dossier contient des scripts utilitaires pour l'application ESBTP School Management System.

## Scripts disponibles

### reset_app.bat (Windows)

Script batch pour réinitialiser l'application à son état initial sous Windows.

**Utilisation :**
```
reset_app.bat
```

**Fonctionnalités :**
- Efface tous les caches
- Supprime toutes les tables et les recrée
- Exécute toutes les migrations
- Alimente la base de données avec les données initiales
- Crée un utilisateur superadmin

### reset_app.sh (Linux/Mac)

Script bash pour réinitialiser l'application à son état initial sous Linux/Mac.

**Utilisation :**
```
chmod +x reset_app.sh  # Rendre le script exécutable (à faire une seule fois)
./reset_app.sh
```

**Fonctionnalités :**
- Efface tous les caches
- Supprime toutes les tables et les recrée
- Exécute toutes les migrations
- Alimente la base de données avec les données initiales
- Crée un utilisateur superadmin

### start_app.bat (Windows)

Script batch pour démarrer l'application sous Windows.

**Utilisation :**
```
start_app.bat
```

**Fonctionnalités :**
- Démarre le serveur de développement Laravel
- L'application sera accessible à l'adresse http://localhost:8000

### start_app.sh (Linux/Mac)

Script bash pour démarrer l'application sous Linux/Mac.

**Utilisation :**
```
chmod +x start_app.sh  # Rendre le script exécutable (à faire une seule fois)
./start_app.sh
```

**Fonctionnalités :**
- Démarre le serveur de développement Laravel
- L'application sera accessible à l'adresse http://localhost:8000

### update_app.bat (Windows)

Script batch pour mettre à jour l'application sous Windows.

**Utilisation :**
```
update_app.bat
```

**Fonctionnalités :**
- Récupère les dernières modifications du dépôt Git
- Met à jour les dépendances PHP
- Met à jour les dépendances JavaScript
- Compile les assets
- Exécute les nouvelles migrations
- Efface tous les caches

### update_app.sh (Linux/Mac)

Script bash pour mettre à jour l'application sous Linux/Mac.

**Utilisation :**
```
chmod +x update_app.sh  # Rendre le script exécutable (à faire une seule fois)
./update_app.sh
```

**Fonctionnalités :**
- Récupère les dernières modifications du dépôt Git
- Met à jour les dépendances PHP
- Met à jour les dépendances JavaScript
- Compile les assets
- Exécute les nouvelles migrations
- Efface tous les caches

### backup_db.bat (Windows)

Script batch pour sauvegarder la base de données sous Windows.

**Utilisation :**
```
backup_db.bat
```

**Fonctionnalités :**
- Lit la configuration de la base de données depuis le fichier .env
- Crée un dossier de sauvegarde dans storage/app/backups si nécessaire
- Génère un nom de fichier avec la date et l'heure actuelles
- Utilise mysqldump pour créer une sauvegarde complète de la base de données
- Enregistre la sauvegarde dans le dossier spécifié

### backup_db.sh (Linux/Mac)

Script bash pour sauvegarder la base de données sous Linux/Mac.

**Utilisation :**
```
chmod +x backup_db.sh  # Rendre le script exécutable (à faire une seule fois)
./backup_db.sh
```

**Fonctionnalités :**
- Lit la configuration de la base de données depuis le fichier .env
- Crée un dossier de sauvegarde dans storage/app/backups si nécessaire
- Génère un nom de fichier avec la date et l'heure actuelles
- Utilise mysqldump pour créer une sauvegarde complète de la base de données
- Enregistre la sauvegarde dans le dossier spécifié

### create_package.bat (Windows)

Script batch pour créer un package d'installation de l'application sous Windows.

**Utilisation :**
```
create_package.bat
```

**Fonctionnalités :**
- Crée un dossier avec la date actuelle pour le package
- Copie les fichiers essentiels de l'application (sans vendor et node_modules)
- Crée des scripts d'installation pour Windows et Linux/Mac
- Génère un guide d'installation rapide
- Crée un fichier ZIP du package

### create_package.sh (Linux/Mac)

Script bash pour créer un package d'installation de l'application sous Linux/Mac.

**Utilisation :**
```
chmod +x create_package.sh  # Rendre le script exécutable (à faire une seule fois)
./create_package.sh
```

**Fonctionnalités :**
- Crée un dossier avec la date actuelle pour le package
- Copie les fichiers essentiels de l'application (sans vendor et node_modules)
- Crée des scripts d'installation pour Windows et Linux/Mac
- Génère un guide d'installation rapide
- Crée une archive tar.gz du package

## Identifiants par défaut

Après l'exécution des scripts de réinitialisation, vous pouvez vous connecter avec les identifiants suivants :

### Superadmin
- Email : admin@esbtp.ci
- Mot de passe : admin123

## Contenu du dossier

- `esbtp-colors.css` : Fichier CSS contenant la charte graphique ESBTP
- `summary.md` : Résumé des modifications apportées au système
- `guide-developpeurs.md` : Guide pour les développeurs sur la maintenance de la charte graphique
- `publish-css.sh` : Script Bash pour publier le fichier CSS dans le répertoire public
- `publish-css.ps1` : Script PowerShell pour publier le fichier CSS dans le répertoire public
- `restart-app.sh` : Script Bash pour redémarrer l'application Laravel
- `restart-app.ps1` : Script PowerShell pour redémarrer l'application Laravel

## Comment utiliser ces fichiers

### Publication du fichier CSS

Pour rendre le fichier CSS disponible dans votre application, vous devez le publier dans le répertoire public. Utilisez l'un des scripts suivants selon votre système d'exploitation :

#### Sous Linux/Mac (Bash)

```bash
cd /chemin/vers/smart_school_new
./scripts_esbtp/publish-css.sh
```

#### Sous Windows (PowerShell)

```powershell
cd C:\chemin\vers\smart_school_new
.\scripts_esbtp\publish-css.ps1
```

### Redémarrage de l'application

Pour nettoyer le cache et redémarrer le serveur de développement, utilisez l'un des scripts suivants :

#### Sous Linux/Mac (Bash)

```bash
cd /chemin/vers/smart_school_new
./scripts_esbtp/restart-app.sh
```

#### Sous Windows (PowerShell)

```powershell
cd C:\chemin\vers\smart_school_new
.\scripts_esbtp\restart-app.ps1
```

Ces scripts effectuent les opérations suivantes :
1. Nettoyage du cache de l'application
2. Publication du fichier CSS ESBTP
3. Démarrage du serveur de développement

### Inclusion du fichier CSS dans vos pages

Une fois le fichier CSS publié, vous pouvez l'inclure dans vos pages Blade :

```html
<link rel="stylesheet" href="{{ asset('css/esbtp-colors.css') }}">
```

### Consultation du guide développeur

Le fichier `guide-developpeurs.md` contient des instructions détaillées sur la façon d'utiliser et de maintenir la charte graphique ESBTP. Consultez ce guide avant de faire des modifications au design de l'application.

## Palette de couleurs ESBTP

- **Vert ESBTP**: `#01632f`
- **Orange ESBTP**: `#f29400`
- **Blanc**: `#ffffff`

## Maintenance

Pour mettre à jour la charte graphique, modifiez le fichier `esbtp-colors.css` puis exécutez le script de publication pour mettre à jour la version dans le répertoire public.

## Contact

Pour toute question concernant ces scripts, contactez l'administrateur système à [admin@esbtp.edu]. 