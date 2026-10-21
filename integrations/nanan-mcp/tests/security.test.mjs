import assert from 'node:assert/strict';
import { test } from 'node:test';
import { generateKeyPair, exportJWK, SignJWT, createLocalJWKSet } from 'jose';

import { authenticate, checkedPrincipal, tenantRegistry, requireConfiguration, AccessDenied } from '../security.mjs';
import { signedBody, callSchoolBridge } from '../bridge.mjs';

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
  await assert.rejects(() => authenticate('Bearer ' + await jwt({scope:'openid'}), settings, jwks), AccessDenied);
  await assert.rejects(() => authenticate('Bearer ' + await jwt({org_id:undefined}), settings, jwks), AccessDenied);
  await assert.rejects(() => authenticate('Bearer ' + await jwt({}, {audience:'https://other.com'}), settings, jwks), AccessDenied);
  await assert.rejects(() => authenticate('Bearer ' + await jwt({}, {issuer:'https://evil.test/'}), settings, jwks), AccessDenied);
});

test('un utilisateur sans org_id ou sans mcp:read n’est jamais admis', () => {
  assert.throws(() => checkedPrincipal({sub:'user-1', scope:'mcp:read'}), AccessDenied);
  assert.throws(() => checkedPrincipal({sub:'user-1', org_id:orgA, scope:'profile'}), AccessDenied);
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
      return {ok:true,json:async()=>({results:[{nom:'L1'}]})};
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
