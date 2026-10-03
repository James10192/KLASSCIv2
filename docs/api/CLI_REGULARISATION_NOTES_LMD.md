# API CLI — Régularisation de notes LMD

Saisir les notes d'un relevé officiel (souvent d'une année écoulée) quand les
évaluations n'ont jamais été créées. Un appel = un étudiant, une classe LMD, un
semestre. Nanan fait la même chose pour une classe entière avec
`proposer_releve_notes_lmd` (même service : `App\Domain\Notes\RegularisationDeNotesLmd`).

## `POST /api/cli/lmd/evaluations/regulariser-notes`

Ability : `cli:admin`. Simulation par défaut : `dry_run: false` est requis pour écrire.

```json
{
  "etudiant_id": 3089,
  "classe_id": 63,
  "annee_universitaire_id": 4,
  "periode": "semestre2",
  "date_regularisation": "2026-06-30",
  "motif": "Relevé officiel du semestre 2 transmis par l'établissement",
  "nature": "regularisation",
  "dry_run": true,
  "notes": [{ "matiere_id": 148, "note": 15 }]
}
```

- `periode` : semestre **absolu**, celui de la maquette et du bulletin LMD
  (`semestre3` ou `semestre4` pour une L2). Un semestre qui n'est pas l'un des
  deux de la classe est refusé.
- `date_regularisation` : jamais dans le futur.
- `nature` (facultatif) : `regularisation` (défaut) ou `examen`. Un relevé qui ne
  donne que la note d'examen, le contrôle continu venant à part, se saisit en
  `examen` : l'évaluation est de type `examen`, nommée `Examen SEMESTREn — <élément>`,
  et reste distincte du contrôle continu saisi ensuite.
  **Le type ne change aucun calcul** : le bulletin LMD fait la moyenne des
  évaluations de l'élément selon leur coefficient (1 ici), et la pondération
  contrôle continu / examen (`lmd_cc_weight`, `lmd_exam_weight`) n'est pas
  encore appliquée. Deux contrôles continus de coefficient 1 plus cet examen
  donnent donc un tiers chacun.
- Une note déjà saisie pour le même élément sous l'autre nature est **refusée**
  (`422`, qui nomme l'évaluation) : elle serait comptée deux fois. Même risque si
  l'examen est ensuite planifié dans le module des examens : ne pas le ressaisir.
- `notes[].matiere_id` : un élément (ECUE) de la maquette du semestre, tel que
  la classe le voit (`getEcuesEffectifs`, clé étrangère comprise). Au plus 40.
- L'étudiant doit être inscrit **activement** dans la classe, cette année-là.

Ce qui est écrit, une fois `dry_run: false` :

- une évaluation `Régularisation SEMESTREn — <élément>` par élément, **terminée**
  (donc lue par le bulletin LMD) et **non publiée** (invisible des étudiants) ;
- la note, avec l'année en clair (`annee_universitaire`), relue par la réinscription.

Rejouer le même appel est sans effet de bord : il remet à jour les notes, et
passe en `completed` une régularisation restée en brouillon (versions antérieures
au 2 octobre 2026).

Réponse (simulation) :

```json
{ "success": true, "data": { "dry_run": true, "evaluations_et_notes": [
  { "matiere_id": 148, "matiere": "Analyse numérique", "note": 15, "avant": null,
    "evaluation": "Régularisation SEMESTRE2 — Analyse numérique" } ] } }
```

Refus : `422`, `{"success": false, "message": "...", "errors": {...}}`
(classe non LMD, étudiant non inscrit, semestre hors classe, élément hors
maquette, date future).

## Requalifier après coup

Un relevé déjà saisi en `regularisation` qui contenait en fait les notes d'examen
se requalifie sans ressaisie : écran des notes LMD (fenêtre de la classe, bandeau
« évaluations de régularisation »), ou Nanan (`proposer_requalification_examen`).
Les évaluations prennent le titre et le type d'un examen ; les notes ne changent
pas. Service : `App\Domain\Notes\RequalificationEnExamen`.

## Historique

- 3 octobre 2026 : paramètre `nature` (`regularisation` | `examen`), aussi pour
  Nanan (`proposer_releve_notes_lmd`). Sans lui, rien ne change.
- 2 octobre 2026 : semestre absolu, évaluations terminées au lieu de brouillon,
  année écrite en clair sur la note, maquette lue par clé étrangère, date future
  refusée. Service partagé avec Nanan.
