# Guide Utilisateur - Gestion des Frais par Statut d'Affectation

## Introduction

Ce guide explique comment utiliser le nouveau système de frais différenciés selon le statut d'affectation des étudiants dans KLASSCI.

## Comprendre les Statuts d'Affectation

### 🟢 Affecté
- **Définition** : Étudiant placé directement par l'État ivoirien via le portail bac.mesrs-ci.net
- **Avantage** : Bénéficie de subventions étatiques (frais réduits)
- **Couleur** : Vert avec icône de validation

### 🟡 Réaffecté  
- **Définition** : Étudiant réassigné par la DOB suite à une demande de changement
- **Avantage** : Conserve les subventions (frais intermédiaires)
- **Couleur** : Orange avec icône d'échange

### 🔴 Non affecté
- **Définition** : Étudiant inscrit directement sans passer par l'affectation étatique
- **Frais** : Montant complet sans subvention
- **Couleur** : Rouge avec icône de croix

## Configuration des Frais

### Accès à la Configuration

1. Connectez-vous à KLASSCI avec un compte administrateur
2. Accédez au menu **Frais > Configuration des frais**
3. La page affiche toutes les combinaisons Filière + Niveau

### Interface de Configuration

Pour chaque catégorie de frais (Inscription, Scolarité, etc.), vous verrez 3 colonnes :

| Affecté (État) | Réaffecté (DOB) | Non affecté |
|----------------|-----------------|-------------|
| Montant réduit | Montant intermédiaire | Montant complet |
| ex: 30 000 FCFA | ex: 35 000 FCFA | ex: 50 000 FCFA |

### Fonctionnalités Pratiques

#### 📋 Copier les Montants
- Cliquez sur le bouton "📋" pour copier un montant vers les autres colonnes
- Utile si le montant est identique pour tous les statuts

#### 🔄 Copier vers Toutes les Catégories
- Bouton pour appliquer les montants d'une catégorie à toutes les autres
- Gain de temps pour la configuration initiale

#### ✅ Validation Automatique
- Vérification que les montants sont cohérents
- Au moins un montant doit être défini par catégorie

## Inscription des Étudiants

### Processus d'Inscription

1. **Page d'inscription** : `/esbtp/inscriptions/create`
2. **Informations académiques** : Sélectionnez Filière, Niveau, Classe
3. **Statut d'affectation** : 
   - Sélectionnez le statut approprié dans la liste déroulante
   - Les frais se mettent à jour automatiquement

### Mise à Jour Dynamique des Frais

Quand vous changez le statut d'affectation :
- Les montants s'actualisent instantanément
- Le total des frais se recalcule
- Les frais obligatoires et optionnels sont ajustés

## Visualisation des Informations

### Page Détails Inscription

Dans la section "Informations académiques" :
- Le statut d'affectation est affiché avec un badge coloré
- Les frais sont calculés selon le statut sélectionné

### Page Profil Étudiant

Chaque inscription affiche :
- Le statut d'affectation avec l'icône appropriée
- L'historique des changements de statut si applicable

## Exemples Concrets

### Exemple 1 : Configuration Frais d'Inscription

| Catégorie | Affecté | Réaffecté | Non affecté |
|-----------|---------|-----------|-------------|
| Frais d'inscription | 30 000 F | 35 000 F | 50 000 F |
| Frais de scolarité | 300 000 F | 350 000 F | 500 000 F |

**Résultat** : Un étudiant affecté paiera 330 000 F au total, contre 550 000 F pour un non affecté.

### Exemple 2 : Changement de Statut

Un étudiant initialement "non affecté" obtient une réaffectation DOB :
1. Modifier le statut dans son dossier
2. Les frais se recalculent automatiquement
3. La différence peut donner lieu à un remboursement

## Gestion Multi-Établissements (SAAS)

### Configuration par Établissement

Chaque établissement peut :
- Définir ses propres taux de réduction
- Configurer différemment selon les accords avec l'État
- Personnaliser les statuts si nécessaire

### Rapports et Statistiques

- Répartition des étudiants par statut
- Montants collectés par catégorie de statut  
- Analyse de l'impact des subventions

## Résolution de Problèmes

### Erreur de Configuration

**Problème** : "Erreur lors de la configuration des frais"
**Solution** : Vérifiez qu'au moins un montant est défini pour chaque catégorie

### Frais non Mis à Jour

**Problème** : Les frais ne changent pas selon le statut
**Solution** : 
1. Vérifiez que la configuration est complète
2. Actualisez la page d'inscription
3. Vérifiez la connexion internet pour les mises à jour AJAX

### Montants Incohérents

**Problème** : Montant affecté supérieur au non affecté
**Solution** : Respectez la logique : Affecté ≤ Réaffecté ≤ Non affecté

## Conseils d'Utilisation

### 💡 Bonnes Pratiques

1. **Planification** : Configurez tous les frais avant la période d'inscription
2. **Test** : Testez avec quelques inscriptions pilotes
3. **Communication** : Informez les équipes des nouveaux statuts
4. **Suivi** : Surveillez les statistiques de répartition

### 📊 Recommandations de Tarification

- **Affecté** : 60-70% du montant de base
- **Réaffecté** : 70-80% du montant de base
- **Non affecté** : 100% du montant de base

### 🔒 Sécurité

- Seuls les administrateurs peuvent modifier les configurations
- Les changements sont tracés et horodatés
- Sauvegarde automatique des modifications

## Support

Pour toute question ou problème :
1. Consultez cette documentation
2. Contactez l'équipe KLASSCI
3. Utilisez la fonction d'aide intégrée dans l'interface

---

*Guide mis à jour le 13 septembre 2025*
*KLASSCI SAAS - Système de Gestion Éducative Ivoirienne*