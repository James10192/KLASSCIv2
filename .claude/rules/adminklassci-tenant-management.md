# Rule: adminKlassci — Gestion centralisée des tenants KLASSCI

## Quand s'active

Cette rule s'active automatiquement quand tu :
- Travailles sur le sous-dossier `adminKlassci/` du repo KLASSCIv2
- Touches au `PaywallMiddleware` de tenant (qui appelle l'API adminKlassci)
- Discutes du provisioning, déploiement, monitoring, backup d'un tenant
- Touches aux variables d'env `MASTER_API_URL`, `MASTER_API_TOKEN`, `TENANT_CODE`
- Mentions « klassci-master », « SaaS master », « portail groupe », « fondateur multi-tenant »
- Crées une feature qui doit être centralisée cross-tenant (config, secrets, billing, monitoring)
- Provisionne un nouveau tenant ou un déploiement multi-tenant

## Architecture KLASSCI multi-instance — 2 applications, 2 repos, 2 rôles

KLASSCI suit une architecture SaaS multi-instance avec **isolation complète par tenant** (chaque école = DB séparée) ET **orchestration centralisée** via adminKlassci.

```
┌─────────────────────────────────────────────────────────────────┐
│ adminKlassci (SaaS Master Application)                          │
│ - Repo : James10192/adminKlassci (séparé)                        │
│ - Sous-dossier local : klassciv2/adminKlassci/ (Symlink/clone)   │
│ - URL prod : https://admin.klassci.com                           │
│ - DB : klassci_master (centrale, unique)                         │
│ - Stack : Laravel 12 + Filament 3.3 + Sanctum + Pest             │
│ - Rôle : Provisionner, déployer, monitorer, billing, sécurité    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ API REST (Sanctum tokens)
                              │ /api/tenants/{code}/limits
                              │ /api/tenants/{code}/cache/invalidate
                              │ /api/cli/*
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ KLASSCIv2 (Tenant Business Application) × N tenants              │
│ - Repo : James10192/KLASSCIv2 (séparé)                          │
│ - Branches : presentation, esbtp-abidjan, esbtp-yakro, ephrata,  │
│   hetec, rostan (1 branche par tenant)                          │
│ - DB : klassci_{tenant} (1 DB par école, isolée)                │
│ - Stack : Laravel 12 + Blade + Alpine + DomPDF + Sanctum         │
│ - Rôle : App métier (étudiants, notes, paiements, bulletins...)  │
└─────────────────────────────────────────────────────────────────┘
```

**Cross-repo discipline** :
- adminKlassci et KLASSCIv2 sont 2 repos Git séparés
- Ne JAMAIS mélanger les commits entre les deux
- Tenant branches dans KLASSCIv2, master branche dans adminKlassci

## Tenants en production (2026)

| Code tenant | Branch git | URL | Plan | Inscriptions | Dossier serveur |
|---|---|---|---|---|---|
| presentation | presentation | presentation.klassci.com | Démo (Free) | démo | ~/public_html/presentation |
| esbtp-abidjan | esbtp-abidjan | esbtp-abidjan.klassci.com | **Élite** | > 2000 | ~/public_html/esbtp-abidjan |
| esbtp-yakro | esbtp-yakro | esbtp-yakro.klassci.com | **Élite** | > 2000 | ~/public_html/esbtp-yakro |
| ephrata | ephrata | ephrata.klassci.com | **Partenaire** | en cours | ~/public_html/ephrata |
| hetec | hetec | hetec.klassci.com | Test (Élite) | en cours | ~/public_html/hetec |
| rostan | rostan | rostan.klassci.com | Test (Élite) | en cours | **~/public_html/islg-rostan** (⚠️ pas `rostan`) |

**Serveur prod** : LWS web44.lws-hosting.com (CloudLinux + LiteSpeed) — `c2569688c@web44.lws-hosting.com`

## Tables clés `klassci_master`

```sql
-- Source de vérité pour TOUS les tenants
tenants (
    id, code, name, subdomain,
    database_name, database_credentials (encrypted JSON),
    git_branch, git_commit_hash, last_deployed_at,
    api_token (sanctum), api_token_created_at,
    status (active/suspended/archived),
    plan (free/essentiel/professional/elite), monthly_fee,
    subscription_start_date, subscription_end_date,
    max_users, max_staff, max_students, max_inscriptions_per_year, max_storage_mb,
    current_users, current_staff, current_students, current_storage_mb,
    storage_measured_at,
    admin_name, admin_email, support_email,
    created_at, updated_at, deleted_at
)

-- Historique déploiements (1 row par tenant:deploy run)
tenant_deployments (id, tenant_id, git_commit_hash (nullable), git_branch,
    status (pending/in_progress/success/failed/rolled_back),
    error_message, started_at, completed_at, duration_seconds, deployed_by_user_id)

-- Health checks (6 types)
tenant_health_checks (id, tenant_id, check_type, status, response_time_ms,
    details (JSON), checked_at)
-- types : http_status, database_connection, disk_space, ssl_certificate,
--         application_errors, queue_workers

-- Backups
tenant_backups (id, tenant_id, type (full/database_only/files_only),
    backup_path, size_bytes, status, expires_at)

-- Features togglables par tenant
tenant_features (id, tenant_id, feature_key, is_enabled, config (JSON))

-- Audit trail
tenant_activity_logs (id, tenant_id, action, description, ip_address,
    user_agent, performed_by_user_id, metadata (JSON), performed_at)

-- Admins SaaS (super_admin / support / billing)
saas_admins (id, name, email, password, role, is_active)

-- Portail groupe (fondateurs multi-tenants type ROSTAN)
groups, group_members, group_portal_sso_logs, group_alert_notifications_log,
group_member_notification_preferences
```

## Commandes adminKlassci canoniques

```bash
# Provisioning (17 étapes : DB + Git + .env + migrations + subdomain + SSL)
php artisan tenant:provision --code=lycee-yop --name="Lycée Y" \
    --subdomain=lycee-yop --branch=main --plan=elite \
    --admin-email=admin@example.ci

# Déploiement (9 étapes : backup + maintenance + git pull + composer + migrate + cache)
php artisan tenant:deploy esbtp-yakro              # 1 tenant
php artisan tenant:deploy --all                    # tous les tenants
# ↑ détecte automatiquement local vs prod via isOnProductionServer() :
#   - Si /home/c2569688c/public_html/ existe → exécution directe
#   - Sinon → SSH vers PRODUCTION_HOST

# Monitoring (toutes les 5min via scheduler)
php artisan tenant:health-check presentation       # 1 tenant
php artisan tenant:health-check --all              # tous

# Backups (quotidien 2h via scheduler, database_only par défaut)
php artisan tenant:backup esbtp-abidjan --type=database_only
php artisan tenant:cleanup-backups --days=30       # rétention 30j

# Stats usage (toutes les heures via scheduler)
php artisan tenant:update-stats --all
# Met à jour : current_users, current_staff, current_students,
#              current_inscriptions, current_storage_mb

# Storage measurement (quotidien 3h30, via SSH `du -sm`)
php artisan tenant:update-storage --all

# Authentification
php artisan saas:create-admin --name="Marcel" --email="marcel@klassci.com" --role=super_admin
php artisan tenant:generate-token esbtp-abidjan    # token Sanctum pour API

# Portail groupe (fondateurs multi-tenants)
php artisan group:dispatch-alert-notifications     # toutes 15min
php artisan group:send-alert-digests               # toutes 30min
```

## Scheduler adminKlassci (`bootstrap/app.php` ou `routes/console.php`)

```php
// Health checks toutes les 5 minutes
$schedule->command('tenant:health-check --all')->everyFiveMinutes();

// Backups quotidiens 2h du matin
$schedule->command('tenant:backup --all --type=database_only')->dailyAt('02:00');

// Cleanup backups expirés 3h
$schedule->command('tenant:cleanup-backups')->dailyAt('03:00');

// Update stats toutes les heures
$schedule->command('tenant:update-stats --all')->hourly();

// Update storage 3h30
$schedule->command('tenant:update-storage --all')->dailyAt('03:30');

// Notifications portail groupe
$schedule->command('group:dispatch-alert-notifications')->everyFifteenMinutes();
$schedule->command('group:send-alert-digests')->everyThirtyMinutes();
```

**Activation crontab prod** :
```cron
* * * * * cd /home/c2569688c/public_html/admin && php artisan schedule:run >> /dev/null 2>&1
```

## API REST adminKlassci → tenant

### Endpoint `/api/tenants/{code}/limits` (lu par PaywallMiddleware)

Authentification : Bearer token Sanctum (`MASTER_API_TOKEN` per tenant).

Réponse JSON :
```json
{
  "tenant_code": "esbtp-abidjan",
  "plan": "elite",
  "status": "active",
  "subscription": { "is_expired": false, "days_remaining": 364 },
  "limits": { "max_users": 30, "max_students": 3000, "max_storage_mb": 5120 },
  "current_usage": { "users": 25, "students": 2150, "storage_mb": 1024 },
  "usage_percentage": { "users": 83, "students": 71 },
  "quota_status": { "is_over_quota": false, "users_over_limit": false },
  "blocked_features": []
}
```

### Endpoint `/api/tenants/{code}/cache/invalidate` (webhook tenant → master)

Quand un tenant valide un paiement, il appelle cet endpoint pour invalider le cache groupe (vue fondateur multi-tenant).

### Endpoints CLI `/api/cli/*` (utilisés par klassci-cli standalone)

CRUD complet sur students, inscriptions, classes, paiements, users, années universitaires, logs. Auth Sanctum avec abilities `cli:read | cli:write | cli:admin`.

## PaywallMiddleware (côté tenant) — 3-tier strategy

```php
// app/Http/Middleware/PaywallMiddleware.php (442 lignes côté tenant)
public function handle(Request $request, Closure $next)
{
    $status = $this->checkPaywallStatus();  // orchestrateur
    // ...
}

private function checkPaywallStatus(): array
{
    // 1. Cache local 5min (paywall_limits_{tenant_code})
    return Cache::remember("paywall_limits_{$this->tenantCode}", 300,
        fn() => $this->getLimitsFromMaster()                  // 2. API master
        ?: $this->checkPaywallStatusLocal()                   // 3. Fallback local
    );
}
```

**Configuration** :
```env
# .env tenant
MASTER_API_URL=https://admin.klassci.com/api
MASTER_API_TOKEN=<token Sanctum généré via tenant:generate-token>
TENANT_CODE=esbtp-abidjan
```

**Architecture clé** :
- Cache 5min → réduit la charge sur API master
- Fallback local → résilience si master down
- Configuration via .env (jamais hardcodée)

## SSO cross-app (HMAC-SHA256) — portail groupe → tenant

Quand un fondateur clique "Ouvrir l'établissement" dans le portail groupe master :

```
Master signe HMAC-SHA256 (secret partagé GROUP_SSO_SHARED_SECRET, TTL 2min, nonce random)
  → URL signée vers tenant
  → Tenant vérifie HMAC + expiry + rate limit 10/min/IP + open-redirect strict
  → Auth::login + session regenerate
  → Audit log master + tenant
```

**Config requise** (master ET chaque tenant) :
```env
GROUP_SSO_SHARED_SECRET=<64 hex chars MÊME VALEUR>
```

## UI Filament adminKlassci

Pages disponibles :
- `/admin` — Login SaaS admin
- `/admin/tenants` — Liste, view, edit tenants (4 boutons header sur view : Update stats / Health check / Deploy / Edit)
- `/admin/tenant-deployments` — Historique déploiements + détails
- `/admin/tenant-health-checks` — Issues uniquement (filtre 24h status unhealthy/degraded)
- `/admin/tenant-backups` — Liste backups
- `/admin/groups` — Groupes fondateurs (ROSTAN groupe)
- `/groupe/*` — Portail groupe (séparé du panel admin, accessible par GroupMember)

**Pattern Filament v3.3** :
- Resources : Tenant, TenantDeployment, TenantHealthCheck, TenantBackup, Group
- RelationManagers : Deployments, HealthChecks, Backups sur TenantResource (read-only, action buttons)
- Widgets : StatsOverviewWidget, TenantsByPlanChart, TenantsTableWidget, GroupHealthOverview
- Modèle User Filament implémente FilamentUser via table `saas_admins`

## Multi-tenant secrets storage (pattern recommandé)

**Choisir selon sensibilité et scope** :

| Type de secret | Storage | Pourquoi |
|---|---|---|
| API token tenant (auth API adminKlassci) | `tenants.api_token` (master, encrypted nullable) | Centralisé, rotatable, audit |
| Database credentials tenant | `tenants.database_credentials` (master, encrypted JSON) | Provisioning automatique |
| WhatsApp/SMS API keys (futur) | `tenants.whatsapp_credentials` ou tenant `esbtp_settings` encrypted | À décider selon centralisation ops |
| GROUP_SSO_SHARED_SECRET | `.env` master + chaque tenant (même valeur) | Symétrique, rotation manuelle |
| MASTER_API_URL/TOKEN/TENANT_CODE | `.env` tenant | Spécifique au tenant |

**Anti-patterns à BLOQUER en review** :
1. Hardcoder un secret tenant dans `config/` (perdu en config:cache)
2. Mettre un secret partagé multi-tenant dans le code (incompatible avec multi-instance)
3. Lire un secret directement dans le repo (env, JSON committé) — toujours via DB encrypted ou env .gitignored

## Cross-branch push pattern (pour tenant updates)

Depuis la branche `presentation` (canonique), pousser vers les 5 autres tenants :

```bash
git push origin presentation:esbtp-yakro
git push origin presentation:esbtp-abidjan
git push origin presentation:rostan
git push origin presentation:hetec
git push origin presentation:ephrata
```

**Discipline cross-branch** :
- Toujours faire `git push origin presentation:<tenant>` (pas checkout + merge local)
- Si conflit non-fast-forward → utiliser worktree dédié (cf. `multi-agent-git-safety.md`)
- Ne JAMAIS force-push une branche tenant en prod

## Onboarding nouveau tenant (étapes coordonnées)

1. **Master adminKlassci** :
   - `php artisan tenant:provision --code=X ...` (17 étapes auto)
   - Vérifier `tenant_deployments.status = success`
   - Générer API token : `php artisan tenant:generate-token X`

2. **Tenant nouveau (serveur)** :
   - Dossier créé : `~/public_html/X`
   - DB créée : `klassci_X`
   - `.env` rempli : MASTER_API_URL, MASTER_API_TOKEN, TENANT_CODE
   - GROUP_SSO_SHARED_SECRET si tenant fait partie d'un groupe
   - Setup script exécuté : storage symlinks, permissions, seeders

3. **Subdomain cPanel** :
   - `X.klassci.com` → `/home/c2569688c/public_html/X/public`
   - SSL Let's Encrypt automatique

4. **Health check initial** :
   - `php artisan tenant:health-check X` doit retourner 6/6 healthy

5. **Mettre à jour** `CLAUDE.md` ligne « Instances actives » pour ajouter le nouveau tenant + son offre.

## Patterns anti-régression connus (incidents fondateurs)

### 1. JSON double-encoded dans Filament Textarea

**Problème** : Textarea avec cast `'array'` Eloquent → double `json_encode()` sur save.

**Fix** : Ajouter `dehydrateStateUsing()` pour décoder le JSON string avant que le cast Eloquent ne l'encode :
```php
Textarea::make('database_credentials')
    ->dehydrateStateUsing(fn($state) => is_string($state) ? json_decode($state, true) ?? $state : $state)
    ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
```

### 2. CloudLinux PHP Selector vs `.htaccess AddHandler`

**Problème** : Ajouter `AddHandler` dans `.htaccess` casse PHP sur LWS (CloudLinux PHP Selector).

**Fix** : SUPPRIMER toutes les directives `AddHandler` des `.htaccess`. Configurer PHP version via cPanel PHP Selector uniquement.

### 3. RelationManagers en lecture seule

**Problème** : RelationManagers (HealthChecks, Backups, Deployments) sur TenantResource affichent un bouton "Créer" comme si on pouvait créer manuellement, alors que ces données sont générées par commands Artisan.

**Fix** : Vider le `form()` (`return $form->schema([])`) + ajouter `Action` dans `headerActions` qui lance la commande Artisan via `\Artisan::call()`.

### 4. Bouton Deploy sur TenantResource

**Pattern Filament v3.3 pour actions opérationnelles** :
```php
Tables\Actions\Action::make('deploy')
    ->requiresConfirmation()
    ->action(function ($record) {
        \Artisan::call('tenant:deploy', ['tenant' => $record->code]);
        \Filament\Notifications\Notification::make()->success()
            ->title('Déploiement démarré')->send();
        return redirect()->route('filament.admin.resources.tenant-deployments.index');
    })
    ->visible(fn ($record) => $record->status === 'active');
```

### 5. git_commit_hash nullable

**Problème** : TenantDeploy crée le record AVANT git pull (hash pas encore connu). Migration originale était NOT NULL → SQL error.

**Fix** : `$table->string('git_commit_hash', 40)->nullable()->change();`

## Anti-patterns à BLOQUER en review

1. ❌ Implémenter une feature centralisée (config cross-tenant, billing, monitoring) dans le tenant KLASSCIv2 au lieu de adminKlassci.
2. ❌ Hardcoder l'URL master `https://admin.klassci.com/api` dans le code (utiliser `MASTER_API_URL`).
3. ❌ Modifier `tenants.database_credentials` sans `dehydrateStateUsing()` (double-encoding garanti).
4. ❌ Force-push une branche tenant en prod sans PR / sans coordination.
5. ❌ Mettre des credentials tenant dans `.env` master (ils doivent être dans `tenants.database_credentials` chiffrés).
6. ❌ Créer un job de health-check ou backup dans le tenant KLASSCIv2 (c'est adminKlassci qui orchestre).
7. ❌ Mélanger commits adminKlassci et KLASSCIv2 dans la même branche (2 repos séparés).
8. ❌ Builder une UI Filament dans KLASSCIv2 (le panel admin EST adminKlassci).
9. ❌ Oublier d'ajouter la rule `tenant-branches.md` au cours de l'onboarding d'un nouveau tenant.
10. ❌ Modifier `klassci_master.tenants` directement par SQL au lieu de passer par les commands Artisan ou Filament.

## Voir aussi

- `.claude/rules/tenant-branches.md` — pattern branches Git tenant
- `.claude/rules/multi-agent-git-safety.md` — discipline cross-branch + worktree
- `~/.claude/rules/no-migrate-fresh.md` — destruction DB interdite
- Mémoire projet : `klassci-cli-tool.md` — CLI standalone + tokens
- Mémoire projet : `deployment.md` — commandes de déploiement multi-tenant
- Mémoire projet : `portail-groupe-pr1-pr2-pr3-april2026.md` — portail groupe fondateurs
- Mémoire projet : `session-2026-04-22-group-portal-marathon.md` — leçons Filament + scheduler
- adminKlassci CLAUDE.md (sous-dossier) — documentation interne SaaS
- adminKlassci `docs/SAAS_ARCHITECTURE.md` — architecture détaillée
- adminKlassci `docs/SAAS_DEPLOYMENT_PLAN.md` — plan déploiement
