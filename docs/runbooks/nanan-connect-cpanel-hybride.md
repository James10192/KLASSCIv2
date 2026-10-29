# Nanan Connect — déploiement hybride pour KLASSCI sur cPanel mutualisé

> Procédure de **préparation et recette**, **pas** d'autorisation de mise en service. Les trois flags MCP restent à `false` jusqu'à une recette OAuth réelle sans données d'élèves. Chaque établissement possède son propre cPanel, sa propre base et sa propre clé HMAC.

## Architecture adaptée au mutualisé

| Composant | Hébergement | Processus permanent requis |
| --- | --- | --- |
| Laravel ERP, utilisateurs, permissions, MySQL | cPanel actuel par établissement | Non (PHP-FPM / requêtes HTTP) |
| `POST /api/mcp-bridge`, consentement et révocation | Même Laravel cPanel | Non |
| Contrôle anti-rejeu des requêtes signées | Table MySQL `mcp_bridge_nonces` propre à l'école | Non |
| Purge des codes temporaires / nonces | `php artisan schedule:run` avec cron cPanel | Non |
| Serveur MCP Streamable HTTP, OAuth, SDK Node 22+ | **Passerelle séparée** derrière HTTPS (VPS/Dokploy ou équivalent) | Oui, géré hors cPanel |
| Auth0 Organizations | Fournisseur OAuth externe | Non sur cPanel |

Le serveur Node n'est **pas** exécuté via cron, PHP ou `queue:work` sur l'hébergement scolaire. N'utilisez cPanel Application Manager/Passenger pour la passerelle que si votre offre fournit réellement Node 22+, le réseau HTTP attendu, les processus persistants et les ressources nécessaires ; sinon utilisez un service isolé. La passerelle ne stocke pas les données scolaires, ne se connecte pas aux MySQL des écoles et n'utilise jamais les sessions Laravel.

## 1. Prérequis de chaque établissement sur cPanel

- PHP CLI de même version compatible que PHP web (projet testé en PHP 8.3), modules Laravel habituels, HTTPS valide, MySQL ou MariaDB, accès au terminal/SSH ou gestionnaire de fichiers et interface Cron Jobs.
- Racine du projet Laravel **hors** `public_html` autant que possible ; seul `public/` doit être public. Conservez `.env`, `storage/`, `vendor/` et les clés hors de la racine web, avec permissions minimales.
- Base et `MCP_TENANT_ORGANIZATION` indépendantes pour chaque école ; secret `MCP_BRIDGE_SECRET` aléatoire d'au moins 32 octets différent par école, uniquement dans les variables d'environnement cPanel et passerelle. Aucun secret GitHub, mail ni URL.
- **Avant activation**, déployer le commit, exécuter `php artisan migrate --force` et vérifier `mcp_bridge_nonces`, `mcp_link_challenges` et `mcp_external_identities`. Effectuer une sauvegarde de la base avant migration et disposer d'un plan de restauration.
- Pas de Redis requis pour les nonces : chaque `(org, nonce)` est stocké en SHA-256 sous clé primaire unique en MySQL, expirant logiquement 120 secondes après création. Un rejeu est refusé même si un processus PHP perd son cache. Si la table manque ou la base devient indisponible, le pont échoue fermé (401).
- Ne pas changer `CACHE_DRIVER` de toute l'application pour Nanan : le cache `file` actuel peut servir aux autres fonctions du site. Les travaux de `onOneServer()` existants méritent un audit **séparé** selon l'environnement et la présence d'un cache central.
- Vérifier que le WAF/ModSecurity de l'hébergeur laisse passer un POST JSON avec l'en-tête `X-Klassci-Mcp-Signature` sur la **route précise**. En cas de faux 403, demander au fournisseur une analyse de l'ID de règle WAF pour ce chemin ; ne jamais désactiver globalement le WAF, ni désactiver les vérifications HMAC, ni exposer l'API en clair.
- Plafonner le body à 16 Kio côté Laravel, avec un reverse proxy/WAF cohérent ; n'autoriser la passerelle qu'en HTTPS avec contrôle TLS normal. Restreindre l'accès par IP de sortie fixe au WAF si l'offre cPanel permet une règle ciblée, mais **ne pas remplacer la signature HMAC** par cette règle IP.

## 2. Cron cPanel

Laravel utilise le Scheduler existant : n'ajoutez pas de worker permanent pour Nanan, et **n'ajoutez pas deux fois** les mêmes crons.

Depuis `cPanel > Advanced > Cron Jobs`, vérifier que la tâche de planification Laravel existe déjà. Si elle n'existe pas et que l'hébergeur autorise le passage chaque minute, l'entrée standard est :

```cron
* * * * * /usr/local/bin/php /home/COMPTE/klassci/artisan schedule:run >> /home/COMPTE/klassci/storage/logs/scheduler-cron.log 2>&1
```

Adapter le chemin du binaire à `command -v php` et `php -v` **sous SSH cPanel** ; un chemin versionné comme `/usr/local/bin/ea-php83` peut être nécessaire. Adapter `/home/COMPTE/klassci` au projet réel. Ne pas appliquer cette ligne sans vérification : elle active **toutes** les tâches Laravel déjà définies, et non Nanan seulement. Sur un mutualisé saturé, inspecter la charge CPU, les e-mails cron et les temps d'exécution ; éviter les chevauchements.

Le Scheduler exécute `nanan:mcp-prune-challenges --limit=1000` toutes les dix minutes. Il supprime au plus 1 000 codes de liaison expirés de plus de 24 h **et** 1 000 nonces arrivés à échéance depuis plus de dix minutes, dans la seule base de l'établissement. Le nettoyage n'accorde aucun droit MCP et est sans effet si les tables n'existent pas.

Pour ne tester que le nettoyage sans exécuter d'autres tâches :

```bash
php artisan nanan:mcp-prune-challenges --limit=100
```

Il est possible d'utiliser **un cron dédié** à cette commande si le Scheduler global n'est volontairement pas utilisé, mais ne programmez pas en parallèle la même purge via le Scheduler et un cron séparé. Les autres tâches métier Laravel continueront à nécessiter leur propre planification.

## 3. Recette du pont et de la passerelle

1. Conserver `MCP_READ_ONLY_ENABLED=false`, `MCP_BRIDGE_ENABLED=false` et `MCP_PAIRING_ENABLED=false` à l'installation. Vérifier la migration, les permissions de fichiers et le cron sans ouvrir de données.
2. Sur une **école fictive**, configurer `MCP_OAUTH_ISSUER`, `MCP_TENANT_ORGANIZATION`, `MCP_BRIDGE_SECRET` en dehors du dépôt puis redémarrer/recharger la configuration PHP de manière contrôlée. Ne pas copier le même secret sur une seconde école.
3. Héberger la passerelle Node 22+ sur un environnement séparé, HTTPS `https://mcp.klassci.com/mcp`, en séparant les configurations d'organisations. Le registre `org_id` pointe uniquement vers le sous-domaine cPanel attendu.
4. Valider Auth0 et les métadonnées protégées, l'audience exacte du JWT, OAuth PKCE, la liaison supervisée puis la liste d'outils réellement autorisés dans MCP Inspector et ChatGPT.
5. Tester école A / école B, comptes révoqués, signatures fausses, replays simultanés, latence et un refus 403 WAF. Aucun port MySQL, RDP ou SSH des établissements ne doit être ouvert à la passerelle. Mesurer la charge MySQL avec 2 appels signés par opération (`tools/list` puis lecture).
6. Une activation passe **école par école et après autorisation explicite**, jamais par synchronisation des branches. Désactiver les flags en cas d'incident ; la révocation locale reste immédiate.

## Ce que les tests ne prouvent pas

Les CI Node 22/24 et PHP/MariaDB prouvent des propriétés du code, pas que LWS/cPanel accepte l'en-tête signé, que le DNS public est correct, que le vrai Auth0 est configuré ou que ChatGPT se connecte. La recette réseau et juridique (consentement, données d'enfants et conservation) doit être réalisée avant la production.

Références :
- https://docs.cpanel.net/cpanel/advanced/cron-jobs/
- https://docs.cpanel.net/knowledge-base/web-services/how-to-install-a-node.js-application/
- https://laravel.com/docs/10.x/scheduling
- https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization
