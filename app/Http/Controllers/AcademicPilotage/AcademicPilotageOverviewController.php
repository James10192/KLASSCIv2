<?php

declare(strict_types=1);

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\ApercuDuPilotage;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Le tableau de bord pédagogique : quelles classes n'ont pas toutes leurs
 * notes, qui relancer, qui ne vient plus en cours.
 *
 * La page s'ouvre sans rien calculer ; le constat arrive par `apercu()`, parce
 * qu'il lit la couverture de chaque classe. Le suivi des fiches, les alertes
 * et les affectations ont leur propre page (`AcademicPilotageController`).
 */
class AcademicPilotageOverviewController extends Controller
{
    private const PERIODES = [
        'semestre1' => 'Semestre 1',
        'semestre2' => 'Semestre 2',
        'annuel' => 'Année',
    ];

    public function __construct(
        private readonly AcademicActorScopeService $perimetre,
        private readonly ApercuDuPilotage $apercu,
    ) {}

    public function index(Request $request): View
    {
        $annee = $this->annee($request);
        $autorisees = $annee ? $this->classesAutorisees($request, (int) $annee->id) : collect();

        return view('esbtp.pilotage-academique.apercu', [
            'annees' => ESBTPAnneeUniversitaire::query()->orderByDesc('start_date')->limit(8)->pluck('name', 'id')->all(),
            'annee' => $annee,
            'classes' => $annee ? $this->optionsDeClasses((int) $annee->id, $autorisees) : [],
            'periodes' => $this->optionsDePeriodes($annee?->id, $autorisees, $this->periodeDemandee($request)),
            'filtres' => [
                'annee' => $annee?->id,
                'periode' => $this->periodeDemandee($request) ?? '',
                'systeme' => $this->systeme($request) ?? '',
                'classe' => $request->integer('classe') ?: '',
            ],
        ]);
    }

    public function apercu(Request $request): JsonResponse
    {
        $annee = $this->annee($request);

        if ($annee === null) {
            return response()->json(['html' => view('esbtp.pilotage-academique.partials._apercu-vide', [
                'message' => 'Aucune année universitaire n’est ouverte. Créez-la dans les réglages académiques.',
            ])->render()]);
        }

        $autorisees = $this->classesAutorisees($request, (int) $annee->id);
        $classe = $request->integer('classe') ?: null;
        abort_if($classe !== null && $autorisees !== null && ! $autorisees->contains($classe), 403, 'Cette classe est hors de votre périmètre.');

        $periode = $this->periodeDemandee($request);
        if ($periode === null) {
            ['periode' => $periode, 'donnees' => $donnees] = $this->apercu->choisirLaPeriode((int) $annee->id, $this->systeme($request), $classe, $autorisees);
        } else {
            $donnees = $this->apercu->construire((int) $annee->id, $periode, $this->systeme($request), $classe, $autorisees);
        }

        return response()->json([
            'periode' => $periode,
            'html' => view('esbtp.pilotage-academique.partials._apercu', [
                'd' => $donnees,
                'annee' => $annee,
                'periodeLabel' => self::libelle($periode),
                'classeFiltree' => $classe,
            ])->render(),
        ]);
    }

    private function annee(Request $request): ?ESBTPAnneeUniversitaire
    {
        if ($request->filled('annee')) {
            return ESBTPAnneeUniversitaire::query()->find($request->integer('annee'));
        }

        return ESBTPAnneeUniversitaire::query()->where('is_current', true)->first()
            ?? ESBTPAnneeUniversitaire::query()->orderByDesc('start_date')->first();
    }

    /** Null = périmètre global ; sinon les classes que l'acteur enseigne, corrige ou suit. */
    private function classesAutorisees(Request $request, int $anneeId): ?Collection
    {
        $perimetre = $this->perimetre->dashboardScope($request->user(), $anneeId);

        return $perimetre->global ? null : $perimetre->classIds;
    }

    /**
     * La période demandée, ou null : la page choisit alors elle-même la période
     * la plus récente qui attend des notes (voir ApercuDuPilotage).
     */
    private function periodeDemandee(Request $request): ?string
    {
        $demandee = trim((string) $request->input('periode', ''));
        if ($demandee === '') {
            return null;
        }

        try {
            return app(AcademicPeriodNormalizer::class)->normalize($demandee);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * « Période en cours » d'abord, puis les deux semestres, les autres
     * semestres que portent les évaluations de l'année (un parcours LMD va
     * jusqu'au sixième), et l'année.
     *
     * @return array<string, string>
     */
    private function optionsDePeriodes(?int $anneeId, ?Collection $autorisees, ?string $demandee): array
    {
        // La période d'un lien partagé figure toujours dans la liste, même
        // sans évaluation : sinon le sélecteur afficherait autre chose.
        $semestres = collect(['semestre1', 'semestre2', $demandee])->filter()
            ->merge($anneeId ? $this->apercu->periodesRecentes($anneeId, $autorisees) : [])
            ->reject(fn ($p) => $p === 'annuel')->unique()
            ->sortBy(fn ($p) => (int) substr($p, strlen('semestre')))->values();

        return ['' => 'Période en cours']
            + $semestres->mapWithKeys(fn ($p) => [$p => self::libelle($p)])->all()
            + ['annuel' => self::PERIODES['annuel']];
    }

    private static function libelle(string $periode): string
    {
        return self::PERIODES[$periode] ?? (str_starts_with($periode, 'semestre') ? 'Semestre '.substr($periode, strlen('semestre')) : $periode);
    }

    private function systeme(Request $request): ?string
    {
        $systeme = strtoupper(trim((string) $request->input('systeme', '')));

        return in_array($systeme, ['BTS', 'LMD'], true) ? $systeme : null;
    }

    /** @return array<int, string> */
    private function optionsDeClasses(int $anneeId, ?Collection $autorisees): array
    {
        return ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->whereHas('inscriptions', fn ($q) => $q->where('annee_universitaire_id', $anneeId)->where('status', 'active'))
            ->when($autorisees !== null, fn ($q) => $q->whereIn('id', $autorisees))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
