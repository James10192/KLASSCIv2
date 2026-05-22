# API Jury de délibération LMD UEMOA

Workflow complet : composition → délibération → PV PDF officiel → archivage légal 5 ans.

## Préfixe routes

`/esbtp/lmd/jurys/*` — middleware `auth + admin.access + module.lmd.access + paywall + lmd.jury.*`

## Permissions

| Permission | Action |
|---|---|
| `lmd.jury.view` | Lecture (index, show, KPIs, PV preview/download) |
| `lmd.jury.preside` | Créer + supprimer jury + ajouter/retirer membres |
| `lmd.jury.deliberate` | Override décision (motif obligatoire) + signer + appliquer auto |
| `lmd.jury.publish` | Générer PV + publier décisions |

## Endpoints

### GET `esbtp.lmd.jurys.index`
Vue premium : hero KPIs (total, préparation, en_cours, publiés) + table jurys + modal create.

### GET `esbtp.lmd.jurys.show`
**Salle de délibération** : 4 tabs Alpine (Composition / Délibération / Statistiques / PV).
Pré-calcul `quorum` + `stats`. Membres + décisions chargés en relations.

### POST `esbtp.lmd.jurys.store`
**Body** : `{ annee_universitaire_id, session_id?, parcours_id?, classe_id?, semestre?, libelle, date_jury?, observations? }`.
Status initial : `preparation`.

### POST `esbtp.lmd.jurys.membres.store`
**Body** : `{ user_id, role: "president"|"assesseur"|"secretaire"|"consultatif", present? }`.
**Response** : `{ success, membre, quorum }`.
Idempotent (update existing si déjà présent).

### POST `esbtp.lmd.jurys.membres.signer`
Enregistre signature canvas HTML5 base64 ou JSON checkbox (preuve légale).
**Body** : `{ signature_data: string (max 200000) }`.
Capture IP + User-Agent automatiquement.

### POST `esbtp.lmd.jurys.decisions.auto`
**Permission** : `lmd.jury.deliberate`. **Throttle** : 10/min.
Applique en bulk les décisions auto pour tous les étudiants concernés.
Idempotent : préserve les overrides existants (`override_par_jury=true`).
Status jury → `en_cours`.

### PATCH `esbtp.lmd.jurys.decisions.override`
**Body** : `{ decision: "admis"|"admission_rattrapage"|"ajourne"|"exclu"|"admis_sous_condition"|"defere", motif: string (min 5), vote_resultat?: "unanime"|"majorite"|"partage_voix_president" }`.

Guards :
- jury pas locked (PV pas généré)
- décision valide (in enum)
- motif non vide

### GET `esbtp.lmd.jurys.kpis`
**Response** : `{ stats, quorum }` — pour refresh live de la hero.

### POST `esbtp.lmd.jurys.pv.generer`
**Permission** : `lmd.jury.publish`. **Throttle** : 10/min.

Pipeline :
1. Vérifie quorum (422 si KO)
2. Réserve numéro PV thread-safe (DB lockForUpdate)
3. Génère PDF via DomPDF template `pdf/lmd-jury-pv.blade.php`
4. Stocke `storage/pv/{tenant}/{annee}/{numero}.pdf`
5. **Lock toutes les décisions** (`locked=true` + `locked_at`)
6. Audit log
7. Status jury → `clos`

**Response** : `{ success, pv: { numero, path, genere_at, download_url } }`.

### GET `esbtp.lmd.jurys.pv-preview` / `pv-download`
Stream PDF depuis storage (inline ou attachment).
404 si pas encore généré.

### POST `esbtp.lmd.jurys.publier`
Guard : `pv_genere_at` doit exister.
Status → `publie`, horodatage.

## Format numéro PV

`PV-{ANNEE_LIBELLE}-{TENANT_CODE}-{SEQ_4DIGITS}`
Exemple : `PV-20252026-PRESENTATION-0042`

## Décisions canoniques UEMOA

| Décision | Sens |
|---|---|
| `admis` | Moyenne ≥ seuil + tous crédits validés |
| `admission_rattrapage` | Éligible 2e session sur ECUE non validés |
| `ajourne` | 2e session échouée → repasse année |
| `exclu` | Exclusion académique (motif obligatoire) |
| `admis_sous_condition` | Crédits manquants mais jury accepte (motif obligatoire) |
| `defere` | Cas exceptionnels (médical, force majeure) |

## Mentions UEMOA (seuils configurables)

| Mention | Seuil |
|---|---|
| Passable | ≥ `lmd_mention_p_threshold` (default 10) |
| Assez Bien | ≥ `lmd_mention_ab_threshold` (default 12) |
| Bien | ≥ `lmd_mention_b_threshold` (default 14) |
| Très Bien | ≥ `lmd_mention_tb_threshold` (default 16) |
| Excellent | ≥ 18 (hardcoded — rare) |

## Quorum

- `lmd_jury_quorum_min` (default 2) — membres présents minimum
- `lmd_jury_quorum_assesseurs_min` (default 1) — assesseurs présents min
- Président obligatoire (sinon quorum KO)
- Secrétaire recommandé (warning seulement)

## Archivage légal

- Setting `lmd_pv_retention_years` (default 5)
- Soft delete uniquement (`SoftDeletes` trait)
- Audit log immutable via `OwenIt\Auditing\Auditable` whitelist
- Stockage `storage/app/pv/{tenant}/{annee}/{numero}.pdf`

## Signature digital

Deux modes selon implémentation client :
- **Canvas HTML5** : `data:image/png;base64,iVBORw0KGgo...` (rendu dans PV PDF)
- **Checkbox** : JSON `{checked, ip, ts}` (mention "Signé électroniquement" dans PV PDF)

Métadonnées capturées : `signature_at` + `signature_ip` + `signature_user_agent`.

## Modèles

- `App\Models\ESBTPLMDJury` — Auditable, scope `notLocked`, helpers `isLocked()` `isPublished()`
- `App\Models\ESBTPLMDJuryMembre` — Auditable, helper `hasSigned()`
- `App\Models\ESBTPLMDJuryDecision` — Auditable, scope `notLocked`, helper `isLocked()`
