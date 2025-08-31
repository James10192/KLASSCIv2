# Check-list de vérification pour ESBTP-yAKRO

Utilisez cette check-list pour vérifier que toutes les fonctionnalités de l'application sont correctement implémentées.

## 1. Installation et configuration

-   [ ] Le script `reset_app.sh` fonctionne correctement
-   [ ] L'installation via l'interface web crée un compte superAdmin
-   [ ] SuperAdminSeeder est bien désactivé
-   [ ] La base de données est bien initialisée avec les données de référence

## 2. Gestion des utilisateurs et des rôles

-   [ ] Le superAdmin peut créer des comptes secrétaires
-   [ ] Les secrétaires créés ont toutes les permissions requises
-   [ ] Les étudiants sont créés automatiquement lors de l'inscription
-   [ ] Les matricules des étudiants sont générés au format spécifié
-   [ ] Les mots de passe sont générés aléatoirement et affichés

## 3. Tableaux de bord adaptés aux rôles

-   [ ] Le tableau de bord superAdmin affiche toutes les options de gestion
-   [ ] Le tableau de bord secrétaire n'affiche que les options autorisées
-   [ ] Le tableau de bord étudiant n'affiche que les informations personnelles

## 4. Fonctionnalités principales

### Gestion des classes

-   [ ] Création de classes avec filière, formation et niveau d'étude
-   [ ] Association d'étudiants aux classes
-   [ ] Association d'emploi du temps aux classes

### Gestion des étudiants

-   [ ] Enregistrement de toutes les informations personnelles
-   [ ] Association des parents/tuteurs
-   [ ] Génération automatique de matricule
-   [ ] Création automatique de compte utilisateur

### Gestion des matières

-   [ ] Organisation en groupes selon filière, formation et niveau
-   [ ] Gestion des coefficients pour les moyennes

### Gestion des emplois du temps

-   [ ] Création d'emplois du temps par classe

### Gestion des évaluations

-   [ ] Création d'évaluations (examens, quiz, devoirs)
-   [ ] Saisie des notes par évaluation

### Gestion des bulletins

-   [ ] Génération de bulletins avec calcul des moyennes
-   [ ] Affichage des bulletins pour les étudiants

### Communication

-   [ ] Envoi d'annonces ciblées (tous, classe, étudiants spécifiques)
-   [ ] Interface pour sélectionner les destinataires

### Gestion des présences

-   [ ] Enregistrement des présences par classe et par cours
-   [ ] Consultation des présences par les étudiants

## 5. Permissions et sécurité

-   [ ] Les permissions sont correctement appliquées pour chaque rôle
-   [ ] Redirection appropriée selon le rôle après connexion
-   [ ] Protection des routes selon les permissions
