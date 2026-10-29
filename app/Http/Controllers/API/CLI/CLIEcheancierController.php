<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\EcheancierRecomputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Regenerer les snapshots d'echeancier d'un tenant depuis klassci-cli.
 *
 * Meme moteur que `php artisan echeanciers:recompute` : le controleur ne fait
 * que valider, resoudre l'annee et repondre. Simulation par defaut — il faut
 * dire dry_run=false pour ecrire.
 */
class CLIEcheancierController extends BaseApiController
{
    /**
     * POST /api/cli/echeanciers/recompute
     */
    public function recompute(Request $request, EcheancierRecomputeService $service): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'annee_id' => 'nullable|integer|min:1',
            'inscription_id' => 'nullable|integer|min:1',
            'dry_run' => 'nullable|boolean',
            'chunk' => 'nullable|integer|min:1|max:'.EcheancierRecomputeService::CHUNK_MAX,
        ]);

        $simulation = $request->boolean('dry_run', true);
        $inscriptionId = isset($valide['inscription_id']) ? (int) $valide['inscription_id'] : null;
        $annee = null;

        if (! $inscriptionId) {
            $annee = isset($valide['annee_id'])
                ? ESBTPAnneeUniversitaire::find($valide['annee_id'])
                : $this->getAnneeCouraante();

            if (! $annee) {
                return $this->errorResponse(
                    isset($valide['annee_id'])
                        ? 'Annee universitaire introuvable.'
                        : 'Aucune annee universitaire courante configuree.',
                    ['code' => 'NO_ACADEMIC_YEAR'],
                    422
                );
            }
        }

        $rapport = $service->recalculer(
            $annee?->id,
            $inscriptionId,
            $simulation,
            (int) ($valide['chunk'] ?? EcheancierRecomputeService::CHUNK_PAR_DEFAUT),
        );

        $rapport['annee'] = $annee ? ['id' => $annee->id, 'name' => $annee->name] : null;
        $rapport['inscription_id'] = $inscriptionId;

        if (! $simulation) {
            Log::warning('CLI: snapshots d echeancier regeneres', [
                'annee_id' => $annee?->id,
                'inscription_id' => $inscriptionId,
                'traitees' => $rapport['traitees'],
                'creees' => $rapport['creees'],
                'mises_a_jour' => $rapport['mises_a_jour'],
                'erreurs' => $rapport['erreurs'],
            ]);
        }

        $message = $simulation
            ? sprintf('Simulation : %d inscription(s) dans le perimetre (%d a creer, %d a mettre a jour). Relancer avec dry_run=false pour appliquer.',
                $rapport['perimetre'], $rapport['a_creer'], $rapport['a_mettre_a_jour'])
            : sprintf('%d snapshot(s) cree(s), %d mis a jour, %d erreur(s).',
                $rapport['creees'], $rapport['mises_a_jour'], $rapport['erreurs']);

        return $this->successResponse($rapport, $message);
    }
}
