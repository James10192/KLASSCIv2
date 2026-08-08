<?php

namespace App\Services\ParentChatbot;

use Illuminate\Http\Request;

/**
 * Verifies the callbacks MailPulse pushes from its external-application
 * forward endpoint. The wire format is `v1:<keyId>=<hex hmac>` over
 * `timestamp . "." . rawBody`, signed with the endpoint secret.
 */
class ParentChatbotInboundSignature
{
    public function isValid(Request $request): bool
    {
        $timestamp = $request->header('x-external-timestamp');
        $signature = $request->header('x-external-signature');

        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($signature)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > (int) config('services.mailpulse.parent_chatbot_signature_ttl', 300)) {
            return false;
        }

        $parsed = $this->parse($signature);
        if ($parsed === null) {
            return false;
        }

        $keyId = (string) config('services.mailpulse.external_callback_key_id', '');
        if ($keyId === '' || ! hash_equals($keyId, $parsed['key_id'])) {
            return false;
        }

        $secret = $this->secret();
        if ($secret === null) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $parsed['digest']);
    }

    /**
     * @return array{key_id: string, digest: string}|null
     */
    private function parse(string $signature): ?array
    {
        if (preg_match('/^v1:([A-Za-z0-9_-]{1,128})=([a-f0-9]{64})$/', $signature, $matches) !== 1) {
            return null;
        }

        return ['key_id' => $matches[1], 'digest' => $matches[2]];
    }

    /**
     * Only the secret MailPulse actually signs with is accepted. Falling back to
     * a derived secret would make the E2E harness pass while every real callback
     * failed its signature check, and would keep a rotated secret valid.
     */
    private function secret(): ?string
    {
        $configured = (string) config('services.mailpulse.external_callback_secret', '');

        return $configured === '' ? null : $configured;
    }
}
