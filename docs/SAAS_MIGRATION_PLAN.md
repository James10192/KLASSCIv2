# Plan de Migration SaaS Klassci - Version Finale

## 📋 Contexte Actuel (Octobre 2025)

### Tenants existants

Vous avez actuellement **3 tenants** sur le serveur :

1. **ESBTP Abidjan** (`esbtp-abidjan.klassci.com`)
2. **ESBTP Yakro** (`esbtp-yakro.klassci.com`)
3. **Test/Présentation** (`presentation.klassci.com`)

### Système de paywall actuel

✅ **Déjà implémenté dans KLASSCIv2** :
- Middleware `PaywallMiddleware` qui bloque l'accès
- Contrôleur `ESBTPPaywallConfigController` pour configuration
- Interface `/esbtp/paywall-config` pour gérer :
  - Activation/désactivation du paywall
  - Date d'expiration abonnement
  - Limite utilisateurs (enseignants, coordinateurs, secrétaires)
  - Limite inscriptions par année
  - Plans (Essentiel, Pro, Elite)
  - Codes d'urgence temporaires

**Problème actuel** : Cette configuration est **locale à chaque tenant**. Vous devez aller sur chaque sous-domaine pour configurer son paywall individuellement.

### Processus de déploiement actuel

**Manuel et répétitif** :
1. SSH sur le serveur
2. Pull sur chaque dossier (`esbtp-abidjan`, `esbtp-yakro`, `presentation`)
3. Composer install sur chacun
4. Migrate sur chacun
5. Cache clear sur chacun

**Temps estimé** : 15-20 minutes par tenant × 3 = 45-60 minutes

---

## 🎯 Objectif de la Migration

**Créer un système centralisé où VOUS pourrez** :

1. ✅ **Gérer tous les tenants depuis un seul endroit** (`admin.klassci.com`)
2. ✅ **Configurer les plans et limites centralement** (plus de paywall local)
3. ✅ **Déployer une mise à jour sur tous les tenants en 1 clic**
4. ✅ **Ajouter un nouveau tenant en 2 minutes** (formulaire simple)
5. ✅ **Monitorer la santé de tous les tenants** (dashboard en temps réel)
6. ✅ **Automatiser les backups quotidiens**
7. ✅ **Gérer la facturation centralement**

---

## 🏗️ Architecture Finale

```
SERVEUR PRODUCTION
│
├─ /var/www/klassci-master/              ← Application Master (NOUVELLE)
│  │  URL: https://admin.klassci.com
│  │  DB: klassci_master
│  │  Rôle: Gérer TOUS les établissements + Paywall centralisé
│  │
│  ├─ Dashboard SaaS
│  │  ├─ Liste des tenants (esbtp-abidjan, esbtp-yakro, presentation)
│  │  ├─ Configuration paywall centralisée
│  │  ├─ Déploiement en 1 clic
│  │  ├─ Monitoring (HTTP, DB, Storage)
│  │  ├─ Facturation & abonnements
│  │  └─ Logs centralisés
│  │
│  └─ Base de données klassci_master
│     ├─ tenants (liste établissements + config paywall)
│     ├─ tenant_deployments
│     ├─ tenant_backups
│     ├─ tenant_health_checks
│     ├─ saas_admins (vous + support)
│     └─ invoices
│
├─ /var/www/tenants/
│  │
│  ├─ esbtp-abidjan/                     ← Tenant 1 (EXISTANT)
│  │  │  URL: https://esbtp-abidjan.klassci.com
│  │  │  DB: klassci_esbtp_abidjan
│  │  │  Clone de KLASSCIv2 (branche main)
│  │  │
│  │  ├─ .env
│  │  │  TENANT_CODE=esbtp-abidjan
│  │  │  TENANT_NAME="ESBTP Abidjan"
│  │  │  DB_DATABASE=klassci_esbtp_abidjan
│  │  │
│  │  └─ .tenant.json                    ← Métadonnées (NOUVEAU)
│  │     {
│  │       "code": "esbtp-abidjan",
│  │       "name": "ESBTP Abidjan",
│  │       "subdomain": "esbtp-abidjan",
│  │       "database_name": "klassci_esbtp_abidjan",
│  │       "git_branch": "main",
│  │       "plan": "pro",
│  │       "subscription_end": "2026-10-11",
│  │       "max_users": 30,
│  │       "max_inscriptions_per_year": 3000,
│  │       "created_at": "2024-01-15T08:00:00Z"
│  │     }
│  │
│  ├─ esbtp-yakro/                       ← Tenant 2 (EXISTANT)
│  │  │  URL: https://esbtp-yakro.klassci.com
│  │  │  DB: klassci_esbtp_yakro
│  │  │  Clone de KLASSCIv2 (branche main)
│  │  │
│  │  ├─ .env
│  │  └─ .tenant.json
│  │
│  └─ presentation/                      ← Tenant 3 (EXISTANT - Test)
│     │  URL: https://presentation.klassci.com
│     │  DB: klassci_presentation
│     │  Clone de KLASSCIv2 (branche main)
│     │
│     ├─ .env
│     └─ .tenant.json
│
└─ /var/backups/klassci/
   ├─ esbtp-abidjan/
   │  ├─ 20251011_020000/ (backup quotidien)
   │  └─ 20251010_020000/
   ├─ esbtp-yakro/
   └─ presentation/
```

---

## 📊 Migration du Paywall : Avant → Après

### ❌ Avant (Système actuel - Local)

**Configuration par tenant** :
- ESBTP Abidjan : `/esbtp/paywall-config` sur `esbtp-abidjan.klassci.com`
- ESBTP Yakro : `/esbtp/paywall-config` sur `esbtp-yakro.klassci.com`
- Presentation : `/esbtp/paywall-config` sur `presentation.klassci.com`

**Problèmes** :
- Vous devez vous connecter 3 fois (un par tenant)
- Configuration stockée dans `esbtp_system_settings` de chaque DB tenant
- Aucune vue d'ensemble
- Risque de configuration incohérente

### ✅ Après (Système SaaS - Centralisé)

**Configuration centralisée** :
- Tout se gère depuis : `https://admin.klassci.com/saas/tenants`
- Un seul login (vous = admin SaaS)
- Vue d'ensemble de tous les tenants en une page
- Configuration cohérente

**Interface Master** :

```
┌─────────────────────────────────────────────────────────────────┐
│  KLASSCI MASTER - Dashboard SaaS                                │
│  admin.klassci.com/saas/dashboard                               │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────────────┐
│  KPI GLOBAUX             │                                      │
├──────────────────────────┼──────────────────────────────────────┤
│  Total Tenants: 3        │  Total Étudiants: 487                │
│  Actifs: 3               │  Total Personnel: 28                 │
│  Suspendus: 0            │  MRR: 3,600,000 FCFA (~5,488€)       │
└──────────────────────────┴──────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  LISTE DES TENANTS                                              │
├────────┬─────────────────┬──────────────┬────────┬──────────────┤
│ Code   │ Nom             │ URL          │ Status │ Plan         │
├────────┼─────────────────┼──────────────┼────────┼──────────────┤
│ esbtp  │ ESBTP Abidjan   │ esbtp-abj... │ ✅ UP   │ Pro          │
│ -abj   │                 │              │        │ 30 users     │
│        │                 │              │        │ 180/3000 ins │
│        │                 │              │        │ Expire:30j   │
├────────┼─────────────────┼──────────────┼────────┼──────────────┤
│ esbtp  │ ESBTP Yakro     │ esbtp-yak... │ ✅ UP   │ Essentiel    │
│ -yakro │                 │              │        │ 20 users     │
│        │                 │              │        │ 120/700 ins  │
│        │                 │              │        │ Expire:7j ⚠️  │
├────────┼─────────────────┼──────────────┼────────┼──────────────┤
│ pres   │ Test Présent.   │ presentation │ ✅ UP   │ Free         │
│        │                 │              │        │ 5 users      │
│        │                 │              │        │ 25/50 ins    │
│        │                 │              │        │ Illimité     │
└────────┴─────────────────┴──────────────┴────────┴──────────────┘

Actions disponibles:
├─ [Modifier Plan] - Changer plan/limites d'un tenant
├─ [Déployer Tout] - git pull + migrate sur tous les tenants
├─ [Déployer Un] - git pull + migrate sur un tenant spécifique
├─ [Backup Manuel] - Créer backup maintenant
├─ [Voir Logs] - Logs d'activité d'un tenant
└─ [Ajouter Tenant] - Provisionner nouveau tenant
```

**Clic sur "ESBTP Abidjan" →** Page détails avec onglets :

```
┌─────────────────────────────────────────────────────────────────┐
│  ESBTP ABIDJAN - Détails Tenant                                 │
│  https://admin.klassci.com/saas/tenants/esbtp-abidjan           │
└─────────────────────────────────────────────────────────────────┘

[Onglet: Général] [Plan & Limites] [Déploiements] [Backups] [Logs]

┌─────────────────────────────────────────────────────────────────┐
│  📋 PLAN & LIMITES                                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Plan actuel: Pro (2,400,000 FCFA/an)                          │
│  Abonnement: Actif jusqu'au 11/10/2026 (30 jours restants)     │
│                                                                 │
│  ┌─────────────────┬──────────┬──────────┬───────────────┐     │
│  │ Ressource       │ Utilisé  │ Limite   │ État          │     │
│  ├─────────────────┼──────────┼──────────┼───────────────┤     │
│  │ Utilisateurs    │ 12       │ 30       │ ✅ 40%        │     │
│  │ Inscriptions    │ 180      │ 3000     │ ✅ 6%         │     │
│  │ Stockage        │ 512 MB   │ 5120 MB  │ ✅ 10%        │     │
│  └─────────────────┴──────────┴──────────┴───────────────┘     │
│                                                                 │
│  [Changer de Plan]  [Prolonger Abonnement]  [Générer Code]     │
│                                                                 │
│  Formulaire de modification:                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Plan: [Essentiel ▼] [Pro ▼] [Elite ▼] [Custom ▼]       │   │
│  │                                                         │   │
│  │ Max Utilisateurs: [30    ]                             │   │
│  │ Max Inscriptions: [3000  ]                             │   │
│  │ Max Stockage (MB): [5120  ]                            │   │
│  │                                                         │   │
│  │ Date expiration: [2026-10-11]                          │   │
│  │                                                         │   │
│  │ [✅ Sauvegarder]  [❌ Annuler]                          │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Stratégie de Migration (Zero Downtime)

### Phase 1 : Préparation (Jour 1)

**1.1 Créer l'application Master**

```bash
# Sur votre machine locale
cd ~/workspace
mkdir klassci-master && cd klassci-master

# Créer nouvelle app Laravel
composer create-project laravel/laravel . --prefer-dist

# Init Git
git init
git remote add origin https://github.com/your-org/klassci-master.git
```

**1.2 Migrer les tenants existants vers Master DB**

Script pour extraire la config actuelle de chaque tenant :

```bash
# Sur le serveur (SSH)
cd /var/www/tenants/esbtp-abidjan

# Extraire config paywall
php artisan tinker

>>> $config = [
...   'code' => 'esbtp-abidjan',
...   'name' => 'ESBTP Abidjan',
...   'subdomain' => 'esbtp-abidjan',
...   'database_name' => env('DB_DATABASE'),
...   'max_users' => \App\Models\ESBTPSystemSetting::getValue('paywall_max_users', 50),
...   'max_inscriptions_per_year' => \App\Models\ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
...   'subscription_end' => \App\Models\ESBTPSystemSetting::getValue('subscription_end_date', null),
...   'plan_name' => \App\Models\ESBTPSystemSetting::getValue('paywall_plan_name', 'Plan Standard'),
...   'plan_price' => \App\Models\ESBTPSystemSetting::getValue('paywall_plan_price', 0),
... ];
>>> print_r($config);
>>> exit
```

Répéter pour `esbtp-yakro` et `presentation`.

**1.3 Créer fichier `.tenant.json` pour chaque tenant**

```bash
# Pour esbtp-abidjan
cat > /var/www/tenants/esbtp-abidjan/.tenant.json <<EOF
{
  "code": "esbtp-abidjan",
  "name": "ESBTP Abidjan",
  "subdomain": "esbtp-abidjan",
  "database_name": "klassci_esbtp_abidjan",
  "database_host": "localhost",
  "database_port": 3306,
  "git_branch": "main",
  "plan": "pro",
  "monthly_fee": 200000,
  "subscription_end": "2026-10-11",
  "max_users": 30,
  "max_staff": 30,
  "max_inscriptions_per_year": 3000,
  "max_storage_mb": 5120,
  "status": "active",
  "created_at": "2024-01-15T08:00:00Z"
}
EOF

# Pour esbtp-yakro
cat > /var/www/tenants/esbtp-yakro/.tenant.json <<EOF
{
  "code": "esbtp-yakro",
  "name": "ESBTP Yakro",
  "subdomain": "esbtp-yakro",
  "database_name": "klassci_esbtp_yakro",
  "database_host": "localhost",
  "database_port": 3306,
  "git_branch": "main",
  "plan": "essentiel",
  "monthly_fee": 100000,
  "subscription_end": "2025-10-18",
  "max_users": 20,
  "max_staff": 20,
  "max_inscriptions_per_year": 700,
  "max_storage_mb": 2048,
  "status": "active",
  "created_at": "2024-03-20T10:00:00Z"
}
EOF

# Pour presentation
cat > /var/www/tenants/presentation/.tenant.json <<EOF
{
  "code": "presentation",
  "name": "Test Présentation",
  "subdomain": "presentation",
  "database_name": "klassci_presentation",
  "database_host": "localhost",
  "database_port": 3306,
  "git_branch": "main",
  "plan": "free",
  "monthly_fee": 0,
  "subscription_end": null,
  "max_users": 5,
  "max_staff": 5,
  "max_inscriptions_per_year": 50,
  "max_storage_mb": 512,
  "status": "active",
  "is_trial": false,
  "created_at": "2024-08-01T14:00:00Z"
}
EOF
```

### Phase 2 : Déploiement Master (Jour 2-3)

**2.1 Installer Master sur le serveur**

```bash
# SSH sur le serveur
cd /var/www
git clone https://github.com/your-org/klassci-master.git
cd klassci-master

# Installer dépendances
composer install --no-dev --optimize-autoloader

# Configuration
cp .env.example .env
nano .env  # Configurer DB_DATABASE=klassci_master
php artisan key:generate

# Créer DB master
mysql -u root -p
> CREATE DATABASE klassci_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> exit

# Migrer
php artisan migrate

# Créer admin SaaS
php artisan saas:create-admin --name="Votre Nom" --email="votre@email.com"

# Permissions
sudo chown -R www-data:www-data /var/www/klassci-master
sudo chmod -R 755 /var/www/klassci-master
sudo chmod -R 775 /var/www/klassci-master/storage
sudo chmod -R 775 /var/www/klassci-master/bootstrap/cache
```

**2.2 Configurer Nginx pour admin.klassci.com**

```nginx
# /etc/nginx/sites-available/admin.klassci.com
server {
    listen 80;
    listen [::]:80;
    server_name admin.klassci.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name admin.klassci.com;

    root /var/www/klassci-master/public;
    index index.php index.html;

    # SSL (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/admin.klassci.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.klassci.com/privkey.pem;

    # Logs
    access_log /var/log/nginx/admin-klassci-access.log;
    error_log /var/log/nginx/admin-klassci-error.log;

    # PHP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Activer le site
sudo ln -s /etc/nginx/sites-available/admin.klassci.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Certificat SSL
sudo certbot --nginx -d admin.klassci.com
```

**2.3 Importer les tenants existants dans Master DB**

```bash
cd /var/www/klassci-master

php artisan tinker

>>> use App\Models\Tenant;
>>> use Carbon\Carbon;

>>> # Tenant 1: ESBTP Abidjan
>>> Tenant::create([
...   'code' => 'esbtp-abidjan',
...   'name' => 'ESBTP Abidjan',
...   'subdomain' => 'esbtp-abidjan',
...   'database_name' => 'klassci_esbtp_abidjan',
...   'database_host' => 'localhost',
...   'database_port' => 3306,
...   'database_username' => 'klassci_esbtp_abidjan',
...   'database_password' => 'REMPLACER_PAR_MOT_DE_PASSE_RÉEL',
...   'git_branch' => 'main',
...   'status' => 'active',
...   'plan' => 'professional',
...   'monthly_fee' => 200000,
...   'subscription_start_date' => Carbon::parse('2024-01-15'),
...   'subscription_end_date' => Carbon::parse('2026-10-11'),
...   'max_users' => 30,
...   'max_staff' => 30,
...   'max_students' => 3000,
...   'max_inscriptions_per_year' => 3000,
...   'max_storage_mb' => 5120,
... ]);

>>> # Tenant 2: ESBTP Yakro
>>> Tenant::create([
...   'code' => 'esbtp-yakro',
...   'name' => 'ESBTP Yakro',
...   'subdomain' => 'esbtp-yakro',
...   'database_name' => 'klassci_esbtp_yakro',
...   'database_host' => 'localhost',
...   'database_port' => 3306,
...   'database_username' => 'klassci_esbtp_yakro',
...   'database_password' => 'REMPLACER_PAR_MOT_DE_PASSE_RÉEL',
...   'git_branch' => 'main',
...   'status' => 'active',
...   'plan' => 'starter',
...   'monthly_fee' => 100000,
...   'subscription_start_date' => Carbon::parse('2024-03-20'),
...   'subscription_end_date' => Carbon::parse('2025-10-18'),
...   'max_users' => 20,
...   'max_staff' => 20,
...   'max_students' => 700,
...   'max_inscriptions_per_year' => 700,
...   'max_storage_mb' => 2048,
... ]);

>>> # Tenant 3: Presentation (Test)
>>> Tenant::create([
...   'code' => 'presentation',
...   'name' => 'Test Présentation',
...   'subdomain' => 'presentation',
...   'database_name' => 'klassci_presentation',
...   'database_host' => 'localhost',
...   'database_port' => 3306,
...   'database_username' => 'klassci_presentation',
...   'database_password' => 'REMPLACER_PAR_MOT_DE_PASSE_RÉEL',
...   'git_branch' => 'main',
...   'status' => 'active',
...   'plan' => 'free',
...   'monthly_fee' => 0,
...   'subscription_start_date' => Carbon::parse('2024-08-01'),
...   'subscription_end_date' => null,
...   'max_users' => 5,
...   'max_staff' => 5,
...   'max_students' => 50,
...   'max_inscriptions_per_year' => 50,
...   'max_storage_mb' => 512,
... ]);

>>> exit
```

### Phase 3 : Adapter KLASSCIv2 (Jour 4)

**3.1 Middleware Paywall : Lecture depuis Master DB**

Modifier `/var/www/tenants/*/app/Http/Middleware/PaywallMiddleware.php` :

```php
protected function checkPaywallStatus()
{
    // NOUVELLE LOGIQUE : Lire config depuis Master DB au lieu de local
    $tenantCode = env('TENANT_CODE');

    if (!$tenantCode) {
        // Fallback : Utiliser ancien système si pas de TENANT_CODE
        return $this->checkPaywallStatusLocal();
    }

    try {
        // Connexion à Master DB
        $masterDb = DB::connection('master');

        $tenant = $masterDb->table('tenants')
            ->where('code', $tenantCode)
            ->first();

        if (!$tenant) {
            // Tenant non trouvé → Bloquer par sécurité
            return [
                'is_blocked' => true,
                'reasons' => ['Configuration tenant introuvable'],
                'warnings' => [],
            ];
        }

        // Utiliser config depuis Master
        $config = [
            'subscription_end' => $tenant->subscription_end_date,
            'max_users' => $tenant->max_users,
            'max_inscriptions_per_year' => $tenant->max_inscriptions_per_year,
        ];

        // Logique de vérification (inchangée)
        // ...
    } catch (\Exception $e) {
        \Log::error("Erreur lecture config paywall depuis Master: " . $e->getMessage());
        // Fallback : Ancien système
        return $this->checkPaywallStatusLocal();
    }
}
```

**3.2 Ajouter connexion Master DB dans config/database.php**

```php
'connections' => [
    // Connexion tenant locale (inchangée)
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_DATABASE', 'forge'),
        // ...
    ],

    // NOUVELLE connexion Master
    'master' => [
        'driver' => 'mysql',
        'host' => env('MASTER_DB_HOST', 'localhost'),
        'port' => env('MASTER_DB_PORT', 3306),
        'database' => env('MASTER_DB_DATABASE', 'klassci_master'),
        'username' => env('MASTER_DB_USERNAME', 'root'),
        'password' => env('MASTER_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
],
```

**3.3 Ajouter variables Master DB dans .env de chaque tenant**

```bash
# /var/www/tenants/esbtp-abidjan/.env
# ... existing vars ...

# Master DB Connection (pour lire config paywall)
MASTER_DB_HOST=localhost
MASTER_DB_PORT=3306
MASTER_DB_DATABASE=klassci_master
MASTER_DB_USERNAME=klassci_master_readonly
MASTER_DB_PASSWORD=GÉNÉRER_MOT_DE_PASSE_SÉCURISÉ

# Tenant Info
TENANT_CODE=esbtp-abidjan
TENANT_NAME="ESBTP Abidjan"
```

**3.4 Créer utilisateur MySQL readonly pour Master DB**

```sql
-- Sur le serveur MySQL
CREATE USER 'klassci_master_readonly'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_SÉCURISÉ';
GRANT SELECT ON klassci_master.tenants TO 'klassci_master_readonly'@'localhost';
GRANT SELECT ON klassci_master.tenant_features TO 'klassci_master_readonly'@'localhost';
FLUSH PRIVILEGES;
```

**3.5 Désactiver l'ancien paywall-config local**

```php
// /var/www/tenants/*/app/Http/Controllers/ESBTPPaywallConfigController.php

public function index()
{
    // DÉSACTIVER pour les tenants (seulement accessible depuis Master maintenant)
    abort(403, 'La configuration du paywall se fait désormais depuis le panneau admin SaaS à https://admin.klassci.com');
}
```

### Phase 4 : Tests (Jour 5)

**4.1 Test déploiement automatique**

```bash
# Depuis Master
cd /var/www/klassci-master
php artisan tenant:deploy esbtp-abidjan

# Vérifier logs
tail -f storage/logs/laravel.log
```

**4.2 Test modification plan**

```
1. Aller sur https://admin.klassci.com/saas/tenants/esbtp-abidjan
2. Cliquer sur onglet "Plan & Limites"
3. Changer max_users de 30 à 25
4. Sauvegarder
5. Aller sur https://esbtp-abidjan.klassci.com
6. Vérifier que paywall affiche nouvelle limite (25)
```

**4.3 Test health check**

```bash
cd /var/www/klassci-master
php artisan tenant:health-check --all

# Doit afficher:
# ✅ esbtp-abidjan: HTTP OK (120ms), DB OK, Storage OK
# ✅ esbtp-yakro: HTTP OK (98ms), DB OK, Storage OK
# ✅ presentation: HTTP OK (105ms), DB OK, Storage OK
```

---

## 📅 Planning de Migration

| Jour | Phase | Tâches | Durée | Statut |
|------|-------|--------|-------|--------|
| 1 | Préparation | Créer klassci-master, migrations, fichiers .tenant.json | 4h | ⏳ |
| 2 | Déploiement Master | Installer sur serveur, configurer Nginx, SSL | 3h | ⏳ |
| 3 | Import Tenants | Importer tenants existants dans Master DB, tester dashboard | 2h | ⏳ |
| 4 | Adapter Tenants | Modifier PaywallMiddleware, ajouter connexion Master DB | 3h | ⏳ |
| 5 | Tests | Tests end-to-end, déploiement, health checks | 2h | ⏳ |

**Total : 14 heures (2 jours de travail)**

---

## ✅ Checklist de Validation

### Préparation
- [ ] klassci-master créé et pushhé sur GitHub
- [ ] Migrations Master exécutées
- [ ] Modèles Tenant, TenantDeployment, etc. créés
- [ ] Dashboard SaaS fonctionnel en local

### Déploiement
- [ ] klassci-master installé sur serveur
- [ ] Nginx configuré pour admin.klassci.com
- [ ] Certificat SSL Let's Encrypt obtenu
- [ ] Admin SaaS créé (vous)

### Import Tenants
- [ ] 3 tenants importés dans Master DB
- [ ] Fichiers .tenant.json créés pour chaque tenant
- [ ] Connexion Master DB testée

### Adaptation Tenants
- [ ] PaywallMiddleware lit config depuis Master
- [ ] Variables MASTER_DB_* ajoutées dans .env
- [ ] Ancien paywall-config désactivé
- [ ] Utilisateur MySQL readonly créé

### Tests
- [ ] Déploiement automatique fonctionne
- [ ] Modification plan depuis Master fonctionne
- [ ] Health checks OK sur tous les tenants
- [ ] Paywall bloque correctement si limite atteinte
- [ ] Codes d'urgence fonctionnent

---

## 🚀 Prochaines Étapes

Une fois la migration terminée, vous pourrez :

1. **Ajouter de nouveaux tenants en 2 minutes** :
   ```
   https://admin.klassci.com/saas/tenants/create
   → Remplir formulaire → Cliquer "Créer" → DONE
   ```

2. **Déployer une mise à jour sur tous les tenants en 1 clic** :
   ```
   https://admin.klassci.com/saas/deployments
   → Cliquer "Déployer sur tous les tenants" → DONE
   ```

3. **Surveiller la santé de tous les tenants** :
   ```
   https://admin.klassci.com/saas/monitoring
   → Health checks automatiques toutes les 5 min
   → Alertes email/Slack si tenant down
   ```

---

## 💰 Estimation Coûts

Aucun coût supplémentaire ! Tout tourne sur le même serveur.

**Avant** : 1 serveur pour 3 tenants = ~26,000 FCFA/mois
**Après** : 1 serveur pour 3 tenants + Master = ~26,000 FCFA/mois (inchangé)

---

## ❓ Questions Fréquentes

**Q: Est-ce que les tenants existants seront hors ligne pendant la migration ?**
R: Non ! Migration zero downtime. Les tenants continuent de fonctionner normalement.

**Q: Que se passe-t-il si Master DB tombe en panne ?**
R: Fallback automatique : PaywallMiddleware utilise l'ancien système local.

**Q: Puis-je rollback si problème ?**
R: Oui ! Il suffit de supprimer les variables MASTER_DB_* du .env et tout revient comme avant.

**Q: Les établissements verront-ils des changements ?**
R: Non ! L'interface utilisateur reste identique. Seule la gestion côté admin change.

---

## 📞 Support

**Vous êtes prêt à commencer ?**

Dites-moi "OUI" et je créerai immédiatement :

1. ✅ Structure complète de klassci-master
2. ✅ Toutes les migrations
3. ✅ Tous les modèles Eloquent
4. ✅ Toutes les commandes Artisan
5. ✅ Dashboard complet avec vues
6. ✅ Scripts de déploiement
7. ✅ Documentation complète

**Temps estimé pour tout créer : 30 minutes** 🚀
