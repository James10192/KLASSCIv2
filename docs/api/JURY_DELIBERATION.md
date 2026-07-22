# API Jury de délibération LMD

Workflow officiel: composition, décisions, signatures, émission du PV, publication et archivage.

## Sécurité et permissions

Toutes les routes `/esbtp/lmd/jurys/*` utilisent `auth`, `admin.access`, `module.lmd.access` et `paywall`.

| Permission | Usage |
|---|---|
| `lmd.jury.view` | Consulter le jury et télécharger un PV accessible |
| `lmd.jury.preside` | Créer le jury et gérer sa composition |
| `lmd.jury.deliberate` | Calculer, modifier et signer les décisions |
| `lmd.jury.publish` | Émettre le PV et publier les décisions |
| `lmd.jury.documents.reconcile` | Réconcilier un PV historique existant |

## Conditions d émission du PV

L émission verrouille transactionnellement le jury, les membres, les décisions, la cohorte, les identités et les feuilles de notes. Elle est refusée lorsque:

- le quorum ou le nombre minimal d assesseurs n est pas atteint;
- un membre présent n a pas signé lorsque la signature est obligatoire;
- une signature existante serait remplacée;
- un étudiant de la cohorte ne possède pas exactement une décision complète et un bulletin cohérent;
- aucune feuille de notes LMD ne couvre le périmètre;
- une feuille du périmètre n est pas au statut `validated`.

Le PDF est rendu depuis le snapshot canonique construit sous verrou. Les collections sont triées. Le snapshot conserve le périmètre, les libellés, les membres, les signatures, les étudiants, les matricules, les décisions, les votes, les observations, les statistiques, les feuilles et le profil de règles LMD.

## Registre documentaire

Chaque document possède une série logique, une version positive, une référence unique, un SHA-256, les versions des règles, du template et du renderer, ainsi qu un journal append-only.

États:

- `valid`: document officiel accessible et vérifiable;
- `revoked`: document révoqué, conservé mais inaccessible;
- `superseded`: document remplacé par une nouvelle version, conservé mais inaccessible;
- `legacy`: PV historique réconcilié, accessible en authentifié, sans snapshot historique inventé et sans vérification publique.

Une seule version `valid` est autorisée par série. Une nouvelle émission après révocation prend la version suivante et référence explicitement le document précédent avec `supersedes_document_id`.

## Endpoints jury

### POST `esbtp.lmd.jurys.membres.signer`

Body: `{ "signature_data": "data:image/png;base64,..." }`.

Le serveur capture dans cet ordre l utilisateur authentifié, l IP et le User-Agent. La ligne membre est verrouillée. Une signature existante ne peut jamais être remplacée.

### POST `esbtp.lmd.jurys.pv.generer`

Permission: `lmd.jury.publish`. Limite: 10 requêtes par minute.

Réponse:

```json
{
  "success": true,
  "pv": {
    "numero": "PV-20252026-PRESENTATION-0001",
    "reference": "DOC-PV-...",
    "version": 1,
    "status": "valid",
    "genere_at": "2026-07-22T10:00:00+00:00",
    "download_url": "/esbtp/lmd/jurys/1/pv/download"
  }
}
```

Le chemin de stockage privé n est jamais exposé.

### GET `esbtp.lmd.jurys.pv-preview`

### GET `esbtp.lmd.jurys.pv-download`

Permission: `lmd.jury.view`. Ces endpoints redirigent vers une URL signée et liée par HMAC, valide cinq minutes. L intégrité et le statut sont contrôlés avant la création de l URL puis avant le stream. Une ancienne URL ne fonctionne plus après révocation ou remplacement.

### POST `esbtp.lmd.jurys.pv-reconcile`

Permission: `lmd.jury.documents.reconcile`. Limite: 5 requêtes par minute.

Cette action valide le fichier PDF historique existant, calcule son SHA-256 et l inscrit avec le statut `legacy`. Elle ne régénère pas le fichier et ne fabrique ni règles ni snapshot historiques.

### POST `esbtp.lmd.jurys.publier`

Permission: `lmd.jury.publish`.

Le document archivé est contrôlé par `assertValidAndIntact`. Les décisions sont ensuite projetées vers les bulletins avec `LmdDecisionProjectionService`, puis le jury passe à `publie` dans la même transaction.

## Endpoint public de vérification

### POST `/verifier-document-officiel`

Body:

```json
{
  "reference": "DOC-PV-...",
  "code": "CODE_FORT_IMPRIME_SUR_LE_PV"
}
```

Deux limites indépendantes sont appliquées: une par IP et une par référence, toutes IP confondues. Le code brut n est jamais stocké ni journalisé.

Une référence inconnue, un code incorrect, un document altéré, révoqué, remplacé ou legacy renvoient la même réponse:

```json
{ "valid": false }
```

Une vérification réussie ne renvoie que `valid`, `reference`, `document_type` et `issued_at`. Aucune note, identité étudiante, empreinte, chemin ou snapshot n est exposé.

## Numérotation

Format: `PV-{ANNEE}-{TENANT}-{SEQ4}`.

La table `esbtp_pv_sequences` porte une séquence distincte par tenant et année universitaire. L initialisation tient compte des numéros déjà présents. L incrément utilise un verrou de ligne et trois tentatives transactionnelles.

## Journal append-only

Événements principaux: `issued`, `downloaded`, `verified`, `revoked`, `superseded`, `reconciled`, `integrity_failed`.

## Historique

### 2026-07-22

- Ajout du registre documentaire officiel versionné.
- Ajout du snapshot canonique et du rendu PV depuis ce snapshot.
- Ajout des contrôles transactionnels d émission.
- Ajout des URLs signées avec HMAC et du contrôle centralisé d intégrité.
- Ajout de la vérification publique non énumérante.
- Ajout de la réconciliation explicite des PV historiques.
- Ajout de la publication atomique vers les bulletins LMD.
- Breaking change: un PV révoqué ou remplacé devient inaccessible, y compris avec une ancienne URL signée.
