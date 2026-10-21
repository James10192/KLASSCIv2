<?php

namespace App\Domain\Assistant\Mcp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class RequetePontSignee
{
    /**
     * Ne renvoie l'identité vérifiée que lorsque TOUS les contrôles passent.
     * Le secret partagé ne doit être connu que du gateway MCP et de cette école.
     *
     * @return array<string, mixed>|null
     */
    public function extraire(Request $request): ?array
    {
        if (config('mcp.bridge.enabled') !== true || config('mcp.read_only.enabled') !== true) {
            return null;
        }

        $secret = config('mcp.bridge.secret');
        $organization = config('mcp.bridge.organization');
        $issuer = config('mcp.bridge.issuer');

        if (! is_string($secret) || strlen($secret) < 32
            || ! is_string($organization) || $organization === ''
            || ! is_string($issuer) || ! str_starts_with($issuer, 'https://')) {
            return null;
        }

        $body = $request->getContent();
        $signature = $request->header('X-Klassci-Mcp-Signature');
        if (strlen($body) > 16384 || ! is_string($signature)
            || ! preg_match('/^[a-f0-9]{64}$/D', $signature)
            || ! hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            return null;
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)
            || ($payload['organization'] ?? null) !== $organization
            || ($payload['issuer'] ?? null) !== $issuer
            || ! is_string($payload['subject'] ?? null)
            || strlen($payload['subject']) > 255
            || ($payload['subject'] ?? '') === ''
            || ! in_array($payload['tool'] ?? null, ['search_classes', 'get_dashboard_kpis', 'get_connected_profile'], true)
            || ! is_array($payload['arguments'] ?? null)
            || ! is_int($payload['iat'] ?? null)
            || abs(time() - $payload['iat']) > 30
            || ! is_string($payload['nonce'] ?? null)
            || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $payload['nonce'])) {
            return null;
        }

        // Rejouer la même requête est toujours interdit, même après erreur.
        // Cache::add est atomique avec les stores distribués compatibles.
        // Le cache de production DOIT être partagé entre processus/serveurs.
        $cle = 'mcp-bridge-replay:'.hash('sha256', $organization.'|'.$payload['nonce']);
        if (! Cache::add($cle, true, 120)) {
            return null;
        }

        return $payload;
    }

    public static function identiteHash(string $issuer, string $subject, string $organization): string
    {
        return hash('sha256', $issuer."\0".$subject."\0".$organization);
    }
}
