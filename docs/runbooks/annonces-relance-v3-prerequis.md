# Relance des annonces — contrat v3 et activation contrôlée

## Position de sécurité

Les 29 `email_bounced` du message n°2 d'ESBTP Abidjan ne sont pas réessayables ; les 1 484 `retrying/local_rate_limit` relèvent de la récupération **sans nouvelle tentative** via PR #1762. Il ne faut jamais mélanger ces deux opérations.

## Architecture implémentée

- Migration Artisan générée dans GitHub Actions (run 41491784776) : `2026_11_02_173048_create_esbtp_annonce_email_attempts_table.php`.
- Table `esbtp_annonce_email_attempts` : identité de tentative v3, lien et snapshot de l'adresse, référence précédente, opérateur, token de requête, issue fournisseur, unicité delivery+attempt et delivery+request_token.
- Table `esbtp_annonce_email_attempt_events` : événements append-only `requested`, `dispatch_boundary` et résultat, sans modification de lignes d'événements existantes.
- `RelanceCourrielsAnnonce` : quota et feature flag d'instance, droit dédié, ID validé, suppression des rebonds, prélecture `GET /messages/by-idempotency-key`, verrou ligne parent, création de l'essai et job transactionnel.
- `EnvoyerTentativeAnnonceCourriel` : nouvelle référence v3 et `retry_of`, frontière irréversible en DB avant HTTP, 429 différé sur même référence, 5xx/timeout en quarantaine, suivi sans rediffusion silencieuse.
- Controller et Blade : `GET .../relance/diagnostic`, `POST .../relance` avec permission `annonces.email_retry`/CSRF/throttle, confirmation utilisateur, modale AJAX no reload.
- Callback signé : compare la référence v3, le MailPulse message_id, le destinataire, la campagne et la tentative avant CAS. Les vieux callbacks ne réécrivent pas le nouvel essai.

## Contrat fournisseur et preuves

MailPulse PR #67 accepte une référence `klassci-v3-<tenant>-annonce-<id>-courriel-<id>-essai-<N>` uniquement si le dernier essai, même API key, établissement et destinataire, est `FAILED`, `providerMessageId=null` et porte un code connu AVANT l'appel Resend (`template_not_configured` ou `channel_not_configured`). Une adresse avec historique `email_bounced`, `email_complained` ou `email_suppressed` reste interdite. `GET 404` n'autorise jamais un POST de secours. Les états `sent`, `submission_unknown`, `delayed`, `delivered` sont exclus.

## Activation (désactivée par défaut)

1. Terminer la revue et vérifier tous les tests/CI de MailPulse PR #67, puis déployer MailPulse en premier.
2. Faire passer KLASSCI dans une vraie queue `database` avec workers et cron. Vérifier `jobs`, `failed_jobs`, throttling et les quotas d'organisation, sans déclencher une campagne réelle.
3. Fusionner uniquement après tests complets et recette visuelle desktop/mobile sur `presentation`. Migrer chaque instance après sauvegarde et sur sa branche FF (ne pas modifier d'autres établissements).
4. Synchroniser permissions par `php bin/deploy/fix_permissions.php` puis attribuer explicitement `annonces.email_retry` à un rôle personnalisé autorisé ; aucun rôle n'en bénéficie par défaut.
5. Ajouter les clés d'établissement `annonces_email_retry_enabled` (booléen false par défaut) et `annonces_email_retry_max_batch` (1..50, défaut 10). Activer la première seulement après test en environnement isolé.
6. Faire un lot simulé avec un ancien email qui a vraiment échoué AVANT tout appel fournisseur. Ne jamais envoyer un vrai message à un destinataire ayant rebondi pendant la recette.

## Vérifications de non-régression requises

- Privilèges 403, CSRF 419, idempotence sur UUID, deux opérateurs concurrents, lot trop grand, tenant voisin, clé historique, bounce/suppression, réponse GET absente ou mal signée.
- Queue sync, transaction rollback après insertion de job, crash avant/après HTTP, 429, 5xx, timeout, webhook re-joué/tardif, réconciliation par référence et désactivation en cours de traitement.
- Tests MySQL 8 et MariaDB 10.11, tests de permissions effectives, recueil des erreurs à l'écran, responsive et aucun rechargement.

## Références

- Laravel Queues 12 : https://laravel.com/docs/12.x/queues
- Resend suppressions et bounces : https://resend.com/changelog/email-suppression-visibility
- MailPulse v3 : https://github.com/James10192/mailpulse/pull/67
- KLASSCI anomalies : https://github.com/James10192/KLASSCIv2/issues/1758