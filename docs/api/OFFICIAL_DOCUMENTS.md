# API Documents officiels KLASSCI

## Périmètre

Cette API couvre la vérification publique CEV des documents officiels émis par KLASSCI, notamment les procès-verbaux LMD archivés via le domaine `OfficialDocuments`.

Le système ne stocke pas le code brut. Seul le digest SHA-256 du code est conservé.

## GET `/verifier-document-officiel`

Affiche la page publique CEV.

Authentification: aucune.

Limite: `throttle:30,1`.

Paramètres optionnels:

| Nom | Type | Description |
| --- | --- | --- |
| `reference` | string | Référence officielle à préremplir |
| `code` | string | Code à préremplir, usage réservé aux liens contrôlés |

La page ne révèle aucune donnée académique détaillée.

## POST `/verifier-document-officiel`

Vérifie une référence et un code.

Authentification: aucune.

CSRF: désactivé volontairement pour permettre une vérification publique depuis un document imprimé.

Limites:

| Portée | Limite |
| --- | --- |
| IP | 12 tentatives par minute |
| Référence | 6 tentatives par minute, toutes IP confondues |

Body JSON:

```json
{
  "reference": "DOC-PV-2025-2026-PRESENTATION-0001-V1-ABCDE12345",
  "code": "CODE_FORT_IMPRIME_SUR_LE_DOCUMENT"
}
```

Réponse invalide, volontairement non énumérante:

```json
{
  "valid": false
}
```

Réponse valide:

```json
{
  "valid": true,
  "reference": "DOC-PV-2025-2026-PRESENTATION-0001-V1-ABCDE12345",
  "document_type": "lmd_jury_pv",
  "issued_at": "2026-07-26T00:00:00+00:00"
}
```

Champs jamais exposés:

| Champ | Motif |
| --- | --- |
| `path` | Chemin interne privé |
| `checksum_sha256` | Empreinte d’intégrité interne |
| `snapshot` | Données académiques et personnelles |
| `verification_code_digest` | Secret de vérification |
| identités étudiantes | Données personnelles non nécessaires à la validation publique |

## Mode HTML

Si la requête ne demande pas JSON, le même contrat est rendu dans la page publique CEV.

Le code saisi n’est jamais réaffiché après soumission.

## Historique

- 2026-07-26: ajout de la page CEV publique `GET /verifier-document-officiel` et du rendu HTML sécurisé du `POST /verifier-document-officiel`.
