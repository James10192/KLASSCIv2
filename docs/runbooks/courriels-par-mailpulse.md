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

Il ne s'active que par `MAIL_MAILER=mailpulse`. Tant qu'une instance reste en
`MAIL_MAILER=smtp`, rien ne bouge pour elle.

## Ce que MailPulse transporte, et ce que le mailer en fait

L'API publique n'accepte qu'**un destinataire**, **un texte**, et un HTML rangé
dans `metadata.email_html` ; l'objet `metadata` entier est plafonné à
**16 384 octets** de JSON. Le mailer s'adapte côté KLASSCI :

| élément du courriel | conduite |
|---|---|
| À, Cc, Cci | un message MailPulse par adresse |
| Clé d'idempotence | tirée du destinataire et du contenu, rangée dans l'heure (`MailPulseTransport::cleIdempotence()`) : une reprise de file dans l'heure ne renvoie pas le courriel. Deux limites : deux courriels **identiques** au même destinataire dans la même heure n'en font qu'un, et une reprise qui franchit l'heure peut doubler |
| Sujet | `metadata.subject` |
| HTML | commentaires et indentation retirés, sauts de ligne gardés, puis `metadata.email_html` |
| HTML au-delà du plafond | **la version texte part seule**, avertissement au journal |
| Texte | `content.text` ; tiré du HTML (liens conservés en clair) s'il manque |
| Expéditeur | réglage `mailpulse_sender_email` s'il est posé, sinon `MAIL_FROM_ADDRESS` ; nom = réglage `mailpulse_sender_name` (défaut `KLASSCI`), sinon nom du courriel. MailPulse ne retient l'adresse que si son domaine est vérifié chez lui, sinon il prend l'expéditeur par défaut de l'organisation |
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

Ce que fait le mailer pour tenir le débit (`App\Mail\Transport\CadenceMailPulse`) :

- il compte ses propres envois par minute (`MAILPULSE_MAIL_PER_MINUTE`, **50** par
  défaut, sous les 60 pour laisser la marge aux autres flux) ;
- **en console** (worker de file, planificateur, commande), au-delà il **attend**
  la minute suivante : une rafale s'étale ; un `429` de débit est réessayé une
  fois après 60 s (un `429` de **quota** ne l'est pas : il ne passera pas avant le
  mois suivant, statut `quota_exceeded`) ;
- **dans une requête web**, il n'attend pas (le temps d'exécution est borné) : au-delà
  du plafond, ou sur un `429`, le courriel est **refusé et journalisé**, et
  l'appelant le compte comme un échec.

Ce compteur ne voit que son instance : il ne protège pas d'un dépassement
cumulé entre écoles d'une même organisation, ni des autres flux MailPulse.

**Ce qui reste exposé.** Deux flux déclenchés depuis un écran envoient en rafale
dans la requête :

- « Exécuter les relances en attente » (`ESBTPComptabiliteRelanceController::executerRelances()`
  → `NotificationService::executerRelancesEnAttente()`) : au-delà de 50 relances par
  minute, les suivantes sont marquées `echec` — **visibles et relançables**, pas
  perdues en silence, mais non parties ;
- les avis aux parents émis pendant une validation groupée.

Tant que ces deux flux ne passent pas par la file (worker actif sur l'instance),
**ne pas basculer les instances Élite (`esbtp-abidjan`, `esbtp-yakro`, plus de
2 000 inscriptions)**. Les petites instances peuvent basculer d'abord.

**Avant toute bascule, vérifier dans MailPulse** l'organisation à laquelle la clé
de l'école appartient, son offre et le quota du mois restant. Une organisation
en offre gratuite atteint 5 000 e-mails en une campagne de relances d'une grande
école.

## Ce qu'il faut poser sur chaque instance

Prérequis : l'instance parle déjà à MailPulse.

| clé | où | valeur |
|---|---|---|
| `MAILPULSE_API_KEY` | `.env`, ou réglage `mailpulse_api_key` | clé API v1 de l'organisation MailPulse (déjà posée là où les notifications parents partent) |
| `mailpulse_enabled` | réglage, ou `.env` `MAILPULSE_ENABLED` | `1` — **à `0`, plus aucun courriel ne part**, y compris les liens de confirmation |
| `MAIL_MAILER` | `.env` | `mailpulse` |
| `MAIL_FROM_ADDRESS` | `.env` | une adresse d'un domaine vérifié chez MailPulse (`noreply@klassci.com`) |
| `mailpulse_sender_email` | réglage (facultatif) | prime sur `MAIL_FROM_ADDRESS` |
| `mailpulse_sender_name` | réglage (facultatif) | nom affiché ; prime sur `MAIL_FROM_NAME` (défaut `KLASSCI`) |
| `MAILPULSE_MAIL_PER_MINUTE` | `.env` (facultatif) | plafond par minute du mailer, `50` par défaut |

Puis `php artisan config:clear` (ou `klassci cache:clear <instance>`).

### Instances

| instance | clé MailPulse | à faire |
|---|---|---|
| `esbtp-abidjan` | posée (relevé du 1er octobre, `activation-notifications-abidjan-yakro.md`) | **attendre** : relances et avis parents encore en rafale dans la requête (voir les limites) ; vérifier offre et quota |
| `esbtp-yakro` | posée (même relevé) | **attendre**, même raison |
| `presentation`, `ephrata`, `hetec`, `rostan`, `usat`, `ucao-benin` | **non vérifiée** | vérifier la clé avant de basculer `MAIL_MAILER` : `GET /api/cli/rendez-vous/diagnostic` rend la ligne `messagerie` à `ok: true` (« Envoi des convocations par MailPulse actif » : MailPulse activé et clé présente) |

Basculer une instance sans clé fait échouer **tous** ses courriels : vérifier
la clé, l'offre et le quota d'abord, basculer ensuite.

## Contrôle après bascule

1. Depuis l'écran « Aide », demander un lien de confirmation d'adresse vers une
   boîte interne : il doit arriver, logo affiché.
2. `storage/logs/laravel.log` : aucune ligne `Courriel refusé par MailPulse`.
   Une ligne `HTML trop volumineux` désigne un gabarit à alléger.
3. Côté MailPulse, le message apparaît avec `external_tenant_id` = code de
   l'instance et `workflow_event` = `laravel_mail`.

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

`MAIL_MAILER=smtp` et les anciennes variables `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, puis `config:clear`.
