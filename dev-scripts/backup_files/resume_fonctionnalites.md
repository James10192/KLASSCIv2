# Résumé des fonctionnalités - Système de Gestion ESBTP

Ce document résume les fonctionnalités implémentées dans le système de gestion ESBTP conformément aux spécifications du fichier "logique de code ESBTP.txt".

## 1. Structure académique

### Filières

-   **Génie Civil** avec ses options :
    -   Bâtiment
    -   Travaux Publics
    -   Urbanisme
    -   Géomètre-Topographe
-   **Mine - Géologie - Pétrole**

### Niveaux d'études

-   **BTS**
    -   Première année
    -   Deuxième année

### Gestion des années universitaires

-   Création et gestion des années académiques
-   Marquage de l'année en cours

## 2. Système d'installation

-   Interface guidée pour l'installation de l'application
-   Configuration de la base de données avec validation de la connexion
-   Création automatique de la base de données si inexistante
-   Exécution des migrations pour créer les tables nécessaires
-   Vérification de l'adéquation entre les migrations et les tables existantes
-   Création du compte administrateur
-   Finition de l'installation et redirection vers le tableau de bord

## 3. Gestion des utilisateurs et rôles

### Rôles implémentés

-   **Superadmin** : Gestion complète de l'application
-   **Data Enters/Secrétaires** : Gestion des données (inscriptions, notes, présences, messages, emplois du temps)
-   **Étudiants** : Accès limité à leurs propres informations

### Authentification

-   Authentification par nom d'utilisateur (format : prenom.nom)
-   Mot de passe généré automatiquement pour les nouveaux comptes

## 4. Fonctionnalités principales

### Gestion des classes

-   Création des classes associant filières, niveaux et années universitaires
-   Attribution des étudiants aux classes

### Inscription des étudiants

-   Formulaires d'inscription des étudiants
-   Gestion des informations personnelles
-   Création automatique des comptes utilisateurs
-   Association avec les parents/tuteurs

### Système de messagerie

-   Envoi de messages aux étudiants
-   Sélection multiple des destinataires (par classe, filière, niveau)
-   Consultation des messages reçus

### Système de notification

-   Notifications des activités importantes
-   Gestion des notifications par rôle
-   Interface de consultation des notifications

### Système d'emploi du temps

-   Création et modification des emplois du temps
-   Visualisation par classe ou par enseignant
-   Affichage adapté selon le rôle de l'utilisateur

### Gestion des notes et évaluations

-   Saisie des notes par les enseignants
-   Consultation des résultats par les étudiants
-   Génération de bulletins

### Gestion des présences

-   Marquage des présences/absences
-   Suivi des absences par étudiant
-   Justification des absences

## 5. Tableau de bord personnalisé

Chaque type d'utilisateur dispose d'un tableau de bord adapté à son rôle :

### Étudiant

-   Profil personnel
-   Emploi du temps
-   Notes et résultats
-   Absences
-   Messagerie et notifications

### Enseignant/Data Enter

-   Gestion des données
-   Saisie des notes et présences
-   Création d'emplois du temps
-   Envoi de messages et notifications

### Administrateur

-   Gestion complète du système
-   Création des structures (filières, niveaux, classes)
-   Gestion des utilisateurs et des rôles
-   Suivi global de l'établissement
