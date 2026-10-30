# Annonces KLASSCI — ciblage, file et délivrabilité

## Choisir le public

Une annonce reste visible dans KLASSCI conformément à son type (générale, classes, étudiants).
**Le filtre e-mail est indépendant**. Par défaut, les NOUVELLES campagnes ciblent les inscriptions `status = active` de l'année universitaire courante ; un agent autorisé peut choisir tout le public de l'annonce, y compris d'anciens inscrits. Le modèle conserve `all` sur les campagnes historiques : aucune modification rétroactive ni nouvelle campagne n'est déclenchée.

Le filtre est appliqué à la même ligne d'inscription pour l'année, le statut et les classes retenues. L'année est figée à la publication, pas au passage du worker : un changement d'année entre la création et l'exécution ne modifie pas le public. Si aucune année n'est définie, publication avec filtre actif refusée avec une validation claire. Chaque adresse valide est normalisée et dédoublonnée par `(annonce_id,email)` ; les étudiants sans e-mail ne sont pas ajoutés à la file.

## États à ne pas confondre

- `pending` : adresse enregistrée, pas encore prise en charge par la queue.
- `processing` : préparation avant le transport HTTP ; un worker interrompu n'est jamais repris automatiquement par un nouvel envoi.
- `retrying` : MailPulse a refusé temporairement, job relâché avec délai.
- `awaiting_dispatch` : issue de soumission inconnue, y compris **avant** la requête HTTP (crash) ou durant l'appel ; aucun renvoi sans rapprochement (`pending_reconciliation`).
- `submitted` : MailPulse a accepté la soumission ; **ne prouve PAS la livraison**.
- `delayed` : fournisseur signale un report temporaire, **ne pas réexpédier** immédiatement.
- `delivered` : événement signé de délivrance reçu.
- `failed` : refus définitif de MailPulse, échec de la queue ou retour signé terminal du fournisseur.

Le compteur dans la fiche annonce n'est pas le nombre d'étudiants ayant ouvert leur boîte. Il compte des **adresses uniques**. Un e-mail soumis peut rester retardé chez Resend ; ne jamais marquer `delivered` sur la base de `MailPulseResult.ok` seul.

## Schéma, migration et travail de queue

Sur CHAQUE instance, après sauvegarde, appliquer les migrations selon son journal de déploiement, dont `2026_10_29_110000_add_email_delivery_to_esbtp_annonces.php` et `2026_10_29_160000_ajouter_ciblage_et_suivi_courriels_annonces.php`. Ne jamais les exécuter automatiquement à partir d'une erreur HTTP.

Configurer `MAILPULSE_API_KEY`, `MAILPULSE_ENABLED=true`, une file asynchrone réelle, workers et surveillance des `failed_jobs`, cadences et quotas. Les jobs de campagne utilisent désormais **directement le client MailPulse**, avec une idempotence permanente `klassci-annonce-{annonce_id}-courriel-{delivery_id}`. Pas de deuxième job de remise silencieux. Les envois sont étalés dans le temps à la cadence autorisée par instance, puis le `RateLimiter` protège contre les autres flux. Un replay de fanout ne réinsère jamais les adresses déjà connues.

Ne jamais relancer en masse les anciennes lignes `processing`, `submitted`, `awaiting_dispatch`, `delayed` ou `delivered` ; les idempotences du transport ancien étaient bornées à l'heure, et une reprise pourrait provoquer des doublons. Les premiers envois existants ne gagnent pas des preuves de livraison rétroactives.

## Lecture automatique des états (sans webhook supplémentaire)

Le planificateur KLASSCI exécute `annonces:synchroniser-courriels --max=60 --budget=45` toutes les cinq minutes.
Il relit l'API officielle `GET /api/v1/messages/{id}` de MailPulse, déjà utilisée
par le suivi des convocations. `delivery_delays` sont des événements réels Resend
conservés par MailPulse ; un statut `sent` accompagné de retards reste non livré.
Les erreurs/bounces sont enregistrés, mais **le rapprochement ne réémet jamais d'e-mail**.

La fenêtre de rapprochement et le budget réseau sont bornés ; en cas de panne MailPulse,
l'opération conserve les statuts existants et les réexamine au passage suivant.
La présence d'une confirmation `delivered_at` est indispensable pour compter
une remise. Vérifier le cron `schedule:run` sur chaque instance : sans cron,
aucune mise à jour automatique n'a lieu.

## Rapprochement sécurisé avec MailPulse : intégration à compléter côté fournisseur

KLASSCI propose `POST /api/v1/mailpulse/announcement-events`. Il **ne remplace pas** les webhooks Resend : MailPulse doit recevoir les événements de son fournisseur, les associer à `metadata.external_reference`, puis transmettre un événement réduit à KLASSCI. Le système n'affichera des délivrances confirmées qu'une fois ce relais configuré et testé.

Les champs autorisés sont `external_reference` (ex. `klassci-annonce-12-courriel-581`) et `state` (`delivered`, `delivery_delayed`, `bounced`, `failed`, `complained`, `suppressed`). Le corps JSON complet est signé en HMAC-SHA256 :
- `X-MailPulse-Timestamp`: secondes Unix,
- `X-MailPulse-Signature`: hex(`HMAC_SHA256(MAILPULSE_ANNOUNCEMENT_WEBHOOK_SECRET, timestamp + "." + rawBody)`),
- horloge tolérée : cinq minutes.

Sans `MAILPULSE_ANNOUNCEMENT_WEBHOOK_SECRET`, le serveur refuse toute mise à jour. En production, provisionner un secret **distinct par instance**, le garder dans les variables privées, ne pas l'exposer à l'écran ni dans Git. Vérifier les callbacks rejoués ou retardés et le lien entre l'ID MailPulse et le fournisseur. Ne pas inventer de compteurs délivrés en attendant.

## Incident du 29/10/2026

L'annonce « Reprise des cours » est publiée ; sa remise a eu lieu avec l'ancien transport. La capture MailPulse « Envoyé » montre l'acceptation en amont tandis que Resend affiche plusieurs `Delivery Delayed`. Certaines ont été délivrées, d'autres ont rebondi. **Ne pas relancer cette campagne** avant d'avoir rapproché les preuves, vérifié les inscriptions de l'année et confirmé un lot de test sous le nouveau contrat. Contrôler le domaine expéditeur (SPF/DKIM/DMARC), les retours SMTP détaillés et la réputation du domaine auprès des serveurs destinataires.

La première reprise est à diagnostiquer et n'est pas un échec de toutes les adresses. Voir la documentation Resend sur `email.delivery_delayed` et les webhooks de livraison.

## Prévisualisation des courriels HTML

Pour les nouveaux envois, le service `App\\Services\\Annonces\\CourrielAnnonce` rend la vue `esbtp.emails.annonce`, qui étend le gabarit KLASSCI `esbtp.emails.layout` déjà utilisé par les autres courriels. Le HTML est placé dans `metadata.email_html` seulement si le payload complet reste sous la limite MailPulse ; la version texte reste toujours disponible. La fiche d'annonce reproduit le rendu dans une iframe sans scripts ni privilèges d'origine. Côté MailPulse, `EmailHtmlPreview` affiche déjà `metadata.email_html` dans le détail du message. Les anciens messages texte ne peuvent pas obtenir rétroactivement cet HTML.

**Ne pas réexpédier les campagnes historiques pour leur appliquer le nouveau design.**

## États de fin et workers interrompus

La fiche annonce affiche `complete_delivered` uniquement si toutes les lignes ont une confirmation de livraison, `complete_with_failures` si aucune ligne n'est en cours mais que des échecs subsistent, et `pending` dès qu'une adresse attend une preuve. Zéro adresse ne constitue jamais une diffusion délivrée. L'épuisement local des tentatives de queue n'est plus assimilé à un échec du fournisseur : `awaiting_dispatch` + `queue_outcome_unknown` sont bloquants jusqu'à réconciliation manuelle, afin de ne pas créer de doublon après timeout.

**Relance effective NON activée** : la nouvelle migration d'essais immuables doit être produite avec `php artisan make:migration` conformément aux règles ; aucun contrôle UI ne doit exposer une relance tant que les garde-fous d'idempotence, preuves fournisseur et autorisations ne sont pas disponibles et testés. Ne pas relancer les 2 391 courriels historiques en attente.

## Précision anti-doublon sur les réponses HTTP incertaines

Un timeout, une perte de connexion, une réponse 5xx ou un HTTP 2xx dépourvu de contrat de dispatch utilisable ne prouve pas que MailPulse a refusé l'envoi. Le job conserve `awaiting_dispatch` avec `submission_outcome_unknown` et l'identifiant de requête lorsqu'il est disponible ; il ne relance pas automatiquement. Une exception après le début du transport HTTP est traitée de la même façon. Un `accepted` sans identifiant de message ne permet pas le rapprochement par API et reste lui aussi bloqué.

Les `rate_limited` (429 explicitement refusés) gardent la reprise différée. La résolution des résultats incertains doit confronter le journal MailPulse, l'identifiant d'idempotence et les événements du fournisseur, puis être auditée manuellement ; le simple temps écoulé ne constitue pas une preuve de non-envoi. Aucun lancement de relances historiques n'est autorisé par ce correctif.

## Réception atomique des événements (contrat renforcé)

L'API d'événements signés impose maintenant `message_id` en plus de `external_reference` et `state`. C'est l'identifiant interne MailPulse retourné à l'envoi, PAS l'identifiant Resend. La ligne cible doit porter les deux identifiants exacts dans la base de l'instance : sinon HTTP 202 sans mutation ni renseignement sur un autre destinataire. `occurred_at` (ISO-8601) peut transporter l'horodatage du fournisseur ; à défaut l'heure de réception est enregistrée. Les transitions `submitted` / `awaiting_dispatch` / `delayed` vers un état confirmé utilisent une mise à jour SQL conditionnelle : doublons, races et callbacks reçus après un état terminal ne réécrivent ni le statut ni son heure. Ce contrat est une évolution incompatible de l'ancienne ébauche de callback ; adapter et tester le relais MailPulse avant son activation. Voir `docs/api/MAILPULSE_ANNONCES.md`.

## Protection des workers interrompus (30/10/2026)

Avant de franchir l'appel `POST /api/v1/messages`, le job marque durablement la livraison comme `awaiting_dispatch` avec l'identifiant de requête original. Une transition SQL conditionnelle garantit qu'un ancien worker ayant perdu son bail n'envoie rien. Une réponse HTTP arrivée après une transition terminale ne la réécrit pas. Les lignes `processing` restées au-delà de `MAILPULSE_ANNOUNCEMENT_STALE_MINUTES` (par défaut 30, minimum 15) sont placées en quarantaine via la synchronisation planifiée, sans `POST`, et rapprochées avec la lecture seule `GET /api/v1/messages/by-idempotency-key`. Une réponse `404` reste **une absence de preuve, pas une permission de réexpédier**.

Ce garde ne résout PAS les clés d'idempotence historiques non préfixées par le tenant : organisations MailPulse et API keys séparées par établissement tant qu'un journal de tentatives versionné et sa migration Artisan ne sont pas déployés. Ce changement n'active aucune relance manuelle.

## Précondition tenant et configuration MailPulse

Avant toute première soumission, le code d'établissement `TENANT_CODE` doit être réel, non `default`, et respecter l'identifiant des instances. Sans ce code, le job met la ligne en quarantaine (`tenant_unconfigured`) **avant** le transport, sans la déclarer refusée par Resend. Un refus purement local (`missing_api_key`, `disabled`, `auth_failed`, `quota_exceeded`) est enregistré sans `provider_failed_at` et ne déclenche pas de nouvelle tentative automatique. Il faut corriger la configuration et faire une intervention explicitement contrôlée et tracée avant toute reprise. Ce contrôle n'est pas une solution au risque des clés historiques partagées entre organisations : provisionner MailPulse séparément par établissement.
