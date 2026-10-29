# CLI — Lire les élèves et les inscriptions d'une autre année

`GET /api/cli/students` et `GET /api/cli/inscriptions` — lecture seule, jeton `cli:read`.

## Paramètre `annee_id`

| Valeur | Année lue |
|---|---|
| absent | l'année en cours (comportement historique) |
| identifiant d'année | cette année, écoulée ou en préparation |
| identifiant inconnu | refusé : 422 `UNKNOWN_ACADEMIC_YEAR`, jamais de repli sur l'année en cours |

Les deux réponses portent `annee` : le nom de l'année effectivement lue.

## Qui compte comme élève (`students`)

Toujours un dossier mené jusqu'à l'élève (`workflow_step = etudiant_cree`).
Un prospect ou un candidat qui n'a pas abouti n'est jamais un élève.

| Année | Statuts retenus |
|---|---|
| en cours | `active` |
| autre | `active` et `terminée` (une inscription close par une spécialisation de tronc commun, par exemple) |

Si l'élève a deux inscriptions dans l'année, la classe affichée est celle qui
reste active.

`inscriptions` ne filtre aucun statut, quelle que soit l'année : `status` et
`workflow_step` se passent en paramètres.

## Pourquoi

Sans `annee_id`, un élève inscrit seulement l'an passé ne sortait d'aucune
recherche. Avant une reprise d'année écoulée
(`POST /api/cli/reprise/inscriptions-annee-ecoulee`, qui ne reconnaît un élève
que par son matricule), on concluait qu'il n'existait pas et on le recréait.
