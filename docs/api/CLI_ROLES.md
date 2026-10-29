# API CLI Rôles

## POST `/api/cli/roles`

Crée un **rôle custom** (Spatie `is_custom=true`). Les noms canoniques (`config/permissions.php` → `roles`) sont refusés.

Auth : Bearer token, ability `cli:admin`.

```json
{
  "name": "informaticien",
  "label_fr": "Informaticien",
  "description": "Valide inscriptions, notes, maquettes, PV",
  "permissions": ["inscriptions.validate", "notes.create"]
}
```

## POST `/api/cli/user/{id}/role`

Assigne un rôle existant (canonique **ou custom**). Body : `{ "role": "secretaire_inscriptions", "mode": "replace" }`.

## Historique

- 2026-08-31 : `roleStore` + assignation des rôles custom existants (plus seulement `VALID_ROLES`).
