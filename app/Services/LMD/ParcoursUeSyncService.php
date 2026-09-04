<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Database\QueryException;
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
        $desired = $this->normalizeDesired($links);

        return DB::transaction(function () use ($parcours, $desired, $detachMissing) {
            // Lock pivot rows for this parcours so a concurrent sync waits its turn —
            // prevents two admins computing diffs against the same snapshot then both
            // attaching, which would 1062 on the unique (parcours_id, ue_id, semestre).
            $current = $this->loadCurrentPivot($parcours, lockForUpdate: true);
            $diff = $this->computeDiff($current, $desired, $detachMissing);

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
     * Meme synchronisation, vue depuis l'UNITE : quelles maquettes l'utilisent,
     * et a quels semestres.
     *
     * L'ecran « Lier a des parcours » travaille dans ce sens-la. Il faisait un
     * `detach()` SANS argument, qui effaçait tous les liens de l'unite avant de
     * les recreer : le credit propre a une maquette, le caractere optionnel et
     * l'ordre repartaient a leur valeur par defaut a chaque enregistrement, donc
     * etaient perdus. Rien ne le signalait.
     *
     * On ne detache ici que les liens de CETTE unite, et le diff ne touche que ce
     * qui differe reellement.
     *
     * @param  array<int, array{parcours_id:int, semestre:int, is_optional?:bool, ordre?:int}>  $liens
     * @return array{attached:int, updated:int, detached:int, unchanged:int}
     */
    public function syncPourUnite(ESBTPUniteEnseignement $ue, array $liens): array
    {
        $voulus = [];
        foreach ($liens as $lien) {
            $cle = ((int) $lien['parcours_id']) . '_' . ((int) $lien['semestre']);
            $voulus[$cle] = [
                'parcours_id' => (int) $lien['parcours_id'],
                'semestre' => (int) $lien['semestre'],
                'is_optional' => (bool) ($lien['is_optional'] ?? false),
                'ordre' => (int) ($lien['ordre'] ?? 0),
            ];
        }

        return DB::transaction(function () use ($ue, $voulus) {
            $actuels = [];
            $lignes = DB::table('esbtp_lmd_parcours_ue')
                ->where('unite_enseignement_id', $ue->id)
                ->lockForUpdate()
                ->get(['parcours_id', 'semestre', 'is_optional', 'ordre']);

            foreach ($lignes as $ligne) {
                $actuels[((int) $ligne->parcours_id) . '_' . ((int) $ligne->semestre)] = [
                    'parcours_id' => (int) $ligne->parcours_id,
                    'semestre' => (int) $ligne->semestre,
                    'is_optional' => (bool) $ligne->is_optional,
                    'ordre' => (int) $ligne->ordre,
                ];
            }

            $ajoutes = $modifies = $retires = $inchanges = 0;

            foreach ($voulus as $cle => $row) {
                if (! isset($actuels[$cle])) {
                    // `credit` n'est PAS ecrit : il reste nul, ce qui veut dire
                    // « pas de credit propre, prendre celui de l'unite ». Le poser
                    // a zero dirait autre chose.
                    DB::table('esbtp_lmd_parcours_ue')->insert([
                        'unite_enseignement_id' => $ue->id,
                        'parcours_id' => $row['parcours_id'],
                        'semestre' => $row['semestre'],
                        'is_optional' => $row['is_optional'],
                        'ordre' => $row['ordre'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $ajoutes++;
                    continue;
                }

                $avant = $actuels[$cle];
                if ($avant['is_optional'] === $row['is_optional'] && $avant['ordre'] === $row['ordre']) {
                    $inchanges++;
                    continue;
                }

                // `credit` reste hors du SET : c'est une decision de l'ecole, pas
                // une consequence d'un enregistrement de cet ecran-la.
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('unite_enseignement_id', $ue->id)
                    ->where('parcours_id', $row['parcours_id'])
                    ->where('semestre', $row['semestre'])
                    ->update([
                        'is_optional' => $row['is_optional'],
                        'ordre' => $row['ordre'],
                        'updated_at' => now(),
                    ]);
                $modifies++;
            }

            foreach ($actuels as $cle => $row) {
                if (isset($voulus[$cle])) {
                    continue;
                }
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('unite_enseignement_id', $ue->id)
                    ->where('parcours_id', $row['parcours_id'])
                    ->where('semestre', $row['semestre'])
                    ->delete();
                $retires++;
            }

            return [
                'attached' => $ajoutes,
                'updated' => $modifies,
                'detached' => $retires,
                'unchanged' => $inchanges,
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
    private function loadCurrentPivot(ESBTPLMDParcours $parcours, bool $lockForUpdate = false): array
    {
        $query = DB::table('esbtp_lmd_parcours_ue')->where('parcours_id', $parcours->id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $rows = $query->get(['unite_enseignement_id', 'semestre', 'is_optional', 'ordre']);

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
