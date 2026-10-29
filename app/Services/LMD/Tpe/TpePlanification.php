<?php

namespace App\Services\LMD\Tpe;

use App\Helpers\SettingsHelper;

final class TpePlanification
{
    public const MODE_NON_PLANIFIABLE = 'non_planifiable';

    public const MODE_SEANCE_ENCADREE = 'seance_encadree';

    public const MODE_AUTONOME_SUR_SITE = 'autonome_sur_site';

    public const MODE_DEPOT_PREUVE = 'depot_preuve';

    public const MODE_HYBRIDE = 'hybride';

    public static function modeIsPlanifiable(string $mode): bool
    {
        $mode = trim($mode);

        return $mode !== '' && $mode !== self::MODE_NON_PLANIFIABLE;
    }

    public static function mode(): string
    {
        $mode = (string) SettingsHelper::get('tpe.mode', self::MODE_NON_PLANIFIABLE);

        return $mode !== '' ? $mode : self::MODE_NON_PLANIFIABLE;
    }

    public static function isPlanifiable(): bool
    {
        return self::modeIsPlanifiable(self::mode());
    }
}
