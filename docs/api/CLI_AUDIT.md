# CLI — Journal d'audit

Deux points d'accès pour regarder la table `audits` d'une instance sans SSH.

## GET /api/cli/audit/etat

Droit du jeton : `cli:read`.

Rend la taille de la table, le nombre de consultations héritées, la date de la
plus ancienne ligne, et **mesure sur place** les trois requêtes que le journal
lance sur « Depuis le début » : la liste (51 lignes), le compte « À regarder » et
le compte des tâches automatiques. Pour chacune : la durée réelle en
millisecondes, le résultat, et le plan MySQL (`type`, `key`, `rows`, `Extra`).

```json
{
  "lignes": 812344,
  "consultations": 604112,
  "plus_ancienne": "2025-07-09 01:22:00",
  "depuis_le_debut": {
    "liste":        { "ms": 12,  "resultat": 51,  "plan": [{ "type": "index", "key": "audits_created_at_idx", "rows": 102, "Extra": "Using where" }] },
    "a_regarder":   { "ms": 840, "resultat": 213, "plan": [...] },
    "automatiques": { "ms": 310, "resultat": 5120, "plan": [...] }
  }
}
```

Les deux comptes sont gardés une minute en cache par l'écran : un compte lent
ne se paie qu'une fois par minute et par filtre.

## POST /api/cli/audit/purger-consultations

Droit du jeton : `cli:admin`. Corps : `{ "dry": true, "lot": 5000 }`.

Les consultations (`event = retrieved`) étaient écrites à chaque lecture d'une
fiche, jusqu'à leur retrait de `config/audit.php` (septembre 2026). Elles ne
portent ni valeur ni changement, et le journal les ignore déjà : les supprimer
n'enlève aucune information, seulement du volume.

- **Simulation par défaut** : sans `"dry": false`, rien n'est supprimé, la
  réponse donne `a_supprimer`.
- Suppression par lots (`lot`, entre 500 et 20 000), par identifiant croissant.
- L'appel s'arrête au bout de 20 secondes pour rester sous le délai de
  l'hébergement : si `restantes` est supérieur à zéro, relancer.
- Chaque purge réelle est journalisée (`Log::warning`, avec l'auteur).

```bash
python3 cli.py esbtp-abidjan POST audit/purger-consultations '{"dry": true}'
python3 cli.py esbtp-abidjan POST audit/purger-consultations '{"dry": false}'
```

Seules les lignes `retrieved` sont touchées : les créations, modifications,
suppressions et restaurations restent intactes.

## Historique

- Septembre 2026 — création des deux points d'accès.
