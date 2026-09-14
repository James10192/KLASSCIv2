# Rule: Réconciliation paiements ↔ caisse physique

## Quand s'active

Cette rule s'active automatiquement quand tu :
- Implémentes une feature de **réconciliation comptable** (rapprocher paiements enregistrés vs caisse physique réelle)
- Modifies les méthodes `update()` / `validate()` / `cancel()` du `ESBTPPaiementController`
- Touches au `ESBTPJournalCaisseController` ou au modèle `ESBTPPaiement`
- Crées des modèles `CashClosure`, `Reconciliation`, `CashCount`, `CashDiscrepancy` ou similaires
- Ajoutes des routes sous `/esbtp/comptabilite/reconciliation` ou `/esbtp/comptabilite/cloture`
- Touches au verrouillage de période (`comptabilite.period_locked_until`, `assertPeriodNotLocked`)

## Pourquoi cette rule existe

**Demande métier Marcel (2026-06-04)** :

> « Après la réconciliation des comptes pour revenir modifier les paiements d'un étudiant
> pour que ça concorde avec ce qu'il y a vraiment en caisse et vice versa »

Concrètement : le système enregistre N paiements (montants, modes, dates). La caisse physique est comptée en fin de période. Il y a presque toujours **divergence** :
- Espèces en plus en caisse (paiement reçu mais pas enregistré)
- Espèces en moins (erreur saisie, fraude, paiement annulé non répercuté)
- Erreur de mode (saisi espèces, en réalité mobile money)

Le comptable doit pouvoir :
1. Constater l'écart
2. Tracer l'origine (lignes paiement candidates)
3. Corriger (modifier le montant, le mode, ou créer un paiement correctif)
4. Justifier la correction (motif obligatoire)
5. Garantir l'audit trail immuable (qui a fait quoi quand, snapshot avant/après)

## État actuel — la feature EST construite

> **Cette rule a d'abord été un cahier des charges. Elle ne l'est plus.** Tout ce qui
> suit (modèles, actions, workflow, permissions, écrans, PV) existe dans le dépôt depuis
> juin 2026. Les sections « architecture », « schéma », « workflow » et « API » décrivent
> désormais **ce qui est en place**, pas ce qu'il reste à écrire. Ne reconstruisez rien :
> ouvrez `app/Domain/Comptabilite/Reconciliation/` d'abord.

Vérifiable en une commande :

```bash
find app/Domain/Comptabilite/Reconciliation -name '*.php' | sort
ls resources/views/esbtp/comptabilite/reconciliation/   # create, index, pdf, show
grep -n "comptabilite/reconciliation" routes/web.php
grep -n "comptabilite.reconciliation" config/permissions.php
```

### Garde-fous existants

Cités par **nom**, pas par numéro de ligne — les lignes bougent à chaque refactor, et
les anciennes adresses écrites ici étaient toutes fausses au bout de trois mois.

1. **`status='validé'` quasi-immuable** — `ESBTPPaiementController::update()` refuse la
   modification d'un paiement validé, avant toute autre garde.
2. **Permission `paiements.manage`** — exigée en tête de `update()`.
3. **Verrouillage de période comptable** (`comptabilite.period_locked_until`) —
   `assertPeriodNotLocked()` vit dans le trait
   `app/Http/Controllers/Concerns/VerrouilleLesPeriodesComptables.php`, partagé par
   `ESBTPPaiementController`, `VentilationPaiementController` et `ModeReglementController`.
4. **Verrouillage post-réconciliation** — `assertReconciliationNotLocked()`, même trait,
   appuyé sur `esbtp_paiements.reconciliation_locked_at`.
5. **Bypass `comptabilite.period.bypass_lock`** — rare, journalisé en `Log::warning`.
6. **Soft-delete** sur `esbtp_paiements`, et **`OwenIt\Auditing`** branché sur
   `ESBTPPaiement` (`implements Auditable` + whitelist `$auditInclude`).

## Architecture livrée (Domain-driven)

Inventaire réel (`find app/Domain/Comptabilite/Reconciliation -name '*.php'`) :

```
app/Domain/Comptabilite/Reconciliation/
├── Models/
│   ├── ReconciliationSession.php          // 1 session = 1 cycle (jour/mois)
│   ├── CashCount.php                      // comptage caisse physique par mode
│   ├── ReconciliationDiscrepancy.php      // 1 ligne d'écart constaté
│   └── PaymentReconciliationLog.php       // trace immuable d'une correction
├── Actions/                               // une action = une transition du workflow
│   ├── OpenSession.php                    // draft
│   ├── RecordCashCount.php                // saisie d'un comptage
│   ├── DetectDiscrepancies.php            // calcul des écarts
│   ├── ResolveDiscrepancy.php             // résolution d'une ligne + log dense
│   ├── ReviewSession.php                  // → review
│   ├── ApproveSession.php                 // → approved (2e personne)
│   ├── CloseSession.php                   // → locked + PV
│   └── ReopenSession.php                  // réouverture exceptionnelle
├── Services/
│   ├── ReconciliationSessionService.php   // orchestration
│   ├── ReconciliationMetricsService.php   // KPIs et santé
│   └── PaymentDrillDownService.php        // lignes candidates d'un écart
├── Support/
│   └── SeparationOfDutiesGuard.php        // opened_by ≠ approved_by (OHADA)
├── Events/
│   ├── ReconciliationSessionOpened.php
│   ├── PaymentAdjusted.php
│   ├── ReconciliationApproved.php
│   └── ReconciliationClosed.php
├── Listeners/
│   └── LockPaymentsAfterReconciliation.php // pose reconciliation_locked_at
└── Notifications/
    └── ReconciliationOverdueNotification.php
```

La séparation des devoirs n'est pas qu'une consigne de cette rule : elle est portée
par `SeparationOfDutiesGuard`, et conditionnée au réglage d'instance
`comptabilite.reconciliation.require_separation_of_duties`.

## Schéma DB (en place)

```sql
-- Sessions de réconciliation (1 par jour ou par période)
CREATE TABLE reconciliation_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE,         -- ex: REC-2026-0042
    annee_universitaire_id BIGINT NOT NULL,
    date_periode DATE NOT NULL,      -- ex: 2026-06-04
    status ENUM('draft','review','approved','locked') DEFAULT 'draft',
    opened_by BIGINT NOT NULL,
    opened_at TIMESTAMP NOT NULL,
    closed_by BIGINT NULL,
    closed_at TIMESTAMP NULL,
    approved_by BIGINT NULL,
    approved_at TIMESTAMP NULL,
    pv_pdf_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP, updated_at TIMESTAMP, deleted_at TIMESTAMP NULL,
    INDEX (annee_universitaire_id, date_periode),
    INDEX (status)
);

-- Comptages physiques par mode (1 row par mode et par session)
CREATE TABLE cash_counts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reconciliation_session_id BIGINT NOT NULL,
    mode_paiement ENUM('especes','mobile_money','virement','cheque','wave','orange_money','mtn_money','moov_money') NOT NULL,
    montant_compte DECIMAL(15,2) NOT NULL,     -- compté physiquement
    montant_systeme DECIMAL(15,2) NOT NULL,     -- somme paiements validés période/mode
    ecart DECIMAL(15,2) GENERATED ALWAYS AS (montant_compte - montant_systeme) STORED,
    counted_by BIGINT NOT NULL,
    counted_at TIMESTAMP NOT NULL,
    notes TEXT NULL,
    FOREIGN KEY (reconciliation_session_id) REFERENCES reconciliation_sessions(id) ON DELETE CASCADE,
    UNIQUE (reconciliation_session_id, mode_paiement)
);

-- Écarts constatés à résoudre (1 row par écart identifié)
CREATE TABLE reconciliation_discrepancies (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reconciliation_session_id BIGINT NOT NULL,
    cash_count_id BIGINT NULL,                   -- lien vers le mode concerné
    type ENUM('paiement_manquant','paiement_en_trop','montant_errone','mode_errone','date_erronee','autre') NOT NULL,
    montant_ecart DECIMAL(15,2) NOT NULL,
    paiement_concerne_id BIGINT NULL,            -- nullable si nouveau paiement à créer
    action ENUM('a_traiter','en_revue','resolu','rejete') DEFAULT 'a_traiter',
    resolution_type ENUM('adjust_payment','create_corrective','cancel_payment','no_action') NULL,
    resolution_payment_id BIGINT NULL,           -- paiement créé ou modifié
    motif TEXT NOT NULL,                         -- justification obligatoire
    resolved_by BIGINT NULL,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reconciliation_session_id) REFERENCES reconciliation_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (cash_count_id) REFERENCES cash_counts(id) ON DELETE SET NULL,
    FOREIGN KEY (paiement_concerne_id) REFERENCES esbtp_paiements(id) ON DELETE SET NULL,
    INDEX (action)
);

-- Log immuable des mutations paiement effectuées en réconciliation
CREATE TABLE payment_reconciliation_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reconciliation_session_id BIGINT NOT NULL,
    paiement_id BIGINT NOT NULL,
    action_type ENUM('adjust_montant','adjust_mode','adjust_date','create','cancel','validate','revalidate') NOT NULL,
    snapshot_before JSON NOT NULL,               -- état complet avant
    snapshot_after JSON NOT NULL,                -- état complet après
    delta JSON NOT NULL,                         -- diff lisible
    motif TEXT NOT NULL,
    performed_by BIGINT NOT NULL,
    performed_at TIMESTAMP NOT NULL,
    FOREIGN KEY (reconciliation_session_id) REFERENCES reconciliation_sessions(id),
    FOREIGN KEY (paiement_id) REFERENCES esbtp_paiements(id),
    INDEX (paiement_id),
    INDEX (performed_at)
);

-- Marqueur "paiement déjà réconcilié" sur la table paiements existante
ALTER TABLE esbtp_paiements
    ADD COLUMN reconciliation_locked_at TIMESTAMP NULL AFTER validated_by,
    ADD COLUMN last_reconciliation_session_id BIGINT NULL AFTER reconciliation_locked_at;
```

## Workflow complet (UEMOA/OHADA-compliant)

```
1. OUVERTURE SESSION
   - Comptable ouvre session pour date X
   - Status: 'draft'
   - Code généré: REC-{ANNEE}-{SEQ4}
   - opened_by, opened_at horodatés

2. SAISIE COMPTAGES CAISSE
   - Pour chaque mode (espèces, MM, virement, etc.) : montant compté
   - Système calcule auto montant_systeme (somme paiements validés ce jour-là et ce mode)
   - Écart = montant_compte - montant_systeme

3. CONSTAT ÉCARTS
   - Liste auto des écarts non-zéro par mode
   - Suggestion lignes paiement candidates :
     • si écart positif espèces : paiements MM/virement qui pourraient avoir été reçus en espèces
     • si écart négatif : paiements espèces validés sans contrepartie physique
     • paiements rejetés non encore traités

4. RÉSOLUTION DISCRÉPANCIES (par ligne)
   Pour chaque ligne d'écart, comptable choisit :
   a. AJUSTER PAIEMENT EXISTANT
      - Sélectionne le paiement, change montant/mode/date
      - Motif obligatoire
      - Action: AdjustPaymentForReconciliation::execute()
      - Log dense écrit dans payment_reconciliation_logs
   b. CRÉER PAIEMENT CORRECTIF
      - Nouveau paiement avec étudiant/montant/mode/date
      - Lien vers cash_count
      - Motif obligatoire
   c. ANNULER PAIEMENT
      - Status passe à 'rejeté' avec motif
      - Crée corrective si fund manquant
   d. NO ACTION (écart accepté)
      - Comptable assume l'écart (perte ou bonus exceptionnel)
      - Motif obligatoire

5. PASSAGE STATUS → 'review'
   - Comptable a résolu tous les écarts
   - Bouton 'Soumettre à revue'
   - Status: 'review'

6. APPROBATION (par 2e personne)
   - Coordinateur/superAdmin valide la session
   - Permission requise: comptabilite.reconciliation.approve
   - Status: 'approved'
   - approved_by, approved_at horodatés

7. CLÔTURE + GENERATION PV
   - Status passe à 'locked' (immuable)
   - PV PDF généré : liste écarts + résolutions + signatures comptable + approbateur
   - Sauvegarde dans pv_pdf_path (storage)
   - Verrouille tous paiements concernés : reconciliation_locked_at = NOW()
   - Update setting comptabilite.period_locked_until si fin de mois

8. ARCHIVAGE LÉGAL
   - Soft-delete uniquement
   - Rétention 10 ans (norme OHADA Côte d'Ivoire)
```

## Permissions (registry `config/permissions.php`, déjà déclarées)

```php
'comptabilite.reconciliation.view' => [
    'label' => 'Voir les sessions de réconciliation',
    'group' => 'Comptabilité — Réconciliation',
    'icon' => 'fa-balance-scale',
],
'comptabilite.reconciliation.open' => [
    'label' => 'Ouvrir une session de réconciliation',
    'group' => 'Comptabilité — Réconciliation',
    'icon' => 'fa-folder-open',
],
'comptabilite.reconciliation.resolve' => [
    'label' => 'Résoudre les écarts de réconciliation',
    'group' => 'Comptabilité — Réconciliation',
    'icon' => 'fa-tools',
],
'comptabilite.reconciliation.approve' => [
    'label' => 'Approuver une session de réconciliation (2e validation)',
    'group' => 'Comptabilité — Réconciliation',
    'icon' => 'fa-check-double',
],
'comptabilite.reconciliation.export' => [
    'label' => 'Exporter PV de réconciliation',
    'group' => 'Comptabilité — Réconciliation',
    'icon' => 'fa-file-pdf',
],
'comptabilite.reconciliation.bypass_lock' => [
    'label' => 'Forcer modification post-réconciliation (rare)',
    'group' => 'Comptabilité — Réconciliation',
    'icon' => 'fa-key',
],
```

Defaults : `comptabilite.reconciliation.view + open + resolve` à `comptable`. `approve` à `superAdmin + coordinateur` uniquement.

## Règles ABSOLUES

1. **JAMAIS modifier un paiement en réconciliation sans `payment_reconciliation_logs` row associée** : audit log obligatoire avec snapshot_before + snapshot_after + delta + motif + performed_by.
2. **JAMAIS clôturer une session sans approbateur distinct du comptable qui a ouvert** : `opened_by != approved_by` (separation of duties OHADA).
3. **JAMAIS supprimer (hard delete) une session de réconciliation** : soft-delete uniquement, rétention 10 ans.
4. **JAMAIS modifier un paiement après `reconciliation_locked_at`** sauf via permission `comptabilite.reconciliation.bypass_lock` (Log::warning obligatoire).
5. **JAMAIS de réconciliation rétroactive sans réouvrir session** : si erreur découverte après lock, créer nouvelle session corrective avec lien vers ancienne.
6. **JAMAIS afficher d'écart calculé sans dater le système** : `montant_systeme` doit être calculé à l'instant T fixe (snapshot DB), pas à chaque requête.
7. **TOUJOURS exiger motif texte ≥ 10 caractères** sur chaque action de résolution (cas legal en cas de contrôle fiscal).
8. **TOUJOURS générer PV PDF même si écart = 0** : preuve qu'une réconciliation a été effectuée.

## API REST (routes en place sous `comptabilite/reconciliation`)

```
POST   /esbtp/comptabilite/reconciliation/sessions          Open new session
GET    /esbtp/comptabilite/reconciliation/sessions          List sessions
GET    /esbtp/comptabilite/reconciliation/sessions/{id}     Show session
PATCH  /esbtp/comptabilite/reconciliation/sessions/{id}     Update notes only (draft)
POST   /esbtp/comptabilite/reconciliation/sessions/{id}/cash-counts  Save cash count
GET    /esbtp/comptabilite/reconciliation/sessions/{id}/discrepancies  List écarts détectés
POST   /esbtp/comptabilite/reconciliation/sessions/{id}/discrepancies/{disc}/resolve  Resolve
POST   /esbtp/comptabilite/reconciliation/sessions/{id}/submit-review  Status → review
POST   /esbtp/comptabilite/reconciliation/sessions/{id}/approve  Status → approved
POST   /esbtp/comptabilite/reconciliation/sessions/{id}/close   Status → locked + génère PV
GET    /esbtp/comptabilite/reconciliation/sessions/{id}/pv      Download PV PDF
```

Toutes en AJAX-no-reload (rule `ajax-no-reload-premium`), validation `FormRequest`, throttle 30/min.

## UI premium (namespace `rec-*`) — `resources/views/esbtp/comptabilite/reconciliation/`

- **Hero gradient bleu** avec icône `fa-balance-scale`, KPIs : sessions ce mois, montant écart total, sessions en review/approuvées
- **Tableau sessions** : code, période, status badge, écart total, opened_by, approved_by, actions
- **Page session** : 3 onglets
  - Tab 1 « Comptages » : grille saisie par mode (espèces, MM, etc.)
  - Tab 2 « Écarts » : liste discrepancies avec actions résolution
  - Tab 3 « Suivi » : journal des actions effectuées
- **Wizard premium 4 étapes** : Ouverture → Comptages → Écarts → Approbation
- **PV PDF** via `<x-pdf-document>` + composant Blade dédié

## Tests obligatoires

1. **Unit** : `ReconciliationSessionService::open()`, `DiscrepancyResolverService::resolve()`
2. **Feature** : workflow complet draft → review → approved → locked
3. **Audit** : vérifier `payment_reconciliation_logs` créé sur chaque mutation
4. **Sécurité** : `opened_by != approved_by` (separation of duties)
5. **Verrouillage** : modification paiement post-lock retourne 422

## Anti-patterns à BLOQUER en review

1. ❌ Modifier `ESBTPPaiement` post-validation sans passer par `AdjustPaymentForReconciliation::execute()` (perte d'audit)
2. ❌ Session approuvée par le même utilisateur qui l'a ouverte (OHADA séparation des devoirs violée)
3. ❌ Clôture sans génération PV (preuve manquante)
4. ❌ Hard-delete `reconciliation_sessions` ou `payment_reconciliation_logs`
5. ❌ Motif texte vide ou < 10 caractères
6. ❌ Réécriture d'un paiement déjà réconcilié sans permission `bypass_lock`
7. ❌ Affichage écart calculé en temps réel (race condition possible si paiement créé pendant la réconciliation)
8. ❌ Permettre `mode_paiement` libre — utiliser ENUM strict
9. ❌ `cash_counts.montant_compte` sans `counted_by` + `counted_at` (audit incomplet)
10. ❌ Génération PV sans signature digitale ou nom du signataire

## Synergie avec features existantes

- **Journal de caisse** (`ESBTPJournalCaisseController`) : exporte les paiements par période/mode → idéal point de départ avant d'ouvrir une réconciliation
- **Audit comptable** (`/esbtp/audit/comptabilite`) : doit indexer les `payment_reconciliation_logs` pour trace globale
- **Settings tenant** : `comptabilite.period_locked_until` doit auto-update sur close de session de fin de mois
- **Analytics prédictifs** : `risk_saturation` est plus fiable si on déduplique les paiements pré-réconciliation
- **Permissions custom** : un rôle « Auditeur externe » peut être créé avec `comptabilite.reconciliation.view` seul (lecture seule sur PV)

## Voir aussi

- `.claude/rules/no-god-code-compta.md` — éviter d'entasser dans ESBTPPaiementController, extraire vers Domain/Comptabilite/Reconciliation
- `.claude/rules/permissions.md` — convention noms `comptabilite.reconciliation.*`
- `.claude/rules/customizable-roles.md` — créer permissions, jamais de nouveau rôle hardcodé
- `.claude/rules/ajax-no-reload-premium.md` — toute l'UI en AJAX
- `.claude/rules/premium-redesign.md` — namespace `rec-*`, palette KLASSCI
- `.claude/rules/exports-pdf-excel.md` — PV PDF via `<x-pdf-document>` + `ExportableReport`
- Norme OHADA Côte d'Ivoire : rétention 10 ans, séparation des devoirs comptable/approbateur
- `app/Http/Controllers/Concerns/VerrouilleLesPeriodesComptables.php` — le trait qui porte `assertPeriodNotLocked()` et `assertReconciliationNotLocked()`
- `docs/audits/2026-06-04-audit-comptable-klassci.md` — audit qui a déclenché cette rule
