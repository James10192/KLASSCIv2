# Avoirs de paiement

## Endpoints web

| Méthode | Route | Nom | Permission |
| --- | --- | --- | --- |
| POST | `/esbtp/paiements/{paiement}/avoir` | `esbtp.paiements.avoir.store` | `paiements.avoir` |
| GET | `/esbtp/paiements/{paiement}/avoir-pdf` | `esbtp.paiements.avoir.pdf` | `paiements.view` / `view_own` / `avoir` |

`?inline=1` sur le PDF pour l’aperçu.

## Métier

Un avoir est **rattaché** à un paiement **validé** (pas à un autre avoir). Montant ≤ reliquat encore non avoiré.

| `avoir_kind` | Compte étudiant | Caisse (journal) |
| --- | --- | --- |
| `credit` | dû augmente (encaissement compensé) | inchangée |
| `refund` | idem | sortie du jour |

Le reçu d’origine n’est pas modifié. Pièce `AV{aa}-#####`.

## Historique

- 2026-08-30 : création (Plan B barre caisse + avoir crédit/remboursement).
