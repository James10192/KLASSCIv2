<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\BtsTroncCommun\BtsOrientationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BtsTroncCommun\SaveSpecialisationRequest;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Services\TroncCommunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESBTPSpecialisationController extends Controller
{
    public function __construct(
        protected TroncCommunService $troncCommunService,
        protected BtsOrientationService $orientationService
    ) {
        $this->middleware(['auth', 'permission:inscriptions.specialisation.manage']);
    }

    public function show(ESBTPInscription $inscription): View|RedirectResponse
    {
        if (! $this->troncCommunService->isTroncCommunEnabled()) {
            return $this->redirectWithError($inscription, "Le mode tronc commun n'est pas activé.");
        }

        $this->loadOrientationContext($inscription);
        $currentSpecialisation = $this->activeSpecialisation($inscription);
        $sourcePhase = $this->sourcePhase($inscription);

        if (! $sourcePhase && ! $currentSpecialisation) {
            if (! $inscription->filiere?->isTroncCommun() || ! $inscription->classe) {
                return $this->redirectWithError(
                    $inscription,
                    "Cette inscription n'a pas de classe tronc commun exploitable."
                );
            }

            $sourcePhase = $this->orientationService->ensureInitialPhase($inscription);
            $sourcePhase->loadMissing(['classe.orientationTargets.targetClasse.filiere', 'filiere']);
        }

        $sourceClasse = $sourcePhase?->classe ?? $inscription->classe;
        $sourceFiliere = $sourcePhase?->filiere ?? $sourceClasse?->filiere ?? $inscription->filiere;

        if (! $sourceClasse || ! $sourceFiliere?->isTroncCommun()) {
            return $this->redirectWithError(
                $inscription,
                "Le parcours tronc commun d'origine est introuvable. Aucune correction n'a été appliquée."
            );
        }

        $specialisations = $this->targetFilieres($sourceClasse, $sourceFiliere);
        $totalPaye = $inscription->paiements()->where('status', 'validé')->sum('montant');
        $isCorrection = $currentSpecialisation !== null;

        return view('esbtp.inscriptions.specialisation', compact(
            'inscription',
            'specialisations',
            'totalPaye',
            'isCorrection',
            'sourceClasse',
            'sourceFiliere',
            'currentSpecialisation'
        ));
    }

    public function getClasses(ESBTPInscription $inscription, Request $request): JsonResponse
    {
        $this->loadOrientationContext($inscription);
        $sourceClasse = $this->sourcePhase($inscription)?->classe ?? $inscription->classe;
        $filiereId = $request->integer('filiere_id');

        $classes = $this->targetClasses($inscription, $sourceClasse, $filiereId);
        $payload = [
            'message' => null,
            'classes' => $classes->map(fn (ESBTPClasse $classe) => [
                'id' => $classe->id,
                'name' => $classe->name,
                'code' => $classe->code,
                'places_totales' => $classe->places_totales,
                'nombre_etudiants' => $classe->nombre_etudiants,
                'places_disponibles' => $classe->places_disponibles,
            ])->values(),
        ];

        if ($classes->isEmpty() && $filiereId) {
            $payload = array_replace($payload, $this->emptyClassPayload($inscription, $filiereId));
        }

        return response()->json($payload);
    }

    public function store(
        ESBTPInscription $inscription,
        SaveSpecialisationRequest $request
    ): JsonResponse|RedirectResponse {
        try {
            $updated = $this->orientationService->orient($inscription, $request->integer('classe_id'));

            return $this->successResponse($request, $updated, 'Orientation enregistrée avec succès.');
        } catch (\InvalidArgumentException $exception) {
            return $this->validationErrorResponse($request, $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return $this->serverErrorResponse($request);
        }
    }

    public function update(
        ESBTPInscription $inscription,
        SaveSpecialisationRequest $request
    ): JsonResponse|RedirectResponse {
        try {
            $updated = $this->orientationService->correctOrientation(
                $inscription,
                $request->integer('classe_id'),
                trim((string) $request->input('correction_reason'))
            );

            return $this->successResponse($request, $updated, 'Spécialisation corrigée avec succès.');
        } catch (\InvalidArgumentException $exception) {
            return $this->validationErrorResponse($request, $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return $this->serverErrorResponse($request);
        }
    }

    private function loadOrientationContext(ESBTPInscription $inscription): void
    {
        $inscription->load([
            'etudiant',
            'filiere',
            'classe.orientationTargets.targetClasse.filiere',
            'niveau',
            'anneeUniversitaire',
            'phases.filiere',
            'phases.classe.orientationTargets.targetClasse.filiere',
        ]);
    }

    private function sourcePhase(ESBTPInscription $inscription): ?ESBTPInscriptionPhase
    {
        return $inscription->phases
            ->first(fn (ESBTPInscriptionPhase $phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_TRONC_COMMUN);
    }

    private function activeSpecialisation(ESBTPInscription $inscription): ?ESBTPInscriptionPhase
    {
        return $inscription->phases->first(
            fn (ESBTPInscriptionPhase $phase) => $phase->type_phase === ESBTPInscriptionPhase::TYPE_SPECIALISATION
                && $phase->is_active
        );
    }

    private function targetFilieres(ESBTPClasse $sourceClasse, ESBTPFiliere $sourceFiliere)
    {
        $targetFilieres = collect($sourceClasse->orientationTargets ?? [])
            ->where('is_active', true)
            ->pluck('targetClasse.filiere')
            ->filter()
            ->unique('id')
            ->values();

        return $targetFilieres->isNotEmpty()
            ? $targetFilieres
            : $this->troncCommunService->getSpecialisationsDisponibles($sourceFiliere);
    }

    private function targetClasses(
        ESBTPInscription $inscription,
        ?ESBTPClasse $sourceClasse,
        int $filiereId
    ) {
        $classes = collect($sourceClasse?->orientationTargets ?? [])
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->map(fn ($target) => $target->targetClasse)
            ->filter(fn ($classe) => $classe?->is_active
                && (int) $classe->niveau_etude_id === (int) $inscription->niveau_id)
            ->when($filiereId, fn ($items) => $items->where('filiere_id', $filiereId))
            ->values();

        $hasOfficialTargets = collect($sourceClasse?->orientationTargets ?? [])
            ->contains(fn ($target) => $target->is_active);

        if ($classes->isEmpty() && $filiereId && ! $hasOfficialTargets) {
            return ESBTPClasse::query()
                ->where('is_active', true)
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $inscription->niveau_id)
                ->orderBy('name')
                ->get();
        }

        return $classes;
    }

    private function emptyClassPayload(ESBTPInscription $inscription, int $filiereId): array
    {
        $filiere = ESBTPFiliere::find($filiereId);
        $filiereName = $filiere?->name;
        $niveauName = $inscription->niveau?->name;

        return [
            'message' => sprintf(
                "Aucune classe de spécialité %s n'est encore créée au niveau %s.",
                $filiereName ?? '-',
                $niveauName ?? '-'
            ),
            'empty_context' => [
                'filiere_id' => $filiereId,
                'filiere_name' => $filiereName,
                'niveau_id' => $inscription->niveau_id,
                'niveau_name' => $niveauName,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'can_create_classe' => auth()->user()?->can('classes.create') ?? false,
                'create_classe_url' => route('esbtp.classes.create', array_filter([
                    'filiere_id' => $filiereId,
                    'niveau_etude_id' => $inscription->niveau_id,
                    'annee_universitaire_id' => $inscription->annee_universitaire_id,
                ])),
            ],
        ];
    }

    private function successResponse(
        Request $request,
        ESBTPInscription $inscription,
        string $message
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'redirect_to' => route('esbtp.inscriptions.show', $inscription),
                'message' => $message,
            ]);
        }

        return redirect()->route('esbtp.inscriptions.show', $inscription)->with('success', $message);
    }

    private function validationErrorResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['status' => 'error', 'message' => $message], 422)
            : redirect()->back()->withInput()->with('error', $message);
    }

    private function serverErrorResponse(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'Une erreur est survenue lors de la mise à jour de la spécialisation.';

        return $request->expectsJson()
            ? response()->json(['status' => 'error', 'message' => $message], 500)
            : redirect()->back()->withInput()->with('error', $message);
    }

    private function redirectWithError(ESBTPInscription $inscription, string $message): RedirectResponse
    {
        return redirect()->route('esbtp.inscriptions.show', $inscription)->with('error', $message);
    }
}
