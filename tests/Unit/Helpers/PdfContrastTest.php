<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SettingsHelper;
use PHPUnit\Framework\TestCase;

class PdfContrastTest extends TestCase
{
    public function test_white_on_white_falls_back_to_dark(): void
    {
        $this->assertSame('#111827', SettingsHelper::contrastingText('#ffffff', '#ffffff'));
    }

    public function test_white_on_green_stays_white(): void
    {
        $this->assertSame('#ffffff', SettingsHelper::contrastingText('#0b7a2f', '#ffffff'));
    }

    public function test_dark_on_white_stays_dark(): void
    {
        $this->assertSame('#111827', SettingsHelper::contrastingText('#ffffff', '#111827'));
    }
}
