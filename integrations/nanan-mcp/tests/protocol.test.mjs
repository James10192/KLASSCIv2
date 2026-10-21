import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createMcpHandler } from '@modelcontextprotocol/server';
import { createSchoolServer, resolveAuthorizedTools } from '../server-factory.mjs';
import { SchoolIdentityNotLinked } from '../bridge.mjs';

const caller = Object.freeze({ subject: 'auth0|test-teacher', organization: 'org_aaaaaa' });

function request(method, params = undefined) {
  const modern = {
    ...(params || {}),
    _meta: {
      'io.modelcontextprotocol/protocolVersion': '2026-07-28',
      'io.modelcontextprotocol/clientInfo': { name: 'nanan-test', version: '1.0' },
      'io.modelcontextprotocol/clientCapabilities': {}
    }
  };
  const headers = {
    Accept: 'application/json, text/event-stream',
    'Content-Type': 'application/json',
    'MCP-Protocol-Version': '2026-07-28',
    'Mcp-Method': method
  };
  if (method === 'tools/call') headers['Mcp-Name'] = params.name;

  return new Request('https://mcp.klassci.com/mcp', {
    method: 'POST',
    headers,
    body: JSON.stringify({ jsonrpc: '2.0', id: 17, method, params: modern })
  });
}

test('SDK MCP expose trois outils en lecture seule, dont le profil, et aucun CRUD', async () => {
  const handler = createMcpHandler(() => createSchoolServer(caller, {
    tenants: new Map(),
    authorizedTools: ['search_classes', 'get_dashboard_kpis', 'get_connected_profile'],
    issuer: 'https://tenant.auth0.com/',
    call: async () => ({ results: [] })
  }), { legacy: 'reject' });
  try {
    const response = await handler.fetch(request('tools/list'));
    const raw = await response.text();
    assert.equal(response.status, 200, raw);
    assert.match(raw, /search_classes/);
    assert.match(raw, /get_dashboard_kpis/);
    assert.match(raw, /get_connected_profile/);
    assert.match(raw, /openai\/profile/);
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
    authorizedTools: ['search_classes', 'get_dashboard_kpis', 'get_connected_profile'],
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
    authorizedTools: ['search_classes', 'get_dashboard_kpis', 'get_connected_profile'],
    issuer: 'https://tenant.auth0.com/',
    call: async (principal, registry, issuer, tool, params) => {
      called.push({ principal, issuer, tool, params });
      return { results: [{ nom: 'L1' }], count: 1 };
    }
  }), { legacy: 'reject' });
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

test('SDK MCP masque un outil absent des permissions du compte local', async () => {
  const requests = [];
  const handler = createMcpHandler(() => createSchoolServer(caller, {
    tenants: new Map(),
    authorizedTools: ['search_classes', 'get_connected_profile'],
    issuer: 'https://tenant.auth0.com/',
    call: async (...parts) => {
      requests.push(parts);
      return {results: []};
    }
  }), { legacy: 'reject' });
  try {
    const list = await handler.fetch(request('tools/list'));
    const listed = await list.text();
    assert.equal(list.status, 200, listed);
    assert.match(listed, /search_classes/);
    assert.match(listed, /get_connected_profile/);
    assert.doesNotMatch(listed, /get_dashboard_kpis/);

    const denied = await handler.fetch(request('tools/call', {
      name:'get_dashboard_kpis', arguments: {focus: 'finance'}
    }));
    const text = await denied.text();
    assert.doesNotMatch(text, /total_paiements/);
    assert.equal(requests.length, 0);
  } finally {
    await handler.close();
  }
});

test('un catalogue absent ou mal typé ne peut pas ouvrir les outils', () => {
  assert.throws(() => createSchoolServer(caller, {
    tenants:new Map(),
    issuer:'https://tenant.auth0.com/',
    authorizedTools:undefined
  }));
  assert.throws(() => createSchoolServer(caller, {
    tenants:new Map(),
    issuer:'https://tenant.auth0.com/',
    authorizedTools:['search_classes', null]
  }));
});

test('une requête MCP relit la liste blanche Laravel sans autorisation héritée d’un autre utilisateur', async () => {
  const alice = { subject: 'auth0|alice', organization: 'org_aaaaaa' };
  const bob = { subject: 'auth0|bob', organization: 'org_aaaaaa' };
  const calls = [];
  const school = async (principal, tenants, issuer, tool, args) => {
    calls.push({ principal, tool, args });
    return principal.subject === 'auth0|alice'
      ? { tools: ['search_classes'] } : { tools: [] };
  };
  const dependencies = { tenants:new Map(), issuer:'https://tenant.auth0.com/', call: school };

  const aliceTools = await resolveAuthorizedTools(alice, dependencies);
  const bobTools = await resolveAuthorizedTools(bob, dependencies);
  assert.deepEqual(aliceTools, ['get_connected_profile', 'search_classes']);
  assert.deepEqual(bobTools, ['get_connected_profile']);
  assert.equal(calls.length, 2);
  assert.ok(calls.every((r) => r.tool === 'list_authorized_tools'));
  assert.ok(calls.every((r) => Object.keys(r.args).length === 0));
});

test('réponse de découverte de permissions inconnue ou indisponible = aucun outil', async () => {
  const base = {tenants:new Map(),issuer:'https://tenant.auth0.com/'};
  for (const payload of [
    null,
    { tools: 'search_classes' },
    { tools: ['search_classes', 'delete_all_students'] },
    { tools: ['search_classes', null] },
    { notTools: [] }
  ]) {
    await assert.rejects(() => resolveAuthorizedTools(caller, {
      ...base, call:async()=>payload
    }));
  }
  await assert.rejects(() => resolveAuthorizedTools(caller, {
    ...base, call:async()=>{throw new Error('bridge refused')}
  }));
});

test('le SDK MCP supporte une factory asynchrone basée sur les permissions', async () => {
  const handler = createMcpHandler(async () => {
    const authorizedTools = await resolveAuthorizedTools(caller, {
      tenants: new Map(), issuer:'https://tenant.auth0.com/',
      call: async () => ({tools: ['search_classes']})
    });
    return createSchoolServer(caller, {
      tenants:new Map(), issuer:'https://tenant.auth0.com/', authorizedTools,
      call:async()=>({results:[]})
    });
  }, {legacy:'reject'});
  try {
    const result=await handler.fetch(request('tools/list'));
    const response=await result.text();
    assert.equal(result.status, 200, response);
    assert.match(response, /search_classes/);
    assert.match(response, /get_connected_profile/);
    assert.doesNotMatch(response, /get_dashboard_kpis/);
  } finally {
    await handler.close();
  }
});

test('l’identité OAuth non liée ne voit que le parcours de liaison lorsque le pilote est activé', async () => {
  const base = { tenants:new Map(), issuer:'https://demo.auth0.example/',
    call: async () => {throw new SchoolIdentityNotLinked();} };
  const onlyLink = await resolveAuthorizedTools(caller, {...base, pairingEnabled:true});
  assert.deepEqual(onlyLink, ['start_account_link']);
  await assert.rejects(() => resolveAuthorizedTools(caller, {...base, pairingEnabled:false}), SchoolIdentityNotLinked);
});

test('un code aléatoire est demandé au pont, et jamais dérivé d’un paramètre modèle', async () => {
  const calls=[];
  const tenant=new Map([[caller.organization,{
    url:'https://demonstration.klassci.com/api/mcp-bridge', secret:'x'.repeat(64)
  }]]);
  const handler=createMcpHandler(() => createSchoolServer(caller, {
    tenants:tenant, issuer:'https://demo.auth0.example/',
    authorizedTools:['start_account_link'],
    call:async (principal, registry, issuer,tool,args)=>{
      calls.push({principal,registry,issuer,tool,args});
      return {accepted:true,expires_in_seconds:600};
    }
  }), {legacy:'reject'});
  try {
    const listed=await (await handler.fetch(request('tools/list'))).text();
    assert.match(listed, /start_account_link/);
    assert.doesNotMatch(listed, /search_classes/);
    assert.doesNotMatch(listed, /get_connected_profile/);
    const result=await handler.fetch(request('tools/call',{
      name:'start_account_link', arguments:{}
    }));
    const raw=await result.text();
    assert.equal(result.status,200,raw);
    assert.equal(calls.length,1);
    assert.equal(calls[0].principal,caller);
    assert.equal(calls[0].tool,'create_pairing_challenge');
    assert.match(calls[0].args.code,/^[A-Za-z0-9_-]{43}$/);
    assert.match(raw,/https:\/\/demonstration\.klassci\.com\/nanan\/mcp\/connect/);
    assert.match(raw,/verification_code/);
    assert.match(raw,/expires_in_seconds/);
  } finally {
    await handler.close();
  }
});

test('échec du stockage du challenge : le code n’est pas retourné au client', async () => {
  const handler=createMcpHandler(() => createSchoolServer(caller, {
    tenants:new Map([[caller.organization,{url:'https://demo.klassci.com/api/mcp-bridge'}]]),
    issuer:'https://demo.auth0.example/',
    authorizedTools:['start_account_link'],
    call:async()=>{throw new Error('rate limited');}
  }), {legacy:'reject'});
  try {
    const result=await handler.fetch(request('tools/call',{
      name:'start_account_link',arguments:{}
    }));
    const raw=await result.text();
    assert.equal(result.status,200,raw);
    assert.match(raw,/La création du code/);
    assert.doesNotMatch(raw,/verification_code/);
  } finally {
    await handler.close();
  }
});
