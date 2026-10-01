<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Comptabilite\Souscriptions\AjustementMontantSouscription;
use App\Domain\Comptabilite\Souscriptions\AjustementRefuse;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/cli/frais/souscriptions/ajuster — change ce qu'UN étudiant doit sur
 * UN frais (exonération, remise, dette reprise à tort).
 *
 * La correction de masse (`/frais/corriger-souscriptions`) cible un MONTANT :
 * elle ne sait pas viser une seule inscription. Montre par défaut, n'écrit que
 * sur `apply`, avec un motif obligatoire.
 *
 * @see docs/api/CLI_FRAIS_SOLDES.md
 */
class CLIAjustementSouscriptionController extends BaseApiController
{
    public function ajuster(Request $request, AjustementMontantSouscription $ajustement): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'inscription_id' => ['required', 'integer'],
            'categorie_id' => ['nullable', 'integer'],
            'montant' => ['required', 'numeric', 'min:0'],
            'motif' => ['nullable', 'string', 'max:500'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $examen = $ajustement->examiner(
            (int) $valide['inscription_id'],
            isset($valide['categorie_id']) ? (int) $valide['categorie_id'] : null,
            (float) $valide['montant'],
        );

        if (! ($valide['apply'] ?? false)) {
            return $this->successResponse($examen + ['applique' => false], "Aperçu : rien n'a été écrit.");
        }

        try {
            $resultat = $ajustement->appliquer($examen, (string) ($valide['motif'] ?? ''), (int) $request->user()->id);
        } catch (AjustementRefuse $e) {
            return $this->errorResponse($e->getMessage(), $examen, 422);
        }

        return $this->successResponse($examen + ['applique' => true, 'resultat' => $resultat], 'Montant dû ajusté.');
    }
}
