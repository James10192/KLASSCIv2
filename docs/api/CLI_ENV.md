# API CLI — clés `.env` gérables à distance

Poser un réglage d'environnement sur une instance **sans SSH ni cPanel**, par le
jeton CLI de l'instance (capacité `cli:admin`). Seules les clés d'une liste blanche
sont écrivables : `App\Services\Deployment\CleEnvAutorisee`. Une clé s'y ajoute par
une modification de code revue, jamais au vol.

## Clés gérables

| Clé | Secrète | Contrôle | Usage |
|---|---|---|---|
| `REINSCRIPTION_PORTAL_SECRET` | oui | ≥ 32 caractères | secret partagé avec le portail public de réinscription |
| `TENANT_CODE` | non | minuscules, chiffres, tirets, 3 à 32 | code de l'instance (quotas, identité publique) |
| `MASTER_SUPPORT_TOKEN` | oui | exactement `kc_` + 12 minuscules/chiffres + `_` + 40 lettres/chiffres (56 caractères) | identifiant KLASSCI Care émis par le Master |

Une valeur secrète ne ressort jamais : la lecture n'en donne qu'une empreinte.

## `GET /api/cli/env`

Rend, pour chaque clé gérable : `cle`, `definie`, `empreinte`, `longueur_min`,
`description` ; et, pour une clé non secrète seulement, `valeur` et `format_attendu`.

## `POST /api/cli/env`

Corps : `{"cle": "…", "valeur": "…"}`. Écrit la clé, purge le cache de configuration
(si cette purge échoue, la clé reste écrite mais l'appel répond en erreur : une
valeur sans effet n'est jamais annoncée comme posée), et journalise l'empreinte.

| Code | Cause |
|---|---|
| 403 | jeton sans `cli:admin` |
| 422 | clé hors liste blanche (le message énumère les clés gérables), valeur trop courte, format non respecté, caractère interdit |
| 500 | `.env` illisible ou non inscriptible, cache de configuration non purgeable |

## Activer KLASSCI Care sur une instance

1. Sur adminKlassci : `php artisan care:identifiant <code>` — le jeton ne s'affiche qu'une fois.
2. Depuis le poste : `klassci env <code> MASTER_SUPPORT_TOKEN --value=kc_…`
3. Le bouton « Aide / Signaler un problème » apparaît au chargement suivant, à condition
   que `MASTER_API_URL` soit aussi renseigné sur l'instance (il l'est dès que le paywall
   interroge le Master).

Ne pas utiliser `--generate` pour cette clé : un jeton inventé n'est connu d'aucun
Master, et le format le refuse de toute façon.

## Historique

- **Septembre 2026** — ajout de `MASTER_SUPPORT_TOKEN`. Pas de changement cassant :
  les deux clés existantes et le contrat des deux routes sont inchangés.
