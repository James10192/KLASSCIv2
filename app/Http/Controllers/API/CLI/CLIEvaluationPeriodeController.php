<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evaluations placees sur un semestre ou leur classe n'accueille encore
 * personne.
 *
 * Une classe de specialite issue du tronc commun ne s'ouvre qu'au semestre
 * porte par esbtp_classe_orientation_targets.semestre_activation. Une
 * evaluation creee avant ce semestre produit une note qui remonte ensuite sur
 * le bulletin de tronc commun de l'etudiant.
 *
 * Le diagnostic est en lecture seule. La reparation est en simulation par
 * defaut : elle n'ecrit que si dry_run vaut explicitement false.
 */
class CLIEvaluationPeriodeController extends BaseApiController
{
    /**
     * GET /api/cli/diagnostics/evaluations-periode
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $anomalies = $this->detecter($request->query('annee_id'));

        return $this->successResponse([
            'anomalies' => $anomalies,
            'total' => count($anomalies),
            'notes_concernees' => array_sum(array_column($anomalies, 'nb_notes')),
            'lecture' => 'Ces evaluations sont placees sur un semestre anterieur '
                .'a l ouverture de leur classe. Les notes correspondantes remontent '
                .'sur les bulletins de tronc commun des etudiants.',
        ]);
    }

    /**
     * POST /api/cli/diagnostics/evaluations-periode/repair
     *
     * Deplace chaque evaluation vers le semestre d'ouverture de sa classe, et
     * aligne la colonne denormalisee des notes. Simulation par defaut.
     */
    public function repair(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $simulation = $request->boolean('dry_run', true);
        $anomalies = $this->detecter($request->query('annee_id'));

        if ($anomalies === []) {
            return $this->successResponse([
                'dry_run' => $simulation,
                'traitees' => 0,
            ], 'Aucune evaluation a deplacer.');
        }

        if ($simulation) {
            return $this->successResponse([
                'dry_run' => true,
                'a_traiter' => $anomalies,
                'total' => count($anomalies),
            ], 'Simulation : rien n a ete ecrit. Relancer avec dry_run=false pour appliquer.');
        }

        $traitees = [];

        DB::transaction(function () use ($anomalies, &$traitees) {
            foreach ($anomalies as $a) {
                $evaluation = ESBTPEvaluation::find($a['evaluation_id']);
                if (! $evaluation) {
                    continue;
                }

                $avant = $evaluation->periode;
                $evaluation->periode = 'semestre'.$a['semestre_attendu'];
                $evaluation->save();

                // esbtp_notes porte une copie denormalisee du semestre. Sans
                // cette mise a jour, la note resterait rattachee a l ancien
                // semestre partout ou cette colonne est lue.
                $notes = ESBTPNote::where('evaluation_id', $evaluation->id)
                    ->update(['semestre' => 'semestre'.$a['semestre_attendu']]);

                $traitees[] = [
                    'evaluation_id' => $evaluation->id,
                    'titre' => $a['titre'],
                    'classe' => $a['classe'],
                    'matiere' => $a['matiere'],
                    'de' => $avant,
                    'vers' => $evaluation->periode,
                    'notes_realignees' => $notes,
                ];
            }
        });

        Log::warning('CLI: evaluations deplacees vers le semestre d ouverture de leur classe', [
            'nombre' => count($traitees),
            'evaluations' => array_column($traitees, 'evaluation_id'),
        ]);

        return $this->successResponse([
            'dry_run' => false,
            'traitees' => $traitees,
            'total' => count($traitees),
        ], count($traitees).' evaluation(s) deplacee(s).');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detecter(?string $anneeId): array
    {
        // Semestre d ouverture le plus precoce, par classe cible.
        $ouvertures = DB::table('esbtp_classe_orientation_targets')
            ->where('is_active', true)
            ->groupBy('target_classe_id')
            ->pluck(DB::raw('MIN(semestre_activation)'), 'target_classe_id');

        if ($ouvertures->isEmpty()) {
            return [];
        }

        $evaluations = ESBTPEvaluation::query()
            ->with(['classe:id,name', 'matiere:id,name'])
            ->whereIn('classe_id', $ouvertures->keys())
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', (int) $anneeId))
            ->where('status', '!=', 'cancelled')
            ->get();

        $anomalies = [];

        foreach ($evaluations as $evaluation) {
            $semestre = ESBTPEvaluation::numeroDeSemestre((string) $evaluation->periode);
            $attendu = (int) $ouvertures[$evaluation->classe_id];

            if ($semestre === null || $semestre >= $attendu) {
                continue;
            }

            $anomalies[] = [
                'evaluation_id' => $evaluation->id,
                'titre' => $evaluation->titre,
                'classe' => $evaluation->classe->name ?? null,
                'matiere' => $evaluation->matiere->name ?? null,
                'periode_actuelle' => $evaluation->periode,
                'semestre_attendu' => $attendu,
                'nb_notes' => ESBTPNote::where('evaluation_id', $evaluation->id)->count(),
            ];
        }

        return $anomalies;
    }
}
