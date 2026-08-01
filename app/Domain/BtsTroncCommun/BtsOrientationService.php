<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
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
        return DB::transaction(function () use ($inscription, $targetClasseId) {
            $lockedInscription = $this->lockInscriptionWithPhases($inscription->id);
            $lockedInscription->load(['filiere', 'classe.orientationTargets.targetClasse.filiere']);

            if ($this->activeSpecialisation($lockedInscription)) {
                throw new InvalidArgumentException('Cette inscription possède déjà une spécialisation active. Utilisez le workflow de correction de spécialisation.');
            }

            if (! $this->policySupport->canOrient($lockedInscription)) {
                throw new InvalidArgumentException('Cette inscription ne peut pas être orientée.');
            }

            $targetClasse = $this->lockClasse($targetClasseId);
            $target = $this->policySupport->validateTarget($lockedInscription, $targetClasse);

            if (! $target) {
                throw new InvalidArgumentException("La classe cible n'est pas autorisée pour cette classe tronc commun.");
            }

            $activePhase = $this->ensureInitialPhase($lockedInscription);
            $activePhase->update([
                'is_active' => false,
                'date_cloture' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->createSpecialisationPhase($lockedInscription, $targetClasse, $target, now());
            $this->updatePrimaryPointer($lockedInscription, $targetClasse);

            return $this->freshInscription($lockedInscription);
        });
    }

    public function correctOrientation(ESBTPInscription $inscription, int $targetClasseId, string $reason): ESBTPInscription
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de correction de la spécialisation est obligatoire.');
        }

        return DB::transaction(function () use ($inscription, $targetClasseId, $reason) {
            $lockedInscription = $this->lockInscriptionWithPhases($inscription->id);
            $sourceClasse = $this->originalTroncCommunClasse($lockedInscription);
            $activePhases = $lockedInscription->phases->where('is_active', true);
            $activeSpecialisation = $this->requireActiveSpecialisation($activePhases);
            $targetClasse = $this->lockClasse($targetClasseId);

            $target = $this->validateCorrectionTarget(
                $lockedInscription,
                $sourceClasse,
                $activeSpecialisation,
                $targetClasse
            );

            $now = now();
            $this->closeActivePhases($activePhases, $now);
            $this->createSpecialisationPhase($lockedInscription, $targetClasse, $target, $now, $reason);
            $this->updatePrimaryPointer($lockedInscription, $targetClasse);

            return $this->freshInscription($lockedInscription);
        });
    }

    public function syncAfterClassChange(ESBTPInscription $inscription, ESBTPClasse $newClasse): ESBTPInscription
    {
        $newClasse->loadMissing(['filiere']);

        return DB::transaction(function () use ($inscription, $newClasse) {
            $inscription = $this->lockInscriptionWithPhases($inscription->id);
            $inscription->load(['filiere']);

            $activeSpecialisation = $this->activeSpecialisation($inscription);
            if ($activeSpecialisation) {
                if ((int) $activeSpecialisation->classe_id !== (int) $newClasse->id) {
                    throw new InvalidArgumentException(
                        "Cette inscription a deja une specialisation active. Utilisez l'action de specialisation pour modifier ce parcours."
                    );
                }

                return $this->freshInscription($inscription);
            }

            if (! $newClasse->filiere?->isTroncCommun()) {
                $inscription->phases()
                    ->whereIn('type_phase', [
                        ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
                        ESBTPInscriptionPhase::TYPE_SPECIALISATION,
                    ])
                    ->delete();

                return $this->freshInscription($inscription);
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

            return $this->freshInscription($inscription);
        });
    }

    /**
     * @return array{status:string, before:string, after:string, message:string}
     */
    public function syncSingleInscription(ESBTPInscription $inscription): array
    {
        $inscription->loadMissing(['filiere', 'phases.classe.filiere', 'classe.filiere']);

        if ($inscription->inscription_origine_id !== null) {
            return $this->syncResult('skipped', $inscription, 'Inscription en mode legacy dual, sync non applicable.');
        }

        $activePhase = $inscription->phases->first(fn ($phase) => (bool) $phase->is_active);
        if (! $inscription->classe && $activePhase) {
            return $this->syncResult(
                'error',
                $inscription,
                'Phase '.$activePhase->type_phase.' active détectée sans classe principale. Correction manuelle requise pour préserver la filière.'
            );
        }

        if (! $inscription->classe) {
            return $this->syncResult('skipped', $inscription, 'Inscription sans classe, rien à synchroniser.');
        }

        $before = $this->snapshotPhases($inscription);
        $activeSpe = $this->activeSpecialisation($inscription);
        $activeTc = $inscription->phases->first(
            fn ($phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_TRONC_COMMUN && $phase->is_active
        );
        $classeIsTc = (bool) $inscription->classe?->filiere?->isTroncCommun();

        if ($activeSpe && (int) $activeSpe->classe_id === (int) $inscription->classe_id && ! $classeIsTc) {
            return ['status' => 'ok', 'before' => $before, 'after' => $before, 'message' => 'Déjà cohérent (spécialisation active alignée avec la classe).'];
        }

        if ($activeTc && ! $classeIsTc) {
            return $this->syncTcPhase($inscription, $before, 'Phase TC obsolète supprimée (étudiant a une classe non-TC : '.$inscription->classe->name.').');
        }

        if ($classeIsTc && $inscription->phases->isEmpty()) {
            $this->ensureInitialPhase($inscription);
            return [
                'status' => 'fixed',
                'before' => $before,
                'after' => $this->snapshotPhases($inscription->fresh(['phases.classe.filiere'])),
                'message' => 'Phase TC initiale créée (étudiant en classe TC sans phase).',
            ];
        }

        if ($activeTc && $classeIsTc && (int) $activeTc->classe_id !== (int) $inscription->classe_id) {
            return $this->syncTcPhase($inscription, $before, 'Phase TC resynchronisée avec la classe actuelle.');
        }

        return ['status' => 'ok', 'before' => $before, 'after' => $before, 'message' => 'Cohérent ou pas BTS (rien à faire).'];
    }

    /**
     * @return array{total:int, fixed:int, skipped:int, ok:int, errors:int, details:array}
     */
    public function bulkSyncAll(?int $anneeUniversitaireId = null): array
    {
        $stats = ['total' => 0, 'fixed' => 0, 'skipped' => 0, 'ok' => 0, 'errors' => 0, 'details' => []];

        $query = ESBTPInscription::query()
            ->whereNull('inscription_origine_id')
            ->where(function ($query) {
                $query->whereHas('phases')
                    ->orWhereHas('filiere', fn ($filiere) => $filiere->where('is_tronc_commun', true));
            });

        if ($anneeUniversitaireId) {
            $query->where('annee_universitaire_id', $anneeUniversitaireId);
        }

        $query->with(['filiere', 'phases.classe.filiere', 'classe.filiere'])
            ->chunkById(100, function ($inscriptions) use (&$stats) {
                foreach ($inscriptions as $inscription) {
                    $stats['total']++;
                    $result = $this->syncSingleInscription($inscription);
                    $stats[$result['status']] = ($stats[$result['status']] ?? 0) + 1;

                    if (in_array($result['status'], ['fixed', 'error'], true)) {
                        $stats['details'][] = [
                            'inscription_id' => $inscription->id,
                            'etudiant' => trim(($inscription->etudiant?->nom ?? '').' '.($inscription->etudiant?->prenoms ?? '')) ?: '#'.$inscription->id,
                            'classe' => $inscription->classe?->name,
                            'status' => $result['status'],
                            'message' => $result['message'],
                        ];
                    }
                }
            });

        return $stats;
    }

    private function lockInscriptionWithPhases(int $inscriptionId): ESBTPInscription
    {
        $inscription = ESBTPInscription::query()->lockForUpdate()->findOrFail($inscriptionId);
        $inscription->setRelation('phases', ESBTPInscriptionPhase::query()
            ->where('inscription_id', $inscription->id)
            ->orderBy('semestre_debut')
            ->orderBy('id')
            ->lockForUpdate()
            ->get());

        return $inscription;
    }

    private function lockClasse(int $classeId): ESBTPClasse
    {
        return ESBTPClasse::query()->with('filiere')->lockForUpdate()->findOrFail($classeId);
    }

    private function activeSpecialisation(ESBTPInscription $inscription): ?ESBTPInscriptionPhase
    {
        return $inscription->phases->first(
            fn ($phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_SPECIALISATION && $phase->is_active
        );
    }

    private function originalTroncCommunClasse(ESBTPInscription $inscription): ESBTPClasse
    {
        $sourcePhase = $inscription->phases
            ->where('type_phase', ESBTPInscriptionPhase::TYPE_TRONC_COMMUN)
            ->sortBy('id')
            ->first();

        if (! $sourcePhase) {
            throw new InvalidArgumentException('La phase tronc commun d’origine est introuvable pour cette correction.');
        }

        $sourceClasse = ESBTPClasse::query()
            ->with(['filiere', 'orientationTargets.targetClasse.filiere'])
            ->lockForUpdate()
            ->find($sourcePhase->classe_id);

        if (! $sourceClasse || ! $sourceClasse->filiere?->isTroncCommun()) {
            throw new InvalidArgumentException('La classe source tronc commun est invalide pour cette correction.');
        }

        return $sourceClasse;
    }

    private function requireActiveSpecialisation($activePhases): ESBTPInscriptionPhase
    {
        $activeSpecialisation = $activePhases->first(
            fn ($phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_SPECIALISATION
        );

        if (! $activeSpecialisation) {
            throw new InvalidArgumentException('Aucune spécialisation active ne peut être corrigée.');
        }

        return $activeSpecialisation;
    }

    private function validateCorrectionTarget(
        ESBTPInscription $inscription,
        ESBTPClasse $sourceClasse,
        ESBTPInscriptionPhase $activeSpecialisation,
        ESBTPClasse $targetClasse
    ): ESBTPClasseOrientationTarget {
        if ((int) $activeSpecialisation->classe_id === (int) $targetClasse->id) {
            throw new InvalidArgumentException('La classe cible correspond déjà à la spécialisation active.');
        }

        $target = $this->policySupport->validateTarget(
            $this->sourceContext($inscription, $sourceClasse),
            $targetClasse
        );

        if (! $target) {
            throw new InvalidArgumentException('La classe cible n’est pas autorisée depuis la classe tronc commun d’origine.');
        }

        return $target;
    }

    private function sourceContext(ESBTPInscription $inscription, ESBTPClasse $sourceClasse): ESBTPInscription
    {
        $context = clone $inscription;
        $context->setAttribute('classe_id', $sourceClasse->id);
        $context->setAttribute('filiere_id', $sourceClasse->filiere_id);
        $context->setRelation('classe', $sourceClasse);
        $context->setRelation('filiere', $sourceClasse->filiere);

        return $context;
    }

    private function closeActivePhases($activePhases, $now): void
    {
        foreach ($activePhases as $activePhase) {
            $activePhase->update([
                'is_active' => false,
                'date_cloture' => $now,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    private function createSpecialisationPhase(
        ESBTPInscription $inscription,
        ESBTPClasse $targetClasse,
        ESBTPClasseOrientationTarget $target,
        $activatedAt,
        ?string $correctionReason = null
    ): ESBTPInscriptionPhase {
        $attributes = [
            'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $targetClasse->id,
            'filiere_id' => $targetClasse->filiere_id,
            'semestre_debut' => (int) $target->semestre_activation,
            'semestre_fin' => null,
            'is_active' => true,
            'orientation_target_id' => $target->id,
            'date_activation' => $activatedAt,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        if ($correctionReason !== null) {
            $attributes['correction_reason'] = $correctionReason;
        }

        return $inscription->phases()->create($attributes);
    }

    private function updatePrimaryPointer(ESBTPInscription $inscription, ESBTPClasse $classe): void
    {
        $inscription->update([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'updated_by' => Auth::id(),
        ]);
    }

    private function freshInscription(ESBTPInscription $inscription): ESBTPInscription
    {
        return $inscription->fresh(['phases.classe.filiere', 'classe.filiere', 'filiere']);
    }

    private function syncTcPhase(ESBTPInscription $inscription, string $before, string $message): array
    {
        try {
            $this->syncAfterClassChange($inscription, $inscription->classe);
            return [
                'status' => 'fixed',
                'before' => $before,
                'after' => $this->snapshotPhases($inscription->fresh(['phases.classe.filiere'])),
                'message' => $message,
            ];
        } catch (InvalidArgumentException $exception) {
            return ['status' => 'error', 'before' => $before, 'after' => $before, 'message' => $exception->getMessage()];
        }
    }

    private function syncResult(string $status, ESBTPInscription $inscription, string $message): array
    {
        $snapshot = $this->snapshotPhases($inscription);

        return ['status' => $status, 'before' => $snapshot, 'after' => $snapshot, 'message' => $message];
    }

    private function snapshotPhases(ESBTPInscription $inscription): string
    {
        $phases = $inscription->relationLoaded('phases') ? $inscription->phases : $inscription->phases()->get();
        if ($phases->isEmpty()) {
            return '(aucune phase)';
        }

        return $phases
            ->map(fn ($phase) => $phase->type_phase.':'.($phase->classe?->name ?? '#'.$phase->classe_id).($phase->is_active ? '*' : ''))
            ->join(' | ');
    }
}
