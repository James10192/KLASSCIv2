# Scripts du Projet ESBTP

Ce dossier contient les scripts utilitaires pour le projet ESBTP. Ces scripts facilitent la maintenance, la sauvegarde et la gestion du site web.

## Liste des Scripts

### 1. backup_projet.ps1

**Description** : Script PowerShell pour sauvegarder les fichiers importants du projet.

**Utilisation** :
```powershell
.\backup_projet.ps1
```

**Fichiers sauvegardés** :
- resources/views/welcome.blade.php (page d'accueil)
- public/css/esbtp-colors.css (styles personnalisés)
- public/img/esbtp_logo.png (logo standard)
- public/img/esbtp_logo_white.png (logo blanc)

### 2. check_integrity.ps1

**Description** : Script PowerShell pour vérifier l'intégrité des fichiers principaux du projet.

**Utilisation** :
```powershell
.\check_integrity.ps1
```

**Fonctionnalités** :
- Vérifie l'existence des fichiers essentiels
- Contrôle la taille minimale des fichiers
- Affiche un rapport détaillé avec code couleur
- Calcule le pourcentage de fichiers valides

### 3. create_logos.ps1

**Description** : Script PowerShell pour créer des fichiers HTML temporaires pour visualiser les logos.

**Utilisation** :
```powershell
.\create_logos.ps1
```

**Fonctionnalités** :
- Crée un fichier HTML pour visualiser le logo standard
- Crée un fichier HTML pour visualiser le logo blanc sur fond sombre

### 4. update_contact_info.ps1

**Description** : Script PowerShell pour mettre à jour les informations de contact sur la page d'accueil.

**Utilisation** :
```powershell
.\update_contact_info.ps1
```

**Fonctionnalités** :
- Interface interactive pour saisir les nouvelles informations
- Mise à jour de l'adresse, téléphones, email, site web
- Mise à jour de l'iframe Google Maps
- Sauvegarde automatique avant modification
- Confirmation avant application des changements

## Bonnes Pratiques

1. **Sauvegarde** : Exécutez régulièrement le script de sauvegarde, surtout avant d'apporter des modifications importantes.
2. **Vérification** : Utilisez le script de vérification d'intégrité pour vous assurer que tous les fichiers essentiels sont présents et valides.
3. **Chemins** : Vérifiez que les chemins dans les scripts correspondent à votre environnement.
4. **Permissions** : Assurez-vous d'avoir les droits d'administrateur pour exécuter ces scripts.

## Compatibilité

Ces scripts sont conçus pour fonctionner sur Windows avec PowerShell. Pour une utilisation sur Linux, des modifications seraient nécessaires pour adapter les chemins et les commandes.

## Documentation

Pour une documentation plus complète du projet, consultez le fichier `documentation.md` dans ce même dossier. 