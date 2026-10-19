# API CLI — Accès individuels

Une permission accordée à **une seule personne**, sans modifier son rôle ni ses
permissions Spatie. Deux modes : `temporaire` (expiration automatique à la date
prévue) et `definitif` (sans expiration, révocation manuelle obligatoire).
Les anciennes requêtes sans `mode` conservent le comportement temporaire.

Même logique que l'écran `/esbtp/acces-individuels` (ancienne URL
`/esbtp/acces-temporaires` conservée) : les deux passent par
`App\Domain\Permissions\AccesTemporaires`, donc refusent les mêmes choses.

## Ce qui est refusé (422, `code: ACCES_REFUSE`)

- une permission absente du registre `config/permissions.php` ;
- une permission non accordable individuellement, y compris en mode définitif :
  - `identity.*` et `admin.access`, lues par `hasAnyPermission()`, qui ne voit pas les
    accès temporaires ;
  - ce qui modifie les comptes, les rôles, les réglages ou l'abonnement (`users.manage`,
    `personnel.manage`, `settings.edit`, `settings.pdf.manage`, `system.*`, `paywall.*`,
    `module.*`, `admin.system.security`, `security.users.monitor`,
    `security.backup.restore`, `permissions.temporaires.manage`,
    `permissions.individuels.permanents.manage`, `sod.bypass` et les
    permissions de contournement des contrôles financiers) : ces droits
    permettraient une élévation de privilèges ou la neutralisation des gardes ;
  - dans les familles de comptes du personnel (`directeurs_etudes`,
    `responsables_scolarite`, `services_scolarite`, `agents_inscription`,
    `secretaires`, `comptables`, `caissiers`, `coordinateurs`, `teachers`), tout
    sauf `.view` ;
- un bénéficiaire étudiant (compte partagé avec les parents) ;
- pour un temporaire : fin passée, antérieure au début, début trop lointain, ou
  durée dépassant `permissions.temporaires.duree_max_jours` (90 jours par défaut) ;
- pour un définitif : une date de début/fin transmise par API, ou un auteur sans
  **les trois permissions permanentes** `personnel.manage`,
  `permissions.temporaires.manage`, `permissions.individuels.permanents.manage`
  (superAdmin excepté) ;
- une auto-attribution de permission, même par le superAdmin ;
- un auteur qui ne détient pas lui-même la permission de façon permanente
  (superAdmin excepté) ;
- un bénéficiaire qui la détient déjà par son rôle ;
- un accès non révoqué à la même permission couvrant déjà la période,
  avec sérialisation transactionnelle des octrois concurrents.

## Ce qui reste accordable, et pourquoi

`students.*` et `inscriptions.*` restent accordables : leurs chemins de création de
compte écrivent le rôle `etudiant` en dur. Un bénéficiaire peut donc créer un compte
étudiant, ou réinitialiser le mot de passe d'un étudiant, et garder cet accès de
niveau étudiant après l'échéance — jamais un accès du personnel.

`AccesTemporairesNonAccordablesTest` vérifie le reste : il relève chaque route
d'écriture d'un contrôleur qui pose un rôle, une permission ou un mot de passe, et
échoue si l'une de ses gardes est accordable (en supposant que le bénéficiaire
détient déjà les autres).

## Ce qui est couvert

Tout ce qui passe par `can()`, `@can` et le middleware `permission:`. La porte
`finances.etudiants.voir` lit aussi les accès individuels. Une ability posée par
`Gate::define` qui répond `false` n'est pas couverte (le `Gate::after` ne renverse
qu'un résultat nul).

Les dates portant un décalage (`2026-09-30T10:00+01:00`) sont converties dans le
fuseau de l'application avant d'être enregistrées.

## Endpoints

### `GET /api/cli/user/{id}/acces-temporaires` — `cli:read`

Liste les accès de l'utilisateur, du plus récent au plus ancien.

```json
{ "data": { "user_id": 413, "acces": [ { "id": 1, "permission": "notes.edit", "debut": "…",
  "fin": null, "mode": "definitif", "statut": "definitif", "motif": "…", "accorde_par": 1, "retire_le": null } ] } }
```

`statut` : `active`, `definitif`, `a_venir`, `expiree` ou `retiree`.
Une ancienne ligne avec `fin: null` ne devient **jamais** définitive par défaut :
elle reste inactive si son attribut explicite `is_permanent` est faux.

### `POST /api/cli/user/{id}/acces-temporaires` — `cli:admin`

| Champ | Requis | Description |
|---|---|---|
| `permission` | oui | Nom canonique ou alias legacy |
| `mode` | non | `temporaire` (défaut) ou `definitif` |
| `fin` | temporaire, sauf si `duree_heures` | Date de fin ; interdit en définitif |
| `duree_heures` | temporaire, sauf si `fin` | Durée depuis `debut` ; interdit en définitif |
| `debut` | non | Maintenant par défaut en temporaire ; interdit en définitif |
| `motif` | oui | 10 caractères minimum, conservé dans l'historique |

L'auteur enregistré est le titulaire du jeton. `cli:admin` seul ne suffit **pas**
à accorder un accès définitif sans les habilitations permanentes mentionnées.

### `POST /api/cli/acces-temporaires/{grant}/retirer` — `cli:admin`

Retire l'accès immédiatement. La ligne reste en base avec `revoked_at` et `revoked_by`.
La révocation d'un définitif exige les mêmes habilitations supplémentaires que
son octroi. Les actions sont journalisées et les anciennes routes CLI restent
compatibles.

## Historique

- **Octobre 2026** — accès individuels définitifs, contrôles d'habilitation
  supplémentaires, historique paginé à l'écran et confirmation centrale AJAX.
- **Septembre 2026** — création des accès temporaires.
