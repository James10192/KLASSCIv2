# Architecture SaaS Klassci - Guide Complet

## Vue d'ensemble

L'architecture SaaS de Klassci repose sur **2 applications distinctes** :

```
┌─────────────────────────────────────────────────────────────────┐
│                     SERVEUR PRODUCTION                          │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  1. APPLICATION MASTER (Panneau Admin SaaS)               │ │
│  │                                                           │ │
│  │  Chemin : /var/www/klassci-master                        │ │
│  │  URL    : https://admin.klassci.com                      │ │
│  │  DB     : klassci_master (UNIQUE)                        │ │
│  │                                                           │ │
│  │  Contenu :                                                │ │
│  │  ├─ Table tenants (liste établissements)                 │ │
│  │  ├─ Dashboard monitoring global                          │ │
│  │  ├─ Provisioning automatique                             │ │
│  │  ├─ Déploiement centralisé                               │ │
│  │  ├─ Facturation & abonnements                            │ │
│  │  └─ Statistiques agrégées                                │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  2. APPLICATIONS TENANTS (Établissements)                │ │
│  │                                                           │ │
│  │  ┌─────────────────────────────────────────────────────┐ │ │
│  │  │ Tenant 1 : ESBTP Abidjan                           │ │ │
│  │  │ Chemin : /var/www/tenants/esbtp-abj                │ │ │
│  │  │ URL    : https://esbtp-abj.klassci.com             │ │ │
│  │  │ DB     : klassci_esbtp_abj                         │ │ │
│  │  └─────────────────────────────────────────────────────┘ │ │
│  │                                                           │ │
│  │  ┌─────────────────────────────────────────────────────┐ │ │
│  │  │ Tenant 2 : Lycée Koumassi                          │ │ │
│  │  │ Chemin : /var/www/tenants/lycee-koumassi           │ │ │
│  │  │ URL    : https://lycee-koumassi.klassci.com        │ │ │
│  │  │ DB     : klassci_lycee_koumassi                    │ │ │
│  │  └─────────────────────────────────────────────────────┘ │ │
│  │                                                           │ │
│  │  ┌─────────────────────────────────────────────────────┐ │ │
│  │  │ Tenant 3 : Collège Marcory                         │ │ │
│  │  │ Chemin : /var/www/tenants/college-marcory          │ │ │
│  │  │ URL    : https://college-marcory.klassci.com       │ │ │
│  │  │ DB     : klassci_college_marcory                   │ │ │
│  │  └─────────────────────────────────────────────────────┘ │ │
│  │                                                           │ │
│  │  ... (autant de tenants que nécessaire)                  │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. Application Master (Panneau Admin SaaS)

### Localisation
- **Repository Git** : `klassci-master` (nouveau repo séparé)
- **Chemin serveur** : `/var/www/klassci-master`
- **URL** : `https://admin.klassci.com`
- **Base de données** : `klassci_master`

### Responsabilités

#### A. Gestion des Tenants
- **Créer un nouveau tenant** : Provisionner établissement + DB + sous-domaine
- **Lister tous les tenants** : Vue d'ensemble avec statuts
- **Modifier un tenant** : Changer plan, limites, configuration
- **Suspendre/Réactiver** : Gérer les impayés ou violations
- **Supprimer** : Archivage + suppression données

#### B. Déploiement Centralisé
- **Déployer une mise à jour** : Push vers tous les tenants ou un seul
- **Gérer les branches Git** : Chaque tenant peut avoir sa branche (main, staging, custom)
- **Rollback** : Revenir à une version précédente
- **Historique des déploiements** : Logs et statuts

#### C. Monitoring & Santé
- **Health checks automatiques** : HTTP, DB, Storage, Queue
- **Alertes en temps réel** : Email/SMS si un tenant est down
- **Statistiques d'usage** :
  - Nombre d'étudiants par tenant
  - Stockage utilisé
  - Trafic HTTP
  - Requêtes DB
- **Logs centralisés** : Agréger les logs de tous les tenants

#### D. Facturation & Abonnements
- **Plans tarifaires** : Free, Starter, Pro, Enterprise
- **Facturation automatique** : Générer factures mensuelles
- **Gestion des essais** : 30 jours gratuits puis facturation
- **Paiements** : Intégration Mobile Money, cartes bancaires
- **Relances** : Email automatique pour impayés

#### E. Support & Analytics
- **Tickets support** : Chaque tenant peut créer des tickets
- **Analytics agrégées** :
  - Nombre total d'étudiants sur la plateforme
  - Revenus mensuels récurrents (MRR)
  - Taux de rétention
  - Taux de churn

### Structure de la base de données `klassci_master`

```sql
-- Tenants (établissements)
tenants (
    id, code, name, subdomain, database_name,
    database_credentials, git_branch, status, plan,
    monthly_fee, subscription_dates, usage_stats, ...
)

-- Déploiements
tenant_deployments (
    id, tenant_id, git_commit, status, started_at,
    completed_at, error_message, ...
)

-- Backups
tenant_backups (
    id, tenant_id, type, backup_path, size,
    expires_at, ...
)

-- Health checks
tenant_health_checks (
    id, tenant_id, check_type, status, response_time,
    checked_at, ...
)

-- Features activées
tenant_features (
    id, tenant_id, feature_key, is_enabled, config
)

-- Logs d'activité
tenant_activity_logs (
    id, tenant_id, action, description, performed_by,
    performed_at, ...
)

-- Admins SaaS (vous + support)
saas_admins (
    id, name, email, password, role, is_active
)

-- Tickets support
support_tickets (
    id, tenant_id, subject, message, status,
    priority, assigned_to, ...
)

-- Factures
invoices (
    id, tenant_id, amount, period, status,
    paid_at, ...
)
```

### Interface (Dashboard)

```
https://admin.klassci.com
├─ /login (authentification admin SaaS)
├─ /dashboard (vue d'ensemble)
│   ├─ KPI : Total tenants, MRR, Étudiants, Uptime
│   ├─ Graphiques : Croissance, Santé des tenants
│   └─ Alertes récentes
├─ /tenants (liste des établissements)
│   ├─ /tenants/create (nouveau tenant)
│   ├─ /tenants/{code} (détails tenant)
│   ├─ /tenants/{code}/edit (modifier)
│   ├─ /tenants/{code}/deploy (déployer mise à jour)
│   ├─ /tenants/{code}/backup (créer backup)
│   ├─ /tenants/{code}/suspend (suspendre)
│   └─ /tenants/{code}/logs (voir logs)
├─ /deployments (historique déploiements)
├─ /monitoring (health checks)
├─ /billing (facturation)
│   ├─ /billing/invoices
│   ├─ /billing/plans
│   └─ /billing/payments
├─ /support (tickets)
└─ /settings (configuration globale)
```

---

## 2. Application Tenant (Établissement)

### Localisation
- **Repository Git** : `KLASSCIv2` (votre app actuelle)
- **Chemin serveur** : `/var/www/tenants/{tenant_code}`
- **URL** : `https://{subdomain}.klassci.com`
- **Base de données** : `klassci_{tenant_code}`

### Responsabilités

**C'est l'application métier actuelle** : gestion étudiants, inscriptions, notes, bulletins, paiements, absences, etc.

**Modifications nécessaires** :
- Ajouter variable d'environnement `TENANT_CODE` dans `.env`
- Supprimer les tables `tenants`, `saas_admins`, etc. (elles vont dans Master)
- Conserver uniquement les tables métier

### Structure du `.env` (généré par Master)

```env
APP_NAME="ESBTP Abidjan"
APP_URL=https://esbtp-abj.klassci.com

DB_DATABASE=klassci_esbtp_abj
DB_USERNAME=klassci_esbtp_abj
DB_PASSWORD=<généré_automatiquement>

# Informations tenant (injectées par Master)
TENANT_CODE=esbtp-abj
TENANT_NAME="ESBTP Abidjan"
TENANT_PLAN=starter
```

---

## 3. Workflow de déploiement

### Étape 1 : Configuration initiale (une seule fois)

```bash
# Sur votre serveur de production

# 1. Cloner l'application Master
cd /var/www
git clone https://github.com/your-org/klassci-master.git
cd klassci-master
composer install --no-dev
cp .env.example .env
php artisan key:generate

# 2. Configurer la DB master
mysql -u root -p
CREATE DATABASE klassci_master;
exit

# 3. Migrer la DB master
php artisan migrate

# 4. Créer un admin SaaS
php artisan saas:create-admin

# 5. Configurer Nginx pour admin.klassci.com
# (virtual host pointant vers /var/www/klassci-master/public)

# 6. Obtenir certificat SSL
sudo certbot --nginx -d admin.klassci.com
```

### Étape 2 : Provisionner un nouveau tenant (depuis Master)

```bash
# Option 1 : Via l'interface web
https://admin.klassci.com/tenants/create

# Option 2 : Via commande Artisan
cd /var/www/klassci-master
php artisan tenant:provision \
    --code=esbtp-abj \
    --name="ESBTP Abidjan" \
    --subdomain=esbtp-abj \
    --branch=main \
    --plan=starter \
    --admin-email=admin@esbtp-abidjan.ci
```

**Ce que fait cette commande** :
1. ✅ Créer entrée dans table `tenants`
2. ✅ Créer base de données `klassci_esbtp_abj`
3. ✅ Créer utilisateur DB avec mot de passe sécurisé
4. ✅ Cloner le repo `KLASSCIv2` dans `/var/www/tenants/esbtp-abj`
5. ✅ Générer fichier `.env` avec credentials uniques
6. ✅ Exécuter `composer install`
7. ✅ Exécuter `php artisan migrate`
8. ✅ Exécuter `php artisan db:seed` (créer admin par défaut)
9. ✅ Configurer virtual host Nginx pour `esbtp-abj.klassci.com`
10. ✅ Obtenir certificat SSL Let's Encrypt
11. ✅ Envoyer email de bienvenue avec credentials

### Étape 3 : Déployer une mise à jour

```bash
# Option 1 : Depuis Master (web interface)
https://admin.klassci.com/tenants/esbtp-abj/deploy

# Option 2 : Commande Artisan
cd /var/www/klassci-master
php artisan tenant:deploy esbtp-abj

# Option 3 : Déployer TOUS les tenants
php artisan tenant:deploy --all
```

**Ce que fait cette commande** :
1. ✅ Créer backup automatique (DB + fichiers)
2. ✅ Mettre le tenant en mode maintenance (`php artisan down`)
3. ✅ `git pull origin main` (ou branche configurée)
4. ✅ `composer install --no-dev`
5. ✅ `php artisan migrate --force`
6. ✅ `php artisan cache:clear && config:cache`
7. ✅ Restaurer permissions (chown www-data)
8. ✅ Désactiver mode maintenance (`php artisan up`)
9. ✅ Enregistrer dans `tenant_deployments`
10. ✅ Envoyer notification (email/Slack)

---

## 4. Schéma de communication

```
┌─────────────────────────────────────────────────────────────┐
│                    DÉVELOPPEMENT                            │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Vous (développeur)                                  │  │
│  │  - Modifiez le code de KLASSCIv2 (app tenant)       │  │
│  │  - Testez localement                                 │  │
│  │  - Commit & Push sur GitHub                          │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                  │
│                          │ git push                         │
│                          ▼                                  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                     GITHUB                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Repository : KLASSCIv2 (app tenant)                 │  │
│  │  Branch : main                                       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          │
                          │ webhook (optionnel)
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              SERVEUR PRODUCTION                             │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Application Master                                  │  │
│  │  https://admin.klassci.com                           │  │
│  │                                                      │  │
│  │  Vous vous connectez ici et cliquez sur :           │  │
│  │  "Déployer mise à jour vers tous les tenants"       │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                  │
│                          │ déploiement automatique          │
│                          ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Tenant 1 : esbtp-abj.klassci.com                   │  │
│  │  - git pull origin main                              │  │
│  │  - composer install                                  │  │
│  │  - php artisan migrate                               │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Tenant 2 : lycee-koumassi.klassci.com              │  │
│  │  - git pull origin main                              │  │
│  │  - composer install                                  │  │
│  │  - php artisan migrate                               │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Tenant 3 : college-marcory.klassci.com             │  │
│  │  - git pull origin main                              │  │
│  │  - composer install                                  │  │
│  │  - php artisan migrate                               │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Réponse à votre question

> "Comment ce que tu fais dans l'application pourra servir tous les établissements ?"

### Réponse :

**Les fichiers que j'ai créés (`Tenant.php`, `TenantProvision.php`, etc.) ne vont PAS dans `KLASSCIv2` actuel.**

Ils vont dans une **nouvelle application séparée** (`klassci-master`) que vous allez créer.

### Voici la structure correcte :

```
📁 Votre machine locale
├─ 📁 KLASSCIv2/              ← Application actuelle (devient app TENANT)
│   ├─ app/Models/ESBTPEtudiant.php
│   ├─ app/Models/ESBTPInscription.php
│   ├─ app/Models/ESBTPPaiement.php
│   └─ ... (toute votre logique métier actuelle)
│
└─ 📁 klassci-master/        ← NOUVELLE application (app MASTER SaaS)
    ├─ app/Models/Tenant.php             ← Ces fichiers vont ICI
    ├─ app/Models/TenantDeployment.php
    ├─ app/Models/SaasAdmin.php
    ├─ app/Console/Commands/TenantProvision.php
    ├─ resources/views/tenants/index.blade.php
    └─ deploy-saas.sh
```

### Sur le serveur de production :

```
📁 /var/www/
├─ 📁 klassci-master/         ← Application Master (1 seule instance)
│   └─ Gère TOUS les établissements
│
└─ 📁 tenants/                ← Applications Tenant (N instances)
    ├─ 📁 esbtp-abj/          ← Clone de KLASSCIv2
    ├─ 📁 lycee-koumassi/     ← Clone de KLASSCIv2
    └─ 📁 college-marcory/    ← Clone de KLASSCIv2
```

---

## 6. Plan d'action pour vous

### Option A : Architecture recommandée (2 apps séparées)

**Avantages** : ✅ Propre, scalable, maintenable, sécurisé
**Inconvénients** : ❌ Nécessite créer nouvelle app Master

1. Créer nouveau repo Git `klassci-master`
2. Y mettre les modèles Tenant, SaasAdmin, commandes, dashboard
3. Configurer serveur avec 2 apps distinctes
4. Provisionner tenants depuis Master

### Option B : Architecture simplifiée (1 app multi-tenant)

**Avantages** : ✅ Plus simple, pas besoin de 2 apps
**Inconvénients** : ❌ Moins scalable, mélange admin SaaS et métier

1. Garder une seule app `KLASSCIv2`
2. Ajouter middleware de détection du tenant (basé sur sous-domaine)
3. Changer dynamiquement la connexion DB selon le tenant
4. Section admin SaaS accessible uniquement à vous

**Je recommande l'Option A pour un vrai SaaS professionnel.**

---

## 7. Voulez-vous que je continue ?

Je peux :

1. **Créer la structure complète de `klassci-master`** (app séparée)
2. **Adapter `KLASSCIv2`** pour être "tenant-ready"
3. **Créer les scripts de déploiement automatisés**
4. **Documenter le processus complet**

Dites-moi quelle option vous préférez (A ou B) et je continue ! 🚀
