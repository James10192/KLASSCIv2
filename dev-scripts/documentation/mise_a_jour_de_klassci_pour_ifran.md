# Synthèse complète du projet Klassci - Application SaaS de gestion d'établissement scolaire

## Vue d'ensemble du projet

Klassci est une application SaaS complète de gestion d'établissement scolaire physique, conçue pour centraliser et optimiser tous les aspects administratifs, pédagogiques et comptables d'une institution éducative. L'application vise à offrir une solution intégrée permettant un suivi complet des étudiants, des enseignants et des activités pédagogiques. Le système s'articule autour d'une architecture multi-utilisateurs avec des rôles spécifiques, chacun ayant des responsabilités précises dans la gestion quotidienne de l'établissement.

## Architecture des comptes utilisateurs

### Hiérarchie des rôles et responsabilités détaillées

#### Directeur général - Dashboard de synthèse stratégique

Le directeur général dispose d'un accès limité mais stratégique à l'information. Son interface est conçue comme un tableau de bord exécutif qui agrège les données les plus pertinentes pour la prise de décision au niveau de la direction. Il n'a pas accès aux détails opérationnels mais bénéficie d'une vue d'ensemble consolidée.

**Fonctionnalités du dashboard :**
- **Indicateurs clés de performance** : Taux de présence global des étudiants et enseignants, évolution des inscriptions, statistiques académiques
- **Synthèses financières** : Vue d'ensemble des recettes, des impayés, des taux de recouvrement
- **Rapports automatisés** : Génération de rapports périodiques sur l'activité de l'établissement
- **Alertes stratégiques** : Notifications sur les événements importants nécessitant une attention de la direction

**Principe de dépendance fonctionnelle :**
Toutes les informations du directeur général proviennent des saisies et actions des autres comptes utilisateurs. Cette approche garantit une cohérence des données et évite la duplication des saisies.

#### Coordinateur - Maître d'œuvre de la planification pédagogique

Le coordinateur est le pilote central de l'organisation pédagogique. Il orchestre l'ensemble des activités liées à la planification des cours, la gestion des ressources humaines enseignantes et le suivi des activités académiques.

**Gestion du calendrier académique :**
Le coordinateur établit le calendrier semestriel ou annuel qui sert de référence pour toutes les activités pédagogiques. Cette planification inclut les périodes d'enseignement, les examens, les congés et les événements académiques importants. Ce calendrier maître conditionne l'ensemble des autres planifications de l'établissement.

**Gestion des disponibilités enseignants :**
Les enseignants remplissent des formulaires de disponibilité que le coordinateur réceptionne et traite. Cette gestion centralisée permet d'optimiser l'allocation des ressources humaines et d'éviter les conflits d'horaires. Le coordinateur analyse ces disponibilités en fonction des besoins pédagogiques et des contraintes organisationnelles.

**Allocation horaire par module :**
Le coordinateur définit le nombre d'heures que chaque module/matière doit représenter selon le programme pédagogique. Cette répartition horaire est ensuite utilisée pour programmer les séances de cours de manière équilibrée sur l'année académique.

**Programmation hebdomadaire des séances :**
Sur la base des heures allouées par module, des disponibilités enseignants et des contraintes de classes, le coordinateur place les séances de cours de manière hebdomadaire. Cette programmation prend en compte les spécificités de chaque classe, filière et année universitaire.

**Gestion des codes d'émargement :**
Le coordinateur crée et gère les codes que les enseignants utilisent pour émarger leurs séances. Ces codes permettent de valider la tenue effective des cours et constituent un outil de contrôle de la présence enseignante.

**Suivi des présences étudiantes :**
Le coordinateur dispose d'une vue d'ensemble sur les taux de présence des étudiants. En cas d'impossibilité pour les étudiants de gérer leur propre présence, le coordinateur peut créer les listes de présence et les gérer centralement.

#### Secrétaire - Support administratif transversal

Le secrétaire assure le lien entre les aspects comptables et pédagogiques. Ce rôle polyvalent nécessite une bonne compréhension des deux domaines pour assurer un support efficace aux différents processus administratifs.

**Responsabilités comptables :**
- Assistance dans la gestion des paiements et des relances
- Suivi des dossiers étudiants du point de vue financier
- Interface avec le comptable pour les questions spécialisées

**Responsabilités pédagogiques :**
- Support dans la gestion des inscriptions
- Assistance dans l'organisation des examens et évaluations
- Gestion des documents administratifs étudiants

#### Comptable - Spécialiste de la gestion financière

Le comptable se concentre exclusivement sur les aspects financiers de l'établissement avec un système volontairement simplifié pour maintenir l'efficacité opérationnelle.

**Paramétrage des frais :**
Le comptable configure les différentes catégories de frais que l'établissement peut facturer. Cette configuration inclut les frais obligatoires (inscription, scolarité) et les frais optionnels (cantine, transport, services additionnels). La flexibilité du système permet d'adapter la tarification aux besoins spécifiques de l'établissement.

**Suivi des paiements :**
Le comptable assure le suivi individualisé des paiements de chaque étudiant. Il peut vérifier le statut des soldes, identifier les impayés et gérer les relances nécessaires. Le système permet une vue claire de la situation financière de chaque étudiant.

#### Enseignant - Acteur pédagogique de terrain

L'enseignant dispose d'outils spécifiques pour gérer ses activités pédagogiques quotidiennes et assurer le suivi de ses obligations professionnelles.

**Émargement des séances :**
Les enseignants utilisent les codes créés par le coordinateur pour émarger leurs séances de cours. Cette fonctionnalité permet de valider leur présence et de confirmer la tenue effective des cours programmés.

**Suivi de la présence personnelle :**
Chaque enseignant peut consulter son taux de présence personnel, lui permettant de suivre son assiduité et de s'assurer du respect de ses obligations contractuelles.

**Historique des émargements :**
L'accès à l'historique complet des émargements permet aux enseignants de disposer d'une traçabilité de leur activité pédagogique, utile pour les évaluations professionnelles et les justificatifs administratifs.

**Gestion des listes de présence :**
Les enseignants sont responsables de la gestion des listes de présence de leurs étudiants. Cette fonctionnalité, communément appelée "faire l'appel", consiste à enregistrer la présence ou l'absence de chaque étudiant lors de chaque séance de cours. Cette saisie est obligatoire avant de pouvoir procéder à l'émargement de la séance.

**Saisie des notes et évaluations :**
Les enseignants saisissent les notes des différentes évaluations (évaluations en classe, compositions, examens) et gèrent le suivi pédagogique de leurs étudiants.

#### Étudiant - Bénéficiaire des services pédagogiques

L'étudiant accède aux informations qui le concernent directement dans son parcours académique.

**Consultation des informations académiques :**
L'étudiant peut consulter ses notes, ses bulletins publiés, ses absences et ses présences. Cette transparence favorise le suivi personnel de sa progression académique.

**Services administratifs :**
L'étudiant peut demander des certificats de scolarité et accéder aux informations relatives à sa situation administrative et financière.

### Sécurité et authentification

**Politique de sécurité :**
Tous les utilisateurs, à l'exception du superadmin, doivent obligatoirement changer leur mot de passe lors de leur première connexion. Cette mesure garantit que seul l'utilisateur légitime connaît ses identifiants d'accès.

**Gestion des permissions :**
Le système implémente un contrôle d'accès basé sur les rôles (RBAC) qui garantit que chaque utilisateur n'accède qu'aux fonctionnalités correspondant à ses responsabilités.

## Gestion des inscriptions et étudiants

### Processus d'inscription détaillé

**Phase de prospection :**
Lors de la création d'une inscription, l'utilisateur reste dans un statut de "prospect". À cette étape, il peut remplir les informations nécessaires et exprimer ses préférences de classe et de filière, mais il n'est pas encore considéré comme un étudiant actif de l'établissement.

**Processus de validation :**
La validation d'une inscription par l'administration déclenche automatiquement la transformation du prospect en étudiant actif. Cette validation est conditionnée par l'association d'un paiement (généralement les frais d'inscription) au dossier.

**Attribution automatique de classe :**
Le système vérifie automatiquement la disponibilité de places dans la classe présélectionnée par le prospect. Si des places sont disponibles, l'attribution se fait automatiquement. Dans le cas contraire, l'administrateur doit intervenir pour proposer une classe alternative.

**Gestion des exceptions :**
L'administrateur conserve la possibilité de modifier l'attribution de classe même après la validation, permettant une gestion flexible des cas particuliers.

### Profil étudiant complet

**Informations personnelles :**
Le profil étudiant centralise toutes les informations personnelles, académiques et administratives nécessaires au suivi de son parcours dans l'établissement.

**Historique académique :**
Le système conserve l'historique complet des inscriptions par année scolaire ou universitaire, permettant un suivi longitudinal du parcours étudiant.

**Suivi des présences :**
L'étudiant peut consulter ses présences et absences, lui permettant de suivre son assiduité et de prendre les mesures correctives nécessaires.

**Accès aux résultats :**
L'étudiant accède à ses notes, évaluations et bulletins une fois que l'administration a décidé de les publier.

**Services administratifs :**
L'étudiant peut demander des certificats de scolarité directement depuis son interface, ces demandes étant ensuite traitées par l'administration.

## Système pédagogique avancé

### Gestion des classes et filières

**Organisation structurelle :**
Le système organise les étudiants selon une hiérarchie classe/filière/session qui correspond aux réalités organisationnelles de l'établissement. Cette structure permet une gestion fine des groupes d'étudiants et de leurs spécificités pédagogiques.

**Affichage des matières :**
Pour chaque classe, le système affiche les matières enseignées avec une fonctionnalité "voir plus" qui permet d'accéder aux détails des programmes, volumes horaires et enseignants responsables.

**Gestion des capacités :**
Le système surveille les capacités d'accueil de chaque classe pour éviter les sureffectifs et optimiser l'utilisation des ressources pédagogiques.

### Évaluations et notations

**Types d'évaluations :**
Le système distingue trois types d'évaluations : les évaluations en classe (contrôles continus), les compositions (évaluations intermédiaires) et les examens (évaluations finales). Cette classification permet une gestion différenciée selon l'importance et la nature de l'évaluation.

**Interface enseignant pour les évaluations :**
Les enseignants disposent d'outils de saisie des notes adaptés à chaque type d'évaluation. L'interface permet la saisie individuelle ou collective des notes, avec des fonctionnalités de validation et de correction.

**Suivi étudiant des évaluations :**
Les étudiants peuvent consulter leurs notes et évaluations une fois que l'enseignant et l'administration ont validé leur publication. Cette transparence favorise le suivi personnel de la progression académique.

**Système de navigation temporelle :**
Une fonctionnalité de navigation par slider permet de basculer entre les évaluations déjà effectuées et celles à venir, offrant une vision chronologique claire du parcours d'évaluation.

### Génération de bulletins

**Création automatique :**
Le système génère automatiquement les bulletins sur la base des notes saisies par les enseignants. Cette génération respecte les périodes définies dans le calendrier académique.

**Contrôle de publication :**
L'administration conserve le contrôle sur la publication des bulletins, permettant de vérifier la cohérence des résultats avant leur diffusion aux étudiants.

**Export PDF :**
Les bulletins peuvent être exportés au format PDF pour impression ou envoi par email, facilitant la communication avec les étudiants et leurs familles.

## Gestion des emplois du temps et coordination

### Fonctionnalités de planification par le coordinateur

**Calendrier académique maître :**
Le coordinateur établit le calendrier semestriel ou annuel qui sert de référence pour toutes les activités de l'établissement. Cette planification globale inclut les périodes d'enseignement, les examens, les congés et les événements académiques spéciaux.

**Réception et traitement des disponibilités :**
Les enseignants soumettent leurs disponibilités via des formulaires dédiés. Le coordinateur analyse ces informations pour optimiser l'allocation des ressources humaines et éviter les conflits d'horaires.

**Définition des volumes horaires :**
Pour chaque module/matière, le coordinateur définit le nombre d'heures d'enseignement nécessaire selon le programme pédagogique et les objectifs d'apprentissage.

**Programmation intelligente :**
Le coordinateur utilise les contraintes de disponibilité des enseignants, les volumes horaires requis et les spécificités de chaque classe/filière/année pour programmer les séances de cours de manière optimale sur la semaine.

**Gestion centralisée des codes d'émargement :**
Le coordinateur génère et gère les codes que les enseignants utilisent pour valider leur présence aux cours. Cette centralisation permet un contrôle efficace de la tenue effective des séances programmées.

### Suivi des cours et présences

**Processus de démarrage de cours :**
Les enseignants utilisent des codes spécifiques pour signaler le début de leurs séances. Cette fonctionnalité permet un suivi en temps réel de la tenue des cours.

**Gestion obligatoire des listes de présence :**
Avant de pouvoir émarger leur propre présence, les enseignants doivent obligatoirement effectuer l'appel de leurs étudiants. Cette séquence garantit que la présence enseignante est liée à une activité pédagogique effective.

**Émargement enseignant :**
Une fois l'appel effectué, les enseignants peuvent émarger leur présence en utilisant les codes fournis par le coordinateur. Cette validation confirme la tenue effective du cours.

**Processus de clôture :**
À la fin de chaque séance, les enseignants procèdent à la clôture du cours, permettant de finaliser l'enregistrement de la séance et de ses participants.

**Suivi de progression :**
Le système compare en permanence les séances effectuées avec celles programmées, permettant d'identifier les retards ou les avances dans l'exécution du programme pédagogique.

### Monitoring des présences

**Taux de présence étudiants :**
Le coordinateur dispose d'une vue d'ensemble sur les taux de présence des étudiants par classe, matière et période. Cette information permet d'identifier les problèmes d'assiduité et de prendre les mesures correctives appropriées.

**Taux de présence enseignants :**
Le suivi des émargements enseignants permet de calculer les taux de présence individuels et collectifs, information cruciale pour l'évaluation des performances et le respect des obligations contractuelles.

**Historique complet :**
Tous les émargements sont archivés, créant un historique complet consultable pour les évaluations, les justificatifs administratifs et les analyses de performance.

**Génération de rapports :**
Le système génère automatiquement des rapports de présence selon différents critères (période, classe, enseignant, matière), facilitant le suivi administratif et pédagogique.

## Système comptable simplifié

### Paramétrage des frais par le comptable

**Configuration des catégories :**
Le comptable configure les différentes catégories de frais que l'établissement peut facturer. Cette configuration flexible permet d'adapter le système aux spécificités tarifaires de chaque établissement.

**Frais obligatoires :**
Les frais d'inscription et de scolarité constituent les frais de base que tout étudiant doit acquitter. Ces frais sont paramétrés comme obligatoires dans le système.

**Frais optionnels :**
Les services additionnels comme la cantine, le transport ou d'autres prestations sont paramétrés comme frais optionnels que les étudiants peuvent souscrire selon leurs besoins.

**Tarification flexible :**
Le système permet de définir des tarifs différenciés selon les catégories d'étudiants, les niveaux d'études ou d'autres critères spécifiques à l'établissement.

### Suivi des paiements

**Liaison inscription-paiement :**
La validation d'une inscription est conditionnée par l'association d'un paiement correspondant aux frais d'inscription minimum. Cette liaison garantit que seuls les étudiants ayant acquitté leurs frais peuvent être activés dans le système.

**Suivi individualisé :**
Chaque étudiant dispose d'un compte individuel où sont enregistrés tous ses paiements, permettant un suivi précis de sa situation financière vis-à-vis de l'établissement.

**Gestion des soldes :**
Le système calcule automatiquement les soldes dus par chaque étudiant en fonction des frais paramétrés et des paiements effectués. Cette fonctionnalité permet d'identifier rapidement les situations d'impayés.

**Saisie rapide des paiements :**
Une interface modale permet la saisie rapide des paiements sans changer de page, améliorant l'efficacité du processus d'enregistrement des règlements.

### Fonctionnalités comptables

**Certificats de solde :**
Le système génère automatiquement des certificats attestant que l'étudiant a soldé l'ensemble de ses obligations financières, document souvent requis pour les procédures administratives.

**Suivi des échéances :**
Le système permet de définir et de suivre les échéances de paiement, facilitant la gestion des relances et des recouvrements.

**Vue d'ensemble des paiements :**
Une interface dédiée offre une vue consolidée des paiements de chaque étudiant, permettant une analyse rapide de sa situation financière.

**Notifications automatiques :**
Le système peut générer automatiquement des notifications aux parents concernant les mouvements comptables de leurs enfants, améliorant la communication et la transparence financière.

## Fonctionnalités de communication

### Système de notifications

**Alertes de paiement :**
Le système génère automatiquement des alertes pour les échéances de paiement approchant ou dépassées, permettant une gestion proactive des recouvrements.

**Rappels aux enseignants :**
Les coordinateurs peuvent envoyer des rappels automatiques aux enseignants concernant leurs obligations pédagogiques, leurs émargements manquants ou leurs évaluations en retard.

**Communication avec les parents :**
Le système facilite la communication avec les parents concernant la scolarité de leurs enfants, notamment les aspects financiers et pédagogiques.

**Suivi des mouvements de scolarité :**
Les parents sont automatiquement informés des événements importants concernant la scolarité de leurs enfants (notes, absences, paiements).

### Gestion des certificats

**Demandes étudiantes :**
Les étudiants peuvent demander des certificats de scolarité directement depuis leur interface, ces demandes étant automatiquement transmises au service administratif compétent.

**Génération automatique :**
Le système génère automatiquement les certificats sur la base des informations présentes dans la base de données, garantissant la cohérence et la rapidité de délivrance.

**Suivi des demandes :**
Un système de suivi permet de connaître l'état d'avancement de chaque demande de certificat, améliorant la transparence du processus administratif.

## Optimisations techniques et expérience utilisateur

### Performance et navigation

**Optimisation de l'année universitaire :**
Le système d'année universitaire est optimisé pour gérer efficacement les transitions entre années académiques et maintenir la cohérence des données historiques.

**Amélioration continue du design :**
L'interface utilisateur fait l'objet d'améliorations continues pour optimiser l'expérience utilisateur et l'efficacité opérationnelle.

**Réorganisation de la navigation :**
La sidebar est réorganisée pour placer l'espace étudiant avant les fonctionnalités de communication, reflétant mieux les priorités d'utilisation.

**Corrections d'affichage :**
Les bugs d'affichage, notamment la confusion entre nom d'utilisateur et nom d'enseignant, sont corrigés pour améliorer la clarté des informations.

### Calendrier et planification

**Calendrier général :**
Un calendrier général du semestre permet à tous les utilisateurs d'avoir une vue d'ensemble des événements académiques importants.

**Planification horaire par module :**
Chaque module dispose d'une planification horaire détaillée permettant de suivre la progression pédagogique et de détecter les éventuels retards.

**Programmation des évaluations :**
Les évaluations sont programmées avec leurs dates précises, permettant aux étudiants et enseignants de s'organiser efficacement.

**Suivi des contrats enseignants :**
Le système suit les contrats et disponibilités des enseignants pour optimiser la planification pédagogique et anticiper les besoins en ressources humaines.

## Gestion budgétaire et prévisionnelle

### Anticipation des coûts

**Coûts enseignants :**
Le système identifie les coûts liés aux enseignants comme poste principal de dépenses, permettant une gestion prévisionnelle efficace des ressources humaines.

**Budgets annuels :**
L'anticipation des budgets au début de chaque année académique permet une gestion financière proactive et une meilleure planification des investissements.

**Contrats prévisionnels :**
La gestion prévisionnelle des contrats enseignants permet d'anticiper les besoins en ressources humaines et d'optimiser les coûts de personnel.

**Monitoring des disponibilités :**
Le suivi des disponibilités enseignantes permet d'optimiser l'utilisation des ressources humaines et d'identifier les besoins en recrutement.

### Reporting et analyses

**Récapitulatifs enseignants :**
Le système génère des récapitulatifs détaillés des heures de cours effectuées par chaque enseignant, permettant le suivi des obligations contractuelles.

**Suivi de progression étudiante :**
Le suivi de la progression des étudiants permet d'identifier les difficultés pédagogiques et d'adapter les méthodes d'enseignement.

**Détection des décalages :**
La comparaison entre planification et réalisation permet de détecter les décalages dans l'exécution du programme pédagogique.

**Analyses comparatives :**
Le système permet des analyses comparatives entre les objectifs fixés et les performances réalisées, facilitant l'amélioration continue des processus.

Cette synthèse représente un système complet et intégré qui répond aux besoins spécifiques de gestion d'un établissement scolaire, avec une attention particulière portée à l'expérience utilisateur, à l'efficacité opérationnelle et à la répartition claire des responsabilités entre les différents acteurs du système éducatif.