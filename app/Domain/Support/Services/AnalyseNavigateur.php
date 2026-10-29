<?php

namespace App\Domain\Support\Services;

/**
 * Navigateur, systeme et type d'appareil, deduits de l'User-Agent.
 *
 * Volontairement grossier : on veut savoir « Chrome 128 sur Android, mobile »,
 * pas identifier un appareil. L'User-Agent brut, lui, ne part pas au Master.
 */
final class AnalyseNavigateur
{
    /** @return array{browser: array{family: ?string, version: ?string}, os: ?string, device: string} */
    public static function depuis(?string $ua): array
    {
        $ua = (string) $ua;

        $famille = null;
        $version = null;
        foreach ([
            'Edge' => '/Edg(?:e|A|iOS)?\/(\d+)/',
            'Opera' => '/OPR\/(\d+)/',
            'Samsung Internet' => '/SamsungBrowser\/(\d+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/(\d+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/(\d+)/',
            'Safari' => '/Version\/(\d+).*Safari/',
        ] as $nom => $motif) {
            if (preg_match($motif, $ua, $m)) {
                [$famille, $version] = [$nom, $m[1]];
                break;
            }
        }

        $os = match (true) {
            str_contains($ua, 'Android') => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'CrOS') => 'ChromeOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        $appareil = match (true) {
            (bool) preg_match('/iPad|Tablet/i', $ua), str_contains($ua, 'Android') && ! str_contains($ua, 'Mobile') => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod/i', $ua) => 'mobile',
            default => 'desktop',
        };

        return ['browser' => ['family' => $famille, 'version' => $version], 'os' => $os, 'device' => $appareil];
    }
}
