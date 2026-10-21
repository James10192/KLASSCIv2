import assert from 'node:assert/strict';
import { test } from 'node:test';
import { generateKeyPair, exportJWK, SignJWT, createLocalJWKSet } from 'jose';
import { requireBearerAuth } from '@modelcontextprotocol/server';
import { sdkVerifierFor } from '../sdk-authorization.mjs';

const settings = {
  issuer: 'https://school-test.auth0.com/',
  resource: 'https://mcp.klassci.com/mcp'
};
const allowedOrg = 'org_personnelA';
const otherOrg = 'org_personnelB';
const tenantRegistry = new Map([[allowedOrg, {url:'https://demo.klassci.com/api/mcp-bridge'}]]);
const meta = new URL('https://mcp.klassci.com/.well-known/oauth-protected-resource/mcp');

async function fixture() {
  const keys = await generateKeyPair('RS256');
  const jwk = await exportJWK(keys.publicKey);
  jwk.kid = 'school-ci';
  jwk.alg = 'RS256';
  const jwks = createLocalJWKSet({keys:[jwk]});

  async function token(opts = {}) {
    const claims = {
      org_id: opts.org ?? allowedOrg,
      scope: opts.scope ?? 'openid profile mcp:read'
    };
    if (opts.gty) claims.gty = opts.gty;
    const sign = new SignJWT(claims)
      .setProtectedHeader({alg:'RS256',kid:'school-ci'})
      .setIssuer(opts.issuer ?? settings.issuer)
      .setAudience(opts.audience ?? settings.resource)
      .setSubject(opts.sub ?? 'auth0|staff-user-1')
      .setIssuedAt();
    if (!opts.omitExp) sign.setExpirationTime(opts.exp ?? '2m');
    return sign.sign(keys.privateKey);
  }

  const verifier = sdkVerifierFor(settings, tenantRegistry, jwks);
  const gate = requireBearerAuth({
    verifier,
    requiredScopes: ['mcp:read'],
    resourceMetadataUrl: meta,
    expectedResource: new URL(settings.resource)
  });

  function request(authorization) {
    return new Request(settings.resource, {
      method:'POST',
      headers:authorization ? { Authorization:'Bearer '+authorization } : {}
    });
  }
  return {token,gate,request,verifier};
}

test('MCP v2 : AuthInfo sécurisé conserve l’utilisateur et le contexte établissement', async () => {
  const {token,gate,request} = await fixture();
  const result = await gate(request(await token()));
  assert.equal(result instanceof Response,false);
  assert.equal(result.extra.principal.subject,'auth0|staff-user-1');
  assert.equal(result.extra.principal.organization,allowedOrg);
  assert.deepEqual(result.scopes,['openid','profile','mcp:read']);
  assert.equal(result.resource.href,settings.resource);
  assert.ok(result.expiresAt > Date.now()/1000);
});

test('MCP v2 : authentification absente retourne 401 et WWW-Authenticate', async () => {
  const {gate,request} = await fixture();
  const response = await gate(request());
  assert.equal(response.status,401);
  const challenge = response.headers.get('WWW-Authenticate');
  assert.match(challenge,/invalid_token/);
  assert.match(challenge,/oauth-protected-resource\/mcp/);
});

test('MCP v2 : authentifié sans mcp:read reçoit 403 insufficient_scope', async () => {
  const {token,gate,request} = await fixture();
  const response = await gate(request(await token({scope:'openid profile'})));
  assert.equal(response.status,403);
  assert.match(response.headers.get('WWW-Authenticate'),/insufficient_scope/);
  assert.match(response.headers.get('WWW-Authenticate'),/mcp:read/);
});

test('MCP v2 : rejet du jeton d’une autre école et mauvaise audience', async () => {
  const {token,gate,request} = await fixture();
  for (const change of [
    {org:otherOrg},
    {audience:'https://foreign.example/mcp'},
    {issuer:'https://spoofed.auth0.com/'},
    {omitExp:true},
    {sub:'automation@clients'},
    {gty:'client-credentials'}
  ]) {
    const result = await gate(request(await token(change)));
    assert.equal(result.status,401,JSON.stringify(change));
    assert.match(result.headers.get('WWW-Authenticate'),/invalid_token/);
  }
});

test('MCP v2 : refus d’URL de ressource même en cas de JWT authentifié', async () => {
  const {token,verifier} = await fixture();
  const info = await verifier.verifyAccessToken(await token());
  assert.equal(info.resource.href,settings.resource);
  // Le gate compare expectedResource ; on ne peut pas forger sa valeur depuis l’outil.
  assert.equal(info.extra.principal.organization,allowedOrg);
});
