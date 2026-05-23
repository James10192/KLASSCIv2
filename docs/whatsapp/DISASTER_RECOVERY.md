# WhatsApp Disaster Recovery & Rollback runbook

> Phase 20 Plan v4 — Disaster recovery production-ready KLASSCI.

## Scénarios couverts

1. [Ban numéro Meta](#1-ban-numéro-meta)
2. [Templates massivement rejetés / rétractés](#2-templates-révoqués)
3. [Master API adminKlassci down](#3-master-api-down)
4. [Coût mensuel anormalement élevé](#4-coût-anormal)
5. [Plainte ARTCI Côte d'Ivoire](#5-plainte-artci)
6. [Données fuite (PII parents)](#6-fuite-pii)

---

## 1. Ban numéro Meta

**Symptômes** : Toutes les requêtes Cloud API retournent 401 / 403. Webhook ne reçoit plus d'updates. Dashboard delivery rate s'effondre à 0%.

**Détection** : 
- Circuit breaker s'ouvre automatiquement (5 échecs/60s — `PerTenantRateLimiter::status`)
- Alerte budget tenant si pas de delivery réussi en 24h
- KPI `parent_notification_logs.status='failed'` > 80% sur fenêtre 1h

**Mitigation immédiate (< 1h)** :
1. SuperAdmin adminKlassci → `whatsapp_enabled = false` sur tenant impacté
2. Cache invalidation : `TenantConfigResolver::invalidateCache()` côté tenant
3. Les autres canaux (email, SMS) continuent automatiquement (fallback gracieux dans MultiChannelDispatcher)
4. Annonce école parents via canal alternatif

**Recovery (30+ jours)** :
1. Soumettre appel à Meta : Business Manager → Aide → Mon compte est suspendu
2. **Souvent rejet appel** → préparer nouveau numéro :
   - Acheter nouvelle ligne dédiée (ne JAMAIS réutiliser ancien numéro)
   - Repasser Business Verification (5-15j Afrique)
   - Re-soumettre 6 templates (24-48h × 6 = 4-7j)
   - Re-configurer credentials adminKlassci
3. Notifier parents du changement de numéro WhatsApp officiel

**Préventif** (évite récidive) :
- Opt-in OBLIGATOIRE avant 1er envoi (table `whatsapp_opt_ins`)
- Footer "Répondez STOP" dans chaque template
- Respect strict fenêtre service 24h pour replies texte
- Monitoring opt-out rate quotidien (alerte si > 2% en 7j)

---

## 2. Templates révoqués

**Symptômes** : Templates qui marchaient → soudain rejetés "Marketing classification". Meta peut requalifier rétroactivement.

**Mitigation immédiate** :
1. SuperAdmin via Filament UI : marquer template comme `status=rejected`
2. NotificationDispatcher skip ce type proprement (autres canaux prennent le relai)
3. Revoir copy template avec biais Utility :
   - Pas d'urgence ("URGENT", "Dernière chance")
   - Pas de promo
   - Pas de "Cher client" / "Cher Monsieur" trop commercial
   - Préférer factuel : "Votre paiement de X FCFA a été validé"

**Recovery (3-7 jours)** :
1. Réécrire body template
2. Soumettre nouvelle version via WhatsApp Manager → Templates → Créer
3. Attendre approval 24-48h
4. Activer nouvelle version + désactiver ancienne dans `whatsapp_templates` table

---

## 3. Master API down

**Symptômes** : Tenant ne peut plus récupérer ses credentials WhatsApp. `TenantConfigResolver::fetchFromMaster()` timeout.

**Comportement** : Le resolver retourne `enabled: false` avec `reason: 'Master API unreachable'`. Les envois WhatsApp sont skipés proprement. Les autres canaux continuent.

**Mitigation** :
1. Vérifier https://admin.klassci.com/admin/tenant-health-checks
2. SSH master server : `systemctl status nginx php-fpm`
3. Redéployer si crash : depuis adminKlassci, `php artisan tenant:deploy admin --skip-backup` (sur lui-même)

**Préventif** :
- Cache 5 min côté tenant (réduit hits master)
- Health check toutes les 5 min côté master (notification critical Slack si down 10+ min)
- Fallback : SMS + email continuent même si master totalement down

---

## 4. Coût anormal

**Symptômes** : Dashboard cost adminKlassci montre dépassement budget tenant (> 200% baseline).

**Détection** : Cron quotidien compare `SUM(parent_notification_logs.cost_fcfa WHERE created_at > -24h)` vs `tenants.monthly_budget_fcfa / 30`.

**Mitigation** :
1. **Auto-suspension** : `whatsapp_enabled = false` automatique si > 150% budget mensuel atteint avant la fin du mois
2. Email + Slack alerte ops KLASSCI + admin école
3. Audit `parent_notification_logs` pour identifier le pattern aberrant :
   - Boucle infinie ? (même message_type 100× même parent)
   - Bug job queue ? (job dispatch 1000× au lieu de 1)
   - Spam externe ? (numéro tenant compromised)

**Recovery** :
1. Identifier cause root
2. Fix code si bug
3. Reset budget tenant manuel après accord école
4. Re-activate WhatsApp + monitoring renforcé 7 jours

---

## 5. Plainte ARTCI Côte d'Ivoire

**Symptômes** : Lettre officielle ARTCI suite plainte parent (spam, harcèlement, etc.).

**Mitigation immédiate** :
1. Identifier le numéro parent plaignant dans `parent_notification_logs`
2. Désactiver immédiatement `parent_notification_preferences.preferred_channels = ['app']` pour ce parent
3. Archiver historique audit (Auditable trait sur ParentNotificationLog) — preuve consent + STOP keyword respecté
4. Réponse formelle ARTCI sous 7 jours (délai légal)

**Préventif** :
- Politique opt-in documentée + signée à l'inscription (template écrit)
- Logs audit `whatsapp_opt_ins` immuables 5 ans
- Respecter STOP keyword sous 24h (gestion `MetaWhatsAppWebhookController::handleStopKeyword`)

---

## 6. Fuite PII

**Symptômes** : Suspicion d'accès non autorisé à `esbtp_parents.telephone` ou logs sensibles.

**Mitigation** (< 24h) :
1. Rotate tous les tokens API : `php artisan tenant:generate-token <tenant>` × 6
2. Rotate Meta access tokens (regénérer via Business Manager)
3. Audit `parent_notification_logs` accès récents
4. Notifier parents impactés (loi 2016-886 Côte d'Ivoire)

**Préventif** :
- Encrypted casts sur tous les credentials adminKlassci
- HTTPS only (Let's Encrypt auto-renew via cPanel)
- Rate limiting sur endpoints API (60 req/min/IP via Throttle middleware)
- Pas de PII dans les logs applicatifs (mask téléphone : `+225 07 ** ** ** 56`)
- Auditable trait sur tous les models Notification (immutable trail)

---

## Outils diagnostic

```bash
# Status circuit breaker tous tenants
klassci whatsapp:status --all

# Force close circuit breaker (post-recovery)
klassci whatsapp:reset-circuit <tenant>

# Audit logs sur 7 derniers jours
klassci logs:show <tenant> --filter=whatsapp --days=7

# Snapshot rate limiter usage
klassci whatsapp:rate-snapshot <tenant>
```

---

## Voir aussi

- `docs/whatsapp/ONBOARDING_TENANT.md` — procédure initiale
- `docs/whatsapp/RUNBOOK_OPS.md` — runbook ops quotidien
- `.claude/rules/multi-agent-git-safety.md` — discipline git production
- Plan v4 Phase 20 — Rollback strategies + disaster recovery
