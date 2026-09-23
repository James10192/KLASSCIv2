# API CLI — Accès temporaires

Une permission ouverte à une personne jusqu'à une date. L'accès cesse de lui-même à
l'échéance : c'est la date qui décide, aucune tâche planifiée n'a besoin de tourner.
Rien n'est écrit dans les rôles ni dans les permissions directes de Spatie.

Même logique que l'écran `/esbtp/acces-temporaires` : les deux passent par
`App\Domain\Permissions\AccesTemporaires`, donc refusent les mêmes choses.

## Ce qui est refusé (422, `code: ACCES_REFUSE`)

- une permission absente du registre `config/permissions.php` ;
- une permission non accordable pour un temps limité : `identity.*`, `admin.access`,
  `module.technical_support.access`, `permissions.temporaires.manage`, `*`. Elles sont
  lues par `hasAnyPermission()`, qui ne voit pas les accès temporaires ;
- une fin passée, ou antérieure au début ;
- une durée au-delà du réglage d'instance `permissions.temporaires.duree_max_jours`
  (90 jours par défaut) ;
- un auteur qui ne détient pas lui-même la permission de façon permanente
  (superAdmin excepté) ;
- un bénéficiaire qui la détient déjà par son rôle ;
- un accès actif à la même permission qui couvre déjà la période.

## Ce qui est couvert

Tout ce qui passe par `can()`, `@can` et le middleware `permission:`. La porte
`finances.etudiants.voir` lit aussi les accès temporaires.

## Endpoints

### `GET /api/cli/user/{id}/acces-temporaires` — `cli:read`

Liste les accès de l'utilisateur, du plus récent au plus ancien.

```json
{ "user_id": 413, "acces": [ { "id": 1, "permission": "notes.edit", "debut": "…",
  "fin": "…", "statut": "active", "motif": "…", "accorde_par": 1, "retire_le": null } ] }
```

`statut` : `active`, `a_venir`, `expiree` ou `retiree`.

### `POST /api/cli/user/{id}/acces-temporaires` — `cli:admin`

| Champ | Requis | Description |
|---|---|---|
| `permission` | oui | Nom canonique ou alias legacy |
| `fin` | oui, sauf si `duree_heures` | Date de fin |
| `duree_heures` | oui, sauf si `fin` | Durée comptée depuis `debut` |
| `debut` | non | Maintenant par défaut |
| `motif` | oui | 10 caractères minimum, conservé dans l'historique |

L'auteur enregistré est le titulaire du jeton.

### `POST /api/cli/acces-temporaires/{grant}/retirer` — `cli:admin`

Retire l'accès immédiatement. La ligne reste en base avec `revoked_at` et `revoked_by`.

## Historique

- **Septembre 2026** — création.
