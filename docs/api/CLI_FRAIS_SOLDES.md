# API CLI — Soldes inscription

## GET `/api/cli/frais/soldes-inscription`

Lit le reste à payer **à partir des souscriptions** de l'étudiant (montant chargé, dépôt en nature, allocations), pas du tarif catalogue.

Auth : Bearer token, ability `cli:read`.

Query :

| Paramètre | Type | Défaut |
|-----------|------|--------|
| `inscription_id` | int | dernière inscription qui a une souscription active |

Réponse utile :

- `souscriptions[].charged` — ce que l'étudiant doit vraiment
- `souscriptions[].catalogue` — `default_amount` de la catégorie (souvent faux)
- `souscriptions[].ecart_catalogue` — écart ; non nul = l'ancien écran se trompait
- `soldes.total_remaining` — reste affiché sur `paiements.create`

## POST `/api/cli/frais/souscriptions/ajuster`

Change ce qu'**une** inscription doit sur **un** frais : exonération, remise, ou dette reprise d'un autre outil qui ne correspond pas à l'état de compte de l'école. La correction de masse (`/frais/corriger-souscriptions`) vise un montant, pas un élève.

Auth : Bearer token, ability `cli:admin`. Aperçu par défaut, n'écrit que sur `apply`.

| Champ | Type | |
|---|---|---|
| `inscription_id` | int | requis |
| `categorie_id` | int | requis si l'inscription a plusieurs frais |
| `montant` | number ≥ 0 | nouveau montant dû |
| `motif` | string ≥ 10 | requis avec `apply` ; écrit dans `notes` de la souscription |
| `apply` | bool | défaut `false` |

Refus (422 avec `apply`, listés dans `refus` en aperçu) : montant sous ce qui est déjà payé, frais réglé en nature, montant identique, motif trop court, frais modifié entre l'examen et l'écriture.

Effets : modèle audité (la régénération des frais traite le montant comme « retouché à la main » et ne l'écrase pas), échéancier recalculé. Un montant à 0 s'affiche « Montant non défini » sur l'état financier.

Même règle que l'action de Nanan `proposer_ajustement_souscription` (`App\Domain\Comptabilite\Souscriptions\AjustementMontantSouscription`).

## Historique

- 2026-09-02 : endpoint de diagnostic pour le reste à payer par souscription.
- 2026-10-01 : ajout de `POST /frais/souscriptions/ajuster` (non cassant).
