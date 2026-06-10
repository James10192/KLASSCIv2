<?php

namespace App\Domain\LMD\Actions;

use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fusionne N UE doublons vers une UE canonique.
 *
 * Why: après import de maquettes multi-parcours, la même UE existe en plusieurs
 * exemplaires (un par parcours). Cette action repointe tous les liens des UE
 * absorbées vers la canonique :
 *  - pivot esbtp_lmd_parcours_ue (parcours ↔ UE) → lie la canonique à TOUS les parcours
 *  - pivot esbtp_ue_matiere (UE ↔ ECUE)
 *  - FK esbtp_matieres.unite_enseignement_id (ECUE legacy)
 *  - esbtp_lmd_resultats_ues (résultats bulletins)
 * puis soft-delete les UE absorbées.
 *
 * SÉCURITÉ : refuse de fusionner une UE qui porte des résultats de bulletins
 * (esbtp_lmd_resultats_ues) sans flag `force`. Tout en transaction, idempotent,
 * avec log d'audit. `dry_run` (défaut true) retourne un aperçu d'impact + la
 * re-validation UEMOA (30 crédits par parcours/semestre) SANS commit.
 */
class MergeDuplicateUe
{
    /** Crédits attendus par semestre/parcours (réf UEMOA). */
    public const UEMOA_CREDITS_PER_SEMESTER = 30;

    /**
     * @param  int    $canonicalId  UE qui survit
     * @param  int[]  $absorbedIds  UE absorbées (soft-deleted)
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
            return $this->emptyReport($canonicalId, 'Aucune UE à absorber (idempotent).');
        }

        $canonical = ESBTPUniteEnseignement::findOrFail($canonicalId);
        $absorbed = ESBTPUniteEnseignement::whereIn('id', $absorbedIds)->get();

        if ($absorbed->count() !== count($absorbedIds)) {
            throw new RuntimeException('Une ou plusieurs UE à absorber sont introuvables.');
        }

        // ── Garde-fou : résultats de bulletins ──
        $blocking = $this->blockingDependencies($absorbedIds);
        if ($blocking['resultats_ue'] > 0 && !$force) {
            return [
                'success' => false,
                'dry_run' => $dryRun,
                'blocked' => true,
                'reason' => 'UE absorbées portant des résultats de bulletins — fusion bloquée sans confirmation explicite (force).',
                'blocking' => $blocking,
                'canonical_id' => $canonicalId,
                'absorbed_ids' => $absorbedIds,
            ];
        }

        $impact = $this->computeImpact($canonicalId, $absorbedIds);
        $impact['blocking'] = $blocking;

        if ($dryRun) {
            $impact['uemoa_check'] = $this->uemoaCreditCheck($canonicalId, $absorbedIds);

            return array_merge(['success' => true, 'dry_run' => true, 'committed' => false], $impact);
        }

        DB::transaction(function () use ($canonicalId, $absorbed, $absorbedIds, $force) {
            // 1. Pivot parcours ↔ UE : repointer en évitant la collision sur
            //    unique(parcours_id, unite_enseignement_id, semestre).
            $this->repointParcoursUePivot($canonicalId, $absorbedIds);

            // 2. Pivot UE ↔ ECUE : repointer en évitant unique(ue_id, matiere_id).
            $this->repointUeMatierePivot($canonicalId, $absorbedIds);

            // 3. FK legacy esbtp_matieres.unite_enseignement_id.
            DB::table('esbtp_matieres')
                ->whereIn('unite_enseignement_id', $absorbedIds)
                ->update(['unite_enseignement_id' => $canonicalId, 'updated_at' => now()]);

            // 4. Résultats de bulletins (seulement si force).
            if ($force) {
                DB::table('esbtp_lmd_resultats_ues')
                    ->whereIn('unite_enseignement_id', $absorbedIds)
                    ->update(['unite_enseignement_id' => $canonicalId, 'updated_at' => now()]);
            }

            // 5. Soft-delete des UE absorbées.
            foreach ($absorbed as $ue) {
                $ue->delete();
            }

            Log::info('[LMD reconciliation] UE merge', [
                'canonical_id' => $canonicalId,
                'absorbed_ids' => $absorbedIds,
                'forced' => $force,
                'by' => optional(auth()->user())->id,
            ]);
        });

        $report = array_merge(['success' => true, 'dry_run' => false, 'committed' => true], $impact);
        $report['uemoa_check'] = $this->uemoaCreditCheck($canonicalId, []);

        return $report;
    }

    /**
     * Repointe esbtp_lmd_parcours_ue vers la canonique en évitant les collisions
     * sur la contrainte unique (parcours_id, unite_enseignement_id, semestre).
     */
    private function repointParcoursUePivot(int $canonicalId, array $absorbedIds): void
    {
        $rows = DB::table('esbtp_lmd_parcours_ue')
            ->whereIn('unite_enseignement_id', $absorbedIds)
            ->get();

        foreach ($rows as $row) {
            $exists = DB::table('esbtp_lmd_parcours_ue')
                ->where('parcours_id', $row->parcours_id)
                ->where('unite_enseignement_id', $canonicalId)
                ->where('semestre', $row->semestre)
                ->exists();

            if ($exists) {
                DB::table('esbtp_lmd_parcours_ue')->where('id', $row->id)->delete();
            } else {
                DB::table('esbtp_lmd_parcours_ue')->where('id', $row->id)->update([
                    'unite_enseignement_id' => $canonicalId,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function repointUeMatierePivot(int $canonicalId, array $absorbedIds): void
    {
        $rows = DB::table('esbtp_ue_matiere')
            ->whereIn('unite_enseignement_id', $absorbedIds)
            ->get();

        foreach ($rows as $row) {
            $exists = DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $canonicalId)
                ->where('matiere_id', $row->matiere_id)
                ->exists();

            if ($exists) {
                DB::table('esbtp_ue_matiere')->where('id', $row->id)->delete();
            } else {
                DB::table('esbtp_ue_matiere')->where('id', $row->id)->update([
                    'unite_enseignement_id' => $canonicalId,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @return array{resultats_ue:int}
     */
    private function blockingDependencies(array $absorbedIds): array
    {
        return [
            'resultats_ue' => DB::table('esbtp_lmd_resultats_ues')->whereIn('unite_enseignement_id', $absorbedIds)->count(),
        ];
    }

    private function computeImpact(int $canonicalId, array $absorbedIds): array
    {
        return [
            'canonical_id' => $canonicalId,
            'absorbed_ids' => $absorbedIds,
            'repointed' => [
                'parcours_ue_links' => DB::table('esbtp_lmd_parcours_ue')->whereIn('unite_enseignement_id', $absorbedIds)->count(),
                'ue_matiere_links' => DB::table('esbtp_ue_matiere')->whereIn('unite_enseignement_id', $absorbedIds)->count(),
                'ecue_fk' => DB::table('esbtp_matieres')->whereIn('unite_enseignement_id', $absorbedIds)->count(),
            ],
            'soft_deleted_count' => count($absorbedIds),
        ];
    }

    /**
     * Re-valide que chaque parcours/semestre touché totalise toujours les crédits
     * UEMOA attendus (30 par semestre). Émet un warning par scope hors-cible.
     *
     * @return array{ok:bool, warnings:array<int, array<string, mixed>>}
     */
    private function uemoaCreditCheck(int $canonicalId, array $absorbedIds): array
    {
        $ueIds = array_merge([$canonicalId], $absorbedIds);

        // Parcours/semestres impactés par les UE concernées.
        $scopes = DB::table('esbtp_lmd_parcours_ue')
            ->whereIn('unite_enseignement_id', $ueIds)
            ->select('parcours_id', 'semestre')
            ->distinct()
            ->get();

        $warnings = [];
        foreach ($scopes as $scope) {
            $totalCredits = (int) DB::table('esbtp_lmd_parcours_ue as pue')
                ->join('esbtp_unites_enseignement as ue', 'ue.id', '=', 'pue.unite_enseignement_id')
                ->where('pue.parcours_id', $scope->parcours_id)
                ->where('pue.semestre', $scope->semestre)
                ->whereNull('ue.deleted_at')
                ->sum('ue.credit');

            if ($totalCredits !== self::UEMOA_CREDITS_PER_SEMESTER) {
                $warnings[] = [
                    'parcours_id' => $scope->parcours_id,
                    'semestre' => $scope->semestre,
                    'total_credits' => $totalCredits,
                    'expected' => self::UEMOA_CREDITS_PER_SEMESTER,
                ];
            }
        }

        return ['ok' => empty($warnings), 'warnings' => $warnings];
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
            'repointed' => ['parcours_ue_links' => 0, 'ue_matiere_links' => 0, 'ecue_fk' => 0],
            'soft_deleted_count' => 0,
            'blocking' => ['resultats_ue' => 0],
            'uemoa_check' => ['ok' => true, 'warnings' => []],
        ];
    }
}
