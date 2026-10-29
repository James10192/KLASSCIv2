# Guide d'Utilisation - Améliorations de la Page d'Accueil ESBTP

## Introduction

Ce guide explique les modifications apportées à la page d'accueil de l'application ESBTP et comment les utiliser efficacement. Les améliorations visent à moderniser l'interface, mettre à jour les informations de contact et améliorer l'expérience utilisateur.

## Fichiers Modifiés

1. **welcome.blade.php** - Page d'accueil principale
2. **esbtp-colors.css** - Fichier de styles CSS pour la cohérence visuelle

## Nouvelles Fonctionnalités

### 1. Design Responsive

La page s'adapte désormais à tous les appareils (ordinateurs, tablettes, smartphones). Pour tester :
- Redimensionnez votre navigateur
- Utilisez les outils de développement de votre navigateur (F12) et activez le mode responsive

### 2. Animations au Défilement

Des animations apparaissent lorsque l'utilisateur fait défiler la page :
- Les sections apparaissent progressivement
- Les éléments s'animent de manière fluide

### 3. Navigation Améliorée

- La barre de navigation change de couleur lors du défilement
- Les liens mènent directement aux sections correspondantes avec défilement fluide
- Le menu s'adapte aux appareils mobiles

### 4. Informations de Contact Mises à Jour

Les coordonnées ont été mises à jour avec les informations réelles de l'ESBTP Yamoussoukro :
- Adresse : Quartier Millionnaire, Yamoussoukro
- Téléphone : +225 27 30 64 66 75 / +225 07 07 43 43 75
- Email : info@esbtp-ci.net
- Carte Google Maps intégrée avec l'emplacement exact

## Comment Modifier le Contenu

### Modifier le Texte

Pour modifier le texte de la page d'accueil :
1. Ouvrez le fichier `resources/views/welcome.blade.php`
2. Localisez la section que vous souhaitez modifier
3. Modifiez le texte entre les balises HTML
4. Enregistrez le fichier

### Modifier les Styles

Pour ajuster les couleurs, polices ou espacements :
1. Ouvrez le fichier `public/css/esbtp-colors.css`
2. Modifiez les variables CSS dans la section `:root`
3. Enregistrez le fichier

### Ajouter une Nouvelle Section

Pour ajouter une nouvelle section à la page d'accueil :
1. Ouvrez le fichier `resources/views/welcome.blade.php`
2. Copiez la structure d'une section existante
3. Collez-la à l'endroit souhaité
4. Modifiez l'ID, le titre et le contenu
5. Ajoutez un lien dans la navigation si nécessaire

## Sauvegarde des Fichiers

Un script de sauvegarde a été créé pour préserver vos modifications :
1. Accédez au dossier `scripts_projet`
2. Exécutez le script PowerShell `backup_script.ps1`
3. Les fichiers seront sauvegardés dans un dossier daté

## Conseils pour les Futures Améliorations

1. **Images** : Utilisez des images optimisées (format WebP) pour améliorer les performances
2. **Contenu** : Mettez régulièrement à jour les informations pour maintenir la pertinence
3. **SEO** : Ajoutez des balises meta description et des attributs alt aux images
4. **Accessibilité** : Assurez-vous que le contraste des couleurs est suffisant pour tous les utilisateurs

## Support

Pour toute question ou assistance concernant ces modifications, consultez le fichier `recap_modifications.md` ou contactez l'équipe de développement. 