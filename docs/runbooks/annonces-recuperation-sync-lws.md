# Récupération des campagnes d'annonce après queue sync — LWS

## Diagnostic du 2 novembre 2026 — ESBTP Abidjan / annonce n°2

- queue.default=sync ; aucun artisan queue:work actif.
- 31 delivered, 29 failed, 1 484 retrying datés du 31 octobre.
- En sync, les jobs delayed/released ne forment pas une vraie file persistante. Passer à database ne réinsère pas les tâches perdues.

## Préflight lecture seule avant reprise

1. Faire une sauvegarde de la base et vérifier la présence des tables jobs et failed_jobs.
2. Vérifier le tenant réel, le plafond MailPulse, les limites Resend et la santé du planificateur.
3. Grouper les livraisons de l'annonce par status et error_code en lecture seule :

    php artisan tinker --execute='dump(\Illuminate\Support\Facades\DB::table("esbtp_annonce_email_deliveries")->where("annonce_id",2)->select("status","error_code")->selectRaw("COUNT(*) AS total")->groupBy("status","error_code")->get());'

4. Contrôler que les lignes à récupérer sont retrying + local_rate_limit, sans submitted_at, mailpulse_message_id, provider_failed_at ni provider_delivered_at. **La référence idempotente v2 déjà enregistrée doit appartenir au tenant et à la livraison** ; les clés anciennes/absentes sont refusées.
5. Simulation : php artisan annonces:recuperer-file --annonce=2

## Configurer la queue persistante et le worker

Dans l'environnement de cette seule instance (et après sauvegarde) : QUEUE_CONNECTION=database, TENANT_CODE=esbtp-abidjan, MAILPULSE_MAIL_PER_MINUTE=30 (ou plafond autorisé).
Puis : php artisan optimize:clear ; vérifier la configuration avec tinker et Schema::hasTable("jobs").
Si jobs n'existe pas, vérifier les migrations existantes et sauvegarder avant toute migration. Ne jamais migrer à l'aveugle.

Sur LWS/cPanel, prévoir un cron de worker borné toutes les minutes, après vérification des chemins PHP et flock, avec un verrou non bloquant :

    * * * * * cd /home/COMPTE/public_html/esbtp-abidjan && /usr/bin/flock -n /home/COMPTE/annonces-queue.lock /CHEMIN/PHP artisan queue:work database --queue=annonces --stop-when-empty --max-jobs=15 --max-time=45 --timeout=35 --sleep=1 --tries=12 >> /home/COMPTE/annonces-queue.log 2>&1

Utiliser les chemins réels et vérifier que l'hébergeur autorise ce cron. Le worker est distinct du planificateur schedule:run ; default et les autres queues demandent leurs propres consommateurs.

## Reprise en petit lot, seulement après les contrôles

    php artisan annonces:recuperer-file --annonce=2 --tenant=esbtp-abidjan --limit=10 --execute

La commande ne fait aucun HTTP MailPulse. Elle ne récupère que retrying + local_rate_limit, avec absence de preuve d'envoi, et exige une clé v2 valide, déjà enregistrée et propre à l'établissement ; les références legacy ou absentes restent en quarantaine manuelle. Dans une même transaction database, elle marque pending et enfile EnvoyerAnnonceCourriel sur annonces. Réexécuter ne réinsère pas les mêmes lignes.

Contrôler les événements MailPulse et Resend, le statut des lignes, les logs du worker et les quotas. Répéter sur petits lots si les preuves sont conformes. Stopper si refus, bounce, 429 ou quota.

## Ne pas mélanger les 29 échecs avec les 1 484 retrying

Les états failed, awaiting_dispatch, submitted, delayed et delivered ne sont jamais admissibles à cette récupération. L'issue #1758 traite les véritables nouvelles tentatives : essais immuables, nouvelle référence par essai et contrat MailPulse, permission dédiée, refus des bounces et suppressions, audit et tests. PR #1757 = diagnostic AJAX, sans remise en file.

## Anti-régression

Le contrôleur refuse la création de nouvelles campagnes e-mail sur queue sync, et le job fanout bloque avant d'insérer le registre en sync.