# Nanan Connect — passerelle MCP privée (phase 1)

> **Prototype de code — aucun établissement n'est activé ou lié à ChatGPT.**
> Ce projet ne doit pas être exposé à Internet tant que la recette OAuth et
> inter-tenant (et les contrôles CI) n'a pas été validée.

> **Mutualisé cPanel** : Laravel demeure dans l'hébergement PHP/MySQL
> de chaque école ; seul le serveur MCP Node 22+ fonctionne à l'extérieur
> (VPS/Dokploy ou service équivalent). Les signatures HMAC utilisent un
> nonce à clé primaire MySQL, pas Redis. Lire le
> [runbook hybride cPanel](../../docs/runbooks/nanan-connect-cpanel-hybride.md)
> **avant** toute recette ou activation.

## Fonctionnement

Client ChatGPT/Code MCP -> `https://mcp.klassci.com/mcp` (Streamable HTTP, SDK v2)
-> vérification stricte du JWT OAuth 2.1 par JWKS (RS256, issuer, audience, expiration,
scopes `mcp:read`, `org_id`)
-> registre d'organisations explicitement provisionnées
-> POST HMAC à **l'instance HTTPS correspondante**, sans transmettre le jeton OAuth
-> Laravel `/api/mcp-bridge` :
    signature + horodatage ±30 sec + nonce anti-rejeu
    -> org_id identique à la configuration locale -> identité externe liée à un utilisateur actif
    -> `CatalogueLectureExterne` et droits KLASSCI natifs
    -> données minimisées vers le modèle.

Le LLM peut filtrer une recherche, pas indiquer quel établissement ou compte
utilisateur le serveur doit choisir. Les écritures et le CRUD restent **fermés**.

## Préparer une instance de recette (aucune valeur de production dans le dépôt)

1. Installer un fournisseur OAuth 2.1 reconnu (ex. Auth0) qui expose son
   metadata discovery, PKCE S256, client ID metadata / DCR ou client
   pré-enregistré. Exiger l'audience correspondant exactement à
   `MCP_RESOURCE_URL` et la présence de l'`org_id` dans les access tokens.
   La page d'authentification doit permettre de choisir l'organisation
   appropriée ; sans organisation, le jeton est rejeté.
2. Configurer la passerelle Node dans un environnement isolé :
   ```dotenv
   MCP_RESOURCE_URL=https://mcp.klassci.com/mcp
   MCP_OAUTH_ISSUER=https://tenant-auth.example.com/
   MCP_TENANTS_JSON=[{"organization":"org_EXAMPLE123","bridgeUrl":"https://demonstration.klassci.com/api/mcp-bridge","secretEnv":"MCP_BRIDGE_SECRET_DEMO"}]
   MCP_BRIDGE_SECRET_DEMO=<secret aléatoire de 32 octets minimum, identique à celui du tenant>
   PORT=3200
   ```
   Ne mettre aucun secret dans `MCP_TENANTS_JSON` ni dans GitHub.
   Ce pilote limite les destinations aux sous-domaines `*.klassci.com`.
3. Installer les dépendances dans `integrations/nanan-mcp` :
   ```bash
   npm install
   npm run check
   npm test
   npm start
   ```
4. Après migration de l'instance de **recette** KLASSCI :
   ```dotenv
   MCP_READ_ONLY_ENABLED=true
   MCP_BRIDGE_ENABLED=true
   MCP_OAUTH_ISSUER=https://tenant-auth.example.com/
   MCP_TENANT_ORGANIZATION=org_EXAMPLE123
   MCP_BRIDGE_SECRET=<le même secret que dans la passerelle>
   ```
   Contrôler la cohérence de l'issuer exact (slash final inclus).
   Migrer `mcp_bridge_nonces` **avant l'activation** du pont. La clé primaire
   de cette table locale empêche les rejeux entre les processus PHP cPanel,
   même si `CACHE_DRIVER=file`. Redis n'est pas requis.

## Lier un compte de recette

Ne jamais associer une identité par e-mail, nom affiché, ou paramètre envoyé
par le modèle. Après preuve d'identité et consentement côté administrateur,
lier **explicitement** l'utilisateur KLASSCI local à son `sub` Auth0 via
la table `mcp_external_identities` de CE tenant. L'empreinte est :

```php
\App\Domain\Assistant\Mcp\RequetePontSignee::identiteHash(
    'https://tenant-auth.example.com/', 'auth0|identifiant-confirmé', 'org_EXAMPLE123'
);
```

Un enregistrement associe cette empreinte unique à un `user_id` autorisé.
`revoked_at` permet la révocation immédiate d'une liaison. 
Ne pas procéder à un appairage automatique sur l'e-mail ; ne pas provisionner
de compte étudiant ou de mineur pour ce pilote.

## Connexions utilisateur : consulter et révoquer (phase 5)

La session Laravel de **l'utilisateur lui-même** donne accès à :

- `GET /nanan/mcp/connections` — liste uniquement ses liaisons dans son
  établissement (identifiants locaux, dates, état révoqué).
- `POST /nanan/mcp/connections/{identity}/revoke` — révoque sa liaison,
  avec authentification de session, cookie + token CSRF du groupe `web`,
  contrôle de propriété dans le `UPDATE` et limitation de fréquence.

Ces routes sont disponibles après la migration de
`mcp_external_identities` et un `MCP_TENANT_ORGANIZATION` non vide,
même lorsque `MCP_BRIDGE_ENABLED=false` : désactiver le pont ne doit pas
priver le personnel du contrôle de ses anciens accès.

L'identifiant de profil `get_connected_profile` est pseudonyme et
**stable** lors de la rotation du secret HMAC de transport. Ne pas
l'utiliser comme jeton, preuve OAuth ou identifiant de base de données.

**Limite cruciale :** révoquer coupe la liaison locale au prochain appel
MCP, même si le JWT reste valide ; cela ne révoque pas automatiquement les
tokens émis par Auth0, ne retire pas l'application de l'interface ChatGPT,
et n'efface pas les conversations ChatGPT préexistantes. Le futur écran
d'onboarding doit le dire clairement. Un nouveau lien ne peut être créé
sans une preuve OIDC authentifiée et un consentement explicite.

Le serveur ne propose pas encore d'écran de connexion ou
d'autorisation OAuth interactif. Le parcours complet reste bloqué tant
que l'issue #1642 n'est pas implémentée.

## Découverte des outils selon les droits locaux

Le serveur MCP **n'annonce plus tous les outils à tous les comptes**.
Avant chaque requête moderne, la factory asynchrone du SDK v2 interroge
l'instance Laravel via une requête HMAC dédiée
`list_authorized_tools`. Cette opération est **interne au pont** et
n'apparaît jamais dans `tools/list`.

Laravel valide signature, nonce, organisation, identité liée, compte actif,
quota, puis `CatalogueLectureExterne::schemas($user)` afin d'obtenir
uniquement les outils que l'utilisateur peut réellement consulter.

- `search_classes` et `get_dashboard_kpis` ne sont enregistrés que
  si la liste reçue les autorise pour ce compte à cet instant.
- `get_connected_profile` n'est enregistré qu'après vérification de la
  liaison active ; il aide le client à identifier sa connexion même lorsque
  son compte n'a pas le droit de consulter les indicateurs scolaires.
- Une erreur de découverte, un lien révoqué, des données malformées ou un
  outil inconnu ne déclenchent **aucun fallback vers tous les outils**.
- L'appel `tools/call` repasse aussi par cette découverte, et l'outil
  scolaire lui-même réévalue les permissions au moment de son exécution.

### Coût et risque opérationnel à mesurer

Il y a une requête interne signée de plus pour chaque appel MCP ; une
invocation scolaire consomme actuellement deux accès au quota du pont
(découverte + lecture). Tester la latence et réviser les limites par compte
avant un pilote élargi ; ne pas mettre les autorisations en cache au prix
d'une révocation tardive. Ne pas considérer `tools/list` comme un
mécanisme suffisant pour protéger une lecture : l'exécution conserve
ses propres contrôles côté Laravel.

## Premier appairage depuis ChatGPT — code OAuth à usage unique

Cette voie de bootstrap supprime le besoin de manipuler manuellement les
`identity_hash` en SQL. Elle **ne remplace pas OAuth** : Nanan doit d'abord
valider la signature du jeton d'accès émis par l'IdP, son audience, son
`org_id` et le scope `mcp:read`. Elle complète ensuite cette preuve
OAuth avec la **session authentifiée KLASSCI** du vrai utilisateur.

Le pilote est **désactivé par défaut indépendamment du mode lecture**. Pour
l'activer en environnement de démonstration uniquement :

- Dans la passerelle : `MCP_PAIRING_ENABLED=true`.
- Dans l'établissement démo : `MCP_PAIRING_ENABLED=true`, ainsi que les
  paramètres du pont signés configurés dans la section précédente.
- Exécuter la migration `mcp_link_challenges` avec celles des identités.
- Dans Auth0 : provisionner l'organisation pilote, un login d'employé et
  l'audience MCP. Aucune identité n'est créée automatiquement.

**Séquence pour l'utilisateur :**

1. Installer un serveur MCP privé dans ChatGPT/Work, ouvrir Nanan et
   s'authentifier via l'IdP OAuth de KLASSCI.
2. Tant que ce `(iss,sub,org_id)` n'est pas lié dans l'école,
   `tools/list` propose **uniquement** `start_account_link`. Les
   classes, les KPI et le profil KLASSCI restent invisibles.
3. L'outil demande à l'école un challenge HMAC contenant un code **aléatoire
   256 bits** de 43 caractères. La base locale stocke uniquement
   `SHA256(code)`, l'empreinte de l'identité, l'organisation et sa date
   d'expiration (**10 minutes**). Jusqu'à trois codes par identité toutes
   les dix minutes et quota global par établissement.
4. Le modèle remet le code et l'URL du site officiel de l'école. Pour
   éviter les substitutions de compte, **un administrateur distinct** doit
   vérifier par un canal indépendant l'identité de l'employé, son compte
   OAuth et son appartenance à l'établissement. L'administrateur ouvre
   `/nanan/mcp/review`, indique l'ID utilisateur local exact, le code et
   confirme le contrôle effectué.
5. `POST /nanan/mcp/connections/approve` requiert une session Laravel
   `superAdmin` active, CSRF et une identité locale distincte de
   l'administrateur ; le code doit exister, être non expiré, non consommé
   et pas déjà approuvé. Cette opération n'ouvre **aucune lecture scolaire**.
6. L'employé ouvre `/nanan/mcp/connect` et s'authentifie dans **sa**
   session Laravel. Le formulaire demande le code et le consentement
   explicite. `POST /nanan/mcp/connections/claim` exige que la session
   corresponde exactement à l'utilisateur préapprouvé.
7. La preuve est consommée dans la base propre à l'école via
   `SELECT FOR UPDATE` ; une erreur, un rejeu, un code intercepté par
   une autre session ou un changement de compte est refusé.
8. Après liaison seulement, Nanan découvre les permissions locales
   et peut effectuer les lectures autorisées. La révocation coupe
   l'accès au prochain appel.

**Modèle de menace et limites :** il s'agit d'une approbation humaine
explicite **en deux étapes**, pas d'une preuve cryptographique que le compte
Auth0 et le compte Laravel appartiennent à la même personne. L'administrateur
doit réellement vérifier les deux identités. Le contrôle indépendant
réduit le risque de phishing par substitution de code ; il ne le supprime
pas si l'administrateur valide une fausse identité. Pour une liaison
entièrement autonome à grande échelle, remplacer cette revue par une
seconde authentification OIDC avec `state`, `nonce` et PKCE et une
égalité stricte des sujets signés ; **ne pas désactiver** la revue
sans disposer de cette preuve automatisée.

Le code reste strictement désactivé par défaut sur le gateway et les
écoles ; aucun membre du personnel ou administrateur de production n'est
invité à effectuer un appairage à ce stade.

Le succès du flux en tests Node/PHP ne démontre PAS que l'IdP est
configuré, ni que le callback OAuth/CIMD fonctionne dans ChatGPT.
Tester le parcours réel via MCP Inspector, puis ChatGPT sur une instance
de démonstration avec deux comptes et sans données réelles d'enfants.

Références officielles :
https://developers.openai.com/plugins/build/auth et
https://developers.openai.com/plugins/deploy/connect-chatgpt

## Refus de compte et limites réseau — durcissement du 23 octobre 2026

Le pont Laravel ne retourne `code=identity_not_linked` sur
`list_authorized_tools` **que pour une identité jamais connue localement**,
sur une requête HMAC valide et si `MCP_PAIRING_ENABLED=true`. Une
identité **révoquée**, un compte Laravel **inactif**, un accès désactivé,
une erreur de droits ou un autre refus HTTP 403 n'autorisent **jamais**
le retour de `start_account_link`. La passerelle ne déduit plus « non lié »
du seul statut 403 : il faut le code exact, sinon le refus reste fermé.

Chaque réponse HTTP du tenant est décodée par flux sous un plafond de
**128 Kio** (et **4 Kio** pour identifier un refus d'appairage), y compris
si le serveur ne fournit pas `Content-Length` ou annonce une longueur
mensongère. Cela borne l'utilisation mémoire face à une réponse anormale
et évite de transmettre un rapport scolaire arbitrairement volumineux au
modèle. Les codes et erreurs internes restent absents des réponses MCP
publiques.

Ce contrôle ne remplace pas un plafond de requêtes à l'entrée publique
ni un filtrage de sortie du réseau contre le détournement DNS/SSRF. Les
deux restent des prérequis à une exposition Internet.

## Mettre en ligne plus tard

- HTTPS public stable sur `/mcp` derrière proxy, Host/Origin allowlist,
  aucune ouverture du bridge à un réseau tiers sans filtrage supplémentaire.
- Protéger les secrets par gestionnaire de secrets, avec rotation par tenant.
- Ouvrir le flux OAuth dans MCP Inspector (auth code + PKCE, resource, audience).
- Tester identité A/école A, identité B/école B, compte désactivé, révocation,
  droits retirés pendant la session, scope absent, jeton périmé, jeton d'une
  autre ressource, double requête, panne et dépassements de quotas.
- Ne jamais autoriser les redirections HTTP vers une destination non approuvée.
- Les instances KLASSCI indépendantes conservent leurs propres données ; aucune
  copie centralisée d'informations scolaires dans la passerelle.


### Compatibilité des métadonnées OAuth du SDK MCP v2

Au contrôle de protocole, le SDK v2 conservait la politique OAuth dans
`_meta.securitySchemes` mais omettait son équivalent racine
`securitySchemes` dans `tools/list`. Les deux outils publient donc
le miroir `_meta` et le serveur présente systématiquement un défi HTTP
`401 WWW-Authenticate` avec les métadonnées de ressource OAuth.
**Ne pas considérer cela comme une preuve de compatibilité ChatGPT** :
MCP Inspector + écran de liaison OAuth ChatGPT doivent confirmer le parcours.
Si le client exige le champ racine, adapter le SDK ou un adaptateur explicitement
testé plutôt que bricoler silencieusement les messages MCP.

## Limites actuelles

- Aucun IdP, secret, compte ou domaine MCP de production n'est configuré.
- Aucun test end-to-end connecté à ChatGPT n'a été réalisé.
- Démarrage de la passerelle uniquement avec une configuration explicite.
- Auth0/IdP doit être configuré pour émettre un jeton d'accès JWT avec
  `org_id` et `aud` correspondant à la ressource MCP.
- Le registre des établissements est statique pour ce pilote ; onboarding,
  provisioning sécurisé, révocation automatisée et surveillance opérationnelle
  seront nécessaires avant un service commercial.
- Tests sécurité/charge et conformité des données scolaires exigés avant déploiement.

Documentation : https://developers.openai.com/plugins/build/auth et
https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization


### Sécurité du SDK MCP — CVE-2026-111771 (avis du 20 octobre 2026)

Les versions `@modelcontextprotocol/server` 2.0.0–2.2.0 et
`@modelcontextprotocol/express` 2.0.0–2.0.1 sont affectées par
[GHSA-rvq5-wwqv-78pq](https://github.com/advisories/GHSA-rvq5-wwqv-78pq).
Le vérificateur JOSE refuse déjà tout JWT dont l'audience n'est pas
`MCP_RESOURCE_URL`, mais la configuration garde **en plus**
`requireBearerAuth({expectedResource})`. Le manifeste impose
`server >=2.3.1`, `express >=2.0.2` et `node >=2.1.1`.

Le test HTTP `tests/express-audience.test.mjs` est un test de régression :
il injecte un `AuthInfo.resource` étranger et exige un refus 401 dans
le véritable middleware Express. Ne jamais retirer ce contrôle, même si
la vérification `jwtVerify(audience)` est toujours présente.

**Avant déploiement** : figer et vérifier les dépendances résolues dans un
`package-lock.json` audité et utiliser `npm ci`; le CI actuel utilise
`npm install` pour le pilote, ce qui n'est pas encore un build reproductible.
