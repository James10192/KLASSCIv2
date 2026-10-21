import { McpServer } from '@modelcontextprotocol/server';
import * as z from 'zod/v4';
import { callSchoolBridge } from './bridge.mjs';

/**
 * Un serveur et un utilisateur par appel HTTP.
 * Aucune variable globale ne doit contenir le principal du dernier appel.
 */
export function createSchoolServer(principal, { tenants, issuer, call = callSchoolBridge }) {
  const mcp = new McpServer({ name: 'klassci-nanan-connect', version: '0.1.0' });

  const read = async (name, args) => {
    try {
      const result = await call(principal, tenants, issuer, name, args);
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
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: false },
    securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }],
    // SDK v2 ne sérialise pas toujours le champ top-level : miroir compatible.
    _meta: { securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }] },
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
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: false },
    securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }],
    // SDK v2 ne sérialise pas toujours le champ top-level : miroir compatible.
    _meta: { securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }] },
    inputSchema: z.object({
      focus: z.enum(['general', 'academic', 'attendance', 'finance']).optional()
    })
  }, async (args) => read('get_dashboard_kpis', args));

  return mcp;
}
