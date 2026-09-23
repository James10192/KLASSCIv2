# API portail : vérification du contact (e-mail ou WhatsApp)

Consommée par klassci.com (klassci-landing). Routes signées comme `api/public/*`
(en-têtes `X-Klassci-Signature` / `X-Klassci-Timestamp`, même secret
`services.reinscription_portal.secret`), seau de débit `verification`, toujours
ouvertes (une saison qui ferme ne bloque pas une vérification en cours).
Le corps de ces routes n'est jamais journalisé (`LogRequests`).

## Dépôt (réponse enrichie)

`POST /api/public/inscription/submit` et `POST /api/public/reinscription/submit`
répondent toujours `201` avec leurs champs habituels. Quand une vérification est
lancée, la réponse contient en plus :

```json
{"statut":"verification_email_requise","demande_id":"<uuid>","email_masque":"k***@gmail.com"}
{"statut":"verification_telephone_requise","demande_id":"<uuid>","telephone_masque":"+22507*****04"}
```

Seule une demande NEUVE (ou déjà masquée) est masquée, et seulement une fois le
premier code parti : `verification_contact` = `email_non_verifie` /
`telephone_non_verifie`, invisible pour l'école. Une demande que l'école voyait
déjà (ancienne, ou vérifiée) n'est JAMAIS masquée : si un redépôt change son
adresse ou son numéro, les dates de vérification tombent, la demande passe en
`contact_a_reconfirmer` (visible, badge « Contact à reconfirmer ») et un code part.
La réponse du dépôt renvoie alors `statut` et `demande_id` pour saisir le code, avec
le message « transmise ».
Un redépôt d'une demande déjà masquée renvoie le code sous le débit du renvoi.

Canal : l'e-mail s'il est joignable (candidature : champ `email` ; réinscription :
adresse du dossier étudiant), sinon WhatsApp sur le mobile. Si le premier code ne
part pas, la demande passe en `verification_impossible`, reste visible, et la
réponse n'a pas de champ `statut`. Un 429 de MailPulse au premier envoi n'est pas une
impossibilité : la demande est masquée et la famille redemande un code plus tard. Une demande masquée non confirmée au bout de
48 h redevient visible en `verification_expiree`, avec le badge « Contact non
confirmé » dans les listes de l'école (`inscriptions:expirer-verifications-contact`,
toutes les heures). Une confirmation tardive reste acceptée.
Les demandes `verification_expiree`, `verification_impossible` et `contact_a_reconfirmer`
ne sont ni placées automatiquement en rendez-vous ni convoquées par courriel (elles vont
dans « Familles à prévenir ») tant qu'un agent n'a pas cliqué « Confirmer le contact »
(permission de traitement des candidatures ou des demandes).

Le courriel contient un code à 6 chiffres (30 min) et le lien
`<URL_PORTAIL_PUBLIC>/verification-email?ecole=<code_ecole>#jeton=<jeton>` (48 h).
Un code WhatsApp vit 10 minutes, comme chez MailPulse.
Code et jeton ne sont stockés qu'en empreinte HMAC. 5 tentatives par code, 15 au
total par demande, renvois compris. Une demande déjà vérifiée ne répond « vérifié »
qu'au code qui l'a vérifiée (ou à son lien).

## `POST /api/portail/email/verifier`

Corps : `{"canal":"email"|"telephone","jeton":"…"}` ou `{"canal","demande_id","code":"123456"}`.
`canal` absent = `email` ; toute autre valeur est refusée.

- `200 {"verifie":true,"type":"candidature"|"reinscription"}`
- `422 {"verifie":false,"motif":"code_invalide"|"expire"|"trop_de_tentatives"}`
  (demande inconnue = `code_invalide`)
- `503 {"verifie":false,"motif":"indisponible"}` : MailPulse n'a pas pu contrôler un code WhatsApp.

## `POST /api/portail/email/renvoyer`

Corps : `{"canal","demande_id"}`. `202 {"envoye":true}` (y compris pour une demande
inconnue), `429 {"envoye":false,"retry_after":<s>}` au-delà d'un renvoi par minute
ou cinq par heure et par demande (ou quand MailPulse limite : son `retry_after` est
rendu tel quel), `422` si `canal` n'est ni `email` ni `telephone`.

## WhatsApp

Via MailPulse `POST /api/v1/verifications` puis `/{id}/check` (clé API de l'école).
Codes gérés : `201`, `409 whatsapp_indisponible`, `429 retry_after`, `502 envoi_echoue`,
`503 verification_indisponible`. L'identifiant MailPulse est conservé dans
`esbtp_verifications_contact.mailpulse_verification_id`.

## Familles déjà en base

`php artisan inscriptions:verifier-familles-sans-email` (simulation par défaut) liste
les demandes en attente sans e-mail joignable qui recevraient un code WhatsApp.
`--execute` envoie, avec l'accord de l'école ; la demande reste visible. `--limite`
(20 par défaut, 20 au plus) borne un passage ; la commande s'arrête au premier refus de
débit (`rate_limited`, `trop_de_demandes`) ou de configuration, et ne garde aucune ligne
de vérification pour un envoi échoué.

## Historique

- 2026-09-23 : création.
- 2026-09-23 : `contact_a_reconfirmer`, « Confirmer le contact », `retry_after` MailPulse, code WhatsApp de 10 min.
- 2026-09-23 : une demande visible n'est jamais masquée ; masquage après envoi seulement ; expiration à 48 h ; plafond cumulé de 15 tentatives.
