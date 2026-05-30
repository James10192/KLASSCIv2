<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\BtsTroncCommun\BtsAnnualAggregationService;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIBtsTroncCommunController extends BaseApiController
{
    public function __construct(
        private BtsPhaseResolver $phaseResolver,
        private BtsAnnualAggregationService $aggregationService
    ) {
        parent::__construct();
    }

    public function diagnoseInscription(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $inscription = ESBTPInscription::with([
            'etudiant',
            'filiere',
            'classe.orientationTargets.targetClasse.filiere',
            'phases.classe.filiere',
            'inscriptionOrigine.classe.filiere',
            'inscriptionSpecialisation.classe.filiere',
        ])->find($id);

        if (! $inscription) {
            return $this->errorResponse('Inscription not found', [], 404);
        }

        $journey = $this->phaseResolver->buildJourney($inscription);
        $warnings = [];
        $errors = [];

        if (($journey['current_phase']['type_phase'] ?? null) === 'tronc_commun' && $inscription->classe?->orientationTargets->isEmpty()) {
            $warnings[] = 'Aucune classe cible configurée pour cette classe tronc commun.';
        }

        return $this->successResponse([
            'status' => empty($errors) ? 'ok' : 'error',
            'source_model' => $journey['source_model'],
            'current_phase' => $journey['current_phase'],
            'timeline' => $journey['timeline'],
            'warnings' => $warnings,
            'errors' => $errors,
            'recommended_actions' => empty($warnings)
                ? []
                : ['Configurer au moins une sortie autorisée pour la classe tronc commun.'],
        ], 'BTS TC inscription diagnostic generated');
    }

    public function studentJourney(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $etudiant = ESBTPEtudiant::find($id);
        if (! $etudiant) {
            return $this->errorResponse('Student not found', [], 404);
        }

        $anneeId = $request->integer('annee_universitaire_id') ?: ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        $context = $this->aggregationService->resolveStudentContext($etudiant, $anneeId, null, 'annuel', true);

        return $this->successResponse([
            'status' => 'ok',
            'source_model' => $context['source_model'] ?? 'phase_based',
            'current_phase' => $context['journey']['current_phase'] ?? null,
            'timeline' => $context['journey']['timeline'] ?? [],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
        ], 'BTS TC student journey generated');
    }

    public function classOrientationCheck(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $classe = ESBTPClasse::with(['orientationTargets.targetClasse.filiere', 'filiere', 'niveau', 'annee'])->find($id);
        if (! $classe) {
            return $this->errorResponse('Class not found', [], 404);
        }

        $warnings = [];
        foreach ($classe->orientationTargets as $target) {
            if ((int) $target->targetClasse?->annee_universitaire_id !== (int) $classe->annee_universitaire_id) {
                $warnings[] = "La cible {$target->targetClasse?->name} ne partage pas la même année.";
            }
            if ((int) $target->targetClasse?->niveau_etude_id !== (int) $classe->niveau_etude_id) {
                $warnings[] = "La cible {$target->targetClasse?->name} ne partage pas le même niveau.";
            }
        }

        return $this->successResponse([
            'status' => empty($warnings) ? 'ok' : 'warning',
            'source_model' => 'phase_based',
            'current_phase' => null,
            'timeline' => [],
            'warnings' => $warnings,
            'errors' => [],
            'recommended_actions' => empty($warnings) ? [] : ['Corriger le mapping des sorties autorisées.'],
        ], 'BTS TC orientation check generated');
    }

    public function resultsConsistency(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $etudiant = ESBTPEtudiant::find($id);
        if (! $etudiant) {
            return $this->errorResponse('Student not found', [], 404);
        }

        $anneeId = $request->integer('annee_universitaire_id');
        $periode = (string) ($request->input('periode') ?: 'annuel');
        $context = $this->aggregationService->resolveStudentContext($etudiant, $anneeId, null, $periode, true);

        return $this->successResponse([
            'status' => 'ok',
            'source_model' => $context['source_model'] ?? 'phase_based',
            'current_phase' => $context['effective_phase'] ?? null,
            'timeline' => $context['journey']['timeline'] ?? [],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
        ], 'BTS TC results consistency generated');
    }

    public function legacyAudit(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $anneeId = $request->integer('annee_universitaire_id');
        $items = ESBTPInscription::with(['inscriptionOrigine', 'classe', 'filiere'])
            ->where('type_changement', 'specialisation')
            ->when($anneeId, fn ($query) => $query->where('annee_universitaire_id', $anneeId))
            ->get()
            ->map(fn (ESBTPInscription $inscription) => [
                'legacy_inscription_id' => $inscription->id,
                'origine_id' => $inscription->inscription_origine_id,
                'etudiant_id' => $inscription->etudiant_id,
                'classe' => $inscription->classe?->name,
                'filiere' => $inscription->filiere?->name,
                'compatible' => $inscription->inscriptionOrigine !== null,
            ])
            ->values();

        return $this->successResponse([
            'status' => 'ok',
            'source_model' => 'legacy_dual_inscription',
            'current_phase' => null,
            'timeline' => [],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
            'items' => $items,
        ], 'BTS TC legacy audit generated');
    }
}
