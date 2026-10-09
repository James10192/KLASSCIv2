<?php

namespace App\Services\Familles;

use App\Helpers\SettingsHelper;

final class ReglagesFamille
{
    public const ENABLED = 'familles.portal.enabled';

    public function enabled(): bool
    {
        return in_array(
            strtolower(trim((string) SettingsHelper::get(self::ENABLED, '0'))),
            ['1', 'true', 'on', 'yes', 'oui'],
            true
        );
    }
}
