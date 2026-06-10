<?php

namespace App\Domain\LMD\Actions;

use App\Models\ESBTPMatiere;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fusionne N ECUE (matières) doublons vers une ECUE canonique.
 *
 * Why: après import de maquettes multi-parcours, le même ECUE existe en plusieurs
 * exemplaires distincts. Cette action repointe tous les liens (pivot
 * esbtp_ue_matiere, FK unite_enseignement_id, pivot esbtp_matiere_filiere,
 * planifications esbtp_planifications_academiques) des ECUE absorbés vers la
 * canonique, lie la canonique à TOUTES les UEs concernées, puis soft-delete les
 * absorbés.
 *
 * SÉCURITÉ : refuse de fusionner un ECUE qui porte des évaluations
 * (esbtp_evaluations) ou des notes (esbtp_notes) sans flag `force`. Tout en
 * transaction, idempotent, avec log d'audit. `dry_run` (défaut true) retourne un
 * aperçu d'impact SANS commit.
 */
class MergeDuplicateEcue
{
    /**
     * @param  int    $canonicalId  ECUE qui survit
     * @param  int[]  $absorbedIds  ECUE absorbés (soft-deleted)
     * @param  array{dry_run?:bool, force?:bool}  $options
     * @return array<string, mixed>  aperçu / rapport d'impact
     */
    public function execute(int $canonicalId, array $absorbedIds, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $force = (bool) ($options['force'] ?? false);

        $absorbedIds = array_values(array_unique(array_filter(
            array_map('intval', $absorbedIds),
            fn ($id) => $id !== 0 && $id !== $canonicalId
        )));

        if (empty($absorbedIds)) {
            return $this->emptyReport($canonicalId, 'Aucune ECUE à absorber (idempotent).');
        }

        $canonical = ESBTPMatiere::findOrFail($canonicalId);
        $absorbed = ESBTPMatiere::whereIn('id', $absorbedIds)->get();

        if ($absorbed->count() !== count($absorbedIds)) {
            throw new RuntimeException('Une ou plusieurs ECUE à absorber sont introuvables.');
        }

        // ── Garde-fou : évaluations / notes ──
        $blocking = $this->blockingDependencies($absorbedIds);
        if (($blocking['evaluations'] > 0 || $blocking['notes'] > 0) && !$force) {
            return [
                'success' => false,
                'dry_run' => $dryRun,
                'blocked' => true,
                'reason' => 'ECUE absorbées portant des évaluations ou des notes — fusion bloquée sans confirmation explicite (force).',
                'blocking' => $blocking,
                'canonical_id' => $canonicalId,
                'absorbed_ids' => $absorbedIds,
            ];
        }

        $impact = $this->computeImpact($canonicalId, $absorbedIds);
        $impact['blocking'] = $blocking;

        if ($dryRun) {
            return array_merge(['success' => true, 'dry_run' => true, 'committed' => false], $impact);
        }

        DB::transaction(function () use ($canonicalId, $absorbed, $absorbedIds, $force) {
            // 1. Pivot esbtp_ue_matiere : repointer matiere_id absorbé → canonique
            //    (en évitant les collisions sur unique(ue_id, matiere_id)).
            $this->repointUeMatierePivot($canonicalId, $absorbedIds);

            // 2. Planifications académiques : matiere_id absorbé → canonique
            DB::table('esbtp_planifications_academiques')
                ->whereIn('matiere_id', $absorbedIds)
                ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);

            // 3. Évaluations / Notes (seulement si force) : matiere_id → canonique
            if ($force) {
                DB::table('esbtp_evaluations')
                    ->whereIn('matiere_id', $absorbedIds)
                    ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);
                DB::table('esbtp_notes')
                    ->whereIn('matiere_id', $absorbedIds)
                    ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);
            }

            // 4. Pivot esbtp_matiere_filiere : repointer en évitant doublons.
            $this->repointMatiereFilierePivot($canonicalId, $absorbedIds);

            // 5. Soft-delete des absorbés.
            foreach ($absorbed as $ecue) {
                $ecue->delete();
            }

            Log::info('[LMD reconciliation] ECUE merge', [
                'canonical_id' => $canonicalId,
                'absorbed_ids' => $absorbedIds,
                'forced' => $force,
                'by' => optional(auth()->user())->id,
            ]);
        });

        return array_merge(['success' => true, 'dry_run' => false, 'committed' => true], $impact);
    }

    /**
     * Repointe le pivot esbtp_ue_matiere des ECUE absorbés vers la canonique,
     * sans créer de doublon sur la contrainte unique (ue_id, matiere_id).
     */
    private function repointUeMatierePivot(int $canonicalId, array $absorbedIds): void
    {
        $rows = DB::table('esbtp_ue_matiere')
            ->whereIn('matiere_id', $absorbedIds)
            ->get();

        foreach ($rows as $row) {
            $existsForCanonical = DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $row->unite_enseignement_id)
                ->where('matiere_id', $canonicalId)
                ->exists();

            if ($existsForCanonical) {
                // La canonique est déjà liée à cette UE → on supprime simplement le doublon.
                DB::table('esbtp_ue_matiere')->where('id', $row->id)->delete();
            } else {
                DB::table('esbtp_ue_matiere')->where('id', $row->id)->update([
                    'matiere_id' => $canonicalId,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function repointMatiereFilierePivot(int $canonicalId, array $absorbedIds): void
    {
        $rows = DB::table('esbtp_matiere_filiere')
            ->whereIn('matiere_id', $absorbedIds)
            ->get();

        foreach ($rows as $row) {
            $exists = DB::table('esbtp_matiere_filiere')
                ->where('filiere_id', $row->filiere_id)
                ->where('matiere_id', $canonicalId)
                ->exists();

            if ($exists) {
                DB::table('esbtp_matiere_filiere')->where('id', $row->id)->delete();
            } else {
                DB::table('esbtp_matiere_filiere')->where('id', $row->id)->update([
                    'matiere_id' => $canonicalId,
                ]);
            }
        }
    }

    /**
     * @return array{evaluations:int, notes:int}
     */
    private function blockingDependencies(array $absorbedIds): array
    {
        return [
            'evaluations' => DB::table('esbtp_evaluations')->whereIn('matiere_id', $absorbedIds)->count(),
            'notes' => DB::table('esbtp_notes')->whereIn('matiere_id', $absorbedIds)->count(),
        ];
    }

    private function computeImpact(int $canonicalId, array $absorbedIds): array
    {
        return [
            'canonical_id' => $canonicalId,
            'absorbed_ids' => $absorbedIds,
            'repointed' => [
                'ue_matiere_links' => DB::table('esbtp_ue_matiere')->whereIn('matiere_id', $absorbedIds)->count(),
                'planifications' => DB::table('esbtp_planifications_academiques')->whereIn('matiere_id', $absorbedIds)->count(),
                'matiere_filiere_links' => DB::table('esbtp_matiere_filiere')->whereIn('matiere_id', $absorbedIds)->count(),
            ],
            'soft_deleted_count' => count($absorbedIds),
        ];
    }

    private function emptyReport(int $canonicalId, string $message): array
    {
        return [
            'success' => true,
            'committed' => false,
            'dry_run' => true,
            'message' => $message,
            'canonical_id' => $canonicalId,
            'absorbed_ids' => [],
            'repointed' => ['ue_matiere_links' => 0, 'planifications' => 0, 'matiere_filiere_links' => 0],
            'soft_deleted_count' => 0,
            'blocking' => ['evaluations' => 0, 'notes' => 0],
        ];
    }
}
