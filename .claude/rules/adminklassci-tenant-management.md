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
| ucao-benin | ucao-benin | ucao-benin.klassci.com | Élite | en cours | ~/public_html/ucao-benin |

**Serveur prod** : LWS web44.lws-hosting.com (CloudLinux + LiteSpeed) — `c2569688c@web44.lws-hosting.com`

## Hors Cote d'Ivoire : ce qui a ete corrige, et ce qui reste

`ucao-benin` est la PREMIERE instance hors de Cote d'Ivoire. Ce qui suit a ete
**mesure**, pas deduit : chaque affirmation se rejoue avec la commande qui
l'accompagne.

### Le telephone — CORRIGE (septembre 2026)

> **Cette section a d'abord decrit un defaut a corriger. Il l'est.** Elle decrit
> maintenant le code en place. Ne reconstruisez rien : ouvrez d'abord
> `app/Domain/Notifications/PhoneNormalizer.php`, dont les commentaires portent
> le *pourquoi* de chaque choix.

**Le defaut, pour comprendre ce que les gardes protegent.** Le Benin est passe de
8 a 10 chiffres le **30 novembre 2024**, en prefixant `01` devant l'ancien numero
([ARCEP Benin](https://arcep.bj/a-partir-de-30-novembre-2024-les-numeros-de-telephone-au-benin-passent-de-08-a-10-chiffres/),
[UIT-T](https://www.itu.int/dms_pub/itu-t/oth/02/02/T02020000170002PDFF.pdf)).
La Cote d'Ivoire a fait de meme en 2021, et `01` y designe Moov. **Les deux plans
se recouvrent entierement** : meme longueur, meme debut. L'analyseur apposait
`+225` a toute saisie, donc `0142345678` (MTN Benin) devenait `+2250142345678` —
pour huit series attribuees des deux cotes (`0140`-`0143`, `0150`-`0153`), un
mobile Moov CI **joignable appartenant a un tiers**, a qui partait la relance avec
le nom de l'etudiant et le montant du. Et l'ecriture correcte `+229…` etait, elle,
REFUSEE : le portail n'acceptait que la saisie qui corrompt.

**Ce qui est en place aujourd'hui** :

1. **L'indicatif explicite est cru.** Une entree portant `+` ou `00` est validee
   sur sa FORME (UIT-T E.164 §6.2) et conservee telle quelle, sans rejouer le
   controle de prefixe national. `+229 01 42 34 56 78` est accepte.
2. **DEUX reglages, pas un** — `telephone_indicatif_pays` et
   `telephone_prefixes_mobiles`, exposes dans `/esbtp/settings` onglet General.
   L'indicatif seul ne suffit pas : pose a `229` avec la liste ivoirienne livree,
   l'instance accepterait `0707123456`, qui ne designe personne au Benin.
   `TelephoneSettingsService::incoherenceDuChangementDePays()` refuse cette
   combinaison au moment ou l'indicatif change.
3. **Jamais de liste blanche de prefixes par pays.** `0142345678` est
   simultanement un MTN Benin et un Moov CI valides : aucune inference n'est
   possible, et les series se reattribuent. Que l'instance DECLARE ses prefixes
   est l'inverse — ce n'est plus le code qui devine un pays, c'est l'ecole qui
   dit le sien. Rien n'est derive de `school_country`, un libelle libre.
4. **Les cinq normaliseurs paralleles sont supprimes**, y compris le
   `formatPhone()` en JavaScript du recouvrement. Le controle a rejouer avant
   d'en rajouter un :

   ```bash
   grep -rn "+225" app/ --include="*.php" | grep -vE "^\S+:[0-9]+:\s*(\*|//)"
   ```

   Rend **deux lignes**, et deux seulement : `MailPulseTestNotificationService`
   et `ESBTPComptabiliteRelanceController`, qui sont des jeux de donnees de
   demonstration (`'school_phone' => …`, `'telephone' => …`). Aucun normaliseur.
   Toute TROISIEME ligne est un normaliseur qui repousse.
5. **`estMobileNational()` est distinct d'`isValid()`**. Le premier exige un
   mobile du pays de l'instance ; il n'est pose que la ou la portee etroite est
   une DECISION, c'est-a-dire sur les cles d'unicite (portail de candidature,
   chatbot parent). Partout ailleurs, `isValid()` accepte tout numero
   international bien forme — une famille de la diaspora sur une instance
   ivoirienne est un cas legitime, que le portail documente lui-meme.

**L'invariant qui protege les six instances ivoiriennes, et qui tient** : la forme
canonique d'un numero ivoirien reste **octet pour octet** `+225` + 10 chiffres.
`esbtp_candidatures.telephone` est le seul stockage canonique, sous index UNIQUE
`(telephone, annee_universitaire_id)` — la detection de doublon en depend. Les cas
ivoiriens de `PhoneNormalizerTest` sont restes verts **sans qu'une seule ligne
existante soit modifiee** ; c'est le controle a refaire avant tout changement ici.
`esbtp_etudiants.telephone` et `esbtp_parents.telephone` sont bruts : aucun
backfill.

**Ce qui reste a faire, et son declencheur.** Le declencheur n'est pas l'ouverture
de l'instance, c'est la **premiere relance ou la premiere candidature beninoise** :
tant qu'aucune n'est partie, rien n'est corrompu. Les gestes de mise en service —
poser les deux reglages, poser le fuseau, recenser l'existant — sont dans
[docs/runbooks/ucao-benin-mise-en-service.md](../../docs/runbooks/ucao-benin-mise-en-service.md).
Ce recensement ne peut PAS trancher tout seul : `+2250142345678` est simultanement
la corruption d'un MTN Benin et un Moov CI valide, donc la requete enumere des
candidats et l'ecole decide.

### Le fuseau — dans le `.env`, et nulle part ailleurs (septembre 2026)

`config/app.php` lit desormais `env('APP_TIMEZONE', 'UTC')`. Defaut `UTC`, soit
le comportement d'avant ; `Africa/Abidjan` valant UTC+0 sans heure d'ete, les six
instances ivoiriennes ne bougent pas d'une seconde. Les trois taches planifiees
qui figeaient `->timezone('Africa/Abidjan')` suivent maintenant le fuseau de
l'application.

**La fausse piste a ne pas reprendre.** `SettingsSeeder` semait un `app_timezone`
que rien ne lisait et que l'ecran des reglages n'affichait pas — d'ou la
conclusion tentante « il suffit de le brancher ». Elle est fausse : Laravel
appelle `date_default_timezone_set()` pendant `LoadConfiguration`, **avant** tout
fournisseur de services et avant que la base soit joignable. Un reglage en base
ne peut pas etre lu a cet instant. La ligne a ete retiree du seeder ; les
instances en service gardent une ligne morte, que rien ne lit ni ne montre.

**Le moment compte plus que la valeur.** Laravel ecrit les horodatages dans le
fuseau de l'application : le changer sur une instance QUI A DEJA DES DONNEES
laisse derriere des lignes ecrites dans l'ancien, que les nouvelles ne rejoignent
pas. Le seul moment gratuit est le **provisionnement**, avant la premiere
inscription. C'est aussi pourquoi `APP_TIMEZONE` n'est volontairement PAS dans
`CleEnvAutorisee` : le CLI ne doit pas pouvoir deplacer le fuseau d'une instance
vivante.

**Le provisionnement le pose donc lui-meme** (`tenant:provision --timezone=`,
adminKlassci, septembre 2026). Defaut `UTC`, soit le comportement d'avant. La
commande REFUSE un fuseau qui pratique l'heure d'ete plutot que de figer un
decalage juste la moitie de l'annee, et refuse un identifiant IANA inconnu.

**Il y a un SECOND fuseau, et `APP_TIMEZONE` ne le couvre pas.** MySQL renseigne
lui-meme sept colonnes (`useCurrent()` dans leur migration, dont
`esbtp_inscription_workflow_history.action_timestamp`,
`group_portal_sso_logs.created_at` et `esbtp_grade_sheet_revisions.created_at`)
et les ecrit dans le fuseau de SA session. Laissee a celle du serveur, une
instance a UTC+1 verrait ces colonnes prendre une heure de retard sur le
`created_at` de la MEME ligne. D'ou `DB_TIMEZONE`, lu par `config/database.php`,
pose au provisionnement a cote d'`APP_TIMEZONE`. Defaut null : Laravel n'emet
alors AUCUN `SET time_zone` (`MySqlConnector::configureTimezone()` teste
`isset()`, verifie en 9.x comme en 10.x), donc rien ne change la ou rien n'est
regle. Un **decalage fixe** et non un nom de fuseau, parce que les tables de
fuseaux de MySQL ne sont pas chargees sur l'hebergement mutualise.

**Ce que le fuseau ne corrige PAS** : les numeros de telephone. C'est un defaut
distinct, decrit plus haut dans cette rule, et il se corrige par ses deux
reglages a lui.

Les gestes concrets pour `ucao-benin` — reglages, fuseau, recensement de
l'existant — sont dans
[docs/runbooks/ucao-benin-mise-en-service.md](../../docs/runbooks/ucao-benin-mise-en-service.md).

Ce que le fuseau aligne, une fois pose au provisionnement — **pour les donnees
ecrites ensuite, pas pour celles deja en base** :

- **24 filtres `whereDate('created_at', …)`** comparent le jour de `now()` a une
  chaine que Laravel a ecrite dans le fuseau de l'application — dont
  `CashSessionService::queryJour()`, la caisse d'un caissier. Les deux cotes
  bougent ensemble, donc l'alignement est immediat pour les nouvelles lignes ;
  celles ecrites avant gardent l'ancien fuseau et decalent d'une heure.
- **`date_paiement` est ecrite depuis `now()`** en une dizaine d'endroits. Elle est
  bien de type `date` (donc la lecture de la reconciliation, elle, ne decale pas),
  mais un encaissement saisi apres minuit local porterait la date de la veille — et
  pourrait tomber dans une periode deja verrouillee.
- `app/Console/Kernel.php` fige `->timezone('Africa/Abidjan')` sur trois taches.

**`APP_TIMEZONE` ne suffit pas seul : il y a un second fuseau.** Sept colonnes
sont renseignees par MySQL lui-meme (`useCurrent()` dans leur migration) — dont
`esbtp_inscription_workflow_history.action_timestamp`,
`group_portal_sso_logs.created_at` et `esbtp_grade_sheet_revisions.created_at`.
MySQL les ecrit dans le fuseau de SA session, pas dans celui de l'application :
sur une instance a UTC+1 servie par un serveur a UTC, elles prennent une heure de
retard sur le `created_at` de la MEME ligne, dans un journal d'audit. D'ou
`DB_TIMEZONE` sur la connexion mysql (`config/database.php`), a poser **en meme
temps** qu'`APP_TIMEZONE`. Defaut `null` = aucun `SET time_zone` emis, donc les
instances ivoiriennes ne bougent pas.

**Ce que le fuseau ne corrige PAS** : les huit sites qui posent `telephone` ou un
indicatif. Ce sont deux chantiers distincts — voir la section telephone ci-dessus.

### Les moyens de paiement

`app/Enums/ModePaiement.php` liste Wave, Djamo et Orange Money — et **Celtiis Cash
manque**. `cash_counts.mode_paiement` etant un `string(30)` et non un `ENUM` SQL,
l'ajout ne coute ni `ALTER` ni verrou. **Ne retirez pas** les modes ivoiriens pour
autant : l'enum est partage par les huit instances et lu par la reconciliation ; un
mode inutilise ne coute rien, un mode manquant rend un encaissement invisible du
rapprochement.

### Ce qui est deja neutre — ne le rouvrez pas

Les textes d'Etat des releves et bulletins LMD sont **deja des reglages**
(`lmd_bulletin_republic_text`, `_union_text`, `_ministry_text`), branches et exposes
a l'ecran. `LmdTranscriptSnapshotBuilder` porte meme le commentaire qui l'explique :
« un releve delivre au Benin serait sorti au nom d'un autre Etat ». C'est le
precedent a imiter. La nationalite « Beninoise » est deja au referentiel, et la
monnaie est commune (XOF, zone UEMOA), comme le cadre LMD.

**Le declencheur n'est pas l'ouverture de l'instance, c'est la premiere relance ou
la premiere candidature beninoise.** Tant qu'aucune n'est partie, rien n'est
corrompu.

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
    --admin-email=admin@example.ci \
    --timezone=Africa/Abidjan      # hors CI : Africa/Porto-Novo pour le Bénin.
                                   # Defaut UTC. Le seul moment ou le fuseau
                                   # se pose sans degats — voir plus haut.

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
git push origin presentation:ucao-benin
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
   - **Instance hors Côte d'Ivoire** → suivre
     [docs/runbooks/ucao-benin-mise-en-service.md](../../docs/runbooks/ucao-benin-mise-en-service.md)
     **avant la première candidature** : les deux réglages de téléphone et les
     deux lignes de fuseau. Le runbook porte `ucao-benin` dans son titre parce
     qu'il en vient, mais ses gestes valent pour toute instance hors CI — seules
     changent les valeurs (indicatif, préfixes, fuseau). Posés après la première
     saisie, ils ne rattrapent plus ce qui est déjà écrit.

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
