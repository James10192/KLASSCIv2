# API Examens LMD

Workflow scolarité UEMOA — planification, surveillance, convocations, lock notes.

## Préfixe routes

`/esbtp/examens/*` — middleware `auth + admin.access + paywall + lmd.examens.*`

## Permissions

| Permission | Action |
|---|---|
| `lmd.examens.view` | Lecture (index, show, convocations preview/download, KPIs) |
| `lmd.examens.manage` | CRUD + bulk-generate + assignation surveillants |
| `lmd.examens.notes_lock` | Verrouillage anti-tampering des notes |

## Endpoints

### GET `esbtp.examens.index`
Page premium avec hero KPIs + filtres + table chronologique.
**Query** : `annee_universitaire_id`, `classe_id`, `type` (EXAMEN|PARTIEL|RATTRAPAGE|SOUTENANCE), `status`, `from`, `to`.

### GET `esbtp.examens.kpis`
**JSON** : `{ total, a_venir, en_cours, notes_lockees }`.
**Throttle** : 120/min.

### POST `esbtp.examens.bulk-generate`
Génération idempotente d'examens pour un scope (classe + semestre + type).
**Body JSON** : `{ classe_id, annee_universitaire_id, semestre, type_examen, date_premier_examen?, session_id? }`.
**Response** : `{ success, created_count, examens: [{ id, titre, date_debut, numero_convocation }] }`.

### POST `esbtp.examens.surveillants.assign`
**Body** : `{ user_ids: [int], role?: "surveillant"|"surveillant_principal"|"secretaire"|"responsable_salle" }`.
**Response** : `{ success, assigned_count, surveillants: [{ id, user_id, user_name, role, confirmed }] }`.

### POST `esbtp.examens.lock-notes`
**Permission** : `lmd.examens.notes_lock`.
Anti-tampering : `notes_locked=true` + `notes_locked_at` + `notes_locked_by` + status `notes_locked`.
Audit log via Auditable trait.

### GET `esbtp.examens.convocations.preview`
PDF inline (Content-Disposition: inline). **Throttle** : 60/min.
Mêmes query params que index pour filtrage.

### GET `esbtp.examens.convocations.download`
PDF attachment. **Throttle** : 10/min.

## Format numéro convocation

`CONV-{TENANT_CODE}-{ANNEE_LIBELLE}-{SEQ_4DIGITS}`
Exemple : `CONV-PRESENTATION-20252026-0042`
Thread-safe via DB `lockForUpdate`.

## Settings tenant

Aucun obligatoire pour ce module — settings hérités du jury et rattrapage.

## Modèles

- `App\Models\ESBTPExamenPlanifie` — Auditable whitelist (titre, type_examen, dates, salle, coefficient, bareme, numero_convocation, is_anonymous, status, notes_locked)
- `App\Models\ESBTPExamenSurveillant` — pivot examen+user

## Migration data legacy → table dédiée

Commande Artisan à créer en PR ultérieure : `php artisan klassci:migrate-examens-to-table`.
Pour l'instant : créer manuellement via UI ou bulk-generate.
