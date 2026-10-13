# KLASSCI — fiabilité des communications multicanal (13 octobre 2026)

## Périmètre et constat sur `presentation`

Cette livraison est une **première barrière de sécurité**, pas une campagne activée.
L'application dispose déjà de `MailPulseWorkflowIntent`, `DispatchMailPulseParentNotificationJob`,
`MailPulseParentNotificationLog` (outbox chiffrée, idempotence, lease et retry),
`MailPulseWorkflowPolicy` (préférences / STOP / publication),
`mailpulse:reconcile-parent-notifications`, du mailer Laravel et de notifications in-app.

**Lacunes constatées :**

1. L'e-mail SMTP/Laravel et l'e-mail MailPulse peuvent partir tous deux pour
   un événement transactionnel ; il faut désigner un canal autoritaire par
   type de message et par destinataire, sans supprimer le récapitulatif étudiant.
2. L'outbox ne couvre pas tous les `Mail::to()->send()` hors MailPulse ;
   sa présence ne garantit donc pas la reprise de tous les e-mails.
3. L'emploi du temps n'a pas de `MailPulseWorkflowIntent` de publication / modification /
   annulation. **Ne pas déclarer cette notification opérationnelle.**
4. Aucun contrôle de débit KLASSCI n'entourait tous les appels `sendWhatsAppMessage`
   via MailPulse. Le présent lot ajoute un garde désactivé par défaut,
   configuré à partir de `services.mailpulse.*`.
5. Baileys/Evolution se trouve derrière MailPulse : ce dépôt ne permet pas de
   garantir le débit global d'un **numéro WhatsApp physique** employé par
   plusieurs écoles. Le débit définitif et les pauses doivent aussi vivre
   dans MailPulse/Evolution, au niveau de la **session/numéro émetteur**.

## Architecture cible

```text
Événement métier validé / transaction commit
  -> identité stable : tenant + workflow + entité + version/occurrence
  -> destinataires autorisés, consentement, STOP, secret interdit
  -> canal in-app (toujours disponible selon permission)
  -> outbox par (occurrence, destinataire, canal) avec déduplication
  -> worker selon priorité et délai admissible
     -> E-mail : transport actif, quotas, retries, webhook de délivrabilité
     -> WhatsApp : limiter KLASSCI -> limiter MailPulse/Evolution -> Baileys
     -> SMS : seulement sur motif de fallback confirmé et consentement
  -> statuts distincts : demandé / en attente / accepté / livré / lu / échoué
  -> réconciliation / incidents / métriques / gestion des préférences
```

Le statut HTTP 202 de MailPulse signifie au mieux *accepté/en cours de traitement* :
il n'est jamais une preuve de remise ou de lecture.

## Contrôle de débit WhatsApp (ce lot)

`MailPulseWhatsAppThrottle` s'exécute **avant** le POST `/api/v1/messages`
pour le canal WhatsApp, y compris lors des retries de l'outbox. Son verrou
protège les réservations concurrentes via un cache partagé. Les clés de
limitation sont dérivées d'une empreinte de configuration de transport et
d'une empreinte du destinataire : aucun numéro ni token en clair n'est
inscrit dans les clés du cache.

| Variable | Valeur initiale | Fonction |
| --- | --- | --- |
| `MAILPULSE_WHATSAPP_PACING_ENABLED` | `false` | activation explicite après test |
| `MAILPULSE_WHATSAPP_PER_MINUTE` | `6` | plafond local, par transport |
| `MAILPULSE_WHATSAPP_PER_HOUR` | `120` | plafond horaire local |
| `MAILPULSE_WHATSAPP_PER_RECIPIENT_HOUR` | `6` | éviter les répétitions au même parent |
| `MAILPULSE_WHATSAPP_MIN_SPACING_SECONDS` | `8` | espace les admissions au transport |

**Ce ne sont ni des limites officielles WhatsApp ni une garantie anti-blocage.**
Aucune quantité de temporisation ne rend conforme une campagne non sollicitée.
La passerelle doit aussi vérifier opt-in, fenêtre de conversation, modèles
autorisés lorsque nécessaires, bannissement, reconnexion, état de la session,
saturation et réputation, et doit pouvoir arrêter immédiatement les envois.

Si la limite est atteinte, le transport rend un résultat `whatsapp_pacing`
sans appeler MailPulse. L'outbox garde le même `request_id`, replanifie selon
`retryAfter` et ne décompte **pas** la tentative comme une requête fournisseur.
Une panne du cache rend `whatsapp_pacing_unavailable` et bloque l'envoi
(fail closed). Les messages continuent à expirer après leur TTL métier
(habituellement 24 h) : **ne pas livrer une annonce scolaire périmée**.

**Préconditions d'activation** : `QUEUE_CONNECTION=database|redis`, workers et
scheduler en service, cache central supportant les verrous (Redis conseillé),
horloge correcte, espaces de clés cohérents pour toutes les instances qui
partagent le transport. Si les tenants tournent sur plusieurs serveurs sans
cache commun, seule la limite côté passerelle fait autorité. Une file
`QUEUE_CONNECTION=sync` n'est pas adaptée à une campagne.

## Matrice à compléter avant ouverture d'une campagne

| Événement | Preuve métier / moment d'émission | Canaux ciblés | Précautions |
| --- | --- | --- | --- |
| Inscription | inscription engagée et définitivement confirmée | app + email étudiant et parents ; WhatsApp opt-in | un seul compte familial, aucune transmission du mot de passe |
| Réinscription | nouvelle inscription confirmée | app + e-mail familial ; WA opt-in | ne jamais réinitialiser le mot de passe existant |
| Paiement | validation ou rejet **effectif**, pas création seule | app + e-mail + WA opt-in | idempotence sur paiement + transition d'état ; éviter double reçu |
| Absence | présence définitivement saisie/confirmée | app + e-mail + WA opt-in | correction d'absence et justificatif ; éviter les faux positifs |
| Emploi du temps | publication/version/diff, changement confirmé | app + e-mail ciblé ; WA urgent opt-in | à développer : ne pas envoyer au simple brouillon/sauvegarde |
| Notes/bulletins | contenu publié aux parties habilitées | app + email + WA opt-in | aucun détail avant publication, pas de lien public non protégé |
| Rappel financier | dette valide, échéance admissible | app + email ; WA après opt-in et quotas | éviter répétitions, tenir compte des règlements récents |
| Annonces | ciblage, consentement, horaire, annulation | app + email ; WA si conformité démontrée | campagne distincte des messages de service |

## Recette technique avant activation

1. Sur `presentation`, vérifier les paramètres de queue, cache, scheduler,
   MailPulse et la séparation des instances ; ne pas toucher aux données live.
2. Lancer les tests `MailPulseWhatsAppThrottleTest`, `MailPulseClientTest`,
   `MailPulseParentNotificationLogTest`, `MailPulseWorkflowQueueTest`.
3. Tester le verrou avec plusieurs workers simultanés et simuler 429, 503,
   coupure de Redis et déconnexion Evolution ; vérifier l'absence de POST quand
   le garde refuse. **Le provider doit également appliquer son quota propre.**
4. Prouver un message accepté, livré, lu, puis un échec reprenable dans les
   événements/webhooks du provider. Ne pas inférer « livré » d'un simple 202.
5. Vérifier l'arrêt volontaire (`STOP`), les canaux parentaux, les parents
   ayant plusieurs enfants, les adresses en double, l'absence annulée,
   le paiement rejeté puis validé et l'absence de double e-mail.
6. Vérifier les backlog `jobs`, `failed_jobs` et `parent_notification_logs`
   **avant** de démarrer les workers, pour ne pas vider une ancienne file en masse.
7. N'activer le garde sur `presentation` qu'après tests et avec destinataires
   internes consentants ; ne pas propager automatiquement aux autres tenants.

## Phase 2 : e-mail familial autoritaire (13 octobre)

Le routage dans `WorkflowEmailAuthority` sélectionne le **mailer Laravel**
comme autorité unique pour `enrollment_created`, `reenrollment_created`,
`payment_received`, `payment_rejected`, `absence_reported`,
`low_attendance` et `low_grades`. Pour ces événements, la politique
MailPulse refuse le **canal email uniquement** ; les canaux WhatsApp/SMS
peuvent encore partir selon préférences, STOP et contrôle de débit.

`FamilyWorkflowMailer` produit une seule copie par e-mail validé/dédupliqué
du compte étudiant-parent ; les destinataires sont traités indépendamment
sans CC/BCC. Les événements où aucun mailer Laravel ne remplace MailPulse
(`grade_published`, `bulletin_published`, `fee_reminder`) restent sur
le mailer MailPulse.

**Limite explicite :** le mailer familial est encore synchrone ; un envoi
accepté ne prouve pas la délivrance. Le futur chantier d'outbox e-mail
transactionnelle et de webhook de suivi reste nécessaire. Ne pas confondre
la suppression des doublons avec la garantie de livraison.

## Suite recommandée (non incluse dans ce premier lot)

- Unifier l'envoi email dans une outbox transactionnelle et déclarer un
  transport autoritaire par type d'événement, pour supprimer les doublons
  Laravel/MailPulse sans perdre l'e-mail étudiant.
- Créer les événements de planification `timetable.published`,
  `timetable.changed`, `timetable.cancelled` avec version de planning,
  différentiel et destinataires calculés à l'instant d'envoi.
- Conserver les reçus et notifications d'absence comme messages
  transactionnels ; séparer nettement les **campagnes** marketing/annonces
  avec consentement démontrable, suppression, fréquence et périodes de repos.
- Ajouter un tableau opérateur (tentatives, erreurs, latence, statut de session,
  taux de remise, volume par école et canal) et alertes surcharge.
- Construire un limiteur et un circuit breaker au niveau **MailPulse/Evolution**
  pour la session Baileys physique, et les tests de reconnexion et d'absence
  de double livraison après timeout.

## Références

- Laravel queues, rate limiting et `afterCommit` :
  https://laravel.com/docs/9.x/queues
- Laravel rate limiting :
  https://laravel.com/docs/9.x/rate-limiting
- Mainteneurs Baileys : aucune limitation de débit applicative fournie
  https://github.com/WhiskeySockets/Baileys/blob/master/SECURITY.md
- Politique WhatsApp Business :
  https://business.whatsapp.com/policy/

## Confidentialité des alertes de résultats — correctif ciblé

Avant tout e-mail familial `low_grades`, KLASSCI exige un bulletin
réellement publié, signé et autorisé via
`ParentChatbotPublicationPolicy::reportCardIsPublished()`.
L'émetteur MailPulse réalise le même contrôle avant de préparer le message,
et `MailPulseWorkflowDisclosure` exige une déclaration
`report_card` au moment de la publication et lors des reprises de l'outbox.
Si l'identifiant manque ou si la publication a été annulée, le message
est bloqué. Les notes brutes et bulletins déjà publiés conservent leurs
règles de confidentialité initiales.
