<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Models\ESBTPUniteEnseignement;

/**
 * Une UE et ses elements tels que l'ecran /esbtp/lmd/ue les lit.
 *
 * Sorti du controleur, qui le construisait dans une closure de soixante lignes
 * au milieu de son index() : la forme du JSON de l'ecran vit ici, une fois.
 */
final class PresentationDesUnites
{
    /** Une UE telle que l'ecran /esbtp/lmd/ue la lit. */
    public function unite(ESBTPUniteEnseignement $ue, ?int $parcoursFiltre): array
    {
        $ecues = $ue->getEcuesEffectifs($parcoursFiltre);
        return [
            'id' => $ue->id,
            // Le code que la maquette imprime. Une UE propre a un
            // parcours porte une cle suffixee (`AGR2103~LPA`) qui ne
            // s'affiche pas ; `propre_a` dit a quel parcours.
            'code' => $ue->code_affiche,
            'propre_a' => CodeDeMaquette::suffixe($ue->code),
            'name' => $ue->name,
            'type_ue' => $ue->type_ue,
            'credit' => $ue->credit,
            'description' => $ue->description,
            'filiere_id' => $ue->filiere_id,
            'niveau_id' => $ue->niveau_id,
            // Les elements que CETTE vue montre : filtree sur un
            // parcours, ceux de sa maquette. Le compte par cle
            // etrangere affichait « 2 » a cote de « Aucun ECUE
            // rattache » (USAT).
            'matieres_count' => $ecues->pluck('id')->unique()->count(),
            // Elements a la fois communs et reserves a un parcours :
            // la ligne commune les montre a TOUS les parcours, ce
            // que la reservation laisse croire impossible. Calcule
            // sur le pivot entier, quel que soit le filtre.
            'communs_et_reserves' => $this->communsEtReserves($ue),
            'parcours' => $ue->parcoursMultiple->groupBy('id')->map(fn($pivots) => [
                'id' => $pivots->first()->id,
                'code' => $pivots->first()->code,
                'name' => $pivots->first()->name,
                'semestres' => $pivots->pluck('pivot.semestre')->sort()->values(),
            ])->values(),
            'ecues' => $ecues->map(fn ($e) => $this->element($e, $ue)),
        ];
    }

    /**
     * La maquette que porte chaque ligne : 0 pour la composition commune,
     * l'identifiant du parcours pour une reservation. Sans elle, l'ecran ne peut
     * ni dire a qui appartient un element, ni viser la bonne ligne pour le
     * modifier ou le retirer.
     */
    private function element($e, ESBTPUniteEnseignement $ue): array
    {
        $portee = (int) ($e->pivot->parcours_id ?? 0);
        $parcours = $portee > 0 ? $ue->parcoursMultiple->firstWhere('id', $portee) : null;

        return [
            'id' => $e->id,
            'code' => $e->code_affiche,
            'name' => $e->name,
            // Le coefficient que les bulletins utilisent vraiment
            // (meme repli que LMDBulletinService) : afficher
            // « — » laissait croire a un element sans poids.
            'coefficient' => $e->pivot->coefficient_ecue ?? $e->coefficient_ecue ?? $e->coefficient ?? 1,
            'credit' => $e->pivot->credit_ecue ?? $e->credit_ecue ?? null,
            'ordre' => $e->pivot->ordre_bulletin ?? $e->ordre_bulletin ?? 0,
            'portee' => $portee,
            'portee_code' => $parcours?->code,
            'portee_label' => $parcours ? ($parcours->name ?? $parcours->code) : null,
        ];
    }

    /**
     * @return array<int, array{id:int, name:string, reserve_a:array<int,string>}>
     */
    private function communsEtReserves(ESBTPUniteEnseignement $ue): array
    {
        $codes = $ue->parcoursMultiple->pluck('code', 'id');

        return $ue->ecues->groupBy('id')
            ->map(function ($lignes) use ($codes) {
                $portees = $lignes->map(fn ($l) => (int) ($l->pivot->parcours_id ?? 0));
                if (! $portees->contains(0) || $portees->filter()->isEmpty()) {
                    return null;
                }

                return [
                    'id' => (int) $lignes->first()->id,
                    'name' => (string) $lignes->first()->name,
                    'reserve_a' => $portees->filter()->map(fn ($id) => $codes[$id] ?? ('#' . $id))->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
