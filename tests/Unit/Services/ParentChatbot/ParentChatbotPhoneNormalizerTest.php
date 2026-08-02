<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Services\ParentChatbot\ParentChatbotPhoneNormalizer;
use Tests\TestCase;

class ParentChatbotPhoneNormalizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.mailpulse.parent_chatbot_phone_hash_key', str_repeat('p', 32));
    }

    public function test_it_normalizes_registered_ivoirian_phone_formats(): void
    {
        $normalizer = app(ParentChatbotPhoneNormalizer::class);

        $this->assertSame('+2250707123456', $normalizer->normalize('07 07 12 34 56'));
        $this->assertSame('+2250707123456', $normalizer->normalize('2250707123456'));
        $this->assertSame('+2250707123456', $normalizer->normalize('+225 07 07 12 34 56'));
    }

    public function test_it_rejects_invalid_phone_values(): void
    {
        $normalizer = app(ParentChatbotPhoneNormalizer::class);

        $this->assertNull($normalizer->normalize('not-a-phone'));
        $this->assertNull($normalizer->normalize(''));
        $this->assertNull($normalizer->normalize('+33612345678'));
    }

    public function test_it_hashes_phone_numbers_with_the_dedicated_key(): void
    {
        $normalizer = app(ParentChatbotPhoneNormalizer::class);

        $this->assertSame(
            hash_hmac('sha256', '+2250707123456', str_repeat('p', 32)),
            $normalizer->hash('+2250707123456')
        );
    }
}
