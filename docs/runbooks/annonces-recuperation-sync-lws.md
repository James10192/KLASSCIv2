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
5. Simulation : php artisan annonces:recuperer-file --annonce=2. Le nombre **strictement récupérable** applique désormais la correspondance v2 complète et l'exclusion des antécédents locaux de rebond/plainte/suppression ; ne pas déduire ce nombre du seul préfixe SQL.
6. Avant `--execute`, prouver que la connexion `database` utilise **la même base** que les livraisons, avec `after_commit=false`. Un dispatch différé après commit détruirait l'atomicité statut/job.
7. Valider le statut MailPulse/Resend, la liste de suppression fournisseur, l'état des DNS SPF/DKIM/DMARC et la capacité SMTP autorisée. La protection locale ne voit pas les suppressions externes non synchronisées.

## Configurer la queue persistante et le worker

Dans l'environnement de cette seule instance (et après sauvegarde) : QUEUE_CONNECTION=database, TENANT_CODE=esbtp-abidjan, MAILPULSE_MAIL_PER_MINUTE=30 (ou plafond autorisé).
Puis : php artisan optimize:clear ; vérifier la configuration avec tinker et Schema::hasTable("jobs").
Si jobs n'existe pas, vérifier les migrations existantes et sauvegarder avant toute migration. Ne jamais migrer à l'aveugle.

Sur LWS/cPanel, tester d'abord un cron borné et **non chevauchant**. LWS déconseille explicitement l'exécution toutes les minutes sur mutualisé : choisir 5 minutes par défaut si l'offre et la latence opérationnelle l'imposent, puis mesurer. Vérifier les chemins réels de PHP/CLI, la disponibilité de `flock`, l'extension `pcntl` (timeouts), les limites de processus et de cron. Ne pas supposer ces capacités disponibles. Exemple à adapter après validation :

    */5 * * * * cd /home/COMPTE/public_html/esbtp-abidjan && /usr/bin/flock -n /home/COMPTE/annonces-queue.lock /CHEMIN/PHP artisan queue:work database --queue=annonces --stop-when-empty --max-jobs=15 --max-time=45 --timeout=35 --sleep=1 --tries=12 >> /home/COMPTE/annonces-queue.log 2>&1

Utiliser les chemins réels et vérifier que l'hébergeur autorise ce cron. Le worker est distinct du planificateur schedule:run ; default et les autres queues demandent leurs propres consommateurs.

## Reprise en petit lot, seulement après les contrôles

    php artisan annonces:recuperer-file --annonce=2 --tenant=esbtp-abidjan --limit=10 --execute

La commande ne fait aucun HTTP MailPulse. Elle ne récupère que retrying + local_rate_limit, avec absence de preuve d'envoi, et exige une clé v2 valide, déjà enregistrée et propre à l'établissement ; les références legacy ou absentes restent en quarantaine manuelle. Dans une même transaction database, elle marque pending et enfile EnvoyerAnnonceCourriel sur annonces. Réexécuter ne réinsère pas les mêmes lignes.

Contrôler les événements MailPulse et Resend, le statut des lignes, les logs du worker et les quotas. Répéter sur petits lots si les preuves sont conformes. Stopper si refus, bounce, 429 ou quota.

## Ne pas mélanger les 29 échecs avec les 1 484 retrying

Les états failed, awaiting_dispatch, submitted, delayed et delivered ne sont jamais admissibles à cette récupération. L'issue #1758 traite les véritables nouvelles tentatives : essais immuables, nouvelle référence par essai et contrat MailPulse, permission dédiée, refus des bounces et suppressions, audit et tests. PR #1757 = diagnostic AJAX, sans remise en file.

## Anti-régression

Le contrôleur refuse la création de nouvelles campagnes e-mail sur queue sync, et le job fanout bloque avant d'insérer le registre en sync.

## Vérifications complémentaires avant le premier vrai lot

- Faire tourner **un job de test sans courriel** dans un environnement isolé avec le même utilisateur PHP/cron, puis constater son traitement et l'absence de chevauchement. La seule présence de `jobs` ne prouve pas que le worker fonctionne.
- Confirmer `queue.connections.database.retry_after > timeout du worker` ; les 90 s actuelles doivent rester supérieures au timeout choisi. Confirmer les connexions SQL et le cache des workers.
- Relever `jobs` par file (`annonces`, `default`, `high`, `low`, etc.), `failed_jobs`, espace disque et logs. Un worker `--queue=annonces` ne traite aucune autre file.
- Les lots doivent utiliser la cadence `services.mailpulse.mail_per_minute`, **pas** une valeur fixe. Les envois sortants doivent rester arrêtés si le quota global ou la suppression fournisseur est inconnu.
- L'exclusion locale des adresses rebondies concerne les **annonces de cette instance seulement**. Le chantier #1758 doit ajouter un registre global d'exclusions et de tentatives, compatible avec le fournisseur et les autres modules, avant toute relance d'un échec.
- Ne jamais faire `queue:retry all` ou `queue:flush` comme moyen de récupérer cet incident : ces commandes ignorent les garanties métier d'idempotence.

Références officielles : https://laravel.com/docs/12.x/queues ; https://aide.lws.fr/base/cPanel/Fonctions-supplementaires/programmer-une-tache-Cron-dans-cPanel ; https://aide.lws.fr/base/Hebergement-web-mutualise/Outils-web/Comment-creer-une-tache-cron-LWS-Panel ; https://resend.com/changelog/suppression-list-support
