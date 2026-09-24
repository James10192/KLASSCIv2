<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Cli\JournalActionsCli;
use App\Services\RendezVous\FamillesARecontacter;
use App\Services\RendezVous\SynchroStatutsConvocations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Suivi des convocations de rendez-vous par le CLI : relire la remise chez
 * MailPulse, et savoir quelles familles recontacter.
 */
class CLISuiviConvocationsController extends BaseApiController
{
    /** `POST /api/cli/rendez-vous/synchroniser-convocations` : un passage de synchronisation. */
    public function synchroniser(Request $request, SynchroStatutsConvocations $synchro, JournalActionsCli $journal): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $r = $synchro->synchroniser(max(1, min(500, (int) $request->input('max', 100))));
        $donnees = [
            'lus' => $r['lues'],
            'delivrees' => $r['delivrees'],
            'echecs' => $r['echecs'],
            'rebonds' => $r['rebonds'],
            'supprimees' => $r['supprimees'],
            'en_attente' => $r['en_transit'],
            'arretee_sur' => $r['bloque'],
            'erreurs' => $r['erreurs'],
        ];
        $journal->consigner($request, 'cli.rendez_vous.synchroniser_convocations', $donnees);

        // Un arret (MailPulse desactive, injoignable...) reste une reponse lisible :
        // `arretee_sur` le dit, les compteurs disent ce qui a ete fait avant.
        return $this->successResponse($donnees, $r['bloque'] !== null
            ? 'Synchronisation arrêtée : '.$r['bloque']
            : sprintf('%d convocation(s) relue(s).', $r['lues']));
    }

    /** `GET /api/cli/rendez-vous/familles[?details=1]` : lecture seule, tout est masque. */
    public function familles(Request $request, FamillesARecontacter $familles): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $rapport = $familles->rapport();
        $donnees = ['synthese' => $rapport['synthese']];
        if ($request->boolean('details')) {
            $donnees['familles'] = $rapport['familles'];
        }

        return $this->successResponse($donnees, sprintf('%d famille(s).', $rapport['synthese']['familles']));
    }
}
