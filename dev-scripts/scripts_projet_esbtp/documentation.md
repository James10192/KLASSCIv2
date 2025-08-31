# Documentation du Projet ESBTP

## Présentation

Ce document décrit les modifications apportées au site web de l'École Supérieure du Bâtiment et des Travaux Publics (ESBTP) de Yamoussoukro. Le projet a consisté à améliorer la page d'accueil et à mettre à jour les informations de contact.

## Modifications Apportées

### 1. Page d'Accueil

La page d'accueil a été entièrement redessinée avec :

- **Design moderne** : Utilisation de Bootstrap 5 et de composants visuels attrayants
- **Animations** : Intégration de la bibliothèque AOS (Animate On Scroll) pour des animations fluides
- **Navigation améliorée** : Barre de navigation responsive qui change de couleur au défilement
- **Section Hero** : Grande image avec effet parallax et texte centré
- **Sections structurées** : À propos, Formations, Contact avec mise en page claire

### 2. Informations de Contact

Les informations de contact ont été mises à jour avec les données réelles de l'ESBTP Yamoussoukro :

- **Adresse** : Quartier Millionnaire, Yamoussoukro, Côte d'Ivoire
- **Téléphone** : +225 27 30 64 66 75 / +225 07 07 43 43 75
- **Email** : info@esbtp-ci.net
- **Site web** : www.esbtp-ci.net
- **Carte Google Maps** : Intégration d'une carte interactive avec les coordonnées exactes

### 3. Logos

Les logos ESBTP ont été intégrés à deux endroits :

- **Navbar** : Logo standard sur fond blanc
- **Footer** : Logo blanc sur fond sombre

## Structure des Fichiers

- **welcome.blade.php** : Page d'accueil principale (Laravel Blade)
- **esbtp-colors.css** : Feuille de style CSS personnalisée
- **esbtp_logo.png** : Logo standard
- **esbtp_logo_white.png** : Logo blanc pour le footer

## Technologies Utilisées

- **Frontend** :
  - HTML5
  - CSS3
  - JavaScript
  - Bootstrap 5
  - Font Awesome (icônes)
  - AOS (animations)
  - Google Fonts (Poppins et Roboto)

- **Backend** :
  - Laravel (framework PHP)

## Fonctionnalités JavaScript

Le site inclut plusieurs fonctionnalités JavaScript :

1. **Animations au défilement** : Utilisation de la bibliothèque AOS
2. **Navbar dynamique** : Changement de couleur au défilement
3. **Défilement fluide** : Navigation douce vers les sections lors du clic sur les liens
4. **Responsive** : Adaptation à tous les appareils (mobile, tablette, desktop)

## Maintenance

Pour maintenir le site :

1. **Mise à jour des informations** : Modifier directement le fichier welcome.blade.php
2. **Sauvegarde** : Utiliser le script backup_projet.ps1 avant toute modification importante
3. **Images** : Stocker les nouvelles images dans le dossier public/images/
4. **Styles** : Modifier le fichier esbtp-colors.css pour les changements de style

## Recommandations pour Améliorations Futures

1. **Multilinguisme** : Ajouter une version anglaise du site
2. **Blog/Actualités** : Créer une section pour les actualités de l'école
3. **Espace étudiant** : Développer un portail étudiant plus complet
4. **Formulaire de contact** : Connecter le formulaire à un système d'email
5. **Galerie photos** : Ajouter une galerie des installations et événements de l'école 