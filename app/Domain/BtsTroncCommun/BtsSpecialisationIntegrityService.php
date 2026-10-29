<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BtsSpecialisationIntegrityService
{
    public function diagnose(ESBTPInscription $inscription): array
    {
        $inscription->loadMissing(['classe', 'filiere', 'phases.classe.filiere']);
        $activePhases = $this->activeSpecialisations($inscription);

        if ($activePhases->count() > 1) {
            return $this->result($inscription, null, 'multiple_active_specialisations', false);
        }

        $phase = $activePhases->first();

        if (! $phase) {
            return $this->result($inscription, null, 'no_active_specialisation', false);
        }

        $classMatches = (int) $inscription->classe_id === (int) $phase->classe_id;
        $filiereMatches = (int) $inscription->filiere_id === (int) $phase->filiere_id;

        if ($classMatches && $filiereMatches) {
            return $this->result($inscription, $phase, 'coherent', false);
        }

        $missingClass = $inscription->classe_id === null;
        $compatibleFiliere = $inscription->filiere_id === null || $filiereMatches;
        $status = $missingClass && $compatibleFiliere
            ? 'repairable_missing_primary_pointer'
            : 'conflicting_primary_pointer';

        return $this->result($inscription, $phase, $status, $status === 'repairable_missing_primary_pointer');
    }

    public function repairPrimaryPointer(ESBTPInscription $inscription, int $actorId): array
    {
        return DB::transaction(function () use ($inscription, $actorId) {
            $locked = ESBTPInscription::query()->lockForUpdate()->findOrFail($inscription->id);
            $locked->setRelation('phases', ESBTPInscriptionPhase::query()
                ->where('inscription_id', $locked->id)
                ->lockForUpdate()
                ->get());

            $before = $this->diagnose($locked);

            if (! $before['repairable']) {
                throw new InvalidArgumentException(
                    "L'inscription {$locked->id} n'est pas réparable automatiquement ({$before['status']})."
                );
            }

            $phase = $this->activeSpecialisations($locked)->first();
            $locked->update([
                'classe_id' => $phase->classe_id,
                'filiere_id' => $phase->filiere_id,
                'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                'updated_by' => $actorId,
            ]);

            return [
                'before' => $before,
                'after' => $this->diagnose($locked->fresh(['classe', 'filiere', 'phases.classe.filiere'])),
            ];
        });
    }

    private function activeSpecialisations(ESBTPInscription $inscription): Collection
    {
        return $inscription->phases->filter(
            fn (ESBTPInscriptionPhase $phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_SPECIALISATION
                && $phase->is_active
        );
    }

    private function result(
        ESBTPInscription $inscription,
        ?ESBTPInscriptionPhase $phase,
        string $status,
        bool $repairable
    ): array {
        return [
            'inscription_id' => $inscription->id,
            'etudiant_id' => $inscription->etudiant_id,
            'status' => $status,
            'repairable' => $repairable,
            'primary' => [
                'classe_id' => $inscription->classe_id,
                'classe' => $inscription->classe?->name,
                'filiere_id' => $inscription->filiere_id,
                'filiere' => $inscription->filiere?->name,
                'affectation_status' => $inscription->affectation_status,
            ],
            'active_specialisation' => $phase ? [
                'phase_id' => $phase->id,
                'classe_id' => $phase->classe_id,
                'classe' => $phase->classe?->name,
                'filiere_id' => $phase->filiere_id,
                'filiere' => $phase->filiere?->name,
            ] : null,
        ];
    }
}
