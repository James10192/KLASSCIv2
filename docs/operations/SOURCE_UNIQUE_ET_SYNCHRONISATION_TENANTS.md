# KLASSCI — Politique canonique : code unique, configuration par établissement

> Cette règle est impérative pour les agents IA, développeurs et opérateurs de déploiement.

## Source unique

- **`presentation` est la seule source de vérité de tout le code applicatif commun** (Laravel, Blade, JS, CSS, migrations, tests, routes et outils CLI).
- **Aucune fonctionnalité, correction ou comportement métier ne doit être développé exclusivement sur une branche d'établissement.** Toute évolution entre dans `presentation` avant propagation.
- La différenciation des établissements passe par les **settings en base, données propres au tenant, .env et fichiers métiers non versionnés**, jamais par du code divergent ou des conditions codées en dur sur le nom du tenant.
- Les branches `esbtp-abidjan`, `esbtp-yakro`, `rostan` (dossier cPanel `islg`), `usat`, `ucao-benin`, `uic` sont des **branches de distribution**, pas des branches de développement. Les branches supplémentaires doivent suivre la même règle.

## Contrat de synchronisation

1. Développer et tester sur une branche de travail ; intégrer via PR à `presentation`.
2. Après validation CI de `presentation`, propager **le même arbre de code versionné** aux branches de distribution. Ne pas confondre égalité de contenu et égalité de SHA quand l'historique ancien est divergent.
3. Préférer une stratégie produisant des mises à jour **fast-forward** depuis les branches cPanel, avec vérification avant publication. La propagation doit être idempotente et sérialisée par branche.
4. Tant qu'une branche conserve un historique indépendant, ne pas annoncer « même code » uniquement parce que `presentation` est son ancêtre ; comparer les arbres, en tenant compte des seules exceptions techniques documentées.
5. **Ne jamais créer des commits dans les checkouts de production.** `git pull --ff-only` seulement après vérification de la branche, de l'upstream, du statut Git et de la possibilité d'avance rapide.
6. En cas de divergence historique (notamment USAT), créer une sauvegarde, comparer les arbres, isoler la réconciliation, vérifier l'absence de changements fonctionnels non remontés, puis effectuer une migration contrôlée de l'historique. Ne jamais utiliser `reset --hard` / `push --force` en routine. Une remise à plat exceptionnelle exige sauvegarde vérifiée, revue d'impact et procédure explicite.
7. L'état propre du répertoire est requis avant déploiement. Les documents, caches, polices et autres fichiers générés dans `storage/` doivent être sortis du versionnement après un audit et sauvegarde ; **ne jamais les supprimer automatiquement**.
8. Les secrets, `.env`, bases SQL, uploads et configurations cPanel restent propres à chaque établissement ; ils ne sont jamais synchronisés depuis `presentation`.

## Contrat de déploiement

Pour chaque instance : vérifier les préconditions Git, sauvegarder et vérifier le plan de reprise, déployer le SHA attendu, exécuter les migrations compatibles et supervisées, nettoyer/reconstruire les caches adaptés, vérifier l'application (HTTP, DB, permissions et routes), puis afficher les SHA local/distant, les migrations en attente et le résultat des smoke tests.

**Une propagation GitHub n'est pas un déploiement en production.** L'échec d'une instance ne doit pas interrompre les autres, mais doit être visible et empêcher un faux statut « réussi ».

## Interdictions supplémentaires

- Ne jamais faire un cherry-pick ou correctif directement sur une branche d'établissement sans intégration préalable dans `presentation`.
- Ne jamais justifier une divergence de code par « fonctionnalité spécifique à l'école » : ajouter un **setting** commun lorsque le métier le nécessite.
- Ne jamais faire un `git pull` implicitement fusionnant deux historiques sur cPanel.
- Ne jamais effacer des fichiers suivis localement pour débloquer un pull sans en examiner les contenus.
- Ne jamais déclarer toutes les instances synchronisées sans vérifier **code + déploiement + migrations + santé applicative** séparément.

## Cible d'architecture

Faire converger toutes les branches de distribution vers un **code strictement identique** à celui de `presentation`, avec uniquement les données et réglages qui varient. À moyen terme, préférer un même artefact/release versionné déployé sur plusieurs instances, si compatible avec l'hébergement existant.
