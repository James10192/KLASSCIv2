# API Rattrapage LMD UEMOA

Workflow 2 sessions (normale → rattrapage) avec recalcul des notes.

## Préfixe routes

`/esbtp/lmd/rattrapage/*` — middleware `auth + admin.access + module.lmd.access + paywall + lmd.rattrapage.*`

## Permissions

| Permission | Action |
|---|---|
| `lmd.rattrapage.view` | Lecture (index, show) |
| `lmd.rattrapage.manage` | Créer session, lancer rattrapage, recalculer, inscrire, publier |

## Endpoints

### GET `esbtp.lmd.rattrapage.index`
Vue premium avec hero KPIs + table sessions + modal create.

### POST `esbtp.lmd.rattrapage.store`
**Body** : `{ annee_universitaire_id, parcours_id?, type: "normale"|"rattrapage"|"extra", parent_session_id?, semestre?, libelle, date_debut?, date_fin? }`.

### POST `esbtp.lmd.rattrapage.lancer`
**Permission** : `lmd.rattrapage.manage`. **Throttle** : 10/min.

Workflow cascade : génère session rattrapage enfant + identifie éligibles + crée examens RATTRAPAGE.
**Body** : `{ date_debut? }`.
**Response** : `{ success, session_rattrapage: { id, libelle, date_debut }, eligibles_count, examens_count }`.

Guards :
- session parent doit être de type `normale`
- session parent doit être `completed` ou `published`

### POST `esbtp.lmd.rattrapage.recalculer`
Recalcule `note_finale` pour tous les éligibles (ou subset via `etudiant_ids`).
Setting `lmd_rattrapage_replace` (default false) :
- `false` : `note_finale = max(note_session_normale, note_rattrapage)`
- `true` : `note_finale = note_rattrapage` (remplace)

**Body** : `{ etudiant_ids?: [int] }`.

### POST `esbtp.lmd.rattrapage.inscrire`
Marque les éligibles comme inscrits en 2e session (idempotent bulk update).
**Body** : `{ etudiant_ids?: [int] }`.

### POST `esbtp.lmd.rattrapage.publier`
Status → `published`, horodatage `published_at` + `published_by`.

## Colonnes ajoutées à `esbtp_lmd_resultats_ecues`

- `note_session_normale` decimal(5,2) — snapshot avant rattrapage
- `note_rattrapage` decimal(5,2) — 2e session
- `note_finale` decimal(5,2) — recalcul max|replace
- `rattrapage_eligible` boolean — auto computed via seuil
- `rattrapage_inscrit` boolean — manuel/bulk

Index : `idx_ecue_rattrapage (rattrapage_eligible, rattrapage_inscrit)`.

## Settings tenant

- `lmd_seuil_validation_ecue` (default `10`) — seuil sous lequel ECUE = éligible rattrapage
- `lmd_rattrapage_replace` (default `false`) — mode recalcul note_finale
