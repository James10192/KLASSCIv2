# API des retours d'annonces MailPulse (KLASSCI)

## Endpoint

`POST /api/v1/mailpulse/announcement-events` — réception d'événements uniquement. **Cet endpoint ne renvoie jamais de courriel.** Ce n'est pas le webhook Resend directement : MailPulse doit valider Resend et signer un événement de corrélation avant toute activation. L'implémentation du relais est suivie dans `mailpulse#62`.

## Authentification

Headers : `X-MailPulse-Timestamp` (secondes Unix), `X-MailPulse-Signature` (hex HMAC-SHA256 du texte brut `timestamp + "." + corps JSON` avec `MAILPULSE_ANNOUNCEMENT_WEBHOOK_SECRET`). Fenêtre de cinq minutes ; secret distinct par instance, hors de Git, signature comparée en temps constant. Sans secret, le serveur répond 401.

## Corps JSON

```json
{
  "external_reference": "klassci-annonce-12-courriel-581",
  "message_id": "mailpulse-message-id",
  "state": "delivered",
  "occurred_at": "2026-10-29T12:30:00+00:00"
}
```

- `external_reference`, `message_id` et `state` sont obligatoires ; `message_id` est l'ID interne MailPulse, pas Resend (191 caractères au maximum).
- `state` : `delivered`, `delivery_delayed`, `bounced`, `failed`, `complained`, `suppressed`.
- `occurred_at` est facultatif ; s'il est absent, l'heure de réception est utilisée et ne doit pas être présentée comme l'heure de Resend.
- Le corps signé doit correspondre aux deux identifiants de la ligne existante de **l'instance** en cours. Une différence retourne HTTP 202 sans modifier quoi que ce soit et sans confirmer l'existence d'un autre message.
- Les callbacks dupliqués, retardés et concurrents n'altèrent pas une ligne déjà `delivered` ou `failed`. Les délais restent transitoires et ne déclenchent pas de réémission.
- Validation invalide : HTTP 422, signature invalide : HTTP 401, résultat conforme : HTTP 200.

## Historique

- 2026-10-29 — **Breaking change par rapport à l'ébauche non activée** : `message_id` devient obligatoire et doit correspondre à la ligne `mailpulse_message_id`. Mise à jour SQL conditionnelle pour éviter le déclassement sur replay/race ; horodatage fournisseur facultatif.
- Initial — endpoint HMAC interne KLASSCI, réservé à une future passerelle MailPulse.

## Déploiement sécurisé

Ne pas publier de secrets ni relancer l'annonce historique ; garder le relais désactivé tant que l'adaptateur fournisseur, l'isolation tenant/organisation, les tests de replay et les vérifications cron/queue ne sont pas approuvés. Les campagnes utilisent aussi un polling de secours sur les identifiants MailPulse connus.
