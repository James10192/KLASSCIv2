# Système d'Affectation des Étudiants - Documentation Technique KLASSCI

## 📋 Vue d'ensemble

KLASSCI est un logiciel SAAS (Software as a Service) de gestion d'établissements d'enseignement supérieur. Le système d'affectation permet aux établissements-clients de gérer différents statuts d'étudiants selon le système d'affectation post-BAC gouvernemental en Côte d'Ivoire.

## 🏛️ Contexte du Système d'Affectation Ivoirien

### Processus gouvernemental (MESRS)
Le Ministère de l'Enseignement Supérieur et de la Recherche Scientifique (MESRS) gère l'affectation des bacheliers via :
- **Plateforme officielle** : bac.mesrs-ci.net
- **Processus** : Les bacheliers font 4 vœux d'orientation par ordre de préférence
- **Critères d'affectation** : Notes, série BAC, moyenne générale, âge, capacités d'accueil, nationalité
- **Réaffectations** : Gérées par la Direction de l'Orientation et des Bourses (DOB)

### Impact pour les établissements utilisant KLASSCI
Les établissements d'enseignement supérieur (clients KLASSCI) reçoivent des étudiants ayant différents statuts d'affectation gouvernementale et doivent pouvoir :
- Enregistrer le statut d'affectation de chaque étudiant
- Appliquer des tarifs différenciés selon la prise en charge étatique
- Suivre précisément les inscriptions selon les politiques gouvernementales

## 🎯 Objectifs du Module KLASSCI

- Permettre la classification des étudiants en 3 statuts : **Affecté**, **Réaffecté**, **Non Affecté**
- Appliquer des tarifs différenciés selon le statut d'affectation gouvernementale
- Maintenir l'intégrité des données et la cohérence du système de facturation multi-tenant

## 📊 Impact sur les Modules

## 📚 Définition des Statuts d'Affectation

### 1. **"Affecté"**
- **Définition** : Étudiant affecté directement par l'État ivoirien via la plateforme bac.mesrs-ci.net
- **Contexte** : Affectation officielle suite aux résultats du BAC et aux vœux d'orientation
- **Prise en charge** : Bénéficie potentiellement d'une subvention ou prise en charge partielle de l'État
- **Tarification** : Frais réduits selon les politiques de l'établissement

### 2. **"Réaffecté"**
- **Définition** : Étudiant initialement affecté dans un autre établissement par l'État, puis réaffecté via la DOB
- **Contexte** : Demande de réaffectation traitée par la Direction de l'Orientation et des Bourses
- **Prise en charge** : Maintien de la prise en charge étatique après réaffectation
- **Tarification** : Frais généralement similaires aux "affectés" (subvention maintenue)

### 3. **"Non Affecté"**
- **Définition** : Étudiant non retenu dans le système public d'affectation gouvernemental
- **Contexte** : Inscription directe dans l'établissement (privé ou places non-affectées)
- **Prise en charge** : Aucune subvention étatique
- **Tarification** : Tarif complet fixé par l'établissement

## 📊 Impact sur les Modules KLASSCI

### 1. **Module Inscriptions**

#### Modifications apportées :
- **Table** : `esbtp_inscriptions`
  - Nouveau champ : `affectation_status` ENUM('affecté', 'réaffecté', 'non_affecté')
  - Valeur par défaut : 'affecté'
  - Index ajouté pour l'optimisation des requêtes

#### Pages concernées :
- `/esbtp/inscriptions/create` : Sélecteur de statut d'affectation gouvernementale
- `/esbtp/inscriptions/show` : Affichage du statut avec badge coloré selon la prise en charge
- `/esbtp/inscriptions/index` : Filtrage et statistiques par statut d'affectation

#### Modèle ESBTPInscription :
```php
// Nouveau champ ajouté dans $fillable
'affectation_status'

// Nouveaux scopes ajoutés
scopeAffectes($query)
scopeReaffectes($query) 
scopeNonAffectes($query)
```

### 2. **Module Étudiants**

#### Pages concernées :
- `/esbtp/etudiants/show` : Affichage du statut d'affectation dans l'historique des inscriptions
- Vue étudiants-inscriptions : Ajout d'une colonne pour le statut

### 3. **Module Frais**

#### Modifications majeures :
- **Table** : `esbtp_frais_configurations`
  - Nouveaux champs :
    - `amount_affecte` : Montant pour étudiants affectés
    - `amount_reaffecte` : Montant pour étudiants réaffectés  
    - `amount_non_affecte` : Montant pour étudiants non affectés
  - Migration des données existantes vers les nouveaux champs

#### Pages concernées :
- `/esbtp/frais/configure` : Interface pour saisir 3 montants par combinaison filière+niveau
- `/esbtp/frais/show` : Affichage des 3 tarifs selon les statuts d'affectation
- `/esbtp/frais/index` : Statistiques mises à jour pour inclure les variantes de tarifs

#### Logique de tarification :
```php
public function getMontantByStatus($fraisConfig, $affectationStatus) {
    return match($affectationStatus) {
        'affecté' => $fraisConfig->amount_affecte ?? $fraisConfig->amount,
        'réaffecté' => $fraisConfig->amount_reaffecte ?? $fraisConfig->amount,
        'non_affecté' => $fraisConfig->amount_non_affecte ?? $fraisConfig->amount,
        default => $fraisConfig->amount
    };
}
```

## 🗄️ Structure de Base de Données

### Migrations créées :

1. **`add_affectation_status_to_esbtp_inscriptions_table.php`**
   - Ajoute le champ `affectation_status`
   - Crée l'index `idx_inscriptions_affectation`

2. **`add_affectation_amounts_to_esbtp_frais_configurations_table.php`**  
   - Ajoute les 3 champs de montant par statut
   - Migre les données existantes
   - Crée l'index composé `idx_frais_config_affectation`

### Relations et contraintes :
- Le statut d'affectation est lié à chaque inscription individuelle
- Les montants sont configurés par combinaison (filière + niveau + catégorie de frais)
- Fallback vers le montant principal si un montant spécifique n'est pas défini

## 🎨 Interface Utilisateur

### Conventions d'affichage :
- **Affecté** : Badge vert (`badge success`)
- **Réaffecté** : Badge orange (`badge warning`)
- **Non Affecté** : Badge rouge (`badge danger`)

### Pages de configuration :
- Interface intuitive avec 3 colonnes de montants
- Validation côté client et serveur
- Possibilité de copier un montant sur les 3 statuts

## 🔄 Logique Métier

### Règles de gestion :
1. **Inscription** : Le statut d'affectation est obligatoire à la création
2. **Frais** : Les montants sont récupérés selon le statut de l'inscription
3. **Historique** : Le statut est conservé pour traçabilité
4. **Rétrocompatibilité** : Les inscriptions existantes sont marquées "affecté" par défaut

### Services impactés :
- `ESBTPInscriptionService` : Gestion des inscriptions avec statut
- `FraisCalculationService` : Calcul des frais selon le statut
- `FraisManagementService` : Configuration des montants différenciés

## ⚡ Optimisations

### Index de base de données :
- `idx_inscriptions_affectation` : Requêtes par statut d'affectation
- `idx_frais_config_affectation` : Requêtes de configuration des frais

### Cache et performance :
- Les configurations de frais sont mises en cache
- Les requêtes utilisent les index optimisés
- Lazy loading pour les relations

## 🧪 Tests et Validation

### Cas de test à implémenter :
1. **Inscription** : Création avec différents statuts
2. **Frais** : Calcul correct selon le statut
3. **Interface** : Affichage des badges et formulaires
4. **Migration** : Intégrité des données après migration

### Validation des données :
- Statuts d'affectation : valeurs enum strictes
- Montants : nombres positifs, format décimal cohérent
- Relations : intégrité référentielle maintenue

## 📈 Métriques et Suivi

### Indicateurs à surveiller :
- Répartition des étudiants par statut d'affectation
- Impact sur les revenus par statut
- Performance des requêtes avec les nouveaux index

### Reporting :
- Tableaux de bord avec ventilation par statut
- Statistiques financières différenciées
- Suivi des changements de statut

## 🚀 Déploiement

### Ordre de déploiement :
1. Exécuter les migrations de base de données
2. Mettre à jour les modèles et services
3. Déployer les nouvelles interfaces
4. Vérifier l'intégrité des données
5. Former les utilisateurs sur les nouvelles fonctionnalités

### Points d'attention :
- Migration des données existantes
- Test des performances après ajout des index
- Validation du comportement des formulaires
- Vérification des calculs de frais

---

**Date de création** : 13 septembre 2025  
**Version** : 1.0  
**Auteur** : Claude Code  
**Status** : En développement