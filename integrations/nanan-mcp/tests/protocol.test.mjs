import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createMcpHandler } from '@modelcontextprotocol/server';
import { createSchoolServer } from '../server-factory.mjs';

const caller = Object.freeze({ subject: 'auth0|test-teacher', organization: 'org_aaaaaa' });

function request(method, params = undefined) {
  return new Request('https://mcp.klassci.com/mcp', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/event-stream',
      'Content-Type': 'application/json',
      'MCP-Protocol-Version': '2025-11-25'
    },
    body: JSON.stringify({ jsonrpc: '2.0', id: 17, method, ...(params ? { params } : {}) })
  });
}

test('SDK MCP expose trois outils en lecture seule, dont le profil, et aucun CRUD', async () => {
  const handler = createMcpHandler(() => createSchoolServer(caller, {
    tenants: new Map(),
    issuer: 'https://tenant.auth0.com/',
    call: async () => ({ results: [] })
  }));
  try {
    const response = await handler.fetch(request('tools/list'));
    const raw = await response.text();
    assert.equal(response.status, 200, raw);
    assert.match(raw, /search_classes/);
    assert.match(raw, /get_dashboard_kpis/);
    assert.match(raw, /get_connected_profile/);
    assert.match(raw, /openai\\/profile/);
    assert.doesNotMatch(raw, /proposer_creation_classes/);
    assert.doesNotMatch(raw, /paiements\/annuler/);
    assert.match(raw, /mcp:read/);
  } finally {
    await handler.close();
  }
});

test('outil profil résout une identité locale, non fournie par le modèle', async () => {
  const handler = createMcpHandler(() => createSchoolServer(caller, {
    tenants: new Map(),
    issuer: 'https://tenant.auth0.com/',
    call: async (principal, registry, issuer, tool, args) => {
      assert.equal(principal, caller);
      assert.equal(tool, 'get_connected_profile');
      assert.deepEqual(args, {});
      return { id: 'kl_identity1', name: 'Compte de démonstration' };
    }
  }), { legacy: 'reject' });
  try {
    const response = await handler.fetch(request('tools/call', {
      name: 'get_connected_profile', arguments: {}
    }));
    const raw = await response.text();
    assert.equal(response.status, 200, raw);
    assert.match(raw, /kl_identity1/);
    assert.match(raw, /Compte de démonstration/);
  } finally {
    await handler.close();
  }
});

test('SDK MCP transmet seulement les filtres de lecture au pont interne', async () => {
  const called = [];
  const handler = createMcpHandler(() => createSchoolServer(caller, {
    tenants: new Map(),
    issuer: 'https://tenant.auth0.com/',
    call: async (principal, registry, issuer, tool, params) => {
      called.push({ principal, issuer, tool, params });
      return { results: [{ nom: 'L1' }], count: 1 };
    }
  }));
  try {
    const response = await handler.fetch(request('tools/call', {
      name: 'search_classes', arguments: { name: 'L1', limit: 1 }
    }));
    const raw = await response.text();
    assert.equal(response.status, 200, raw);
    assert.match(raw, /L1/);
    assert.equal(called.length, 1);
    assert.equal(called[0].principal, caller);
    assert.equal(called[0].tool, 'search_classes');
    assert.deepEqual(called[0].params, { name: 'L1', limit: 1 });
  } finally {
    await handler.close();
  }
});
