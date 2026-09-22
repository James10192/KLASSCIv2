# KLASSCI Care — côté instance (KLASSCIv2)

> Le blueprint complet vit dans le dépôt **adminKlassci** :
> `docs/support/KLASSCI_CARE_BLUEPRINT.md`. Le Master est la source de vérité des demandes de
> support ; cette page ne résume que ce qui touche ce dépôt.
>
> **État au 22 septembre 2026 : tranche 1 livrée** sur la branche
> `claude/klassci-care-support-platform-1x9wwy`, non fusionnée. La section « Livré » décrit le
> code en place ; la section « Prévu » ce qui n'existe pas encore.

## Livré (tranche 1)

- **Le point d'entrée « Aide / Signaler un problème »** : le menu du compte (bureau), la feuille
  « plus » du shell mobile, la page 500 et la page « Mes demandes ». Les deux entrées de menu
  viennent d'un seul composant, `<x-support.entrees-menu>`. Pas de bulle flottante : le chatbot
  occupe déjà le coin bas-droit.
- **La fenêtre de signalement** (`<x-support.lanceur>` et `public/js/support/signalement.js`,
  sans étape de build) :
  - deux étapes (catégorie et texte, puis récapitulatif) et un écran de confirmation qui donne
    la référence ;
  - brouillon rangé par utilisateur dans le stockage local, effacé à l'envoi et à la
    déconnexion ;
  - lien direct `?signaler=1&code=…` (page d'erreur, courriel) ;
  - limites de longueur lues dans le bootstrap du Master, avec repli dans `config/support.php`.
- **La collecte de contexte**, sur liste blanche, recalculée côté serveur
  (`App\Domain\Support\Services\ContexteDePage`) :
  - la route et le module, relu depuis le middleware `permission:module.*.access` ;
  - l'élément concerné, déclaré par la page via `data-support-context` ;
  - l'année courante, le navigateur, la taille d'écran, le fuseau ;
  - les codes de suivi `X-Request-ID`.

  Jamais de HTML, de valeurs de champ, de cookies ni d'en-têtes. Le titre de page n'est pas
  transmis.
- **Le client vers le Master** (`app/Services/Care/ClientMasterSupport.php`) :
  - jeton dédié `MASTER_SUPPORT_TOKEN`, **pas** `MASTER_API_TOKEN` ; il n'est jamais rendu dans
    une page ;
  - connexion en 2 s, réponse en 5 s ;
  - coupe-circuit après un échec ; un 401 ou 403 compte comme une indisponibilité et se
    journalise en erreur ;
  - bootstrap (fonctionnalités, limites) mis en cache 5 min sous verrou.
- **La boîte d'envoi** `support_outbox` : si le Master est injoignable, la demande y attend
  (réponse 202) et `support:vider-boite-envoi` la renvoie chaque minute avec la même clé
  d'idempotence. Un refus définitif l'abandonne ; un identifiant d'instance refusé ne consomme
  pas d'essai. « Mes demandes » montre les lignes en attente et celles non transmises.
- **L'identifiant de requête** `X-Request-ID` : middleware `AttribuerIdentifiantRequete`, placé
  avant `LogRequests`, avec `Log::withContext`, affiché sur la page 500.
- **La page « Mes demandes »** (`/support/demandes`, routes dans `routes/support.php`) :
  projection fournie par le Master, dates converties au fuseau de l'instance
  (`DateDuMaster`). La portée « Tout l'établissement » demande la permission
  `support.tickets.view_school`, attribuée à aucun rôle par défaut : l'école la donne à qui
  elle veut.
- **L'interrupteur d'exploitation** `support.widget.enabled` : coupe tout sans attendre le
  Master. Aucun écran ne l'expose ; il se pose par `PUT /api/cli/settings/{key}`.

## Prévu (tranches suivantes)

- Réponse de l'école dans une conversation, et pièces jointes.
- Capture d'écran : masquage des champs avant l'aperçu, annotation, envoi seulement si
  l'utilisateur le confirme.
- Télémétrie d'erreurs : empreinte calculée dans `Handler::register()->reportable()`, agrégats
  envoyés par lot chaque minute, jamais pendant la requête.
- Point de diagnostic signé (HMAC, modèle `ParentChatbotInboundSignature`), en lecture seule :
  permissions requises par la route et celles de l'utilisateur, cohérence de configuration.

## Contraintes propres à ce dépôt

- Laravel 9.52 et PHPUnit 9 : aucun code PHP partagé avec le Master (Laravel 12). Le seul
  contrat est l'API `/api/v1/support`, versionnée.
- Les dossiers de tests `tests/Unit/Care` et `tests/Feature/Care` sont listés dans
  `.github/workflows/hygiene-commits.yml` ; un nouveau dossier doit l'être aussi, sinon la CI ne
  l'exécute pas.
- En local, lancer les deux dossiers **séparément** : `php artisan test` n'exécute que le
  premier chemin passé.
- Défaut rencontré en passant, hors périmètre :
  - `GroupCacheInvalidator` lit `services.master.url` et `.token`, alors que les clés réelles
    sont `api_url` et `api_token` ;
  - une fois `config:cache` actif, l'invalidation du cache groupe est donc probablement
    silencieusement inopérante.
