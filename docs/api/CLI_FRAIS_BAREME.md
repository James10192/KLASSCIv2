# API CLI — Barème des frais

## GET `/api/cli/frais/bareme`

Lit le barème en place : catégories, configurations actives, et montants réellement souscrits.

Auth : Bearer token, ability `cli:read`.

Chaque catégorie porte `audience` (`tous` | `nouveaux_etablissement` | `anciens_etablissement`) — qui paie — et `sort_order` — l'ordre dans lequel un versement solde les frais.

Chaque configuration porte sa portée complète (`systeme`, `parcours_id` en LMD, `filiere_id` en BTS, `niveau_id`, `annee_universitaire_id`) et ses montants par statut d'affectation (`amount`, `amount_affecte`, `amount_reaffecte`, `amount_non_affecte`).

## POST `/api/cli/frais/poser-bareme`

Crée ou met à jour des catégories de frais et leurs configurations (LMD = parcours + niveau, BTS = filière + niveau).

Auth : Bearer token, ability `cli:admin`.

Sans `apply=true` : dry-run, rien n'est écrit.

`confirmer_statut=true` active le réglage qui demande à l'agent, à l'inscription, si l'étudiant est nouveau ou déjà passé par l'établissement — indispensable dès qu'une catégorie a une `audience` autre que `tous`.

Une catégorie peut être envoyée sans aucune configuration : elle vaut alors son `default_amount` partout (cas d'un frais optionnel à prix unique).

## Historique

- 2026-09-02 : création.
- 2026-09-02 : `GET /bareme` expose `audience`, `sort_order`, la portée LMD (`systeme`, `parcours_id`, `parcours`) et les montants par statut d'affectation. Un barème LMD était jusque-là illisible à distance : toutes les lignes affichaient une filière nulle.
