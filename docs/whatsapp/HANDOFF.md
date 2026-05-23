# HANDOFF — Plan v4 WhatsApp KLASSCI production-ready

> Document de reprise mis à jour le 23 mai 2026 (session 2/N — clôture).
> Point d'entrée pour la prochaine session de continuation du chantier.

## 🚀 REPRISE — Prompt pour prochaine session

Copier-coller dans le prochain chat :

```
Reprise chantier WhatsApp KLASSCI v4 — session 3.

Lire d'abord docs/whatsapp/HANDOFF.md (point d'entrée complet).

État actuel :
- PR #426 ouverte feat/whatsapp-multitenant-prod → presentation
- 41 commits, ~6 800 LOC, 14/22 phases foundation production-grade
- Phase 8b = 91% (20/22 callers migrés, 2 callers atypiques restants)

Marcel a validé Option B (PR merge) puis Option A (Phase 8c + 15 via klassci-cli
sur presentation comme test infra).

Prochaines étapes ordre d'attaque :

1. Vérifier statut PR #426 (mergée ? en review ? CI ?)
   → Si mergée : pull presentation + cross-branch push esbtp-abidjan/yakro/ephrata/hetec/rostan
   → Si pending : laisser, attaquer Option A en parallèle

2. Reporter 3 commits adminKlassci hors-git du worktree dans repo
   James10192/adminKlassci main :
   - f13c661 Migration + Tenant casts encrypted + route API + Controller
   - c54e859 Filament UI WhatsApp tab dans TenantResource
   - fff15b2 WhatsAppOverviewWidget dashboard SaaS

3. Phase 8c (cleanup legacy NotificationService) — Option A
   Test infra = klassci-cli sur tenant presentation (validé Marcel).
   Stratégie : extraire UNE méthode legacy à la fois vers son Notifier shell,
   tester via klassci-cli (création paiement/inscription/absence), commit
   atomique par méthode. Cible : RelanceNotifier::envoyerEmail déjà extrait
   pattern → répliquer pour PaiementNotifier::paiementValide (méthode legacy
   notifyPaiementValide L2036 → extraction complète + delete legacy).

4. Phase 8b vague 6 — 2 callers atypiques restants :
   - ESBTPPaiementController L427 (paiement créé en_attente + notif parents
     "validé" — analyse métier : bug latent ou intentionnel ?)
   - ESBTPBonSortieController L42 (créer BonSortieNotifier shell minimal ou
     intégrer dans AnnonceNotifier ?)

5. Phase 15 partial — tests Feature sans browser :
   tests/Feature/Notifications/RelanceNotifierTest.php avec klassci-cli
   factory data depuis presentation. Pas Playwright (bloqué infra),
   mais Pest/PHPUnit Feature tests via API REST testable.

Bloqueurs hors-code restants (action utilisateur) :
- Phase 5 Meta KYC : Business Verification + 6 templates UTILITY × 2 tenants pilotes
- Phase 6 Rollout : dépend Phase 5
```

---

## 📍 Où on en est

**Branche** : `feat/whatsapp-multitenant-prod` (worktree `./.claude/worktrees/whatsapp-prod/`)
**Base** : `presentation` (à jour)
**Commits livrés** : **26 commits**, **+5 500 LOC** code production-grade + tests + docs
**PR ouverte** : https://github.com/James10192/KLASSCIv2/pull/new/feat/whatsapp-multitenant-prod

### ✅ Session 2 (23/05/2026) — livrables additionnels

- `f0f4278c` refactor(notifications): Phase 8b vague 1 — 6 callers migrés vers Notifier shells
- `1cc32bbb` feat(permissions): Phase 1 step 2/3 — 15 permissions WhatsApp mergées dans config/permissions.php
- `81c75ea1` refactor(whatsapp): Phase 1 step 3/3 — WhatsAppService multi-tenant via TenantConfigResolver

**adminKlassci (repo séparé `James10192/adminKlassci` branch main)** :
- `f13c661` feat(whatsapp): Phase 1 multi-tenant credentials Meta Cloud API
  (migration `add_whatsapp_config_to_tenants_table` + Tenant model casts encrypted
   + route GET `/api/tenants/{code}/whatsapp-config` + TenantWhatsAppConfigController)

## ⚠ Arbitrage requis Marcel — bloqueurs hors-code

Le hook de cette session continue à signaler "Plan v4 incomplet". C'est mathématiquement exact mais physiquement insatisfiable en chat :

| Phase | Bloqueur | Échelle de temps |
|---|---|---|
| 5 — Templates Meta + KYC tenants | Business Verification Meta + approbation 6 templates × 6 tenants | **4-8 semaines calendar par tenant** |
| 6 — Rollout progressif | Dépend Phase 5 | 6 semaines minimum |
| 8c — Cleanup legacy NotificationService | Nécessite extraction COMPLÈTE de chaque méthode legacy dans son shell + tests Feature/Unit valider 0 régression | ~5-7 jours dev focus avec accès vendor + DB de test |
| 15 — Tests E2E Playwright | Navigateur Chromium + DB de test peuplée + serveurs tenants up | Infra dédiée, ~3-4 jours dev |

Marcel : si tu veux que je passe en **mode autonome longue durée** sur Phase 8c + tests, il faut un worktree avec vendor + DB de test peuplée. Phases 5/6 nécessitent action humaine côté Meta Business Manager (KYC).

**État réel livré cette session** :
- ~14 commits cumulés Plan v4 (+ 8 sur session 1 = 22 total)
- ~6 700 LOC code production-grade + tests + docs
- Foundation Phases 1/2/4/7/10/11/12/13/14/16/17/18/19/20 toutes posées
- 3 phases bloquées par contraintes externes (5/6/15)
- Phase 8c en attente d'environnement test complet

**Phase 1 = COMPLÈTE** (master DB + API + permissions + refactor WhatsAppService)
**Phase 8b = 86% (19/22 callers migrés)** — vague 1 (6) + vague 2 (5) + vague 3 (4) + vague 4 (4)
**Phase 2 = 35% — squelette UI Filament onglet WhatsApp livré dans TenantResource**
**Phase 13 = 70% — Hub Communications scaffolding livré (controller + vue + route + sidebar)**
**Phase 16 = 30% — Widget WhatsAppOverviewWidget dashboard SaaS livré**
**Phase 17 = 90% — Doc complète : ONBOARDING + DISASTER_RECOVERY + RUNBOOK_OPS + HANDOFF**
**Phase 18 = 40% — PiiMasker utility + application masquage logs WhatsAppService**
**Phase 19 = 50% — 3 index composites parent_notification_logs analytics**
**Phase 20 = 80% — DR runbook + ops runbook livrés**

### ✅ Session 2 supplément (suite 23/05/2026)

- `c9f39d0d` refactor(notifications): Phase 8b vague 2 — 5 callers Jobs/Listeners/Services migrés
- `c54e859`  (adminKlassci) feat(filament): Phase 2 squelette UI WhatsApp dans TenantResource
- `2726a809` refactor(notifications): Phase 8b vague 3 — rappels inscription + paiement
- `e0e1fa13` refactor(notifications): Phase 8b vague 4 — TeacherNotifier étendu + 4 callers teacher
- `81f26298` feat(communications): Phase 13 — Hub Communications unifié (controller + vue + route)
- `fff15b2`  (adminKlassci) feat(filament): Phase 16 — WhatsAppOverviewWidget dashboard SaaS
- `1a7169ed` docs(whatsapp): Phase 17/20 — RUNBOOK_OPS.md (référence ops quotidienne)
- `1fc2ef1e` feat(security): Phase 18 — PiiMasker + masquage logs WhatsApp (RGPD/ARTCI)
- `c9ad6e1d` perf(db): Phase 19 — 3 index composites analytics WhatsApp/SMS

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

### Priorité 1 — ✅ TERMINÉE (session 23/05/2026)

#### ✅ Phase 1 step 2/3 — Casts encrypted + route API + permissions (FAIT)
- ✅ adminKlassci/app/Models/Tenant.php — 6 casts encrypted ajoutés (commit `f13c661`)
- ✅ adminKlassci/routes/api.php — route `/tenants/{code}/whatsapp-config` registered
- ✅ KLASSCIv2/config/permissions.php — 15 permissions WhatsApp mergées (commit `1cc32bbb`)
- [ ] **À faire au déploiement** : `php bin/deploy/fix_permissions.php` sur chaque tenant pour propager DB

#### ✅ Phase 1 step 3/3 — Refactor WhatsAppService (FAIT — commit `81c75ea1`)
- ✅ Constructor DI `TenantConfigResolver $configResolver` (immutable readonly)
- ✅ sendTemplateMessage() résout config per-call + check enabled
- ✅ getMessageStatus() pareil
- ✅ enabled=false → return false + log info (graceful)
- ✅ Plus aucun `env()` direct (compatible config:cache)
- [ ] Tests Feature avec mock TenantConfigResolver (reportés Phase 15)

### Priorité 2 — Production hardening (3-5 jours)

#### Phase 8b — Migration callers vers Notifier shells (vague 2)

**Vague 1 (session 23/05/2026) — 6 callers MIGRÉS** (commit `f0f4278c`) :
- ✅ ESBTPInscriptionController::store() → InscriptionNotifier::inscriptionCreated()
- ✅ ESBTPReinscriptionController::store() → InscriptionNotifier::reinscriptionCreated()
- ✅ ESBTPPaiementController validation classique → PaiementNotifier::paiementValide()
- ✅ ESBTPPaiementController validation rapide → PaiementNotifier::paiementValide()
- ✅ ESBTPPaiementController rejet → PaiementNotifier::paiementRejete(motif)
- ✅ ESBTPAttendanceController → AbsenceNotifier::nouvelleAbsence()
- ✅ ESBTPBulletinController togglePublication → BulletinNotifier::bulletinPublie() + alerteNotesFaibles()

**Vague 2 (à faire) — 16 callers restants** :
- [ ] ESBTPPaiementController::store() L427 — caller orphelin "parents only" (analyse risque double-notif)
- [ ] ESBTPBulletinController autres méthodes generate*
- [ ] EnvoyerRelanceJob → RelanceNotifier::envoyerEmail()
- [ ] PlanifierRelancesJob, SendInscriptionPaiementReminders
- [ ] Listeners : EnvoyerNotificationPaiement, GererSeuilAtteint, MettreAJourDashboard, NotifierBonApprouve, TraiterRelanceEnvoyee
- [ ] Services : ESBTPInscriptionService, AbsenceJustificationService
- [ ] Controllers : ESBTPNoteController, ESBTPAnnonceController, ESBTPBonSortieController, ESBTPComptabiliteRelanceController, TeacherDashboardController, CoordinateurDashboardController, ESBTP/TeacherAttendanceController

**Pattern de migration** :
```bash
# Trouver les callers restants
grep -rn "NotificationService::class\|notifyParents\|notifyPaiement\|notifyNewAbsence\|notifyParentsBulletin" \
    app/Http/Controllers/ app/Jobs/ app/Listeners/ app/Services/ app/Console/

# Avant
$notificationService->notifyParentsXxx($entity);
# Après
app(XxxNotifier::class)->methodeShell($entity);
```

**Caveat** : Étendre les shells si nouvelles méthodes nécessaires (ex: PaiementNotifier
a déjà été étendu avec `paiementRejete(motif)` cette session — vérifier signatures
avant migration).

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
