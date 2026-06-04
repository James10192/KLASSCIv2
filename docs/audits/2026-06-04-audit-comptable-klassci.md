# Audit Comptable E2E — KLASSCI (module compta multi-tenant)

**Date** : 2026-06-04  
**Auditeur** : Claude (Opus 4.7) via session interactive Marcel  
**Portée** : module comptable KLASSCI tous tenants — `esbtp-yakro` utilisé comme tenant **de test sur données réelles** (1 561 étudiants, 1 429 paiements, 209.9M FCFA encaissés)  
**Année universitaire de référence** : id=6, 2025-2026, `is_current=true`  
**Sources de preuves** : api/cli endpoints + dev-browser UI + code source

> Les bugs / observations / recommandations s'appliquent à TOUS les tenants
> (presentation, esbtp-abidjan, esbtp-yakro, ephrata, hetec, rostan). Yakro est
> simplement la source des preuves chiffrées car c'est l'environnement le plus
> représentatif d'usage réel actuel.

> Note : ce document s'enrichit au fil de l'audit. Chaque section a un statut (🟢 conforme / 🟡 écart mineur / 🔴 écart critique / ⚪ bloqué).

---

## 1. Synthèse exécutive

### KPIs globaux (validés via `api/cli/stats` + `api/cli/payments`)

| KPI | Valeur | Source |
|---|---|---|
| Étudiants actifs | 1 561 | `api/cli/stats` |
| Inscriptions validées | 1 561 (100 %) | `api/cli/stats` |
| Classes | 33 | `api/cli/stats` |
| Total paiements | **1 429** | `api/cli/payments` + `stats` ✓ matche |
| Paiements validés | **1 419** | `api/cli/payments?status=valide` |
| Paiements en attente | **10** | `api/cli/payments?status=en_attente` |
| Revenue total | **209 900 000 FCFA** | `api/cli/stats` |
| Snapshot échéancier coverage | 0 % (1 561 sans snapshot) | `api/cli/analytics/diagnose` |
| Mode échéancier | **fallback** (0 règles actives) | `api/cli/analytics/diagnose` |
| Risk saturation | 76.5 % haut risque (200 actifs analysés) | `api/cli/analytics/diagnose` |

### Bugs critiques fixés pendant l'audit

| Commit | Fichier | Bug | Impact UI |
|---|---|---|---|
| `9d5aa138` | `AnalyticsDiagnoseCommand.php:182` | `AnalyticsContext::__construct` 4 args manquants | CLI analytics diagnose KO |
| `9d5aa138` | `ESBTPBulletinController.php:21` | `ESBTPResultatMatiere` import manquant | Card 3 « Générer en masse » bulletins KO (500) |
| `ee0e6015` | `ESBTPClasseController.php:2101` | `places_totales` HAVING ONLY_FULL_GROUP_BY | Widget classes surcapacité KO sur dashboard |
| `ee0e6015` | `ESBTPAnneeUniversitaire.php` | `date_debut`/`date_fin` vs `start_date`/`end_date` | Log spam + path PDF secondaire cassé |
| `ee0e6015` | `ESBTPBulletinController.php:1524` | `Undefined array key "school_city"` | Préview PDF cassé pour écoles sans ville configurée |

Tous fixes déployés sur yakro.

---

## 2. Audit par page (plan)

### 2.1 Dashboard Comptable (`/esbtp/comptabilite/dashboard`)

**Statut** : ⚪ Bloqué par permissions UI / 🟢 validé via CLI

**Controller** : `ESBTPComptabiliteController::dashboard`  
**Action service** : `BuildDashboardDataAction` (modifié par l'agent compta — commit `8206d3a1`)  
**Permission** : `comptabilite.dashboard.view` (route protégée par `comptabilite.access` middleware)

**KPIs attendus** (via code) :
- Total encaissé période
- Nb paiements
- Paiements en attente
- KPIs annuels/mensuels

**Performance UI** : page rendue en **33 secondes** ⚠ (lente — investigations N+1 supplémentaires nécessaires).

**KPIs affichés (yakro, sans filtre)** :

| KPI | UI valeur | CLI valeur | Cohérence |
|---|---|---|---|
| Total frais dus | 441 450 000 FCFA · 2 943 souscriptions | n/a | — |
| Encaissé | 209 900 000 FCFA | 209 900 000 FCFA | ✅ |
| Taux recouvrement | 47.5 % | computed | ✅ |
| Restant impayé | 230 300 000 FCFA · 1 536 étudiants | n/a | — |
| Paiements en attente | **14** | **10** | ❌ **Discrepancy** |
| Validés | 1 419 | 1 419 | ✅ |

**Bug `count_pending` discrepancy (14 vs 10)** :

- CLI `api/cli/payments?status=en_attente` filtre par `annee_universitaire_id = annee_courante` (=6) → retourne 10.
- Dashboard `BuildDashboardDataAction::paiementsQuery()` ne filtre PAS par année quand aucun filtre user n'est appliqué → retourne 14 (toutes années confondues).

**Fix proposé** : forcer un filtre `annee_active` par défaut quand aucun filtre user n'est appliqué (cohérent avec le KPI « Encaissé » qui semble matcher l'année courante par hasard).

**Bugs trouvés en code** :
- `BuildDashboardDataAction` modifié par agent compta — regroupe les agrégats paiements (réduit N+1) ✓
- Widget classes surcapacité utilisait HAVING cassé → SQL error 500 visible dans logs (fixé `ee0e6015`)
- KPIs filtrés différemment selon le composant : aucune source unique de vérité « scope par défaut dashboard »

**Hero présent** ✓ — 32 KPIs/stat-cards — pas d'erreur visible dans la page (Whoops/Exception/undefined).

---

### 2.2 Gestion des Frais (`/esbtp/frais`)

**Statut** : 🟢 Conforme (validé UI)

**Preuves UI** (modestekouakou session) :
- HTTP 200
- Page title : "Gestion des Frais - KLASSCI"
- 5 lignes table (tarifs configurés)
- 17 KPIs/stat-cards
- Hero présent
- Pas d'erreur visible (Whoops/500/Exception)

**À approfondir** :
- Cohérence frais configurés vs paiements (sample 5 catégories)
- Total dû calculé vs paiements reçus

---

### 2.3 Configuration Frais (`/esbtp/frais/configure`)

**Statut** : 🟢 Conforme (validé UI Admin)

**Preuves UI** :
- HTTP 200, title : « Configuration des Frais - KLASSCI »
- Hero présent, h1 : « Configuration des frais »
- 5 lignes table
- 0 erreur visible

**Service** : `FraisConfigurationPageBuilder` (modifié agent compta — réduit N+1)  
**Permission requise** : `comptabilite.frais.configure` ou `admin.access`

**Modifications agent compta** (commit `8206d3a1`) :
- Batche les données de configuration pour réduire N+1
- Code mort retiré dans `ESBTPFraisController`
- `ESBTPOptionAssignment` supporte `parcours_id`

**À approfondir** : régression possible sur scope parcours LMD (yakro n'a pas de LMD donc non testable ici, à valider sur ephrata).

---

### 2.4 Liste des Paiements (`/esbtp/paiements`)

**Statut** : 🟢 Conforme (validé UI)

**Preuves UI** :
- HTTP 200
- Page title : "Suivi des Paiements - KLASSCI"
- 20 lignes paginées par défaut (sur 1 429 total)
- 25 KPIs
- Pas d'erreur

**Cohérence CLI ↔ UI** :
- Total CLI : 1 429
- Page : paginated 20/page → 72 pages

**Code review observations** (`ESBTPPaiementController.php`) :
- `index()` ligne 53 : `FuzzyNameMatcher` injecté pour recherche tolérante orthographe
- `edit()` ligne 484 : seuls `paiements.manage` peuvent éditer
- `update()` ligne 533 : refuse si `status='validé'` OU période comptable verrouillée (`assertPeriodNotLocked`)
- **Verrouillage période** : `comptabilite.period_locked_until` (setting tenant). Bypass via `comptabilite.period.bypass_lock`

---

### 2.5 Suivi par Catégorie (`/esbtp/paiements/suivi-categories`)

**Statut** : 🟢 Conforme (validé UI Admin)

**⚠ Correction route** : la route réelle est `/esbtp/paiements/suivi-categories` (pas `/esbtp/comptabilite/suivi-categories`).

**Preuves UI** :
- HTTP 200, title : « Suivi des Paiements par Catégorie - KLASSCI »
- h1 : « Suivi des Paiements par Catégorie »
- Hero présent, 22 KPIs, 5 lignes table
- 0 erreur visible

**Controller** : `ESBTPPaiementSuiviController::suiviCategories`  
**Refresh AJAX** : `paiements.suivi-categories.refresh`  
**Drill-down par statut** : `paiements.suivi-categories.load.{statut}`

---

### 2.6 Export détaillé

**Statut** : 🟡 Routes consolidées identifiées

**⚠ Correction route** : préfixe `/esbtp/paiements/export-detaille/...` (pas `/esbtp/comptabilite/export-detaille`).

Routes (lignes 905-945 routes/web.php) :
- `paiements.export-detaille.*` (groupe dédié avec throttle preview 60/min, download 10/min)
- `paiements.export.excel/pdf/csv`
- `analytics.export-pdf`, `analytics.export-excel`, `analytics.preview-pdf`
- `recouvrement.export-pdf`, `recouvrement.export-excel`, `recouvrement.email-pdf`
- `relances.export-pdf`, `relances.export-excel`, `relances.preview-pdf`

Tous protégés par `permission:paiements.export` ou `comptabilite.reports.export` + throttle.

**À approfondir** : tester un export PDF/Excel + vérifier cohérence totaux exportés ↔ totaux UI.

---

### 2.7 Relances (`/esbtp/comptabilite/relances`)

**Statut** : 🟢 Conforme (validé UI Admin)

**Preuves UI** :
- HTTP 200, title : « Relances Paiements »
- h1 : « Gestion des Relances 2025-2026 »
- 30 lignes table affichées
- 29 KPIs
- Hero présent, 0 erreur

**Controller** : `ESBTPComptabiliteRelanceController`  
**Routes** : `gestionRelances`, `analyticsRelances`, `relanceEtudiant`, `apercuRelances`...

**Méthodes intéressantes** :
- `planifierRelancesAvancees` : segmentation avancée
- `previewSegmentation` : preview avant envoi
- `executerRelances` : envoie réellement (throttle 5/min)

**À approfondir** : workflow planning → preview → execute, audit des envois.

---

### 2.8 Recouvrement quotidien (`/esbtp/comptabilite/recouvrement`)

**Statut** : 🟢 Conforme (validé UI Admin)

**Preuves UI** :
- HTTP 200, title : « Recouvrement quotidien »
- h1 : « Recouvrement quotidien »
- 55 lignes table (top étudiants à risque)
- 18 KPIs
- Hero présent, 0 erreur

**Controller** : `ESBTPRecouvrementController`  
**Sprint 11** : `RecouvrementOptimizer` + WhatsApp deeplinks

**Routes** : `index`, `logIntent`, `confirmSent`, `markDone` + exports

**Workflow** : voir étudiants en retard → log intent (préparation) → confirm sent (WhatsApp) → mark done.

**À approfondir** : la table affiche 55 lignes (top-risque limité) mais 1 561 étudiants existent — vérifier que tri par risque + filtrage permettent d'accéder à tous les retards.

---

### 2.9 Analytics prédictifs (`/esbtp/comptabilite/analytics`)

**Statut** : 🟢 Validé via CLI / ⚪ UI bloquée

**Diagnostic CLI complet** :

```
{
  "annee_universitaire": { "id": 6, "name": "2025-2026" },
  "echeancier": {
    "mode": "fallback",
    "rules_summary": { "total_active": 0, "with_active_lines": 0 }
  },
  "coverage": { "total_actives": 1561, "with_snapshot": 0, "coverage_pct": 0 },
  "monthly_attendu": { "rows": [] },
  "risk_saturation": {
    "total_actifs": 200,
    "buckets": { "haut": 153, "moyen": 0, "bas": 47 },
    "haut_risque_pct": 76.5,
    "is_saturated": true
  }
}
```

**Observations métier** :
- **Mode fallback** : aucune règle d'échéancier active → tout l'attendu sur 1 tranche unique par défaut (rule `analytics-pitfalls.md` piège #2)
- **Risk saturation** : 76.5 % de la cohorte (200 actifs) en haut risque → cohorte dégénérée typique (piège #4)
- **Coverage 0 %** : aucun étudiant n'a de snapshot d'échéancier → conséquence directe du mode fallback

**Action école** : configurer les règles d'échéancier dans `/esbtp/echeanciers` pour sortir du mode fallback.

---

### 2.10 Journal de caisse (`/esbtp/comptabilite/journal-caisse`)

**Statut** : 🟢 Conforme (validé UI Admin)

**Preuves UI** :
- HTTP 200, title : « Journal de caisse OHADA »
- h1 : « Journal de caisse »
- 55 lignes table
- 12 KPIs
- Hero présent, 0 erreur visible

**Controller** : `ESBTPJournalCaisseController`  
**Permission** : `comptabilite.journal_caisse.view` ou équivalent

**Méthodes** :
- `index` : liste paiements par période, classe, filière, mode
- `buildTotals` : agrégats par mode_paiement (count + sum montant)
- `exportPdf` / `exportPdfPreview` / `previewPdf` : exports

**Filtres** :
- date_debut / date_fin (période)
- statut paiement
- mode_paiement (espèces, mobile money, virement...)
- classe_id ou filiere_id

**Forte intersection avec la feature reconciliation** : la page journal de caisse présente déjà les paiements par mode, et c'est exactement la base pour comparer à la caisse physique.

---

### 2.11 Audit comptable (`/esbtp/audit/comptabilite`)

**Statut** : 🟢 Conforme (validé UI Admin)

**Preuves UI** :
- HTTP 200, title : « Audit comptable »
- h1 : « Audit comptable »
- 55 lignes table (événements)
- 17 KPIs
- Hero présent, 0 erreur

**Route** : `Route::middleware(['auth', 'throttle:audit'])->prefix('esbtp/audit')`

**Throttle** : `throttle:audit` (rate limiter custom).

**Observations** :
- La page existe — peut-être basée sur OwenIt audits table ou `audits` génériques
- À vérifier : enrichissement par mutation paiement, snapshot avant/après (nécessaire pour future feature réconciliation)

---

## 2.12 Investigation discrepancy Dashboard count_pending (TRACED)

**Via nouveaux endpoints CLI** (commit `9f1416cd`) :

```
api/cli/comptabilite/dashboard-kpis (sans filtre)
→ count_pending = 14, total_pending_amount = 1 800 000 FCFA

api/cli/comptabilite/dashboard-kpis?annee_id=6
→ count_pending = 10, total_pending_amount = 1 200 000 FCFA

api/cli/comptabilite/payments-summary (multi-années)
→ 2025-2026 (id=6, courante) : 10 en_attente / 1 419 validés = 1 429
→ 2024-2025 (id=5, passée)    :  4 en_attente / 0 validés    =     4
→ Grand total : 14 en_attente / 1 419 validés = 1 433
```

**Cause racine identifiée** : 4 paiements `en_attente` sur l'année passée (2024-2025) montant 600 000 FCFA. Ces paiements ne sont jamais validés et restent en limbo.

**Action recommandée** : workflow de **clôture mensuelle/annuelle** qui force la résolution (valider, rejeter, ou archiver) avant transition. Voir rule `cloture-mensuelle-configurable.md`.

---

## 2.13 Anomalies majeures détectées (paiements suspects)

**Via `api/cli/comptabilite/reconciliation-candidates`** :

| Catégorie | Nombre | Risque |
|---|---|---|
| Paiements montant = 0 FCFA | **18** dont plusieurs `validé` | 🔴 Audit fiscal — saisies invalides |
| Paiements sans inscription | 0 | ✅ |
| Paiements sans annee_universitaire_id | 0 | ✅ |
| Paiements en_attente > 7 jours | 5 | 🟡 Workflow validation lent |

**Exemples montant=0 validés** :
```
id=282 etudiant_id=2320 status=validé mode=Espèces motif="Frais d'inscription" date=2026-04-29
id=417 etudiant_id=2174 status=validé mode=Espèces motif="Frais d'inscription" date=2026-05-04
```

**Hypothèses** :
- a) Sentinelles pour marquer « frais d'inscription payés ailleurs » sans montant
- b) Bug de saisie : utilisateur a soumis 0 par erreur, validation manquée
- c) Frais d'inscription = 0 FCFA pour certaines catégories (étudiants exonérés)

**À clarifier avec Marcel** avant tout traitement automatique.

---

## 2.14 État verrouillage périodique

**Via `api/cli/comptabilite/period-locks`** :

- `comptabilite.period_locked_until` : **null** (aucune période verrouillée)
- Tous les paiements (toutes années) sont actuellement **modifiables**
- Bypass permission : `comptabilite.period.bypass_lock`

**Action recommandée** : implémenter la **clôture mensuelle configurable** (déclenchée par cet audit) pour activer automatiquement le verrouillage de période quand le mois est clôturé.

---

## 2.15 Cash balance — paiements du jour

**Via `api/cli/comptabilite/cash-balance` (date=2026-06-04)** :

```
by_mode:
  Espèces : 5 paiements × moyenne 130 000 = 650 000 FCFA
grand_total: 650 000 FCFA
```

**Note** : aucun autre mode utilisé aujourd'hui (Mobile Money, Virement). Cohérent avec l'usage majoritaire « espèces » indiqué par Marcel.

---

## 3. Bugs détectés via logs (`api/cli/logs`)

### Bugs critiques résolus

| Bug | Trace | Statut |
|---|---|---|
| `Too few arguments to AnalyticsContext::__construct` | AnalyticsDiagnoseCommand:182 | ✅ Fixé `9d5aa138` |
| `Class "App\Http\Controllers\ESBTPResultatMatiere" not found` | ESBTPBulletinController:1146 | ✅ Fixé `9d5aa138` |
| `Non-grouping field 'places_totales' is used in HAVING clause` | ESBTPClasseController:2101 | ✅ Fixé `ee0e6015` |
| `Dates de l'année universitaire non définies` | BulletinService:1709 | ✅ Fixé `ee0e6015` (accesseurs date_debut/date_fin) |
| `Undefined array key "school_city"` | ESBTPBulletinController:1524 | ✅ Fixé `ee0e6015` (?? '') |

### Bugs persistants détectés (non encore fixés)

| Bug | Source | Risque |
|---|---|---|
| `User c2569688c_Marcel already has more than 'max_user_connections' active connections` | DB MySQL | 🟡 Mid (sous charge utilisateur) — config DB côté hébergeur |
| `Call to undefined function previewClasseValue()` | bulletins/select compiled view (CACHE STALE) | 🟢 Résolu après `cache:clear` |

---

## 4. Observations métier (préparation features)

### 4.1 Modèle paiement (`ESBTPPaiement`)

**Champs identifiés** :
- `etudiant_id`, `inscription_id`, `frais_category_id`
- `montant`, `mode_paiement`, `motif`, `reference`
- `date_paiement`, `status` (en_attente, validé, rejeté)
- `created_by`, `updated_by`, `validated_by`
- Soft-delete : `deleted_at`

**Modes de paiement observés** (via CLI sample) :
- espèces
- (à compléter : mobile money, virement, chèque ?)

### 4.2 Workflow modification d'un paiement

```
Création paiement (status=en_attente)
   ↓
Validation par compta/caissier (status=validé)
   ↓
⛔ Plus modifiable sauf bypass permission spéciale
   ↓
Clôture période comptable (setting comptabilite.period_locked_until)
   ↓
⛔ Plus modifiable même avec permission, sauf bypass_lock (rare)
```

### 4.3 Garde-fous existants pour modification rétroactive

1. **`paiements.manage`** : permission pour éditer
2. **`status='validé'` immuable par défaut** : seul un bypass code-level peut contourner
3. **`comptabilite.period_locked_until`** : verrouillage de période comptable
4. **`comptabilite.period.bypass_lock`** : permission de bypass (rare, log warning)
5. **Audit trail** : `updated_by` + `Log::warning('[S1.4] Bypass verrouillage période utilisé')` mais pas d'OwenIt audit complet visible

### 4.4 Cas d'usage Réconciliation (besoin Marcel)

> « Réconciliation des comptes pour revenir modifier les paiements d'un étudiant pour que ça concorde avec ce qu'il y a vraiment en caisse et vice versa »

**Décomposition** :
1. Le système enregistre N paiements (montant, mode, date)
2. La caisse physique est comptée en fin de journée/mois
3. Il y a divergence (espèces réelles ≠ espèces enregistrées) → ex : 50 000 FCFA en plus en caisse
4. Le comptable doit pouvoir attribuer ces 50 000 à un paiement existant (correction montant) ou créer un nouveau paiement avec audit complet
5. Inversement : si paiement enregistré sans caisse réelle → flag pour suppression ou rectification

**Mécaniques nécessaires** :
- Saisie « solde caisse physique » par mode (espèces, MM, virement) à une date donnée
- Calcul automatique solde système attendu
- UI de réconciliation : montrer écart, permettre rapprochement ligne par ligne
- Workflow : draft → review (par 2e personne) → validate
- Audit log immuable de la session de réconciliation (qui, quoi, pourquoi, montant ajusté)
- Génération PV de réconciliation PDF signable
- Verrouillage automatique des paiements rapprochés
- Permission `comptabilite.reconciliation.execute` + `comptabilite.reconciliation.approve`

Voir rule détaillée : `.claude/rules/reconciliation-paiements-caisse.md`

---

## 5. Recommandations & next steps

### Priorité 🔴 Haute

1. **Sortir le mode fallback échéancier** : configurer au moins 1 règle d'échéancier sur yakro (page `/esbtp/echeanciers`)
2. **Vérifier `max_user_connections`** côté hébergeur LWS : augmenter le quota ou poser un pool de connexions
3. **Auditer UI compta avec compte ayant `comptabilite.access`** (Admin/superAdmin)

### Priorité 🟡 Moyenne

4. **Feature Réconciliation caisse** : prioriser la rule + implémentation phasée (voir rule dédiée)
5. **Étendre audit log paiements** : passer à `OwenIt\Auditing` avec whitelist sur `montant`, `status`, `mode_paiement`, `date_paiement`
6. **Dashboard exception list** : page listant tous les paiements modifiés post-validation (rétro-modif suspecte)

### Priorité 🟢 Backlog

7. **Bulk validation paiements en attente** : 10 actuellement, workflow batch validation possible
8. **Exporter état des comptes par étudiant** : PDF / Excel récapitulatif individuel

---

## 6. Index commits audit

| Commit | Sujet |
|---|---|
| `8206d3a1` | fix(compta+analytics+install): 4 axes (agent compta) |
| `9d5aa138` | fix(critique): AnalyticsContext + ESBTPResultatMatiere |
| `ee0e6015` | fix(audit): 3 bugs prod détectés via api/cli/logs |

---

*Document vivant — enrichi au fur et à mesure de l'audit.*
