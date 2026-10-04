<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPPlanificationAcademique;

/**
 * Invalidation des projections de pilotage lors d'un changement de planning.
 *
 * IMPORTANT LMD : une planification parcours/niveau/semestre peut alimenter
 * plusieurs classes et contenir plusieurs professeurs. On ne propage donc plus
 * ici `enseignant_principal_id` vers toutes les evaluations de la filiere : la
 * confirmation du professeur se fait classe par classe via EnseignantDeClasseLmd.
 */
final class ESBTPPlanificationAcademicPilotageObserver
{
    public function __construct(private readonly AcademicMetricSnapshotInvalidationService $invalidation) {}

    public function saved(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }

    public function deleted(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }

    public function restored(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }
}
