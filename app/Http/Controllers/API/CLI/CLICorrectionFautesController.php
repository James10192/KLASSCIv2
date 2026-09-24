<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\CLI\CorrigerFautesRequest;
use App\Services\CLI\JournalActionsCli;
use App\Services\Emails\Fautes\CorrectionFautes;
use App\Services\Emails\Fautes\PropositionsCorrection;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * `POST /api/cli/emails/corriger-fautes` : propose (simulation) puis corrige,
 * cle par cle, les adresses dont le domaine est une faute de frappe. Voir
 * CorrectionFautes et docs/api/CLI_EMAILS_DIAGNOSTIC.md.
 */
class CLICorrectionFautesController extends BaseApiController
{
    public function __invoke(
        CorrigerFautesRequest $request,
        PropositionsCorrection $propositions,
        CorrectionFautes $correction,
        JournalActionsCli $journal,
    ): JsonResponse {
        $donnees = ['execute' => $request->executer(), 'inclure_comptes' => $request->inclureComptes(),
            'propositions_total' => 0, 'propositions' => [], 'corrigees' => 0, 'ignorees' => [],
            'sauvegarde' => null, 'convocations_a_renvoyer' => 0];

        if (! $request->executer()) {
            $liste = $propositions->lister($request->inclureComptes(), $request->avecMx());
            $donnees['propositions_total'] = $liste['total'];
            $donnees['propositions'] = $liste['propositions'];
        } else {
            try {
                $donnees = $correction->executer($request->corrections(), $request->inclureComptes(), $request->user(), $request->avecMx()) + $donnees;
            } catch (RuntimeException $e) {
                return $this->errorResponse($e->getMessage(), [], 500);
            }
        }

        $journal->consigner($request, 'cli.emails.corriger_fautes', [
            'execute' => $donnees['execute'],
            'propositions' => $donnees['propositions_total'],
            'corrigees' => $donnees['corrigees'],
            'ignorees' => count($donnees['ignorees']),
            'sauvegarde' => $donnees['sauvegarde'],
        ]);

        return $this->successResponse($donnees, $donnees['execute']
            ? sprintf('%d adresse(s) corrigée(s).', $donnees['corrigees'])
            : sprintf('Simulation : %d correction(s) proposée(s), rien n\'a été modifié.', $donnees['propositions_total']));
    }
}
