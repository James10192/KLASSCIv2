<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Reprise\InscriptionsAnneeEcoulee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reprise de l'historique d'une ecole arrivee en cours de route.
 *
 * Pilotable a distance, parce que la donnee source est un etat tenu ailleurs :
 * elle voyage dans le corps de la requete. Un fichier d'eleves reels committe
 * serait une fuite, et il perimerait.
 *
 * Montre par defaut, n'ecrit que sur `apply`.
 */
class CLIRepriseController extends BaseApiController
{
    /**
     * Premiere marche : les eleves et leurs inscriptions sur l'annee ecoulee.
     * Ni frais, ni versement, ni reliquat.
     */
    public function inscriptionsAnneeEcoulee(
        Request $request,
        InscriptionsAnneeEcoulee $reprise
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
            // Le libelle d'origine ne sert qu'a rendre les ecarts lisibles :
            // sans lui, la colonne « classe » du rapport affiche un tiret et
            // l'ecart devient inexploitable pour celui qui doit le corriger.
            'lignes.*.classe_pdf' => ['nullable', 'string', 'max:100'],
            'lignes.*.telephone_anomalie' => ['nullable', 'string', 'max:255'],
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
                    '%d eleve(s) et %d inscription(s) crees, %d ligne(s) ecartee(s).',
                    $resultat['ecrit']['etudiants'],
                    $resultat['ecrit']['inscriptions'],
                    $resultat['ecartes']
                )
                : sprintf(
                    "%d eleve(s) et %d inscription(s) a creer, %d ligne(s) ecartee(s). Rien n'a ete ecrit.",
                    $resultat['a_creer']['etudiants'],
                    $resultat['a_creer']['inscriptions'],
                    $resultat['ecartes']
                )
        );
    }
}
