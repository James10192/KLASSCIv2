# API CLI — Accès temporaires

Une permission ouverte à une personne jusqu'à une date. L'accès cesse de lui-même à
l'échéance : c'est la date qui décide, aucune tâche planifiée n'a besoin de tourner.
Rien n'est écrit dans les rôles ni dans les permissions directes de Spatie.

Même logique que l'écran `/esbtp/acces-temporaires` : les deux passent par
`App\Domain\Permissions\AccesTemporaires`, donc refusent les mêmes choses.

## Ce qui est refusé (422, `code: ACCES_REFUSE`)

- une permission absente du registre `config/permissions.php` ;
- une permission non accordable pour un temps limité :
  - `identity.*` et `admin.access`, lues par `hasAnyPermission()`, qui ne voit pas les
    accès temporaires ;
  - ce qui modifie les comptes, les rôles, les réglages ou l'abonnement (`users.manage`,
    `personnel.manage`, `settings.edit`, `settings.pdf.manage`, `coordinateurs.*`,
    `system.*`, `paywall.*`, `module.*`, `admin.system.security`,
    `security.users.monitor`, `permissions.temporaires.manage`) : le bénéficiaire
    pourrait s'en servir pour se donner un accès qui survivrait à l'échéance ;
- un bénéficiaire étudiant (compte partagé avec les parents) ;
- une fin passée, ou antérieure au début ;
- une durée au-delà de `permissions.temporaires.duree_max_jours` (90 jours par défaut,
  pas encore exposé dans l'écran des réglages) ;
- un auteur qui ne détient pas lui-même la permission de façon permanente
  (superAdmin excepté) ;
- un bénéficiaire qui la détient déjà par son rôle ;
- un accès actif à la même permission qui couvre déjà la période.

## Ce qui est couvert

Tout ce qui passe par `can()`, `@can` et le middleware `permission:`. La porte
`finances.etudiants.voir` lit aussi les accès temporaires. Une ability posée par
`Gate::define` qui répond `false` n'est pas couverte (le `Gate::after` ne renverse
qu'un résultat nul).

Les dates portant un décalage (`2026-09-30T10:00+01:00`) sont converties dans le
fuseau de l'application avant d'être enregistrées.

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
