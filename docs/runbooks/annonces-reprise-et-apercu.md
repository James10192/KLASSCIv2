# Annonces : prévisualisation et reprise des courriels

## Message et rendu partagé

Le gabarit `esbtp.emails.annonce` réutilise le layout officiel de tous les courriels KLASSCI : logo, couleurs d'établissement, mise en page responsive, bouton et pied de page. Le service `CorpsCourrielAnnonce` rend le même HTML pour l'aperçu isolé `GET /esbtp/annonces/{annonce}/courriel/apercu` et `metadata.email_html` envoyé au client MailPulse. Le texte saisi est échappé, jamais injecté comme du HTML. L'HTML dépassant le plafond des métadonnées est refusé explicitement (pas de dégradation silencieuse vers un simple texte brut).

Dans MailPulse, la prévisualisation HTML existe déjà via `EmailHtmlPreview` pour les messages dont `metadata.email_html` est présent. Les messages historiques envoyés en texte brut ne gagnent pas rétroactivement ce gabarit.

## Statuts et contrôle des reprises

* `pending` : message pas encore soumis ; bouton « Reprendre les non soumis », file bornée par `MAILPULSE_ANNOUNCEMENT_RESUME_BATCH` (40 par défaut, maximum technique 100).
* `failed` avec `error_code=queue_exhausted`, sans `mailpulse_message_id` ni `submitted_at` : bouton séparé « Réessayer les échecs techniques vérifiés », qui récupère la même clé d'idempotence. Il ne reprend aucune autre erreur fournisseur.
* `processing`, `retrying`, `submitted`, `awaiting_dispatch`, `delayed`, `delivered`, `failed` pour bounce/complaint/suppression : **ne jamais relancer à l'aveugle**. Vérifier MailPulse et ses confirmations de remise ; seuls les états non soumis sûrs sont replanifiés.
* La confirmation « tous délivrés » apparaît uniquement si le nombre de confirmations couvre toutes les adresses enregistrées. Des échecs définitifs demandent un traitement séparé, pas un envoi en boucle.

Le contrôleur garde la permission effective `annonces.edit` (avec alias historique), une vérification de publication, de non-expiration et un throttle sur la reprise. Le worker effectue une transition atomique `pending -> processing`, même si des jobs ont été planifiés deux fois. La référence d'envoi MailPulse reste stable par annonce et adresse.

## UX et performance

La fiche annonce affiche l'aperçu du courriel dans un iframe sandbox, des KPI rafraîchis par requêtes AJAX de lecture et une liste de destinataires chargée par pages de 25 via IntersectionObserver. Un bouton « Charger d'autres destinataires » reste disponible pour l'accessibilité et sans IntersectionObserver. Le filtre est fourni par le sélecteur premium KLASSCI. Le rafraîchissement des KPI s'arrête quand tous les états non terminaux sont épuisés, mais n'en déduit pas que tous les e-mails ont été délivrés. L'intervalle se configure avec `MAILPULSE_ANNOUNCEMENT_STATUS_POLL_MS` (30000 par défaut).

## Déploiement et recette

1. Ne pas réexpédier l'annonce historique du 29 octobre par une procédure globale. Vérifier qu'elle a sa migration et que son registre contient les bonnes références.
2. Déployer le code et les vues, vider le cache des vues et vérifier la configuration MailPulse/worker/cron. Ce lot ne crée aucune migration.
3. Sur un brouillon de test, contrôler la prévisualisation KLASSCI et la même prévisualisation dans le détail MailPulse après soumission à une adresse test autorisée ; valider les couleurs et le logo de l'établissement.
4. Avec un envoi test, vérifier les retours `submitted`, `delivery_delayed`, `delivered`, `failed`. Contrôler les statuts de chaque destinataire, la pagination et l'infinite scroll sans reload.
5. Exécuter une reprise en double-clic/test concurrent : une seule soumission réelle doit être acceptée. Vérifier que `delivered` et `delayed` restent intacts.
6. Contrôler séparément les refus SMTP et les avis Resend, sans affirmer que la soumission MailPulse équivaut à une livraison.

La fusion Git, le déploiement et les migrations de chaque instance sont trois étapes différentes. Aucun envoi de production ne doit être lancé depuis une suite de tests.
