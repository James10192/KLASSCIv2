# Dossier de recette — Nanan Connect MCP, identité et confidentialité

**État : audit de code / tests hors ligne seulement.** Aucun IdP de production,
aucune liaison ChatGPT réelle ni donnée scolaire cliente utilisée. Document
associé aux PR #1636, #1639, #1641, #1643 et aux issues #1637, #1642.

## Décisions confirmées sur les documents officiels (21/10/2026)

1. **Serveur MCP universel, non public dans un premier temps.** ChatGPT peut
   utiliser une connexion MCP privée pour la recette. Un plugin public
   commercial demande un domaine accessible publiquement, vérification
   de l'organisation, politique de confidentialité, scénarios de test et
   passage par la procédure de publication. Les URLs MCP par tenant
   « template » sont réservées à certains développeurs approuvés :
   choisir **une seule URL** pour Nanan Connect et router via l'identité OAuth.
2. **OAuth 2.1 délégué :** OpenAI utilise authorization code + PKCE S256 ;
   support de CIMD, DCR ou client prédéfini. Le fournisseur doit annoncer
   le bon \`issuer\`, endpoints OAuth, méthodes de client compatibles, et
   \`code_challenge_methods_supported: ["S256", ...]\`. ChatGPT demande
   exactement le \`resource\` annoncé dans les métadonnées RFC 9728 ;
   les JWT de ce serveur doivent porter ce \`aud\`.
3. **Privilèges par appel :** \`@modelcontextprotocol/express\`
   \`requireBearerAuth\` valide le jeton avant de passer \`AuthInfo\` au
   protocole. Le SDK émet \`401 invalid_token\` pour un jeton invalide
   et \`403 insufficient_scope\` pour un jeton valide sans
   \`mcp:read\`. Un accès non authentifié ne passe pas au catalogue.
4. **Spécification MCP 2026-07-28 :** coeur stateless, POST et contexte
   par requête, clients anciens refusés pour ce pilote. Ne jamais
   sauvegarder une identité vérifiée dans un singleton mutable.
5. **Pas de pont multi-écoles basé sur l'IP du client :** toutes les
   requêtes arrivent des mêmes IP de sortie du gateway. Désactiver le
   quota Laravel \`throttle:api\` sur la route signée et limiter
   après signature HMAC + résolution de compte local. Garder des
   défenses anti-abus WAF/L7 en amont pour les tentatives non signées.
6. **Comptes des établissements :** le \`sub\` Auth0 signé et
   l'\`org_id\` ne donnent jamais accès automatiquement : le compte
   Laravel doit être explicitement lié, actif et autorisé. Refuser
   \`client_credentials\` et les comptes machine. Aucun email
   d'étudiant ni notes exportées pour résoudre l'identité.
7. **Données et conformité :** limiter les sorties des outils aux
   besoins explicites ; tester les champs imbriqués, journaux et identifiants
   avant publication, déclarer les catégories traitées dans la politique
   de confidentialité. Les descriptions « read-only » doivent être
   véridiques ; toute écriture future nécessite confirmation humaine.

## Outillage installé dans PR #1643

- \`sdk-authorization.mjs\` : valider cryptographiquement RS256,
  issuer, audience exacte, expiration, personne humaine, organisation
  provisionnée ; renvoyer \`AuthInfo\` avec scopes pour le gate officiel.
- \`server.mjs\` : utiliser le gate du SDK et la factory demandée par
  requête. Refus automatiques 401 / 403. Un handler partagé, aucun
  principal global.
- \`oauth-discovery.mjs\` / \`check-idp.mjs\` : inspection des
  métadonnées RFC 8414 ou OIDC, confirmation de PKCE S256 et des
  mécanismes de client. Ce contrôle **ne réalise pas de connexion**.
- Route \`/api/mcp-bridge\` Laravel : suppression des limitations
  basées sur l'adresse IP de sortie ; quota par compte local authentifié
  et réponse 429 + Retry-After.

Commande de diagnostic **sans secret en sortie** après installation des
dépendances et configuration de l'issuer (sur l'infrastructure de recette) :

\`\`\`bash
cd integrations/nanan-mcp
npm install
npm run check
npm test
MCP_OAUTH_ISSUER=https://auth-demo.example.com/ \
MCP_RESOURCE_URL=https://mcp.klassci.com/mcp \
npm run check:idp
\`\`\`

Si un client OAuth pré-enregistré est utilisé plutôt que CIMD/DCR,
renseigner \`MCP_OAUTH_PRE_REGISTERED_CLIENT_ID\` dans les secrets
de recette. Ne pas créer un client ni un token à partir de ce diagnostic.
\`check:idp\` n'effectue aucune migration KLASSCI.

### Matrice d'acceptation obligatoire

| Test | Attendu |
| --- | --- |
| Pas de jeton, jeton expiré, issuer erroné | 401 invalid_token, URL de métadonnées |
| JWT authentique sans mcp:read | 403 insufficient_scope, challenge correct |
| Audience autre que MCP_RESOURCE_URL | 401 et aucune lecture |
| JWT d'une org différente/non enregistrée | 401 et aucune lecture |
| \`client_credentials\` ou \`sub\` machine | 401 |
| Identité liée puis révoquée dans école A | Lecture puis 403 immédiat |
| École B / compte B | Aucun dossier A ; aucune permutation via paramètres |
| Deux employés derrière l'IP du gateway | Quotas indépendants |
| GET /mcp ou legacy \`initialize\` | Refus |
| \`get_connected_profile\` | id opaque + nom local, aucune PII supplémentaire |
| Scan Tools ChatGPT | Trois outils avec schémas/permissions exactes |
| OAuth fournisseur réel | Login, PKCE, consentement, CIMD, refresh, révocation |
| Publication | Vérification entreprise + politique confidentialité + tests |

## Limitations à résoudre avant exposition réseau

- OAuth Auth0 et ses Organizations ne sont pas provisionnés.
  Vérifier l'offre commerciale, quotas d'orgs, coûts B2B, résidence des
  données, MFA, gestion des membres, et mode de client CIMD.
- \`sdkVerifierFor\` utilise par défaut le JWKS officiel attendu d'Auth0
  \`/.well-known/jwks.json\`. Pour un fournisseur alternatif,
  passer une source JWKS validée à partir de \`jwks_uri\` ; ne pas suivre
  une URL arbitraire découverte sans contrainte d'origine.
- Protéger le gateway de l'épuisement CPU par quota par identité
  Auth0/organization en amont, puis Cloudflare WAF et limitation au
  niveau réseau ; la limite dans Laravel ne protège pas le point d'entrée
  public des demandes sans signature.
- Valider les réponses métier imbriquées et les limites par outil.
  Documenter la politique de conservation des logs sans PII.
- Réaliser une véritable liaison OAuth dans l'interface KLASSCI avec
  preuve de possession, anti-CSRF et état non rejouable : issue #1642.
- Faire passer les CI et une revue sécurité indépendante ; ne pas
  activer les flags MCP sur une école cliente auparavant.

## Références faisant autorité

- OpenAI Auth : https://developers.openai.com/plugins/build/auth
- OpenAI requirements : https://developers.openai.com/plugins/deploy/app-review
- OpenAI privacy : https://developers.openai.com/plugins/guides/security-privacy
- MCP SDK v2 : https://ts.sdk.modelcontextprotocol.io/v2/serving/authorization
- MCP Protocol : https://modelcontextprotocol.io/specification/2026-07-28
- RFC 8414 discovery : https://www.rfc-editor.org/rfc/rfc8414
- Auth0 JWKS : https://auth0.com/docs/secure/tokens/json-web-tokens/json-web-key-sets
- Laravel RateLimiter : https://laravel.com/docs/10.x/rate-limiting

**Aucun composant de cette PR ne constitue une autorisation de déploiement.**
