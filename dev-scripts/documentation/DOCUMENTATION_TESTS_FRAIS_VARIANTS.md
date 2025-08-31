# Documentation de Test - Système de Frais avec Variants

## Vue d'ensemble

Ce document décrit comment tester le nouveau système de frais avec variants implémenté dans ESBTP Klassci. Le système permet de gérer différents types de frais (transport, cantine, hébergement, etc.) avec des options/variants spécifiques (différents arrêts de transport, types de menus, etc.).

## Fonctionnalités Implémentées

### 1. Modèle de Données
- ✅ **ESBTPFraisVariant**: Nouveau modèle pour les variants de frais
- ✅ **Relations**: Intégration avec ESBTPFraisCategory, ESBTPFraisSubscription
- ✅ **Migration**: Base de données mise à jour

### 2. Interface de Gestion des Frais
- ✅ **Vue matricielle**: Affichage des frais par classe (filière + niveau)
- ✅ **Gestion des variants**: CRUD complet pour les variants
- ✅ **Interface responsive**: Bootstrap 5 avec modals centrés
- ✅ **API REST**: Endpoints pour les opérations AJAX

### 3. Système de Relances
- ✅ **Détection automatique**: Étudiants en retard par catégorie
- ✅ **Planification**: Relances automatiques intégrées
- ✅ **Types de relances**: Email, SMS, courrier, appel

### 4. Intégration Inscriptions
- ✅ **Sélection dynamique**: Variants selon la classe choisie
- ✅ **Calcul temps réel**: Montants et résumé automatiques
- ✅ **Souscription**: Enregistrement des choix étudiants

## Plan de Tests

### Phase 1: Configuration Initiale

#### Test 1.1: Accès au Module Frais
```
URL: http://localhost:8000/esbtp/frais
Utilisateur: superAdmin ou comptable
```

**Étapes:**
1. Se connecter avec un compte superAdmin
2. Aller dans "Comptabilité" → "Frais"
3. Vérifier l'affichage de la vue matricielle
4. Vérifier les statistiques en haut de page

**Résultat attendu:**
- Page s'affiche sans erreur
- Vue matricielle avec filières en lignes, niveaux en colonnes
- Statistiques: Total catégories, Obligatoires, Optionnelles, Actives
- Boutons "Configuration" et "Nouvelle Catégorie" visibles

#### Test 1.2: Création d'une Catégorie de Frais
```
URL: http://localhost:8000/esbtp/frais/create
```

**Étapes:**
1. Cliquer sur "Nouvelle Catégorie"
2. Remplir le formulaire:
   - Nom: "Transport"
   - Code: "TRANSPORT" (auto-généré)
   - Description: "Frais de transport scolaire"
   - Montant par défaut: 25000
   - Délai de paiement: 30 jours
   - Icône: "fas fa-bus"
   - Couleur: "warning"
   - ☑ Frais obligatoire
3. Cliquer "Créer la catégorie"

**Résultat attendu:**
- Redirection vers la liste des frais
- Message de succès affiché
- Nouvelle catégorie visible dans la liste
- Badge "Obligatoire" en rouge

#### Test 1.3: Ajout de Variants
**Étapes:**
1. Dans la liste des frais, cliquer sur l'icône "👁" (voir variants) pour "Transport"
2. Cliquer "Ajouter un variant"
3. Créer plusieurs variants:

**Variant 1:**
- Nom: "Arrêt Centre-ville"
- Description: "Transport avec arrêt au centre-ville"
- Montant: 30000 FCFA
- ☑ Variant par défaut

**Variant 2:**
- Nom: "Arrêt Université"
- Description: "Transport direct à l'université"
- Montant: 20000 FCFA

**Variant 3:**
- Nom: "Arrêt Résidentiel"
- Description: "Transport vers zones résidentielles"
- Montant: 35000 FCFA

**Résultat attendu:**
- Chaque variant créé apparaît dans la liste
- Le variant "Centre-ville" est marqué comme défaut
- Les montants sont correctement affichés

### Phase 2: Configuration par Classe

#### Test 2.1: Configuration des Frais
```
URL: http://localhost:8000/esbtp/frais/configure
```

**Étapes:**
1. Aller dans "Configuration"
2. Sélectionner une classe (ex: "Informatique - Licence 1")
3. Configurer les montants pour chaque catégorie:
   - Transport: 25000 FCFA, 30 jours
   - (autres catégories selon disponibilité)
4. Cliquer "Enregistrer la Configuration"

**Résultat attendu:**
- Configuration sauvegardée avec succès
- Retour à la vue principale
- Statut "Complet" ou "Partiel" mis à jour dans la matrice

#### Test 2.2: Vue Détaillée d'une Classe
**Étapes:**
1. Dans la vue matricielle, cliquer sur l'icône "👁" pour une classe configurée
2. Vérifier les détails affichés dans le modal

**Résultat attendu:**
- Modal s'ouvre au centre de l'écran
- Affichage des frais configurés avec variants
- Montants corrects pour chaque variant
- Bouton "Configurer les frais" fonctionnel

### Phase 3: Système de Relances

#### Test 3.1: Détection des Retards
**Étapes:**
1. Dans la liste des frais, cliquer sur "Retards" pour une catégorie
2. Vérifier l'affichage des étudiants en retard

**Résultat attendu:**
- Modal "Étudiants en Retard" s'ouvre centré
- Liste des étudiants avec:
  - Nom et prénom
  - Filière - Niveau
  - Montant dû
  - Jours de retard
- Bouton "Planifier des relances pour tous"

#### Test 3.2: Planification de Relances
**Étapes:**
1. Cliquer "Planifier des relances pour tous"
2. Configurer la relance:
   - Niveau: "1er rappel (doux)"
   - Type: "Email"
   - Délai: 3 jours
3. Cliquer "Planifier les relances"

**Résultat attendu:**
- Message de confirmation avec nombre de relances créées
- Retour à la vue principale
- (Vérifier dans le module Relances que les relances ont été créées)

### Phase 4: Intégration avec les Inscriptions

#### Test 4.1: Nouvelle Inscription avec Variants
```
URL: http://localhost:8000/esbtp/inscriptions/create
```

**Étapes:**
1. Aller dans "Étudiants" → "Inscriptions" → "Nouvelle Inscription"
2. Remplir les informations générales (nom, prénom, etc.)
3. Sélectionner une classe configurée
4. **Nouveau:** Vérifier la section "Frais d'inscription et options"

**Résultat attendu:**
- Section frais apparaît automatiquement après sélection de classe
- Frais obligatoires pré-sélectionnés avec option par défaut
- Frais optionnels disponibles à cocher
- Résumé des frais mis à jour en temps réel

#### Test 4.2: Sélection de Variants
**Étapes:**
1. Dans la section frais, modifier les sélections:
   - Transport: Changer de "Arrêt Centre-ville" vers "Arrêt Université"
   - (autres variants selon disponibilité)
2. Observer le résumé des frais

**Résultat attendu:**
- Montant mis à jour instantanément
- Résumé affiche le bon variant sélectionné
- Total recalculé automatiquement

#### Test 4.3: Finalisation de l'Inscription
**Étapes:**
1. Compléter le formulaire d'inscription
2. Cliquer "Enregistrer l'inscription"
3. Vérifier la page de détails de l'inscription

**Résultat attendu:**
- Inscription créée avec succès
- Variants sélectionnés enregistrés en base
- Souscriptions créées dans esbtp_frais_subscriptions

### Phase 5: Tests API

#### Test 5.1: API Détails de Classe
```
GET /esbtp/frais/class-details/{filiere_id}/{niveau_id}
```

**Test avec curl:**
```bash
curl -X GET "http://localhost:8000/esbtp/frais/class-details/1/1" \
  -H "Accept: application/json"
```

**Résultat attendu:**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Transport",
      "code": "TRANSPORT",
      "is_mandatory": true,
      "amount": 25000,
      "variants": [
        {
          "id": 1,
          "name": "Arrêt Centre-ville",
          "amount": 30000,
          "is_default": true
        }
      ]
    }
  ]
}
```

#### Test 5.2: API Variants d'une Catégorie
```
GET /esbtp/frais/category-variants/{category_id}
```

#### Test 5.3: API Étudiants en Retard
```
GET /esbtp/frais/{category_id}/overdue-students
```

### Phase 6: Tests de Modals et UX

#### Test 6.1: Modals Centrés
**Étapes:**
1. Tester tous les modals du système:
   - Détails de classe
   - Variants d'une catégorie
   - Ajout de variant
   - Tous les variants
   - Étudiants en retard
   - Planification de relances

**Résultat attendu:**
- Tous les modals s'ouvrent au centre de l'écran
- Scrolling interne pour contenu long
- Boutons de fermeture fonctionnels
- Pas de problèmes de positionnement

#### Test 6.2: Responsive Design
**Étapes:**
1. Tester sur différentes tailles d'écran:
   - Desktop (1920px+)
   - Tablette (768px-1200px)
   - Mobile (320px-768px)

**Résultat attendu:**
- Interface s'adapte correctement
- Modals restent utilisables sur mobile
- Tableaux avec scrolling horizontal si nécessaire

### Phase 7: Tests de Performance et Sécurité

#### Test 7.1: Permissions
**Étapes:**
1. Tester avec différents rôles:
   - superAdmin: Accès complet
   - comptable: Accès frais uniquement
   - etudiant: Pas d'accès aux frais
   - secretaire: Selon permissions

#### Test 7.2: Validation des Données
**Étapes:**
1. Tester avec des données invalides:
   - Montants négatifs
   - Variants sans nom
   - Catégories avec codes dupliqués

**Résultat attendu:**
- Validation côté serveur fonctionne
- Messages d'erreur appropriés
- Pas de corruption de données

## Checklist de Validation Finale

### ✅ Fonctionnalités Core
- [ ] Création/modification/suppression de catégories de frais
- [ ] Gestion complète des variants (CRUD)
- [ ] Configuration par classe (filière + niveau)
- [ ] Vue matricielle responsive et intuitive

### ✅ Système de Relances
- [ ] Détection automatique des retards
- [ ] Planification de relances par catégorie
- [ ] Intégration avec le module relances existant

### ✅ Inscriptions
- [ ] Sélection dynamique de variants lors de l'inscription
- [ ] Calcul temps réel des montants
- [ ] Enregistrement des souscriptions en base

### ✅ Interface Utilisateur
- [ ] Modals centrés et accessibles
- [ ] Design responsive sur tous supports
- [ ] Messages de succès/erreur appropriés
- [ ] Navigation intuitive

### ✅ APIs et Intégrations
- [ ] Endpoints REST fonctionnels
- [ ] Données JSON valides retournées
- [ ] Gestion d'erreurs appropriée

### ✅ Sécurité et Permissions
- [ ] Contrôle d'accès par rôle
- [ ] Validation des données côté serveur
- [ ] Protection CSRF fonctionnelle

## Problèmes Connus et Solutions

### 1. Erreur "View not found"
**Problème:** `View [esbtp.frais.edit] not found`
**Solution:** ✅ Résolu - Créé toutes les vues manquantes (create, edit, show)

### 2. Erreur de paramètres de route
**Problème:** `Missing required parameter for [Route: esbtp.frais.update]`
**Solution:** ✅ Résolu - Utilisation des IDs au lieu des objets complets

### 3. Modals hors écran
**Problème:** Modals non centrés
**Solution:** ✅ Résolu - Ajout des classes `modal-dialog-centered` et `modal-dialog-scrollable`

### 4. Conflits SQL
**Problème:** Colonne 'status' vs 'statut'
**Solution:** ✅ Résolu - Utilisation cohérente de 'statut' en français

## Maintenance et Évolutions Futures

### Améliorations Possibles
1. **Cache**: Mise en cache des règles de frais pour performance
2. **Notifications**: Alertes en temps réel pour nouveaux retards
3. **Rapports**: Analytics avancés sur les frais et variants
4. **Import/Export**: Gestion en lot des configurations
5. **Historique**: Suivi des modifications de variants

### Monitoring
- Surveiller les logs d'erreurs dans `storage/logs/`
- Vérifier les performances des requêtes AJAX
- Contrôler l'utilisation de l'espace de stockage (uploads)

---

## Contact et Support

Pour toute question ou problème avec ce système:
1. Vérifier les logs Laravel dans `storage/logs/`
2. Consulter cette documentation
3. Tester étape par étape selon ce guide
4. Documenter tout nouveau bug avec étapes de reproduction

**Version:** 1.0.0  
**Date:** 17 juillet 2025  
**Développé avec:** Claude Code (Anthropic)