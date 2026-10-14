# KLASSCI — Intents MailPulse persistants (cPanel/LWS)

## Objectif

Une mutation métier (paiement, inscription, absence, publication académique)
enregistre une **intention chiffrée** dans MySQL **avant** toute dépendance à
un worker ou au fournisseur MailPulse. Le worker reste une voie rapide ; la
commande `mailpulse:drain-workflow-intents` reprend ce qui n'a pas pu être pris
en charge. Un intent traité signifie **remis aux sous-workflows** : ce n'est
pas une preuve de livraison email/WhatsApp/SMS. La table
`parent_notification_logs` mesure et rapproche chaque envoi par canal.

## Activer par établissement, sans perte de données

1. Sauvegarder la base puis exécuter les migrations Laravel (nouvelle table
   `mailpulse_workflow_intents`). Vérifier que les index sont créés.
2. Conserver `MAILPULSE_WORKFLOW_INTENTS_OUTBOX_ENABLED=false` tant que
   `schedule:run` n'est pas réellement exécuté **chaque minute** sur l'instance.
   Sur cPanel, une tâche Cron exécute le PHP CLI compatible avec l'application :
   `cd /home/<compte>/<application> && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1`
   Adapter le chemin PHP et application **après vérification** des chemins
   propres à l'hébergement ; ne jamais copier ces placeholders en production.
3. Confirmer que `php artisan mailpulse:drain-workflow-intents --limit=5`
   s'exécute (aucune sortie d'erreur) et que les tables peuvent être lues.
4. Activer progressivement
   `MAILPULSE_WORKFLOW_INTENTS_OUTBOX_ENABLED=true` **uniquement** avec
   `MAILPULSE_REAL_WORKFLOWS_ENABLED=true` sur l'établissement pilote.
   Régénérer le cache de configuration selon la méthode du déploiement de
   l'instance. Vérifier les permissions des répertoires de logs.
5. Déclencher un workflow de test autorisé après sauvegarde et vérifier
   `queued → processing → processed`, puis les statuts des
   `parent_notification_logs`. Tester aussi un rollback et l'indisponibilité
   de la file. Le cron doit récupérer la ligne même si la queue est arrêtée.

## Garanties et limites

- Table tenant-localisée (la base de **chaque instance** KLASSCI).
- Insertion SQL dans la transaction métier : un rollback supprime aussi l'intent.
- `payload_ciphertext` chiffré via la clé `APP_KEY` de l'instance.
- Claim atomique empêchant un worker et le cron de consommer simultanément
  la même ligne. Une lease expirée (crash/kill -9) permet la reprise.
- Jusqu'à 8 tentatives de traitement, backoff croissant et `failed` terminal ;
  aucun traitement éternel silencieux. `last_error` garde la classe
  d'exception, pas les données personnelles.
- Les doublons après crash post-envoi restent protégés par l'idempotence
  **par parent/canal** existante dans l'outbox des notifications.
- Un état `processed` **ne signifie pas livré**. Les suppressions métier,
  refus de consentement, publication ou statut sont tracés en aval.
- Si MySQL est inaccessible au moment de l'insertion, l'opération applicative
  n'émet pas un appel fournisseur non traçable ; une alerte critique va aux
  logs. Cette limite reste à exposer au Control Center d'administration.
- Les statuts `failed` et `retry` nécessitent un tableau de bord opérateur,
  alertes et runbook de reprise avant une campagne à grande échelle.
- Le mode est **désactivé par défaut**, aucun envoi réel ni migration n'est
  exécuté par la fusion GitHub seule.

### Hébergement cPanel sans worker permanent

Lorsque `QUEUE_CONNECTION=sync`, le web **n'exécute pas** l'appel réseau
MailPulse : l'intention est seulement écrite dans MySQL et la commande cron
la traite au prochain passage. Avec une vraie queue asynchrone, le job accélère
la prise en charge, mais n'est pas nécessaire à la récupération. Un cron qui
n'est pas lancé est donc bloquant pour la diffusion : surveiller son pouls.
