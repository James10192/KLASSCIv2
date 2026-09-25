# Convocation de rendez-vous par WhatsApp, après accord (KLASSCI ↔ MailPulse)

KLASSCI remet la convocation **et** le texte de la demande d'accord à MailPulse.
MailPulse tient le consentement (OUI / NON / STOP, échéance, plafond de premières
demandes par jour, heures d'envoi) et rend compte par le callback signé. KLASSCI
ne décide pas si un message part : il dit à l'école où en est chaque famille.

## Quand c'est proposé

| Condition | Source |
|---|---|
| Réglage d'instance `inscriptions.rdv.whatsapp_relais` allumé (éteint par défaut) | `/esbtp/inscriptions/rendez-vous`, réglages |
| Permission `inscriptions.rdv.whatsapp` (aucun rôle par défaut, `superAdmin` couvert) | rôles personnalisés |
| Réservation confirmée, créneau pas encore commencé, dossier ouvert | `RelaisWhatsappConvocationRdv::eligible()` |
| Courriel de convocation `sans_email` ou `echec` | `esbtp_rdv_reservations.convocation_statut` |
| Mobile lisible par `PhoneNormalizer::toE164()` | réglages téléphone de l'instance |
| Aucun relais en cours, ou un échec technique à rejouer | `whatsapp_statut` null ou `echec` |

Une famille qui a refusé (NON, STOP) ou n'a pas répondu en 48 h n'est **pas**
reproposée depuis l'écran : elle est à appeler. Si elle répond OUI plus tard,
MailPulse envoie `consent.granted` ; la réservation redevient proposable.

## La commande

`POST {MAILPULSE_BASE_URL}/api/v1/external-applications/{applicationKey}/commands`,
signée comme les commandes du chatbot parent (`x-external-organization-id`,
`x-external-timestamp`, `x-external-signature: v1:<keyId>=<hmac sha256 de "timestamp.body">`).

```json
{
  "operation_key": "rdv.convocation",
  "channel": "whatsapp",
  "recipient": { "type": "phone", "value": "+2250700000001" },
  "content": {
    "type": "document",
    "url": "https://<tenant>.klassci.com/convocation-rdv/<id>.<hmac>",
    "filename": "convocation-CAND-2026-00042.pdf",
    "mimeType": "application/pdf",
    "caption": "Convocation — <nom de l'école>\nmardi 6 octobre 2026 10:00 – 10:30\nRéférence : CAND-2026-00042"
  },
  "consent": {
    "request": { "text": "Bonjour, ici le service des inscriptions de <école>, via KLASSCI. Nous avons un rendez-vous d'inscription pour <candidat> le <date> à <heure>. Acceptez-vous de recevoir la convocation sur WhatsApp ? Répondez OUI ou NON." },
    "expiresInSeconds": 172800
  },
  "metadata": { "idempotency_key": "klassci-<tenant>-rdv-convocation-<reservationId>-<tentative>" }
}
```

- L'URL du document est le lien PDF signé existant (`ConvocationRdvPdf::url()`).
  Elle doit être en HTTPS : sinon le lot s'arrête avec un motif, rien ne part.
- Le texte d'accord est le réglage `inscriptions.rdv.whatsapp_texte_accord`
  (vide = texte par défaut). Repères : `{ecole}`, `{candidat}`, `{date}`,
  `{heure}`, `{reference}`. Le nom de l'école vient des réglages, jamais du code.
- La clé d'idempotence change seulement à une nouvelle tentative (après un
  échec). Une panne de transport rejoue la **même** clé.

## Les réponses

| Réponse MailPulse | `whatsapp_statut` | Effet sur le lot |
|---|---|---|
| `202 { "status": "consent_pending", "operationId" }` | `demandee` | compté « demande en file » |
| `202 { "dispatch_state": "accepted" }` (accord déjà donné) | `accordee` | compté « envoyée » |
| `409 { "code": "consent_refused" }` | `refusee` | compté « refus » |
| 401/403/404/400, MailPulse désactivé ou non configuré | inchangé (`null`) | **lot arrêté**, motif affiché |
| connexion, 429, 5xx, 409 sans code | inchangé (`null`), clé gardée | lot arrêté, rejouable |
| `422` rejet durable | `echec` | compté « échec » |

## Les comptes rendus

`POST /api/v1/integrations/mailpulse/events` (throttle 120/min). Même vérification
de signature que `/api/v1/integrations/mailpulse/parent-chatbot/inbound`
(`MAILPULSE_EXTERNAL_CALLBACK_KEY_ID`, `MAILPULSE_EXTERNAL_CALLBACK_SECRET`).
Si MailPulse n'a qu'un point d'arrivée configuré, les mêmes événements sont
acceptés sur l'URL du chatbot : elle les reconnaît au champ `event` et les
délègue.

```json
{ "event": "message.delivered", "operationKey": "rdv.convocation",
  "idempotencyKey": "klassci-<tenant>-rdv-convocation-42-1", "operationId": "op_…",
  "recipient": "+2250700000001", "occurredAt": "2026-09-25T16:00:00Z",
  "messageId": "…", "failureCode": "…" }
```

| Événement | `whatsapp_statut` | Libellé à l'écran | Horodatage |
|---|---|---|---|
| `consent.requested` | `accord_demande` | WhatsApp : accord demandé | `whatsapp_accord_demande_at` |
| `consent.granted` | `accordee` | WhatsApp : accord donné | `whatsapp_accord_at` |
| `consent.refused` | `refusee` | WhatsApp : refusée | — |
| `consent.expired` | `sans_reponse` | WhatsApp : sans réponse (48 h) | — |
| `message.sent` | `envoyee` | WhatsApp : envoyée | `whatsapp_envoyee_at` |
| `message.delivered` | `remise` | WhatsApp : remise | `whatsapp_remise_at` |
| `message.read` | `lue` | WhatsApp : lue | `whatsapp_remise_at` |
| `message.failed` | `echec` | WhatsApp : échec (+ `failureCode`) | — |

Réponse toujours `202 { "accepted": true, "applied": bool, "duplicate": bool }`
une fois la signature valide ; `401` sinon ; `422` sur un corps invalide.

**Idempotence.** La réservation se retrouve par `idempotencyKey` de sa tentative
en cours : un événement d'une tentative antérieure ne touche rien. Un événement
n'est appliqué que s'il fait avancer la ligne (rang : demandée < accord demandé <
accord donné < envoyée < remise < lue) ; un doublon ou un événement en retard est
accusé sans effet. Un refus, un silence ou un échec n'efface jamais une remise.

## La liste d'appel

Une famille sort de la liste des familles à prévenir (écran, PDF, Excel) **dès que
la convocation est remise ou lue**. Tant que WhatsApp est en attente, ou après un
refus, un silence ou un échec, elle y reste ; le motif dit l'état WhatsApp et sa
raison. La feuille de suivi imprimée et son Excel indiquent « WhatsApp » comme
canal de convocation quand elle a été remise.

## Historique

- **Septembre 2026** — création. Nouveau type de contenu `document`, champ
  `consent`, route `POST /api/v1/integrations/mailpulse/events`. Additif, aucun
  changement cassant : `MailPulseClient` reconnaît en plus `consent_pending`
  (202) et `consent_refused` (409), qui étaient auparavant lus comme une erreur
  générique.
