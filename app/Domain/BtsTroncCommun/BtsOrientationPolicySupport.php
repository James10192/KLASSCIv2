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
        // Garde-fous communs : niveau + classe active.
        // Les classes KLASSCI sont universelles (cf rule classes-universelles-pas-annee.md) :
        // on ne compare PAS annee_universitaire_id entre la classe et l'inscription —
        // une même classe peut accueillir des étudiants sur plusieurs années.
        if ((int) $targetClasse->niveau_etude_id !== (int) $inscription->niveau_id) {
            return null;
        }
        if (! $targetClasse->is_active) {
            return null;
        }

        // 1. Cible canonique : ClasseOrientationTarget configuré explicitement
        $target = $inscription->classe?->orientationTargets
            ->firstWhere('target_classe_id', $targetClasse->id);

        if ($target && $target->is_active) {
            return $target;
        }

        // 2. Fallback hiérarchie filière (parent_id) :
        //    Si la filière target est enfant de la filière TC source, on auto-crée
        //    le ClasseOrientationTarget. Cela évite à l'admin de configurer
        //    manuellement N×M mappings quand la hiérarchie filière est déjà en place.
        $sourceFiliere = $inscription->classe?->filiere;
        $targetFiliere = $targetClasse->filiere;

        if ($sourceFiliere
            && $sourceFiliere->isTroncCommun()
            && $targetFiliere
            && $targetFiliere->parent_id !== null
            && (int) $targetFiliere->parent_id === (int) $sourceFiliere->id) {

            return ESBTPClasseOrientationTarget::firstOrCreate(
                [
                    'source_classe_id' => $inscription->classe->id,
                    'target_classe_id' => $targetClasse->id,
                ],
                [
                    'is_active' => true,
                    'semestre_activation' => 2,
                    'sort_order' => 0,
                    'notes' => 'Auto-créé via hiérarchie filière (parent_id)',
                ]
            );
        }

        return null;
    }
}
