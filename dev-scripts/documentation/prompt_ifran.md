# Prompt de Migration et Adaptation - Klassci

## Contexte du projet

Klassci est une application SaaS de gestion d'établissement scolaire qui a déjà été développée partiellement. Il existe donc un code base existant qui doit être adapté, complété ou modifié selon les nouvelles spécifications détaillées.

## Référence complète du projet

**FICHIER DE RÉFÉRENCE ABSOLUE** : @mise_a_jour_de_klassci_pour_ifran.md

Toutes les décisions de développement, modifications, suppressions ou ajouts doivent être basées sur ce document de référence qui contient la vision complète et détaillée du projet.

## Instructions de migration

### 1. Analyse de l'existant

Avant toute modification, vous devez :

1. **Analyser le code existant** pour comprendre :
   - L'architecture actuelle
   - Les fonctionnalités déjà implémentées
   - Les modèles de données en place
   - Les interfaces utilisateur existantes
   - Les rôles et permissions actuels

2. **Comparer avec la référence** (@mise_a_jour_de_klassci_pour_ifran.md) pour identifier :
   - Les fonctionnalités manquantes à ajouter
   - Les fonctionnalités existantes à modifier
   - Les éléments à supprimer car non conformes
   - Les optimisations à apporter

### 2. Stratégie de migration

#### A. Fonctionnalités à SUPPRIMER
- Toute fonctionnalité qui n'est pas mentionnée dans @mise_a_jour_de_klassci_pour_ifran.md
- Les éléments de comptabilité complexes (remplacer par le système simplifié)
- Les interfaces non conformes aux rôles définis
- Les processus qui ne respectent pas les workflows décrits

#### B. Fonctionnalités à ADAPTER
- Les rôles utilisateurs selon la hiérarchie définie
- Les processus d'inscription selon le workflow prospect→étudiant
- Les interfaces selon les responsabilités de chaque rôle
- Les systèmes d'émargement et de présence selon les nouveaux processus

#### C. Fonctionnalités à AJOUTER
- Les fonctionnalités manquantes identifiées lors de la comparaison
- Les nouvelles interfaces spécifiques à chaque rôle
- Les systèmes de notification et communication
- Les outils de reporting et synthèse

### 3. Plan de migration par module

#### Module 1 : Système d'authentification et rôles
- [ ] Vérifier la conformité des rôles existants
- [ ] Implémenter la hiérarchie : Directeur général, Coordinateur, Secrétaire, Comptable, Enseignant, Étudiant
- [ ] Ajouter l'obligation de changement de mot de passe première connexion
- [ ] Adapter les permissions selon @mise_a_jour_de_klassci_pour_ifran.md

#### Module 2 : Gestion des inscriptions
- [ ] Implémenter le workflow prospect→étudiant
- [ ] Supprimer les formulaires de comptabilité complexes de l'inscription
- [ ] Adapter le processus de validation par liaison paiement
- [ ] Implémenter l'attribution automatique de classe

#### Module 3 : Interfaces par rôle

##### Directeur général
- [ ] Créer le dashboard de synthèse uniquement
- [ ] Supprimer tout accès aux détails opérationnels
- [ ] Implémenter les KPI consolidés

##### Coordinateur
- [ ] Implémenter la gestion du calendrier semestriel/annuel
- [ ] Créer l'interface de réception des disponibilités enseignants
- [ ] Développer la programmation hebdomadaire des séances
- [ ] Implémenter la gestion des codes d'émargement
- [ ] Créer le monitoring des taux de présence

##### Comptable
- [ ] Simplifier l'interface comptable
- [ ] Implémenter le paramétrage des catégories de frais
- [ ] Créer le suivi simplifié des paiements
- [ ] Supprimer les fonctionnalités comptables complexes

##### Enseignant
- [ ] Implémenter l'émargement par codes
- [ ] Créer l'interface de suivi de présence personnelle
- [ ] Développer l'historique des émargements
- [ ] Adapter la gestion des listes de présence (appel obligatoire)

##### Étudiant
- [ ] Adapter l'interface selon les spécifications
- [ ] Implémenter les demandes de certificats
- [ ] Créer l'accès aux informations académiques

#### Module 4 : Gestion des cours et emplois du temps
- [ ] Implémenter le processus : démarrage → appel → émargement → clôture
- [ ] Créer le système de codes d'émargement
- [ ] Développer le suivi de progression des séances
- [ ] Implémenter les codes couleurs et icônes

#### Module 5 : Système d'évaluations
- [ ] Vérifier la conformité des types d'évaluations
- [ ] Implémenter le système de navigation par slider
- [ ] Adapter les interfaces de saisie des notes
- [ ] Créer le système de publication contrôlée

#### Module 6 : Comptabilité simplifiée
- [ ] Supprimer les fonctionnalités comptables complexes
- [ ] Implémenter le paramétrage simple des frais
- [ ] Créer le suivi des paiements par étudiant
- [ ] Développer la saisie rapide par modal

#### Module 7 : Communication et notifications
- [ ] Implémenter les alertes automatiques
- [ ] Créer le système de rappels
- [ ] Développer les notifications aux parents
- [ ] Implémenter la gestion des certificats

### 4. Priorités de migration

#### Phase 1 (Urgent) :
1. Adaptation des rôles et permissions
2. Suppression des éléments non conformes
3. Correction des workflows d'inscription

#### Phase 2 (Important) :
1. Interfaces spécifiques par rôle
2. Système d'émargement et présence
3. Comptabilité simplifiée

#### Phase 3 (Optimisation) :
1. Fonctionnalités de communication
2. Reporting et analyses
3. Optimisations UX/UI

### 5. Checklist de validation

Pour chaque fonctionnalité modifiée/ajoutée, vérifier :
- [ ] Conformité avec @mise_a_jour_de_klassci_pour_ifran.md
- [ ] Respect des rôles et permissions
- [ ] Cohérence avec l'architecture existante
- [ ] Tests fonctionnels réalisés
- [ ] Documentation mise à jour

### 6. Règles de développement

1. **Référence absolue** : @mise_a_jour_de_klassci_pour_ifran.md est la source de vérité
2. **Approche progressive** : Migrer module par module
3. **Tests systématiques** : Tester chaque modification
4. **Documentation** : Documenter chaque changement
5. **Validation** : Valider chaque étape avant de passer à la suivante

### 7. Commandes de gestion des tâches

Utiliser les commandes du task-master pour gérer les tâches :
- Créer des tâches pour chaque module
- Suivre l'avancement des modifications
- Valider les étapes de migration

### 8. Points d'attention spécifiques

#### Suppressions importantes :
- Schéma évolution des inscriptions
- Fonctionnalités comptables complexes de l'inscription
- Accès détaillés pour le directeur général

#### Ajouts critiques :
- Dashboard de synthèse pour directeur général
- Gestion des codes d'émargement
- Processus obligatoire appel avant émargement
- Paramétrage simple des frais

#### Modifications majeures :
- Workflow d'inscription (prospect→étudiant)
- Interfaces par rôle
- Système de présence et émargement
- Comptabilité simplifiée

---

**RAPPEL IMPORTANT** : Chaque décision de développement doit être validée par rapport à @mise_a_jour_de_klassci_pour_ifran.md qui contient la vision complète et détaillée du projet Klassci.