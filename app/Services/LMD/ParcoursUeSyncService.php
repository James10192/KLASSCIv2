<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            $this->refuserCodeImprimeEnDouble((int) $parcours->id, collect($diff['attach'])->pluck('ue_id'));

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
     * `detach()` SANS argument, qui effacait tous les liens de l'unite avant de
     * les recreer : le credit propre a une maquette, le caractere optionnel et
     * l'ordre repartaient a leur valeur par defaut a chaque enregistrement, donc
     * etaient perdus. Rien ne le signalait.
     *
     * Le diff est celui de `computeDiff` — le meme, pas un jumeau : la seule
     * difference entre les deux sens est la colonne tenue fixe, et une seconde
     * implementation aurait diverge au premier changement de regle.
     *
     * Le VERROU, lui, reste pose sur `parcours_id`, colonne de tete de l'index
     * unique (parcours_id, unite_enseignement_id, semestre). Verrouiller par
     * unite laisserait un enregistrement concurrent depuis l'autre ecran inserer
     * une ligne invisible a notre instantane : notre INSERT heurterait alors le
     * triplet unique, erreur 1062 non rattrapee, 500 — exactement ce que
     * `sync()` a ete ecrite pour empecher.
     *
     * @param  array<int, array{parcours_id:int, semestre:int, is_optional?:bool, ordre?:int}>  $liens
     * @return array{attached:int, updated:int, detached:int, unchanged:int}
     */
    public function syncPourUnite(ESBTPUniteEnseignement $ue, array $liens): array
    {
        $voulus = [];
        foreach ($liens as $lien) {
            $parcoursId = (int) $lien['parcours_id'];
            $semestre = (int) $lien['semestre'];
            $voulus[$parcoursId.'_'.$semestre] = [
                'parcours_id' => $parcoursId,
                'ue_id' => (int) $ue->id,
                'semestre' => $semestre,
                'is_optional' => (bool) ($lien['is_optional'] ?? false),
                'ordre' => (int) ($lien['ordre'] ?? 0),
            ];
        }

        return DB::transaction(function () use ($ue, $voulus) {
            // Un verrou par parcours concerne, pris dans un ordre stable : deux
            // enregistrements simultanes attendent leur tour au lieu de calculer
            // leurs diffs sur le meme instantane. L'ordre evite l'interblocage.
            $parcoursConcernes = collect($voulus)->pluck('parcours_id')
                ->merge(DB::table('esbtp_lmd_parcours_ue')
                    ->where('unite_enseignement_id', $ue->id)
                    ->pluck('parcours_id'))
                ->map(fn ($id) => (int) $id)->unique()->sort()->values();

            foreach ($parcoursConcernes as $parcoursId) {
                DB::table('esbtp_lmd_parcours_ue')->where('parcours_id', $parcoursId)->lockForUpdate()->get(['id']);
            }

            $actuels = [];
            $lignes = DB::table('esbtp_lmd_parcours_ue')
                ->where('unite_enseignement_id', $ue->id)
                ->get(['parcours_id', 'semestre', 'is_optional', 'ordre']);

            foreach ($lignes as $ligne) {
                $actuels[((int) $ligne->parcours_id).'_'.((int) $ligne->semestre)] = [
                    'parcours_id' => (int) $ligne->parcours_id,
                    'ue_id' => (int) $ue->id,
                    'semestre' => (int) $ligne->semestre,
                    'is_optional' => (bool) $ligne->is_optional,
                    'ordre' => (int) $ligne->ordre,
                ];
            }

            $diff = $this->computeDiff($actuels, $voulus, detachMissing: true);
            foreach (collect($diff['attach'])->pluck('parcours_id')->unique() as $parcoursId) {
                $this->refuserCodeImprimeEnDouble((int) $parcoursId, collect([(int) $ue->id]));
            }

            foreach ($diff['attach'] as $row) {
                // `credit` n'est PAS ecrit : il reste nul, ce qui veut dire
                // « pas de credit propre, prendre celui de l'unite ». Le poser a
                // zero dirait autre chose.
                DB::table('esbtp_lmd_parcours_ue')->insert([
                    'unite_enseignement_id' => $ue->id,
                    'parcours_id' => $row['parcours_id'],
                    'semestre' => $row['semestre'],
                    'is_optional' => $row['is_optional'],
                    'ordre' => $row['ordre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($diff['update'] as $row) {
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
            }

            foreach ($diff['detach'] as $row) {
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('unite_enseignement_id', $ue->id)
                    ->where('parcours_id', $row['parcours_id'])
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
     * Un parcours n'imprime jamais deux fois le meme code d'UE.
     *
     * Deux unites differentes peuvent porter le meme code imprime, pourvu
     * qu'elles vivent dans deux parcours differents (CodeDeMaquette). Ce
     * service est le passage commun du formulaire, de l'import et de l'ecran de
     * rattachement : c'est donc ici que l'unicite par parcours se garde.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $ueIds
     */
    private function refuserCodeImprimeEnDouble(int $parcoursId, \Illuminate\Support\Collection $ueIds): void
    {
        if ($ueIds->isEmpty()) {
            return;
        }

        $maquette = app(CodeDeMaquette::class);
        foreach (ESBTPUniteEnseignement::whereIn('id', $ueIds->all())->get(['id', 'code', 'name']) as $ue) {
            $deja = $maquette->autreUniteDuParcours($parcoursId, $ue->code, (int) $ue->id);
            if ($deja === null) {
                continue;
            }

            $parcours = DB::table('esbtp_lmd_parcours')->where('id', $parcoursId)->value('code');
            throw ValidationException::withMessages(['parcours_id' => sprintf(
                'Le parcours %s porte déjà l\'UE « %s » (%s) sous le code %s. Un relevé ne peut pas imprimer deux fois le même code : retirez d\'abord cette UE du parcours.',
                $parcours ?? ('#' . $parcoursId),
                $deja->name,
                CodeDeMaquette::affiche($deja->code),
                CodeDeMaquette::affiche($ue->code)
            )]);
        }
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
