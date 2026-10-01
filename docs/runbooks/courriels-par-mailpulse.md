# Faire partir tous les courriels par MailPulse

Runbook du 1er octobre 2026. Aucun secret ici : les clés se lisent sur le serveur
ou se posent depuis l'écran des réglages de l'école.

## Ce qui change

Le mailer `mailpulse` (`App\Mail\Transport\MailPulseTransport`, branché dans
`AppServiceProvider`) fait partir **tout** courriel de Laravel par l'API publique
de MailPulse (`POST /api/v1/messages`, via `MailPulseClient::sendEmailMessage()`) :
`Mail::to()->send()`, `Mail::raw()`, mailables en file, canal `mail` des
notifications. Aucun appelant n'est modifié : c'est le mailer par défaut qui
change.

**La bascule est un réglage de l'école**, pas une ligne du `.env` : écran
`/esbtp/settings`, onglet **MailPulse**, case « Envoyer tous les e-mails de
l'école par MailPulse » (réglage `mailpulse_courriels_enabled`), puis
« Enregistrer MailPulse ». Il faut aussi « Activer MailPulse pour cette
instance » : sans lui, la case reste sans effet et les e-mails continuent de
partir par le serveur de messagerie. L'enregistrement refuse de cocher la case
si MailPulse est coupé ou si aucune clé API n'est posée : sans eux, **plus aucun**
e-mail ne partirait.

Le mailer est relu **à chaque envoi** (`App\Mail\Transport\MailerDeLEcole`, via
`MailManagerDeLEcole::getDefaultDriver()`), pas au démarrage : un worker de file
qui tourne suit la case dès son enregistrement, sans `queue:restart`. Rien n'est
lu en base au démarrage de l'application, donc `migrate` sur une installation
neuve ou une base injoignable ne plantent pas : le mailer du serveur reste en place.

`MAIL_MAILER=mailpulse` dans le `.env` reste accepté comme **dérogation du
serveur** : il impose MailPulse quelle que soit la case, et l'écran le dit. Ne
l'utilisez plus pour basculer une école ; laissez `MAIL_MAILER=smtp`, qui sert de
repli quand la case est décochée.

**La CLI ne peut pas basculer une école.** `mailpulse_courriels_enabled` et
`mailpulse_base_url` sont refusés par `POST /api/cli/settings` comme par
`PUT /api/cli/settings/{key}` : avec la case cochée, ces réglages décident qui lit
un lien de réinitialisation de mot de passe — la raison même qui tient `MAIL_*`
hors de `CleEnvAutorisee`. Ils se changent depuis l'écran (`system.manage`).

## Ce que MailPulse transporte, et ce que le mailer en fait

L'API publique n'accepte qu'**un destinataire**, **un texte**, et un HTML rangé
dans `metadata.email_html` ; l'objet `metadata` entier est plafonné à
**16 384 octets** de JSON. Le mailer s'adapte côté KLASSCI :

| élément du courriel | conduite |
|---|---|
| À, Cc, Cci | un message MailPulse par adresse |
| Clé d'idempotence | tirée du destinataire et du contenu, rangée dans l'heure (`MailPulseTransport::cleIdempotence()`) : une reprise de file dans l'heure ne renvoie pas le courriel. Deux limites : deux courriels **identiques** au même destinataire dans la même heure n'en font qu'un, et une reprise qui franchit l'heure peut doubler. Et un message **refusé définitivement** par MailPulse ne peut pas être renvoyé à l'identique dans la même heure : la même clé rend le même refus. Modifier le contenu, ou attendre l'heure suivante |
| Sujet | `metadata.subject` |
| HTML | commentaires et indentation retirés, sauts de ligne gardés, puis `metadata.email_html` |
| HTML au-delà du plafond | **la version texte part seule**, avertissement au journal |
| Texte | `content.text` ; tiré du HTML (liens conservés en clair) s'il manque |
| Expéditeur | réglage `mailpulse_sender_email` s'il est posé, sinon `MAIL_FROM_ADDRESS` ; nom = réglage `mailpulse_sender_name` (`KLASSCI` tant que l'école n'en pose pas d'autre ; `MAIL_FROM_NAME` n'est pas lu). MailPulse ne retient l'adresse que si son domaine est vérifié chez lui, sinon il prend l'expéditeur par défaut de l'organisation |
| Image intégrée (`cid:`) | retirée du HTML, avertissement au journal. Le gabarit commun `esbtp.emails.layout` passe déjà par l'URL du logo quand ce mailer est actif |
| Pièce jointe | **refus** : exception, rien ne part. Un « ci-joint votre export » sans l'export serait un succès mensonger |
| Accepté mais remise à confirmer (`pending`, `pending_reconciliation`) | part sans exception, avertissement au journal (`remise à confirmer`) : MailPulse rejoue ou tranche |
| Reply-To, en-têtes personnalisés | non transmis (Reply-To journalisé) |

Tout refus de MailPulse (désactivé, clé absente, injoignable, 4xx/5xx, envoi
refusé par le fournisseur) **lève une exception** et s'écrit au journal
(`Courriel refusé par MailPulse`, statut, code HTTP, request id). Les écrans qui
disent « le courriel n'a pas pu partir » continuent donc de le dire.

## Les limites de MailPulse — à lire avant de basculer

Mesurées dans le code de MailPulse (dépôt `mailpulse`, branche `master`, 1er octobre 2026) :

| limite | valeur | où | au-delà |
|---|---|---|---|
| débit e-mail | **60 par minute et par organisation** | `src/lib/mailpulse/api-rate-limits.ts`, appliqué par `delivery-limits.ts` | refus `429` |
| quota mensuel | **5 000 e-mails** sur l'offre `FREE` (« Starter »), illimité sur `PRO` et `ENTERPRISE` | `src/lib/plan-catalog.ts` | refus `429` (« Monthly email quota exceeded ») jusqu'au mois suivant |

Les deux comptent **par organisation MailPulse, pas par école**, et tous canaux
API confondus pour l'e-mail : les notifications parents, les convocations de
rendez-vous et les codes de vérification, qui passent déjà par MailPulse,
consomment le même budget que les courriels du mailer. Si plusieurs écoles
partagent une organisation, elles partagent ces 60 par minute.

Ce que fait le mailer pour tenir le débit (`App\Mail\Transport\CadenceMailPulse`),
**au mieux** :

- il compte ses propres envois par minute (`MAILPULSE_MAIL_PER_MINUTE`, **30** par
  défaut, pour laisser la place aux notifications et aux convocations qui passent
  déjà par MailPulse) ;
- au-delà, ou sur un `429` de débit de MailPulse, il **diffère** le courriel au
  lieu de le refuser, et **sans jamais attendre** : un `sleep()` ferait dépasser
  au worker son délai de 60 s ou le `retry_after` de 90 s, et le job repartirait
  en double. Le message déjà construit et sa clé d'idempotence partent dans un
  job dédié, `App\Jobs\MailPulse\RemettreCourrielMailPulse`, **un par
  destinataire**, avec le délai du refus (l'en-tête `Retry-After` de MailPulse
  s'il est présent, 60 s sinon). La charge est chiffrée en file
  (`ShouldBeEncrypted`) : elle porte le corps du courriel, donc parfois un mot de
  passe initial ou un lien de réinitialisation, et `jobs` comme `failed_jobs`
  partent dans les sauvegardes. **Ne changez pas `APP_KEY` tant que la file
  `jobs` contient des courriels différés** : Laravel 9 ne garde pas l'ancienne clé,
  ces jobs deviendraient illisibles et seraient perdus sans trace. L'appelant n'a rien à rattraper : pour
  lui le courriel est parti (journal : `Courriel par MailPulse : envoi différé`) ;
- il en va de même des **pannes passagères** de MailPulse
  (`RefusMailPulse::PASSAGERS` : service indisponible en 5xx, délai dépassé,
  connexion impossible) : le courriel est différé, pas perdu. Le rejouer est sans
  risque : la clé d'idempotence fait renvoyer par MailPulse le message déjà pris
  au lieu d'en créer un second ;
- ce job réessaie tant que le refus est passager, sans brûler d'essai : sa patience est
  bornée dans le temps (`retryUntil`, **deux heures** à compter de la mise en
  file), pas en tentatives, et `--tries` est alors ignoré par le worker. Chaque
  refus le relâche avec au moins 60 s, plus un peu de hasard pour que les
  courriels retenus ne retombent pas tous dans la même minute. Parti, il le
  journalise (`Courriel différé parti par MailPulse`) ; passé deux heures, il
  échoue (`Courriel différé abandonné`, et `failed_jobs`) ;
- un refus **définitif** dans ce job (adresse refusée, clé invalide, quota) le
  fait échouer **tout de suite**, journalisé : il ne passerait pas davantage dans
  deux heures. Le journal d'abandon ne reprend pas le message brut de MailPulse,
  qui peut citer l'adresse : le statut et le code sont sur la ligne
  `Courriel refusé par MailPulse` qui le précède ;
- un `429` de **quota** (`quota_exceeded`) n'est jamais différé : il ne passera
  pas avant le mois suivant ou un changement d'offre. C'est un échec d'envoi
  ordinaire, et il arrête la file des convocations ;
- **sans file** (`QUEUE_CONNECTION=sync`), différer est impossible : le refus de
  débit ou la panne passagère redevient un échec d'envoi, journalisé, que l'appelant traite comme
  toute panne. L'écran des paramètres l'annonce à côté de la case (voir plus bas).

« Au mieux » veut dire : pas de garantie. La fenêtre est fixe ici et glissante
chez MailPulse, l'incrément du cache `file` n'est pas atomique entre deux
processus, et le compteur ne voit que son instance — ni les autres écoles d'une
même organisation, ni les autres flux MailPulse. Un `429` reste possible ; il
prend le même chemin que le refus local. Il s'écrit en **avertissement**
au journal (`Courriel refusé par MailPulse`), pas en erreur : rien n'est cassé.

Un courriel à plusieurs destinataires (À, Cc, Cci) part en un message par
adresse ; seuls ceux que le débit retient sont différés, chacun dans son job.
Aucun n'est renvoyé deux fois, et le plafond ne peut plus bloquer un courriel
qui aurait plus de destinataires que lui.

**Ce qui se passe, appelant par appelant**, quand le débit est atteint (avec une
file et un worker actifs) :

| appelant | contexte | ce qui se passe |
|---|---|---|
| Appel de fin de cours (`TeacherDashboardController`), appels du LMS (`API\LMSWriteController`, `API\LMSDataController`), saisie d'absence (`ESBTPAttendanceController`) → `notifyParentsAbsence()` | requête web, en rafale | **différé**, plus perdu : l'avis au parent part dans les deux heures |
| « Exécuter les relances en attente » (`ESBTPComptabiliteRelanceController::executerRelances()`) | requête web, en rafale | **différé** ; la relance est marquée `envoyee` dès la mise en file (voir plus bas) |
| `EnvoyerRelanceJob` (« Renvoyer » une relance), `SendReinscriptionMailJob` (réinscription groupée) | file | **différé** par son propre job, le job appelant se termine normalement |
| Notifications en file (`ESBTPNotification`, `PaiementNotification`, `AbsenceNotification`, …), canal `mail` | file | **différé**, l'avis en base n'est pas doublé |
| Inscription, réinscription à l'unité, validation et rejet de paiement, publication de bulletin → `notifyParents*()` | requête web, un courriel | **différé** |
| Mot de passe oublié (`ForgotPasswordController`) | requête web | **différé** ; sous `sync` seulement, message « n'a pas pu partir, réessayez dans quelques minutes » |
| Fin des tâches de bulletins, retour du support, rapport généré | file ou web, un courriel | **différé** |

**Ce qu'un courriel différé ne dit pas à l'appelant.** Pour lui, le courriel est
parti au moment de la mise en file. Si le job échoue ensuite (deux heures de
débit saturé ou MailPulse en panne, ou un refus définitif), l'appelant ne le saura pas : une
relance reste marquée `envoyee`, un avis reste « envoyé ». La seule trace est au
journal (`Courriel différé abandonné`) et dans `failed_jobs`. C'est le prix de ne
plus perdre les rafales ; un écran de suivi des courriels différés n'existe pas.

Deux autres limites, à connaître :

- un job différé mis en file **dans une transaction** qui est ensuite annulée
  l'est avec elle ; le courriel ne part pas, comme les données ;
- la clé d'idempotence voyage dans le job, inchangée : si MailPulse a déjà pris
  le message (réponse perdue), il ne le doublera pas.

**Les instances Élite (`esbtp-abidjan`, `esbtp-yakro`, plus de 2 000
inscriptions) peuvent maintenant basculer, à trois conditions** : un worker qui
tourne en permanence et `QUEUE_CONNECTION` différent de `sync` ; une offre
MailPulse dont le quota tient le mois (une journée d'appels y dépasse vite 30
courriels par minute, et chaque courriel retardé reste un courriel compté) ;
et l'acceptation qu'une rafale prolongée au-delà de deux heures se perde au
journal plutôt qu'à l'écran. Les 60 par minute restent partagés par toute
l'organisation MailPulse : deux grandes écoles sur la même organisation se
ralentissent l'une l'autre. Commencer par une petite instance reste le plus sûr.

**Avant toute bascule, vérifier dans MailPulse** l'organisation à laquelle la clé
de l'école appartient, son offre et le quota du mois restant. Une organisation
en offre gratuite atteint 5 000 e-mails en une campagne de relances d'une grande
école.

## Ce qu'il faut poser sur chaque instance

Prérequis : l'instance parle déjà à MailPulse. **Une file qui tourne est
recommandée**, et exigée pour les grandes instances : `QUEUE_CONNECTION` à
`database` ou `redis` et un worker (`php artisan queue:work`) lancé en permanence
(cron ou superviseur). Sans worker, les courriels différés s'accumulent dans
`jobs` et ne partent jamais.

### Et sous `QUEUE_CONNECTION=sync` ?

La case **reste activable** sous `sync`, et c'est voulu. Une petite instance
(`presentation`, les instances en test) envoie quelques courriels par heure, loin
du plafond de 30 par minute ; lui refuser MailPulse faute de worker la laisserait
sur un SMTP qu'on cherche justement à quitter. Ce qu'elle perd est borné et
visible :

- un courriel refusé pour débit ou panne passagère **n'est pas différé** : il est
  refusé comme toute panne (exception `TransportException`, ligne
  `refus passager et aucune file pour différer` au journal) ;
- l'écran des paramètres l'annonce **à côté de la case**, tant que l'instance
  est en `sync` : « Les e-mails ne pourront pas être différés si MailPulse est
  saturé : les tâches en arrière-plan ne tournent pas sur cette instance. »

Une instance Élite, elle, ne bascule qu'avec une file et un worker (voir plus haut).

| clé | où | valeur |
|---|---|---|
| `QUEUE_CONNECTION` | `.env` | `database` (ou `redis`), **pas `sync`** ; et un worker actif |
| `MAILPULSE_API_KEY` | `.env`, ou réglage `mailpulse_api_key` | clé API v1 de l'organisation MailPulse (déjà posée là où les notifications parents partent) |
| `mailpulse_enabled` | réglage, ou `.env` `MAILPULSE_ENABLED` | `1` — **à `0`, plus aucun courriel ne part**, y compris les liens de confirmation |
| `mailpulse_courriels_enabled` | réglage, écran `/esbtp/settings` onglet MailPulse | coché — **c'est la bascule** |
| `MAIL_MAILER` | `.env` | laisser `smtp` (repli quand la case est décochée) ; `mailpulse` force MailPulse quelle que soit la case |
| `MAIL_FROM_ADDRESS` | `.env` | une adresse d'un domaine vérifié chez MailPulse (`noreply@klassci.com`), utilisée si `mailpulse_sender_email` est vide |
| `mailpulse_sender_email` | réglage (facultatif) | prime sur `MAIL_FROM_ADDRESS` |
| `mailpulse_sender_name` | réglage (facultatif) | nom affiché, `KLASSCI` par défaut ; `MAIL_FROM_NAME` n'est pas lu |
| `MAILPULSE_MAIL_PER_MINUTE` | `.env` (facultatif) | plafond par minute du mailer, `30` par défaut, au mieux |

Les réglages s'enregistrent depuis l'écran et prennent effet tout de suite.
Seul un changement du `.env` demande `php artisan config:clear` (ou
`klassci cache:clear <instance>`).

### Instances

| instance | clé MailPulse | à faire |
|---|---|---|
| `esbtp-abidjan` | posée (relevé du 1er octobre, `activation-notifications-abidjan-yakro.md`) | vérifier `QUEUE_CONNECTION` et le worker, puis offre et quota ; basculer après une petite instance |
| `esbtp-yakro` | posée (même relevé) | idem |
| `presentation`, `ephrata`, `hetec`, `rostan`, `usat`, `ucao-benin` | **non vérifiée** | vérifier la clé avant de cocher la case : `GET /api/cli/rendez-vous/diagnostic` rend la ligne `messagerie` à `ok: true` (« Envoi des convocations par MailPulse actif » : MailPulse activé et clé présente) ; puis `QUEUE_CONNECTION` et le worker |

Basculer une instance sans clé fait échouer **tous** ses courriels : vérifier
la clé, la file, l'offre et le quota d'abord, basculer ensuite.

## Contrôle après bascule

1. Depuis l'écran « Aide », demander un lien de confirmation d'adresse vers une
   boîte interne : il doit arriver, logo affiché.
2. `storage/logs/laravel.log` : aucune ligne `Courriel refusé par MailPulse`.
   Une ligne `HTML trop volumineux` désigne un gabarit à alléger.
3. Côté MailPulse, le message apparaît avec `external_tenant_id` = code de
   l'instance et `workflow_event` = `laravel_mail`.
4. Les jours de rafale (appels, relances) : `jobs` ne doit pas grossir sans fin
   (sinon le worker ne tourne pas), et aucune ligne `Courriel différé abandonné`
   au journal. `failed_jobs` les garde sept jours (`queue:prune-failed --hours=168`).

## Ce qui ne passe pas encore

- **L'envoi d'un export PDF par courriel** (`ExportableReportMail`) n'est plus
  proposé : le menu d'export masque « Envoyer par e-mail » quand ce mailer est
  actif, et `ExportRenderer::emailPdf()` le refuse avec un message (422) plutôt
  que d'annoncer un envoi qui finirait en job échoué. Le téléchargement reste la
  voie, tant que MailPulse n'accepte pas de pièce jointe.
- Le plafond de 16 Ko oblige les gros gabarits à partir en texte.
- Le débit de 60 par minute et par organisation (voir plus haut).

Ces limites tiennent à l'API publique de MailPulse, produit distinct : elles
sont des demandes d'évolution à lui adresser (pièces jointes, images intégrées,
HTML hors `metadata`, Reply-To, Cc), pas des contournements à écrire ici.

## Revenir en arrière

Décocher « Envoyer tous les e-mails de l'école par MailPulse » et enregistrer :
les e-mails repartent aussitôt par le serveur de messagerie du `.env`
(`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_ENCRYPTION`). Si le `.env` porte `MAIL_MAILER=mailpulse`, le remettre à
`smtp`, puis `config:clear`.
