<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Reprise\RepriseElevesInsolvables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reprise d'historique d'une ecole qui arrive sur KLASSCI avec un arriere.
 *
 * Pilotable a distance, parce que la donnee source est un etat de compte tenu
 * ailleurs : elle se transmet en corps de requete, elle ne vit pas dans le depot.
 * Un fichier d'eleves reels committe serait une fuite, et il perimerait.
 *
 * Montre par defaut, n'ecrit que sur `apply`.
 */
class CLIRepriseController extends BaseApiController
{
    public function elevesInsolvables(
        Request $request,
        RepriseElevesInsolvables $reprise
    ): JsonResponse {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'annee_id' => ['required', 'integer'],
            'apply' => ['nullable', 'boolean'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.matricule' => ['required', 'string', 'max:50'],
            'lignes.*.nom' => ['required', 'string', 'max:255'],
            'lignes.*.prenoms' => ['nullable', 'string', 'max:255'],
            'lignes.*.telephone' => ['nullable', 'string', 'max:30'],
            'lignes.*.classe_id' => ['required', 'integer'],
            'lignes.*.total_frais' => ['required', 'numeric', 'min:0'],
            'lignes.*.reduction' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.montant_reclame' => ['required', 'numeric', 'min:0'],
            'lignes.*.paye' => ['required', 'numeric', 'min:0'],
            'lignes.*.reste_du' => ['required', 'numeric', 'min:0'],
        ]);

        $resultat = $reprise->executer(
            $valide['lignes'],
            (int) $valide['annee_id'],
            (bool) ($valide['apply'] ?? false),
        );

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf(
                    '%d eleve(s) repris, %d ecarte(s). Les reliquats se poseront a la reinscription.',
                    $resultat['retenus'],
                    $resultat['ecartes']
                )
                : sprintf(
                    "%d eleve(s) reconstituable(s), %d ecarte(s). Rien n'a ete ecrit.",
                    $resultat['retenus'],
                    $resultat['ecartes']
                )
        );
    }
}
