<?php

namespace App\Http\Controllers;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Page lecture-seule du Planning LMD : pour un parcours / niveau / semestre,
 * affiche la hiérarchie UE -> ECUE avec volumes horaires UEMOA depuis
 * `esbtp_planifications_academiques`. L'édition arrive en PR LMD-2 Phase 2.
 */
class ESBTPLMDPlanningController extends Controller
{
    /** Types `esbtp_niveau_etudes.type` identifiant les niveaux LMD (valeurs canoniques de niveaux-etudes/create). */
    private const LMD_TYPES = ['Licence', 'Master', 'Doctorat'];

    public function index(Request $request): View
    {
        $ctx = $this->buildContext($request);
        $semestres = range(1, 10);

        return view('esbtp.lmd.planning.index', array_merge($ctx, compact('semestres')));
    }

    /**
     * GET /esbtp/lmd/planning/partial — returns JSON {kpis, listing, semestresMap, filters} for AJAX reload.
     */
    public function partial(Request $request)
    {
        $ctx = $this->buildContext($request);

        return response()->json([
            'kpis' => view('esbtp.lmd.planning._kpis', $ctx)->render(),
            'listing' => view('esbtp.lmd.planning._listing', $ctx)->render(),
            'semestresMap' => $ctx['semestresMap'],
            'filters' => $ctx['filters'],
        ]);
    }

    /**
     * Shared resolver for index/partial — loads parcours + niveaux + filters
     * + cascade semestre map + planning rows + kpis. Single source of truth.
     */
    private function buildContext(Request $request): array
    {
        $parcours = ESBTPLMDParcours::with(['filiere', 'mention.domaine'])
            ->where('is_active', true)->orderBy('name')->get();

        $niveaux = ESBTPNiveauEtude::whereIn('type', self::LMD_TYPES)
            ->orderBy('year')->orderBy('name')->get();

        $parcoursId = $request->integer('parcours_id') ?: null;
        $parcoursSelected = $parcoursId ? $parcours->firstWhere('id', $parcoursId) : null;

        $semestresMap = $this->buildSemestresMap($parcoursSelected);
        $allowedSemestres = $semestresMap['all'] ?? [];

        $filters = [
            'parcours_id' => $parcoursId,
            'niveau_id' => $this->validateNiveauId($request->integer('niveau_id'), $niveaux),
            'semestre' => $this->validateSemestre($request->integer('semestre'), $allowedSemestres),
        ];

        $rows = $parcoursSelected ? $this->buildPlanningRows($parcoursSelected, $filters) : collect();

        $kpis = [
            'ue_count' => $rows->count(),
            'ecue_count' => $rows->sum(fn ($row) => $row['ecues']->count()),
            'cect_total' => $rows->sum('cect'),
        ];

        return compact('parcours', 'niveaux', 'parcoursSelected', 'semestresMap', 'filters', 'rows', 'kpis');
    }

    /**
     * Defensively reject a niveau_id from URL/query that is NOT in the LMD set
     * (typically a stale URL after the type-filter shipped). Falls back to null
     * (= "tous niveaux") rather than silently returning empty results.
     */
    private function validateNiveauId(?int $niveauId, $allowedNiveaux): ?int
    {
        if (!$niveauId) {
            return null;
        }
        return $allowedNiveaux->firstWhere('id', $niveauId) ? $niveauId : null;
    }

    /**
     * Reject a semestre that is NOT actually present in the parcours pivot
     * (option E cascade — keep the dropdown semantically consistent with the
     * imported maquette). Null = "all semestres".
     */
    private function validateSemestre(?int $semestre, array $allowedSemestres): ?int
    {
        if (!$semestre) {
            return null;
        }
        return in_array($semestre, $allowedSemestres, true) ? $semestre : null;
    }

    /**
     * Build the cascade map for the Semestre dropdown.
     *
     * Returns shape:
     *   [
     *     'all'      => [1, 2, 3, ...],   // union across all niveaux for this parcours
     *     <niveauId> => [1, 2],            // semestres on UEs whose niveau_id = <niveauId>
     *     ...
     *   ]
     *
     * When parcours is null, returns ['all' => []] (no parcours selected → no
     * semestres should be selectable).
     */
    private function buildSemestresMap(?ESBTPLMDParcours $parcours): array
    {
        if (!$parcours) {
            return ['all' => []];
        }

        $rows = DB::table('esbtp_lmd_parcours_ue as pivot')
            ->join('esbtp_unites_enseignement as ue', 'ue.id', '=', 'pivot.unite_enseignement_id')
            ->where('pivot.parcours_id', $parcours->id)
            ->where('ue.is_active', true)
            ->whereNotNull('pivot.semestre')
            ->select('pivot.semestre', 'ue.niveau_id')
            ->distinct()
            ->get();

        $map = ['all' => []];
        $allSet = [];

        foreach ($rows as $row) {
            $sem = (int) $row->semestre;
            if ($sem <= 0) continue;
            $allSet[$sem] = true;
            if ($row->niveau_id === null) continue;
            $key = (int) $row->niveau_id;
            $map[$key] ??= [];
            if (!in_array($sem, $map[$key], true)) $map[$key][] = $sem;
        }

        $map['all'] = array_keys($allSet);
        foreach ($map as $k => $_) sort($map[$k]);

        return $map;
    }

    /**
     * @return Collection<int, array{ue: ESBTPUniteEnseignement, cect: int, ecues: Collection<int, array>}>
     */
    private function buildPlanningRows(ESBTPLMDParcours $parcours, array $filters): Collection
    {
        $ues = $this->loadUesForParcours($parcours, $filters['semestre'], $filters['niveau_id']);

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

    private function loadUesForParcours(ESBTPLMDParcours $parcours, ?int $semestre, ?int $niveauId = null): Collection
    {
        $query = $parcours->unitesEnseignement()
            ->with(['ecues', 'matieres'])
            ->where('esbtp_unites_enseignement.is_active', true);

        if ($semestre) {
            $query->wherePivot('semestre', $semestre);
        }
        if ($niveauId) {
            $query->where('esbtp_unites_enseignement.niveau_id', $niveauId);
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
