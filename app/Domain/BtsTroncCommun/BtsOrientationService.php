<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BtsOrientationService
{
    public function __construct(
        private BtsOrientationPolicySupport $policySupport
    ) {
    }

    public function ensureInitialPhase(ESBTPInscription $inscription): ESBTPInscriptionPhase
    {
        $inscription->loadMissing(['filiere', 'classe.filiere', 'phases']);

        $existing = $inscription->phases->sortBy('id')->first();
        if ($existing) {
            return $existing;
        }

        return $inscription->phases()->create([
            'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
            'classe_id' => $inscription->classe_id,
            'filiere_id' => $inscription->filiere_id,
            'semestre_debut' => 1,
            'semestre_fin' => max(1, (int) ($inscription->filiere?->semestres_tronc_commun ?: 1)),
            'is_active' => true,
            'date_activation' => $inscription->date_inscription ?? now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function orient(ESBTPInscription $inscription, int $targetClasseId): ESBTPInscription
    {
        $inscription->loadMissing([
            'filiere',
            'classe.orientationTargets.targetClasse.filiere',
            'phases.classe.filiere',
        ]);

        if (! $this->policySupport->canOrient($inscription)) {
            throw new InvalidArgumentException("Cette inscription ne peut pas être orientée.");
        }

        $targetClasse = ESBTPClasse::with(['filiere'])->findOrFail($targetClasseId);
        $target = $this->policySupport->validateTarget($inscription, $targetClasse);

        if (! $target) {
            throw new InvalidArgumentException("La classe cible n'est pas autorisée pour cette classe tronc commun.");
        }

        return DB::transaction(function () use ($inscription, $targetClasse, $target) {
            $activePhase = $this->ensureInitialPhase($inscription);

            $activePhase->update([
                'is_active' => false,
                'date_cloture' => now(),
                'updated_by' => Auth::id(),
            ]);

            $inscription->phases()->create([
                'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
                'classe_id' => $targetClasse->id,
                'filiere_id' => $targetClasse->filiere_id,
                'semestre_debut' => (int) $target->semestre_activation,
                'semestre_fin' => null,
                'is_active' => true,
                'orientation_target_id' => $target->id,
                'date_activation' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $inscription->update([
                'classe_id' => $targetClasse->id,
                'filiere_id' => $targetClasse->filiere_id,
                'updated_by' => Auth::id(),
            ]);

            return $inscription->fresh(['phases.classe.filiere', 'classe.filiere', 'filiere']);
        });
    }

    public function syncAfterClassChange(ESBTPInscription $inscription, ESBTPClasse $newClasse): ESBTPInscription
    {
        $inscription->loadMissing(['filiere', 'phases']);
        $newClasse->loadMissing(['filiere']);

        return DB::transaction(function () use ($inscription, $newClasse) {
            $inscription->refresh()->load(['phases', 'filiere']);

            $activeSpecialisation = $inscription->phases
                ->first(fn ($phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_SPECIALISATION && $phase->is_active);

            if ($activeSpecialisation && (int) $activeSpecialisation->classe_id !== (int) $newClasse->id) {
                throw new InvalidArgumentException(
                    "Cette inscription a deja une specialisation active. Utilisez l'action de specialisation pour modifier ce parcours."
                );
            }

            if (! $newClasse->filiere?->isTroncCommun()) {
                $inscription->phases()
                    ->whereIn('type_phase', [
                        ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
                        ESBTPInscriptionPhase::TYPE_SPECIALISATION,
                    ])
                    ->delete();

                return $inscription->fresh(['phases.classe.filiere', 'classe.filiere', 'filiere']);
            }

            $inscription->phases()
                ->where('type_phase', ESBTPInscriptionPhase::TYPE_SPECIALISATION)
                ->delete();

            $initialPhase = $inscription->phases()
                ->where('type_phase', ESBTPInscriptionPhase::TYPE_TRONC_COMMUN)
                ->orderBy('id')
                ->first();

            $attributes = [
                'classe_id' => $newClasse->id,
                'filiere_id' => $newClasse->filiere_id,
                'semestre_debut' => 1,
                'semestre_fin' => max(1, (int) ($newClasse->filiere?->semestres_tronc_commun ?: 1)),
                'is_active' => true,
                'orientation_target_id' => null,
                'date_cloture' => null,
                'updated_by' => Auth::id(),
            ];

            if ($initialPhase) {
                $initialPhase->update($attributes);
            } else {
                $inscription->phases()->create($attributes + [
                    'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
                    'date_activation' => $inscription->date_inscription ?? now(),
                    'created_by' => Auth::id(),
                ]);
            }

            return $inscription->fresh(['phases.classe.filiere', 'classe.filiere', 'filiere']);
        });
    }
}
