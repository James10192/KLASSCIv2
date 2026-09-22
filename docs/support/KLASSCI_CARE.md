# KLASSCI Care — côté instance (KLASSCIv2)

> Le blueprint complet vit dans le dépôt **adminKlassci** :
> `docs/support/KLASSCI_CARE_BLUEPRINT.md`. Le Master est la source de vérité des demandes de
> support ; cette page ne résume que ce qui touche ce dépôt. Rien n'est encore implémenté.

## Ce que l'instance porte

- Le point d'entrée « Aide / Signaler » : un élément du menu utilisateur de la navbar, dans la
  feuille « plus » du shell mobile, et sur la page 500. Pas de troisième bulle flottante : le
  chatbot occupe déjà le coin bas-droit (`public/css/chatbot-widget.css:41-50`).
- Le widget : Alpine + JS simple dans `public/js/support/`, chargé à la demande, sans étape de
  build (le dépôt n'en a pas en pratique).
- La collecte de contexte, sur liste blanche :
  - le module, lu dans le middleware `permission:module.*.access` de la route (mécanisme de
    `PorteDeRoute`) ;
  - les identifiants d'entité, lus dans les paramètres de route et dans les attributs
    `data-support-context` ;
  - l'année courante ;
  - le navigateur.

  Jamais de HTML, de valeurs de champ, de cookies ni d'en-têtes.
- La capture d'écran : masquage des champs avant l'aperçu, annotation, envoi seulement si
  l'utilisateur le confirme.
- Le client serveur vers le Master (`app/Services/Care/ClientMasterSupport.php`, à créer) :
  - jeton `MASTER_SUPPORT_TOKEN`, dédié et à portée limitée ; **pas** `MASTER_API_TOKEN` ;
  - connexion en 2 s, réponse en 5 s ;
  - échec mis en cache (coupe-circuit) ;
  - boîte d'envoi locale `support_outbox` rejouée par le scheduler avec la même clé
    d'idempotence.
- L'identifiant de requête `X-Request-ID` : middleware global placé avant `LogRequests`, avec
  `Log::withContext`, affiché sur la page 500.
- La télémétrie d'erreurs :
  - empreinte calculée dans `Handler::register()->reportable()` (vide aujourd'hui) ;
  - agrégats envoyés par lot chaque minute, jamais pendant la requête.
- La page « Mes demandes » (`/support/demandes`) : projection lisible par le client, fournie
  par le Master.
- Un point de diagnostic signé (HMAC, modèle `ParentChatbotInboundSignature`), en lecture
  seule, qui rend des faits minimaux :
  - permissions requises par la route, et celles que l'utilisateur possède ;
  - cohérence de configuration.

## Contraintes propres à ce dépôt, relevées pendant l'audit

- Laravel 9.52 et PHPUnit 9 : aucun code PHP partagé avec le Master (Laravel 12). Le seul
  contrat est l'API `/api/v1/support`, versionnée.
- Les nouveaux dossiers de tests doivent être ajoutés à la liste de
  `.github/workflows/hygiene-commits.yml`, sinon la CI ne les exécute pas.
- Une nouvelle permission (`support.tickets.view_school`, …) n'atteint les rôles déjà en
  production que via `PermissionSyncService::newFeaturePermissions()`.
- Défaut rencontré en passant, hors périmètre :
  - `GroupCacheInvalidator` lit `services.master.url` et `.token`, alors que les clés réelles
    sont `api_url` et `api_token` ;
  - une fois `config:cache` actif, l'invalidation du cache groupe est donc probablement
    silencieusement inopérante.
