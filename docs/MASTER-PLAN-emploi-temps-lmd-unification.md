# MASTER PLAN — Emploi-Temps & LMD Unification

> **Chantier majeur** : aligner BTS legacy et LMD UEMOA sur l'emploi du temps, les examens, le rattrapage et le jury de délibération avec design premium AJAX no-reload.
>
> **Démarré** : 2026-05-22
> **Branche** : `feat/emploi-temps-lmd-master`
> **Ampleur estimée** : 18 PRs · ~30 jours dev · ~7800 LOC tests · 6 tenants en prod

---

## Table des matières

1. [Vision & Objectifs](#vision)
2. [Architecture cible](#architecture)
3. [Conventions UEMOA pédagogiques](#uemoa)
4. [Plan des 18 PRs](#prs)
5. [Stratégie de tests](#tests)
6. [Stratégie de déploiement](#deployment)
7. [Journal d'exécution](#journal)
8. [Decisions log](#decisions)

---

## 1. Vision & Objectifs <a id="vision"></a>

### Problème racine
KLASSCI a 2 systèmes académiques en production (BTS legacy + LMD UEMOA) sur 6 tenants. Le module emploi du temps + séances + bulletins + examens diverge entre ces 2 systèmes avec 4 bugs racines identifiés et plusieurs sites consommateurs de matières qui ne respectent pas la rule globale `klassci-classe-matieres.md` (source canonique = `ESBTPPlanificationAcademique`).

### Objectifs mesurables
- **0 régression BTS legacy** sur les 6 tenants
- **100 % des sites consommateurs de matières d'une classe** passent par `MatiereTreeBuilder` (Single Source of Truth)
- **Workflow examens UEMOA complet** : planification → rattrapage → jury de délibération → PV légal archivé
- **Zéro page reload** sur les actions UI premium (AJAX-driven obligatoire)
- **180+ tests** (Unit + Feature + Browser E2E) couvrant les 4 combos (BTS pivot peuplé / BTS pivot vide / LMD parcours / LMD tronc commun)
- **PV délibération PDF officiel** avec numérotation séquentielle thread-safe + archivage 5 ans
- **Déploiement coordonné 6 tenants** avec smoke tests automatisés

### Périmètre OUT (à ne PAS faire dans ce chantier)
- Refonte ESBTPBulletinController BTS complet (juste guard 422 + audit)
- Module communication / notifications push temps réel (sauf workflow rattrapage email)
- Refonte composant calendar (existant `<x-emploi-temps.grille-horaire>` réutilisé tel quel)
- Migration vers Laravel 11+ (rester sur Laravel 9.x)

---

## 2. Architecture cible <a id="architecture"></a>

### Single Source of Truth — Matières d'une classe

```
┌────────────────────────────────────────────────────────────┐
│                    MatiereTreeBuilder                      │
│           (App\Services\LMD\MatiereTreeBuilder)            │
├────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────┐  │
│  │ buildForPlanning(planificationData, classe)          │  │
│  │   → matieres SANS volumeBudget                        │  │
│  │   Pour : bulk-edit, addSession, seances-cours/edit    │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ buildWithVolumeBudget(planificationData, classe,     │  │
│  │                       annee)                          │  │
│  │   → matieres + CM/TD/TP realisés                      │  │
│  │   Pour : emploi-temps/show                            │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ loadLmdMatieresForClasse(classe)                      │  │
│  │   → ECUEs pour tab Suivi heures (classes.show)        │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────┘
        ↑                ↑                ↑
        │                │                │
ESBTPEmploiTempsController   ESBTPSeanceCoursController   ESBTPClasseController
    ::show()                    ::create()                 ::show()
    ::buildEmploiTempsView()    ::edit()
    ::addSession()
```

### Workflow examens LMD UEMOA

```
┌─────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│  Planification  │───▶│   Session normale │───▶│  Notes saisies   │
│  examen (J-30)  │    │   examens passés  │    │  par enseignants │
└─────────────────┘    └──────────────────┘    └──────────────────┘
                                                          │
                                                          ▼
                                              ┌──────────────────┐
                                              │ Lock notes session│
                                              │ (anti-tampering)  │
                                              └──────────────────┘
                                                          │
                                                          ▼
                                              ┌──────────────────────┐
                                              │ Jury de délibération │
                                              │ (interface AJAX)     │
                                              │ Calcul auto décision │
                                              │ + Override + Motif   │
                                              └──────────────────────┘
                                                          │
                                              ┌───────────┴───────────┐
                                              ▼                       ▼
                                  ┌──────────────────┐    ┌──────────────────┐
                                  │ Décision = ADMIS │    │ Décision =       │
                                  │ Crédits validés  │    │ ADMISSION_RATT.  │
                                  │ Mention attribuée│    │ Eligible 2e session│
                                  └──────────────────┘    └──────────────────┘
                                                                    │
                                                                    ▼
                                                          ┌─────────────────────┐
                                                          │ Session rattrapage  │
                                                          │ examens générés     │
                                                          │ pour ECUE non val.  │
                                                          └─────────────────────┘
                                                                    │
                                                                    ▼
                                                          ┌─────────────────────┐
                                                          │ Nouvelle délibér.   │
                                                          │ post-rattrapage     │
                                                          └─────────────────────┘
                                                                    │
                                                                    ▼
                                              ┌──────────────────────┐
                                              │ Génération PV PDF    │
                                              │ Signature membres    │
                                              │ Archivage légal 5 ans │
                                              └──────────────────────┘
```

### Conventions de nommage par PR

| Concept | Namespace CSS | Fichiers |
|---|---|---|
| Emploi-temps show (existant) | `ets-*` | `resources/views/esbtp/emploi-temps/show.blade.php` |
| Emploi-temps bulk-edit | `bek-*` | `resources/views/esbtp/emploi-temps/bulk-edit.blade.php` |
| Seances-cours create/edit | `sce-*` | `resources/views/esbtp/seances-cours/*.blade.php` |
| LMD planning | `lp-*` (existant) | `resources/views/esbtp/lmd/planning/*.blade.php` |
| LMD planning examens tab | `lpx-*` | `resources/views/esbtp/lmd/planning/_examens_section.blade.php` |
| Examens table dédiée | `exp-*` | `resources/views/esbtp/examens/*.blade.php` |
| Rattrapage | `rtp-*` | `resources/views/esbtp/lmd/rattrapage/*.blade.php` |
| Jury | `juy-*` | `resources/views/esbtp/lmd/jurys/*.blade.php` |

---

## 3. Conventions UEMOA pédagogiques <a id="uemoa"></a>

### Sources canoniques
- **Directive 03/2007/CM/UEMOA** (LMD)
- **CAMES** Conseil Africain et Malgache pour l'Enseignement Supérieur
- **REESAO** Réseau pour l'Excellence de l'Enseignement Supérieur en Afrique de l'Ouest
- **MENA Côte d'Ivoire** (autonomie tenant pour pondération CC/Examen Terminal)

### Pondération CC vs Examen Terminal
- **Non standardisée UEMOA** → tenant-autonomy via settings :
  - `lmd_cc_weight` (default 40 %)
  - `lmd_exam_weight` (default 60 %)

### Compensation
- **UE** : moyenne UE ≥ 10/20 → UE validée (compensation entre ECUE intra-UE)
- **Semestre** : compensation entre UE selon setting `lmd_compensation_enabled`
- **Note éliminatoire** : si setting `lmd_note_eliminatoire > 0`, ECUE < seuil → UE non validable

### Crédits ECTS
- 30 crédits par semestre · 60 par année académique
- Licence : 180 (L1+L2+L3) · Master : 120 (M1+M2) · Doctorat : 180

### Mentions UEMOA
| Mention | Seuil moyenne |
|---|---|
| Passable | 10 ≤ m < 12 |
| Assez Bien | 12 ≤ m < 14 |
| Bien | 14 ≤ m < 16 |
| Très Bien | m ≥ 16 |
| Excellent (rare) | m ≥ 18 |

### Sessions
- **Session normale** : examens fin de semestre (semaines 14-16)
- **Session rattrapage** : 2e session, minimum 2 semaines après publication session 1
  - Note rattrapage : **remplace** ou **max** selon `lmd_rattrapage_replace`
  - CC restent inchangés (uniquement Examen Terminal repassé)

### Décisions canoniques jury LMD
- **ADMIS** : moyenne ≥ 10, tous crédits validés, mention attribuée
- **ADMISSION_RATTRAPAGE** : éligible 2e session sur ECUE non validés
- **AJOURNÉ** : 2e session échouée → repasse année
- **EXCLU** : exclusion académique (cas limites, motif obligatoire)
- **ADMIS_SOUS_CONDITION** : crédits manquants mais jury accepte (motif obligatoire)
- **DEFERE_AU_RECTORAT** : cas exceptionnels (médical, force majeure)

### Composition jury
- **Président** (1, obligatoire — généralement Responsable UE chef département)
- **Assesseurs** (N, enseignants UE de la session)
- **Secrétaire** (1, admin scolarité — saisit le PV)
- **Membres consultatifs** (optionnel)
- **Quorum minimum** : setting `lmd_jury_quorum_min` (default 2 = président + secrétaire)

### PV de délibération
- **Numérotation séquentielle** : `PV-{ANNEE_UNIVERSITAIRE}-{TENANT_CODE}-{NUMERO_SEQ_4_DIGITS}`
  - Ex : `PV-2025-2026-PRESENTATION-0042`
- **Archivage légal** : minimum 5 ans (Côte d'Ivoire MENA)
- **Storage** : `storage/pv/{tenant}/{annee}/{numero}.pdf`
- **Soft delete uniquement** + audit log immutable

---

## 4. Plan des 18 PRs <a id="prs"></a>

### Vue d'ensemble

| # | PR | Phase | Durée | Dépend |
|---|---|---|---|---|
| 0 | Infrastructure | Setup | 1.5j | — |
| 1 | Foundation MatiereTreeBuilder | Foundation | 1.5j | PR0 |
| 2 | Migration callers + suppression duplication | Refactor | 1.5j | PR1 |
| 3 | addSession + edit LMD-aware | Refactor | 2j | PR1 (idéalement après PR2) |
| 4 | Fix embedded styles | Bug fix | 1j | — (parallèle) |
| 5 | Types reactive BTS-aware | Feature | 1.5j | PR3 |
| 6 | Section examens P1 (scope) | Feature | 1.5j | — (parallèle) |
| 7 | Bulletin BTS guard + audit LMD | Bug fix | 1.5j | — |
| 8 | Migration examens table dédiée | Schema | 1j | — |
| 9 | Workflow examens complet | Feature | 3j | PR8 |
| 10 | Workflow rattrapage | Feature | 2j | PR9 |
| 11 | Jury foundation (tables + service) | Feature | 2j | PR9, PR10 |
| 12 | Jury UI premium AJAX | Feature | 3j | PR11 |
| 13 | PV PDF officiel + archivage | Feature | 1.5j | PR12 |
| 14 | Tests E2E exhaustifs | Quality | 2j | tous |
| 15 | Documentation | Docs | 1j | tous |
| 16 | Bonus features | Polish | 3j | — |
| 17 | Déploiement coordonné | Deploy | 0.5j | tous |

### PR0 — Infrastructure (1.5j)

**Goal** : créer toute la structure de support du chantier AVANT de toucher au code.

**Livrables** :
- [x] Branche feature `feat/emploi-temps-lmd-master` créée
- [x] Master plan doc (ce fichier)
- [ ] 8 memory files dans `memory/`
- [ ] 6 rules projet dans `.claude/rules/`
- [ ] 5 skills KLASSCI dans `.claude/skills/`
- [ ] 6 scripts PowerShell dans `scripts/lmd/`
- [ ] Update `.claude/rules/premium-redesign.md` avec section AJAX no-reload
- [ ] Pest Browser plugin installé
- [ ] `klassci-cli config:set-token` pour esbtp-yakro, hetec, ephrata

**Pre-merge checklist** :
- [ ] Tous les fichiers ont frontmatter YAML valide (memory)
- [ ] Skills ont SKILL.md avec name + description + frontmatter
- [ ] Scripts PowerShell sont idempotents (--dry-run par défaut)
- [ ] MEMORY.md index à jour

### PR1 — Foundation MatiereTreeBuilder (1.5j)

**Goal** : Single Source of Truth via 2 méthodes publiques explicites.

**Livrables** :
- [ ] `app/Services/LMD/MatiereTreeBuilder.php` refactored avec :
  - `buildForPlanning(array $planificationData, ESBTPClasse $classe): array`
  - `buildWithVolumeBudget(array $planificationData, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee): array`
- [ ] `ESBTPEmploiTempsController::show()` bascule vers `buildWithVolumeBudget()`
- [ ] `ESBTPSeanceCoursController::create()` bascule vers `buildForPlanning()`
- [ ] `ESBTPEmploiTempsController::overridePlanificationForLmd()` ligne 1068 marquée `@deprecated`
- [ ] `tests/Unit/Services/MatiereTreeBuilderTest.php` (~200 LOC, 8+ tests)
- [ ] `tests/Feature/EmploiTemps/VolumeBudgetRegressionShowTest.php` (~120 LOC)

**Tests** :
- Unit : 8 cases (4 combos × 2 méthodes)
- Feature : régression KPIs CM/TD/TP sur /show

### PR2 — Migration callers + Suppression duplication (1.5j)

**Goal** : appliquer override LMD à bulkEdit + sections + supprimer duplication legacy.

**Livrables** :
- [ ] `buildEmploiTempsViewData()` ligne 711 applique override LMD
- [ ] Méthode privée `overridePlanificationForLmd()` ligne 1068 **SUPPRIMÉE**
- [ ] `tests/Feature/EmploiTemps/BulkEditLmdTest.php` (~250 LOC)
- [ ] `tests/Browser/EmploiTemps/BulkEditFlowTest.php` (~150 LOC E2E)

**Visual-check** : `/esbtp/emploi-temps/bulk-edit?ids=X,Y` avec LICENCE 3 DROIT PRIVE A

### PR3 — addSession + seances-cours/edit + cleanup (2j)

**Goal** : refactor `addSession()` BTS-only + `edit()` LMD-aware + cleanup add-session.blade.

**Livrables** :
- [ ] `addSession()` ligne 1492-1620 refactor avec MatiereTreeBuilder
- [ ] `ESBTPSeanceCoursController::edit()` ligne 828 applique override LMD
- [ ] `resources/views/esbtp/emploi-temps/add-session.blade.php` :
  - Option A : refactor premium avec `<x-au-select>` (namespace `eas-*`)
  - Option B : route redirige vers `seances-cours/create?emploi_temps_id=X`
- [ ] `tests/Feature/EmploiTemps/AddSessionLmdTest.php`
- [ ] `tests/Feature/SeanceCours/EditLmdTest.php`
- [ ] `tests/Feature/Audit/SeanceCoursAuditPreservedTest.php`
- [ ] `tests/Browser/SeanceCours/CreateLmdFlowTest.php`

### PR4 — Fix embedded styles (1j)

**Goal** : régler le mismatch `@section('styles')` / `@stack('styles')` sur les vues embedded.

**Livrables** :
- [ ] `resources/views/esbtp/seances-cours/create.blade.php:5` : `@section` → `@push`
- [ ] `resources/views/esbtp/evaluations/create.blade.php` : même fix
- [ ] `resources/views/layouts/embedded.blade.php` : ajout `@yield('styles')` fallback
- [ ] Audit autres vues embedded (`etudiants/embed/edit.blade.php`, etc.)
- [ ] `tests/Browser/Embedded/StylesLoadedTest.php` (E2E)

### PR5 — Types reactive BTS-aware (1.5j)

**Goal** : sous-types de séances qui s'adaptent selon BTS vs LMD + topType.

**Livrables** :
- [ ] Nouveau partial `resources/views/esbtp/seances-cours/partials/_form_type_seance_bts.blade.php` (~90 LOC)
- [ ] `seances-cours/create.blade.php` + `edit.blade.php` : include conditionnel `_form_type_seance_lmd` ou `_form_type_seance_bts`
- [ ] Alpine `x-data` root avec `topType` + `subType` + `$watch`
- [ ] `tests/Feature/SeanceCours/TypeSeanceMatrixTest.php` (~250 LOC, 8 combos)
- [ ] `tests/Browser/SeanceCours/TypeReactiveSwitchTest.php` (E2E)

### PR6 — Section examens P1 (1.5j)

**Goal** : tab examens dans LMD planning via scope query (PAS nouvelle table — Phase 2 fera ça en PR8).

**Livrables** :
- [ ] `ESBTPLMDPlanningController::index()` ajoute `$examensRows` (scope `type_seance=EXAMEN`)
- [ ] Nouveau partial `resources/views/esbtp/lmd/planning/_examens_section.blade.php` (~180 LOC, namespace `lpx-*`)
- [ ] Hero KPIs + table chronologique premium
- [ ] Route GET partial `/esbtp/lmd/planning/examens-partial` pour AJAX
- [ ] **Extension `App\Enums\TypeSeance`** : ajout cases `PARTIEL`, `RATTRAPAGE`, `SOUTENANCE`
- [ ] **Refactor `mapToType()`** : retourne `null` pour TOUS les types évaluation
- [ ] `tests/Unit/Enums/TypeSeanceExtensionTest.php`
- [ ] `tests/Feature/LMD/ExamensSectionTest.php`
- [ ] `tests/Browser/LMD/PlanningExamensTabTest.php`

### PR7 — Bulletin BTS guard + audit LMD (1.5j)

**Goal** : protéger ESBTPBulletinController BTS contre classes LMD + auditer LMDBulletinService.

**Livrables** :
- [ ] `ESBTPBulletinController::generate()` line 180+ : `abort_if($classe->systeme_academique === 'LMD', 422, ...)`
- [ ] Sites concernés : line 184, 874, 1189
- [ ] Audit `LMDBulletinService::genererBulletinLMD()` : vérifier ECUEs chargés correctement (parcours.unitesEnseignement)
- [ ] Si bug trouvé → refactor pour utiliser `MatiereTreeBuilder::loadLmdMatieresForClasse()`
- [ ] `tests/Feature/Bulletin/BtsLmdRoutingGuardTest.php`
- [ ] `tests/Feature/Bulletin/LmdBulletinEcueLoadingTest.php`

### PR8 — Migration examens table dédiée (1j)

**Goal** : créer schema `esbtp_examens_planifies` + `esbtp_examen_surveillants` pour workflow scolarité.

**Livrables** :
- [ ] Migration `database/migrations/YYYY_MM_DD_create_esbtp_examens_planifies_table.php`
- [ ] Migration `database/migrations/YYYY_MM_DD_create_esbtp_examen_surveillants_table.php`
- [ ] Model `app/Models/ESBTPExamenPlanifie.php` (~150 LOC, Auditable)
- [ ] Model `app/Models/ESBTPExamenSurveillant.php` (~80 LOC)
- [ ] Relations Eloquent + scopes utiles
- [ ] Audit log Auditable trait avec whitelist

### PR9 — Workflow examens complet (3j)

**Goal** : CRUD + scheduling + UI premium grille semaine + convocations PDF.

**Livrables** :
- [ ] `app/Services/ExamenSchedulingService.php` (~250 LOC) :
  - `genererExamensSession(parcoursId, semestre, session)`
  - `detecterConflitsEtudiants(examens)`
  - `assignerSurveillants(examen, userIds, role)`
  - `lockNotesAfterExam(examen)`
- [ ] `app/Http/Controllers/ESBTPExamenPlanifieController.php` (~300 LOC)
- [ ] Routes `/esbtp/examens/*`
- [ ] Vues premium namespace `exp-*` :
  - `index.blade.php` (grille semaine + drill-down conflits)
  - `create.blade.php`, `edit.blade.php`
  - `convocations.blade.php` (preview PDF)
- [ ] Convocation PDF via Browsershot, settings tenant `convocation.template_*`
- [ ] Permissions : `lmd.examens.view`, `lmd.examens.manage`, `lmd.examens.notes_lock`
- [ ] Command `php artisan klassci:migrate-examens-to-table` (migration data existante)
- [ ] Tests Unit ExamenSchedulingService (~300 LOC)
- [ ] Tests Feature CRUD + conflits + migration command
- [ ] Tests Browser E2E

### PR10 — Workflow rattrapage (2j)

**Goal** : sessions rattrapage automatisées avec règles UEMOA.

**Livrables** :
- [ ] Migration `esbtp_lmd_sessions` (type, parent_session_id, status)
- [ ] Migration `esbtp_lmd_resultat_ecue` : ajout `note_session_normale`, `note_rattrapage`, `note_finale`, `rattrapage_eligible`, `rattrapage_inscrit`
- [ ] Model `ESBTPLMDSession.php` (~120 LOC)
- [ ] `app/Services/RattrapageSchedulingService.php` (~350 LOC) :
  - `genererSessionRattrapage(sessionNormale)`
  - `identifierEtudiantsEligibles(sessionId)`
  - `genererExamensRattrapage(sessionRattrapageId)`
  - `recalculerMoyennesAvecRattrapage(etudiantId, sessionId)` (setting `max` ou `replace`)
  - `notifierEtudiantsEligibles(sessionRattrapageId)`
- [ ] Vues premium namespace `rtp-*` :
  - `/esbtp/lmd/sessions/rattrapage` (landing)
  - `/esbtp/lmd/sessions/rattrapage/{id}` (détails session)
- [ ] AJAX no-reload partout
- [ ] Tests Unit RattrapageSchedulingService (~250 LOC)
- [ ] Tests Feature eligibility + recalcul (~450 LOC)
- [ ] Tests Browser session lifecycle (~250 LOC E2E)

### PR11 — Jury foundation (2j)

**Goal** : tables + service délibération avec calcul auto + override.

**Livrables** :
- [ ] Migration `esbtp_lmd_jurys`
- [ ] Migration `esbtp_lmd_jury_membres`
- [ ] Migration `esbtp_lmd_jury_decisions`
- [ ] Models avec Auditable trait
- [ ] `app/Services/JuryDeliberationService.php` (~600 LOC) :
  - `calculerDecisionAuto(etudiant, jury)` (settings tenant compensation, mention)
  - `appliquerDecisionsAuto(jury)`
  - `overrideDecision(jury, etudiant, nouvelleDecision, motif, vote)`
  - `genererPvDeliberation(jury)` (réservation numéro thread-safe + PDF)
  - `publierDecisions(jury)` (lock notes + notifications)
  - `verifierQuorum(jury)`
- [ ] Permissions : `lmd.jury.view`, `lmd.jury.preside`, `lmd.jury.deliberate`, `lmd.jury.publish`
- [ ] Tests Unit `calculerDecisionAuto` × 20 cas
- [ ] Tests Feature quorum + override + audit log

### PR12 — Jury UI premium AJAX (3j)

**Goal** : salle de délibération namespace `juy-*` avec workflow AJAX no-reload complet.

**Livrables** :
- [ ] `app/Http/Controllers/ESBTPLMDJuryController.php`
- [ ] Routes `/esbtp/lmd/jurys/*` (resourceful + actions custom)
- [ ] Vues namespace `juy-*` :
  - `index.blade.php` (liste sessions jury)
  - `show.blade.php` (salle délibération, tabs Composition/Délibération/Statistiques/PV)
  - `partials/_etudiant_decision_modal.blade.php` (modal override)
  - `partials/_signature_canvas_modal.blade.php` (canvas HTML5 signature)
- [ ] AJAX endpoints :
  - `PATCH /jurys/{id}/decisions/{etudiantId}` (override)
  - `POST /jurys/{id}/signatures` (signature digital)
  - `GET /jurys/{id}/kpis` (refresh KPIs live)
  - `POST /jurys/{id}/decisions/auto` (bulk apply décisions auto)
- [ ] Alpine `x-data` root maintient state global
- [ ] CustomEvent `jury:decision-updated` + listeners
- [ ] Tests Feature endpoints
- [ ] Tests Browser flow complet (~400 LOC E2E)

### PR13 — PV PDF officiel + archivage légal (1.5j)

**Goal** : template PV professionnel + numérotation thread-safe + storage 5 ans.

**Livrables** :
- [ ] Template `resources/views/pdf/lmd-jury-pv.blade.php` (~400 LOC)
  - 12 sections (header → footer)
  - Tableau étudiants × décisions
  - Statistiques globales
  - Signatures images base64
- [ ] `JuryDeliberationService::reserverNumeroPv()` avec DB lock
- [ ] Storage `storage/pv/{tenant}/{annee}/{numero}.pdf`
- [ ] Setting `lmd_pv_retention_years` (default 5)
- [ ] Audit log Auditable trait sur `esbtp_lmd_jurys.pv_*`
- [ ] Tests Feature numérotation race condition
- [ ] Tests Feature snapshot PDF content
- [ ] Tests Browser download flow

### PR14 — Tests E2E exhaustifs (2j)

**Goal** : Pest Browser suite complète sur tout le chantier.

**Livrables** :
- [ ] `tests/Browser/EmploiTemps/IndexFlowTest.php`
- [ ] `tests/Browser/EmploiTemps/ShowLmdFlowTest.php`
- [ ] `tests/Browser/EmploiTemps/BulkEditLmdFlowTest.php`
- [ ] `tests/Browser/EmploiTemps/AddSessionLmdFlowTest.php`
- [ ] `tests/Browser/SeanceCours/CreateLmdFlowTest.php`
- [ ] `tests/Browser/SeanceCours/CreateBtsFlowTest.php`
- [ ] `tests/Browser/SeanceCours/EditLmdFlowTest.php`
- [ ] `tests/Browser/SeanceCours/TypeReactiveSwitchTest.php`
- [ ] `tests/Browser/SeanceCours/EmbeddedModalStylesTest.php`
- [ ] `tests/Browser/LMD/PlanningExamensTabTest.php`
- [ ] `tests/Browser/LMD/ExamensWorkflowFullTest.php`
- [ ] `tests/Browser/Bulletin/BtsLmdRoutingGuardTest.php`
- [ ] `tests/Browser/Rattrapage/SessionLifecycleTest.php`
- [ ] `tests/Browser/Jury/DeliberationFullFlowTest.php`
- [ ] `tests/Browser/Jury/PvDownloadFlowTest.php`
- [ ] `tests/Browser/Embedded/StylesLoadedTest.php`

### PR15 — Documentation (1j)

**Goal** : CHANGELOG + landing FR/EN + docs API.

**Livrables** :
- [ ] `CHANGELOG.md` entrée mai 2026 complète
- [ ] `klassci-landing/content/docs/changelog.mdx` (FR) — entrée user-visible
- [ ] `klassci-landing/content/docs/changelog.en.mdx` (EN) — miroir
- [ ] `docs/api/EMPLOI_TEMPS_LMD.md`
- [ ] `docs/api/EXAMENS_LMD.md`
- [ ] `docs/api/JURY_DELIBERATION.md`
- [ ] Memory files marqués `status: applied`

### PR16 — Bonus features (3j)

**Goal** : 7 bonus pour blinder le chantier.

**Livrables** :
- [ ] Pre-commit hook `bin/pre-commit-hook.sh` (grep canonical patterns + view lint)
- [ ] PHPStan custom rule `phpstan-rules/CanonicalMatieresSourceRule.php`
- [ ] Dashboard `/admin/multi-tenant-health` (super-admin)
- [ ] Command `php artisan klassci:doctor --tenant=X`
- [ ] UI bulk-create examens dans LMD planning
- [ ] Composant partagé `<x-seance-cours-form>` (DRY entre 3 vues)
- [ ] Notification Laravel Echo planif zero détectée

### PR17 — Déploiement coordonné (0.5j)

**Goal** : push 6 tenants + pull + cache:clear + migrate + smoke tests.

**Livrables** :
- [ ] Push presentation → 5 autres branches tenants
- [ ] `klassci pull` × 6 tenants
- [ ] `klassci cache:clear` × 6 tenants
- [ ] `klassci migrate` × 6 tenants (avec --dry-run d'abord)
- [ ] `klassci permissions:fix` × 6 tenants
- [ ] Smoke tests via `scripts/lmd/post-deploy-smoke-test.ps1`
- [ ] Visual-check screenshots × 5 vues × 6 tenants

---

## 5. Stratégie de tests <a id="tests"></a>

### Matrice BTS / LMD (4 combos systématiques)

Chaque test Feature critique tourne sur ces 4 cas :

| Combo | Setup |
|---|---|
| **BTS pivot peuplé** | Classe BTS legacy, `esbtp_classe_matiere` pivot rempli |
| **BTS pivot vide** | Classe BTS moderne, pivot vide, planifs présentes |
| **LMD avec parcours** | Classe LMD, parcours assigné, UEs liées |
| **LMD tronc commun** | Classe LMD, filiere_id = mention_id, pas de parcours |

### Couches

| Couche | Tool | Cible | Volume |
|---|---|---|---|
| Unit | Pest | Services, Enums, Helpers | ~1500 LOC, 50 tests |
| Feature | Pest | Controllers, scope, edge cases, audit | ~3500 LOC, 95 tests |
| Browser E2E | Pest Browser | Flows complets utilisateur | ~2800 LOC, 35 tests |
| Visual | Browsershot script | Screenshots multi-tenant baseline diff | 60 screenshots |

### Skill `klassci-test-bts-lmd-matrix`

Automatise la matrice 4 combos × tous les tests Feature critiques.

---

## 6. Stratégie de déploiement <a id="deployment"></a>

### Branches tenants (rule projet `tenant-branches.md`)

```
presentation (dev canonical)
   ├── esbtp-abidjan
   ├── esbtp-yakro
   ├── ephrata
   ├── hetec
   └── rostan
```

### Workflow déploiement post-merge

```bash
# 1. Cross-branch push (script automatisé)
.\scripts\lmd\deploy-coordinated.ps1 -DryRun
.\scripts\lmd\deploy-coordinated.ps1

# 2. Smoke tests
.\scripts\lmd\post-deploy-smoke-test.ps1

# 3. Visual-check screenshots
.\scripts\lmd\visual-check-screenshots.ps1 -Baseline 2026-05-22-pre
.\scripts\lmd\visual-check-screenshots.ps1 -Compare
```

### Rollback strategy

- Chaque PR mergeable indépendamment → revert PR ciblé
- Migrations Phase 8-13 ont `down()` testé
- Cross-branch push : si bug détecté sur tenant X, `git push origin <commit-pre-deploy>:tenant-X --force-with-lease`

---

## 7. Journal d'exécution <a id="journal"></a>

| Date | PR | Action | Commits | Tests | Visual-check | Statut |
|---|---|---|---|---|---|---|
| 2026-05-22 | PR0 | Branche feature créée | — | — | — | 🟡 En cours |

(Mis à jour à chaque PR mergée)

---

## 8. Decisions log <a id="decisions"></a>

| Date | Décision | Raison | Source |
|---|---|---|---|
| 2026-05-22 | 2 méthodes publiques (pas flag boolean) sur MatiereTreeBuilder | Critic round 2 : flag boolean = smell d'avenir, signature documente intent | Iteration 3 |
| 2026-05-22 | Phase 1 examens = scope query `type_seance=EXAMEN` (pas table dédiée) | KLASSCI n'a pas convocations/anonymat/jury Apogée. YAGNI per DevAdvocate. | Iteration 2 |
| 2026-05-22 | Phase 8 = table dédiée `esbtp_examens_planifies` | Marcel : "rien différer", workflow scolarité complet | Iteration 4 (depth=7+) |
| 2026-05-22 | mapToType() retourne null pour TOUS types évaluation | Sémantiquement faux 'homework', Critic round 2 | Iteration 3 |
| 2026-05-22 | Strangler fig PR1 (deprecated mais préserve legacy) → PR2 supprime | Évite régression silencieuse 6 tenants pendant fenêtre PR1→PR2 | Critic round 2 |
| 2026-05-22 | AJAX no-reload obligatoire dans rule premium-redesign | Demande explicite Marcel | Iteration 4 |
| 2026-05-22 | Bulletin BTS et LMD restent séparés (déjà OK) | Marcel a confirmé séparation actuelle valide | Iteration 4 |
| 2026-05-22 | Signature jury via canvas HTML5 (option b) | À confirmer Marcel — DocuSign trop lourd, checkbox simple insuffisant pour valeur légale | À valider |
| 2026-05-22 | PV numérotation séquentielle thread-safe via DB lock | Risque race condition prod (Critic Q1 round 2) | Iteration 3 |

---

## Annexes

### Sources documentaires
- [Directive 03/2007/CM/UEMOA — LMD UEMOA](https://e-docucenter.uemoa.int/fr/directive-ndeg032007cmuemoa-portant-adoption-du-systeme-licence-master-doctorat-lmd-dans-les)
- [Manuel LMD CAMES 2023](https://www.lecames.org/wp-content/uploads/2023/11/Manuel_LMD_CAMES.pdf)
- [Apogée AMUE — Module Examens](https://www.amue.fr/offre-de-solutions-et-services/solutions-et-services/le-catalogue-de-formations/produit/apogee-gestion-des-epreuves-683)
- [Hyperplanning Index Education](https://www.index-education.com/fr/hyperplanning-gestion-planning-salle.php)
- Rule globale : `~/.claude/rules/klassci-classe-matieres.md`
- Rule projet : `.claude/rules/classe-lmd-filiere-as-mention.md`
- Rule projet : `.claude/rules/feature-delivery-methodology.md`

### Skills KLASSCI utiles
- `/plan-and-confirm` — replan si scope change
- `/visual-check` — check visuel après PR
- `/pr-review-toolkit:review-pr` — 4 agents review pre-merge
- `/klassci-lmd-bts-audit` (créé en PR0) — audit canonical patterns
- `/klassci-emploi-temps-deploy` (créé en PR0) — déploiement coordonné
- `/klassci-test-bts-lmd-matrix` (créé en PR0) — matrice tests 4 combos
- `/klassci-jury-lifecycle` (créé en PR0) — workflow jury

### Risk register
Cf section "Risks & attention points" Iteration 3 et 4 (intégrés dans rules + memory).
