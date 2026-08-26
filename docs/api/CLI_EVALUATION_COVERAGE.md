# API CLI Evaluation Coverage

## Endpoint

`GET /api/cli/evaluations/coverage`

Couverture des évaluations et des notes, agrégée par filière, pour un niveau académique.

Défaut : **1ère année BTS** de l'année universitaire courante.

## Authentification

```http
Authorization: Bearer <token-cli>
Accept: application/json
```

Le token doit avoir l'ability `cli:read`.

## Parametres

| Parametre | Type | Defaut | Description |
| --- | --- | --- | --- |
| `systeme` | string | `BTS` | `BTS` ou `LMD` |
| `year` | int | `1` | Année du niveau (`esbtp_niveau_etudes.year`) |
| `annee_id` | int | année courante | Année universitaire |
| `filiere_id` | int | null | Restreindre à une filière |
| `periode` | string | toutes | `semestre1`, `semestre2`, `annuel` |

## Exemple CLI

```powershell
.\klassci-cli.ps1 evaluations:coverage esbtp-abidjan
.\klassci-cli.ps1 evaluations:coverage esbtp-abidjan systeme=BTS year=1
.\klassci-cli.ps1 evaluations:coverage esbtp-abidjan filiere_id=2 periode=semestre1
```

## Reponse

Pour chaque filière : effectif, classes, puis par matière le nombre d'étudiants inscrits actifs ayant au moins une note numérique (`is_absent = 0` et `note IS NOT NULL`), plus la liste des évaluations (hors `cancelled`).

Les absents ne comptent pas dans `etudiants_avec_note`. Les classes de 2e année / LMD sont exclues quand `systeme=BTS` et `year=1`.

## Historique

- 2026-08-26: création — diagnostic CLI couverture notes 1ère année BTS par filière.
