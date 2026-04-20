<?php

namespace App\Services;

use App\Support\SsoClaim;
use RuntimeException;

/**
 * Verifies HMAC-SHA256 tokens signed by the master app (adminKlassci) for cross-app SSO.
 *
 * MUST stay in sync with adminKlassci/app/Services/SsoTokenSigner (same shared secret,
 * same algorithm, same payload claims — see SsoClaim).
 */
class SsoTokenVerifier
{
    private const ALGO = 'sha256';

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $signature] = $parts;

        $expected = hash_hmac(self::ALGO, $payloadB64, $this->getSecret());
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        try {
            $payload = json_decode($this->base64UrlDecode($payloadB64), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload[SsoClaim::EXP]) || $payload[SsoClaim::EXP] < time()) {
            return null;
        }

        return $payload;
    }

    private function getSecret(): string
    {
        $secret = config('services.group_sso.secret') ?: env('GROUP_SSO_SHARED_SECRET');

        if (! $secret || strlen($secret) < 32) {
            throw new RuntimeException('GROUP_SSO_SHARED_SECRET must be set and at least 32 chars');
        }

        return $secret;
    }

    private function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        return base64_decode(strtr($padded, '-_', '+/'), true) ?: '';
    }
}
