<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Frais\CorrectionMontantSouscriptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Operations de frais pilotables a distance.
 *
 * Une correction de montant se decide en regardant les etudiants concernes, pas
 * en lancant une commande a l'aveugle. Cet endpoint MONTRE par defaut et
 * n'ecrit que si on le lui demande explicitement.
 */
class CLIFraisController extends BaseApiController
{
    public function corrigerSouscriptions(
        Request $request,
        CorrectionMontantSouscriptions $correction
    ): JsonResponse {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'from' => ['required', 'numeric', 'min:0'],
            'to' => ['required', 'numeric', 'min:0'],
            'categorie_id' => ['nullable', 'integer'],
            'annee_id' => ['nullable', 'integer'],
            // `apply` doit etre demande. Le defaut ne touche a rien : on ne
            // corrige pas des montants sans les avoir regardes.
            'apply' => ['nullable', 'boolean'],
        ]);

        if ((float) $valide['from'] === (float) $valide['to']) {
            return $this->errorResponse('from et to sont identiques : il n\'y a rien a corriger.', [], 422);
        }

        $resultat = $correction->executer(
            (float) $valide['from'],
            (float) $valide['to'],
            (bool) ($valide['apply'] ?? false),
            $valide['categorie_id'] ?? null,
            $valide['annee_id'] ?? null,
        );

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf('%d souscription(s) corrigee(s).', $resultat['total'])
                : sprintf('%d souscription(s) concernee(s). Rien n\'a ete ecrit.', $resultat['total'])
        );
    }
}
