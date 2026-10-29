import { requireConfiguration } from './security.mjs';
import { discoverAuthorizationServer } from './oauth-discovery.mjs';

try {
  const { issuer, resource } = requireConfiguration();
  const info = await discoverAuthorizationServer(issuer, fetch, {
    preRegisteredClient: Boolean(process.env.MCP_OAUTH_PRE_REGISTERED_CLIENT_ID)
  });
  console.log(JSON.stringify({
    readiness: 'metadata-compatible',
    issuer: info.issuer,
    resource,
    authorizationEndpoint: info.authorizationEndpoint,
    tokenEndpoint: info.tokenEndpoint,
    clientIdentity: info.clientIdentity,
    pkce: info.pkce,
    scopesAdvertised: info.scopesAdvertised,
    jwksUri: info.jwks ? 'discovered' : 'not-advertised',
    note: 'Métadonnées uniquement : PKCE interactif, consentement et ChatGPT non testés.'
  },null,2));
} catch (error) {
  console.error('MCP OAuth readiness FAIL:',error.message);
  process.exitCode = 1;
}
