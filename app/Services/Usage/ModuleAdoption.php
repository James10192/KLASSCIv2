<?php

namespace App\Services\Usage;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quels modules le personnel fait-il vivre ?
 *
 * Pour chaque module : ecritures du personnel, comptes distincts, jours
 * distincts, et la part des ecritures faites par nos outils. Un module
 * nourri seulement par un import ne vit pas : c'est ce que les jours
 * distincts revelent, bien mieux qu'un total de lignes.
 */
class ModuleAdoption
{
    public function __construct(
        private readonly UsageWindow $window,
        private readonly UsageActorDirectory $actors,
    ) {
    }

    /**
     * @param Collection $webRows    (user_id, auditable_type, day, total)
     * @param Collection $originRows (origin, auditable_type, total)
     */
    public function build(Collection $webRows, Collection $originRows): array
    {
        $modelToModule = $this->modelToModule();
        $modules = [];
        foreach (config('usage_report.modules') as $key => $module) {
            $modules[$key] = [
                'module' => $key, 'label' => $module['label'],
                'actions_ecole' => 0, 'actions_klassci' => 0, 'actions_systeme' => 0,
                'comptes' => [], 'jours' => [], 'roles' => [],
            ];
        }

        foreach ($webRows as $row) {
            $key = $modelToModule[$row->auditable_type] ?? null;
            if ($key === null || $this->actors->bucket((int) $row->user_id) !== UsageActorDirectory::ECOLE) {
                continue;
            }
            $modules[$key]['actions_ecole'] += (int) $row->total;
            $modules[$key]['comptes'][(int) $row->user_id] = true;
            $modules[$key]['jours'][$row->day] = true;
            $modules[$key]['roles'][$this->actors->role((int) $row->user_id)] = true;
        }

        foreach ($originRows as $row) {
            $key = $modelToModule[$row->auditable_type] ?? null;
            if ($key === null || $row->origin === AuditActivityQuery::ORIGIN_WEB) {
                continue;
            }
            $column = $row->origin === AuditActivityQuery::ORIGIN_TOOL ? 'actions_klassci' : 'actions_systeme';
            $modules[$key][$column] += (int) $row->total;
        }

        return array_values(array_map(fn ($m) => [
            ...$m,
            'comptes' => count($m['comptes']),
            'jours' => count($m['jours']),
            'roles' => array_keys($m['roles']),
        ], $modules));
    }

    /** Lignes creees dans les modules sans journal d'audit. */
    public function volumes(): array
    {
        $volumes = [];
        foreach (config('usage_report.volumes') as $key => $volume) {
            if (!Schema::hasTable($volume['table']) || !Schema::hasColumn($volume['table'], $volume['date'])) {
                $volumes[] = ['module' => $key, 'label' => $volume['label'], 'lignes' => null, 'jours' => null];
                continue;
            }
            $stats = DB::table($volume['table'])
                ->whereBetween($volume['date'], [$this->window->from, $this->window->to])
                ->selectRaw('COUNT(*) as lignes, COUNT(DISTINCT DATE(' . $volume['date'] . ')) as jours')
                ->first();
            $volumes[] = ['module' => $key, 'label' => $volume['label'], 'lignes' => (int) $stats->lignes, 'jours' => (int) $stats->jours];
        }

        return $volumes;
    }

    /**
     * Carte de chaleur heure x jour de semaine, personnel de l'ecole seulement.
     * Jours au format ISO : 1 = lundi ... 7 = dimanche.
     */
    public function heatmap(Collection $hourRows): array
    {
        $grid = [];
        foreach ($hourRows as $row) {
            if ($this->actors->bucket((int) $row->user_id) !== UsageActorDirectory::ECOLE) {
                continue;
            }
            $isoDay = ((int) $row->dow + 5) % 7 + 1; // MySQL : 1 = dimanche
            $grid[$isoDay][(int) $row->hour] = ($grid[$isoDay][(int) $row->hour] ?? 0) + (int) $row->total;
        }

        $cells = [];
        foreach ($grid as $day => $hours) {
            foreach ($hours as $hour => $total) {
                $cells[] = ['jour_semaine' => $day, 'heure' => $hour, 'actions' => $total];
            }
        }

        return $cells;
    }

    private function modelToModule(): array
    {
        $map = [];
        foreach (config('usage_report.modules') as $key => $module) {
            foreach ($module['models'] as $model) {
                $map[$model] = $key;
            }
        }

        return $map;
    }
}
