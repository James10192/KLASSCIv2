# API CLI — déploiement : pull, caches, migrations, composer

Les routes que `klassci pull`, `klassci cache:clear`, `klassci migrate` et
`klassci composer` appellent sur une instance, sans SSH. Toutes exigent un jeton CLI
portant `cli:admin` (sinon 403). Contrôleur : `CLIMaintenanceController`.

## Caches de configuration et de routes

Depuis octobre 2026, ces quatre routes **reconstruisent** le cache de configuration
(`config:cache`) et le cache de routes (`route:cache`) après les avoir vidés.
Avant, elles les vidaient seulement : chaque requête rechargeait une quarantaine de
fichiers de `config/` et réenregistrait près de 1600 routes.

La reconstruction (`App\Domain\Exploitation\ReconstructionDesCaches`) :

1. invalide dans OPcache les fichiers `config/*.php` et `routes/*.php`, pour qu'un
   `git pull` de la même requête soit bien lu ;
2. lance `config:cache` puis `route:cache` ;
3. contrôle que chaque fichier produit existe et que PHP sait l'analyser.

Un cache qui ne se reconstruit pas (commande en erreur, code de retour non nul,
fichier absent ou tronqué) est **vidé**, jamais laissé à moitié. L'instance tourne
alors sans ce cache : plus lente, mais juste. L'erreur est journalisée
(`Déploiement : cache non reconstruit, vidé à la place`) et rendue dans la réponse.

Forme commune du résultat :

```json
{
  "configuration": { "statut": "reconstruit" },
  "routes": { "statut": "echec", "erreur": "… Cache vidé : l'application fonctionne sans lui." }
}
```

## `POST /api/cli/cache/clear`

Vide `config`, `route`, `cache`, `view`, les permissions, le cache des réglages et
OPcache, puis reconstruit. Réponse : `commands` (inchangé) et `reconstruction`. Le
message dit si un cache n'a pas pu être reconstruit ; le code reste 200, puisque
tout a bien été vidé.

## `POST /api/cli/pull`

`git pull` de la branche courante, purge des caches, puis une étape
`{"action": "cache_rebuild", "status": "done"|"failed", "caches": {…}}` dans `steps`.

## `POST /api/cli/migrate`

Même étape `cache_rebuild` après la purge. Une reconstruction en échec fait répondre
« Migration completed with warnings ».

## `POST /api/cli/composer/install`

Quand composer réussit : purge, OPcache, puis `reconstruction` dans la réponse
(`null` si composer a échoué : rien n'est reconstruit sur des dépendances cassées).

## À savoir

- `POST /api/cli/env` (voir `CLI_ENV.md`) purge le cache de configuration sans le
  reconstruire : la valeur posée prend effet tout de suite, l'instance tourne sans
  cache de configuration jusqu'au prochain `klassci cache:clear`.
- En cache, `env()` rend `null` hors de `config/`. Le test
  `tests/Unit/Deployment/AucunEnvHorsDeConfigTest.php` refuse tout nouvel appel.
- Deux routes portant le même nom empêchent `route:cache`. Le cache de routes
  restera alors vide, et la réponse le dira.

## Historique

- **Octobre 2026** — `cache/clear`, `pull`, `migrate` et `composer/install`
  reconstruisent les caches de configuration et de routes. Ajouts dans les réponses :
  `reconstruction` (`cache/clear`, `composer/install`) et l'étape `cache_rebuild`
  (`pull`, `migrate`). Pas de changement cassant : aucun champ retiré ni renommé.
  Le nom de route `api.cli.settings.update` désignait deux routes ; celle de
  `PUT /api/cli/settings/{key}` s'appelle désormais `api.cli.settings.update-key`
  (URL inchangée, aucun appelant par nom).
