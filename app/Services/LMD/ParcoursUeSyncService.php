<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent sync between an LMD Parcours and its UEs (pivot esbtp_lmd_parcours_ue).
 *
 * Why: the legacy ESBTPLMDParcoursDomainController::syncUes did detach()+attach() on every
 * call, which silently wiped pivot data (is_optional, ordre) set by another session. This
 * service computes a diff (attach / update / detach / unchanged) and only touches what
 * actually differs, preserving existing manual configuration across re-syncs.
 *
 * Two modes via $detachMissing:
 *  - true  (web modal): full sync — pivot rows missing from input get detached
 *  - false (CLI append): only attach new + update existing, never detach
 */
class ParcoursUeSyncService
{
    /**
     * @param  array<int, array{id:int, semestres:array<int>, is_optional?:bool, ordre?:int}>  $links
     * @return array{attached:int, updated:int, detached:int, unchanged:int}
     */
    public function sync(ESBTPLMDParcours $parcours, array $links, bool $detachMissing = true): array
    {
        $current = $this->loadCurrentPivot($parcours);
        $desired = $this->normalizeDesired($links);
        $diff = $this->computeDiff($current, $desired, $detachMissing);

        return DB::transaction(function () use ($parcours, $diff) {
            foreach ($diff['attach'] as $row) {
                $parcours->unitesEnseignement()->attach($row['ue_id'], [
                    'semestre' => $row['semestre'],
                    'is_optional' => $row['is_optional'],
                    'ordre' => $row['ordre'],
                ]);
            }
            foreach ($diff['update'] as $row) {
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('parcours_id', $parcours->id)
                    ->where('unite_enseignement_id', $row['ue_id'])
                    ->where('semestre', $row['semestre'])
                    ->update([
                        'is_optional' => $row['is_optional'],
                        'ordre' => $row['ordre'],
                        'updated_at' => now(),
                    ]);
            }
            foreach ($diff['detach'] as $row) {
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('parcours_id', $parcours->id)
                    ->where('unite_enseignement_id', $row['ue_id'])
                    ->where('semestre', $row['semestre'])
                    ->delete();
            }

            return [
                'attached' => count($diff['attach']),
                'updated' => count($diff['update']),
                'detached' => count($diff['detach']),
                'unchanged' => count($diff['unchanged']),
            ];
        });
    }

    /**
     * Pure diff function — no DB, no Eloquent. Fully unit-testable.
     *
     * @param  array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int}>  $current  keyed by "{ue_id}_{semestre}"
     * @param  array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int}>  $desired  keyed by "{ue_id}_{semestre}"
     * @return array{attach:array, update:array, detach:array, unchanged:array}
     */
    public function computeDiff(array $current, array $desired, bool $detachMissing): array
    {
        $attach = $update = $detach = $unchanged = [];

        foreach ($desired as $key => $row) {
            if (!isset($current[$key])) {
                $attach[] = $row;
                continue;
            }
            $changed = $current[$key]['is_optional'] !== $row['is_optional']
                || $current[$key]['ordre'] !== $row['ordre'];
            if ($changed) {
                $update[] = $row;
            } else {
                $unchanged[] = $row;
            }
        }

        if ($detachMissing) {
            foreach ($current as $key => $row) {
                if (!isset($desired[$key])) {
                    $detach[] = $row;
                }
            }
        }

        return compact('attach', 'update', 'detach', 'unchanged');
    }

    /**
     * @return array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int}>
     */
    private function loadCurrentPivot(ESBTPLMDParcours $parcours): array
    {
        $rows = DB::table('esbtp_lmd_parcours_ue')
            ->where('parcours_id', $parcours->id)
            ->get(['unite_enseignement_id', 'semestre', 'is_optional', 'ordre']);

        $map = [];
        foreach ($rows as $row) {
            $key = $row->unite_enseignement_id . '_' . $row->semestre;
            $map[$key] = [
                'ue_id' => (int) $row->unite_enseignement_id,
                'semestre' => (int) $row->semestre,
                'is_optional' => (bool) $row->is_optional,
                'ordre' => (int) $row->ordre,
            ];
        }
        return $map;
    }

    /**
     * @param  array<int, array{id:int, semestres:array<int>, is_optional?:bool, ordre?:int}>  $links
     * @return array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int}>
     */
    private function normalizeDesired(array $links): array
    {
        $map = [];
        foreach ($links as $link) {
            $ueId = (int) $link['id'];
            $isOptional = (bool) ($link['is_optional'] ?? false);
            $ordre = (int) ($link['ordre'] ?? 0);
            foreach ($link['semestres'] as $sem) {
                $sem = (int) $sem;
                $key = $ueId . '_' . $sem;
                $map[$key] = [
                    'ue_id' => $ueId,
                    'semestre' => $sem,
                    'is_optional' => $isOptional,
                    'ordre' => $ordre,
                ];
            }
        }
        return $map;
    }
}
