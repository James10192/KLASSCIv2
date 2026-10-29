import assert from 'node:assert/strict';
import { test } from 'node:test';
import express from 'express';
import { requireBearerAuth } from '@modelcontextprotocol/express';

// CVE-2026-111771: le SDK principal et le middleware Express ont besoin
// de versions corrigées ENSEMBLE. Contrôler le middleware réellement utilisé
// en HTTP et pas seulement la version web-standard déjà testée ailleurs.
test('Express bearer gate refuse aud de tout autre serveur MCP', async (t) => {
  const app = express();
  const verifier = {
    async verifyAccessToken(token) {
      assert.equal(token, 'signed-demo');
      return {
        token,
        clientId: 'test-client',
        scopes: ['mcp:read'],
        expiresAt: Math.floor(Date.now() / 1000) + 300,
        resource: new URL('https://another.example.com/mcp')
      };
    }
  };
  app.post('/mcp', requireBearerAuth({
    verifier,
    requiredScopes: ['mcp:read'],
    expectedResource: new URL('https://mcp.klassci.com/mcp'),
    resourceMetadataUrl: 'https://mcp.klassci.com/.well-known/oauth-protected-resource/mcp'
  }), (_req, res) => res.status(200).json({error:'MUST_NOT_RUN'}));

  const server = await new Promise(resolve => {
    const instance = app.listen(0, '127.0.0.1', () => resolve(instance));
  });
  t.after(() => new Promise(resolve => server.close(resolve)));

  const port = server.address().port;
  const response = await fetch('http://127.0.0.1:' + port + '/mcp', {
    method: 'POST',
    headers: {Authorization: 'Bearer signed-demo'},
    signal: AbortSignal.timeout(4000)
  });
  assert.equal(response.status, 401);
  assert.match(response.headers.get('WWW-Authenticate') || '', /invalid_token/);
  assert.doesNotMatch(await response.text(), /MUST_NOT_RUN/);
});
