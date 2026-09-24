# API CLI — Annuler ou restaurer un versement

Deux routes, jeton `cli:admin`. Aucune n'écrit sans `"apply": true` : sans lui,
la réponse décrit ce qui serait fait.

## Annuler n'est pas supprimer

| geste | le versement | trace |
|---|---|---|
| **Annuler** (`/annuler`) | reste dans la liste, compensé par un **avoir** validé et numéroté | l'avoir (motif, auteur, date) + journal d'audit |
| **Supprimer** (écran, `paiements.delete`) | disparaît des listes, reste en corbeille | motif, auteur et date sur la ligne + journal d'audit |

Annuler est la contre-écriture comptable : c'est le même geste que le bouton
« Avoir » de la fiche du paiement, sur la totalité restante du versement.

## `POST /api/cli/paiements/{id}/annuler`

| champ | requis | valeurs |
|---|---|---|
| `avoir_kind` | oui | `credit` : l'argent reste à l'école, en crédit réutilisable, et le frais redevient dû · `refund` : l'argent est rendu, la sortie apparaît au journal de caisse du jour |
| `motif` | oui | 10 à 500 caractères |
| `apply` | non | `true` pour écrire |

**`refund` par le CLI** : la sortie est inscrite au journal de caisse du jour,
au nom de l'utilisateur du jeton, alors qu'aucune caisse n'a physiquement bougé.
Pour une saisie faite par erreur (argent jamais reçu), c'est bien la
contre-écriture attendue : l'entrée et la sortie s'annulent. Si l'argent reste
réellement à l'école, choisissez `credit`.

Refus (422) : versement non validé, déjà entièrement compensé, avoir sur un avoir,
période verrouillée, versement rapproché. 404 si le versement est supprimé :
restaurez-le d'abord.

```bash
curl -X POST "$URL/api/cli/paiements/807/annuler" -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"avoir_kind":"refund","motif":"Versement annulé à la demande de la direction.","apply":true}'
```

Réponse : `data.paiement`, `data.montant_a_annuler`, `data.applique`, et
`data.avoir` (`id`, `numero`, `montant`) quand l'avoir est émis.

## `POST /api/cli/paiements/{id}/restaurer`

Remet un versement supprimé, avec son inscription et son étudiant s'ils avaient
été supprimés en cascade (même action que la corbeille à l'écran). La réponse
montre, avant application, la date et le motif de la suppression.

Refus (422) : versement d'une période comptable verrouillée
(`comptabilite.period_locked_until`) ou rapproché par une réconciliation close,
sauf droit de contournement explicite. La corbeille à l'écran applique le même
refus.

## Historique

- **Septembre 2026** — création des deux routes.
