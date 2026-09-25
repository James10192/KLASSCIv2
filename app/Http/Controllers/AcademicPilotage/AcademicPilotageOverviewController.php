<?php

declare(strict_types=1);

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
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
            'periodes' => self::PERIODES,
            'filtres' => [
                'annee' => $annee?->id,
                'periode' => $this->periode($request, $annee?->id, $autorisees),
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

        $periode = $this->periode($request, (int) $annee->id, $autorisees);
        $donnees = $this->apercu->construire((int) $annee->id, $periode, $this->systeme($request), $classe, $autorisees);

        return response()->json([
            'periode' => $periode,
            'html' => view('esbtp.pilotage-academique.partials._apercu', [
                'd' => $donnees,
                'annee' => $annee,
                'periodeLabel' => self::PERIODES[$periode] ?? $periode,
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
     * La période demandée, sinon celle des dernières évaluations passées :
     * en mai, la page s'ouvre sur le second semestre.
     */
    private function periode(Request $request, ?int $anneeId, ?Collection $autorisees): string
    {
        $demandee = (string) $request->input('periode', '');

        if (isset(self::PERIODES[$demandee])) {
            return $demandee;
        }

        return $anneeId ? $this->apercu->periodeCourante($anneeId, $autorisees) : 'semestre1';
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
