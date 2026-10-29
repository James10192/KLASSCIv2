import { createHmac, randomUUID } from 'node:crypto';

export class SchoolIdentityNotLinked extends Error {
  constructor() { super('Compte non lié.'); this.name = 'SchoolIdentityNotLinked'; }
}

export function signedBody(principal, issuer, tool, argumentsObject, secret, timestamp = Math.floor(Date.now()/1000)) {
  if (!['search_classes', 'get_dashboard_kpis', 'get_connected_profile', 'list_authorized_tools', 'create_pairing_challenge'].includes(tool)) throw new Error('Outil non exposé');
  const body = JSON.stringify({
    issuer,
    subject: principal.subject,
    organization: principal.organization,
    tool,
    arguments: argumentsObject,
    iat: timestamp,
    nonce: randomUUID()
  });
  return {
    body,
    signature: createHmac('sha256', secret).update(body, 'utf8').digest('hex')
  };
}

/**
 * Lit un JSON HTTPS en bornant la mémoire AVANT le décodage.
 * Ni Content-Length (qui peut mentir), ni response.json() non borné ne
 * constituent une protection suffisante contre des réponses volumineuses.
 */
export async function readBoundedJson(response, maxBytes = 128 * 1024) {
  const lengthHeader = response.headers?.get('content-length');
  if (lengthHeader && /^\d+$/.test(lengthHeader) && Number(lengthHeader) > maxBytes) {
    throw new Error('Réponse du pont trop volumineuse.');
  }
  if (!response.body || typeof response.body.getReader !== 'function') {
    throw new Error('Réponse HTTP non exploitable.');
  }

  const reader = response.body.getReader();
  const buffers = [];
  let bytes = 0;
  try {
    for (;;) {
      const { done, value } = await reader.read();
      if (done) break;
      bytes += value.byteLength;
      if (bytes > maxBytes) {
        await reader.cancel();
        throw new Error('Réponse du pont trop volumineuse.');
      }
      buffers.push(Buffer.from(value));
    }
  } finally {
    reader.releaseLock();
  }
  return JSON.parse(Buffer.concat(buffers, bytes).toString('utf8'));
}

export async function callSchoolBridge(principal, registry, issuer, tool, argumentsObject, http = fetch) {
  // La destination ne provient JAMAIS des arguments d'un outil.
  const school = registry.get(principal.organization);
  if (!school) throw new Error('Cet établissement n’est pas connecté à Nanan Connect.');
  const signed = signedBody(principal, issuer, tool, argumentsObject, school.secret);
  const response = await http(school.url, {
    method: 'POST',
    redirect: 'error',
    signal: AbortSignal.timeout(8000),
    headers: { 'Content-Type': 'application/json', 'X-Klassci-Mcp-Signature': signed.signature },
    body: signed.body
  });
  if (response.status === 403 && tool === 'list_authorized_tools') {
    // Un refus 403 signifie aussi compte révoqué, école désactivée ou
    // permission retirée. Seule la preuve EXPLICITE « jamais lié » autorise
    // l'affichage de l'outil d'appairage.
    const denial = await readBoundedJson(response, 4096).catch(() => null);
    if (denial?.code === 'identity_not_linked') {
      throw new SchoolIdentityNotLinked();
    }
  }
  if (!response.ok) throw new Error('L’établissement a refusé la demande.');
  // Refuser un JSON volumineux (même sans Content-Length) avant le parse.
  const data = await readBoundedJson(response);
  if (!data || typeof data !== 'object' || Array.isArray(data) || data.error) {
    throw new Error('Réponse de l’établissement non disponible.');
  }
  return data;
}
