<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\CLI\RattrapageConvocationsRequest;
use App\Services\CLI\JournalActionsCli;
use App\Services\RendezVous\Rattrapage\RattrapageConvocations;
use Illuminate\Http\JsonResponse;

/**
 * `POST /api/cli/rendez-vous/rattrapage-convocations` : rattache aux
 * convocations d'avant le suivi l'identifiant du courriel MailPulse qui les a
 * portees. Simulation par defaut ; voir RattrapageConvocations.
 */
class CLIRattrapageConvocationsController extends BaseApiController
{
    public function __invoke(RattrapageConvocationsRequest $request, RattrapageConvocations $rattrapage, JournalActionsCli $journal): JsonResponse
    {
        $rapport = $rattrapage->traiter($request->courriels(), $request->executer(), $request->user());

        $journal->consigner($request, 'cli.rendez_vous.rattrapage_convocations', [
            'source' => RattrapageConvocations::SOURCE,
            'messages_recus' => count($request->courriels()),
        ] + array_diff_key($rapport, ['exemples' => true]));

        return $this->successResponse($rapport, $rapport['execute']
            ? sprintf('%d convocation(s) rattachée(s) à leur courriel MailPulse.', $rapport['ecrites'])
            : sprintf('Simulation : %d convocation(s) seraient rattachées, rien n\'a été modifié.', $rapport['appariees']));
    }
}
