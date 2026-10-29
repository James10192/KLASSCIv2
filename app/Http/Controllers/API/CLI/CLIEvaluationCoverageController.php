<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIEvaluationCoverageController extends BaseApiController
{
    /**
     * GET /api/cli/evaluations/coverage
     *
     * Couverture notes/évaluations agrégée par filière pour un niveau
     * (défaut : 1ère année BTS). Lecture seule.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $validated = $request->validate([
            'annee_id' => 'nullable|integer',
            'systeme' => 'nullable|string|in:BTS,LMD',
            'year' => 'nullable|integer|min:1|max:10',
            'filiere_id' => 'nullable|integer',
            'periode' => 'nullable|string|max:30',
        ]);

        $annee = isset($validated['annee_id'])
            ? ESBTPAnneeUniversitaire::find($validated['annee_id'])
            : $this->getAnneeCouraante();

        if (! $annee) {
            return $this->errorResponse(
                'Aucune annee universitaire courante configuree.',
                ['code' => 'NO_ACADEMIC_YEAR'],
                422
            );
        }

        $systeme = $validated['systeme'] ?? 'BTS';
        $year = (int) ($validated['year'] ?? 1);
        $filiereId = isset($validated['filiere_id']) ? (int) $validated['filiere_id'] : null;
        $periode = $validated['periode'] ?? null;

        $classes = ESBTPClasse::query()
            ->where('systeme_academique', $systeme)
            ->whereHas('niveau', fn ($q) => $q->where('year', $year))
            ->when($filiereId, fn ($q) => $q->where('filiere_id', $filiereId))
            ->with(['filiere:id,name', 'niveau:id,name,year'])
            ->withCount(['inscriptions as effectif' => function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree');
            }])
            ->orderBy('filiere_id')
            ->orderBy('name')
            ->get();

        $classIds = $classes->pluck('id');
        $evaluations = collect();
        $noteStats = collect();
        $studentsByFiliereMatiere = collect();

        if ($classIds->isNotEmpty()) {
            $evaluations = ESBTPEvaluation::query()
                ->whereIn('classe_id', $classIds)
                ->where('annee_universitaire_id', $annee->id)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);
                })
                ->when($this->shouldFilterPeriode($periode), function ($q) use ($periode) {
                    $q->whereIn('periode', ESBTPEvaluation::aliasDePeriode($periode));
                })
                ->with(['matiere:id,name,code', 'classe:id,name,filiere_id'])
                ->orderBy('matiere_id')
                ->orderBy('date_evaluation')
                ->get();

            $evalIds = $evaluations->pluck('id');

            if ($evalIds->isNotEmpty()) {
                $noteStats = ESBTPNote::query()
                    ->whereIn('evaluation_id', $evalIds)
                    ->selectRaw('evaluation_id')
                    ->selectRaw('COUNT(*) as nb_notes')
                    ->selectRaw('SUM(CASE WHEN is_absent = 1 THEN 1 ELSE 0 END) as nb_absents')
                    ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(is_absent, 0) = 0 AND note IS NOT NULL THEN etudiant_id END) as nb_numeric')
                    ->groupBy('evaluation_id')
                    ->get()
                    ->keyBy('evaluation_id');

                $studentsByFiliereMatiere = ESBTPNote::query()
                    ->join('esbtp_evaluations', 'esbtp_evaluations.id', '=', 'esbtp_notes.evaluation_id')
                    ->join('esbtp_classes', 'esbtp_classes.id', '=', 'esbtp_evaluations.classe_id')
                    ->join('esbtp_inscriptions', function ($join) use ($annee) {
                        $join->on('esbtp_inscriptions.etudiant_id', '=', 'esbtp_notes.etudiant_id')
                            ->on('esbtp_inscriptions.classe_id', '=', 'esbtp_evaluations.classe_id')
                            ->where('esbtp_inscriptions.annee_universitaire_id', $annee->id)
                            ->where('esbtp_inscriptions.status', 'active')
                            ->where('esbtp_inscriptions.workflow_step', 'etudiant_cree')
                            ->whereNull('esbtp_inscriptions.deleted_at');
                    })
                    ->whereIn('esbtp_notes.evaluation_id', $evalIds)
                    ->where(function ($q) {
                        $q->where('esbtp_notes.is_absent', false)
                            ->orWhereNull('esbtp_notes.is_absent');
                    })
                    ->whereNotNull('esbtp_notes.note')
                    ->selectRaw('esbtp_classes.filiere_id, esbtp_evaluations.matiere_id, COUNT(DISTINCT esbtp_notes.etudiant_id) as etudiants_avec_note')
                    ->groupBy('esbtp_classes.filiere_id', 'esbtp_evaluations.matiere_id')
                    ->get()
                    ->keyBy(fn ($row) => $row->filiere_id.'-'.$row->matiere_id);
            }
        }

        $evalsByFiliere = $evaluations->groupBy(fn (ESBTPEvaluation $e) => (int) ($e->classe->filiere_id ?? 0));

        $filieres = $classes->groupBy(fn (ESBTPClasse $c) => (int) ($c->filiere_id ?? 0))
            ->map(function ($classeGroup, $fid) use ($evalsByFiliere, $noteStats, $studentsByFiliereMatiere) {
                $first = $classeGroup->first();
                $effectif = (int) $classeGroup->sum('effectif');
                $filiereEvals = $evalsByFiliere->get((int) $fid, collect());

                $matieres = $filiereEvals
                    ->groupBy(fn (ESBTPEvaluation $e) => (int) $e->matiere_id)
                    ->map(function ($evals, $matiereId) use ($noteStats, $studentsByFiliereMatiere, $fid, $effectif) {
                        $matiere = $evals->first()->matiere;
                        $evalRows = $evals->map(function (ESBTPEvaluation $e) use ($noteStats) {
                            $stats = $noteStats->get($e->id);

                            return [
                                'id' => (int) $e->id,
                                'titre' => $e->titre,
                                'type' => $e->type,
                                'periode' => $e->periode,
                                'date' => optional($e->date_evaluation)->toDateString(),
                                'classe_id' => (int) $e->classe_id,
                                'classe' => $e->classe->name ?? null,
                                'nb_notes' => (int) ($stats->nb_notes ?? 0),
                                'nb_numeric' => (int) ($stats->nb_numeric ?? 0),
                                'nb_absents' => (int) ($stats->nb_absents ?? 0),
                            ];
                        })->values();

                        $avecNote = (int) ($studentsByFiliereMatiere->get($fid.'-'.$matiereId)->etudiants_avec_note ?? 0);

                        return [
                            'matiere_id' => (int) $matiereId,
                            'matiere' => $matiere->name ?? 'Matière inconnue',
                            'code' => $matiere->code ?? null,
                            'evaluations_count' => $evalRows->count(),
                            'etudiants_avec_note' => $avecNote,
                            'effectif' => $effectif,
                            'couverture_pct' => $effectif > 0 ? round(100 * $avecNote / $effectif, 1) : 0,
                            'evaluations' => $evalRows->all(),
                        ];
                    })
                    ->sortBy('matiere')
                    ->values();

                return [
                    'filiere_id' => (int) $fid ?: null,
                    'filiere' => $first->filiere->name ?? 'Sans filière',
                    'effectif' => $effectif,
                    'classes' => $classeGroup->map(fn (ESBTPClasse $c) => [
                        'id' => (int) $c->id,
                        'name' => $c->name,
                        'code' => $c->code,
                        'effectif' => (int) $c->effectif,
                    ])->values()->all(),
                    'evaluations_total' => $filiereEvals->count(),
                    'matieres' => $matieres->all(),
                ];
            })
            ->sortBy('filiere')
            ->values();

        return $this->successResponse([
            'annee' => [
                'id' => $annee->id,
                'name' => $annee->name ?? $annee->libelle,
            ],
            'filters' => [
                'systeme' => $systeme,
                'year' => $year,
                'filiere_id' => $filiereId,
                'periode' => $periode,
            ],
            'summary' => [
                'filieres' => $filieres->count(),
                'classes' => $classes->count(),
                'effectif' => (int) $classes->sum('effectif'),
                'evaluations' => $evaluations->count(),
                'matieres' => $filieres->sum(fn ($f) => count($f['matieres'])),
            ],
            'filieres' => $filieres->all(),
        ]);
    }

    private function shouldFilterPeriode(?string $periode): bool
    {
        if ($periode === null || $periode === '') {
            return false;
        }

        return ! in_array(strtolower($periode), ['annuel', 'annual', 'annee'], true);
    }
}
