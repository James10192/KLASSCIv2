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

## Format de webhook MailPulse v1

L'endpoint accepte désormais l'enveloppe standard des webhooks de l'organisation MailPulse : `event_id`, `type`, `tenant_id` (organisation), `created_at` et `data.message` avec `id`, `channel=email`, `status`, `external_tenant_id`, `recipient.type=email`, `recipient.value` et `metadata` (`source=klassci`, `workflow_event=announcement_email`, `external_reference`). L'horodatage fournisseur `data.occurred_at` est facultatif. Types pris en charge : `message.delivered`, `message.delivery_delayed`, `message.failed`, `message.bounced`, `message.complained`, `message.suppressed`.

La signature standard est `mailpulse-signature: v1=<HMAC_SHA256>` (plusieurs signatures séparées par virgules possibles durant la rotation). Le contrat historique en hex brut reste compatible. Le secret du webhook MailPulse d'une instance doit correspondre à `MAILPULSE_ANNOUNCEMENT_WEBHOOK_SECRET` de cette instance. Code école `app.tenant_code` réel et distinct de la valeur par défaut `default` obligatoire pour le format standard ; si `MAILPULSE_EXTERNAL_ORGANIZATION_ID` est configuré, l'organisation doit également correspondre. La ligne en base doit correspondre à la référence, au message **et à l'adresse e-mail exacte** ; aucune réponse ne révèle l'existence d'un destinataire d'une autre instance. Les événements ne peuvent pas provoquer de nouvelle soumission.

## Rapprochement sans renvoi par clé historique

Lorsque la requête d'envoi a pu être acceptée mais que KLASSCI a perdu l'ID MailPulse, le synchroniseur exécute uniquement `GET /api/v1/messages/by-idempotency-key?key=<reference>&tenant=<code_etablissement>` avec la clé API de l'envoi initial. Le résultat doit correspondre à l'instance, la référence, l'adresse du destinataire et au message exact avant toute mise à jour SQL conditionnelle. Une réponse `404`, un problème réseau, une rotation de clé ou un autre destinataire laissent l'e-mail **en quarantaine**, sans nouvelle soumission. Le code `default` ne peut pas effectuer cette recherche. L'API est introduite par MailPulse PR #64 ; ne pas activer ce chemin avant déploiement de cette API.

## Historique

- 2026-10-30 — Lecture seule d'une soumission inconnue par clé d'idempotence et contrôle de l'adresse (MailPulse #64), sans aucune relance.
- 2026-10-30 — Réception des webhooks MailPulse standard v1 en plus du legacy, avec corrélation multi-tenant et adresse.
- 2026-10-29 — **Breaking change par rapport à l'ébauche non activée** : `message_id` devient obligatoire et doit correspondre à la ligne `mailpulse_message_id`. Mise à jour SQL conditionnelle pour éviter le déclassement sur replay/race ; horodatage fournisseur facultatif.
- Initial — endpoint HMAC interne KLASSCI, réservé à une future passerelle MailPulse.

## Déploiement sécurisé

Ne pas publier de secrets ni relancer l'annonce historique ; garder le relais désactivé tant que l'adaptateur fournisseur, l'isolation tenant/organisation, les tests de replay et les vérifications cron/queue ne sont pas approuvés. Les campagnes utilisent aussi un polling de secours sur les identifiants MailPulse connus.

## Références tenant-qualifiées (v2, 30/10/2026)

Les envois **jamais tentés** utilisent `klassci-v2-<TENANT_CODE>-annonce-<annonce_id>-courriel-<delivery_id>` pour éviter la collision d'idempotence entre établissements (même si leurs identifiants SQL locaux sont identiques et qu'une organisation MailPulse est mutualisée). Le code tenant doit être réel et conforme au format accepté. La clé est **figée avant le pacing et avant la requête HTTP** dans `esbtp_annonce_email_deliveries.mailpulse_request_id`, puis conservée à l'identique lors des retours fournisseur, des interruptions et du rapprochement en lecture seule.

Les envois historiques déjà tentés et sans repère v2 gardent `klassci-annonce-<annonce_id>-courriel-<delivery_id>`. Aucun renumérotage des idempotency keys, aucun `POST` de récupération. Un repère v2 ne correspondant pas à `TENANT_CODE` produit une quarantaine sans envoi ni consultation sous l'autre identité.

La réception HMAC de l'événement accepte les deux formats. Sur un événement v2 elle exige le code tenant de l'instance **et** que la ligne possède exactement `mailpulse_request_id = external_reference`. Un callback legacy ne peut pas modifier une livraison déjà estampillée v2. Ce contrat suppose l'API MailPulse compatible ([PR #65](https://github.com/James10192/mailpulse/pull/65)) déployée avant l'activation. Ce changement n'active toujours aucune relance effective ; le journal immuable d'essais reste à créer avec Artisan.
