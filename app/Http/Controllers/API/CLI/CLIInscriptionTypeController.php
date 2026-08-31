<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recensement et nettoyage de `type_inscription`, pilotables a distance.
 *
 * La colonne decide qui est un nouvel inscrit. Une regle metier va s'y brancher
 * — « ce frais ne concerne que les nouveaux » — et elle ne vaudra que ce que
 * vaut cette donnee. On la regarde donc AVANT de construire dessus.
 *
 * Le recensement est en lecture seule. Le nettoyage montre par defaut et
 * n'ecrit que si on le lui demande.
 */
class CLIInscriptionTypeController extends BaseApiController
{
    /**
     * Ce que la colonne contient reellement, tenant par tenant.
     */
    public function recenser(Request $request, NormalisationTypeInscription $service): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $recensement = $service->recenser();

        return $this->successResponse(
            $recensement,
            sprintf(
                '%d ligne(s) hors enum, %d desaccord(s) entre le type declare et le rang reel.',
                $recensement['hors_enum_total'],
                $recensement['desaccords_total']
            )
        );
    }

    /**
     * Ramene les valeurs hors enum sur la valeur attendue.
     */
    public function normaliser(Request $request, NormalisationTypeInscription $service): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'apply' => ['nullable', 'boolean'],
        ]);

        $resultat = $service->executer((bool) ($valide['apply'] ?? false));

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf('%d ligne(s) normalisee(s).', $resultat['total'])
                : sprintf("%d ligne(s) a normaliser. Rien n'a ete ecrit.", $resultat['total'])
        );
    }
}
