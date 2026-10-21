import { createRemoteJWKSet, jwtVerify } from 'jose';

export class AccessDenied extends Error {
  constructor(message = 'Accès refusé') { super(message); this.name = 'AccessDenied'; }
}

export function requireConfiguration(env = process.env) {
  const issuer = env.MCP_OAUTH_ISSUER;
  const resource = env.MCP_RESOURCE_URL;
  if (!issuer || !resource) throw new Error('MCP_OAUTH_ISSUER et MCP_RESOURCE_URL requis');
  const iss = new URL(issuer);
  const res = new URL(resource);
  if (iss.protocol !== 'https:' || iss.search || iss.hash || res.protocol !== 'https:'
    || res.pathname !== '/mcp' || res.search || res.hash || res.port
    || res.username || res.password) throw new Error('Configuration OAuth invalide');
  // L'identité d'issuer correspond aux JWT sans normalisation silencieuse.
  const expectedIssuer = iss.href;
  if (expectedIssuer !== issuer) throw new Error('Issuer doit être canonique');

  return { issuer: expectedIssuer, resource: res.href };
}

export function checkedPrincipal(claims) {
  if (typeof claims.sub !== 'string' || claims.sub.length < 1 || claims.sub.length > 255) {
    throw new AccessDenied();
  }
  // Un jeton sans organisation Auth0 n'est pas un jeton d'établissement.
  if (typeof claims.org_id !== 'string' || !/^org_[a-zA-Z0-9]{3,128}$/.test(claims.org_id)) {
    throw new AccessDenied();
  }
  if (typeof claims.scope !== 'string' || !claims.scope.split(/\s+/).includes('mcp:read')) {
    throw new AccessDenied();
  }
  return Object.freeze({ subject: claims.sub, organization: claims.org_id });
}

export async function authenticate(header, settings, jwks) {
  if (typeof header !== 'string' || !/^Bearer [^\s]+$/.test(header)) {
    throw new AccessDenied();
  }
  try {
    const { payload } = await jwtVerify(header.slice(7), jwks, {
      issuer: settings.issuer,
      audience: settings.resource,
      algorithms: ['RS256'],
      clockTolerance: '5s'
    });
    return checkedPrincipal(payload);
  } catch {
    throw new AccessDenied();
  }
}

export function verifierFor(settings) {
  const jwks = createRemoteJWKSet(new URL('.well-known/jwks.json', settings.issuer));
  return (header) => authenticate(header, settings, jwks);
}

export function tenantRegistry(raw, env = process.env) {
  const entries = JSON.parse(raw || '[]');
  if (!Array.isArray(entries) || entries.length < 1) throw new Error('Aucun établissement MCP provisionné');
  const registry = new Map();
  for (const entry of entries) {
    const { organization, bridgeUrl, secretEnv } = entry;
    if (typeof organization !== 'string' || !/^org_[a-zA-Z0-9]{3,128}$/.test(organization)
      || registry.has(organization) || typeof secretEnv !== 'string'
      || !/^MCP_BRIDGE_SECRET_[A-Z0-9_]+$/.test(secretEnv)) throw new Error('Registry MCP invalide');
    const url = new URL(bridgeUrl);
    // Pilote limité aux sous-domaines officiels, aucune IP, chemin libre ou redirection.
    if (url.protocol !== 'https:' || !url.hostname.endsWith('.klassci.com')
      || url.hostname === '.klassci.com' || url.pathname !== '/api/mcp-bridge'
      || url.port || url.username || url.password || url.search || url.hash)
      throw new Error('URL de tenant interdite');
    const secret = env[secretEnv];
    if (typeof secret !== 'string' || Buffer.byteLength(secret) < 32) throw new Error('Secret MCP manquant');
    registry.set(organization, Object.freeze({ url: url.href, secret }));
  }
  return registry;
}
