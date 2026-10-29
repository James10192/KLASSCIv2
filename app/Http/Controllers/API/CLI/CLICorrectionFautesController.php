<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\CLI\CorrigerFautesRequest;
use App\Services\CLI\JournalActionsCli;
use App\Services\Emails\Fautes\CorrectionFautes;
use App\Services\Emails\Fautes\EchecSauvegarde;
use App\Services\Emails\Fautes\PropositionsCorrection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * `POST /api/cli/emails/corriger-fautes` : propose (simulation) puis corrige,
 * groupe par groupe, les adresses dont le domaine est une faute de frappe.
 * Voir CorrectionFautes et docs/api/CLI_EMAILS_DIAGNOSTIC.md.
 *
 * Une erreur ne rend jamais de detail technique (requete, chemin) : un
 * message fixe, le detail va au journal de l'application. Le journal CLI
 * trace l'appel dans tous les cas.
 */
class CLICorrectionFautesController extends BaseApiController
{
    private const ECHEC_SAUVEGARDE = 'La sauvegarde préalable n\'a pas pu être écrite : aucune adresse n\'a été modifiée.';

    private const ECHEC = 'La correction des adresses a échoué : aucune adresse n\'a été modifiée. Le détail est dans le journal de l\'application.';

    public function __invoke(
        CorrigerFautesRequest $request,
        PropositionsCorrection $propositions,
        CorrectionFautes $correction,
        JournalActionsCli $journal,
    ): JsonResponse {
        $donnees = ['execute' => $request->executer(), 'inclure_comptes' => $request->inclureComptes(),
            'inclure_probables' => $request->inclureProbables(), 'propositions_total' => 0, 'propositions' => [],
            'corrigees' => 0, 'ignorees' => [], 'sauvegarde' => null, 'convocations_a_renvoyer' => 0];
        $erreur = null;

        try {
            if (! $request->executer()) {
                $liste = $propositions->lister($request->inclureComptes(), $request->inclureProbables());
                $donnees['propositions_total'] = $liste['total'];
                $donnees['propositions'] = $liste['propositions'];
            } else {
                $donnees = $correction->executer($request->corrections(), $request->inclureComptes(), $request->inclureProbables(), $request->user()) + $donnees;
            }
        } catch (EchecSauvegarde $e) {
            Log::error('Correction d\'adresses : sauvegarde en echec', ['erreur' => $e->getMessage()]);
            $erreur = self::ECHEC_SAUVEGARDE;
        } catch (\Throwable $e) {
            Log::error('Correction d\'adresses : erreur', ['erreur' => $e->getMessage(), 'classe' => $e::class]);
            $erreur = self::ECHEC;
        }

        $journal->consigner($request, 'cli.emails.corriger_fautes', [
            'execute' => $donnees['execute'],
            'propositions' => $donnees['propositions_total'],
            'corrigees' => $donnees['corrigees'],
            'ignorees' => count($donnees['ignorees']),
            'sauvegarde' => $donnees['sauvegarde'],
            'erreur' => $erreur !== null,
        ]);

        if ($erreur !== null) {
            return $this->errorResponse($erreur, [], 500);
        }

        return $this->successResponse($donnees, $donnees['execute']
            ? sprintf('%d adresse(s) corrigée(s).', $donnees['corrigees'])
            : sprintf('Simulation : %d correction(s) proposée(s), rien n\'a été modifié.', $donnees['propositions_total']));
    }
}
