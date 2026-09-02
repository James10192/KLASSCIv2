# API CLI — Poser un barème

## POST `/api/cli/frais/poser-bareme`

Crée ou met à jour des catégories de frais et leurs configurations (LMD = parcours + niveau, BTS = filière + niveau).

Auth : Bearer token, ability `cli:admin`.

Sans `apply=true` : dry-run, rien n'est écrit.

## Historique

- 2026-09-02 : création.
