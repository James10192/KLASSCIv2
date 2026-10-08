<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Support;

use App\Models\ESBTPEvaluation;
use Illuminate\Support\Collection;

/**
 * Ce qui a été noté pour un élément : contrôle continu, examen, ou les deux.
 *
 * Le partage est celui de la pondération du bulletin LMD
 * (`LMDBulletinService`) : l'examen d'un côté, tout le reste de l'autre. Seules
 * les évaluations qui portent au moins une note traitée comptent.
 */
final class NatureDesNotes
{
    public const CC_EXAMEN = 'cc_examen';

    public const EXAMEN = 'examen';

    public const CC = 'cc';

    /**
     * @param  Collection<int, array{type: ?string, treated_count: int}>  $evaluationRows
     */
    public static function pour(Collection $evaluationRows): ?string
    {
        $types = $evaluationRows->where('treated_count', '>', 0)->pluck('type');
        $examen = $types->contains(ESBTPEvaluation::TYPE_EXAMEN);
        $controle = $types->contains(fn ($type) => $type !== ESBTPEvaluation::TYPE_EXAMEN);

        return match (true) {
            $examen && $controle => self::CC_EXAMEN,
            $examen => self::EXAMEN,
            $controle => self::CC,
            default => null,
        };
    }
}
