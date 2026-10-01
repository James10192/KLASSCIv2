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
| À, Cc, Cci | un message MailPulse par adresse, chacun avec sa clé d'idempotence |
| Sujet | `metadata.subject` |
| HTML | commentaires et indentation retirés (les sauts de ligne restent, un bloc `pre-line` en dépend), puis `metadata.email_html` |
| HTML au-delà du plafond | **la version texte part seule**, avertissement au journal |
| Texte | `content.text` ; tiré du HTML (liens conservés en clair) s'il manque |
| Expéditeur | réglage `mailpulse_sender_email` s'il est posé, sinon `MAIL_FROM_ADDRESS` ; nom = nom du courriel, sinon `mailpulse_sender_name`. MailPulse ne retient l'adresse que si son domaine est vérifié chez lui, sinon il prend l'expéditeur par défaut de l'organisation |
| Image intégrée (`cid:`) | retirée du HTML, avertissement au journal. Le gabarit commun `esbtp.emails.layout` passe déjà par l'URL du logo quand ce mailer est actif |
| Pièce jointe | **refus** : exception, rien ne part. Un « ci-joint votre export » sans l'export serait un succès mensonger |
| Accepté mais remise à confirmer (`pending`, `pending_reconciliation`) | part sans exception, avertissement au journal (`remise à confirmer`) : MailPulse rejoue ou tranche |
| Reply-To, en-têtes personnalisés | non transmis (Reply-To journalisé) |

Tout refus de MailPulse (désactivé, clé absente, injoignable, 4xx/5xx, envoi
refusé par le fournisseur) **lève une exception** et s'écrit au journal
(`Courriel refusé par MailPulse`, statut, code HTTP, request id). Les écrans qui
disent « le courriel n'a pas pu partir » continuent donc de le dire.

## Ce qu'il faut poser sur chaque instance

Prérequis : l'instance parle déjà à MailPulse.

| clé | où | valeur |
|---|---|---|
| `MAILPULSE_API_KEY` | `.env`, ou réglage `mailpulse_api_key` | clé API v1 de l'organisation MailPulse (déjà posée là où les notifications parents partent) |
| `mailpulse_enabled` | réglage, ou `.env` `MAILPULSE_ENABLED` | `1` — **à `0`, plus aucun courriel ne part**, y compris les liens de confirmation |
| `MAIL_MAILER` | `.env` | `mailpulse` |
| `MAIL_FROM_ADDRESS` | `.env` | une adresse d'un domaine vérifié chez MailPulse (`noreply@klassci.com`) |
| `mailpulse_sender_email` | réglage (facultatif) | prime sur `MAIL_FROM_ADDRESS` |

Puis `php artisan config:clear` (ou `klassci cache:clear <instance>`).

### Instances

| instance | clé MailPulse | à faire |
|---|---|---|
| `esbtp-abidjan` | posée (relevé du 1er octobre, `activation-notifications-abidjan-yakro.md`) | `MAIL_MAILER`, `MAIL_FROM_ADDRESS` |
| `esbtp-yakro` | posée (même relevé) | `MAIL_MAILER`, `MAIL_FROM_ADDRESS` |
| `presentation`, `ephrata`, `hetec`, `rostan`, `usat`, `ucao-benin` | **non vérifiée** | vérifier la clé avant de basculer `MAIL_MAILER` : `GET /api/cli/rendez-vous/diagnostic` rend la ligne `messagerie` à `ok: true` (« Envoi des convocations par MailPulse actif » : MailPulse activé et clé présente) |

Basculer une instance sans clé fait échouer **tous** ses courriels : vérifier
d'abord, basculer ensuite.

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

Ces deux limites tiennent à l'API publique de MailPulse, produit distinct : elles
sont des demandes d'évolution à lui adresser (pièces jointes, images intégrées,
HTML hors `metadata`, Reply-To, Cc), pas des contournements à écrire ici.

## Revenir en arrière

`MAIL_MAILER=smtp` et les anciennes variables `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, puis `config:clear`.
