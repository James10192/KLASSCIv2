<?php

namespace App\Services\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;

/**
 * Source de vérité du périmètre académique de la fiche : ni un autre élève,
 * ni une autre classe, ni une autre année ne peuvent se glisser dans le
 * panneau, même avec des IDs qui se ressemblent.
 */
final class EvaluationsDeLaFiche
{
    public function pourInscription(ESBTPInscription $inscription): Builder
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $inscription->classe_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id);
    }
}
