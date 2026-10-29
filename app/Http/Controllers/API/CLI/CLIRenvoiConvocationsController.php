<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\CLI\RenvoyerConvocationsRequest;
use App\Services\CLI\JournalActionsCli;
use App\Services\RendezVous\Renvoi\AdressesCorrigeesRdv;
use App\Services\RendezVous\Renvoi\RenvoiConvocationsCiblees;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renvoi CIBLE de convocations : trouver les reservations touchees par une
 * mauvaise adresse, puis remettre en file celles-la seulement. Rien n'est
 * envoye ici ; la tache planifiee envoie la file.
 */
class CLIRenvoiConvocationsController extends BaseApiController
{
    /** `GET /api/cli/rendez-vous/convocations/adresses-corrigees` (`cli:read`, lecture seule, masque). */
    public function adressesCorrigees(Request $request, AdressesCorrigeesRdv $adresses): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        $lignes = $adresses->lister();

        return $this->successResponse(['total' => count($lignes), 'reservations' => $lignes]);
    }

    /** `POST /api/cli/rendez-vous/convocations/renvoyer` (`cli:admin`). */
    public function renvoyer(RenvoyerConvocationsRequest $request, RenvoiConvocationsCiblees $renvoi, JournalActionsCli $journal): JsonResponse
    {
        $ids = $request->reservations();
        $donnees = $request->executer()
            ? ['execute' => true] + $renvoi->executer($ids, $request->motif(), $request->user())
            : ['execute' => false, 'reservations' => $renvoi->simuler($ids)];

        $journal->consigner($request, 'cli.rendez_vous.renvoyer_convocations', [
            'execute' => $donnees['execute'],
            'motif' => $request->motif(),
            'reservations' => $ids,
            'remises' => $donnees['remises'] ?? 0,
            'non_eligibles' => count($donnees['non_eligibles'] ?? []),
        ]);

        return $this->successResponse($donnees, $donnees['execute']
            ? sprintf('%d convocation(s) remise(s) en file. Rien n\'est encore envoyé : la tâche planifiée les enverra.', $donnees['remises'])
            : 'Simulation : rien n\'a été modifié.');
    }
}
