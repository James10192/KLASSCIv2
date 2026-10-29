# Task #20 - Workflow Validation Inscription-Comptabilité

## Statut : ⏳ PENDING

**Date de création :** 2025-01-11
**Priorité :** High

## Description

Implémentation complète du workflow de validation des inscriptions par l'administration avec liaison obligatoire à la comptabilité. Cette tâche transforme le processus d'inscription pour séparer la saisie des informations de la validation comptable.

## 🎯 Objectifs

### Workflow simplifié

1. **Inscription = Prospect** : Le formulaire d'inscription ne contient plus de partie comptabilité, l'étudiant reste un "prospect"
2. **Validation Administration** : L'administration valide l'inscription en la liant obligatoirement à un paiement
3. **Conversion Automatique** : Après validation → création automatique compte étudiant dans la classe présélectionnée
4. **Gestion Places** : Si plus de place dans la classe → administrateur doit changer de classe
5. **Activation Comptabilité** : Les relances et fonctionnalités comptabilité s'activent après validation

## 🔧 Modifications Apportées

### Nettoyage Formulaire Inscription

**Fichier :** `resources/views/esbtp/inscriptions/create.blade.php`

**Sections supprimées :**

-   Section "Frais obligatoires" (tableau des montants obligatoires)
-   Section "Champ de paiement initial obligatoire" (input montant paiement)
-   Section "Services optionnels (frais)" (checkboxes cantine, transport, etc.)
-   Modal de paiement : `@include('components.modals.paiement')`
-   JavaScript du modal paiement : gestion validation AJAX et soumission paiement

**Résultat :** Le formulaire d'inscription se concentre uniquement sur :

-   Informations de classe
-   Informations de l'étudiant
-   Informations des parents/tuteurs

## 📋 Fonctionnalités à Implémenter (Tâche #20)

### 1. Interface d'Administration

-   Tableau de bord des inscriptions en attente de validation
-   Filtres par statut, date, formation, méthode de paiement
-   Indicateurs visuels pour dossiers complets/incomplets

### 2. Système de Liaison Comptabilité-Inscription

-   Modèle de relation entre Inscription et Paiement
-   Contraintes de validation empêchant validation sans paiement associé
-   Règles métier pour vérification montants minimums requis

### 3. Modal de Saisie Rapide des Paiements

-   Formulaire modal accessible depuis liste des inscriptions
-   Validation en temps réel des données de paiement
-   Upload de justificatifs de paiement
-   Intégration avec système de paiement existant

### 4. Workflow de Conversion Prospect → Étudiant

-   Service de vérification de disponibilité des places
-   Algorithme de changement de classe si nécessaire
-   Mise à jour automatique du statut après validation
-   Notifications aux utilisateurs concernés

### 5. Intégration Système de Relances

-   Déclencheur automatique de relances après validation
-   Configuration des modèles de messages pour différents scénarios
-   Planification des relances selon les règles définies

### 6. Nettoyage et Optimisation

-   Refactorisation du code existant pour séparer la logique métier
-   Simplification de l'interface utilisateur
-   Ajout de validations côté client et serveur

## 🏗️ Infrastructure Existante

### Migrations Task #14 (Infrastructure Liaison)

-   `esbtp_inscriptions` : ajout de `workflow_step`, `paiement_validation_id`, `classe_alternative_id`, `comptabilite_activee`
-   `esbtp_inscription_workflow_history` : audit trail complet
-   `esbtp_classes` : gestion `places_totales` et `places_occupees`

### Workflow Sécurisé (5 étapes)

1. **prospect** → Inscription initiale
2. **documents_complets** → Documents soumis
3. **en_validation** → Validation en cours par administration
4. **valide** → Inscription approuvée, peut créer compte étudiant
5. **etudiant_cree** → Compte étudiant créé, processus terminé

## 🧪 Stratégie de Tests

### Tests Unitaires

-   Modèles de relation Inscription-Paiement
-   Règles de conversion de statut
-   Contraintes de validation des paiements

### Tests d'Intégration

-   Workflow complet de validation d'une inscription
-   Intégration avec le système de paiement
-   Déclenchement automatique des relances

### Tests Fonctionnels

-   Scénarios de validation avec différents types de paiements
-   Tests de conversion de statut avec/sans places disponibles
-   Validation du changement automatique de classe

### Tests UI

-   Modal de saisie rapide sur différents appareils
-   Accessibilité et ergonomie de l'interface admin
-   Indicateurs visuels et filtres

## 🔗 Dépendances

-   Task #2 : Développement des services de base
-   Task #7 : Implémentation des jobs et queues
-   Task #9 : Optimisation des performances et cache
-   Task #11 : Intégration des analytics prédictifs

## 📊 Impact

-   **Séparation claire** entre saisie inscription et validation comptable
-   **Workflow sécurisé** avec audit trail complet
-   **Expérience utilisateur** simplifiée pour les candidats
-   **Contrôle administratif** renforcé sur la validation
-   **Gestion automatisée** des places et changements de classe

## 🚀 Prochaines Étapes

1. Analyser l'impact sur les contrôleurs existants
2. Développer l'interface d'administration
3. Implémenter la modal de saisie rapide
4. Tester le workflow complet
5. Déployer et former les utilisateurs
