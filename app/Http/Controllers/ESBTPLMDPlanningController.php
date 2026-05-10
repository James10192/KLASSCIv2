<?php

namespace App\Http\Controllers;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Page lecture-seule du Planning LMD : pour un parcours / niveau / semestre,
 * affiche la hiérarchie UE -> ECUE avec volumes horaires UEMOA depuis
 * `esbtp_planifications_academiques`. L'édition arrive en PR LMD-2 Phase 2.
 */
class ESBTPLMDPlanningController extends Controller
{
    public function index(Request $request): View
    {
        $parcours = ESBTPLMDParcours::with(['filiere', 'mention.domaine'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $niveaux = ESBTPNiveauEtude::orderBy('year')->orderBy('name')->get();
        $semestres = range(1, 10);

        $filters = [
            'parcours_id' => $request->integer('parcours_id') ?: null,
            'niveau_id' => $request->integer('niveau_id') ?: null,
            'semestre' => $request->integer('semestre') ?: null,
        ];

        $parcoursSelected = $filters['parcours_id']
            ? $parcours->firstWhere('id', $filters['parcours_id'])
            : null;

        $rows = $parcoursSelected
            ? $this->buildPlanningRows($parcoursSelected, $filters)
            : collect();

        $kpis = [
            'ue_count' => $rows->count(),
            'ecue_count' => $rows->sum(fn ($row) => $row['ecues']->count()),
            'cect_total' => $rows->sum('cect'),
        ];

        return view('esbtp.lmd.planning.index', compact(
            'parcours',
            'niveaux',
            'semestres',
            'filters',
            'parcoursSelected',
            'rows',
            'kpis'
        ));
    }

    /**
     * GET /esbtp/lmd/planning/partial — returns JSON {kpis, listing} for AJAX reload.
     */
    public function partial(Request $request)
    {
        $parcours = ESBTPLMDParcours::with(['filiere', 'mention.domaine'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $filters = [
            'parcours_id' => $request->integer('parcours_id') ?: null,
            'niveau_id' => $request->integer('niveau_id') ?: null,
            'semestre' => $request->integer('semestre') ?: null,
        ];

        $parcoursSelected = $filters['parcours_id']
            ? $parcours->firstWhere('id', $filters['parcours_id'])
            : null;

        $rows = $parcoursSelected
            ? $this->buildPlanningRows($parcoursSelected, $filters)
            : collect();

        $kpis = [
            'ue_count' => $rows->count(),
            'ecue_count' => $rows->sum(fn ($row) => $row['ecues']->count()),
            'cect_total' => $rows->sum('cect'),
        ];

        $viewData = compact('parcours', 'parcoursSelected', 'rows', 'kpis', 'filters');
        return response()->json([
            'kpis' => view('esbtp.lmd.planning._kpis', $viewData)->render(),
            'listing' => view('esbtp.lmd.planning._listing', $viewData)->render(),
        ]);
    }

    /**
     * @return Collection<int, array{ue: ESBTPUniteEnseignement, cect: int, ecues: Collection<int, array>}>
     */
    private function buildPlanningRows(ESBTPLMDParcours $parcours, array $filters): Collection
    {
        $ues = $this->loadUesForParcours($parcours, $filters['semestre']);

        if ($ues->isEmpty()) {
            return collect();
        }

        $matiereIds = $ues->flatMap->getEcuesEffectifs()->pluck('id')->unique();
        $planifs = $this->loadPlanifications($matiereIds, $parcours, $filters);

        return $ues->map(function (ESBTPUniteEnseignement $ue) use ($planifs) {
            $ecues = $ue->getEcuesEffectifs()->map(fn ($ecue) => [
                'ecue' => $ecue,
                'planif' => $planifs->get($ecue->id),
            ])->values();

            return [
                'ue' => $ue,
                'cect' => (int) ($ue->credit ?? 0),
                'ecues' => $ecues,
            ];
        })->values();
    }

    private function loadUesForParcours(ESBTPLMDParcours $parcours, ?int $semestre): Collection
    {
        $query = $parcours->unitesEnseignement()
            ->with(['ecues', 'matieres'])
            ->where('esbtp_unites_enseignement.is_active', true);

        if ($semestre) {
            $query->wherePivot('semestre', $semestre);
        }

        return $query->orderBy('esbtp_unites_enseignement.name')->get();
    }

    private function loadPlanifications(Collection $matiereIds, ESBTPLMDParcours $parcours, array $filters): Collection
    {
        if ($matiereIds->isEmpty() || !$parcours->filiere_id) {
            return collect();
        }

        $query = ESBTPPlanificationAcademique::query()
            ->with('enseignantPrincipal:id,name')
            ->where('filiere_id', $parcours->filiere_id)
            ->whereIn('matiere_id', $matiereIds);

        if ($filters['niveau_id']) {
            $query->where('niveau_etude_id', $filters['niveau_id']);
        }
        if ($filters['semestre']) {
            $query->where('semestre', $filters['semestre']);
        }

        return $query->get()->keyBy('matiere_id');
    }
}
