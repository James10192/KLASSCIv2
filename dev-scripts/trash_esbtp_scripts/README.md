# Scripts ESBTP

Ce dossier contient les scripts et ressources utilisés pour la personnalisation du système de gestion scolaire pour l'École Supérieure du Bâtiment et des Travaux Publics (ESBTP).

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