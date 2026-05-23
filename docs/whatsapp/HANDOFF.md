# HANDOFF — Plan v4 WhatsApp KLASSCI production-ready

> Document de reprise après session du 23 mai 2026. À utiliser comme point d'entrée
> pour la prochaine session de continuation du chantier.

## 📍 Où on en est

**Branche** : `feat/whatsapp-multitenant-prod` (worktree `./.claude/worktrees/whatsapp-prod/`)
**Base** : `presentation` (à jour)
**Commits livrés** : 14 commits, **+4 000 LOC** code production-grade + tests + docs
**PR ouverte** : https://github.com/James10192/KLASSCIv2/pull/new/feat/whatsapp-multitenant-prod

### Commits chronologiques

```
e52d0491 feat(whatsapp): Phase 10 ChatbotGeminiService + Phase 7/18 permissions registry
ee400bf5 feat(whatsapp): Phase 16 WhatsAppMetricsService + Phase 7 PhoneToParentResolver
6a3c9a2d docs(whatsapp): Phase 17 onboarding + Phase 20 disaster recovery runbooks
4c2d7c14 feat(whatsapp): Phase 9 SmsDispatcher + BeemSmsProvider + Phase 14 SendWhatsAppMessageJob
4fa9a45d feat(whatsapp): Phase 9 SmsProviderInterface + Phase 12 WaveCheckoutLinkBuilder
067ae619 feat(whatsapp): Phase 4 hardening PerTenantRateLimiter + CircuitBreaker + Exception
5ae073e5 feat(whatsapp): Phase 7 schema chat 2-way (migrations + models Auditable)
c7c26ca0 feat(whatsapp): Phase 3 webhook Meta + HMAC signature middleware
032f3ec3 feat(whatsapp): Phase 1 TenantConfigResolver — config via API master + cache
3f0436de refactor(notifications): 7 notifier shells strangler fig
e2aa2e9c refactor(notifications): extract MultiChannelDispatcher orchestrator
20825cdf refactor(notifications): foundation strangler fig — extract RelanceNotifier
```

## 🗂️ Architecture en place

```
app/
├── Domain/Notifications/
│   ├── Contracts/
│   │   ├── NotifierInterface.php ✅
│   │   ├── NotificationResult.php ✅ (DTO immutable readonly PHP 8.2)
│   │   └── SmsProviderInterface.php ✅
│   ├── AbstractNotifier.php ✅ (helpers destinatairesFor + logDispatch + safeExecute)
│   ├── MultiChannelDispatcher.php ✅ (Email/WA/SMS orchestrator)
│   └── Notifiers/
│       ├── RelanceNotifier.php ✅ COMPLET extrait
│       ├── InscriptionNotifier.php ⚠️ SHELL délégation legacy
│       ├── PaiementNotifier.php ⚠️ SHELL délégation legacy
│       ├── AbsenceNotifier.php ⚠️ SHELL délégation legacy
│       ├── BulletinNotifier.php ⚠️ SHELL délégation legacy
│       ├── AnnonceNotifier.php ⚠️ SHELL délégation legacy
│       ├── SystemNotifier.php ⚠️ SHELL délégation legacy
│       └── TeacherNotifier.php ⚠️ SHELL délégation legacy
├── Services/
│   ├── WhatsAppService.php ⚠️ LEGACY (env() direct) — refactor pending
│   ├── WhatsApp/
│   │   ├── TenantConfigResolver.php ✅ (cache 5min + fallback gracieux)
│   │   ├── PerTenantRateLimiter.php ✅ (tiers Meta 1K/10K/100K)
│   │   ├── CircuitBreaker.php ✅ (5 échecs/60s → open 5min)
│   │   ├── RateLimitExceededException.php ✅
│   │   ├── WaveCheckoutLinkBuilder.php ✅ (Phase 12)
│   │   ├── WhatsAppMetricsService.php ✅ (KPIs dashboard)
│   │   ├── PhoneToParentResolver.php ✅ (Phase 7 routing)
│   │   └── Chatbot/
│   │       └── ChatbotGeminiService.php ✅ (Phase 10 IA)
│   └── Sms/
│       ├── SmsDispatcher.php ✅ (cascade fallback)
│       └── Providers/
│           └── BeemSmsProvider.php ✅
├── Http/
│   ├── Controllers/Webhooks/
│   │   └── MetaWhatsAppWebhookController.php ✅ (verify + statuses + inbound)
│   └── Middleware/
│       └── VerifyMetaWebhookSignature.php ✅ HMAC SHA-256
├── Jobs/
│   └── SendWhatsAppMessageJob.php ✅ (queue 'whatsapp' + retry exp)
├── Models/
│   ├── WhatsAppInboundMessage.php ✅ Auditable
│   └── WhatsAppOutboundReply.php ✅ Auditable
└── Providers/
    └── NotificationDomainServiceProvider.php ✅ (9 singletons + tag)

database/migrations/
├── 2026_05_23_174500_create_whatsapp_inbound_messages_table.php ✅
└── 2026_05_23_174501_create_whatsapp_outbound_replies_table.php ✅

config/
├── app.php ✅ (NotificationDomainServiceProvider registered)
└── permissions-whatsapp.php ✅ (13 permissions, à merger dans permissions.php)

docs/whatsapp/
├── ONBOARDING_TENANT.md ✅ (procédure Meta KYC 5-15j)
└── DISASTER_RECOVERY.md ✅ (6 scénarios DR)

tests/Unit/Domain/Notifications/
├── NotificationResultTest.php ✅ (6 tests factories + readonly)
└── RelanceNotifierContractTest.php ✅ (9 tests contract)

[hors-git, à committer dans James10192/adminKlassci]
adminKlassci/
├── database/migrations/2026_05_23_174008_add_whatsapp_config_to_tenants_table.php ✅
└── app/Http/Controllers/API/TenantWhatsAppConfigController.php ✅
```

## 🎯 Phases restantes — ordre d'attaque recommandé

### Priorité 1 — Bloque tout le reste (3-4 jours)

#### Phase 1 step 2/3 — Casts encrypted + route API + permission
**Repo** : adminKlassci + KLASSCIv2
- [ ] adminKlassci/app/Models/Tenant.php — ajouter `protected $casts = ['whatsapp_phone_number_id' => 'encrypted', 'whatsapp_access_token' => 'encrypted', ...]`
- [ ] adminKlassci/routes/api.php — ajouter `Route::get('/tenants/{code}/whatsapp-config', [TenantWhatsAppConfigController::class, 'show'])->middleware('tenant.api')`
- [ ] KLASSCIv2/config/permissions.php — merger `config/permissions-whatsapp.php` (13 permissions)
- [ ] KLASSCIv2/bin/deploy/fix_permissions.php run pour propager

#### Phase 1 step 3/3 — Refactor WhatsAppService legacy
**Fichier** : `app/Services/WhatsAppService.php`
- [ ] Remplacer `__construct() { $this->phoneNumberId = env(...) }` par injection DI `TenantConfigResolver $configResolver`
- [ ] Toutes les méthodes `send*Notification` : vérifier `$this->configResolver->getConfig()['enabled']` avant envoi
- [ ] Si disabled → return false + log info (pas throw)
- [ ] sendTemplateMessage() utilise `$config['phone_number_id']` + `$config['access_token']` à chaque appel
- [ ] Tests Feature avec mock TenantConfigResolver

### Priorité 2 — Production hardening (3-5 jours)

#### Phase 8b — Migration callers vers Notifier shells
**Pattern** : grep callers existants + remplacer par DI Notifier
```bash
# Trouver les callers
grep -rn "app(NotificationService::class)" app/Http/Controllers/ app/Jobs/
grep -rn "new NotificationService" app/

# Migration par caller :
# AVANT
app(NotificationService::class)->notifyParentsInscriptionCreated($i, $c);
# APRÈS
app(InscriptionNotifier::class)->inscriptionCreated($i, $c);
```
- [ ] ESBTPInscriptionController → InscriptionNotifier
- [ ] ESBTPPaiementController (3 callers) → PaiementNotifier
- [ ] ESBTPAttendanceController → AbsenceNotifier
- [ ] ESBTPBulletinController (2 callers) → BulletinNotifier
- [ ] EnvoyerRelanceJob → RelanceNotifier::envoyerEmail()
- [ ] ~15 autres callers (controllers + jobs)
- [ ] Tests Feature après chaque migration

#### Phase 4 step 2/2 — Intégration hardening dans WhatsAppService
- [ ] Wrap `sendTemplateMessage()` avec `try { $rateLimiter->check(); $breaker->isOpen() }`
- [ ] Increment + recordSuccess/recordFailure
- [ ] Bounce auto-disable per-recipient : table `whatsapp_bounces` + listener

### Priorité 3 — Features utilisateur (8-12 jours)

#### Phase 7 step 2/2 — UI Inbox premium
**Namespace CSS** : `wi-*` (cf rule premium-redesign.md)
- [ ] app/Http/Controllers/ESBTPWhatsAppInboxController.php — index, show, reply
- [ ] app/Services/WhatsApp/WhatsAppReplyService.php — POST /messages Meta + fenêtre 24h gestion
- [ ] resources/views/esbtp/communications/whatsapp-inbox/index.blade.php — liste threads + filtres
- [ ] resources/views/esbtp/communications/whatsapp-inbox/show.blade.php — conversation timeline + reply form
- [ ] resources/views/components/wa-conversation.blade.php — composant Blade timeline
- [ ] AJAX no-reload (cf rule ajax-no-reload-premium.md)
- [ ] Countdown 24h Meta visible UI (vert/jaune/rouge)
- [ ] routes/web.php — esbtp.communications.whatsapp-inbox.* avec permission middleware
- [ ] Tests Feature complete flow

#### Phase 10 step 2/2 — Intégration ChatbotGeminiService
- [ ] App/Jobs/ProcessInboundMessageJob.php — déclenché par webhook, appelle ChatbotGemini
- [ ] Si confidence >= threshold → auto-reply via WhatsAppReplyService
- [ ] Si escalate → assignation auto secrétaire UI
- [ ] Tool implementations : get_solde_paiement, get_nb_absences_mois, get_derniere_note_publiee
- [ ] UI review queue (whatsapp.chatbot.review permission)
- [ ] Tests sandbox Gemini

#### Phase 11 — FaqRouter intelligent
- [ ] app/Services/WhatsApp/FaqRouter.php — pattern matching > IA pour FAQ frequents
- [ ] Table whatsapp_faq_patterns (pattern regex, response_template, intent)
- [ ] UI tenant pour gérer ses FAQ patterns

#### Phase 12 step 2/2 — Wave webhook + UI settings
- [ ] app/Http/Controllers/Webhooks/WaveWebhookController.php — POST /api/webhooks/wave
- [ ] Auto-mark paiement.status='validé' + dispatch PaiementNotifier::paiementValide
- [ ] UI tenant /esbtp/settings/paiement section Wave (merchant_id + enabled toggle)

#### Phase 13 — Hub Communications unifié
- [ ] app/Http/Controllers/ESBTPCommunicationsHubController.php
- [ ] resources/views/esbtp/communications/index.blade.php — tabs (annonces / messages app / WhatsApp inbox / chatbot review)
- [ ] Sidebar nouvelle catégorie "Communications" (rule sidebar-permissions.md)

### Priorité 4 — UI admin + Tests + Monitoring (6-8 jours)

#### Phase 2 — UI Filament adminKlassci
- [ ] adminKlassci/app/Filament/Resources/TenantResource.php — section WhatsApp (5 champs encrypted)
- [ ] adminKlassci/app/Filament/Resources/WhatsAppTemplateResource.php (Phase 2)
- [ ] adminKlassci/app/Console/Commands/WhatsAppTemplatesSyncCommand.php
- [ ] Filament Widget WhatsAppCostDashboard (consume WhatsAppMetricsService)
- [ ] Filament Widget WhatsAppHealthOverview

#### Phase 15 — Tests E2E Playwright
- [ ] tests/e2e/whatsapp/inscription-confirm.spec.ts
- [ ] tests/e2e/whatsapp/paiement-valide.spec.ts
- [ ] tests/e2e/whatsapp/absence-notification.spec.ts
- [ ] tests/e2e/whatsapp/bulletin-publie.spec.ts
- [ ] tests/e2e/whatsapp/chat-2way-reply.spec.ts
- [ ] tests/e2e/whatsapp/opt-out-stop.spec.ts

#### Phase 16 step 2/2 — Dashboard + alertes Slack
- [ ] adminKlassci/app/Filament/Widgets/WhatsAppMetricsWidget.php (charts delivery rate, cost FCFA)
- [ ] Notification SlackChannel sur dégradation KPIs
- [ ] Health check WhatsApp dans tenant:health-check command

### Priorité 5 — Quality gates (3-4 jours)

#### Phase 8c — Cleanup NotificationService god-class
- [ ] Quand tous les callers migrés (Phase 8b) : supprimer méthodes déléguées de NotificationService
- [ ] Si vide → suppression complète du fichier
- [ ] Update memory/MEMORY.md + memory/feedback_god_code_eliminated.md

#### Phase 18 — Security audit OWASP
- [ ] OWASP Top 10 review sur tous nouveaux endpoints
- [ ] Verify HMAC webhook signature timing-safe (déjà fait via hash_equals)
- [ ] PII handling : log mask téléphones `+225 07 ** ** ** 56`
- [ ] Audit immutable (Auditable trait sur tous models notification)
- [ ] Rate limit Throttle middleware sur routes sensibles
- [ ] Tests d'intrusion : signature spoofing, replay attack, idempotency bypass

#### Phase 19 — Performance optimization
- [ ] Audit N+1 sur ESBTPWhatsAppInboxController via Laravel Telescope
- [ ] DB indices : parent_notification_logs (composite tenant+created_at), whatsapp_inbound_messages (status+received_at)
- [ ] Cache strategy : 5min config, 1min health, persistant cost
- [ ] Horizon queue tuning (workers 'whatsapp' queue)

### Hors-code calendar (parallèle, 4-8 semaines)

#### Phase 5 — Templates Meta + Onboarding KYC tenants
**Suivre docs/whatsapp/ONBOARDING_TENANT.md** pour chaque tenant :
- [ ] presentation (sandbox test KLASSCI ops)
- [ ] esbtp-yakro (1er Élite pilote volontaire)
- [ ] esbtp-abidjan (2e Élite)
- [ ] ephrata (Partenaire)
- [ ] hetec (Test → Élite)
- [ ] rostan (Test → Élite)

Pour chacun :
- [ ] Création Meta Business Manager
- [ ] Business Verification (5-15j calendar)
- [ ] 6 templates UTILITY soumis + approuvés (~30% rejection first-pass)
- [ ] Numéro WhatsApp dédié provisionné
- [ ] Credentials configurés dans adminKlassci

#### Phase 6 — Rollout progressif (1 par semaine)
- [ ] Activation presentation soft launch 3j
- [ ] esbtp-yakro 1 semaine pilote
- [ ] Monitoring quotidien (delivery rate, opt-out, plaintes)
- [ ] Rollout les 4 autres tenants à 1 par semaine

## 🚀 Pour reprendre rapidement

```bash
# 1. Reprendre dans le worktree
cd C:/Users/yabla/Downloads/dev/klassciv2/.claude/worktrees/whatsapp-prod
git log --oneline | head -15

# 2. Vérifier l'état + tâches
# (TaskList montre 22 tâches dont ~11 in_progress)

# 3. Prochaine session — prompt suggéré :
# "Reprends le chantier WhatsApp v4. Lire docs/whatsapp/HANDOFF.md.
#  Priorité 1 : Phase 1 step 2/3 (casts encrypted Tenant model + route API
#  adminKlassci + merger config/permissions-whatsapp.php dans permissions.php)."
```

## 🔑 Décisions techniques figées (ne pas revisiter)

- **1 numéro WhatsApp par tenant** (décision Marcel 23/05/2026) — pas de numéro KLASSCI partagé
- **Meta direct, pas BSP** (360Dialog rejeté) — $0.0040 USD/msg Utility "Rest of Africa" 2026
- **Strangler fig pattern** pour NotificationService god-class — pas big bang
- **Cache 5min côté tenant** pour TenantConfigResolver (pattern PaywallMiddleware)
- **Fallback gracieux** : si WhatsApp disabled/down, email + SMS continuent
- **Opt-in obligatoire** + footer STOP dans tous templates Meta
- **MultiChannelDispatcher partagé** entre tous les notifiers parents
- **Notifier shells délégation legacy** acceptable pendant Phase 8a→8b
- **Pas de mock SMS** — Phase 9 réelle (réactivation Orange ou Beem)
- **adminKlassci hors-git du worktree** — fichiers Phase 1 à committer dans James10192/adminKlassci

## 🔍 Bugs / risques connus

- `WhatsAppService.php` legacy utilise `env()` direct — cassé sous `config:cache` en prod. Refactor Phase 1 step 3 obligatoire avant rollout.
- 7 notifier shells délèguent au NotificationService legacy — créera double-log si NotificationService modifié sans coordination
- Migration `whatsapp_inbound_messages` référence `esbtp_parents.id` — vérifier que la table existe sur tous tenants avant migrate
- Pricing FCFA dans MultiChannelDispatcher hardcodés (2.4 + 7) — à externaliser en settings tenant (Phase 4 budget alerts)
- Permission `module.whatsapp.access` créée mais pas encore mergée dans config/permissions.php principal

## 📚 Voir aussi

- `.claude/rules/adminklassci-tenant-management.md` — architecture multi-instance (créée cette session)
- `docs/whatsapp/ONBOARDING_TENANT.md` — procédure setup tenant Meta
- `docs/whatsapp/DISASTER_RECOVERY.md` — runbook DR 6 scénarios
- Plan v4 dans message Iteration 5 de la session précédente — détails exhaustifs des 22 phases
- TaskList — 22 tâches trackées (statuts in_progress / pending)
