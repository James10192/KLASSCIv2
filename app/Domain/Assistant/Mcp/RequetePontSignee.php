<?php

namespace App\Domain\Assistant\Mcp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            || ! in_array($payload['tool'] ?? null, ['search_classes', 'get_dashboard_kpis', 'get_connected_profile', 'list_authorized_tools', 'create_pairing_challenge'], true)
            || ! is_array($payload['arguments'] ?? null)
            || ! is_int($payload['iat'] ?? null)
            || abs(time() - $payload['iat']) > 30
            || ! is_string($payload['nonce'] ?? null)
            || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $payload['nonce'])) {
            return null;
        }

        // Protection anti-rejeu à garantie SQL unique, compatible MySQL
        // mutualisé cPanel : PAS de Redis ou worker permanent requis.
        // Ne jamais réutiliser un nonce valide, même si l'exécution métier
        // a ensuite refusé la demande. La migration doit exister AVANT
        // l'activation du pont ; toute panne SQL échoue en mode fermé.
        try {
            $insere = DB::table('mcp_bridge_nonces')->insert([
                'nonce_hash' => hash('sha256', $organization.'|'.$payload['nonce']),
                'expires_at' => now()->addSeconds(120),
                'created_at' => now(),
            ]);

            if (! $insere) {
                return null;
            }
        } catch (\Throwable $exception) {
            // Ni secret ni nonce ne doivent finir dans les logs HTTP.
            return null;
        }

        return $payload;
    }

    public static function identiteHash(string $issuer, string $subject, string $organization): string
    {
        return hash('sha256', $issuer."\0".$subject."\0".$organization);
    }
}
