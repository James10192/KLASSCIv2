<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Cli\JournalActionsCli;
use App\Services\Emails\DiagnosticEmailsInstance;
use App\Services\Emails\NettoyageAdressesFactices;
use App\Services\Emails\RapportNettoyageEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Joignabilite des adresses de l'instance, par le CLI (le SSH des ecoles
 * n'est pas disponible : tout passe par /api/cli/*).
 */
class CLIEmailsController extends BaseApiController
{
    /**
     * `GET /api/cli/emails/diagnostic` : contrat partage avec klassci-cli, rendu
     * tel quel, sans l'enveloppe `success/data` des autres routes CLI.
     */
    public function diagnostic(Request $request, DiagnosticEmailsInstance $diagnostic): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        return response()->json($diagnostic->rapport($request->boolean('details')));
    }

    /**
     * `POST /api/cli/emails/nettoyer-factices` `{"execute": false, "inclure_comptes": false}`.
     * Simulation par defaut ; `execute` et `inclure_comptes` doivent etre de
     * vrais booleens JSON s'ils sont fournis.
     */
    public function nettoyerFactices(Request $request, RapportNettoyageEmails $rapport, NettoyageAdressesFactices $nettoyage, JournalActionsCli $journal): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        foreach (['execute', 'inclure_comptes'] as $champ) {
            if ($request->exists($champ) && ! is_bool($request->json($champ))) {
                return $this->errorResponse(sprintf('« %s » doit être un booléen JSON (true ou false).', $champ), [], 422);
            }
        }
        $executer = $request->json('execute') === true;
        $inclureComptes = $request->json('inclure_comptes') === true;

        $donnees = $rapport->produire(! $request->boolean('sans_mx'))
            + ['execute' => $executer, 'inclure_comptes' => $inclureComptes, 'modifiees' => 0, 'sauvegarde' => null];
        if ($executer) {
            $resultat = $nettoyage->executer($inclureComptes);
            $donnees['modifiees'] = $resultat['modifiees'];
            $donnees['sauvegarde'] = $resultat['sauvegarde'];
        }

        $journal->consigner($request, 'cli.emails.nettoyer_factices', [
            'execute' => $executer,
            'inclure_comptes' => $inclureComptes,
            'domaines_suspects' => count($donnees['lignes']),
            'modifiees' => $donnees['modifiees'],
            'sauvegarde' => $donnees['sauvegarde'],
        ]);

        return $this->successResponse($donnees, $executer
            ? sprintf('%d adresse(s) factice(s) vidée(s).', $donnees['modifiees'])
            : 'Simulation : rien n\'a été modifié.');
    }
}
