import { createMcpExpressApp } from '@modelcontextprotocol/express';
import { toNodeHandler } from '@modelcontextprotocol/node';
import { createMcpHandler, McpServer } from '@modelcontextprotocol/server';
import * as z from 'zod/v4';

import { AccessDenied, requireConfiguration, tenantRegistry, verifierFor } from './security.mjs';
import { callSchoolBridge } from './bridge.mjs';

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
    'Bearer resource_metadata="' + new URL(metadataPath, settings.resource).href + '", scope="mcp:read"');
  return res.status(401).json({ error: 'unauthorized' });
}

// Pas de session partagée : une instance McpServer et un principal par requête.
function createSchoolServer(principal) {
  const mcp = new McpServer({ name: 'klassci-nanan-connect', version: '0.1.0' });

  const read = async (name, args) => {
    try {
      const result = await callSchoolBridge(principal, tenants, settings.issuer, name, args);
      return {
        content: [{ type: 'text', text: JSON.stringify(result) }],
        structuredContent: result
      };
    } catch {
      // Ne jamais divulguer le secret du tenant, un JWT, ni l'URL du backend.
      return { isError: true, content: [{ type: 'text', text: 'Lecture indisponible ou refusée par KLASSCI.' }] };
    }
  };

  mcp.registerTool('search_classes', {
    description: 'Consulter les classes accessibles, les capacités et les effectifs de votre établissement KLASSCI.',
    annotations: { readOnlyHint: true, destructiveHint: false },
    inputSchema: z.object({
      name: z.string().max(120).optional(),
      filiere: z.string().max(120).optional(),
      niveau: z.string().max(120).optional(),
      systeme: z.enum(['BTS', 'LMD']).optional(),
      active_only: z.boolean().optional(),
      has_places: z.boolean().optional(),
      limit: z.number().int().min(1).max(50).optional()
    })
  }, async (args) => read('search_classes', args));

  mcp.registerTool('get_dashboard_kpis', {
    description: 'Lire les indicateurs agrégés autorisés du tableau de bord KLASSCI ; les chiffres financiers exigent des droits financiers.',
    annotations: { readOnlyHint: true, destructiveHint: false },
    inputSchema: z.object({
      focus: z.enum(['general', 'academic', 'attendance', 'finance']).optional()
    })
  }, async (args) => read('get_dashboard_kpis', args));

  return mcp;
}

app.all('/mcp', async (req, res) => {
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
    const handler = createMcpHandler(() => createSchoolServer(principal));
    await toNodeHandler(handler)(req, res, req.body);
  } catch {
    if (!res.headersSent) res.status(500).json({ error: 'mcp_unavailable' });
  }
});

const port = Number(process.env.PORT || 3200);
if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('PORT invalide');
app.listen(port, '0.0.0.0', () => {
  console.info('Nanan Connect MCP actif : point HTTPS obligatoire en frontal.');
});
