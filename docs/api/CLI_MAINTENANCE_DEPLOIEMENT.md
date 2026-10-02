# API CLI — déploiement : pull, caches, migrations, composer

Les routes que `klassci pull`, `klassci cache:clear`, `klassci migrate` et
`klassci composer` appellent sur une instance, sans SSH. Toutes exigent un jeton CLI
portant `cli:admin` (sinon 403). Contrôleur : `CLIMaintenanceController`.

## Caches de configuration et de routes

Depuis octobre 2026, ces quatre routes **reconstruisent** le cache de configuration
(`config:cache`) et le cache de routes (`route:cache`) après les avoir vidés.
Avant, elles les vidaient seulement : chaque requête rechargeait une quarantaine de
fichiers de `config/` et réenregistrait près de 1600 routes.

Les quatre routes passent par un seul point, `ReconstructionDesCaches::apresDeploiement()` :

1. **purge** : caches de configuration et de routes, `cache:clear`, `view:clear`,
   `permission:cache-reset`, cache des réglages, OPcache ;
2. **reconstruction** : `config:cache` puis `route:cache`, chacun dans un
   **sous-processus PHP CLI**, jamais par `Artisan::call` dans la requête. Ces deux
   commandes démarrent une application neuve, dont le constructeur remplace `app()`
   et les façades pour la suite de la requête ;
3. le sous-processus écrit dans un fichier temporaire du dossier `bootstrap/cache`
   (variables `APP_CONFIG_CACHE` / `APP_ROUTES_CACHE`). Le fichier est vérifié par
   `php -l`, puis mis en place par un `rename()` atomique et invalidé dans OPcache.
   Une requête concurrente lit l'ancien fichier ou le nouveau, jamais un fichier à
   moitié écrit.

Le PHP CLI est cherché à côté de `PHP_BINARY` (sous PHP-FPM ou LiteSpeed,
`PHP_BINARY` désigne le serveur), puis `PHP_BINDIR/php`, puis `php` dans le `PATH`.
Seul un binaire qui annonce la même version majeure.mineure est retenu.

Une reconstruction en échec (code non nul, fichier absent, `php -l` en erreur) ne
met rien en place et l'erreur est journalisée (`Déploiement : cache non
reconstruit`). Comme la purge a eu lieu avant, l'instance tourne alors **sans** ce
cache : plus lente, mais juste. Sans sous-processus possible (`proc_open`
désactivé, aucun PHP CLI de la bonne version), rien n'est lancé : statut
`indisponible`, étape `skipped`.

Forme commune du résultat :

```json
{
  "configuration": { "statut": "reconstruit" },
  "routes": { "statut": "echec", "erreur": "route:cache a rendu le code 1 : …" }
}
```

## `POST /api/cli/cache/clear`

Purge puis reconstruit. Réponse : `commands` (la liste de la purge, comme avant) et
`reconstruction`. Le message dit si un cache n'a pas été reconstruit ; le code reste
200, puisque tout a bien été vidé. Une purge en échec répond 500, comme avant.

## `POST /api/cli/pull`

`git pull` de la branche courante, puis deux étapes dans `steps` :
`{"action": "cache_clear", "status": "done"|"failed", "commands": […]}` et
`{"action": "cache_rebuild", "status": "done"|"failed"|"skipped", "caches": {…}}`.

## `POST /api/cli/migrate`

Mêmes étapes `cache_clear` et `cache_rebuild`. Une purge ou une reconstruction en
échec fait répondre « Migration completed with warnings ».

## `POST /api/cli/composer/install`

Quand composer réussit : purge complète puis `reconstruction` dans la réponse
(`null` si composer a échoué : rien n'est reconstruit sur des dépendances cassées).

## À savoir

- `POST /api/cli/env` (voir `CLI_ENV.md`) purge le cache de configuration sans le
  reconstruire : la valeur posée prend effet tout de suite, l'instance tourne sans
  cache de configuration jusqu'au prochain `klassci cache:clear`.
- En cache, `env()` rend `null` hors de `config/`. Le test
  `tests/Unit/Deployment/AucunEnvHorsDeConfigTest.php` refuse tout nouvel appel.
- Deux routes portant le même nom empêchent `route:cache`. Le cache de routes
  restera alors vide, et la réponse le dira.

## Pourquoi `route:cache` est sûr maintenant

adminKlassci (`TenantDeploy`, étape 7) omettait volontairement `config:cache` et
`route:cache` : `InstallationHelper` lisait l'état d'installation par `env()`, et
`route:cache` est incompatible avec les routes en closure. Le cache sérialise une
closure en la signant avec `APP_KEY` : la clé changée sans `route:clear`, chaque
route en closure lève `InvalidSignatureException`.

Les deux raisons sont levées. `InstallationHelper` lit `config/installation.php`, et
les dix routes en closure (`/`, `csrf-token-refresh`, `esbtp/trash`, `roles`, les
trois anciennes adresses `esbtp/comptabilite/paiements/{id}…`,
`esbtp/classes/{classe}/semestres-lmd`, `api/user`, `api/lms/documentation`) sont
des actions de contrôleur (`Routage\RoutesSimplesController`,
`API\LmsDocumentationController`) ou un `Route::view`. Le test
`tests/Unit/Deployment/RoutesSansClosureTest.php` refuse toute nouvelle closure.

## Historique

- **Octobre 2026** — `cache/clear`, `pull`, `migrate` et `composer/install`
  reconstruisent les caches de configuration et de routes. Ajouts dans les réponses :
  `reconstruction` (`cache/clear`, `composer/install`) et l'étape `cache_rebuild`
  (`pull`, `migrate`). Pas de changement cassant : aucun champ retiré ni renommé.
  Le nom de route `api.cli.settings.update` désignait deux routes ; celle de
  `PUT /api/cli/settings/{key}` s'appelle désormais `api.cli.settings.update-key`
  (URL inchangée, aucun appelant par nom). `pull` et `migrate` gagnent un champ
  `commands` sur l'étape `cache_clear`, qui peut désormais valoir `failed`. Les
  dix routes en closure deviennent des actions de contrôleur, mêmes noms, mêmes
  middlewares, mêmes réponses.
