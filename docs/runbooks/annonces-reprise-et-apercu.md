# Annonces : aperçu et reprise contrôlée

Le gabarit esbtp.emails.annonce hérite du layout officiel des courriels KLASSCI. La même source HTML sert à la prévisualisation et à la transmission à MailPulse. Le contenu libre est échappé par Blade.

Le bouton de reprise ne planifie que les lignes pending, jamais les lignes déjà soumises, en attente de rapprochement, retardées, délivrées ou échouées. Les échecs doivent être rapprochés du fournisseur et investigués avant réémission ; aucune relance à l'aveugle.

Vérifier les workers et le cron, vider le cache des vues après déploiement. Ne jamais déclarer une livraison sur la seule acceptation HTTP.