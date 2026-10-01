<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\DeplacementDePeriode;
use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Deplacement explicite d'evaluations d'un semestre vers un autre.
 *
 * La periode d'une evaluation est saisie a la main. Rien dans la donnee ne
 * permet de deviner la bonne : les dates des deux semestres se chevauchent
 * (a l'ESBTP Abidjan, semestre1 et semestre2 couvrent tous deux octobre a
 * aout). Un meme lot cree le meme jour se retrouve range en S1 pour une
 * classe et en S2 pour la voisine, et le bulletin du semestre s'en trouve
 * incomplet ou pollue selon le sens de l'erreur.
 *
 * Cet endpoint ne devine donc rien : il deplace la liste d'evaluations qu'on
 * lui donne, apres qu'un humain l'a arretee. La simulation est le defaut.
 * L'apercu et l'ecriture vivent dans DeplacementDePeriode, partage avec Nanan.
 *
 * A distinguer de CLIEvaluationPeriodeController, qui detecte tout seul un
 * cas precis : les evaluations posees avant l'ouverture de leur classe de
 * specialite. Ici il n'y a aucune detection possible, seulement une decision.
 */
class CLIEvaluationDeplacementController extends BaseApiController
{
    /**
     * POST /api/cli/evaluations/deplacer-periode
     */
    public function deplacer(Request $request, ?DeplacementDePeriode $deplacement = null): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'evaluation_ids' => 'required|array|min:1|max:'.DeplacementDePeriode::LOT_MAX,
            'evaluation_ids.*' => 'required|integer|min:1',
            'periode' => 'required|string|in:semestre1,semestre2',
            'dry_run' => 'nullable|boolean',
        ]);

        $simulation = $request->boolean('dry_run', true);
        $cible = $valide['periode'];
        $deplacement ??= app(DeplacementDePeriode::class);

        [$aDeplacer, $deja, $introuvables] = $deplacement->apercu($valide['evaluation_ids'], $cible);

        if ($simulation) {
            return $this->successResponse([
                'dry_run' => true,
                'a_deplacer' => $aDeplacer,
                'deja_sur_la_cible' => $deja,
                'introuvables' => $introuvables,
                'total_a_deplacer' => count($aDeplacer),
                'notes_concernees' => array_sum(array_column($aDeplacer, 'nb_notes')),
            ], 'Simulation : rien n a ete ecrit. Relancer avec dry_run=false pour appliquer.');
        }

        ['traitees' => $traitees, 'recalcul' => $recalcul] = $deplacement->appliquer($aDeplacer, $cible, $request->user()->id);

        Log::warning('CLI: evaluations deplacees de semestre sur decision humaine', [
            'periode_cible' => $cible,
            'nombre' => count($traitees),
            'evaluations' => array_column($traitees, 'evaluation_id'),
        ]);

        return $this->successResponse([
            'dry_run' => false,
            'traitees' => $traitees,
            'deja_sur_la_cible' => $deja,
            'introuvables' => $introuvables,
            'total' => count($traitees),
            'notes_realignees' => array_sum(array_column($traitees, 'notes_realignees')),
            'recalculs_tentes' => $recalcul['recalculs_tentes'],
            'agregats_orphelins' => $recalcul['orphelins'],
            'recalculs_en_echec' => $recalcul['echecs'],
            'recalcul_reporte' => $recalcul['reporte'],
            'perimetres_reportes' => $recalcul['perimetres_reportes'],
        ], count($traitees).' evaluation(s) deplacee(s) vers '.$cible.'.'
            .RecalculApresDeplacement::motDeLaFin($recalcul));
    }
}
