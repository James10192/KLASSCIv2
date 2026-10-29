# Incidents techniques des instances vers adminKLASSCI

## Diagnostic réel ESBTP Abidjan (29 octobre 2026)

L'erreur initiale GET 500 correspondait au middleware Spatie Permission indisponible avec l'ancien dossier vendor. Un composer install sur l'instance a aligné Laravel et Spatie. L'erreur suivante, pendant POST /esbtp/annonces, a été précisément identifiée : SQLSTATE 42S22, errno 1054, colonne email_notification_enabled absente. La migration du 29 octobre n'avait pas été exécutée.

Après sauvegarde de la base du seul établissement concerné :

    cd ~/public_html/esbtp-abidjan
    php artisan migrate:status
    php artisan migrate --path=database/migrations/2026_10_29_110000_add_email_delivery_to_esbtp_annonces.php --force
    php artisan optimize:clear

Vérifier d'abord un brouillon sans envoi, puis les files MailPulse et un destinataire de test ; aucun essai de diffusion générale.

## Déploiement progressif de la télémétrie

1. Déployer adminKLASSCI avec sa migration tenant_error_events et son endpoint POST /api/v1/support/incidents, sur la branche principale du Master.
2. Émettre un nouvel identifiant Care pour l'instance avec les portées support:create,support:read,support:update,telemetry:send, puis remplacer MASTER_SUPPORT_TOKEN sur cette instance sans le divulguer.
3. Sur cette instance seulement, définir MASTER_TELEMETRY_ENABLED=true et vider/reconstruire le cache de configuration. Le défaut false garantit que les anciennes instances ne tentent aucune remontée.
4. Générer uniquement un incident de test non personnel et vérifier la référence dans Surveillance > Incidents techniques ; recontrôler les logs serveur.
5. L'émission est faite après la réponse avec délais stricts, sans SQL, trace, payload, URL paramétrée ni PII. Un Master injoignable n'empêche pas la page de répondre ; les logs privés restent la source de diagnostic. Cela ne fournit pas de livraison garantie : un collecteur durable sera nécessaire si une conservation de 100 % des incidents est exigée.

Ne jamais déclencher automatiquement une migration ou un correctif SQL sur la base d'une erreur navigateur. Voir adminKlassci#142, KLASSCIv2#1710 et #1711.
