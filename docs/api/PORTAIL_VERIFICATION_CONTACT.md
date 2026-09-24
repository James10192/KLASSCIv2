# API portail : vérification du contact (e-mail ou WhatsApp)

Consommée par klassci.com (klassci-landing). Routes signées comme `api/public/*`
(en-têtes `X-Klassci-Signature` / `X-Klassci-Timestamp`, même secret
`services.reinscription_portal.secret`), seau de débit `verification`, toujours
ouvertes (une saison qui ferme ne bloque pas une vérification en cours).
Le corps de ces routes n'est jamais journalisé (`LogRequests`).

## Réglage d'instance

`inscriptions.portail.verification_contact` (constante
`TenantScolariteSettings::VERIFICATION_CONTACT`), **désactivé par défaut**, semé par
migration. Écran : Réglages → section Inscriptions, carte « Vérifier le contact des
demandes en ligne ».

- **Désactivé** : le portail se comporte comme avant. Aucun code n'est envoyé, la
  réponse du dépôt n'a pas de champ `statut`, rien n'est marqué ni retenu.
  `inscriptions:verifier-familles-sans-email` ne fait rien.
- **Activé** : un code part après chaque dépôt, et la demande est **marquée**, jamais
  masquée (voir ci-dessous).

Restent actifs quel que soit le réglage : la règle `EmailJoignable` et ses suggestions,
la synchronisation des statuts MailPulse, le diagnostic CLI et `emails:nettoyer-factices`.

## Dépôt (réponse enrichie, réglage activé)

`POST /api/public/inscription/submit` et `POST /api/public/reinscription/submit`
répondent toujours `201` avec leurs champs habituels (message « transmise »). Quand un
code est parti, la réponse contient en plus :

```json
{"statut":"verification_email_requise","demande_id":"<uuid>","email_masque":"k***@gmail.com"}
{"statut":"verification_telephone_requise","demande_id":"<uuid>","telephone_masque":"+22507*****04"}
```

Canal : l'e-mail s'il est joignable (candidature : champ `email` ; réinscription :
adresse personnelle puis adresse du dossier étudiant), sinon WhatsApp sur le mobile.

La demande est **toujours visible** par l'école. Tant que son contact n'est pas prouvé,
`verification_contact` vaut :

- `email_non_verifie` / `telephone_non_verifie` : code parti, badge « Contact non vérifié » ;
- `verification_impossible` : le code n'a pas pu partir (MailPulse désactivé, clé absente,
  WhatsApp indisponible, aucun contact joignable), badge « Contact non vérifiable » ;
- `contact_a_reconfirmer` : un redépôt a changé l'adresse ou le numéro d'une demande
  déjà traitée, un nouveau code est parti, badge « Contact à reconfirmer ».

Les corbeilles candidatures et réinscriptions ont un filtre « Contact non vérifié »
(`?contact=non_verifie`). Ces demandes ne sont ni placées automatiquement en
rendez-vous ni convoquées par courriel (elles vont dans « Familles à prévenir »)
jusqu'à la saisie du code, ou jusqu'à ce qu'un agent clique « Confirmer le contact »
(permission de traitement des candidatures ou des demandes). Réglage coupé, rien
n'est retenu, même une demande marquée auparavant.

« Confirmer le contact » est refusé si le dossier a changé depuis l'affichage
(empreinte postée par le formulaire), il est audité (`contact_confirme_par`,
`contact_confirme_at`), et les convocations de rendez-vous déjà pris restées « sans
e-mail » ou en échec repartent avec le prochain envoi ; l'adresse du dossier ne
remplace celle de la réservation que si elle reçoit du courrier.

Un redépôt d'une demande marquée relance un code (sous le débit du renvoi). Un 429 ou
`503 whatsapp_sature` de MailPulse se traite comme une limite de débit (`429` avec
`retry_after` au site), jamais comme une impossibilité. Les écritures d'un envoi
(secrets, identifiant MailPulse) n'ont lieu que si la ligne a toujours le même canal et
le même destinataire.

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
`--execute` envoie, avec l'accord de l'école (réglage activé) ; la demande n'est pas marquée, la vérification ne fait que dater le contact. `--limite`
(20 par défaut, 20 au plus) borne un passage ; la commande s'arrête au premier refus de
débit (`rate_limited`, `trop_de_demandes`) ou de configuration, et ne garde aucune ligne
de vérification pour un envoi échoué.

## Historique

- 2026-09-23 : création.
- 2026-09-23 : `contact_a_reconfirmer`, « Confirmer le contact », `retry_after` MailPulse, code WhatsApp de 10 min.
- 2026-09-23 : une demande visible n'est jamais masquée ; masquage après envoi seulement ; expiration à 48 h ; plafond cumulé de 15 tentatives.
- 2026-09-24 : **changement de comportement** : vérification derrière le réglage `inscriptions.portail.verification_contact` (désactivé par défaut) ; plus aucun masquage (ni portée globale, ni expiration à 48 h, ni `verification_expiree`) : la demande reste visible, marquée, filtrable.
