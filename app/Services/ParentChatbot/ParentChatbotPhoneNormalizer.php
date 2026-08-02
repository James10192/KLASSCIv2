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
        return hash_hmac('sha256', $normalizedPhone, ParentChatbotSecurityConfig::phoneHashKey());
    }
}
