<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPInscription;

class BtsOrientationPolicySupport
{
    public function canOrient(ESBTPInscription $inscription): bool
    {
        return $inscription->filiere?->isTroncCommun()
            && $inscription->classe_id !== null
            && ! $inscription->phases->contains(fn ($phase) => $phase->type_phase === 'specialisation' && $phase->is_active);
    }

    public function validateTarget(ESBTPInscription $inscription, ESBTPClasse $targetClasse): ?ESBTPClasseOrientationTarget
    {
        $target = $inscription->classe?->orientationTargets
            ->firstWhere('target_classe_id', $targetClasse->id);

        if (! $target || ! $target->is_active) {
            return null;
        }

        if ((int) $targetClasse->annee_universitaire_id !== (int) $inscription->annee_universitaire_id) {
            return null;
        }

        if ((int) $targetClasse->niveau_etude_id !== (int) $inscription->niveau_id) {
            return null;
        }

        return $target;
    }
}
