import { createRemoteJWKSet, jwtVerify } from 'jose';
import { OAuthError, OAuthErrorCode } from '@modelcontextprotocol/server';
import { checkedPrincipal } from './security.mjs';

/**
 * Adaptateur du vérificateur OAuth du SDK MCP v2.
 *
 * La vérification du JWT est entièrement indépendante de la décision des
 * scopes : le gate du SDK répond 403 insufficient_scope, pas 401 invalid_token.
 * L'identité stockée dans authInfo.extra n'est émise qu'après contrôle JWT.
 */
export function sdkVerifierFor(settings, tenants, jwks =
  createRemoteJWKSet(new URL('.well-known/jwks.json', settings.issuer))) {
  return {
    async verifyAccessToken(token) {
      let payload;
      try {
        ({ payload } = await jwtVerify(token, jwks, {
          issuer: settings.issuer,
          audience: settings.resource,
          algorithms: ['RS256'],
          clockTolerance: '5s'
        }));

        if (!Number.isSafeInteger(payload.exp) || payload.exp <= Math.floor(Date.now()/1000)) {
          throw new Error('expired or missing exp');
        }

        const principal = checkedPrincipal(payload, false);
        if (!tenants.has(principal.organization)) {
          throw new Error('unprovisioned organization');
        }

        const scopes = typeof payload.scope === 'string'
          ? payload.scope.split(/\s+/).filter(Boolean) : [];
        return {
          token,
          clientId: typeof payload.azp === 'string' && payload.azp
            ? payload.azp : (typeof payload.client_id === 'string' && payload.client_id
              ? payload.client_id : principal.subject),
          scopes,
          expiresAt: payload.exp,
          // issuer et audience exacts sont déjà validés par jose.
          resource: new URL(settings.resource),
          extra: { principal }
        };
      } catch {
        // Aucun détail JWT, identifiant utilisateur ou org ne sort sur HTTP.
        throw new OAuthError(OAuthErrorCode.InvalidToken, 'Invalid access token');
      }
    }
  };
}
