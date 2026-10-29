<?php

namespace App\Services\Enseignants;

use Illuminate\Support\Collection;

/**
 * Combine les filtres de l'export avec OR au sein d'une famille
 * (plusieurs classes, filieres ou niveaux) et AND entre les familles.
 *
 * Une selection sans classe compatible retourne [] : elle ne doit jamais
 * se transformer en absence de filtre (= toutes les classes).
 */
final class PerimetreExportEnseignants
{
    /**
     * @param Collection<int, object|array> $classes
     * @param Collection<int, object|array> $filieres
     * @param array<string, mixed> $filtres
     * @return array<int, int>|null null signifie « toutes les classes »
     */
    public static function classes(Collection $classes, Collection $filieres, array $filtres): ?array
    {
        $choixClasses = array_map('intval', $filtres['classe_ids'] ?? []);
        if (! empty($filtres['classe_id'])) {
            $choixClasses[] = (int) $filtres['classe_id'];
        }
        $choixClasses = array_values(array_unique($choixClasses));
        $choixFilieres = array_map('intval', $filtres['filiere_ids'] ?? []);
        $choixNiveaux = array_map('intval', $filtres['niveau_ids'] ?? []);

        if ($choixClasses === [] && $choixFilieres === [] && $choixNiveaux === []) {
            return null;
        }

        $descendants = array_fill_keys($choixFilieres, true);
        // Les specialisations rattachees a une filiere parente sont incluses,
        // sans confondre les filieres jumelles ni prendre toutes les classes.
        $parents = $filieres->mapWithKeys(fn ($filiere) => [
            (int) data_get($filiere, 'id') => data_get($filiere, 'parent_id')
                ? (int) data_get($filiere, 'parent_id') : null,
        ])->all();
        foreach ($parents as $id => $parent) {
            $visited = [];
            while ($parent !== null && ! isset($visited[$parent])) {
                $visited[$parent] = true;
                if (isset($descendants[$parent])) {
                    $descendants[(int) $id] = true;
                    break;
                }
                $parent = $parents[$parent] ?? null;
            }
        }

        return $classes->filter(function ($classe) use ($choixClasses, $choixFilieres, $choixNiveaux, $descendants): bool {
            $id = (int) data_get($classe, 'id');
            $filiereId = (int) data_get($classe, 'filiere_id');
            $niveauId = (int) data_get($classe, 'niveau_etude_id');

            return ($choixClasses === [] || in_array($id, $choixClasses, true))
                && ($choixFilieres === [] || isset($descendants[$filiereId]))
                && ($choixNiveaux === [] || in_array($niveauId, $choixNiveaux, true));
        })->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }
}
