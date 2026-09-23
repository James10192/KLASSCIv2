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

Tant que le contact n'est pas vérifié, la demande est invisible pour l'école
(`verification_contact` = `email_non_verifie` / `telephone_non_verifie`).
Canal : l'e-mail s'il est joignable (candidature : champ `email` ; réinscription :
adresse du dossier étudiant), sinon WhatsApp sur le mobile. Si le code ne peut pas
partir pour une raison de configuration (MailPulse désactivé, clé absente, WhatsApp
indisponible), la demande passe en `verification_impossible`, reste visible, et la
réponse n'a pas de champ `statut`.

Le courriel contient un code à 6 chiffres (30 min) et le lien
`<URL_PORTAIL_PUBLIC>/verification-email?ecole=<code_ecole>#jeton=<jeton>` (48 h).
Code et jeton ne sont stockés qu'en empreinte HMAC. 5 tentatives au plus.

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
ou cinq par heure et par demande, `422` si `canal` n'est ni `email` ni `telephone`.

## WhatsApp

Via MailPulse `POST /api/v1/verifications` puis `/{id}/check` (clé API de l'école).
Codes gérés : `201`, `409 whatsapp_indisponible`, `429 retry_after`, `502 envoi_echoue`,
`503 verification_indisponible`. L'identifiant MailPulse est conservé dans
`esbtp_verifications_contact.mailpulse_verification_id`.

## Familles déjà en base

`php artisan inscriptions:verifier-familles-sans-email` (simulation par défaut) liste
les demandes en attente sans e-mail joignable qui recevraient un code WhatsApp.
`--execute` envoie, avec l'accord de l'école ; la demande reste visible.

## Historique

- 2026-09-23 : création.
