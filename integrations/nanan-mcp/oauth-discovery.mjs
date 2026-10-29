/**
 * Contrat de compatibilité IdP <-> ChatGPT MCP.
 *
 * Aucun client OAuth n'est configuré ou créé par ce script. Il vérifie les
 * métadonnées officielles RFC 8414/OIDC avant le pilote.
 */

export function issuerMetadataUrl(issuer, document) {
  const url = new URL(issuer);
  if (url.protocol !== 'https:' || url.username || url.password || url.search || url.hash) {
    throw new Error('Issuer non HTTPS ou mal formé');
  }
  if (!['oauth-authorization-server', 'openid-configuration'].includes(document)) {
    throw new Error('Document de découverte non autorisé');
  }
  // RFC 8414 §3 : insérer .well-known entre le host et le path de l'issuer.
  const path = url.pathname.replace(/\/$/, '');
  return url.origin + '/.well-known/' + document + (path === '/' ? '' : path);
}

function httpsEndpoint(value, issuer) {
  if (typeof value !== 'string') throw new Error('Endpoint manquant');
  const url = new URL(value);
  // Ce pilote utilise un domaine Auth0 unique. Ne jamais suivre des URI
  // d'endpoints arbitraires obtenus d'un document de découverte compromis.
  if (url.protocol !== 'https:' || url.origin !== new URL(issuer).origin
      || url.username || url.password || url.hash) {
    throw new Error('Endpoint OAuth hors du domaine approuvé');
  }
  return url.href;
}

export function validateAuthorizationMetadata(metadata, issuer, opts = {}) {
  if (!metadata || typeof metadata !== 'object' || Array.isArray(metadata)
      || metadata.issuer !== issuer) {
    throw new Error('Issuer inattendu dans les métadonnées OAuth');
  }

  const authorizationEndpoint = httpsEndpoint(metadata.authorization_endpoint, issuer);
  const tokenEndpoint = httpsEndpoint(metadata.token_endpoint, issuer);
  const algorithms = metadata.code_challenge_methods_supported;
  if (!Array.isArray(algorithms) || !algorithms.includes('S256')) {
    throw new Error('PKCE S256 absent des métadonnées OAuth');
  }

  const methods = metadata.token_endpoint_auth_methods_supported;
  if (!Array.isArray(methods) || !methods.some((x) => ['none', 'private_key_jwt'].includes(x))) {
    throw new Error('Méthode token_endpoint incompatible CIMD/ChatGPT');
  }

  const clientIdentity = metadata.client_id_metadata_document_supported === true
    ? 'cimd'
    : (typeof metadata.registration_endpoint === 'string'
      ? 'dcr' : (opts.preRegisteredClient ? 'pre-registered' : null));

  if (!clientIdentity) {
    throw new Error('Pas de CIMD, DCR ou client pré-enregistré');
  }

  if (clientIdentity === 'dcr') {
    httpsEndpoint(metadata.registration_endpoint, issuer);
  }

  const jwks = metadata.jwks_uri ? httpsEndpoint(metadata.jwks_uri, issuer) : null;

  return Object.freeze({
    issuer,
    authorizationEndpoint,
    tokenEndpoint,
    jwks,
    clientIdentity,
    pkce: 'S256',
    scopesAdvertised: Array.isArray(metadata.scopes_supported)
      ? metadata.scopes_supported.includes('mcp:read') : null
  });
}

export async function discoverAuthorizationServer(
  issuer, transport = fetch, opts = {}
) {
  let lastError;
  for (const type of ['oauth-authorization-server', 'openid-configuration']) {
    try {
      const uri = issuerMetadataUrl(issuer, type);
      const response = await transport(uri, {
        method:'GET',
        headers:{ Accept:'application/json' },
        redirect:'error',
        signal: AbortSignal.timeout(5000)
      });
      if (!response.ok) throw new Error('Découverte indisponible');
      const raw = await response.text();
      if (raw.length > 65536) throw new Error('Document OAuth excessivement volumineux');
      const metadata = JSON.parse(raw);
      const parsed = validateAuthorizationMetadata(metadata, issuer, opts);
      return { ...parsed, source: uri };
    } catch (error) {
      lastError = error;
    }
  }
  throw new Error('Découverte OAuth incompatible : '+lastError?.message);
}
