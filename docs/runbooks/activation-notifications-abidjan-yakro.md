# Activer les notifications réelles (e-mail et WhatsApp) — esbtp-abidjan et esbtp-yakro

Runbook du 1er octobre 2026. Il ne contient aucun secret : les clés se posent
depuis l'écran des réglages de l'école ou se lisent sur le serveur, jamais ici.

Ordre impératif : **lire, neutraliser les rafales, tester vers l'interne, puis
seulement ouvrir aux parents.** Une école à plus de deux mille inscriptions qui
bascule sans ces étapes envoie dès le lendemain 8 h des rappels accumulés, et
chaque validation de paiement part en double e-mail (voir « Risques »).

---

## 0. État réel constaté le 1er octobre 2026 (lecture seule, ~04 h 40)

Lu avec `settings:get`, `env`, `logs:show`, `payments:list` et
`GET /api/cli/rendez-vous/diagnostic`. **Rien n'a été écrit.**

| | esbtp-yakro | esbtp-abidjan |
|---|---|---|
| `TENANT_CODE` | `esbtp-yakro` | `esbtp-abidjan` |
| `mailpulse_enabled` | **1** | **1** |
| `mailpulse_real_workflows_enabled` | **1** | **1** |
| `mailpulse_api_key` | renseignée (masquée ; le diagnostic dit « messagerie active ») | renseignée (envois acceptés en 202 dans le journal du jour) |
| `mailpulse_sender_email` | **vide** | **vide** |
| `mailpulse_test_email_recipients` / `_phone_recipients` | `[]` (vides) | `[]` (vides) |
| `mailpulse_test_email`, `_test_phones` | vides | vides |
| `inscriptions.portail.verification_contact` | 0 | 1 |
| `inscriptions.rdv.enabled` | 0 (prise de RDV fermée, réglages incomplets) | 1 (333 places, 30 créneaux) |
| `inscriptions.workflow.*` | lignes absentes : parcours configurable inactif | lignes absentes : parcours configurable inactif |
| `sms_notifications` | 0 | 0 |
| Paiements `en_attente` | 9 | 5 |
| Convocations RDV | aucune | 12 en attente, 1 347 envoyées, 251 remises, 28 en échec, **1 190 sans e-mail** |
| Envois WhatsApp dans le journal du jour | aucun | aucun |
| Ligne « Système opérationnel » (tâche toutes les 15 min) | absente | absente |

Ce que cela veut dire :

1. **Les deux interrupteurs sont déjà à 1 sur les deux écoles.** Il n'y a rien
   à « allumer » côté réglages : les workflows parents réels sont actifs.
   Les étapes 5.4 et 6 ne servent plus qu'à revérifier.
2. **Le WhatsApp ne part pourtant presque jamais**, parce que les préférences
   parents valent par défaut `["app","email"]` (§ 1.2) et que les convocations
   de RDV ne partent que par e-mail. Faire partir réellement du WhatsApp aux
   parents demande une décision : faire adhérer les parents (chatbot parent,
   invitation Meta), ou poser `whatsapp` dans leurs canaux — ce qui relève du
   consentement, pas d'un réglage technique.
3. **Le planificateur ne semble pas tourner** : aucune trace de la tâche de
   surveillance (toutes les 15 min), et 12 convocations restent « en attente »
   à Abidjan alors que la tâche des convocations passe toutes les 5 min.
   À vérifier sur le serveur (crontab `schedule:run`) : sans lui, ni rappels,
   ni convocations, ni rejeux MailPulse ne partent, et la file `database` ne se
   vide que si un worker tourne. Le jour où la crontab est posée, les rafales du
   § 4 partent d'un coup.
4. **Aucun destinataire de test** : l'essai contrôlé (5.3) est impossible tant
   que les listes de test sont vides — c'est la première chose à poser.
5. **Expéditeur vide** : les e-mails MailPulse partent avec l'expéditeur par
   défaut de l'organisation MailPulse. À renseigner avec une adresse d'un
   domaine vérifié chez Resend.

---

## 1. Ce qui décide de l'envoi (inventaire vérifié dans le code)

### 1.1 Les deux interrupteurs MailPulse (table `settings`, groupe `mailpulse`)

| Clé | Lu par | Défaut si absent | Effet |
|---|---|---|---|
| `mailpulse_enabled` | `MailPulseClient::enabled()` (app/Services/MailPulse/MailPulseClient.php:262), `MailPulseApi.php:38`, `EtatChaineRdv.php:171`, `MailPulseWorkflowPolicy::realWorkflowsEnabled()` | **ligne absente → `.env` `MAILPULSE_ENABLED` (défaut `true`)** ; ligne créée par l'écran → `0` | Coupe TOUT appel MailPulse (convocations de rendez-vous, vérification de contact, lien d'activation, workflows parents). |
| `mailpulse_real_workflows_enabled` | `MailPulseWorkflowPolicy::realWorkflowsEnabled()` (MailPulseWorkflowPolicy.php:16), `MailPulseWorkflowQueue::push()`, `MailPulseWorkflowNotificationService::notifyTutor()` | `0` (ligne et `.env` `MAILPULSE_REAL_WORKFLOWS_ENABLED=false`) | Ouvre les notifications **aux parents** : paiement reçu / rejeté, absence, note publiée, bulletin publié, rappel de frais, inscription, réinscription, notes basses, assiduité basse. |
| `mailpulse_api_key` | `MailPulseClient.php:187` | `.env` `MAILPULSE_API_KEY` | Sans clé, tout envoi échoue en `missing_api_key`. **Ne se pose pas par `settings:set`.** |
| `mailpulse_base_url` | `MailPulseClient::url()` | `https://mailpulse-two.vercel.app` | URL de production MailPulse. Ne pas changer. |
| `mailpulse_sender_email`, `mailpulse_sender_name` | payloads e-mail | vide / `KLASSCI` | Expéditeur affiché. L'adresse doit appartenir à un domaine vérifié chez Resend. |
| `mailpulse_test_email_recipients`, `mailpulse_test_phone_recipients` | `MailPulseTestNotificationService::activeEmails()/activePhones()` (l.141, l.165) | vide (repli : `mailpulse_test_email`, `mailpulse_test_phones`) | Seuls destinataires de l'essai contrôlé. JSON `[{"value":"…","enabled":true}]`. |

Les deux interrupteurs sont **séparés exprès** : `mailpulse_enabled=1` seul fait
partir les convocations de rendez-vous et les codes de vérification, mais aucun
message aux parents.

Piège de défaut : `MailPulseWorkflowPolicy` et `MailPulseClient` retombent sur
`MAILPULSE_ENABLED=true` quand la ligne n'existe pas, alors que l'écran de
communication (`CommunicationMailPulseController.php:28`) affiche « désactivé »
dans le même cas. Toujours lire la ligne réelle avant de conclure.

### 1.2 Qui reçoit, par quel canal (préférences parent)

`parent_notification_preferences` (modèle `ParentNotificationPreference`) :

- `notify_inscriptions|paiements|absences|notes|bulletins|annonces` : défaut `true`.
- `preferred_channels` : défaut **`["app","email"]`** (ParentNotificationPreference.php:38).
  **WhatsApp n'est PAS dans le défaut.** Un parent ne reçoit de WhatsApp de
  workflow que si `whatsapp` figure dans ses canaux (choix fait par le parent ou
  posé par l'école). Activer l'école ne déclenche donc pas de WhatsApp de masse
  aux parents ; elle déclenche des **e-mails** à tous les parents qui ont une adresse.
- Un parent qui a répondu STOP au chatbot (`parent_chatbot_links.status = stopped`)
  ne reçoit plus ni WhatsApp ni SMS (MailPulseWorkflowPolicy::isStoppedForMessaging).

### 1.3 Envois SMTP directs (`.env` `MAIL_*`), indépendants de MailPulse

`app/Services/NotificationService.php` envoie aussi par Laravel `Mail::` (donc
par le SMTP du `.env`, sans aucun interrupteur d'école) :

| Méthode | Ligne | Destinataire | Garde |
|---|---|---|---|
| `notifyParentsInscriptionCreated` | 2586 | parent | préférence `email` du parent |
| `notifyParentsPaiementValide` | 2680 | parent | idem |
| `notifyParentsPaiementRejete` | 2739 | parent | idem |
| `notifyParentsAbsence` | 2817, 2824 | parent | idem |
| `notifyParentsLowGrades` | 2946 | parent | idem |
| `notifyParentsReinscriptionCreated` | 3035 | parent | idem |
| `envoyerRelanceEmail` | 54 | étudiant | bouton « Exécuter les relances en attente » de la comptabilité |
| `AdmissionActivationNotifier::sendEmail` | AdmissionActivationNotifier.php:131 | candidat | `inscriptions.workflow.notify_email` |

Si le SMTP de l'instance fonctionne, ces e-mails **partent déjà aujourd'hui**.
Si la préférence parent contient `email` et que `mailpulse_real_workflows_enabled`
passe à 1, le parent reçoit **deux e-mails** pour le même évènement (un SMTP, un
MailPulse). Voir « Risques ».

### 1.4 Autres interrupteurs

| Clé | Table | Lu par | Défaut | Rôle |
|---|---|---|---|---|
| `inscriptions.workflow.notify_email` | settings | `AdmissionActivationNotifier` l.53, 66, 123 | `1` | Lien d'activation du compte étudiant par e-mail (SMTP) — parcours d'inscription configurable seulement (`inscriptions.workflow.enabled=1`). |
| `inscriptions.workflow.notify_whatsapp` | settings | `AdmissionActivationNotifier` l.54, 75 ; `AdmissionWhatsappActivationLink` l.45 | `1` | Même lien par WhatsApp (MailPulse). |
| `inscriptions.portail.verification_contact` | settings | `TenantScolariteSettings::VERIFICATION_CONTACT` | — | Code de vérification e-mail/téléphone du portail de candidature (MailPulse). |
| `inscriptions.rdv.enabled` | settings | `RendezVousReglages::ENABLED` | — | Rendez-vous ; leurs convocations partent dès que `mailpulse_enabled=1` (tâche `inscriptions:envoyer-convocations-rdv`, toutes les 5 min). |
| `reminder_inscription_enabled` | **esbtp_system_settings** | `SendInscriptionPaiementReminders` l.79 | `true` | Rappels quotidiens 8 h (in-app aux superAdmin). |
| `reminder_paiement_enabled` | **esbtp_system_settings** | `SendInscriptionPaiementReminders` l.163 | `true` | Rappels quotidiens 8 h des paiements `en_attente` : in-app superAdmin **+ `fee_reminder` MailPulse au parent** (NotificationService.php:2202). |
| `reminder_paiement_first_delay` / `_frequency` / `_max_count` | esbtp_system_settings | idem | 2 j / 1 j / 7 | Cadence des rappels paiement. |
| `relances.relances_automatiques` | settings | `ESBTPComptabiliteRelanceController` l.515 | `0` | Affiché seulement : la planification quotidienne des relances (`PlanifierRelancesJob`, 8 h et 14 h) n'en tient pas compte ; elle crée des lignes `planifiee` qui ne partent qu'au clic « Exécuter ». |
| `tpe.notify_email` | settings | `TpeDeclarationStatusChangedNotification.php:36` | `false` | E-mail au déclarant TPE. |
| `analytics.anomaly.notifications_enabled` | settings | `DetectAnalyticsAnomaliesJob.php:78` | `1` | Alertes in-app aux comptables. |
| `comptabilite.notify_high_amount_threshold` | settings | `ESBTPPaiementController.php:2209` | 5 000 000 | Seuil d'alerte in-app gros montant. |

### 1.5 Variables `.env` (serveur, hors CLI)

| Variable | Défaut | Rôle |
|---|---|---|
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | `smtp` | Tous les envois SMTP du § 1.3. Volontairement non modifiables par le CLI (`CleEnvAutorisee` : intercepter les réinitialisations de mot de passe). |
| `QUEUE_CONNECTION` | `database` (config/queue.php:16) | Les notifications parents passent par la file (`DispatchMailPulseParentNotificationJob`). En `database` **sans worker**, elles s'empilent dans `jobs` et partent en rafale au premier `queue:work`. En `sync`, elles partent dans la requête. |
| `MAILPULSE_*` (clé d'API, chatbot parent : `EXTERNAL_*`, `PARENT_CHATBOT_*`) | voir `.env.example` | Rail du chatbot parent. Non modifiables par le CLI. |
| `TENANT_CODE` | — | Préfixe les identifiants MailPulse (`MailPulseTenantContext`). Doit valoir `esbtp-abidjan` / `esbtp-yakro`. Lisible par `klassci env`. |

---

## 2. Ce que le CLI permet, et ce qu'il ne permet pas

| Besoin | Commande | Remarque |
|---|---|---|
| Lire un réglage | `klassci settings:get <ecole> <cle>` | Droit `cli:read`. Valeurs « secrètes » masquées. |
| Poser un réglage | `klassci settings:set <ecole> <cle> <valeur>` | Droit `cli:admin`. Aperçu, confirmation, journalisé. **Ne crée pas une ligne absente** (404), sauf quelques clés de scolarité. |
| Poser la clé MailPulse | — | Refusé par `settings:set` : écran `/esbtp/settings`, onglet MailPulse. |
| Essai d'envoi vers l'interne | `klassci mailpulse:test <ecole> <email\|whatsapp\|sms\|both> [--evenement=] [--envoyer]` | **Nouvelle commande** (branche `claude/sharp-archimedes-58vj1j` de klassci-cli). Simulation par défaut. N'écrit qu'aux destinataires de test ; l'école refuse si la liste est vide. |
| Lire `TENANT_CODE` | `klassci env <ecole>` | Seules 3 clés `.env` sont gérables. |
| Lire les journaux | `klassci logs:show <ecole> --search=… --level=…` | Max 200 entrées. |
| Interrupteurs de rappels (`reminder_*`) | — | Table `esbtp_system_settings`, hors API CLI : écran `/esbtp/settings`, section Rappels. |
| Préférences parents, file `jobs`, `parent_notification_logs` | — | Aucune route CLI. Écrans ou base. |

Les lignes `mailpulse_*` sont créées par `ESBTPSettingsController::ensureMailPulseSettings()`
dès qu'un administrateur ouvre `/esbtp/settings`. Si `settings:get` répond
« introuvable », ouvrir cette page une fois avec un compte superAdmin, puis relancer.

---

## 3. Prérequis humains (hors CLI)

1. **MailPulse** : une organisation par école, une clé d'API `mp_live_…` par
   école, un domaine d'envoi vérifié chez Resend pour `mailpulse_sender_email`.
2. **WhatsApp (Evolution)** : une instance Evolution par école sur
   `https://evolution.klassci.com`, à l'état `open`, avec un numéro WhatsApp
   dédié à l'école (jamais le numéro de presentation). Voir
   `mailpulse/.claude/rules/evolution-whatsapp-server.md` (Memurai en marche,
   reprise automatique des services).
3. **Templates** : les gabarits e-mail des évènements parents existent dans
   MailPulse ; pour le chatbot parent seulement, le gabarit Meta
   `parent_chatbot.invitation` approuvé.
4. **SMTP** : décider si les e-mails parents passent par le SMTP Laravel ou par
   MailPulse (voir Risques 4.2). Vérifier `MAIL_*` sur le serveur.
5. **File** : vérifier `QUEUE_CONNECTION` et qu'un `queue:work` (ou
   `schedule:run`) tourne. La tâche « surveillance-systeme » écrit toutes les
   15 min `queue_size` dans le journal.
6. **Scheduler** : la crontab `schedule:run` doit tourner sur les deux
   instances, sinon rappels, convocations et rejeux ne partent jamais.
7. **Deux adresses et deux numéros internes** (équipe ADC + un responsable de
   l'école) pour l'essai.

---

## 4. Risques à neutraliser (déjà ouverts : les interrupteurs sont à 1, voir § 0)

### 4.1 Rafale de « Rappel de frais impayés »

`reminders:send-inscription-paiement` tourne chaque jour à 8 h depuis le début
de l'année. Pour chaque paiement `en_attente` (versé par la famille, **pas encore
validé par l'école**), il envoie un rappel tous les jours, jusqu'à 7. Les
compteurs ont avancé même sans MailPulse ; à l'activation, tout paiement encore
en attente et non plafonné déclenche dès le lendemain un message au parent
intitulé « Rappel de frais impayés » — alors que le parent a payé et que c'est
l'école qui n'a pas validé.

Neutraliser, au choix :
- passer `reminder_paiement_enabled` à **non** dans `/esbtp/settings` (section
  Rappels) — recommandé tant que le libellé du message n'est pas corrigé ;
- ou solder la file : valider/rejeter les paiements `en_attente` (lecture du
  montant : `klassci payments:list <ecole> --status=en_attente` ou `/esbtp/paiements`).

### 4.2 Double e-mail aux parents

Avec `mailpulse_real_workflows_enabled=1`, un parent dont la préférence contient
`email` reçoit l'e-mail SMTP de `NotificationService` **et** l'e-mail MailPulse.
Aucun réglage d'école ne sépare les deux. Options :
- tant que le code n'est pas corrigé, n'activer les workflows que si le SMTP de
  l'instance est **inopérant** (vérifier les erreurs `Erreur notification paiement
  validé parent` dans les journaux) ;
- ou corriger le code (voir § 8) avant d'activer.

### 4.3 File `database` sans worker

Si `QUEUE_CONNECTION=database` et qu'aucun worker ne tourne, les intentions
s'empilent dans `jobs` et partent toutes au premier `queue:work`. Vérifier
`queue_size` dans les journaux avant et une heure après l'activation.

### 4.4 Convocations de rendez-vous en attente

Dès `mailpulse_enabled=1`, la tâche des convocations envoie toutes les
convocations à l'état « en attente » (par paquets de 50 toutes les 5 min).
Lire d'abord `GET /api/cli/rendez-vous/diagnostic` et
`klassci rendez-vous:familles <ecole>`.

### 4.5 Relances planifiées

`PlanifierRelancesJob` crée chaque jour des lignes `planifiee`. Elles ne partent
qu'au clic « Exécuter les relances en attente » de la comptabilité, mais alors
toutes ensemble, par SMTP, aux étudiants. Prévenir le comptable de ne pas cliquer
pendant la mise en service.

---

## 5. Commandes, dans l'ordre — esbtp-yakro

Faire Yakro d'abord (même ordre ensuite pour Abidjan). `Y=esbtp-yakro`.

### 5.1 Lecture (rien n'est écrit)

```bash
klassci config:list                       # l'école est configurée avec un jeton cli:admin
klassci env esbtp-yakro                   # TENANT_CODE = esbtp-yakro
klassci settings:get esbtp-yakro mailpulse_enabled
klassci settings:get esbtp-yakro mailpulse_real_workflows_enabled
klassci settings:get esbtp-yakro mailpulse_api_key           # doit afficher (masque), pas vide
klassci settings:get esbtp-yakro mailpulse_sender_email
klassci settings:get esbtp-yakro mailpulse_test_email_recipients
klassci settings:get esbtp-yakro mailpulse_test_phone_recipients
klassci settings:get esbtp-yakro inscriptions.workflow.enabled
klassci settings:get esbtp-yakro inscriptions.workflow.notify_email
klassci settings:get esbtp-yakro inscriptions.workflow.notify_whatsapp
klassci settings:get esbtp-yakro inscriptions.rdv.enabled
klassci settings:get esbtp-yakro inscriptions.portail.verification_contact
klassci payments:list esbtp-yakro --status=en_attente --limit=500 --json      # taille de la rafale 4.1
klassci logs:show esbtp-yakro --search=queue_size --lines=5       # file 4.3
klassci logs:show esbtp-yakro --search="notification paiement" --level=error --lines=20   # SMTP 4.2
```

Si une clé `mailpulse_*` est « introuvable » : ouvrir `/esbtp/settings` une fois
(superAdmin), relancer la lecture.

### 5.2 Mise en place (écran, par un humain)

1. `/esbtp/settings` → onglet MailPulse : coller la clé `mp_live_…` de l'école,
   l'adresse d'expéditeur vérifiée, cocher **« Activer les envois MailPulse »**,
   laisser **« workflows parents réels » décoché**.
2. `/esbtp/settings` → section Rappels : décocher **Rappels paiements** (4.1).

### 5.3 Destinataires de test et essai contrôlé

```bash
klassci settings:set esbtp-yakro mailpulse_test_email_recipients '[{"value":"<adresse-interne>","enabled":true}]'
klassci settings:set esbtp-yakro mailpulse_test_phone_recipients '[{"value":"<+225 numéro interne>","enabled":true}]'

klassci mailpulse:test esbtp-yakro both                      # simulation : config + aperçu
klassci mailpulse:test esbtp-yakro email --envoyer
klassci mailpulse:test esbtp-yakro whatsapp --envoyer
klassci mailpulse:test esbtp-yakro whatsapp --evenement=absence_reported --envoyer
```

Contrôle : l'e-mail et le WhatsApp arrivent bien sur les appareils internes,
avec le nom de l'école et l'expéditeur attendu. Côté MailPulse, le message
apparaît dans l'organisation de l'école (pas dans celle de presentation).

### 5.4 Ouverture aux parents

Seulement après 4.1 à 4.5 traités et 5.3 vert. Au 1er octobre 2026 la valeur est déjà `1` : la commande répondra « Rien à faire ». Si l’on décide de suspendre le temps de traiter le § 4, la passer d’abord à `non` (§ 8).

```bash
klassci settings:set esbtp-yakro mailpulse_real_workflows_enabled oui
```

Puis valider UN paiement réel d'un parent volontaire (ou d'un membre du
personnel parent d'élève) et vérifier la réception.

### 5.5 Parcours d'inscription (si l'école l'utilise)

```bash
klassci settings:set esbtp-yakro inscriptions.workflow.notify_email oui
klassci settings:set esbtp-yakro inscriptions.workflow.notify_whatsapp oui
```

Le lien d'activation ne part que vers un contact **prouvé** (code de
vérification) : rien ne part vers un numéro saisi non vérifié.

## 6. Commandes, dans l'ordre — esbtp-abidjan

Identiques, en remplaçant le code d'école :

```bash
klassci env esbtp-abidjan
klassci settings:get esbtp-abidjan mailpulse_enabled
klassci settings:get esbtp-abidjan mailpulse_real_workflows_enabled
klassci settings:get esbtp-abidjan mailpulse_api_key
klassci settings:get esbtp-abidjan mailpulse_sender_email
klassci settings:get esbtp-abidjan mailpulse_test_email_recipients
klassci settings:get esbtp-abidjan mailpulse_test_phone_recipients
klassci settings:get esbtp-abidjan inscriptions.workflow.enabled
klassci settings:get esbtp-abidjan inscriptions.rdv.enabled
klassci payments:list esbtp-abidjan --status=en_attente --limit=500 --json
klassci logs:show esbtp-abidjan --search=queue_size --lines=5
klassci logs:show esbtp-abidjan --search="notification paiement" --level=error --lines=20
# écran : clé MailPulse + expéditeur + mailpulse_enabled ; rappels paiements décochés
klassci settings:set esbtp-abidjan mailpulse_test_email_recipients '[{"value":"<adresse-interne>","enabled":true}]'
klassci settings:set esbtp-abidjan mailpulse_test_phone_recipients '[{"value":"<+225 numéro interne>","enabled":true}]'
klassci mailpulse:test esbtp-abidjan both
klassci mailpulse:test esbtp-abidjan email --envoyer
klassci mailpulse:test esbtp-abidjan whatsapp --envoyer
klassci settings:set esbtp-abidjan mailpulse_real_workflows_enabled oui
```

Abidjan a sa **propre** instance Evolution et son **propre** numéro : un essai
WhatsApp qui arrive depuis le numéro de Yakro signale une instance mal câblée
dans MailPulse — arrêter là.

---

## 7. Vérifier après activation

- **Journal parent** : table `parent_notification_logs` (une ligne par envoi,
  `metadata.provider = mailpulse`, `status` `sent` / `pending` / `failed`,
  `request_id`). Les `pending` sont rejoués toutes les 5 min par
  `mailpulse:reconcile-parent-notifications`.
- **Journaux** : `klassci logs:show <ecole> --search=MailPulse --lines=50`. Messages à
  surveiller : `MailPulse workflow notification could not be queued`,
  `MailPulse parent notification job failed`, `MailPulse workflow notification lost`.
- **File** : `queue_size` doit redescendre ; `failed_jobs` ne doit pas monter.
- **MailPulse** : tableau de bord de l'organisation de l'école (remises,
  rebonds, lectures).
- **Evolution** : `GET /instance/connectionState/<instance>` doit dire `open`.

## 8. Retour arrière

Immédiat, sans déploiement :

```bash
klassci settings:set <ecole> mailpulse_real_workflows_enabled non   # coupe les parents
klassci settings:set <ecole> mailpulse_enabled non                  # coupe TOUT MailPulse
```

Les messages déjà acceptés par MailPulse ne se rappellent pas. Les intentions
encore dans la file sont abandonnées par le worker (il revérifie l'interrupteur
avant d'émettre). Les rappels se coupent dans `/esbtp/settings` (Rappels).

## 9. Corrections de code proposées (non appliquées)

1. **Double e-mail (4.2)** : dans `NotificationService::notifyParents*`, ne
   faire le `Mail::to($tuteur->email)` SMTP que si
   `MailPulseWorkflowPolicy::realWorkflowsEnabled()` est faux — MailPulse prend
   alors le relais de l'e-mail parent.
2. **Rappel trompeur (4.1)** : `fee_reminder` déclenché sur un paiement
   `en_attente` dit « frais impayés » à un parent qui a payé. Soit le retirer de
   `sendPaiementReminder` (rappel interne aux superAdmin seulement), soit le
   réserver aux échéances impayées.
3. **Défaut contradictoire de `mailpulse_enabled`** : aligner le repli de
   `MailPulseClient` / `MailPulseWorkflowPolicy` (`true`) sur celui de l'écran (`0`).
4. **Rappels pilotables par CLI** : les clés `reminder_*` vivent dans
   `esbtp_system_settings`, invisibles de `/api/cli/settings`. Les exposer en
   lecture (et en écriture sur `reminder_paiement_enabled` /
   `reminder_inscription_enabled`, booléens sans risque) dans
   `CLISettingsController` permettrait de neutraliser la rafale à distance.
