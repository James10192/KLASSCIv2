# API — Rendez-vous d'inscription (portail public)

Surface signée consommée par klassci.com. Même garde, même HMAC, seaux **séparés** du dépôt en ligne.

## Authentification

HMAC-SHA256 (`X-Klassci-Signature`, `X-Klassci-Timestamp`), comme les candidatures.
Canal : `portail.public:rendezvous`.

Le listing (`/creneaux`) est `catalogue`. Consulter, renvoyer, déplacer, annuler, retrouver portent un **plancher** de temps de réponse.

Aucun champ `matricule` : le garde le lirait et verrouillerait la réinscription. L'identifiant de secours s'appelle `identifiant`.

## Endpoints

Tous en `POST /api/public/rendez-vous/…`, corps JSON + `ip_client` injecté par le relais.

| Chemin | Nature | Corps | Succès |
|---|---|---|---|
| `/creneaux` | catalogue | `{}` | `{ creneaux: [{ id, date, heure_debut, heure_fin, etat }] }` |
| `/reserver` | identité | `reference`, `date_naissance`, `creneau_id` | 201 `{ enregistre, reservation }` |
| `/consulter` | identité + plancher | `reference`, `date_naissance` | `{ trouve, reservation, peut_modifier }` |
| `/renvoyer` | identité + plancher | `reference`, `date_naissance` | 202 `{ enregistre, message, reservation }` |
| `/deplacer` | identité + plancher | + `creneau_id` | `{ enregistre, reservation }` |
| `/annuler` | identité + plancher | `reference`, `date_naissance` | `{ enregistre: true }` |
| `/retrouver` | identité + plancher | `identifiant`, `date_naissance` | `{ trouve, reference? }` |

`etat` vaut `disponible` ou `complet` — jamais un compte exact (le relais met les lectures en cache 60 s).

Un créneau plein rend 409 `{ code: complet, creneaux }` : la liste rafraîchie, pour que l'écran se redessine sur place.

Échec d'identification : `{ trouve: false, code: introuvable }`, seau `rdv-ref` / `rdv-id` (5 essais / 15 min). Réponse uniforme.

### Convocation dans la réponse

Lorsqu'une réservation existe, la représentation publique peut inclure :

```json
{
  "date": "2026-09-29",
  "heure_debut": "10:00",
  "heure_fin": "10:30",
  "statut": "confirmee",
  "convocation_url": "https://instance.../convocation-rdv/...",
  "convocation": {
    "statut": "envoyee",
    "canal": "whatsapp",
    "destination": "+225 07 ** ** ** 54",
    "message_id": "...",
    "tentatives": 1,
    "fallback_utilise": false,
    "envoyee_at": "...",
    "delivree_at": null,
    "erreur": null
  }
}
```

Le contact brut n'est jamais renvoyé. `destination` est déjà masquée. `convocation_url` est une URL signée et temporaire.

`POST /renvoyer` **ne crée aucun rendez-vous** : il retrouve la réservation existante avec le même couple référence + date de naissance et remet uniquement sa convocation dans la file asynchrone.

## Accès vérifié (réglage `inscriptions.portail.verification_contact`)

Quand l'école a activé la vérification du contact, `reference` (ou `identifiant`) + `date_naissance` ne font plus qu'**identifier** le dossier. L'accès se prouve par un code :

- `/consulter` sans jeton valide : `200 { trouve: true, statut: "verification_email_requise" | "verification_telephone_requise", demande_id, email_masque | telephone_masque }` — un code part au contact du dossier (même choix de canal que la vérification d'une demande : e-mail joignable, sinon WhatsApp). Un code par minute et par dossier ; un rechargement redonne le même `demande_id`. Aucune donnée de convocation dans cette réponse.
  - `{ trouve: true, statut: "contact_injoignable" }` : ni e-mail joignable ni mobile au dossier.
  - `{ trouve: true, statut: "verification_indisponible" }` : le code n'a pas pu partir.
  - Avec `sans_code: true` : `{ trouve: true, statut: "verification_requise" }`, **sans envoi** (lecture du créneau juste après un dépôt).
- Le code se contrôle par les routes de vérification habituelles (`api/portail/email/verifier`, `renvoyer`), avec ce `demande_id` : `200 { verifie: true, type: "rendez_vous", reference, jeton_acces }`.
- `jeton_acces` (30 min, signé par la clé de l'application, valable pour CE dossier seulement) se joint ensuite à `/consulter`, `/reserver`, `/deplacer`, `/annuler`, `/renvoyer`. Sans lui : `403 { ok: false, code: "verification_requise" }`.
- La vérification réussie d'une **demande** (candidature ou réinscription) rend aussi `jeton_acces` : la famille choisit son créneau sans second code.

Réglage désactivé : comportement inchangé, `jeton_acces` ignoré.

## Dépôt et vérification

Les dépôts de candidature et de réinscription émettent une `reference_publique`. La vérification du contact constitue ensuite le point de passage avant la suite du parcours. Lorsqu'un rendez-vous doit être attribué après vérification, le flux canonique utilise la réservation existante ou attribue le créneau prévu puis programme la convocation ; le renvoi public ne repasse jamais par cette attribution.

La convocation est multicanale : e-mail lorsque l'adresse est joignable, WhatsApp lorsqu'il s'agit du canal disponible, avec fallback e-mail → WhatsApp dans les cas d'échec destinataire compatibles. Le PDF est accessible par lien signé dans les messages. L'envoi comme document natif MailPulse reste volontairement désactivé tant que le contrat exact des pièces jointes/documents n'est pas confirmé.

Les dossiers historiques déjà en attente peuvent être placés via les outils CLI. Les échecs passagers sont repris par la tâche planifiée `inscriptions:envoyer-convocations-rdv`.

## Historique

- 2026-11-14 — accès vérifié par code quand la vérification du contact est active : `jeton_acces`, `sans_code`, `403 verification_requise` ; `/retrouver` compare le matricule sans espaces ni séparateurs. **Changement de comportement** pour les écoles qui ont activé la vérification : `/consulter` sans jeton ne rend plus la réservation.

- 2026-09-28 — convocation multicanale e-mail/WhatsApp, suivi du canal et du fallback, URL PDF signée exposée à la consultation, ajout de `/renvoyer` sans duplication de rendez-vous.
- 2026-09-22 — la convocation n'est plus perdue en silence : son état est consigné sur la réservation et les échecs passagers sont renvoyés par la tâche planifiée `inscriptions:envoyer-convocations-rdv`. `POST /api/cli/rendez-vous/placer` ne fait plus partir les courriels lui-même — voir [CLI_RENDEZ_VOUS.md](CLI_RENDEZ_VOUS.md).
- 2026-09-09 — **breaking** : `rendez_vous` retiré du 201. Placement post-commit, lecture via `/consulter`. Convocation HTML via MailPulse, plus de PDF joint.
- 2026-09-08 — le 201 attribue un créneau et envoie la convocation.
- 2026-09-08 — `rdv_ouvert` retiré du 201 (le canal se lit ailleurs). Référence émise au dépôt.
- 2026-09-07 — création (PR B, inscriptions physiques).
