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

    /**
     * Synchronise une seule inscription si elle est incohérente.
     *
     * Cas traités :
     *  - Phase TC active mais filière de classe non-TC → suppression des phases (la classe a été changée hors workflow officiel)
     *  - Filière TC active mais aucune phase → création de la phase TC initiale
     *  - Phase TC active avec classe_id différent de inscription->classe_id → resync de la phase TC
     *
     * Skip explicitement :
     *  - Inscriptions en mode legacy_dual_inscription (inscription_origine_id)
     *  - Inscriptions sans classe (rien à synchroniser)
     *  - Inscriptions avec phase 'specialisation' active correspondant à la classe actuelle (déjà cohérent)
     *
     * @return array{status:string, before:string, after:string, message:string}
     */
    public function syncSingleInscription(ESBTPInscription $inscription): array
    {
        $inscription->loadMissing(['filiere', 'phases.classe.filiere', 'classe.filiere']);

        if ($inscription->inscription_origine_id !== null) {
            return [
                'status' => 'skipped',
                'before' => $this->snapshotPhases($inscription),
                'after' => $this->snapshotPhases($inscription),
                'message' => 'Inscription en mode legacy dual — sync non applicable.',
            ];
        }

        if (! $inscription->classe) {
            return [
                'status' => 'skipped',
                'before' => $this->snapshotPhases($inscription),
                'after' => $this->snapshotPhases($inscription),
                'message' => 'Inscription sans classe — rien à synchroniser.',
            ];
        }

        $before = $this->snapshotPhases($inscription);
        $activeSpe = $inscription->phases->first(fn ($p) => $p->type_phase === ESBTPInscriptionPhase::TYPE_SPECIALISATION && $p->is_active);
        $activeTc = $inscription->phases->first(fn ($p) => $p->type_phase === ESBTPInscriptionPhase::TYPE_TRONC_COMMUN && $p->is_active);
        $classeIsTc = (bool) $inscription->classe?->filiere?->isTroncCommun();

        // Cas 1 : déjà cohérent — spé active dont la classe matche l'inscription
        if ($activeSpe && (int) $activeSpe->classe_id === (int) $inscription->classe_id && ! $classeIsTc) {
            return [
                'status' => 'ok',
                'before' => $before,
                'after' => $before,
                'message' => 'Déjà cohérent (spécialisation active alignée avec la classe).',
            ];
        }

        // Cas 2 : phase TC active + classe non-TC → désynchronisation détectée, on supprime les phases
        if ($activeTc && ! $classeIsTc) {
            try {
                $this->syncAfterClassChange($inscription, $inscription->classe);
                return [
                    'status' => 'fixed',
                    'before' => $before,
                    'after' => $this->snapshotPhases($inscription->fresh(['phases.classe.filiere'])),
                    'message' => 'Phase TC obsolète supprimée (étudiant a une classe non-TC : '.$inscription->classe->name.').',
                ];
            } catch (InvalidArgumentException $e) {
                return [
                    'status' => 'error',
                    'before' => $before,
                    'after' => $before,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Cas 3 : filière TC mais aucune phase → créer la phase TC initiale
        if ($classeIsTc && $inscription->phases->isEmpty()) {
            $this->ensureInitialPhase($inscription);
            return [
                'status' => 'fixed',
                'before' => $before,
                'after' => $this->snapshotPhases($inscription->fresh(['phases.classe.filiere'])),
                'message' => 'Phase TC initiale créée (étudiant en classe TC sans phase).',
            ];
        }

        // Cas 4 : phase TC active mais classe_id différent (admin a changé la classe TC)
        if ($activeTc && $classeIsTc && (int) $activeTc->classe_id !== (int) $inscription->classe_id) {
            try {
                $this->syncAfterClassChange($inscription, $inscription->classe);
                return [
                    'status' => 'fixed',
                    'before' => $before,
                    'after' => $this->snapshotPhases($inscription->fresh(['phases.classe.filiere'])),
                    'message' => 'Phase TC resynchronisée avec la classe actuelle.',
                ];
            } catch (InvalidArgumentException $e) {
                return [
                    'status' => 'error',
                    'before' => $before,
                    'after' => $before,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => 'ok',
            'before' => $before,
            'after' => $before,
            'message' => 'Cohérent ou pas BTS (rien à faire).',
        ];
    }

    /**
     * Synchronise en masse toutes les inscriptions BTS du tenant.
     * Parcourt par chunks pour limiter la mémoire.
     *
     * @return array{total:int, fixed:int, skipped:int, ok:int, errors:int, details:array}
     */
    public function bulkSyncAll(?int $anneeUniversitaireId = null): array
    {
        $stats = ['total' => 0, 'fixed' => 0, 'skipped' => 0, 'ok' => 0, 'errors' => 0, 'details' => []];

        $query = ESBTPInscription::query()
            ->whereNull('inscription_origine_id')
            ->whereHas('classe.filiere', function ($q) {
                // BTS only : exclure LMD via systeme_academique de la classe
                $q->whereRaw('1 = 1'); // pas de filtre strict — sync les inscriptions BTS et celles qui ont des phases
            })
            ->where(function ($q) {
                $q->whereHas('phases')                                  // a au moins une phase (potentiellement désynchronisée)
                  ->orWhereHas('filiere', fn ($f) => $f->where('is_tronc_commun', true)); // ou est en filière TC (besoin de phase)
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

    /**
     * Snapshot lisible des phases pour le log/audit (avant/après sync).
     */
    private function snapshotPhases(ESBTPInscription $inscription): string
    {
        $phases = $inscription->relationLoaded('phases') ? $inscription->phases : $inscription->phases()->get();
        if ($phases->isEmpty()) {
            return '(aucune phase)';
        }
        return $phases
            ->map(fn ($p) => $p->type_phase.':'.($p->classe?->name ?? '#'.$p->classe_id).($p->is_active ? '*' : ''))
            ->join(' | ');
    }
}
