<?php

namespace App\Domain\Scolarite;

final class FamilleEvaluation
{
    public const CC = 'cc';

    public const EXAMEN = 'examen';

    public const AUTRE = 'autre';

    public static function depuis(?string $type): string
    {
        $type = mb_strtolower(trim((string) $type));

        if (in_array($type, ['cc', 'controle', 'contrôle', 'devoir', 'td', 'tp'], true)) {
            return self::CC;
        }
        if (in_array($type, ['examen', 'exam', 'session', 'partiel', 'terminal'], true)) {
            return self::EXAMEN;
        }

        return self::AUTRE;
    }
}
