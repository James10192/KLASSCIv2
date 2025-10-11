# Plan de Transformation SaaS de Klassci

## 📋 Résumé Exécutif

**Objectif** : Transformer Klassci en plateforme SaaS multi-tenant permettant de gérer plusieurs établissements scolaires sur un même serveur avec isolation complète des données.

**Architecture choisie** : 2 applications distinctes
- **Application Master** : Panneau d'administration SaaS (nouvelle app à créer)
- **Application Tenant** : Application métier actuelle (KLASSCIv2 avec adaptations)

**Durée estimée** : 2-3 semaines
**Complexité** : Moyenne à élevée
**Impact** : Complet (nécessite refonte infrastructure)

---

## 🎯 Objectifs du projet

### Objectifs principaux
1. ✅ **Provisioning automatique** : Créer un nouvel établissement en 1 commande
2. ✅ **Isolation des données** : Chaque établissement = BDD séparée + sous-domaine
3. ✅ **Déploiement centralisé** : Mettre à jour tous les établissements depuis un seul endroit
4. ✅ **Monitoring en temps réel** : Surveiller la santé de tous les établissements
5. ✅ **Facturation automatisée** : Gérer abonnements, essais gratuits, paiements

### Objectifs secondaires
6. ✅ **Backups automatiques** : Sauvegarde quotidienne de chaque établissement
7. ✅ **Gestion des plans** : Free, Starter, Pro, Enterprise avec limites différentes
8. ✅ **Support intégré** : Système de tickets pour chaque établissement
9. ✅ **Analytics globales** : Statistiques agrégées sur tous les établissements

---

## 📊 Comparaison : Avant vs Après

### ❌ Avant (Système actuel)

```
PROCESSUS MANUEL pour chaque nouvel établissement :

1. SSH sur le serveur
2. cd /var/www/
3. mkdir esbtp-abj && cd esbtp-abj
4. git clone https://github.com/...
5. git checkout main
6. composer install
7. Créer manuellement la BDD dans MySQL
8. Copier .env.example vers .env
9. Modifier manuellement :
   - APP_NAME
   - APP_URL
   - DB_DATABASE
   - DB_USERNAME
   - DB_PASSWORD
   - etc.
10. php artisan key:generate
11. php artisan migrate
12. php artisan db:seed
13. Créer virtual host Nginx manuellement
14. sudo nginx -t && sudo systemctl reload nginx
15. sudo certbot --nginx -d esbtp-abj.klassci.com
16. Configurer permissions (chown, chmod)

⏱️ TEMPS : 30-45 minutes par établissement
🔒 SÉCURITÉ : Risque d'erreur humaine (copier/coller mot de passe, oublier étape)
📈 SCALABILITÉ : Impossible avec 10+ établissements
```

### ✅ Après (Système SaaS automatisé)

```
PROCESSUS AUTOMATIQUE :

1. Se connecter à https://admin.klassci.com
2. Cliquer sur "Ajouter un établissement"
3. Remplir formulaire :
   - Nom : ESBTP Abidjan
   - Code : esbtp-abj
   - Sous-domaine : esbtp-abj
   - Plan : Starter
   - Email admin : admin@esbtp-abidjan.ci
4. Cliquer sur "Créer"

👉 Tout le reste se fait AUTOMATIQUEMENT en 2-3 minutes

⏱️ TEMPS : 2-3 minutes par établissement
🔒 SÉCURITÉ : Mots de passe générés automatiquement, processus standardisé
📈 SCALABILITÉ : Illimitée (100+ établissements sans problème)
📊 MONITORING : Dashboard en temps réel
```

---

## 🏗️ Architecture détaillée

### Structure des répertoires sur le serveur

```
/var/www/
│
├─ klassci-master/                    ← Application Master (NOUVELLE)
│  ├─ app/
│  │  ├─ Models/
│  │  │  ├─ Tenant.php               ← Gestion établissements
│  │  │  ├─ SaasAdmin.php            ← Admins plateforme
│  │  │  ├─ TenantDeployment.php
│  │  │  ├─ TenantBackup.php
│  │  │  └─ ...
│  │  ├─ Console/Commands/
│  │  │  ├─ TenantProvision.php      ← Créer établissement
│  │  │  ├─ TenantDeploy.php         ← Déployer mise à jour
│  │  │  ├─ TenantBackup.php
│  │  │  ├─ TenantHealthCheck.php    ← Surveiller santé
│  │  │  └─ ...
│  │  └─ Http/Controllers/
│  │     ├─ SaaS/
│  │     │  ├─ TenantController.php
│  │     │  ├─ DeploymentController.php
│  │     │  ├─ MonitoringController.php
│  │     │  └─ BillingController.php
│  ├─ resources/views/
│  │  ├─ saas/
│  │  │  ├─ dashboard.blade.php      ← Dashboard global
│  │  │  ├─ tenants/
│  │  │  │  ├─ index.blade.php       ← Liste établissements
│  │  │  │  ├─ create.blade.php
│  │  │  │  ├─ show.blade.php
│  │  │  │  └─ edit.blade.php
│  │  │  ├─ monitoring/
│  │  │  ├─ billing/
│  │  │  └─ support/
│  ├─ database/
│  │  └─ migrations/
│  │     └─ 2025_10_11_create_saas_tables.php
│  └─ .env                            ← Config Master
│     DB_DATABASE=klassci_master
│
├─ tenants/                           ← Dossier des établissements
│  │
│  ├─ esbtp-abj/                      ← Établissement 1
│  │  ├─ app/                         ← Clone de KLASSCIv2
│  │  ├─ .env
│  │  │  APP_NAME=ESBTP Abidjan
│  │  │  APP_URL=https://esbtp-abj.klassci.com
│  │  │  DB_DATABASE=klassci_esbtp_abj
│  │  │  TENANT_CODE=esbtp-abj
│  │  └─ .tenant.json                 ← Métadonnées tenant
│  │
│  ├─ lycee-koumassi/                 ← Établissement 2
│  │  ├─ app/
│  │  ├─ .env
│  │  │  APP_NAME=Lycée Koumassi
│  │  │  APP_URL=https://lycee-koumassi.klassci.com
│  │  │  DB_DATABASE=klassci_lycee_koumassi
│  │  │  TENANT_CODE=lycee-koumassi
│  │  └─ .tenant.json
│  │
│  └─ college-marcory/                ← Établissement 3
│     ├─ app/
│     ├─ .env
│     └─ .tenant.json
│
└─ backups/                           ← Backups centralisés
   ├─ esbtp-abj/
   │  ├─ 20251011_080000/
   │  │  ├─ code.tar.gz
   │  │  ├─ database.sql.gz
   │  │  └─ storage.tar.gz
   └─ lycee-koumassi/
      └─ 20251011_080000/
```

### Base de données

```
MySQL Server
│
├─ klassci_master                     ← BDD Master (1 seule)
│  ├─ tenants                         ← Liste établissements
│  ├─ saas_admins                     ← Admins plateforme
│  ├─ tenant_deployments              ← Historique déploiements
│  ├─ tenant_backups
│  ├─ tenant_health_checks
│  ├─ tenant_features
│  ├─ tenant_activity_logs
│  ├─ support_tickets
│  └─ invoices
│
├─ klassci_esbtp_abj                  ← BDD Tenant 1
│  ├─ users
│  ├─ esbtp_etudiants
│  ├─ esbtp_inscriptions
│  ├─ esbtp_paiements
│  ├─ esbtp_notes
│  └─ ... (toutes les tables métier)
│
├─ klassci_lycee_koumassi             ← BDD Tenant 2
│  ├─ users
│  ├─ esbtp_etudiants
│  └─ ...
│
└─ klassci_college_marcory            ← BDD Tenant 3
   ├─ users
   ├─ esbtp_etudiants
   └─ ...
```

### Sous-domaines (Nginx)

```
admin.klassci.com           → /var/www/klassci-master/public
esbtp-abj.klassci.com       → /var/www/tenants/esbtp-abj/public
lycee-koumassi.klassci.com  → /var/www/tenants/lycee-koumassi/public
college-marcory.klassci.com → /var/www/tenants/college-marcory/public
```

---

## 📝 Plan de développement (7 phases)

### Phase 1 : Préparation (Jour 1-2)

#### 1.1 Créer repository `klassci-master`
```bash
cd ~/workspace
mkdir klassci-master && cd klassci-master
composer create-project laravel/laravel . --prefer-dist
git init
git remote add origin https://github.com/your-org/klassci-master.git
```

#### 1.2 Configuration de base
- Copier fichiers de base (config, helpers)
- Configurer `.env` pour Master DB
- Créer structure de dossiers

#### 1.3 Créer migrations Master
- Table `tenants`
- Table `saas_admins`
- Table `tenant_deployments`
- Table `tenant_backups`
- Table `tenant_health_checks`
- Table `tenant_features`
- Table `tenant_activity_logs`

#### 1.4 Créer modèles Eloquent
- `Tenant.php`
- `SaasAdmin.php`
- `TenantDeployment.php`
- `TenantBackup.php`
- `TenantHealthCheck.php`
- `TenantFeature.php`
- `TenantActivityLog.php`

---

### Phase 2 : Commandes Artisan (Jour 3-4)

#### 2.1 Commande `tenant:provision`
```bash
php artisan tenant:provision \
    --code=esbtp-abj \
    --name="ESBTP Abidjan" \
    --subdomain=esbtp-abj \
    --branch=main \
    --plan=starter \
    --admin-email=admin@esbtp.ci
```

**Actions** :
1. Créer BDD + user MySQL
2. Cloner repo KLASSCIv2
3. Générer `.env` unique
4. `composer install`
5. `php artisan migrate`
6. `php artisan db:seed`
7. Créer virtual host Nginx
8. Obtenir certificat SSL
9. Enregistrer dans table `tenants`
10. Envoyer email bienvenue

#### 2.2 Commande `tenant:deploy`
```bash
# Déployer un tenant
php artisan tenant:deploy esbtp-abj

# Déployer tous les tenants
php artisan tenant:deploy --all
```

**Actions** :
1. Backup automatique
2. Mode maintenance ON
3. `git pull`
4. `composer install`
5. `php artisan migrate`
6. Cache clear + config cache
7. Permissions
8. Mode maintenance OFF
9. Enregistrer dans `tenant_deployments`

#### 2.3 Commande `tenant:backup`
```bash
# Backup manuel
php artisan tenant:backup esbtp-abj

# Backup tous les tenants
php artisan tenant:backup --all
```

#### 2.4 Commande `tenant:health-check`
```bash
# Check un tenant
php artisan tenant:health-check esbtp-abj

# Check tous les tenants
php artisan tenant:health-check --all
```

**Vérifications** :
- HTTP (status code 200)
- Database (connexion OK)
- Storage (espace disponible)
- Queue (workers actifs)

#### 2.5 Commande `saas:create-admin`
```bash
php artisan saas:create-admin \
    --name="Marcel Dev" \
    --email="marcel@klassci.com" \
    --role=super_admin
```

---

### Phase 3 : Dashboard Master (Jour 5-7)

#### 3.1 Authentification
- Login SaaS admin (`/saas/login`)
- Middleware `auth:saas-admin`
- Guards séparés de l'app tenant

#### 3.2 Dashboard principal
**URL** : `https://admin.klassci.com/saas/dashboard`

**KPI Cards** :
- Total établissements actifs
- Total étudiants (agrégé)
- MRR (Monthly Recurring Revenue)
- Uptime moyen
- Stockage total utilisé

**Graphiques** :
- Croissance établissements (12 derniers mois)
- Revenus mensuels
- Santé des tenants (pie chart : healthy/warning/critical)

**Alertes récentes** :
- Tenants down
- Déploiements échoués
- Essais qui expirent bientôt

#### 3.3 Gestion des tenants
**URL** : `https://admin.klassci.com/saas/tenants`

**Liste des tenants** :
```
┌──────────────┬────────────────────┬──────────────────────────┬──────────┬─────────┬────────┐
│ Code         │ Nom                │ URL                      │ Statut   │ Plan    │ Usage  │
├──────────────┼────────────────────┼──────────────────────────┼──────────┼─────────┼────────┤
│ esbtp-abj    │ ESBTP Abidjan      │ esbtp-abj.klassci.com    │ ✅ Active │ Starter │ 45/200 │
│ lycee-koum   │ Lycée Koumassi     │ lycee-koum.klassci.com   │ ✅ Active │ Pro     │ 180/500│
│ college-marc │ Collège Marcory    │ college-marc.klassci.com │ ⏸️ Suspen │ Free    │ 10/50  │
└──────────────┴────────────────────┴──────────────────────────┴──────────┴─────────┴────────┘
```

**Actions** :
- ➕ Créer nouveau tenant
- 👁️ Voir détails
- ✏️ Modifier
- 🚀 Déployer mise à jour
- 💾 Créer backup
- ⏸️ Suspendre
- 🔄 Réactiver
- 🗑️ Supprimer

#### 3.4 Page détails d'un tenant
**URL** : `https://admin.klassci.com/saas/tenants/esbtp-abj`

**Sections** :
1. **Informations générales**
   - Nom, code, sous-domaine
   - Admin contact (nom, email, phone)
   - Statut, plan, dates abonnement

2. **Statistiques d'usage**
   - Étudiants : 45/200 (22.5%)
   - Personnel : 8/15 (53%)
   - Stockage : 512/2048 MB (25%)
   - Graphique évolution 30 derniers jours

3. **Santé du tenant**
   - HTTP : ✅ Healthy (response time: 120ms)
   - Database : ✅ Healthy
   - Storage : ✅ Healthy (25% utilisé)
   - Queue : ✅ 5 workers actifs

4. **Historique des déploiements**
   ```
   ┌────────────────────┬──────────┬─────────┬──────────┬──────────┐
   │ Date               │ Commit   │ Branche │ Statut   │ Durée    │
   ├────────────────────┼──────────┼─────────┼──────────┼──────────┤
   │ 2025-10-11 08:30   │ a8c14cb  │ main    │ ✅ Success │ 45s     │
   │ 2025-10-10 10:15   │ 59fbafb  │ main    │ ✅ Success │ 38s     │
   │ 2025-10-09 14:20   │ 0002fc5  │ main    │ ❌ Failed  │ 12s     │
   └────────────────────┴──────────┴─────────┴──────────┴──────────┘
   ```

5. **Backups disponibles**
   ```
   ┌────────────────────┬────────┬────────┬─────────┬──────────────┐
   │ Date               │ Type   │ Taille │ Statut  │ Actions      │
   ├────────────────────┼────────┼────────┼─────────┼──────────────┤
   │ 2025-10-11 02:00   │ Auto   │ 256 MB │ ✅ OK    │ Restaurer    │
   │ 2025-10-10 02:00   │ Auto   │ 250 MB │ ✅ OK    │ Restaurer    │
   │ 2025-10-09 15:30   │ Manuel │ 248 MB │ ✅ OK    │ Restaurer    │
   └────────────────────┴────────┴────────┴─────────┴──────────────┘
   ```

6. **Logs d'activité récents**
   ```
   ┌────────────────────┬──────────────────┬────────────────────────────┐
   │ Date               │ Action           │ Description                │
   ├────────────────────┼──────────────────┼────────────────────────────┤
   │ 2025-10-11 08:30   │ deploy_success   │ Déploiement réussi         │
   │ 2025-10-10 10:15   │ backup_created   │ Backup automatique créé    │
   │ 2025-10-09 14:20   │ deploy_failed    │ Erreur migration DB        │
   └────────────────────┴──────────────────┴────────────────────────────┘
   ```

7. **Actions rapides**
   - 🚀 Déployer mise à jour
   - 💾 Créer backup maintenant
   - 🔄 Vérifier santé
   - ⏸️ Mode maintenance
   - ⚙️ Modifier configuration

---

### Phase 4 : Monitoring & Alertes (Jour 8-9)

#### 4.1 Health checks automatiques
**Scheduler** : Toutes les 5 minutes
```php
// app/Console/Kernel.php
$schedule->command('tenant:health-check --all')
         ->everyFiveMinutes();
```

#### 4.2 Backups automatiques
**Scheduler** : Tous les jours à 2h du matin
```php
$schedule->command('tenant:backup --all')
         ->dailyAt('02:00');
```

#### 4.3 Notifications Slack/Email
- Tenant down (critical)
- Déploiement échoué
- Stockage > 90%
- Essai gratuit expire dans 7 jours

---

### Phase 5 : Facturation (Jour 10-12)

#### 5.1 Plans tarifaires
```php
'free' => [
    'monthly_fee' => 0,
    'max_students' => 50,
    'max_staff' => 5,
    'max_storage_mb' => 512,
    'features' => ['email', 'reports'],
],
'starter' => [
    'monthly_fee' => 25000, // 25,000 FCFA (~38€)
    'max_students' => 200,
    'max_staff' => 15,
    'max_storage_mb' => 2048,
    'features' => ['email', 'reports', 'parent_access'],
],
'professional' => [
    'monthly_fee' => 50000,
    'max_students' => 500,
    'max_staff' => 30,
    'max_storage_mb' => 5120,
    'features' => ['email', 'whatsapp', 'reports', 'parent_access', 'api'],
],
'enterprise' => [
    'monthly_fee' => 100000,
    'max_students' => 9999,
    'max_staff' => 100,
    'max_storage_mb' => 20480,
    'features' => ['all'],
],
```

#### 5.2 Essais gratuits
- 30 jours gratuits pour tous les nouveaux tenants
- Email automatique 7 jours avant expiration
- Email automatique 1 jour avant expiration
- Suspension automatique si pas de paiement

#### 5.3 Génération factures
- Facture mensuelle automatique
- PDF généré avec détails (plan, période, montant)
- Envoi par email
- Historique dans dashboard

#### 5.4 Intégration paiement
- Mobile Money (Orange Money, MTN, Moov)
- Carte bancaire (Stripe/Paystack)
- Virement bancaire (manuel)

---

### Phase 6 : Scripts de déploiement (Jour 13-14)

#### 6.1 Script Bash principal
**Fichier** : `deploy-saas.sh`

**Commandes** :
```bash
# Setup initial (une seule fois)
sudo ./deploy-saas.sh setup

# Créer un tenant
sudo ./deploy-saas.sh create esbtp-abj "ESBTP Abidjan" esbtp-abj

# Déployer un tenant
sudo ./deploy-saas.sh deploy esbtp-abj

# Déployer tous les tenants
sudo ./deploy-saas.sh deploy-all

# Backup un tenant
sudo ./deploy-saas.sh backup esbtp-abj

# Status de tous les tenants
sudo ./deploy-saas.sh status

# Rollback
sudo ./deploy-saas.sh rollback esbtp-abj 20251010_100000
```

#### 6.2 Webhooks GitHub (optionnel)
- Push sur `main` → Notification dans Master
- Tag créé → Proposition de déploiement
- Pull request merged → Alerte nouveaux changements

---

### Phase 7 : Tests & Documentation (Jour 15-16)

#### 7.1 Tests
- Test provisioning d'un tenant de A à Z
- Test déploiement
- Test backup/restore
- Test suspension/réactivation
- Test health checks
- Test génération factures

#### 7.2 Documentation
- Guide d'installation Master
- Guide de création tenant
- Guide de déploiement
- Guide de monitoring
- FAQ
- Troubleshooting

---

## 🔧 Modifications nécessaires dans KLASSCIv2 (app actuelle)

### 1. Ajout variable tenant dans `.env`
```env
# Informations tenant (injectées automatiquement par Master)
TENANT_CODE=esbtp-abj
TENANT_NAME="ESBTP Abidjan"
TENANT_PLAN=starter
```

### 2. Middleware de vérification tenant (optionnel)
Pour empêcher l'accès si tenant suspendu

### 3. Suppression des tables SaaS
Ne PAS avoir ces tables dans l'app tenant :
- `tenants`
- `saas_admins`
- `tenant_deployments`
- etc.

Ces tables existent UNIQUEMENT dans Master DB.

### 4. Fichier `.tenant.json` (métadonnées)
```json
{
  "code": "esbtp-abj",
  "name": "ESBTP Abidjan",
  "subdomain": "esbtp-abj",
  "database": {
    "name": "klassci_esbtp_abj",
    "host": "localhost",
    "port": 3306
  },
  "git_branch": "main",
  "plan": "starter",
  "created_at": "2025-10-11T08:00:00Z",
  "status": "active"
}
```

---

## 💰 Estimation des coûts

### Coûts serveur (mensuel)

| Ressource          | Configuration                  | Prix/mois | Provider     |
|--------------------|--------------------------------|-----------|--------------|
| VPS SSD            | 4 vCPU, 8GB RAM, 160GB SSD    | ~15,000 FCFA | OVH/Contabo |
| Domaine            | klassci.com                    | ~3,000 FCFA | Namecheap   |
| SSL                | Let's Encrypt (gratuit)        | 0 FCFA    | -            |
| Email (SMTP)       | 500 emails/jour                | ~5,000 FCFA | Mailgun     |
| Backups externes   | 100GB Dropbox/Google Drive     | ~3,000 FCFA | Dropbox     |
| **TOTAL**          |                                | **~26,000 FCFA** (~40€/mois) ||

### Revenus estimés (mensuel)

**Hypothèse : 10 établissements**

| Plan         | Nbre tenants | Prix/tenant | Total      |
|--------------|--------------|-------------|------------|
| Free         | 2            | 0 FCFA      | 0 FCFA     |
| Starter      | 5            | 25,000 FCFA | 125,000 FCFA|
| Professional | 2            | 50,000 FCFA | 100,000 FCFA|
| Enterprise   | 1            | 100,000 FCFA| 100,000 FCFA|
| **TOTAL**    | **10**       |             | **325,000 FCFA (~495€/mois)** |

**Marge brute** : 325,000 - 26,000 = **299,000 FCFA/mois (~456€)**

---

## ⚠️ Risques & Mitigations

### Risque 1 : Complexité technique élevée
**Probabilité** : Moyenne
**Impact** : Élevé
**Mitigation** :
- Plan détaillé (ce document)
- Tests sur environnement staging
- Déploiement progressif (1 tenant pilot d'abord)

### Risque 2 : Temps de développement sous-estimé
**Probabilité** : Élevée
**Impact** : Moyen
**Mitigation** :
- Buffer de 30% sur estimations
- Prioriser features MVP
- Phase 2 pour features avancées

### Risque 3 : Problèmes de performance avec 10+ tenants
**Probabilité** : Faible
**Impact** : Élevé
**Mitigation** :
- Load testing avant production
- Monitoring continu
- Plan d'upgrade serveur si besoin

### Risque 4 : Perte de données lors migration
**Probabilité** : Faible
**Impact** : Critique
**Mitigation** :
- Backups multiples avant toute opération
- Tests sur copies de BDD
- Procédure de rollback documentée

---

## 📅 Planning détaillé (16 jours)

| Jour | Phase | Tâches | Livrable |
|------|-------|--------|----------|
| 1-2 | Phase 1 | Setup repo Master, migrations, modèles | App Master fonctionnelle (backend) |
| 3-4 | Phase 2 | Commandes Artisan | Provisioning automatique OK |
| 5-7 | Phase 3 | Dashboard Master | Interface web complète |
| 8-9 | Phase 4 | Monitoring | Health checks + alertes |
| 10-12 | Phase 5 | Facturation | Plans + essais + factures |
| 13-14 | Phase 6 | Scripts Bash | Déploiement automatisé |
| 15-16 | Phase 7 | Tests & Docs | Production ready |

---

## ✅ Critères d'acceptation (DoD)

### Critère 1 : Provisioning automatique
- [ ] Commande `tenant:provision` fonctionne
- [ ] Tenant accessible via sous-domaine HTTPS
- [ ] Base de données créée et migrée
- [ ] Email de bienvenue envoyé
- [ ] Visible dans dashboard Master

### Critère 2 : Déploiement centralisé
- [ ] Commande `tenant:deploy` fonctionne
- [ ] Backup automatique avant déploiement
- [ ] Git pull + composer + migrate OK
- [ ] Rollback possible en cas d'erreur
- [ ] Logs visibles dans dashboard

### Critère 3 : Monitoring
- [ ] Health checks toutes les 5 min
- [ ] Dashboard affiche statut de tous les tenants
- [ ] Alertes email si tenant down
- [ ] Métriques de performance disponibles

### Critère 4 : Facturation
- [ ] Plans tarifaires configurables
- [ ] Essai gratuit 30 jours
- [ ] Factures générées automatiquement
- [ ] Email de relance avant expiration

### Critère 5 : Documentation
- [ ] Guide d'installation
- [ ] Guide d'utilisation
- [ ] Documentation API
- [ ] FAQ et troubleshooting

---

## 🚀 Prochaines étapes

### Étape 1 : Validation du plan
**👉 VOUS ÊTES ICI**

**Questions à valider** :
1. ✅ Architecture 2 apps distinctes OK ?
2. ✅ Plan de développement (16 jours) réaliste ?
3. ✅ Budget serveur (~26,000 FCFA/mois) acceptable ?
4. ✅ Tarifs proposés (25k-100k FCFA/mois) OK ?
5. ✅ Priorités des phases OK ou à ajuster ?

### Étape 2 : Création app Master
Une fois validé, je peux :
- Créer structure complète de `klassci-master`
- Générer tous les fichiers (modèles, migrations, commandes, contrôleurs, vues)
- Créer script Bash complet
- Documenter procédure d'installation

### Étape 3 : Adapter app Tenant
- Modifier `KLASSCIv2` pour être "tenant-ready"
- Ajouter variable `TENANT_CODE`
- Supprimer tables SaaS
- Ajouter `.tenant.json`

### Étape 4 : Tests en local
- Tester provisioning sur votre machine locale
- Vérifier déploiement
- Valider dashboard

### Étape 5 : Déploiement production
- Configuration serveur
- Installation Master
- Migration premier tenant pilot
- Monitoring

---

## 📞 Support & Ressources

### Documentation Laravel
- Multi-tenancy : https://laravel.com/docs/10.x/database#multiple-database-connections
- Task Scheduling : https://laravel.com/docs/10.x/scheduling
- Artisan Commands : https://laravel.com/docs/10.x/artisan

### Outils recommandés
- **Server monitoring** : Uptime Kuma (open source)
- **Backups** : Spatie Laravel Backup
- **Analytics** : Plausible (alternative Google Analytics)
- **Error tracking** : Sentry

---

## 🎯 Conclusion

Ce plan transforme Klassci en **véritable SaaS multi-tenant** avec :

✅ **Provisioning automatique** : Nouveau tenant en 2 minutes
✅ **Isolation complète** : BDD séparées, sous-domaines uniques
✅ **Déploiement centralisé** : Mise à jour tous les tenants en 1 clic
✅ **Monitoring temps réel** : Dashboard avec santé de tous les établissements
✅ **Facturation automatisée** : Plans, essais, factures, relances

**Investissement** : 16 jours de développement + 26,000 FCFA/mois serveur
**ROI** : Dès 2 tenants payants (50,000 FCFA/mois)

---

## ❓ Questions pour validation

1. **Architecture** : OK avec 2 apps distinctes (Master + Tenant) ?
2. **Durée** : 16 jours réaliste ou besoin de plus de temps ?
3. **Budget** : OK avec ~26,000 FCFA/mois pour le serveur ?
4. **Tarifs** : Les prix (25k-100k FCFA/mois) sont-ils cohérents avec le marché ivoirien ?
5. **Priorités** : Quelles phases sont prioritaires ? (je recommande 1-3-6 pour MVP)
6. **Features** : Des fonctionnalités manquantes à ajouter ?

**👉 Validez ce plan et je commence la création de l'app Master ! 🚀**
