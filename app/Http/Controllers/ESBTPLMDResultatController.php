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
        $request->validate([
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'inscription_id' => ['nullable', 'integer', 'exists:esbtp_inscriptions,id'],
            'classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
            'semestre' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
        $anneeId = $request->annee_universitaire_id
            ?? $annees->firstWhere('is_current', true)?->id;

        $inscriptionsQuery = $etudiant->inscriptions()
            ->when(! $request->filled('inscription_id'), fn ($query) => $query
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree'))
            ->when($anneeId, fn ($query, $id) => $query->where('annee_universitaire_id', $id))
            ->whereHas('classe', fn ($query) => $query->where('systeme_academique', 'LMD'))
            ->with('classe.niveau', 'classe.filiere');

        // Les liens venant d'une fiche portent l'inscription exacte. Un ID
        // provenant de l'URL ne peut sélectionner ni un autre étudiant, ni
        // une autre année, classe ou un parcours BTS.
        $inscriptionSelectionnee = $request->filled('inscription_id')
            ? (int) $request->input('inscription_id')
            : null;
        if ($inscriptionSelectionnee !== null) {
            $inscriptionsQuery->whereKey($inscriptionSelectionnee);
        }
        if ($request->filled('classe_id')) {
            $inscriptionsQuery->where('classe_id', (int) $request->input('classe_id'));
        }
        $inscriptions = $inscriptionsQuery->get();
        if ($inscriptionSelectionnee !== null || $request->filled('classe_id')) {
            abort_if($inscriptions->isEmpty(), 404, 'Inscription LMD introuvable dans ce contexte.');
        }

        $semestresAutorises = $inscriptions
            ->flatMap(fn ($inscription) => $inscription->classe->getSemestresLMD())
            ->map(fn ($semestre) => (int) $semestre)->unique()->sort()->values();
        $semestreSelectionne = $request->filled('semestre')
            ? (int) $request->input('semestre')
            : null;
        abort_if(
            $semestreSelectionne !== null && ! $semestresAutorises->contains($semestreSelectionne),
            422,
            'Ce semestre ne correspond pas à cette inscription LMD.'
        );

        $resultats = $inscriptions->flatMap(function ($inscription) use ($etudiant, $anneeId): Collection {
            $classe = $inscription->classe;
            if (! $classe || ! $anneeId) {
                return collect();
            }

            return collect($classe->getSemestresLMD())
                ->map(function (int $semestre) use ($etudiant, $classe, $anneeId, $inscription): array {
                    return array_merge($this->lmdResults->calculerProjectionLive(
                        $etudiant->id,
                        $classe->id,
                        (int) $anneeId,
                        $semestre
                    ), ['inscription_id' => $inscription->id]);
                });
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
            'anneeId',
            'semestresAutorises',
            'semestreSelectionne',
            'inscriptionSelectionnee'
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
