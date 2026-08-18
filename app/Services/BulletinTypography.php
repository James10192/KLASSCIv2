<?php

namespace App\Services;

final class BulletinTypography
{
    public const MIN_SIZE = 9;
    public const MAX_SIZE = 16;
    public const DEFAULT_SIZE = 13;

    /**
     * @return array<string, float|int>
     */
    public static function scale(int|string|null $baseSize = null): array
    {
        $base = self::normalize($baseSize);

        return [
            'base' => $base,
            'body' => $base,
            'table' => self::offset($base, -1),
            'table_head' => self::offset($base, -1.5),
            'label' => self::offset($base, -3.5),
            'meta' => self::offset($base, -2.5),
            'info' => self::offset($base, -0.5),
            'title' => self::offset($base, 2),
            'heading' => self::offset($base, 3),
            'avatar' => self::offset($base, 9),
            'decision' => self::offset($base, -0.5),
            'signature' => self::offset($base, -1.5),
        ];
    }

    public static function normalize(int|string|null $baseSize): int
    {
        $base = (int) round((float) ($baseSize ?? self::DEFAULT_SIZE));

        return max(self::MIN_SIZE, min(self::MAX_SIZE, $base));
    }

    private static function offset(int $base, float $delta): float
    {
        $size = $base + $delta;

        return max(8.0, min(24.0, $size));
    }
}
