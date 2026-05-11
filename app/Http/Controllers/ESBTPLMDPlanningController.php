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
    /** Types `esbtp_niveau_etudes.type` identifiant les niveaux LMD (valeurs canoniques de niveaux-etudes/create). */
    private const LMD_TYPES = ['Licence', 'Master', 'Doctorat'];

    public function index(Request $request): View
    {
        $ctx = $this->buildContext($request);

        return view('esbtp.lmd.planning.index', $ctx);
    }

    /**
     * GET /esbtp/lmd/planning/partial — returns JSON {kpis, listing, filters_semestre, filters} for AJAX reload.
     *
     * `filters_semestre` is the rendered HTML of the semestre filter dropdown,
     * already filtered server-side to the semestres available for the current
     * niveau_id (server-side cascade — no Alpine option mutation magic needed).
     */
    public function partial(Request $request)
    {
        $ctx = $this->buildContext($request);

        return response()->json([
            'kpis' => view('esbtp.lmd.planning._kpis', $ctx)->render(),
            'listing' => view('esbtp.lmd.planning._listing', $ctx)->render(),
            'filters_semestre' => view('esbtp.lmd.planning._filter_semestre', $ctx)->render(),
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

        // Charge TOUS les niveaux LMD actifs (Licence/Master/Doctorat) — la liste
        // doit être indépendante du parcours sélectionné, sinon L3 (et autres
        // niveaux dont le parcours n'a pas encore d'UE liée) disparaît du dropdown.
        $niveaux = ESBTPNiveauEtude::whereIn('type', self::LMD_TYPES)
            ->where('is_active', true)
            ->orderBy('type')->orderBy('year')->get();

        $parcoursId = $request->integer('parcours_id') ?: null;
        $parcoursSelected = $parcoursId ? $parcours->firstWhere('id', $parcoursId) : null;

        // Map des semestres VALIDES pour chaque niveau selon le standard UEMOA
        // (year * 2 - 1, year * 2). On NE filtre PAS par "UEs déjà liées" sinon
        // S5/S6 disparaissent quand le parcours TC Droit n'a d'UEs que sur L1/L2.
        $semestresMap = $this->buildSemestresMap($niveaux);
        $niveauId = $this->validateNiveauId($request->integer('niveau_id'), $niveaux);

        // Server-side cascade : the semestres allowed depend on the niveau_id.
        // If a niveau is picked, restrict to its semestres ; else union of all.
        $availableSemestres = $niveauId && isset($semestresMap[$niveauId])
            ? $semestresMap[$niveauId]
            : ($semestresMap['all'] ?? []);

        // Defensive fallback : si la map ne contient rien (cas pathologique),
        // expose la plage canonique 1..6 (couverture L1 à M2 standard UEMOA)
        // pour que le user puisse toujours sélectionner quelque chose.
        if (empty($availableSemestres)) {
            $availableSemestres = range(1, 6);
        }

        $filters = [
            'parcours_id' => $parcoursId,
            'niveau_id' => $niveauId,
            'semestre' => $this->validateSemestre($request->integer('semestre'), $availableSemestres),
        ];

        $rows = $parcoursSelected ? $this->buildPlanningRows($parcoursSelected, $filters) : collect();

        $kpis = [
            'ue_count' => $rows->count(),
            'ecue_count' => $rows->sum(fn ($row) => $row['ecues']->count()),
            'cect_total' => $rows->sum('cect'),
        ];

        return compact('parcours', 'niveaux', 'parcoursSelected', 'semestresMap', 'availableSemestres', 'filters', 'rows', 'kpis');
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
     * Build the cascade map for the Semestre dropdown — basée sur le standard
     * UEMOA (year * 2 - 1, year * 2) et NON sur les UEs déjà liées au parcours.
     *
     * Pourquoi : si on filtre par "UEs liées", S5/S6 disparaissent dès que le
     * parcours TC Droit n'a d'UEs que sur L1/L2 (les L3 spé sont sur parcours
     * séparés Droit Privé/Public). On veut que la directrice puisse SAISIR des
     * UEs sur L3 même si aucune n'existe encore — donc map data-driven UEMOA.
     *
     * Returns shape:
     *   [
     *     'all'      => [1, 2, 3, 4, 5, 6, ...],  // union de tous les niveaux LMD
     *     <niveauId> => [year*2 - 1, year*2],     // UEMOA : L1=[1,2], L2=[3,4], L3=[5,6]
     *                                              // M1=[1,2] (M1 redémarre), M2=[3,4]
     *     ...
     *   ]
     *
     * Note : pour Master/Doctorat la numérotation des semestres redémarre (M1 = S1+S2)
     * conformément à la convention LMD UEMOA — d'où le calcul basé uniquement sur
     * `year` du niveau.
     */
    private function buildSemestresMap(Collection $niveaux): array
    {
        $map = ['all' => []];
        $allSet = [];

        foreach ($niveaux as $niveau) {
            $year = (int) ($niveau->year ?? 0);
            if ($year > 0) {
                $semestres = [$year * 2 - 1, $year * 2];
            } else {
                // Niveau sans year défini → fallback large (rare, défensif).
                $semestres = range(1, 6);
            }

            $map[(int) $niveau->id] = $semestres;
            foreach ($semestres as $sem) {
                $allSet[$sem] = true;
            }
        }

        $map['all'] = array_keys($allSet);
        sort($map['all']);

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
