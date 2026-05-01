<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_null_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164(null));
        $this->assertNull(PhoneNormalizer::toE164(''));
        $this->assertNull(PhoneNormalizer::toE164('   '));
    }

    public function test_national_format_mtn(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('0707123456'));
    }

    public function test_national_format_orange(): void
    {
        $this->assertSame('+2250512345678', PhoneNormalizer::toE164('0512345678'));
    }

    public function test_national_format_with_spaces(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('07 07 12 34 56'));
    }

    public function test_national_format_with_dashes(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('07-07-12-34-56'));
    }

    public function test_already_e164(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('+2250707123456'));
    }

    public function test_double_zero_country_code(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('002250707123456'));
    }

    public function test_invalid_prefix_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('1234567890'));
        $this->assertNull(PhoneNormalizer::toE164('99 99 99 99 99'));
    }

    public function test_too_short_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('07071234'));
    }

    public function test_too_long_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('070712345678901'));
    }

    public function test_letters_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('abc'));
    }

    public function test_to_whatsapp_id_strips_plus(): void
    {
        $this->assertSame('2250707123456', PhoneNormalizer::toWhatsAppId('0707123456'));
        $this->assertNull(PhoneNormalizer::toWhatsAppId('invalid'));
    }

    public function test_is_valid(): void
    {
        $this->assertTrue(PhoneNormalizer::isValid('0707123456'));
        $this->assertFalse(PhoneNormalizer::isValid('invalid'));
        $this->assertFalse(PhoneNormalizer::isValid(null));
    }
}
