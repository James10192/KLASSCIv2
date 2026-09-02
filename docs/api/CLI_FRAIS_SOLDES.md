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

## Historique

- 2026-09-02 : endpoint de diagnostic pour le reste à payer par souscription.
