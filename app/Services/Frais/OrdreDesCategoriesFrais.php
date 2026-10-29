<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reordonne les categories de frais d'un etablissement.
 *
 * `sort_order` n'est pas une coquetterie d'affichage : c'est l'ordre dans lequel
 * l'ecole veut que ses frais soient servis. `RepartitionTropPercu` s'en sert pour
 * decider quel frais un versement solde en premier. Une ecole qui veut que la
 * tenue passe avant la scolarite n'a donc rien a demander au code — elle range
 * ses categories, et la repartition suit.
 *
 * Aucune ecriture sans `apply` : on ne change pas l'ordre de service d'un
 * etablissement sans avoir d'abord montre ce que ca donne.
 */
class OrdreDesCategoriesFrais
{
    /**
     * @param  array<int, int>  $idsOrdonnes  Les categories dans l'ordre voulu.
     *                                        Celles qu'on omet gardent leur ordre
     *                                        relatif et sont rangees derriere.
     * @return array{avant: array, apres: array, modifiees: int, applique: bool}
     */
    public function executer(array $idsOrdonnes, bool $appliquer = false): array
    {
        $categories = ESBTPFraisCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'sort_order']);

        $connues = $categories->pluck('id')->all();
        $inconnues = array_values(array_diff($idsOrdonnes, $connues));

        if ($inconnues !== []) {
            throw new \InvalidArgumentException(
                'Categorie(s) de frais introuvable(s) : '.implode(', ', $inconnues)
            );
        }

        if (count($idsOrdonnes) !== count(array_unique($idsOrdonnes))) {
            throw new \InvalidArgumentException(
                'Un meme frais apparait deux fois dans l\'ordre demande.'
            );
        }

        // Les categories non citees suivent, dans leur ordre actuel. Sans ca,
        // elles se retrouveraient a egalite de rang et l'ordre de service
        // deviendrait indetermine — exactement ce qu'on cherche a fixer.
        $ordreComplet = array_merge(
            $idsOrdonnes,
            array_values(array_diff($connues, $idsOrdonnes))
        );

        $rangs = array_flip($ordreComplet);
        $modifiees = [];

        foreach ($categories as $categorie) {
            $nouveau = $rangs[$categorie->id] + 1;

            if ((int) $categorie->sort_order !== $nouveau) {
                $modifiees[$categorie->id] = $nouveau;
            }
        }

        $avant = $this->lignes($categories);

        if ($appliquer && $modifiees !== []) {
            // Sauvegarde par modele, pas par `update()` de masse : `sort_order`
            // est une colonne auditee, et un update de masse ne passerait pas
            // par les evenements Eloquent — le changement d'ordre de service
            // disparaitrait de l'audit.
            DB::transaction(function () use ($categories, $modifiees): void {
                foreach ($categories as $categorie) {
                    if (! isset($modifiees[$categorie->id])) {
                        continue;
                    }

                    $categorie->sort_order = $modifiees[$categorie->id];
                    $categorie->save();
                }
            });

            Log::warning('[frais] ordre de service des categories modifie', [
                'modifiees' => $modifiees,
            ]);
        }

        $apres = $this->lignes(
            $categories
                ->map(function ($categorie) use ($rangs) {
                    $copie = clone $categorie;
                    $copie->sort_order = $rangs[$categorie->id] + 1;

                    return $copie;
                })
                ->sortBy('sort_order')
                ->values()
        );

        return [
            'avant' => $avant,
            'apres' => $apres,
            'modifiees' => count($modifiees),
            'applique' => $appliquer && $modifiees !== [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ESBTPFraisCategory>  $categories
     * @return array<int, array{id: int, code: ?string, name: ?string, sort_order: int}>
     */
    private function lignes($categories): array
    {
        return $categories->map(fn ($c) => [
            'id' => (int) $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'sort_order' => (int) $c->sort_order,
        ])->all();
    }
}
