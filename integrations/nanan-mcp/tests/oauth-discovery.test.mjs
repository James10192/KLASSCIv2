import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
  discoverAuthorizationServer,
  issuerMetadataUrl,
  validateAuthorizationMetadata
} from '../oauth-discovery.mjs';

const issuer='https://school-idp.example.com/';
const example = {
  issuer,
  authorization_endpoint:'https://school-idp.example.com/authorize',
  token_endpoint:'https://school-idp.example.com/oauth/token',
  jwks_uri:'https://school-idp.example.com/.well-known/jwks.json',
  code_challenge_methods_supported:['S256'],
  token_endpoint_auth_methods_supported:['none','private_key_jwt'],
  client_id_metadata_document_supported:true,
  scopes_supported:['openid','profile','mcp:read']
};

test('RFC 8414 : document de découverte respecte les issuers avec chemins', () => {
  assert.equal(issuerMetadataUrl(issuer,'oauth-authorization-server'),
    'https://school-idp.example.com/.well-known/oauth-authorization-server');
  assert.equal(issuerMetadataUrl('https://school-idp.example.com/tenant/',
    'oauth-authorization-server'),
    'https://school-idp.example.com/.well-known/oauth-authorization-server/tenant');
  assert.throws(() => issuerMetadataUrl('http://localhost:3000','openid-configuration'));
});

test('découverte compatible ChatGPT exige PKCE S256, CIMD et méthodes tokens', () => {
  const result=validateAuthorizationMetadata(example,issuer);
  assert.equal(result.clientIdentity,'cimd');
  assert.equal(result.pkce,'S256');
  assert.equal(result.scopesAdvertised,true);
  assert.ok(result.jwks.endsWith('jwks.json'));
  assert.throws(() => validateAuthorizationMetadata({...example,issuer:'https://spoofed.test/'},issuer));
  assert.throws(() => validateAuthorizationMetadata({...example,code_challenge_methods_supported:['plain']},issuer));
  assert.throws(() => validateAuthorizationMetadata({...example,token_endpoint_auth_methods_supported:['client_secret_basic']},issuer));
  assert.throws(() => validateAuthorizationMetadata({...example,token_endpoint:'https://foreign.test/oauth/token'},issuer));
  assert.throws(() => validateAuthorizationMetadata({...example,client_id_metadata_document_supported:false},issuer));
});

test('un fournisseur DCR contrôlé ou un client pré-enregistré est également accepté', () => {
  const dcr={...example,client_id_metadata_document_supported:false,
    registration_endpoint:'https://school-idp.example.com/oauth/register'};
  assert.equal(validateAuthorizationMetadata(dcr,issuer).clientIdentity,'dcr');
  delete dcr.registration_endpoint;
  assert.equal(validateAuthorizationMetadata(dcr,issuer,{preRegisteredClient:true}).clientIdentity,'pre-registered');
  assert.throws(() => validateAuthorizationMetadata(dcr,issuer));
});

test('contrôle CLI mocké ne suit pas les redirections et rejette les métadonnées arbitraires', async () => {
  let requested;
  const doc=await discoverAuthorizationServer(issuer,async (uri,options)=>{
    requested={uri,options};
    return {ok:true,text:async()=>JSON.stringify(example)};
  });
  assert.equal(doc.clientIdentity,'cimd');
  assert.equal(requested.options.redirect,'error');
  assert.ok(requested.uri.endsWith('oauth-authorization-server'));
  await assert.rejects(() => discoverAuthorizationServer(issuer,async()=>({
    ok:true,text:async()=>JSON.stringify({...example,issuer:'https://evil.test/'})
  })),/incompatible/);
});
