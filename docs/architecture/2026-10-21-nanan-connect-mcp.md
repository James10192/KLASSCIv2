# ADR — Nanan Connect : KLASSCI comme serveur MCP (phase 0)

Date : 21 octobre 2026  
État : **proposition technique / base interne, non déployable en l'état**  
Branche de référence : `presentation`

## Décision

Nanan reste l'agent embarqué de KLASSCI. Les clients externes (ChatGPT, Codex,
autres clients MCP) utiliseront à terme les **mêmes services métier** via une
passerelle MCP HTTPS et OAuth. Ils ne reçoivent jamais d'accès SQL, de jeton
d'administration CLI, ni d'identifiants d'établissement arbitraires à renseigner
dans le prompt.

Le premier lot ne crée aucun endpoint réseau. Il fournit un adaptateur PHP
interne, désactivé par défaut, pour les **seuls outils de lecture explicitement
approuvés** :

- `search_classes` — noms, filières, effectifs et capacités, soumis à `classes.view`.
- `get_dashboard_kpis` — statistiques agrégées, soumises à `dashboard.view`. Les indicateurs financiers continuent d'être filtrés par la permission métier existante.

La classe `App\Domain\Assistant\Mcp\CatalogueLectureExterne` ne doit
être appelée que par un futur transport **après** validation de l'identité,
du tenant et des scopes OAuth. Le flag `MCP_READ_ONLY_ENABLED`
ne publie rien sur Internet : il active uniquement l'adaptateur interne.

## Pourquoi réutiliser le catalogue ?

- `CatalogueOutils::outil()` retrouve la définition canonique.
- `ChatbotTool::isAvailableFor()` valide les permissions avant l'exposition.
- `CatalogueOutils::executer()` passe de nouveau par
  `ChatbotTool::executeAuthorized()` au moment de l'appel.
- Les actions `ActionAgent` passent déjà par
  `ExecutionDesPropositions` : préparation sans écriture, accord humain, nouvelle
  lecture de l'état et audit.

**Ne pas exposer `CatalogueOutils::schemas()` tel quel à l'extérieur.**
Son catalogue comprend des outils d'écriture, des outils sensibles et des
éléments propres à l'interface Nanan. L'adaptateur impose une liste fixe de
classes concrètes et retire systématiquement `widget`,
`deep_link` et les champs imprévus des résultats.

## Architecture cible (à construire)

Client MCP -> HTTPS /mcp (streamable HTTP) -> passerelle MCP
-> validation OAuth 2.1, audience, scopes et expiration
-> résolution serveur {tenant, utilisateur, établissement autorisé}
-> instance KLASSCI du tenant -> CatalogueLectureExterne / ActionAgent
-> réponse structurée, journalisée et filtrée.

Choix de mise en œuvre à valider : serveur TypeScript avec SDK officiel MCP
comme transport + vérification des jetons par un fournisseur d'identité reconnu
(Auth0 ou autre supportant MCP/OAuth), et pont interne authentifié vers Laravel.
Une variante PHP native est possible si son transport MCP est suffisamment
maintenu. Ne pas présupposer que Laravel Sanctum fournit à lui seul le flux
OAuth 2.1 attendu par ChatGPT : les jetons Sanctum actuels sont utilisés pour
la CLI et ne constituent pas une intégration OAuth MCP.

Références (documentation à date) :
- https://developers.openai.com/plugins/build/mcp-server
- https://developers.openai.com/plugins/build/auth
- https://developers.openai.com/plugins/build/app-quickstart

## Menaces et mesures obligatoires avant un prototype connecté

| Menace | Protection bloquante |
|---|---|
| Confusion d'établissement (cross-tenant) | Tenant identifié par l'issuer / token et le domaine de l'instance, jamais par un argument LLM ; tests de non-fuite entre deux écoles |
| Jeton volé ou détourné | OAuth 2.1 PKCE, issuer/audience/resource/expiration/scopes contrôlés sur chaque requête, révocation et rotation |
| Étudiant mineur / données personnelles | Limitation stricte des champs, scopes dédiés, minimisation, rétention, règles de confidentialité et accord contractuel |
| Prompt injection dans les données et pièces | Traiter toutes les données lues comme non fiables ; le modèle ne peut jamais modifier l'identité, les permissions ou déclencher seul une écriture |
| Appels en masse / scraping | Quotas par tenant + personne + outil, limites de recherche, pagination et alertes |
| Écriture non autorisée | Outils d'écriture absents de la phase 0 ; future proposition validée dans KLASSCI, confirmée pour l'auteur authentifié, anti-doublon, expiration, état revérifié |
| Retour de secrets / URL interne | Liste blanche des champs renvoyés et tests de non-divulgation |
| Traçabilité | Journal corrélé à l'utilisateur, au tenant, à l'outil, à la validation et au résultat, sans logger inutilement de données scolaires sensibles |
| Indisponibilité d'un tenant | Isolation des erreurs, timeouts, circuits de protection sans traverser vers une autre école |

## Critères d'acceptation phase 0

- [x] Aucun endpoint MCP, aucune authentification non testée, aucun accès client en production.
- [x] Flag off par défaut.
- [x] Seules deux classes de lecture explicitement autorisées sont exposables.
- [x] Les permissions existantes sont contrôlées à la découverte et à l'exécution.
- [x] Les données de widgets internes sont exclues.
- [x] Tests unitaires ajoutés.
- [ ] Exécuter les tests et vérifier les CI sur la PR.

## Lots suivants (ne PAS les considérer comme livrés)

1. **Auth et multi-tenant** : choisir le fournisseur OAuth MCP, discovery
   `.well-known`, consentement, scopes par utilisateur, liaison et révocation du
   compte ; démontrer que le tenant A ne peut jamais lire B.
2. **Transport privé** : serveur MCP streamable HTTP, signature/audience,
   endpoint passerelle Laravel privé, rate-limit, audit, profile read-only.
3. **Deux lectures pilotes** : inspecteur MCP, ChatGPT/Work, identités et
   permissions variées, E2E sans données réelles de mineurs.
4. **Actions proposées** : créer une classe, créer une évaluation, sans
   réécriture métier, approbation liée au même compte dans KLASSCI, puis
   retour du statut au client MCP. Ne pas exposer de CRUD générique.
5. **Audit et pilote client** : analyse de sécurité, RGPD/loi applicable,
   validation de la direction de chaque école, documentation, métriques
   (temps gagné, taux de succès, coût, refus de permissions) et déploiement graduel.

## Vérification locale prévue

```bash
php artisan test --filter=CatalogueLectureExterneTest
php bin/verifier-couverture-nanan.php
```

Si ces tests ne passent pas, ne pas fusionner. Ce lot n'est pas la livraison
d'un plugin ChatGPT utilisable : c'est sa base de sécurité et d'interopérabilité.
