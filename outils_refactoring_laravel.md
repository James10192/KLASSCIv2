# 🛠️ OUTILS REFACTORING & NETTOYAGE - Laravel/PHP

## 🎯 **OUTILS INDISPENSABLES POUR KLASSCI**

---

## 1️⃣ **LARASTAN - Analyse statique Laravel**

### 🔍 **Ce qu'il détecte automatiquement :**
- ✅ **Code mort/inutilisé** (méthodes, propriétés, vues)
- ✅ **Imports inutiles** 
- ✅ **Types incohérents** (status vs statut)
- ✅ **Relations Eloquent incorrectes**
- ✅ **Appels de méthodes inexistantes**
- ✅ **Vues Blade non utilisées**

### 📦 **Installation & Configuration :**
```bash
# Installation
composer require --dev "larastan/larastan:^3.0"

# Configuration phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
        - routes/
        - resources/views/
    level: 5
    checkUnusedViews: true          # ✅ Détecte vues inutilisées
    checkModelProperties: true      # ✅ Détecte propriétés Model incorrectes
    noUnnecessaryCollectionCall: true # ✅ Optimise les requêtes
    noModelMake: true               # ✅ Préfère 'new Model()' à 'Model::make()'
```

### ⚡ **Commandes d'analyse :**
```bash
# Analyse complète
./vendor/bin/phpstan analyse

# Avec mémoire étendue (gros projets)
./vendor/bin/phpstan analyse --memory-limit=2G

# Génération baseline (ignore erreurs existantes)
./vendor/bin/phpstan analyse --generate-baseline
```

### 🎯 **Pour votre cas KLASSCI :**
```bash
# Détecte les doublons dans vos models
./vendor/bin/phpstan analyse app/Models/ESBTPPaiement.php

# Trouve les vues non utilisées
./vendor/bin/phpstan analyse resources/views/esbtp/comptabilite/
```

---

## 2️⃣ **PHP CS FIXER - Refactoring automatique du code**

### 🔧 **Ce qu'il corrige automatiquement :**
- ✅ **Formatage cohérent** (PSR-12)
- ✅ **Imports optimisés** (supprime inutiles, trie alphabétiquement)
- ✅ **Syntaxe moderne** (array() → [], isset() optimisé)
- ✅ **Espaces/indentation** uniforme
- ✅ **DocBlocks** standardisés

### 📦 **Installation & Configuration :**
```bash
# Installation
composer require --dev friendsofphp/php-cs-fixer

# Configuration .php-cs-fixer.php
<?php
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/app')
            ->in(__DIR__ . '/routes')
            ->name('*.php')
    );
```

### ⚡ **Commandes de nettoyage :**
```bash
# Aperçu des changements (dry-run)
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Application automatique des corrections
./vendor/bin/php-cs-fixer fix

# Correction d'un fichier spécifique
./vendor/bin/php-cs-fixer fix app/Http/Controllers/ESBTPComptabiliteController.php
```

---

## 3️⃣ **RECTOR - Refactoring avancé & modernisation**

### 🚀 **Ce qu'il modernise automatiquement :**
- ✅ **Syntaxe PHP moderne** (array() → [], class_alias → use)
- ✅ **Laravel upgrades** (automatiques entre versions)
- ✅ **Patterns obsolètes** (détecte et remplace)
- ✅ **Type declarations** ajoutées automatiquement
- ✅ **Dead code removal** avancé

### 📦 **Installation & Configuration :**
```bash
# Installation
composer require --dev rector/rector

# Configuration rector.php
<?php
use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
    ]);

    // Laravel modern patterns
    $rectorConfig->sets([
        LaravelSetList::LARAVEL_90,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ]);

    // Dead code removal
    $rectorConfig->rules([
        RemoveUselessParamTagRector::class,
    ]);
};
```

### ⚡ **Commandes de modernisation :**
```bash
# Analyse sans modification
./vendor/bin/rector process --dry-run

# Application des transformations
./vendor/bin/rector process

# Modernisation spécifique
./vendor/bin/rector process app/Http/Controllers/
```

---

## 4️⃣ **PHPMD - Détection de code complexe**

### 🔍 **Ce qu'il analyse :**
- ✅ **Méthodes trop longues** (+ de X lignes)
- ✅ **Complexité cyclomatique** élevée
- ✅ **Classes trop grosses**
- ✅ **Paramètres excessifs**
- ✅ **Code dupliqué**

### 📦 **Installation & Usage :**
```bash
# Installation
composer require --dev phpmd/phpmd

# Analyse
./vendor/bin/phpmd app/ text cleancode,codesize,controversial,design,naming,unusedcode
```

---

## 5️⃣ **PHPSTAN UNUSED - Détection code inutilisé**

### 🗑️ **Ce qu'il trouve :**
- ✅ **Méthodes jamais appelées**
- ✅ **Propriétés inutilisées**
- ✅ **Classes orphelines**
- ✅ **Constants non référencées**

### 📦 **Installation :**
```bash
composer require --dev shipmonk/phpstan-rules
```

---

## 6️⃣ **DEPTRAC - Analyse des dépendances**

### 📊 **Architecture analysis :**
- ✅ **Dépendances circulaires**
- ✅ **Violations de couches** (Controller → Model direct)
- ✅ **Architecture respect**

---

## 🚀 **WORKFLOW AUTOMATISÉ POUR KLASSCI**

### 1️⃣ **Script de nettoyage complet :**
```bash
#!/bin/bash
# scripts/clean-code.sh

echo "🔍 Analyse statique avec Larastan..."
./vendor/bin/phpstan analyse --memory-limit=2G

echo "🔧 Correction automatique du style..."
./vendor/bin/php-cs-fixer fix

echo "🚀 Modernisation avec Rector..."
./vendor/bin/rector process --dry-run

echo "📊 Détection complexité..."
./vendor/bin/phpmd app/ text cleancode,codesize,unusedcode

echo "✅ Nettoyage terminé !"
```

### 2️⃣ **Configuration Composer scripts :**
```json
// composer.json
{
    "scripts": {
        "analyse": [
            "./vendor/bin/phpstan analyse"
        ],
        "fix-style": [
            "./vendor/bin/php-cs-fixer fix"
        ],
        "modernize": [
            "./vendor/bin/rector process"
        ],
        "clean-code": [
            "@analyse",
            "@fix-style", 
            "@modernize"
        ]
    }
}
```

### 3️⃣ **Commandes simplifiées :**
```bash
# Analyse complète
composer analyse

# Nettoyage automatique 
composer fix-style

# Modernisation
composer modernize

# Tout en une fois
composer clean-code
```

---

## 🎯 **RECOMMANDATIONS SPÉCIFIQUES KLASSCI**

### **Phase 1 : Analyse (0 modifications)**
```bash
# 1. Installer Larastan
composer require --dev "larastan/larastan:^3.0"

# 2. Créer phpstan.neon
# 3. Première analyse
./vendor/bin/phpstan analyse

# Résultat : Liste complète des problèmes
```

### **Phase 2 : Corrections automatiques**
```bash
# 1. Installer PHP CS Fixer
composer require --dev friendsofphp/php-cs-fixer

# 2. Corrections de style automatiques
./vendor/bin/php-cs-fixer fix

# Résultat : Code formaté, imports nettoyés
```

### **Phase 3 : Modernisation**
```bash
# 1. Installer Rector
composer require --dev rector/rector

# 2. Modernisation Laravel
./vendor/bin/rector process

# Résultat : Code moderne, patterns optimisés
```

### **Phase 4 : Vérification finale**
```bash
# Nouvelle analyse Larastan
./vendor/bin/phpstan analyse

# Résultat : Erreurs réduites de 70-90% !
```

---

## 💡 **BÉNÉFICES ATTENDUS**

### ✅ **Automatisation :**
- **90% des nettoyages** sont automatiques
- **Zéro effort manuel** pour le formatage
- **Détection proactive** des problèmes

### ✅ **Qualité :**
- **Code cohérent** (nomenclature unifiée)
- **Performance améliorée** (requêtes optimisées)
- **Maintenance facilitée**

### ✅ **Productivité :**
- **Moins de bugs** en production
- **Reviews plus rapides**
- **Onboarding simplifié**

**RÉSULTAT :** Votre refactoring KLASSCI sera **beaucoup plus rapide et fiable** avec ces outils ! 🚀

---

## 📋 **CHECKLIST D'INSTALLATION**

```bash
# 1. Installation des outils
composer require --dev "larastan/larastan:^3.0"
composer require --dev friendsofphp/php-cs-fixer  
composer require --dev rector/rector

# 2. Configuration des fichiers
# - phpstan.neon
# - .php-cs-fixer.php  
# - rector.php

# 3. Premier run
composer clean-code

# 4. Intégration CI/CD (optionnel)
# - GitHub Actions
# - Pre-commit hooks
```

Ces outils vont **révolutionner** votre processus de nettoyage ! 🛠️✨