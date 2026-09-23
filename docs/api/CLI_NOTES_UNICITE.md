# API CLI — Unicité des notes

Une seule note **en vigueur** (ni effacée ni archivée) par élève et par évaluation.
La base le garantit par une colonne générée `note_vivante` et l'index unique
`esbtp_notes_etudiant_evaluation_vivante_unique (etudiant_id, evaluation_id, note_vivante)`.

La migration `2026_09_23_062649_add_unique_note_vivante_to_esbtp_notes_table`
pose la colonne partout, mais **ne pose pas l'index** sur une instance qui porte
déjà des notes en double : elle le journalise (`Log::warning`) et passe. Elle ne
repassera pas. Ces deux routes permettent de finir le travail sans SSH.

Aucune note n'est jamais effacée par ces routes : laquelle garder est une
décision d'école.

## `GET /api/cli/diagnostics/notes-doublons`

Ability : `cli:read`.

```json
{
  "success": true,
  "data": {
    "index_pose": false,
    "doublons": [
      { "etudiant_id": 412, "evaluation_id": 58, "nombre": 2, "note_ids": [9120, 9133] }
    ]
  }
}
```

- `index_pose` : l'unicité est-elle en place.
- `doublons` : les paires élève × évaluation qui portent plusieurs notes en
  vigueur. Des jumelles archivées n'y figurent pas : elles ne heurtent pas
  l'index, et au retour de l'élève dans sa classe une seule est rendue vivante
  (la plus récente).

## `POST /api/cli/notes/unicite`

Ability : `cli:admin`. Pose l'index s'il ne reste aucun doublon.

- `200` : unicité en place (réponse identique au diagnostic, `index_pose: true`).
- `409` : il reste des doublons ; `errors` porte le même état que le diagnostic.
- `422` : base autre que MySQL.

Équivalent serveur : `php artisan notes:unicite`.

## Déploiement

L'ajout d'une colonne `STORED` reconstruit `esbtp_notes`, écritures bloquées le
temps de l'opération. Sur les grosses instances, migrer hors des heures de
saisie des notes. Puis appeler le diagnostic : `index_pose: false` signale des
doublons à trancher.

## Historique

- **23 septembre 2026** — création des deux routes.
