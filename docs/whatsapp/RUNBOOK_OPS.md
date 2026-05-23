# RUNBOOK OPS — WhatsApp Business KLASSCI

> Runbook ops quotidien — référence pour l'équipe support / ingénieur d'astreinte.
> Complémentaire à `DISASTER_RECOVERY.md` (incidents majeurs) et `ONBOARDING_TENANT.md` (provisioning).

## 📅 Routines quotidiennes (8h00 UTC)

### 1. Vérification health checks WhatsApp (5 min)

```bash
# Sur adminKlassci
ssh c2569688c@web44.lws-hosting.com 'cd /home/c2569688c/public_html/admin && php artisan tenant:health-check --all'
```

Indicateurs attendus :
- ✅ Tous les tenants `whatsapp_enabled=true` → statut healthy
- ⚠️ Si un tenant passe degraded : vérifier logs `parent_notification_logs` derniers 1h
- ❌ Si unhealthy + multiples failures : checklist DR scénario 1 (rate limit Meta) ou 2 (templates révoqués)

### 2. Revue dashboard adminKlassci (3 min)

URL : https://admin.klassci.com/admin

Widgets à scanner :
- **WhatsApp Actifs** : doit matcher nombre attendu de tenants production
- **Configurés mais OFF** : si >0 → vérifier raison avec l'école (sortie volontaire ?)
- **Onboarding KYC** : backlog actionnable, contacter écoles pour avancer
- **Coût mensuel estimé** : si dérive >150% du baseline → BudgetGuard auto-suspend (Phase 4)

### 3. Spot check inbox WhatsApp tenant pilote (5 min)

URL : https://esbtp-yakro.klassci.com/esbtp/communications

Vérifier :
- KPI "WhatsApp non lus" → si >5 messages : alerter secrétaire école
- Tab WhatsApp accessible (pas 500) → sinon vérifier permission `whatsapp.inbox.view`
- Latence chargement <2s

## 🚨 Alertes automatiques

### Configuration alertes Slack (Phase 16 step 2 — à implémenter)

```bash
# Cible : webhook Slack #klassci-ops
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXX/YYY/ZZZ
```

Triggers automatiques (à scheduler via Laravel scheduler) :

| Trigger | Seuil | Action |
|---|---|---|
| Delivery rate WhatsApp < 95% sur 1h | par tenant | Alert Slack + email superAdmin |
| Bounces > 10 sur 30 min | global | Alert Slack #klassci-ops |
| Budget WhatsApp tenant > 150% du baseline | par tenant | Auto-suspend + alert + email école |
| Templates Meta rejetés/révoqués | global | Alert P0 Slack + page on-call |
| Webhook Meta GET challenge fail | par tenant | Alert avant Meta désactive le webhook |
| Master API down >5 min | global | Tenants basculent en cache 5min, alert P1 |

## 🛠 Commandes ops courantes

### Toggle WhatsApp tenant (urgence)

```bash
# Désactiver WhatsApp pour un tenant (instant, propagation 5min via cache)
php artisan tinker
>>> $t = \App\Models\Tenant::where('code', 'esbtp-yakro')->first();
>>> $t->update(['whatsapp_enabled' => false]);
>>> // Invalider le cache tenant
>>> \Illuminate\Support\Facades\Http::post(
...     'https://esbtp-yakro.klassci.com/api/tenants/esbtp-yakro/cache/invalidate',
...     ['headers' => ['Authorization' => 'Bearer ' . $t->api_token]]
... );
```

### Rotation access_token Meta (60j)

```bash
# Toutes les 60 jours par tenant (rappel calendar)
# 1. Aller Meta Business Manager → System Users → Generate New Token
# 2. Copier nouveau token (jamais en clair en log/commit)
# 3. Update adminKlassci :
php artisan tinker
>>> $t = \App\Models\Tenant::where('code', 'esbtp-yakro')->first();
>>> $t->update(['whatsapp_access_token' => 'EAA...nouveau_token']);
>>> // Invalider cache tenant pour forcer reload
```

### Inspection logs WhatsApp tenant

```bash
# Sur le serveur tenant
ssh c2569688c@web44.lws-hosting.com
cd /home/c2569688c/public_html/esbtp-yakro/storage/logs
tail -f laravel.log | grep -i "whatsapp"

# Filtrer par type d'erreur
grep "WhatsApp.*non configurée\|Erreur API WhatsApp\|Rate limit" laravel.log | tail -50
```

### Stats coût par tenant (mois en cours)

```sql
-- Sur DB tenant
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as mois,
    channel,
    COUNT(*) as messages_envoyes,
    SUM(cost_fcfa) as cout_total_fcfa,
    AVG(cost_fcfa) as cout_moyen_fcfa,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as echecs
FROM parent_notification_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
GROUP BY mois, channel
ORDER BY mois DESC, channel;
```

## 📊 Monitoring metrics clés

### Bons indicateurs (target SLO)

| Métrique | Target | Critique |
|---|---|---|
| Delivery rate WhatsApp | ≥98% | <90% |
| Latence p95 send msg | <3s | >10s |
| Opt-out rate | <2% par mois | >10% |
| Coût/msg moyen | <3 FCFA | >5 FCFA |
| Master API uptime | 99.9% | <99% |
| Webhook GET challenge success | 100% | <100% sur 1h |

### Sources données

- **WhatsAppMetricsService** (KLASSCIv2) : KPIs par tenant, lecture parent_notification_logs
- **Filament Widget WhatsAppOverview** (adminKlassci) : agrégation cross-tenant
- **Laravel Telescope** (si activé) : N+1, slow queries
- **Logs Meta Cloud API** : Business Manager → WhatsApp → Insights

## 🔐 Sécurité ops

### Rotation secrets recommandée

| Secret | Fréquence | Procédure |
|---|---|---|
| Meta access_token | 60j max (recommandé Meta) | Voir "Rotation access_token Meta" |
| Webhook verify_token | 6 mois | Générer 64 chars random, update tenant, mettre à jour Meta config |
| MASTER_API_TOKEN | 1 an | Régénérer via `php artisan tenant:generate-token <code>` |
| GROUP_SSO_SHARED_SECRET | 1 an | Coordonné master + chaque tenant |

### Audit log obligatoire

Tout changement de credentials WhatsApp doit être tracé dans `tenant_activity_logs` :

```php
// app/Filament/Resources/TenantResource — onSave hook (à ajouter Phase 18)
TenantActivityLog::create([
    'tenant_id' => $tenant->id,
    'action' => 'whatsapp_credentials_updated',
    'description' => 'Credentials Meta mis à jour',
    'performed_by_user_id' => Auth::id(),
    'ip_address' => request()->ip(),
    'metadata' => [
        'fields_changed' => array_keys($changedFields),
        'whatsapp_enabled_after' => $tenant->whatsapp_enabled,
    ],
]);
```

### Masquage PII dans logs

Convention KLASSCI :
- Téléphones : `+225 07 ** ** ** 56` (masquer chiffres centraux)
- Emails : `m***@example.com` (masquer local-part)
- Tokens : JAMAIS en clair dans logs — `***REDACTED***`

Filtre Monolog à activer (config/logging.php) — à implémenter Phase 18.

## 🔄 Procédures hebdo (vendredi 17h00)

1. **Rapport hebdo coût WhatsApp** (15 min)
   - Export CSV depuis dashboard adminKlassci → email comptable
   - Comparaison N-1 vs N-2 (détecter dérive)

2. **Cleanup logs anciens** (auto via scheduler)
   - `parent_notification_logs` > 90j → archive (déplace vers table `_archived`)
   - `whatsapp_inbound_messages` lus + archivés > 30j → delete physique

3. **Revue bounces** (10 min)
   - Lister parents en bounce auto-disable (table `whatsapp_bounces`)
   - Contacter école pour vérifier numéros valides
   - Re-enable si justifié (`BounceTracker::recordSuccess()`)

## 📞 Escalation

| Sévérité | Symptôme | Contact |
|---|---|---|
| P0 | Master API down + multi-tenants impactés | superAdmin SaaS + Marcel |
| P0 | Meta révoque templates en masse | superAdmin + équipe Meta Partner support |
| P1 | 1 tenant en bounce loop | superAdmin + secrétaire école concernée |
| P2 | Latence dégradée (p95 >10s) | Service Technique |
| P3 | Bug UI inbox | dev queue normale |

## Voir aussi

- `DISASTER_RECOVERY.md` — incidents majeurs (6 scénarios DR détaillés)
- `ONBOARDING_TENANT.md` — provisioning nouveau tenant Meta KYC
- `HANDOFF.md` — état chantier dev en cours
- `.claude/rules/never-build-on-prod-server.md` — discipline production
- adminKlassci `CLAUDE.md` — architecture SaaS multi-tenant
