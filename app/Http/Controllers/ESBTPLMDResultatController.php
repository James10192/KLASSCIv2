<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Services\LMD\LmdBulletinProjectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ESBTPLMDResultatController extends Controller
{
    public function __construct(private readonly LmdBulletinProjectionService $lmdResults) {}

    /**
     * Dashboard resultats LMD: vue live par classe.
     */
    public function index(Request $request)
    {
        $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
        $anneeId = $request->annee_universitaire_id
            ?? $annees->firstWhere('is_current', true)?->id;

        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->where('is_active', true)
            ->with(['filiere', 'niveau'])
            ->withCount([
                'inscriptions as total_etudiants' => fn ($q) => $q
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->when($anneeId, fn ($q2, $id) => $q2->where('annee_universitaire_id', $id)),
            ])
            ->orderBy('name')
            ->get();

        $classStats = $classes->mapWithKeys(function (ESBTPClasse $classe) use ($anneeId): array {
            $semestre = (int) ($classe->getSemestresLMD()[0] ?? 1);
            $resultats = $anneeId
                ? $this->lmdResults->calculerProjectionsClasse($classe->id, (int) $anneeId, $semestre)
                : collect();

            return [$classe->id => array_merge(
                $this->statsFromLiveResults($resultats),
                ['semestre' => $semestre]
            )];
        });

        return view('esbtp.lmd.resultats.index', compact('classes', 'annees', 'anneeId', 'classStats'));
    }

    /**
     * Resultats live d'une classe LMD par semestre.
     */
    public function classe(Request $request, ESBTPClasse $classe)
    {
        $semestresAutorises = $classe->getSemestresLMD();
        $semestre = (int) ($request->semestre ?? $semestresAutorises[0] ?? 1);
        if (! in_array($semestre, $semestresAutorises, true)) {
            $semestre = (int) ($semestresAutorises[0] ?? 1);
        }

        $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
        $anneeId = $request->annee_universitaire_id
            ?? $annees->firstWhere('is_current', true)?->id;

        $resultats = $anneeId
            ? $this->lmdResults->calculerProjectionsClasse($classe->id, (int) $anneeId, $semestre)
            : collect();
        $stats = $this->statsFromLiveResults($resultats);
        $canGenerateIncomplete = $request->user()?->can('bulletins.generate_incomplete') ?? false;

        return view('esbtp.lmd.resultats.classe', compact(
            'classe',
            'resultats',
            'stats',
            'semestre',
            'anneeId',
            'annees',
            'semestresAutorises',
            'canGenerateIncomplete'
        ));
    }

    /**
     * Resultats live individuels d'un etudiant LMD.
     */
    public function etudiant(Request $request, ESBTPEtudiant $etudiant)
    {
        $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
        $anneeId = $request->annee_universitaire_id
            ?? $annees->firstWhere('is_current', true)?->id;

        $inscriptions = $etudiant->inscriptions()
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->when($anneeId, fn ($query, $id) => $query->where('annee_universitaire_id', $id))
            ->whereHas('classe', fn ($query) => $query->where('systeme_academique', 'LMD'))
            ->with('classe.niveau', 'classe.filiere')
            ->get();

        $resultats = $inscriptions->flatMap(function ($inscription) use ($etudiant, $anneeId): Collection {
            $classe = $inscription->classe;
            if (! $classe || ! $anneeId) {
                return collect();
            }

            return collect($classe->getSemestresLMD())
                ->map(fn (int $semestre): array => $this->lmdResults->calculerProjectionLive(
                    $etudiant->id,
                    $classe->id,
                    (int) $anneeId,
                    $semestre
                ));
        })->sortBy([
            ['annee_universitaire_id', 'desc'],
            ['semestre', 'asc'],
        ])->values();

        $creditsCumules = $resultats->sum('credits_capitalises');
        $creditsTotauxCumules = $resultats->sum('credits_totaux');

        return view('esbtp.lmd.resultats.etudiant', compact(
            'etudiant',
            'resultats',
            'creditsCumules',
            'creditsTotauxCumules',
            'annees',
            'anneeId'
        ));
    }

    private function statsFromLiveResults(Collection $resultats): array
    {
        $withAverage = $resultats->filter(fn (array $resultat): bool => $resultat['moyenne_generale'] !== null);
        $effectif = $resultats->count();
        $validated = $resultats->filter(fn (array $resultat): bool => ($resultat['credits_totaux'] ?? 0) > 0
            && ($resultat['credits_capitalises'] ?? 0) >= ($resultat['credits_totaux'] ?? 0));

        return [
            'effectif' => $effectif,
            'moyenne_classe' => $withAverage->avg('moyenne_generale'),
            'min' => $withAverage->min('moyenne_generale'),
            'max' => $withAverage->max('moyenne_generale'),
            'taux_validation' => $effectif > 0 ? round($validated->count() / $effectif * 100, 1) : null,
            'total_valides' => $validated->count(),
            'total_complets' => $resultats->where('status', 'complete')->count(),
            'total_incomplets' => $resultats->where('status', 'incomplete')->count(),
            'total_configuration_missing' => $resultats->where('status', 'configuration_missing')->count(),
            'total_bulletins' => $resultats->where('has_bulletin', true)->count(),
        ];
    }
}
