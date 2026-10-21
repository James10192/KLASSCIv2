# ADR — Stack cible de Nanan Connect et compatibilité ChatGPT

**Date :** 21 octobre 2026  
**Statut :** décision pour un pilote privé, pas une validation de déploiement  
**Dépendances :** PR #1636 (catalogue de lecture), PR #1639 (gateway MCP), issue #1637

## Objectif

Permettre aux dirigeants et équipes habilitées d'utiliser Nanan sur KLASSCI, depuis
ChatGPT, Codex ou tout autre client MCP, sans réécrire le CRM éducatif, sans
lui donner un token CLI administrateur et sans sortir le dossier scolaire
d'un établissement de son instance.

**Architecture recommandée pour le produit actuel :**

| Composant | Choix | Raison |
| --- | --- | --- |
| Données et règles scolaires | Laravel KLASSCIv2 + base par établissement | Déjà en production ; permissions et actions métiers canoniques à réutiliser |
| Contrôle des droits | Spatie permissions + garde métier dans l'instance Laravel | Réévaluation à chaque appel ; aucune autorisation déduite des instructions du LLM |
| Transport externe | Node.js 22 LTS + TypeScript cible, SDK MCP v2 officiel (déjà amorcé en ESM JS) | Compatible MCP 2026-07-28, Streamable HTTP stateless, clients ChatGPT/Codex |
| Authentification du pilote | Auth0 Auth for MCP + Organizations + OAuth 2.1/CIMD | IdP existant, issuer/JWKS, consentement, PKCE, organisation dans le jeton ; en disponibilité générale |
| Liaison du compte | Compte Laravel actif **explicitement lié** à (issuer, sub, org_id) | Pas d'association implicite par nom/e-mail ; révocable |
| Pont vers les écoles | HTTPS + HMAC horodaté, clé différente par tenant, nonce anti-rejeu | Jeton OAuth jamais retransmis au tenant ; séparation stricte |
| Edge et réseau | Cloudflare DNS/TLS/WAF/rate limiting, origin Node privé derrière proxy | Pas de SQL au niveau du gateway ; durcissement du trafic |
| Hébergement du pilote | Service Node en conteneur isolé sous Dokploy/VPS ou plateforme gérant un serveur Node permanent | SDK Node, accès HTTPS aux tenants, logs et maintenance contrôlés |
| Observabilité | Logs structurés sans PII/notes + corrélation de requêtes + indicateurs de refus | Audit utile sans centraliser les dossiers d'étudiants |
| Expérience future | TanStack Start + shadcn/ui + Tailwind pour le portail d'onboarding | UX premium, partageable entre opérateurs et clients ; étape **séparée**, pas nécessaire au MVP |
| Métadonnées/SaaS futures | Convex facultatif, seulement pour configuration/événements **non scolaires** après évaluation | Ne pas ajouter un second stockage à toutes les instances pour deux outils de lecture |

### Pourquoi Auth0 maintenant, Better Auth plus tard si pertinent ?

La documentation d'OpenAI demande un OAuth 2.1 conforme, une découverte
`/.well-known`, une vérification du paramètre `resource`, des jetons liés à
l'audience, PKCE S256 et un enregistrement client (CIMD conseillé ou DCR/client
préenregistré). La version 2 du SDK MCP n'est **pas** à elle seule un fournisseur
OAuth. Auth0 fournit actuellement un produit Auth for MCP orienté vers ces
contraintes, y compris des organisations et une gouvernance B2B.

`@better-auth/mcp` est une vraie option open source désormais documentée :
Better Auth OAuth Provider + JWT + `@better-auth/cimd`, profil MCP
2026-07-28, refus des anciens transports. C'est **l'option recommandée pour
un nouvel ensemble TanStack Start entièrement sous notre contrôle**,
à condition de concevoir la fédération/liaison fiable avec l'authentification
Laravel des utilisateurs actuels. Ne pas remplacer Laravel Auth par un
système parallèle par simple préférence de stack.

**Décision :** démarrer Auth0 pour la recette de Nanan Connect KLASSCIv2,
garder l'adaptateur JWT remplaçable, comparer Better Auth à coûts réels et
aux exigences d'identité uniques une fois le pilote fonctionnel. Ne pas
imposer Better Auth + Convex + TanStack à la base Laravel du ERP.

## Contrat de sécurité à respecter

1. Chaque requête `POST /mcp` exige un **access token délégué humain**
   valide : signature RS256/JWKS, `iss`, `aud` exacte, expiration,
   `mcp:read`, `org_id`. Refuser les jetons client_credentials et
   les `sub` techniques machine.
2. Le registre `org_id -> instance HTTPS autorisée` ne dépend **jamais**
   d'un champ choisi par le modèle. Vérifier l'appartenance à l'organisation
   au niveau de l'IdP et refuser tout tenant absent du registre.
3. Chaque instance KLASSCI valide la signature, la fraîcheur, le nonce,
   son `organization` configuré, la liaison locale non révoquée, le
   compte actif et les permissions métier. Absence de liaison = 403.
4. Exposer des **outils précis**, pas un CRUD SQL générique. La première
   surface fournit `search_classes`, `get_dashboard_kpis` et
   `get_connected_profile`. Le profil doit émaner du compte Laravel
   résolu par le pont, avec un ID opaque et sans e-mail par défaut.
5. MCP 2026-07-28 : handler stateless, POST uniquement, `legacy: 'reject'`.
   Les outils possèdent `securitySchemes` OAuth et
   `_meta.securitySchemes` pour compatibilité ChatGPT.
6. Publier le Protected Resource Metadata (RFC 9728) ; répondre aux
   requêtes non authentifiées avec `WWW-Authenticate`. Tester à la fois
   l'interface de connexion serveur et les retours tool-level
   `_meta['mcp/www_authenticate']` si l'UX nécessite un step-up.
7. Mettre des limites quantitatives par établissement, utilisateur et
   outil ; corps de requête borné, pas de redirection en aval. Valider
   les DNS et destinations au déploiement (risques SSRF, DNS rebinding),
   secrets rotatifs et anti-rejeu via cache partagé.
8. Données scolaires traitées comme **non fiables** pour les instructions
   du modèle. Limiter les sorties et redacter les champs sensibles.
9. Aucune écriture avant approbation humaine **dans KLASSCI**, avec
   revalidation métier, expiration, identité identique et journal.

## Onboarding utilisateur : la prochaine fonctionnalité métier nécessaire

Ne pas configurer des correspondances par SQL manuel en production. Construire
un écran « Connecter Nanan à ChatGPT » dans KLASSCI, accessible via une
session Laravel authentifiée, montrant : établissement, utilisateur,
permissions prévues, consentement, appareils/connecteurs liés et bouton
**Révoquer**.

Le lien d'identité doit résulter d'une **preuve de possession OAuth vérifiée
côté serveur**, avec state/nonce/PKCE et callback protégé, puis une confirmation
de l'utilisateur dans sa session Laravel. Aucun `sub`, `org_id` ou identifiant
d'école arbitraire passé dans l'URL ne peut devenir une liaison. Prévoir le
choix explicite de l'établissement et l'absence de liaison pour les comptes
étudiants/mineurs au pilote.

## Critères de recette et couverture

- [x] Un SDK MCP v2 offre un transport de lecture et des contrôles JWT dans les PR antérieures.
- [x] Tests de base OAuth, sous-domaines autorisés et non-fuite inter-tenant déjà créés.
- [ ] Tester en CI `POST /mcp` et refuser `GET`/`DELETE` et l'ancien protocole.
- [ ] Vérifier le profil authentifié via la base Laravel et sa révocation.
- [ ] Provisionner l'IdP et tester `authorization_code` PKCE avec **CIMD** ChatGPT.
- [ ] Vérifier la découverte du serveur d'autorisation et des ressources, les
      scopes, l'audience, les callbacks et l'échange du code.
- [ ] Test réel MCP Inspector puis connexion ChatGPT (ordinateur et mobile).
- [ ] Établissements A et B en bases distinctes ; démontrer 0 fuite,
      révocation immédiate et baisse de permission active.
- [ ] Revue de sécurité et confidentialité puis déploiement pilote approuvé.

## Sources officielles vérifiées en octobre 2026

- https://developers.openai.com/plugins/build/auth
- https://developers.openai.com/plugins/build/mcp-server
- https://developers.openai.com/plugins/reference
- https://ts.sdk.modelcontextprotocol.io/v2/serving/authorization
- https://better-auth.com/docs/plugins/mcp
- https://auth0.com/blog/auth0-auth-for-mcp-servers-generally-available/
- https://auth0.com/docs/manage-users/organizations/using-tokens
- https://docs.convex.dev/quickstart/tanstack-start

**État du document :** architecture choisie, mais IdP non provisionné et
connexion ChatGPT non encore démontrée. Cette ADR n'autorise aucun déploiement.
