<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Controllers\AcademicPilotage\AcademicCoverageController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Répare les dates saisies hors de l'année universitaire de l'évaluation.
 *
 * Exemple : une évaluation de novembre 2026 rattachée à l'année 2025-2026
 * devient novembre 2025. Le jour et le mois ne sont jamais modifiés.
 */
class CLIEvaluationDateCoherenceController extends BaseApiController
{
    /**
     * GET /api/cli/diagnostics/evaluations-dates?annee_id=4
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->annee($request);

        if (! $annee) {
            return $this->errorResponse('Année universitaire introuvable.', [], 404);
        }

        $anomalies = $this->detecter($annee);

        return $this->successResponse([
            'annee' => $this->anneePayload($annee),
            'total' => count($anomalies),
            'evaluations' => $anomalies,
        ], count($anomalies).' évaluation(s) datée(s) hors année universitaire.');
    }

    /**
     * POST /api/cli/evaluations/corriger-dates
     *
     * Body: { annee_id: int, dry_run?: bool }. La simulation est le défaut.
     */
    public function corriger(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'annee_id' => 'required|integer',
            'dry_run' => 'nullable|boolean',
        ]);

        $annee = ESBTPAnneeUniversitaire::find((int) $validated['annee_id']);
        if (! $annee) {
            return $this->errorResponse('Année universitaire introuvable.', [], 404);
        }

        $dryRun = $request->boolean('dry_run', true);
        $anomalies = $this->detecter($annee);

        if ($dryRun) {
            return $this->successResponse([
                'dry_run' => true,
                'annee' => $this->anneePayload($annee),
                'total' => count($anomalies),
                'a_corriger' => $anomalies,
            ], 'Simulation : aucune date n’a été modifiée.');
        }

        $corrigees = [];
        DB::transaction(function () use ($anomalies, &$corrigees): void {
            foreach ($anomalies as $anomalie) {
                $evaluation = ESBTPEvaluation::find($anomalie['evaluation_id']);
                if (! $evaluation) {
                    continue;
                }

                $evaluation->date_evaluation = $anomalie['date_cible'];
                $evaluation->save();

                $corrigees[] = $anomalie;
            }
        });

        foreach (collect($corrigees)->pluck('classe_id')->unique() as $classeId) {
            foreach (['semestre1', 'semestre2', 'annuel'] as $periode) {
                Cache::forget(AcademicCoverageController::cle((int) $classeId, (int) $annee->id, $periode));
            }
        }

        Log::warning('CLI: dates des évaluations réalignées avec l’année universitaire', [
            'annee_id' => $annee->id,
            'total' => count($corrigees),
            'evaluation_ids' => array_column($corrigees, 'evaluation_id'),
            'caller_user_id' => $request->user()->id,
        ]);

        return $this->successResponse([
            'dry_run' => false,
            'annee' => $this->anneePayload($annee),
            'total' => count($corrigees),
            'corrigees' => $corrigees,
        ], count($corrigees).' date(s) d’évaluation corrigée(s).');
    }

    private function annee(Request $request): ?ESBTPAnneeUniversitaire
    {
        $id = $request->integer('annee_id');

        return $id > 0 ? ESBTPAnneeUniversitaire::find($id) : ESBTPAnneeUniversitaire::anneeCourante();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detecter(ESBTPAnneeUniversitaire $annee): array
    {
        $start = $annee->start_date ? Carbon::parse($annee->start_date)->startOfDay() : null;
        $end = $annee->end_date ? Carbon::parse($annee->end_date)->endOfDay() : null;

        if (! $start || ! $end || $end->lessThan($start)) {
            return [];
        }

        return ESBTPEvaluation::query()
            ->with(['classe:id,name', 'matiere:id,name'])
            ->where('annee_universitaire_id', $annee->id)
            ->whereNotNull('date_evaluation')
            ->where(function ($q): void {
                $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);
            })
            ->orderBy('date_evaluation')
            ->get()
            ->map(function (ESBTPEvaluation $evaluation) use ($start, $end): ?array {
                $date = Carbon::parse($evaluation->date_evaluation)->startOfDay();
                if ($date->betweenIncluded($start, $end)) {
                    return null;
                }

                $cible = $date->copy()->setYear($date->month >= $start->month ? $start->year : $end->year);
                if (! $cible->betweenIncluded($start, $end)) {
                    return null;
                }

                return [
                    'evaluation_id' => (int) $evaluation->id,
                    'classe_id' => (int) $evaluation->classe_id,
                    'classe' => $evaluation->classe?->name,
                    'matiere' => $evaluation->matiere?->name,
                    'titre' => $evaluation->titre,
                    'periode' => $evaluation->periode,
                    'date_actuelle' => $date->toDateString(),
                    'date_cible' => $cible->toDateString(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function anneePayload(ESBTPAnneeUniversitaire $annee): array
    {
        return [
            'id' => (int) $annee->id,
            'name' => $annee->name,
            'start_date' => optional($annee->start_date)->toDateString(),
            'end_date' => optional($annee->end_date)->toDateString(),
        ];
    }
}
