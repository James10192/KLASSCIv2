# Relance des annonces — préparation du contrat v3

## Objectif

Permettre une nouvelle tentative **uniquement** après preuve que le dernier essai n'a jamais été présenté à Resend et qu'aucune suppression/bounce ne concerne l'adresse. Les `email_bounced` de l'annonce 2 d'ESBTP Abidjan ne sont pas réessayables.

## Références

- Première soumission : référence v1/legacy ou v2 de la livraison, inchangée.
- Tentative >=2 : `klassci-v3-<tenant>-annonce-<id>-courriel-<id>-essai-<N>`.
- `ReferenceCourrielAnnonce::tentative` et `extraireTentative` préparent ce contrat mais **ne déclenchent aucun envoi**. Ne pas remplacer `mailpulse_request_id` de la livraison historique par la nouvelle référence.
- MailPulse PR #67 n'autorise les nouveaux POST v3 que si le message précédent (même tenant/API key/destinataire) est `FAILED`, sans providerMessageId et avec refus explicitement survenu AVANT tout HTTP fournisseur. Références inconnues, changements de destinataire et rebonds restent interdits.

## Ce qu'il reste à implémenter AVANT activation

1. Dans un checkout Laravel réellement exécutable, lancer `php artisan make:migration create_esbtp_annonce_email_attempts_table` conformément à CLAUDE.md, puis construire une table immuable `id, annonce_id, livraison_id, numero_tentative, reference, previous_reference, etat, actor_id, created_at, provider_message_id`, contrainte unique livraison+numéro et reference, index tenant-local et références au parent.
2. Écrire un service atomique de reservation de tentative, audit, CAS, verrou SQL, check MailPulse read-only, et un job versionné qui conserve la référence v3 de bout en bout. Les webhooks doivent retrouver la tentative exacte puis appliquer les transitions conditionnelles sans toucher un autre essai.
3. Exposer un POST CSRF+throttle+`permission:annonces.email_retry` et une modale premium AJAX no reload sur `annonces.show`. L'ancien diagnostic lecture seule de PR #1757 peut rester visible sans ce droit, la réémission non.
4. Rendre le quota, la taille de lot et les règles de reprise configurables par établissement ; prévoir une feature flag désactivée par défaut et une procédure explicite d'activation après tests.
5. Tests de concurrence (deux utilisateurs simultanés), timeout HTTP après soumission, crash avant et après frontière, message déjà livré, bounce/complaint/suppression, mauvaise API key/tenant, fraîcheur provider, requêtes répétées, 429/quota, rollback DB, réponses au callback antérieur.

## Déploiement / ordre

1. MailPulse #67 vert et déployé.
2. Migration KLASSCI générée, implémentée, tests en base MySQL/MariaDB verts, droits synchronisés via `php bin/deploy/fix_permissions.php`.
3. Recette présentation sans envoi réel, puis un seul test fournisseur explicitement autorisé.
4. Sync instance via fast-forward et migrations par instance après sauvegarde.

Il est interdit d'utiliser `queue:retry all`, de changer `email_bounced` en `pending`, de réutiliser l'ancienne clé sur un nouveau POST ou d'exécuter un envoi au seul motif qu'un GET retourne 404.