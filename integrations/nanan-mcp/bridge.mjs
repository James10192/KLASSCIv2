import { createHmac, randomUUID } from 'node:crypto';

export function signedBody(principal, issuer, tool, argumentsObject, secret, timestamp = Math.floor(Date.now()/1000)) {
  if (!['search_classes', 'get_dashboard_kpis', 'get_connected_profile'].includes(tool)) throw new Error('Outil non exposé');
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
  if (!response.ok) throw new Error('L’établissement a refusé la demande.');
  // Le serveur Laravel supprime déjà les champs réservés à l'interface.
  const data = await response.json();
  if (!data || typeof data !== 'object' || Array.isArray(data) || data.error) {
    throw new Error('Réponse de l’établissement non disponible.');
  }
  return data;
}
