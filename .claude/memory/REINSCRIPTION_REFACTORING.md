# Refactorisation du Système de Réinscription

## Changements apportés

### 1. Séparation des responsabilités

#### Page `reinscription.show` (Vue d'ensemble)
- **Avant** : Contenait formulaires complexes avec sélection de classe et configuration de frais
- **Après** : Page simplifiée affichant uniquement :
  - Informations de l'étudiant
  - Situation financière avec KPI
  - Analyse académique (notes, décision, règles)
  - Bouton vers la finalisation

#### Page `reinscription.create` (Finalisation détaillée)
- **Nouveau** : Contient maintenant TOUT le processus de finalisation :
  - Sélection de décision académique
  - **Sélection du statut d'affectation** avec options correctes
  - Choix de classe avec logique locale
  - Configuration détaillée des frais
  - Gestion des reliquats (superadmin)

### 2. Correction des options de statut d'affectation

#### Problème identifié
Les options dans `reinscription.create` ne correspondaient pas aux standards :
- ❌ "Toujours affecté", "Plus affecté", "Maintenant affecté"

#### Solution appliquée
Harmonisation avec `inscriptions.create` :
- ✅ **"Affecté"** (`affecté`)
- ✅ **"Réaffecté"** (`réaffecté`)
- ✅ **"Non affecté"** (`non_affecté`)

### 3. Logique locale vs AJAX

#### Problème précédent
- Appels AJAX complexes pour charger classes et frais
- Gestion d'erreurs difficile
- Expérience utilisateur lente

#### Solution implémentée
- **Préchargement** : Toutes les données nécessaires depuis le contrôleur
- **JavaScript local** : Changements dynamiques sans appels réseau
- **Fallback AJAX** : Seulement si données locales manquantes

### 4. Gestion intelligente des montants par statut

#### Configuration flexible
Le système utilise maintenant `ESBTPFraisConfiguration` avec :
```php
// Montants différenciés selon le statut
$config->getMontantByStatus($statutAffectation)
// - 'affecté' → amount_affecte ?? amount
// - 'réaffecté' → amount_reaffecte ?? amount
// - 'non_affecté' → amount_non_affecte ?? amount
```

#### Normalisation des statuts
```php
private function normaliserStatutAffectation($statutAffectation)
{
    return match($statutAffectation) {
        'affecté', 'affecte' => 'affecté',
        'non-affecté', 'non_affecte', 'non-affecte', 'non_affecté' => 'non_affecté',
        'réaffecté', 'reaffecte', 'maintenant-affecté' => 'réaffecté',
        default => 'affecté'
    };
}
```

## Architecture technique

### Contrôleur `ESBTPReinscriptionController`

#### Méthode `show()`
- **Rôle** : Vue d'ensemble et vérification d'éligibilité
- **Données** : Informations étudiant, situation financière, analyse académique
- **Action** : Redirection vers `create()` si éligible

#### Méthode `create()`
- **Rôle** : Formulaire complet de finalisation
- **Données** : Classes par décision + frais par classe/statut préchargés
- **Logique** : `prechargerFraisPourToutesLesClasses()` + `getFraisForClasseEtAffectation()`

### Service de dispatch des paiements

#### `ReliquatPaymentDispatchService`
- **Priorité** : Reliquats année précédente → Frais courants
- **Algorithme** : FIFO pour reliquats, frais obligatoires prioritaires
- **Synchronisation** : Bidirectionnelle entre inscriptions source/destination

## Impact utilisateur

### Expérience améliorée
1. **Page show** : Vue claire de l'éligibilité sans surcharge
2. **Page create** : Processus guidé avec toutes les options
3. **Réactivité** : Changements dynamiques instantanés
4. **Cohérence** : Options standardisées dans tout le système

### Flux utilisateur optimisé
```
Étudiants → Reinscription.index → Reinscription.show → Reinscription.create → Update
         [Liste]          [Éligibilité]      [Finalisation]    [Traitement]
```

## Données techniques

### Modèles impliqués
- `ESBTPInscription` : Inscriptions principales
- `ESBTPFraisConfiguration` : Configuration frais par statut
- `ESBTPReliquatDetail` : Détail des reports de reliquats
- `ESBTPFraisSubscription` : Souscriptions frais par inscription

### Relations clés
```php
// Configuration → Frais par statut d'affectation
$fraisConfigs->getMontantByStatus($statutAffectation)

// Inscription → Reliquats sortants/entrants
$reliquatsOut = ESBTPReliquatDetail::where('inscription_source_id', $id)
$reliquatsIn = ESBTPReliquatDetail::where('inscription_destination_id', $id)
```

## Sécurité et permissions

### Gestion des reliquats
- **Superadmin** : Peut créer réinscription avec reliquat
- **Utilisateurs standards** : Réinscription bloquée si impayés
- **Validation** : Confirmation obligatoire du report de reliquat

### Intégrité financière
- Transactions atomiques pour dispatch paiements
- Logging complet des opérations financières
- Recalcul automatique des totaux après modifications

---
*Documentation mise à jour le {{ date('Y-m-d H:i:s') }}*