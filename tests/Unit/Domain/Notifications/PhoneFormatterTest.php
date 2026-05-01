<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\PhoneFormatter;
use PHPUnit\Framework\TestCase;

class PhoneFormatterTest extends TestCase
{
    public function test_null_returns_null(): void
    {
        $this->assertNull(PhoneFormatter::toReadable(null));
        $this->assertNull(PhoneFormatter::toReadable(''));
    }

    public function test_invalid_returns_null(): void
    {
        $this->assertNull(PhoneFormatter::toReadable('abc'));
        $this->assertNull(PhoneFormatter::toReadable('99 99 99 99 99'));
    }

    public function test_national_format_pairs(): void
    {
        $this->assertSame('+225 07 07 12 34 56', PhoneFormatter::toReadable('0707123456'));
    }

    public function test_orange_format(): void
    {
        $this->assertSame('+225 05 12 34 56 78', PhoneFormatter::toReadable('0512345678'));
    }

    public function test_with_spaces(): void
    {
        $this->assertSame('+225 07 07 12 34 56', PhoneFormatter::toReadable('07 07 12 34 56'));
    }

    public function test_already_e164(): void
    {
        $this->assertSame('+225 07 07 12 34 56', PhoneFormatter::toReadable('+2250707123456'));
    }
}
