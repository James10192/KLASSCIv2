<?php

namespace Tests\Unit\Services;

use App\Services\BulletinTypography;
use PHPUnit\Framework\TestCase;

class BulletinTypographyTest extends TestCase
{
    public function test_scale_uses_fourteen_as_body_and_keeps_table_one_step_smaller(): void
    {
        $scale = BulletinTypography::scale(14);

        self::assertSame(14, $scale['body']);
        self::assertSame(13.0, $scale['table']);
        self::assertSame(16.0, $scale['title']);
        self::assertSame(17.0, $scale['heading']);
        self::assertSame(13.5, $scale['decision']);
        self::assertSame(12.5, $scale['signature']);
    }

    public function test_normalize_clamps_between_nine_and_sixteen(): void
    {
        self::assertSame(9, BulletinTypography::normalize(4));
        self::assertSame(16, BulletinTypography::normalize(40));
        self::assertSame(13, BulletinTypography::normalize(null));
    }
}
