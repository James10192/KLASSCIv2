# Rule: Clôture mensuelle/périodique configurable par tenant

## Quand s'active

Cette rule s'active automatiquement quand tu :
- Implémentes la **feature clôture mensuelle / périodique** (mensuelle, trimestrielle, annuelle)
- Touches au `comptabilite.period_locked_until` setting
- Modifies la garde `ESBTPPaiementController::assertPeriodNotLocked()`
- Crées des routes sous `/esbtp/comptabilite/cloture` ou `/esbtp/comptabilite/period`
- Ajoutes des modèles `PeriodClosure`, `PeriodClosureRule`, `ClosureSnapshot`
- Touches au workflow paiement validé / rejeté / en_attente lors d'une fermeture

## Pourquoi cette rule existe

**Demande métier Marcel (2026-06-04)** après audit comptable :

> « Bon je n'en ai pas entendu parler [d'un workflow clôture] mais si tu trouves que ça
> peut être une feature killer alors fais le on le fera entièrement configurable pour
> rendre ça flexible pour chaque tenant. »

L'audit a révélé sur yakro :
- **4 paiements `en_attente` sur 2024-2025** (année passée, 600 000 FCFA) → jamais résolus
- **5 paiements `en_attente` depuis > 7 jours** → workflow validation lent
- **18 paiements montant = 0 FCFA** dont plusieurs validés → ambiguïté métier
- **Aucune période verrouillée** (`period_locked_until = null`) → modifications libres rétroactives

La clôture périodique est la feature qui force la résolution avant transition, donne une **photo comptable à un instant T** (immuable, archivable), et active automatiquement le verrouillage anti-modification rétroactive.

KLASSCI a déjà :
- `comptabilite.period_locked_until` (setting tenant)
- `ESBTPPaiementController::assertPeriodNotLocked()` (garde)
- Permission `comptabilite.period.bypass_lock`

Manque :
- **Workflow de clôture** (pas juste un setting manuel)
- **Configurabilité par tenant** (mensuelle vs trimestrielle vs annuelle, jour de cutoff, rappels)
- **Vérifications pré-clôture** (paiements en_attente à résoudre, écarts caisse, etc.)
- **Snapshot post-clôture** (KPIs gelés pour archive)
- **Réouverture exceptionnelle** avec audit log

## Architecture cible

```
app/Domain/Comptabilite/PeriodClosure/
├── Models/
│   ├── PeriodClosure.php             // 1 instance = 1 clôture (mois/trim/annuelle)
│   ├── PeriodClosureRule.php         // règle de clôture par tenant
│   ├── ClosureSnapshot.php           // KPIs gelés (JSON dense)
│   └── ClosurePrecheck.php           // état des vérifications pré-clôture
├── Services/
│   ├── PeriodClosureService.php      // open / precheck / close / reopen
│   ├── ClosureSnapshotService.php    // génère le snapshot KPI complet
│   └── PrecheckRunnerService.php     // exécute les vérifications
├── Actions/
│   ├── ClosePeriod.php
│   ├── ReopenPeriod.php
│   └── GenerateClosurePV.php
├── Events/
│   ├── PeriodClosureOpened.php       // début workflow
│   ├── PeriodClosed.php              // verrouillage actif
│   └── PeriodReopened.php            // déverrouillage (rare)
└── Listeners/
    └── UpdateAcademicYearLockSetting.php  // sync comptabilite.period_locked_until
```

## Schéma DB

```sql
-- Règles de clôture (1 row par tenant, configurable)
CREATE TABLE period_closure_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    frequency ENUM('monthly','quarterly','semester','annual') NOT NULL DEFAULT 'monthly',
    cutoff_day TINYINT NOT NULL DEFAULT 5,        -- jour du mois suivant pour clôturer le mois précédent
    auto_close BOOLEAN DEFAULT FALSE,              -- si true, clôture auto au cutoff_day
    require_review BOOLEAN DEFAULT TRUE,           -- exiger une 2e validation
    require_no_pending BOOLEAN DEFAULT TRUE,       -- bloquer si paiements en_attente sur la période
    require_no_zero_amount BOOLEAN DEFAULT FALSE,  -- bloquer si paiements montant=0 sur la période
    require_cash_reconciliation BOOLEAN DEFAULT FALSE,  -- exiger réconciliation caisse
    notify_users JSON NULL,                        -- ids des users à notifier (rappels J-3, J-1, J)
    grace_period_days TINYINT DEFAULT 7,           -- nb jours après cutoff pour clôturer manuellement
    created_at TIMESTAMP, updated_at TIMESTAMP
);

-- Clôtures effectuées (1 row par période close)
CREATE TABLE period_closures (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE,                       -- ex: CLO-2026-05 (mensuel) ou CLO-2026-T1 (trim)
    frequency ENUM('monthly','quarterly','semester','annual') NOT NULL,
    annee_universitaire_id BIGINT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,                      -- date de coupure incluse (modifie comptabilite.period_locked_until)
    status ENUM('draft','precheck_running','precheck_failed','ready','reviewed','closed','reopened') DEFAULT 'draft',
    opened_by BIGINT NOT NULL,
    opened_at TIMESTAMP NOT NULL,
    closed_by BIGINT NULL,
    closed_at TIMESTAMP NULL,
    reviewed_by BIGINT NULL,
    reviewed_at TIMESTAMP NULL,
    reopened_by BIGINT NULL,
    reopened_at TIMESTAMP NULL,
    reopen_reason TEXT NULL,
    pv_pdf_path VARCHAR(255) NULL,
    snapshot_id BIGINT NULL,                       -- lien vers ClosureSnapshot
    notes TEXT NULL,
    created_at TIMESTAMP, updated_at TIMESTAMP, deleted_at TIMESTAMP NULL,
    INDEX (annee_universitaire_id, period_start, period_end),
    INDEX (status)
);

-- Snapshots KPI gelés (1 par closure, JSON dense)
CREATE TABLE closure_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    period_closure_id BIGINT NOT NULL,
    kpis JSON NOT NULL,                            -- TOUS les KPIs dashboard à T = close_at
    payments_summary JSON NOT NULL,                -- breakdown par mode + status + filière + classe
    cash_balances JSON NULL,                       -- soldes caisse par mode si réconciliation activée
    impayes_aging JSON NOT NULL,                   -- aging buckets à T
    risk_distribution JSON NULL,                   -- buckets risk si analytics activé
    audit_hash VARCHAR(64) NOT NULL,               -- hash SHA256 pour intégrité
    created_at TIMESTAMP,
    FOREIGN KEY (period_closure_id) REFERENCES period_closures(id) ON DELETE CASCADE
);

-- Pré-checks (1 ou plusieurs par closure)
CREATE TABLE closure_prechecks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    period_closure_id BIGINT NOT NULL,
    check_type VARCHAR(64) NOT NULL,               -- 'no_pending_payments', 'no_zero_amount', 'cash_reconciled', etc.
    status ENUM('pending','passed','failed','warning','skipped') DEFAULT 'pending',
    details JSON NULL,                             -- liste IDs incriminés, montants, etc.
    executed_at TIMESTAMP NULL,
    FOREIGN KEY (period_closure_id) REFERENCES period_closures(id) ON DELETE CASCADE
);
```

## Workflow

```
1. OUVERTURE
   - Auto au cutoff_day (si rule.auto_close=true) OU manuel par comptable
   - status = 'draft'
   - Code généré: CLO-{ANNEE}-{PERIODE} (ex: CLO-2026-05, CLO-2026-T1, CLO-2026)
   - period_start/period_end calculés selon frequency

2. PRECHECKS (asynchrone)
   - status = 'precheck_running'
   - Exécute toutes les vérifications activées dans rule :
     a. no_pending_payments    : 0 paiement en_attente sur période
     b. no_zero_amount         : 0 paiement montant=0 sur période (si rule.require_no_zero_amount)
     c. cash_reconciled        : session réconciliation 'approved' sur période
     d. no_overdue_validation  : 0 paiement validation en attente > 7j
   - Sur échec : status='precheck_failed', notification utilisateur, blocage
   - Sur succès : status='ready'

3. REVIEW (si rule.require_review=true)
   - Comptable habilité valide la closure
   - status = 'reviewed'
   - reviewed_by/at horodatés

4. CLÔTURE
   - status = 'closed'
   - closed_by/at horodatés
   - Génère ClosureSnapshot (snapshot KPI complet, hash SHA256)
   - Génère PV PDF signable
   - Update setting comptabilite.period_locked_until = period_end
   - Verrouille paiements de la période (assertPeriodNotLocked rejette)
   - Event PeriodClosed dispatched

5. RÉOUVERTURE (RARE, audit log requis)
   - Permission requise: comptabilite.cloture.bypass_close
   - reopen_reason obligatoire (texte ≥ 30 chars)
   - status passe à 'reopened'
   - Setting period_locked_until reset à la dernière clôture antérieure non réouverte
   - Event PeriodReopened dispatched + Log::warning permanent

6. ARCHIVAGE
   - Soft-delete uniquement
   - Rétention 10 ans (OHADA)
   - Snapshots immuables
```

## Permissions à ajouter

```php
'comptabilite.cloture.view' => [
    'label' => 'Voir les clôtures comptables',
    'group' => 'Comptabilité — Clôture',
    'icon' => 'fa-lock',
],
'comptabilite.cloture.configure' => [
    'label' => 'Configurer les règles de clôture',
    'group' => 'Comptabilité — Clôture',
    'icon' => 'fa-gear',
],
'comptabilite.cloture.open' => [
    'label' => 'Ouvrir une clôture',
    'group' => 'Comptabilité — Clôture',
    'icon' => 'fa-folder-open',
],
'comptabilite.cloture.review' => [
    'label' => 'Valider une clôture (2e validation)',
    'group' => 'Comptabilité — Clôture',
    'icon' => 'fa-check-double',
],
'comptabilite.cloture.close' => [
    'label' => 'Effectuer la clôture finale',
    'group' => 'Comptabilité — Clôture',
    'icon' => 'fa-lock',
],
'comptabilite.cloture.reopen' => [
    'label' => 'Rouvrir une clôture (rare)',
    'group' => 'Comptabilité — Clôture',
    'icon' => 'fa-unlock',
],
```

Defaults :
- `view` à `comptable + coordinateur + superAdmin`
- `configure + open` à `comptable + superAdmin`
- `review` à `coordinateur + superAdmin` (séparation des devoirs : pas `comptable` qui a ouvert)
- `close` à `superAdmin` uniquement
- `reopen` à `superAdmin` uniquement (action exceptionnelle, audit dense)

## Configurabilité par tenant

Toutes les options sont dans `period_closure_rules` (1 row par tenant) :
- `frequency` : monthly / quarterly / semester / annual
- `cutoff_day` : 1 à 28 (jour du mois suivant pour clôturer)
- `auto_close` : true/false (clôture auto sans intervention humaine)
- `require_review` : true/false (séparation des devoirs)
- `require_no_pending` : true/false (force la résolution des en_attente)
- `require_no_zero_amount` : true/false (force la résolution des 0 FCFA)
- `require_cash_reconciliation` : true/false (lien avec rule `reconciliation-paiements-caisse`)
- `notify_users` : JSON array des user_id à notifier
- `grace_period_days` : nb jours après cutoff pour clôturer manuellement avant blocage

**Exemples de profiles tenants** :

| Tenant | Profil suggéré |
|---|---|
| `esbtp-yakro` | monthly, cutoff=5, auto_close=false, require_review=true, require_no_pending=true, require_no_zero_amount=true |
| `esbtp-abidjan` | monthly, cutoff=5, auto_close=true (gros volume), require_no_pending=true |
| `presentation` | annual, auto_close=false (env de démo) |
| `ephrata` | quarterly (petite école), auto_close=false |

## Règles absolues

1. **JAMAIS clôturer si require_no_pending=true et il reste des en_attente sur la période** : précheck doit échouer.
2. **JAMAIS clôturer sans snapshot** : `ClosureSnapshot` est OBLIGATOIRE (preuve immuable).
3. **JAMAIS réouvrir sans motif texte ≥ 30 chars + Log::warning permanent**.
4. **JAMAIS hard-delete `period_closures` ou `closure_snapshots`** : soft-delete uniquement.
5. **JAMAIS modifier `closure_snapshots.kpis` après création** : immuable, hash SHA256 vérifié.
6. **JAMAIS dispatcher `PeriodClosed` sans avoir update `comptabilite.period_locked_until`** : sync atomique requise.
7. **JAMAIS clôturer une période postérieure sans avoir clôturé la précédente** : chaîne ordonnée stricte.
8. **JAMAIS un même utilisateur qui ouvre ET review ET close** : séparation des devoirs OHADA.

## API REST attendue

```
GET    /esbtp/comptabilite/cloture/rule              Show règle tenant
PUT    /esbtp/comptabilite/cloture/rule              Update règle (permission configure)
GET    /esbtp/comptabilite/cloture                   List clôtures
POST   /esbtp/comptabilite/cloture                   Open nouvelle clôture
GET    /esbtp/comptabilite/cloture/{id}              Show clôture
POST   /esbtp/comptabilite/cloture/{id}/run-prechecks  Re-run prechecks
POST   /esbtp/comptabilite/cloture/{id}/review       Validate (status → reviewed)
POST   /esbtp/comptabilite/cloture/{id}/close        Close final (status → closed)
POST   /esbtp/comptabilite/cloture/{id}/reopen       Reopen (rare)
GET    /esbtp/comptabilite/cloture/{id}/snapshot     Show KPI snapshot
GET    /esbtp/comptabilite/cloture/{id}/pv           Download PV PDF
```

## UI premium (namespace `clo-*`)

- **Hero gradient** : icône `fa-lock`, KPIs : prochaine clôture (date), périodes ouvertes, périodes fermées
- **Page index** : tableau clôtures, badge status color-coded, drilldown
- **Page show** : 3 onglets
  - Tab 1 « Prechecks » : liste vérifications + détails sur échecs
  - Tab 2 « Snapshot KPIs » : tous les KPIs gelés (gros tableau ou cards)
  - Tab 3 « Actions » : review / close / reopen / download PV
- **Wizard ouverture** : choix période + checklist precheck en live
- **Bannière sticky** sur dashboard compta : « Clôture mai 2026 en attente — J-3 avant cutoff »

## Synergie avec autres features

- **Réconciliation caisse** (rule `reconciliation-paiements-caisse.md`) : si `require_cash_reconciliation=true`, exiger session approuvée pour la période avant clôture.
- **Verrouillage `comptabilite.period_locked_until`** : mis à jour automatiquement par event `PeriodClosed`.
- **Analytics** : snapshot des KPIs permet de comparer mois après mois (graphes évolution).
- **Audit comptable** : index `period_closures` + `closure_snapshots` pour trail global.
- **Exports OHADA** : PV de clôture génère le rapport légal pour archivage 10 ans.

## Anti-patterns à BLOQUER en review

1. ❌ Clôture sans précheck (skip prechecks via flag) — interdit sauf permission superAdmin spéciale
2. ❌ Snapshot manqué — closure incomplete impossible à archiver
3. ❌ Update direct de `comptabilite.period_locked_until` sans passer par PeriodClosure
4. ❌ Réouverture sans `reopen_reason` ou < 30 chars
5. ❌ Modifier `closure_snapshots.kpis` après création
6. ❌ Hard-delete soft-deletable models
7. ❌ Réutiliser code de calcul KPI sans le geler dans snapshot (race condition)
8. ❌ Permettre `auto_close=true` sans `require_review=false` (logique incohérente)
9. ❌ Clôture chevauchante (2 closures avec period_end identiques)
10. ❌ Notification utilisateur sans logger l'envoi (perte trace en cas de litige)

## Voir aussi

- `.claude/rules/reconciliation-paiements-caisse.md` — feature sœur, complémentaire
- `.claude/rules/no-god-code-compta.md` — Domain extraction
- `.claude/rules/permissions.md` — convention noms `comptabilite.cloture.*`
- `.claude/rules/customizable-roles.md` — pas de rôle hardcodé, tout via permissions
- `.claude/rules/exports-pdf-excel.md` — PV via `<x-pdf-document>` + `ExportableReport`
- `app/Http/Controllers/ESBTPPaiementController.php::assertPeriodNotLocked()` — pattern existant
- `docs/audits/2026-06-04-audit-comptable-klassci.md` — audit déclencheur
- Norme OHADA : rétention 10 ans, séparation comptable/approbateur, immutabilité des snapshots
