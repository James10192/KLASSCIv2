import assert from 'node:assert/strict';
import { test } from 'node:test';
import { generateKeyPair, exportJWK, SignJWT, createLocalJWKSet } from 'jose';

import { authenticate, checkedPrincipal, tenantRegistry, requireConfiguration, AccessDenied } from '../security.mjs';
import { signedBody, callSchoolBridge, readBoundedJson, SchoolIdentityNotLinked } from '../bridge.mjs';

const settings = {
  issuer: 'https://example.auth0.com/',
  resource: 'https://mcp.klassci.com/mcp'
};
const orgA = 'org_aaaaaaaa';
const orgB = 'org_bbbbbbbb';
const secret = 'c'.repeat(64);
const sampleTenants = [
  { organization: orgA, bridgeUrl: 'https://usat.klassci.com/api/mcp-bridge', secretEnv: 'MCP_BRIDGE_SECRET_TEST' }
];

test('configuration refuse les schémas HTTP et ressources incorrectes', () => {
  assert.throws(() => requireConfiguration({ MCP_OAUTH_ISSUER: 'http://auth.test/', MCP_RESOURCE_URL: settings.resource }));
  assert.throws(() => requireConfiguration({ MCP_OAUTH_ISSUER: settings.issuer, MCP_RESOURCE_URL: 'https://mcp.klassci.com/other' }));
  assert.deepEqual(requireConfiguration({MCP_OAUTH_ISSUER:settings.issuer,MCP_RESOURCE_URL:settings.resource}), settings);
});

test('OAuth : un JWT valide exige audience, issuer, scope et organisation corrects', async () => {
  const pair = await generateKeyPair('RS256');
  const jwk = await exportJWK(pair.publicKey);
  jwk.kid = 'test-key';
  jwk.alg = 'RS256';
  const jwks = createLocalJWKSet({ keys: [jwk] });
  const jwt = (extra = {}, overrides = {}) => new SignJWT({
    org_id: orgA, scope: 'mcp:read', ...extra
  }).setProtectedHeader({ alg: 'RS256', kid: 'test-key' })
    .setIssuer(overrides.issuer ?? settings.issuer)
    .setAudience(overrides.audience ?? settings.resource)
    .setSubject('auth0|staff-1')
    .setIssuedAt()
    .setExpirationTime('2m')
    .sign(pair.privateKey);

  const bearer = 'Bearer ' + await jwt();
  assert.deepEqual(await authenticate(bearer, settings, jwks), {subject:'auth0|staff-1',organization:orgA});
  await assert.rejects(() => authenticate('', settings, jwks), AccessDenied);
  const noScope = await jwt({scope:'openid'});
  await assert.rejects(() => authenticate('Bearer ' + noScope, settings, jwks), AccessDenied);
  const noOrg = await jwt({org_id:undefined});
  await assert.rejects(() => authenticate('Bearer ' + noOrg, settings, jwks), AccessDenied);
  const wrongAudience = await jwt({}, {audience:'https://other.com'});
  await assert.rejects(() => authenticate('Bearer ' + wrongAudience, settings, jwks), AccessDenied);
  const wrongIssuer = await jwt({}, {issuer:'https://evil.test/'});
  await assert.rejects(() => authenticate('Bearer ' + wrongIssuer, settings, jwks), AccessDenied);
});

test('un utilisateur sans org_id ou sans mcp:read n’est jamais admis', () => {
  assert.throws(() => checkedPrincipal({sub:'user-1', scope:'mcp:read'}), AccessDenied);
  assert.throws(() => checkedPrincipal({sub:'user-1', org_id:orgA, scope:'profile'}), AccessDenied);
  assert.throws(() => checkedPrincipal({sub:'machine@clients', org_id:orgA, scope:'mcp:read'}), AccessDenied);
  assert.throws(() => checkedPrincipal({sub:'service-1', org_id:orgA, scope:'mcp:read', gty:'client-credentials'}), AccessDenied);
});

test('registry refuse tenant arbitraire, doublon, URL non HTTPS, host extérieur et secret faible', () => {
  const env = {MCP_BRIDGE_SECRET_TEST:secret};
  assert.equal(tenantRegistry(JSON.stringify(sampleTenants),env).get(orgA).url,
    'https://usat.klassci.com/api/mcp-bridge');
  assert.throws(() => tenantRegistry(JSON.stringify([...sampleTenants,...sampleTenants]),env));
  assert.throws(() => tenantRegistry(JSON.stringify([{...sampleTenants[0],bridgeUrl:'http://usat.klassci.com/api/mcp-bridge'}]),env));
  assert.throws(() => tenantRegistry(JSON.stringify([{...sampleTenants[0],bridgeUrl:'https://attacker.com/api/mcp-bridge'}]),env));
  assert.throws(() => tenantRegistry(JSON.stringify([{...sampleTenants[0],bridgeUrl:'https://localhost/api/mcp-bridge'}]),env));
  assert.throws(() => tenantRegistry(JSON.stringify(sampleTenants), {MCP_BRIDGE_SECRET_TEST:'weak'}));
});

test('un JWT du tenant B ne peut jamais appeler A même en demandant A', async () => {
  const registry = tenantRegistry(JSON.stringify(sampleTenants),{MCP_BRIDGE_SECRET_TEST:secret});
  let called = false;
  await assert.rejects(() => callSchoolBridge(
    {subject:'auth0|staff-1', organization:orgB}, registry, settings.issuer,
    'search_classes', { name:'L1' }, async () => { called = true; }
  ));
  assert.equal(called, false);
});

test('pont HMAC : identité/organisation proviennent du jeton, pas des paramètres outil', async () => {
  const registry = tenantRegistry(JSON.stringify(sampleTenants),{MCP_BRIDGE_SECRET_TEST:secret});
  const user = {subject:'auth0|staff-1',organization:orgA};
  let requested;
  const result = await callSchoolBridge(user,registry,settings.issuer,'search_classes',
    {name:'L1',organization:orgB,subject:'intruder'},async (url,opts)=>{
      requested = {url,opts};
      return Response.json({results:[{nom:'L1'}]});
    });
  assert.equal(requested.url,'https://usat.klassci.com/api/mcp-bridge');
  assert.equal(requested.opts.redirect,'error');
  assert.equal(requested.opts.headers.Authorization,undefined);
  assert.deepEqual(result,{results:[{nom:'L1'}]});
  const parsed = JSON.parse(requested.opts.body);
  assert.equal(parsed.organization,orgA);
  assert.equal(parsed.subject,user.subject);
  assert.equal(parsed.arguments.organization,orgB); // argument ignoré pour la sélection du tenant
  assert.equal(requested.opts.headers['X-Klassci-Mcp-Signature'].length,64);
  assert.throws(() => signedBody(user,settings.issuer,'proposer_creation_classes',{},secret));
});

test('bootstrap réservé au motif explicite jamais lié, sans ouvrir un compte révoqué', async () => {
  const registry = tenantRegistry(JSON.stringify(sampleTenants), {MCP_BRIDGE_SECRET_TEST:secret});
  const principal = {subject:'auth0|staff-1', organization:orgA};
  let count=0;
  const fetcher = async (_url, opts) => {
    count++;
    assert.equal(JSON.parse(opts.body).tool, 'list_authorized_tools');
    if (count===1) return Response.json({error:'Accès refusé.', code:'identity_not_linked'}, {status:403});
    if (count===2) return Response.json({error:'Accès refusé.'}, {status:403}); // révoqué
    if (count===3) return Response.json({error:'Appairage indisponible.'}, {status:403});
    return Response.json({error:'Accès refusé.',code:'identity_not_linked'}, {status:401});
  };

  await assert.rejects(
    () => callSchoolBridge(principal,registry,settings.issuer,'list_authorized_tools',{},fetcher),
    SchoolIdentityNotLinked
  );
  for (let n=0;n<3;n++) {
    await assert.rejects(
      () => callSchoolBridge(principal,registry,settings.issuer,'list_authorized_tools',{},fetcher),
      (error) => !(error instanceof SchoolIdentityNotLinked)
    );
  }
  assert.equal(count,4);
});

test('réponses scolaires >128KiB rejetées avec ou sans Content-Length', async () => {
  const registry = tenantRegistry(JSON.stringify(sampleTenants), {MCP_BRIDGE_SECRET_TEST:secret});
  const principal = {subject:'auth0|staff-1', organization:orgA};
  const oversized = JSON.stringify({results:[{nom:'X'.repeat(140000)}]});
  for (const headers of [{}, {'Content-Length':String(Buffer.byteLength(oversized))}]) {
    await assert.rejects(
      () => callSchoolBridge(principal,registry,settings.issuer,'search_classes',{},
        async () => new Response(oversized,{headers})),
      /volumineuse/
    );
  }
  const exactlySmall = Response.json({tools:['search_classes']});
  assert.deepEqual(await readBoundedJson(exactlySmall,4096), {tools:['search_classes']});
  await assert.rejects(() => readBoundedJson(Response.json({results:[]}), 5), /volumineuse/);
});

test('refus très volumineux ou JSON erroné ne peuvent déclencher l’appairage', async () => {
  const registry = tenantRegistry(JSON.stringify(sampleTenants), {MCP_BRIDGE_SECRET_TEST:secret});
  const principal = {subject:'auth0|staff-1', organization:orgA};
  for (const denial of [
    new Response(JSON.stringify({code:'identity_not_linked',pad:'X'.repeat(7000)}),{status:403}),
    new Response('{broken', {status:403})
  ]) {
    await assert.rejects(
      () => callSchoolBridge(principal,registry,settings.issuer,'list_authorized_tools',{},
        async () => denial),
      (error) => !(error instanceof SchoolIdentityNotLinked)
    );
  }
});
