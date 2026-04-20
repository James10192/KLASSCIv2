<?php

namespace App\Services;

use RuntimeException;

/**
 * Verifies HMAC-SHA256 tokens signed by the master app (adminKlassci) for cross-app SSO.
 *
 * Mirror of adminKlassci/app/Services/SsoTokenSigner. Must use the same shared secret
 * (GROUP_SSO_SHARED_SECRET env var) and same algorithm (HMAC-SHA256, same payload format).
 *
 * Why: the group portal signs a short-lived token embedding {tenant_code, user_email,
 * redirect_to}. When the user clicks "Ouvrir l'établissement", the browser hits
 * /auth/sso-from-group?token=... on the tenant app. This service validates the token
 * and the controller logs the user in.
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

        if (! is_array($payload) || ! isset($payload['exp']) || $payload['exp'] < time()) {
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
