<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;

/**
 * Retirer des matieres de la maquette d'un couple filiere x niveau.
 *
 * En deux temps comme le chargement : `preparer()` constate, `appliquer()`
 * ecrit. La simulation est donc la meme lecture que l'ecriture, pas une
 * branche parallele.
 *
 * DEUX GARDES, ET ELLES NE DISENT PAS LA MEME CHOSE :
 *
 * - une matiere absente de la maquette n'est pas une erreur, mais elle n'est
 *   pas non plus « retiree ». La compter comme telle faisait annoncer
 *   « 3 matiere(s) retiree(s) » pour un lot dont une seule etait la ;
 * - une matiere qui porte des EVALUATIONS sur ce couple est refusee tant que
 *   l'appelant ne l'a pas confirme : la note resterait en base sans plus
 *   apparaitre nulle part.
 */
final class RetraitDeMaquette
{
    public function __construct(
        private readonly ResolutionDeMatiere $resolution,
        private readonly LiaisonsDeMatiere $liaisons,
    ) {}

    /**
     * Ce que ce retrait ferait, sans rien ecrire.
     *
     * @param  array<int, mixed>  $matieres
     * @return array{lignes: array<int, array<string, mixed>>, ambigus: array<int, array<string, mixed>>, introuvables: array<int, array<string, mixed>>, notees: array<int, array<string, mixed>>}
     */
    public function preparer(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, array $matieres): array
    {
        $classeIds = ESBTPClasse::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->pluck('id');

        $plan = ['lignes' => [], 'ambigus' => [], 'introuvables' => [], 'notees' => []];

        foreach (array_values($matieres) as $entree) {
            $resolue = $this->resolution->matiere($entree, (int) $filiere->id, (int) $niveau->id);

            if ($resolue['statut'] === 'ambigu') {
                $plan['ambigus'][] = ['libelle' => $resolue['libelle'], 'candidats' => $resolue['candidats']];

                continue;
            }
            if ($resolue['statut'] === 'introuvable') {
                $plan['introuvables'][] = ['libelle' => $resolue['libelle']];

                continue;
            }

            $matiere = $resolue['matiere'];

            $evaluations = $classeIds->isEmpty() ? 0 : ESBTPEvaluation::query()
                ->where('matiere_id', $matiere->id)
                ->whereIn('classe_id', $classeIds)
                ->count();

            if ($evaluations > 0) {
                $plan['notees'][] = [
                    'matiere_id' => $matiere->id,
                    'matiere' => $matiere->name,
                    'evaluations' => $evaluations,
                ];
            }

            $plan['lignes'][] = [
                'matiere_id' => $matiere->id,
                'matiere' => $matiere->name,
                'code' => $matiere->code,
                'dans_la_maquette' => ESBTPMatiereFilierNiveau::query()
                    ->where('filiere_id', $filiere->id)
                    ->where('niveau_etude_id', $niveau->id)
                    ->where('matiere_id', $matiere->id)
                    ->exists(),
                'evaluations_sur_ce_couple' => $evaluations,
            ];
        }

        return $plan;
    }

    /**
     * Retire ce que le plan designe, et dit combien l'etaient vraiment.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array{lignes: array<int, array<string, mixed>>, retirees: int}
     */
    public function appliquer(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, array $lignes): array
    {
        $retirees = 0;

        foreach ($lignes as $index => $ligne) {
            if (! $ligne['dans_la_maquette']) {
                $lignes[$index]['retire'] = ['canonique' => 0, 'places_semestre' => 0];

                continue;
            }

            $lignes[$index]['retire'] = $this->liaisons->retirer(
                (int) $ligne['matiere_id'],
                (int) $filiere->id,
                (int) $niveau->id,
            );
            $retirees++;
        }

        return ['lignes' => $lignes, 'retirees' => $retirees];
    }
}
