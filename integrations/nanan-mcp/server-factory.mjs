import { McpServer } from '@modelcontextprotocol/server';
import * as z from 'zod/v4';
import { callSchoolBridge } from './bridge.mjs';

/**
 * Un serveur et un utilisateur par appel HTTP.
 * Aucune variable globale ne doit contenir le principal du dernier appel.
 */
export function createSchoolServer(principal, { tenants, issuer, authorizedTools, call = callSchoolBridge }) {
  // Cette liste provient exclusivement de Laravel APRES preuve signée et
  // résolution du compte local. Aucun paramètre modèle ne choisit ces droits.
  if (!Array.isArray(authorizedTools)
    || authorizedTools.some((name) => typeof name !== 'string')) {
    throw new Error('Liste de permissions de l’établissement indisponible');
  }
  const allowed = new Set(authorizedTools);
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

  if (allowed.has('search_classes')) mcp.registerTool('search_classes', {
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

  if (allowed.has('get_dashboard_kpis')) mcp.registerTool('get_dashboard_kpis', {
    description: 'Lire les indicateurs agrégés autorisés du tableau de bord KLASSCI ; les chiffres financiers exigent des droits financiers.',
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: false },
    securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }],
    // SDK v2 ne sérialise pas toujours le champ top-level : miroir compatible.
    _meta: { securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }] },
    inputSchema: z.object({
      focus: z.enum(['general', 'academic', 'attendance', 'finance']).optional()
    })
  }, async (args) => read('get_dashboard_kpis', args));

  // OpenAI utilise ce profil pour différencier les comptes KLASSCI connectés.
  // La réponse est récupérée dans Laravel à partir d'un compte VÉRIFIÉ.
  if (allowed.has('get_connected_profile')) mcp.registerTool('get_connected_profile', {
    description: 'Identifier le compte KLASSCI réellement connecté ; aucune recherche de données scolaires.',
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: false },
    securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }],
    _meta: {
      securitySchemes: [{ type: 'oauth2', scopes: ['mcp:read'] }],
      'openai/profile': true
    },
    inputSchema: z.object({}),
    outputSchema: z.object({ id: z.string(), name: z.string() })
  }, async () => read('get_connected_profile', {}));

  return mcp;
}
