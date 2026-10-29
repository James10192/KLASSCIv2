# Documentation Complète du Système de Frais ESBTP

## Vue d'ensemble de l'architecture

Le système de frais ESBTP a été refactorisé pour séparer clairement les frais obligatoires (par classe) des services optionnels (globaux).

### Tables principales (post-cleanup)

#### 1. esbtp_frais_categories
**Rôle** : Catégories principales de frais
**Structure** :
- 6 catégories actives
- Types : `academic` (inscription, scolarité), `service` (cantine, transport), `administrative` (documentation, examen)
- Colonnes clés : `name`, `category_type`, `is_mandatory`, `is_active`, `default_amount`

**Relations** :
```php
// Configurations par classe
public function configurations() {
    return $this->hasMany(ESBTPFraisConfiguration::class, 'frais_category_id');
}

// Options via configurations (pour class-based)
public function options() {
    return $this->hasManyThrough(ESBTPFraisOption::class, ESBTPFraisConfiguration::class);
}

// Options globales directes
public function globalOptions() {
    return $this->hasMany(ESBTPFraisOption::class, 'frais_category_id')
        ->where('option_type', 'global')
        ->whereNull('configuration_id');
}
```

#### 2. esbtp_frais_configurations
**Rôle** : Configurations de frais par classe (filière + niveau)
**Structure** :
- 8 configurations actives pour BTS1 BATIMENT + Première année BTS
- Colonnes : `frais_category_id`, `filiere_id`, `niveau_id`, `amount`, `is_active`, `is_valid`
- Utilisé pour frais obligatoires configurés spécifiquement par classe

#### 3. esbtp_frais_options
**Rôle** : Options de frais (remplace ESBTPFraisVariant supprimé)
**Structure** :
- 21+ options actives
- Types : `class_based` vs `global`
- Colonnes : `frais_category_id`, `configuration_id`, `additional_amount`, `option_type`

**Logique de types** :
- `global` : `configuration_id = null`, disponible pour tous
- `class_based` : `configuration_id` renseigné, spécifique à une classe

**Scopes importants** :
```php
public function scopeGlobal($query) {
    return $query->whereNull('configuration_id')->where('option_type', 'global');
}

public function scopeClassBased($query) {
    return $query->whereNotNull('configuration_id')->where('option_type', 'class_based');
}

public function scopeForFraisCategory($query, $categoryId) {
    return $query->where('frais_category_id', $categoryId);
}
```

### Tables supprimées (cleanup)
- `esbtp_frais_variants` (7 records) - remplacé par esbtp_frais_options
- `esbtp_frais_rules` (6 records) - ancien système
- `esbtp_types_frais` (0 records) - vide
- `esbtp_frais_scolarite` (0 records) - vide

## Logique d'affichage unifiée

### Page de référence : optional-config
**URL** : `/esbtp/frais/optional-config`
**Controller** : `ESBTPFraisController::optionalConfig()`
**Logique** :
```php
$optionalCategories = ESBTPFraisCategory::with(['options.assignments.filiere', 'options.assignments.niveau'])
    ->where('is_mandatory', false)
    ->active()
    ->get();
```

### Uniformisation appliquée sur

#### 1. Page frais/{id}
**Controller** : `ESBTPFraisController::show()`
**Modification** : Utilise la même logique que optional-config pour services optionnels

#### 2. Page inscriptions/create
**Controller** : `ESBTPInscriptionController::getFraisByClasse()`
**Route AJAX** : `/esbtp/inscriptions/frais-by-classe/{classeId}`
**Modification** : Services optionnels utilisent la même logique

## Types de configuration

### 1. configuration
**Origine** : `ESBTPFraisConfiguration` trouvée pour filière+niveau
**Affichage** : "Tarif configuré pour cette classe"
**Utilisation** : Frais obligatoires avec montant spécifique par classe

### 2. global_options  
**Origine** : Options globales via relation `options`
**Affichage** : "Options globales disponibles pour ce service"
**Utilisation** : Services optionnels (cantine, transport)

### 3. default
**Origine** : `category.default_amount` utilisé
**Affichage** : "Montant par défaut utilisé (non configuré pour cette classe)"
**Utilisation** : Catégories sans configuration spécifique

### 4. variant/rule (legacy)
**Origine** : Anciennes tables supprimées
**Status** : Remplacé par `configuration`

## Exemple concret : Menu cantine

### Données
```
Option: "Menu complet (entre-plat-dessert)"
Prix: 325,000 FCFA
Type: global
frais_category_id: 3 (Frais de cantine)
configuration_id: null
```

### Affichage sur les 3 pages
1. **optional-config** : ✅ S'affiche dans "Frais de cantine" avec 1 option
2. **frais/3** : ✅ S'affiche avec la même logique
3. **inscriptions/create** : ✅ S'affiche dans frais optionnels

## Intégration JavaScript

### Route AJAX
```javascript
fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}`)
```

### Format de réponse
```json
{
  "success": true,
  "classe": {...},
  "frais": [
    {
      "category": {...},
      "default_amount": "750000.00",
      "configured_amount": "750000.00", 
      "is_configured": true,
      "configuration_type": "configuration",
      "options": [...]
    }
  ],
  "has_unconfigured_fees": false
}
```

### Gestion des types de configuration
```javascript
// Messages d'alerte
configurationType === 'configuration' -> "Tarif configuré pour cette classe"
configurationType === 'global_options' -> "Options globales disponibles"
configurationType === 'default' -> "Montant par défaut utilisé (non configuré)"

// Labels des boutons radio
configurationType === 'configuration' -> "Tarif configuré pour cette classe - X FCFA"
```

### Résumé des frais
```javascript
// Extraction du nom de catégorie pour le résumé
const fraisCard = option.closest('.card');
const categoryName = fraisCard.querySelector('h6').textContent.split('\n')[0].trim();

// Résultat : "Frais d'inscription" au lieu de "Tarif configuré pour cette classe"
```

## Erreurs corrigées

### 1. Erreur 500 : ESBTPFraisRule not found
**Cause** : Code utilisait encore `ESBTPFraisRule` après suppression
**Solution** : Remplacé par `ESBTPFraisConfiguration`

### 2. Montants erronés (750 FCFA au lieu de 750,000)
**Cause** : Regex JavaScript mal configurée pour parsing des montants
**Solution** : Amélioration de la regex et du parsing

### 3. Messages "non configuré" incorrects
**Cause** : JavaScript ne gérait pas `configurationType = 'configuration'`
**Solution** : Ajout du cas dans les conditions JavaScript

### 4. Colonne 'is_valid' manquante
**Cause** : Code référençait une colonne non existante
**Solution** : Migration pour ajouter la colonne

## Points clés pour maintenance future

1. **Logique unifiée** : Toujours utiliser la même requête pour les services optionnels
2. **Types d'options** : Bien distinguer `global` vs `class_based`
3. **Configuration** : Une configuration = une combinaison (catégorie + filière + niveau)
4. **Options globales** : Indépendantes de la classe, disponibles pour tous
5. **JavaScript** : Extraire les noms de catégories pour un résumé explicite

## Architecture finale validée

✅ **3 pages cohérentes** utilisant la même logique
✅ **Base de données nettoyée** (4 tables supprimées)
✅ **Frais obligatoires** correctement configurés par classe  
✅ **Services optionnels** correctement gérés de manière globale
✅ **Interface utilisateur** avec messages explicites et montants corrects