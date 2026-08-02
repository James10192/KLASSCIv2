<?php

namespace App\Services\ParentChatbot;

use App\Domain\Notifications\PhoneNormalizer;

final class ParentChatbotPhoneNormalizer
{
    public function normalize(string $phone): ?string
    {
        return PhoneNormalizer::toE164($phone);
    }

    public function hash(string $normalizedPhone): string
    {
        $key = (string) config('services.mailpulse.parent_chatbot_phone_hash_key', '');
        if (strlen($key) < 32) {
            throw new \LogicException('MAILPULSE_PARENT_CHATBOT_PHONE_HASH_KEY must contain at least 32 characters.');
        }

        return hash_hmac('sha256', $normalizedPhone, $key);
    }
}
