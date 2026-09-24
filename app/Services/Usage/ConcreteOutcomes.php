<?php

namespace App\Services\Usage;

use App\Helpers\EntityLabelHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que le personnel a concretement fait, dit en objets metier plutot
 * qu'en ecritures : combien de notes saisies, d'evaluations creees, de
 * bulletins produits, et combien d'argent encaisse.
 */
class ConcreteOutcomes
{
    private const EVENTS = ['created' => 'crees', 'updated' => 'modifies', 'deleted' => 'supprimes', 'restored' => 'restaures'];

    public function __construct(
        private readonly UsageWindow $window,
        private readonly UsageActorDirectory $actors,
    ) {
    }

    /**
     * @param Collection $rows (user_id, auditable_type, event, month, total)
     * @return array{realisations: array, par_mois: array}
     */
    public function build(Collection $rows): array
    {
        $entities = [];
        $months = [];

        foreach ($rows as $row) {
            if ($this->actors->bucket((int) $row->user_id) !== UsageActorDirectory::ECOLE) {
                continue;
            }
            $column = self::EVENTS[$row->event] ?? null;
            if ($column === null) {
                continue;
            }
            $type = $row->auditable_type;
            $entities[$type] ??= ['entite' => $type, 'label' => EntityLabelHelper::plural($type), 'crees' => 0, 'modifies' => 0, 'supprimes' => 0, 'restaures' => 0, 'comptes' => []];
            $entities[$type][$column] += (int) $row->total;
            $entities[$type]['comptes'][(int) $row->user_id] = true;

            if ($row->event === 'created') {
                $months[$row->month][$type] = ($months[$row->month][$type] ?? 0) + (int) $row->total;
            }
        }

        $realisations = array_map(fn ($e) => [...$e, 'entite' => class_basename($e['entite']), 'comptes' => count($e['comptes'])], array_values($entities));
        usort($realisations, fn ($a, $b) => ($b['crees'] + $b['modifies']) <=> ($a['crees'] + $a['modifies']));

        ksort($months);
        $parMois = [];
        foreach ($months as $month => $created) {
            arsort($created);
            $parMois[] = [
                'mois' => $month,
                'creations' => array_map(
                    fn ($type, $count) => ['label' => EntityLabelHelper::plural($type), 'nombre' => $count],
                    array_keys($created),
                    $created
                ),
            ];
        }

        return ['realisations' => $realisations, 'creations_par_mois' => $parMois];
    }

    /** Paiements valides par mois de paiement : nombre et montant encaisse. */
    public function payments(): array
    {
        if (!Schema::hasTable('esbtp_paiements')) {
            return [];
        }

        return DB::table('esbtp_paiements')
            ->whereNull('deleted_at')
            ->whereIn('status', ['validé', 'valide'])
            ->whereBetween('date_paiement', [$this->window->from->toDateString(), $this->window->to->toDateString()])
            ->selectRaw("DATE_FORMAT(date_paiement, '%Y-%m') as mois, COUNT(*) as nombre, SUM(montant) as montant")
            ->groupByRaw("DATE_FORMAT(date_paiement, '%Y-%m')")
            ->orderBy('mois')
            ->get()
            ->map(fn ($r) => ['mois' => $r->mois, 'nombre' => (int) $r->nombre, 'montant' => (float) $r->montant])
            ->all();
    }
}
