<?php

namespace App\Services\ParentChatbot;

use Illuminate\Http\Request;

class ParentChatbotInboundSignature
{
    public function isValid(Request $request): bool
    {
        $secret = (string) config('services.mailpulse.parent_chatbot_webhook_secret', '');
        $timestamp = $request->header('X-MailPulse-Timestamp');
        $signature = $request->header('X-MailPulse-Signature');

        if (strlen($secret) < 32 || ! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($signature)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > (int) config('services.mailpulse.parent_chatbot_signature_ttl', 300)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
