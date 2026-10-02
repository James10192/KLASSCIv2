# API CLI — Statut redoublant

Lire, recenser et établir le statut « redoublant » des inscriptions.

| | |
|---|---|
| Base | `/api/cli` |
| Authentification | Bearer Sanctum |
| Contrôleur | `App\Http\Controllers\API\CLI\CLIStatutRedoublantController` |
| Règle | `App\Domain\Inscriptions\StatutRedoublant` |
| Commande équivalente | `php artisan inscriptions:recenser-redoublants [--apply]` |

## Ce que dit le statut

Une inscription porte `is_redoublant` et l'origine de cette valeur,
`redoublant_source` :

| source | sens |
|---|---|
| `null` | jamais établi (inscription créée avant octobre 2026, pas encore recensée) |
| `deduit` | posé par le logiciel : même niveau d'étude que l'année d'avant |
| `confirme` | une personne habilitée a validé la valeur déduite |
| `corrige` | une personne habilitée l'a changée, motif à l'appui (`redoublant_motif`) |

Une valeur confirmée ou corrigée n'est jamais réécrite par une déduction, sauf
si l'inscription change de niveau : la confirmation portait sur l'ancien.

`decision_reinscription` garde la décision choisie à la réinscription
(`passage`, `redoublement`, `rattrapage`). Elle vivait seulement en tête de
`reinscription_observations`, d'où le recensement la reprend.

Une inscription est « à confirmer » quand personne n'a tranché ET que la
question se pose : réinscription, transfert, ou valeur déduite « oui ».

## `GET /inscriptions/redoublants` — ability `cli:read`

Recensement à blanc, plus le nombre d'inscriptions à confirmer par année.

```json
{
  "examinees": 4210, "redoublants": 188, "a_poser": 3950, "changees": 61,
  "decisions": 1402, "indeterminees": 0, "etablies": 12, "ecrit": false,
  "a_confirmer_par_annee": { "2025-2026": 942, "2026-2027": 310 }
}
```

- `a_poser` : inscriptions dont le statut serait écrit.
- `changees` : parmi elles, celles dont la valeur passerait de non à oui ou l'inverse.
- `indeterminees` : inscriptions d'une année sans date de début, laissées telles quelles.
- `etablies` : confirmées ou corrigées par une personne, jamais touchées.

## `POST /inscriptions/redoublants/recenser` — ability `cli:admin`

| champ | type | |
|---|---|---|
| `apply` | booléen | sans lui, à blanc |

Pose la valeur déduite sur toutes les années, et reprend `decision_reinscription`.
La migration `2026_10_02_170015_recenser_le_statut_redoublant` le fait déjà au
déploiement ; à relancer seulement si le journal signale qu'il a échoué :

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" "$BASE/api/cli/inscriptions/redoublants/recenser" -d apply=1
```

Rejouable : une deuxième course n'écrit rien. À relancer aussi après avoir
modifié la date de début d'une année : les déductions en dépendent.

## `POST /inscriptions/{id}/redoublant` — ability `cli:write`

| champ | type | |
|---|---|---|
| `valeur` | booléen | requis |
| `motif` | texte ≤ 500 | requis (≥ 10 caractères) si la valeur change |

Réponse : l'état affiché sur la fiche (`valeur`, `etat`, `libelle`, `detail`,
`motif`, `incoherence`, `a_confirmer`). 422 si le motif manque.

## Historique

- **Octobre 2026** — création.
