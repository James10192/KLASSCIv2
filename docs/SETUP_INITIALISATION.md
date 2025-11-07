# 🚀 KLASSCI Setup Scripts - Guide d'utilisation

Scripts d'initialisation unifiés pour KLASSCI (African Digit Consulting)

## 📋 Vue d'ensemble

Ce système unifie tous les scripts d'initialisation et seeders nécessaires au déploiement de KLASSCI :

| Script | Description | Usage |
|--------|-------------|-------|
| **setup.php** | Orchestrateur principal - Exécute tous les scripts d'init | `php setup.php` |
| **verify.php** | Vérification de l'état du système | `php verify.php` |
| **.setup.lock** | Fichier de tracking (JSON) - Ne PAS committer | Auto-généré |

## ⚙️ Scripts inclus dans setup.php

L'orchestrateur `setup.php` exécute automatiquement dans l'ordre :

1. **init_storage.php** - Initialisation du stockage
   - Création structure dossiers (photos, logos, documents...)
   - Configuration lien symbolique `public/storage`
   - Création fichiers placeholder/sécurité

2. **fix_permissions.php** - Configuration permissions Spatie
   - Création/vérification permissions (210 permissions)
   - Configuration rôles (superAdmin, admin, secretaire, enseignant, etudiant...)
   - Attribution permissions aux rôles

3. **deploy_settings.php** - Déploiement paramètres système
   - Paramètres établissement (nom, logo, adresse...)
   - Configuration bulletins
   - Paramètres système

4. **Seeders Laravel** - Données initiales critiques
   - ChatbotSeeder (prompts IA + templates)
   - ServiceTechniqueSeeder (compte African Digit Consulting)
   - SettingsSeeder (paramètres par défaut)

## 🚀 Utilisation Rapide

### Installation initiale (première fois)

```bash
# Mode automatique - Tout exécuter
php setup.php
```

### Vérifier l'état du système

```bash
# Vérification simple
php verify.php

# Vérification détaillée avec suggestions de correction
php verify.php --verbose --fix

# Export JSON (pour CI/CD)
php verify.php --json
```

### Réexécuter si nécessaire

```bash
# Forcer réexécution complète
php setup.php --force

# Exécuter seulement une étape spécifique
php setup.php --only=storage
php setup.php --only=permissions
php setup.php --only=settings
php setup.php --only=seeders

# Tout sauf seeders
php setup.php --skip=seeders
```

## 📖 Options Avancées

### setup.php

| Option | Description |
|--------|-------------|
| `--interactive` / `-i` | Mode interactif avec confirmations |
| `--force` / `-f` | Réexécuter même si déjà fait |
| `--only=<step>` | Exécuter seulement une étape (storage, permissions, settings, seeders) |
| `--skip=<step>` | Sauter une étape |

**Exemples** :

```bash
# Mode interactif (demande confirmation pour chaque étape)
php setup.php --interactive

# Réinitialiser seulement le stockage
php setup.php --force --only=storage

# Tout sauf les seeders (utile si déjà exécutés manuellement)
php setup.php --skip=seeders

# Combiner plusieurs options
php setup.php --interactive --force --only=permissions,settings
```

### verify.php

| Option | Description |
|--------|-------------|
| `--verbose` / `-v` | Affichage détaillé |
| `--fix` | Suggère commandes de correction |
| `--json` | Output JSON pour intégration CI/CD |

**Exemples** :

```bash
# Vérification détaillée avec suggestions
php verify.php --verbose --fix

# Export JSON pour scripts automatisés
php verify.php --json > setup-status.json

# Intégration CI/CD
if php verify.php --json | jq -r '.ready' | grep -q 'true'; then
  echo "✅ Système prêt"
else
  echo "❌ Système non prêt"
  exit 1
fi
```

## 📁 Structure du fichier .setup.lock

Le fichier `.setup.lock` (JSON) track l'état de chaque composant :

```json
{
  "version": "1.0",
  "last_run": "2025-11-06 19:00:00",
  "storage": {
    "status": "success",
    "date": "2025-11-06 19:00:05",
    "errors": []
  },
  "permissions": {
    "status": "success",
    "date": "2025-11-06 19:01:23",
    "errors": []
  },
  "settings": {
    "status": "success",
    "date": "2025-11-06 19:02:10",
    "errors": []
  },
  "seeders": {
    "ChatbotSeeder": {
      "status": "success",
      "date": "2025-11-06 19:03:00",
      "errors": []
    },
    "ServiceTechniqueSeeder": {
      "status": "success",
      "date": "2025-11-06 19:03:15",
      "errors": []
    },
    "SettingsSeeder": {
      "status": "success",
      "date": "2025-11-06 19:03:30",
      "errors": []
    }
  }
}
```

⚠️ **Important** : Ce fichier est automatiquement ajouté au `.gitignore` car spécifique à chaque environnement.

## 🔄 Workflow Recommandé

### Nouveau Déploiement

```bash
# 1. Cloner le repo
git clone https://github.com/James10192/KLASSCIv2.git
cd KLASSCIv2

# 2. Installer dépendances
composer install
npm install

# 3. Configurer .env
cp .env.example .env
php artisan key:generate
# Éditer .env avec vos paramètres DB, etc.

# 4. Migrations
php artisan migrate

# 5. Initialisation complète
php setup.php

# 6. Vérifier
php verify.php --verbose
```

### Mise à Jour Serveur

```bash
# 1. Pull dernières modifications
git pull origin presentation

# 2. Mettre à jour dépendances
composer install --no-dev --optimize-autoloader
npm install --production

# 3. Migrations
php artisan migrate

# 4. Vérifier état système
php verify.php --fix

# 5. Si problèmes détectés, réexécuter étapes manquantes
php setup.php --only=storage  # Exemple si storage a un problème

# 6. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### CI/CD Integration

```yaml
# .github/workflows/deploy.yml (exemple)
- name: Setup KLASSCI
  run: |
    php setup.php
    php verify.php --json > setup-status.json

- name: Verify Setup
  run: |
    if [ $(jq -r '.ready' setup-status.json) != "true" ]; then
      echo "Setup verification failed"
      jq '.results' setup-status.json
      exit 1
    fi
```

## 🐛 Résolution de Problèmes

### Problème : "setup.php déjà exécuté"

```bash
# Solution 1: Forcer réexécution
php setup.php --force

# Solution 2: Supprimer lock et réexécuter
rm .setup.lock
php setup.php
```

### Problème : "Lien symbolique storage manquant"

```bash
# Solution 1: Via setup.php
php setup.php --only=storage --force

# Solution 2: Manuelle
php artisan storage:link

# Solution 3: Script dédié
php init_storage.php
```

### Problème : "Permissions Spatie manquantes"

```bash
# Solution 1: Via setup.php
php setup.php --only=permissions --force

# Solution 2: Script dédié
php fix_permissions.php

# Solution 3: Clear cache permissions
php artisan permission:cache-reset
```

### Problème : "Seeder a échoué"

```bash
# Solution 1: Réexécuter tous les seeders
php setup.php --only=seeders --force

# Solution 2: Exécuter seeder individuel
php artisan db:seed --class=ChatbotSeeder
php artisan db:seed --class=ServiceTechniqueSeeder
php artisan db:seed --class=SettingsSeeder
```

### Problème : ".setup.lock corrompu"

```bash
# Supprimer et réinitialiser
rm .setup.lock
php setup.php
```

## 📊 Codes de Retour

Les scripts retournent des codes de sortie standard :

- **0** : Succès complet
- **1** : Erreur détectée

Utile pour scripts automatisés :

```bash
if php verify.php; then
  echo "Système OK"
else
  echo "Système KO - Vérifiez les logs"
fi
```

## 🔐 Sécurité

- **Compte Service Technique** : Créé automatiquement avec mot de passe sécurisé
  - Email: `technique@africandigitconsulting.com`
  - MDP par défaut: `ADC2024Tech!SecurePass`
  - ⚠️ **CHANGEZ CE MOT DE PASSE EN PRODUCTION !**

- **Permissions** : Système complet avec 210+ permissions et 7 rôles

## 🆘 Support

Pour tout problème :

1. Exécuter `php verify.php --verbose --fix` pour diagnostic
2. Consulter les logs Laravel : `storage/logs/laravel.log`
3. Contacter African Digit Consulting : `support@africandigitconsulting.com`

---

**Développé par African Digit Consulting**
Version 1.0 - Novembre 2025
