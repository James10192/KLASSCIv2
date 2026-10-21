import { createMcpExpressApp } from '@modelcontextprotocol/express';
import { toNodeHandler } from '@modelcontextprotocol/node';
import { createMcpHandler } from '@modelcontextprotocol/server';
import { createSchoolServer } from './server-factory.mjs';

import { AccessDenied, requireConfiguration, tenantRegistry, verifierFor } from './security.mjs';

const settings = requireConfiguration();
const tenants = tenantRegistry(process.env.MCP_TENANTS_JSON);
const verify = verifierFor(settings);
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

function challenge(res) {
  res.set('Cache-Control', 'no-store');
  res.set('WWW-Authenticate',
    'Bearer resource_metadata="' + new URL(metadataPath, settings.resource).href + '", scope="mcp:read", error="invalid_token", error_description="OAuth access token required"');
  return res.status(401).json({ error: 'unauthorized' });
}

// La factory crée une session sans état, bornée à l'identité OAuth vérifiée.
app.post('/mcp', async (req, res) => {
  let principal;
  try {
    principal = await verify(req.headers.authorization);
    if (!tenants.has(principal.organization)) throw new AccessDenied();
  } catch {
    return challenge(res);
  }

  // Le SDK 2026 utilise Streamable HTTP ; le handler reçoit le body
  // déjà analysé par createMcpExpressApp.
  try {
    const handler = createMcpHandler(
      () => createSchoolServer(principal, { tenants, issuer: settings.issuer }),
      { legacy: 'reject' }
    );
    await toNodeHandler(handler)(req, res, req.body);
  } catch {
    if (!res.headersSent) res.status(500).json({ error: 'mcp_unavailable' });
  }
});

// MCP 2026-07-28 : aucun GET ou DELETE de session hérité.
app.all('/mcp', (_req, res) => res.set('Allow', 'POST').status(405).end());

const port = Number(process.env.PORT || 3200);
if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('PORT invalide');
app.listen(port, '0.0.0.0', () => {
  console.info('Nanan Connect MCP actif : point HTTPS obligatoire en frontal.');
});
