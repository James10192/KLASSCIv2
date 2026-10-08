<?php

namespace App\Observers;

use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPTeacher;

/**
 * Maintient le pivot historique `esbtp_planification_teachers` aligné sur le
 * pool canonique User : enseignant principal + enseignants secondaires.
 *
 * On ne touche au pivot que lorsque ces champs changent. Une ancienne ligne
 * qui n'a encore que le pivot continue donc d'être lisible tant qu'un humain
 * ne l'édite pas ; dès la prochaine édition du pool, la représentation devient
 * cohérente et un professeur retiré ne peut plus réapparaître via le pivot.
 */
final class ESBTPPlanificationTeacherPoolObserver
{
    public function saved(ESBTPPlanificationAcademique $planification): void
    {
        if (! $planification->wasChanged(['enseignant_principal_id', 'enseignants_secondaires'])) {
            return;
        }

        $userIds = collect([$planification->enseignant_principal_id])
            ->merge($planification->enseignants_secondaires ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $teacherIds = ESBTPTeacher::whereIn('user_id', $userIds)->pluck('id')->all();
        $planification->teachers()->sync($teacherIds);
    }
}
