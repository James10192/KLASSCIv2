<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
     * @param  array<int, array{id:int, semestres:array<int>, is_optional?:bool, ordre?:int, credit?:int|null}>  $links
     * @return array{attached:int, updated:int, detached:int, unchanged:int}
     */
    public function sync(ESBTPLMDParcours $parcours, array $links, bool $detachMissing = true): array
    {
        $desired = $this->normalizeDesired($links);

        return DB::transaction(function () use ($parcours, $desired, $detachMissing) {
            // Lock pivot rows for this parcours so a concurrent sync waits its turn —
            // prevents two admins computing diffs against the same snapshot then both
            // attaching, which would 1062 on the unique (parcours_id, ue_id, semestre).
            $current = $this->loadCurrentPivot($parcours, lockForUpdate: true);
            $diff = $this->computeDiff($current, $desired, $detachMissing);

            foreach ($diff['attach'] as $row) {
                $colonnes = [
                    'semestre' => $row['semestre'],
                    'is_optional' => $row['is_optional'],
                    'ordre' => $row['ordre'],
                ];
                if (array_key_exists('credit', $row) && $this->colonneCreditDisponible()) {
                    $colonnes['credit'] = $row['credit'];
                }
                $parcours->unitesEnseignement()->attach($row['ue_id'], $colonnes);
            }
            foreach ($diff['update'] as $row) {
                $colonnes = [
                    'is_optional' => $row['is_optional'],
                    'ordre' => $row['ordre'],
                    'updated_at' => now(),
                ];
                // « Absent » n'est pas « nul » : un appel qui ne parle pas du
                // credit ne doit pas l'effacer. Le modal « Lier a des parcours »
                // n'envoie que le semestre et l'ordre ; sans cette distinction, il
                // remettrait a zero le credit propre a la maquette a chaque
                // enregistrement, sans que personne ne le demande.
                if (array_key_exists('credit', $row) && $this->colonneCreditDisponible()) {
                    $colonnes['credit'] = $row['credit'];
                }
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('parcours_id', $parcours->id)
                    ->where('unite_enseignement_id', $row['ue_id'])
                    ->where('semestre', $row['semestre'])
                    ->update($colonnes);
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
     * @param  array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int, credit?:int|null}>  $current  keyed by "{ue_id}_{semestre}"
     * @param  array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int, credit?:int|null}>  $desired  keyed by "{ue_id}_{semestre}"
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

            // Le credit n'entre dans la comparaison que si l'appelant en a parle.
            // Une cle absente veut dire « je ne me prononce pas » : la valeur en
            // base est conservee telle quelle. Une cle presente a null veut dire
            // « pas de credit propre a cette maquette », et s'ecrit.
            if (array_key_exists('credit', $row)) {
                $changed = $changed || ($current[$key]['credit'] ?? null) !== $row['credit'];
            }
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
     * @return array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int, credit?:int|null}>
     */
    private function loadCurrentPivot(ESBTPLMDParcours $parcours, bool $lockForUpdate = false): array
    {
        $query = DB::table('esbtp_lmd_parcours_ue')->where('parcours_id', $parcours->id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $rows = $query->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $row->unite_enseignement_id . '_' . $row->semestre;
            $map[$key] = [
                'ue_id' => (int) $row->unite_enseignement_id,
                'semestre' => (int) $row->semestre,
                'is_optional' => (bool) $row->is_optional,
                'ordre' => (int) $row->ordre,
                'credit' => isset($row->credit) && $row->credit !== null ? (int) $row->credit : null,
            ];
        }
        return $map;
    }

    /**
     * Le credit propre a une maquette est une colonne recente : les instances qui
     * n'ont pas encore joue la migration ne l'ont pas. On verifie une fois par
     * processus plutot que de faire echouer un enregistrement.
     */
    private function colonneCreditDisponible(): bool
    {
        static $disponible = null;

        if ($disponible === null) {
            $disponible = Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit');
        }

        return $disponible;
    }

    /**
     * @param  array<int, array{id:int, semestres:array<int>, is_optional?:bool, ordre?:int, credit?:int|null}>  $links
     * @return array<string, array{ue_id:int, semestre:int, is_optional:bool, ordre:int, credit?:int|null}>
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
                // La cle n'est reportee que si l'appelant l'a fournie : c'est elle
                // qui distingue « pas de credit propre a cette maquette » (null
                // explicite) de « je ne parle pas du credit » (cle absente).
                if (array_key_exists('credit', $link)) {
                    $map[$key]['credit'] = $link['credit'] === null || $link['credit'] === ''
                        ? null
                        : (int) $link['credit'];
                }
            }
        }
        return $map;
    }
}
