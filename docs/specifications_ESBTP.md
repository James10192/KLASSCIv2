# Spécifications fonctionnelles - Application ESBTP

Ce document détaille toutes les fonctionnalités attendues de l'application de gestion ESBTP.

## 1. Gestion des classes

-   **Contenu**: Filière, formation et niveau d'étude
-   **Processus de création**:
    1. Choix de la filière
    2. Choix du type de formation
    3. Choix du niveau d'étude (1ère année ou 2ème année)
    4. Création de la classe
-   **Associations**:
    -   Étudiants rattachés à la classe
    -   Emploi du temps associé à la classe

## 2. Gestion des étudiants

-   **Informations personnelles**:
    -   Nom, prénom(s)
    -   Date de naissance (format JJ/MM/AAAA)
    -   E-mail
    -   Téléphone (format: +225 XX XX XXX XXX)
    -   Genre (homme/femme)
    -   Adresse (Ville, Commune)
    -   Photo de profil
    -   Date d'admission
    -   Matricule
    -   Mot de passe
    -   Numéro d'inscription (ID de l'étudiant)
-   **Gestion des parents**:
    -   Possibilité d'ajouter jusqu'à deux parents
    -   Option de choisir des parents existants
    -   Option d'ajouter de nouveaux parents
-   **Automatisation**:
    -   Assignation automatique à une classe

## 3. Gestion des matières

-   **Structure**:
    -   Rattachement à un niveau d'étude (BTS 1ère année, BTS 2ème année)
    -   Rattachement à une formation (générale ou technologique et professionnelle)
    -   Rattachement à une filière
-   **Groupes de matières**:
    -   Format: [Niveau d'étude]_[Filière]_[Formation]\_[Année]
    -   Exemple: BTS_mine-géologie-pétrole_formationgeneral_1annee
-   **Gestion des coefficients**:
    -   Interface pour ajouter des coefficients aux matières
    -   Utilisation pour le calcul des moyennes dans les bulletins

## 4. Gestion des emplois du temps (timetable)

-   Interface CRUD complète
-   Création d'un emploi du temps lié à une classe spécifique

## 5. Gestion des évaluations

-   **Types d'évaluation**:
    -   Types prédéfinis: examen, quiz, devoir
    -   Possibilité d'ajouter de nouveaux types
-   Interface CRUD complète

## 6. Gestion des notes

-   Rattachement à une évaluation
-   Rattachement à une classe (filière, formation, niveau d'étude)
-   Rattachement à un étudiant
-   Interface CRUD complète

## 7. Gestion des bulletins

-   **Composants**:
    -   Classe (filière, formation, niveau d'étude)
    -   Notes
    -   Évaluations
    -   Étudiant
-   Calcul automatique des moyennes
-   Interface CRUD complète

## 8. Gestion des annonces

-   **Contenu**:
    -   Objet
    -   Corps du message
    -   Destinataires
-   **Gestion des destinataires**:
    -   Tous les étudiants
    -   Une classe entière (tous les étudiants de cette classe)
    -   Des étudiants spécifiques
-   **Interface de sélection**:
    -   Modal multichoix pour sélectionner les destinataires
    -   Filtres (par classe, filière, formation, niveau d'étude)
    -   Bouton "select all"
-   Interface CRUD complète

## 9. Gestion des présences (attendance)

-   Interface pour marquer les présences des étudiants aux cours
-   Filtrage par classe et par période
-   Génération de rapports
-   Interface CRUD complète

Format brut original: classes: Cet élément contient, la filière, la formation et le niveau d'étude. quand on veut créer une classe on choisit la filière, le type de formation ensuite le niveau d'étude (1ere année ou 2e année) et ensuite on crée la classe.

Students: Cet élément contient nom, prénom(s), date de naissance, E-mail, Téléphone(Format: +225 XX XX XXX XXX), Date de naissance (JJ/MM/AAAA), Genre (homme et femme), Adresse (Ville, Commmune), Photo de profil, date d'admission, le ou les parents, matricule de l'étudiant)
Mot de passe, numéro d'inscription(donc l'ID de l'étudiant), l'assignation à une classe doit être géré automatiquement par le système
On a possibilité d'ajouter un ou deux nouveaux parents ou de choisir un ou deux parents des parents existants
On a une fiche d'un parent déjà là on doit pouvoir ajouter encore un autre parents (de nature tout le monde a deux parents)

Matières: juste des matières mais sont rattachés, à un niveau d'étude ( BTS 1ere année, BTS 2année) à une formation( soit générale ou technologie et professionnelle) et ensuite à une filière.
Donc ils feront plusieurs groupes de matières, par exemple matière (BTS_mine-géologie-pétrole_formationgeneral_1annee) quand une classe est créé regroupant (BTS_mine-géologie-pétrole_formationgeneral_1annee) alors le groupe de matière en question est directement rattachée à cette classe
Dans l'interface CRUD des matières quand on veut créer un groupe de matière on choisit la filière, le type de formation ensuite le niveau d'étude (1ere année ou 2e année) et ensuite on ajoute les matières.
Les matières peuvent avoir des coefficients donc une interface pour ajouter des coefficients aux matières pour les bulletins(le calcul des moyennes)

Interface CRUD pour timetable, pour create un timetable on doit choisir une classe

Classes: On rajoute à cet élément des étudiants et un emploi du temps

Les étudiants passent des évaluations
Une interface CRUD pour les évaluations
Une évaluation comprend le type d'évaluation(examen, quiz et devoir, l'utilisateur peut ajouter un niveau d'étude donc possibilité d'ajouter)
Une interface CRUD pour les notes, les notes sont rattaché à une évaluation, une classe(filière, formation, niveau d'étude) et un étudiant
Une interface CRUD pour générer des bulletins
Un bulletin comprend, une classe(filière, formation, niveau d'étude), des notes, des évaluations et un étudiant, on doit avoir une moyenne à la fin

Une interface CRUD pour les annonces,
Les annonces comprennent destinataire(tous les étudiants, une classe(tous les étudiants de cette classe), des étudiants en particulier) l'objet, le corps du message
Pour le choix du destinataire une interface, un modal multichoix pour choisir le ou les destinataires, un filtre(par classe, par filière, par formation, par niveau d'étude) un bouton select all.
Une interface de presence(attendance) pour marquer les présences des étudiants aux cours

Reprends à zero les différents éléments enoncés dans ce prompt et refais les en satisfaisant les attentes que je demande dans ce prompt
assure toi que tout ce que tu as fait remplis mes points
Fais une vérification que tout ce qui déjà implémenté respecte mon prompt et après continue sur les éléments manquants
Pour la vérification des vues tu dois chercher le nom du dossier(la fonctionnalité) et ensuite dedans les fichiers create.blade.php, edit.blade.php, show.blade.php et index.blade.php

Le format brut original prevaut sur tout si les instructions en haut sont contradictoires avec le format brut original alors le format brut original est prioritaire
