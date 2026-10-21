import { createMcpExpressApp, requireBearerAuth } from '@modelcontextprotocol/express';
import { toNodeHandler } from '@modelcontextprotocol/node';
import { createMcpHandler } from '@modelcontextprotocol/server';
import { createSchoolServer } from './server-factory.mjs';

import { requireConfiguration, tenantRegistry } from './security.mjs';
import { sdkVerifierFor } from './sdk-authorization.mjs';

const settings = requireConfiguration();
const tenants = tenantRegistry(process.env.MCP_TENANTS_JSON);
const publicHost = new URL(settings.resource).hostname;
const app = createMcpExpressApp({ host: '0.0.0.0', allowedHosts: [publicHost] });

const metadataPath = '/.well-known/oauth-protected-resource/mcp';
const resourceMetadata = Object.freeze({
  resource: settings.resource,
  authorization_servers: [settings.issuer],
  scopes_supported: ['mcp:read']
});

app.get(metadataPath, (_req, res) => res.set('Cache-Control', 'public, max-age=300').json(resourceMetadata));
app.get('/.well-known/oauth-protected-resource', (_req, res) => res.set('Cache-Control', 'public, max-age=300').json(resourceMetadata));
app.get('/healthz', (_req, res) => res.status(200).json({ status: 'ok' }));

// Le SDK répond 401 invalid_token, ou 403 insufficient_scope, avec le
// WWW-Authenticate normalisé que ChatGPT comprend. Pas de gate maison.
const resourceMetadataUrl = new URL(metadataPath, settings.resource).href;
const oauthGate = requireBearerAuth({
  verifier: sdkVerifierFor(settings, tenants),
  requiredScopes: ['mcp:read'],
  expectedResource: new URL(settings.resource),
  resourceMetadataUrl
});

// Un handler persistant mais une instance métier par requête.
// Le SDK transmet authInfo depuis le gate, sans variable globale de session.
const protocol = createMcpHandler((ctx) => {
  const principal = ctx.authInfo?.extra?.principal;
  if (!principal || typeof principal.subject !== 'string'
    || typeof principal.organization !== 'string'
    || !tenants.has(principal.organization)) {
    throw new Error('Authenticated principal not available');
  }
  return createSchoolServer(principal, { tenants, issuer: settings.issuer });
}, { legacy: 'reject' });
const serve = toNodeHandler(protocol);

app.post('/mcp', oauthGate, async (req, res) => {
  try {
    await serve(req, res, req.body);
  } catch {
    // N'exposer aucun détail interne, JWT ou erreur du tenant.
    if (!res.headersSent) {
      res.status(500).set('Cache-Control', 'no-store').json({ error: 'mcp_unavailable' });
    } else {
      res.end();
    }
  }
});

// MCP 2026-07-28 : aucun GET ou DELETE de session hérité.
app.all('/mcp', (_req, res) => res.set('Allow', 'POST').status(405).end());

const port = Number(process.env.PORT || 3200);
if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('PORT invalide');
app.listen(port, '0.0.0.0', () => {
  console.info('Nanan Connect MCP actif : point HTTPS obligatoire en frontal.');
});
