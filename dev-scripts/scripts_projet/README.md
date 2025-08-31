# Scripts de Maintenance du Projet ESBTP

Ce dossier contient des scripts et des documents utiles pour la maintenance et la gestion du projet ESBTP.

## Contenu du Dossier

### Documents
- **recap_modifications.md** - Résumé des modifications apportées au projet
- **guide_utilisation.md** - Guide d'utilisation pour les nouvelles fonctionnalités
- **README.md** - Ce fichier d'information

### Scripts PowerShell
- **backup_script.ps1** - Script de sauvegarde des fichiers modifiés
- **check_integrity.ps1** - Script de vérification de l'intégrité des fichiers

## Comment Utiliser les Scripts

### Script de Sauvegarde

Ce script crée une copie de sauvegarde des fichiers importants du projet dans un dossier daté.

```powershell
# Exécuter depuis le dossier scripts_projet
.\backup_script.ps1
```

Les sauvegardes sont stockées dans le sous-dossier `backups` avec un horodatage.

### Script de Vérification d'Intégrité

Ce script vérifie que les fichiers essentiels existent et contiennent les éléments requis.

```powershell
# Exécuter depuis le dossier scripts_projet
.\check_integrity.ps1
```

Le résultat de la vérification est affiché dans la console et enregistré dans un fichier journal.

## Bonnes Pratiques

1. **Exécutez le script de sauvegarde avant toute modification majeure** pour pouvoir revenir en arrière si nécessaire.
2. **Vérifiez régulièrement l'intégrité des fichiers** pour détecter d'éventuels problèmes.
3. **Consultez le guide d'utilisation** avant de modifier les fichiers pour comprendre leur structure.
4. **Mettez à jour le récapitulatif des modifications** lorsque vous apportez des changements importants.

## Remarques

- Ces scripts sont conçus pour fonctionner sur Windows avec PowerShell.
- Les chemins de fichiers sont relatifs au dossier `scripts_projet`.
- Pour toute question ou problème, consultez la documentation ou contactez l'équipe de développement. 