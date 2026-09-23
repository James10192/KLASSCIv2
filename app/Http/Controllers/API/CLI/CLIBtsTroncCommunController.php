<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\BtsTroncCommun\BtsAnnualAggregationService;
use App\Domain\BtsTroncCommun\BtsOrientationService;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\ESBTPResultatMatiere;
use App\Services\BulletinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CLIBtsTroncCommunController extends BaseApiController
{
    public function __construct(
        private BtsPhaseResolver $phaseResolver,
        private BtsAnnualAggregationService $aggregationService,
        private BtsOrientationService $orientationService,
        private BulletinService $bulletinService
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

        // Les classes KLASSCI sont universelles (cf rule classes-universelles-pas-annee.md) :
        // on ne compare PAS annee_universitaire_id entre classes — seul le niveau est requis.
        $warnings = [];
        foreach ($classe->orientationTargets as $target) {
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

    public function orientationTargetsAudit(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $sourceClasses = ESBTPClasse::query()
            ->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', true)->whereNull('parent_id'))
            ->with([
                'filiere',
                'niveau',
                'annee',
                'orientationTargets.targetClasse.filiere',
                'orientationTargets.targetClasse.niveau',
            ])
            ->where('is_active', true)
            ->orderBy('annee_universitaire_id', 'desc')
            ->orderBy('name')
            ->get();

        $tcFiliereIds = $sourceClasses->pluck('filiere_id')->unique()->filter()->values();
        $fillesByTcFiliere = ESBTPFiliere::query()
            ->where('is_active', true)
            ->whereIn('parent_id', $tcFiliereIds)
            ->get(['id', 'name', 'code', 'parent_id'])
            ->groupBy('parent_id');

        $classes = $sourceClasses
            ->map(fn (ESBTPClasse $source) => $this->mapOrientationAuditClass($source, $fillesByTcFiliere))
            ->values();

        return $this->successResponse([
            'status' => 'ok',
            'source_model' => 'phase_based',
            'totals' => [
                'source_classes' => $classes->count(),
                'with_active_targets' => $classes->where('active_targets_count', '>', 0)->count(),
                'without_active_targets' => $classes->where('active_targets_count', 0)->count(),
            ],
            'classes' => $classes,
            'warnings' => $this->detectOrientationTargetDrifts($classes),
            'errors' => [],
            'recommended_actions' => [],
        ], 'BTS TC orientation targets audit generated');
    }

    private function mapOrientationAuditClass(ESBTPClasse $source, $fillesByTcFiliere): array
    {
        $activeTargets = $source->orientationTargets->where('is_active', true)->values();
        $filles = ($fillesByTcFiliere->get($source->filiere_id) ?? collect())->values();
        $candidates = $this->candidateTargetsForSource($source, $filles, $activeTargets->pluck('target_classe_id')->all());

        return [
            'id' => $source->id,
            'name' => $source->name,
            'code' => $source->code,
            'filiere_id' => $source->filiere_id,
            'filiere' => $source->filiere?->only(['id', 'name', 'code', 'parent_id', 'is_tronc_commun']),
            'niveau_etude_id' => $source->niveau_etude_id,
            'niveau' => $source->niveau?->name,
            'annee_universitaire_id' => $source->annee_universitaire_id,
            'annee' => $source->annee?->name,
            'active_targets_count' => $activeTargets->count(),
            'active_target_labels' => $this->targetLabelsForSource($activeTargets),
            'child_filieres_count' => $filles->count(),
            'child_filieres' => $filles->map->only(['id', 'name', 'code', 'parent_id'])->values(),
            'available_candidates_count' => $candidates->count(),
            'available_candidates' => $candidates,
        ];
    }

    private function candidateTargetsForSource(ESBTPClasse $source, $filles, array $existingTargetIds)
    {
        $query = ESBTPClasse::query()
            ->where('niveau_etude_id', $source->niveau_etude_id)
            ->whereNotIn('id', $existingTargetIds)
            ->where('is_active', true)
            ->with('filiere:id,name,code,parent_id,is_tronc_commun');

        if ($filles->isNotEmpty()) {
            $query->whereIn('filiere_id', $filles->pluck('id'));
        } else {
            $query->whereHas('filiere', fn ($q) => $q
                ->where('is_tronc_commun', false)
                ->orWhereNotNull('parent_id'));
        }

        return $query->orderBy('name')->get(['id', 'name', 'code', 'filiere_id'])
            ->map(fn (ESBTPClasse $classe) => [
                'id' => $classe->id,
                'name' => $classe->name,
                'code' => $classe->code,
                'filiere_id' => $classe->filiere_id,
                'filiere_name' => $classe->filiere?->name,
            ])
            ->values();
    }

    private function targetLabelsForSource($activeTargets)
    {
        return $activeTargets
            ->map(fn (ESBTPClasseOrientationTarget $target) => [
                'target_id' => $target->id,
                'target_classe_id' => $target->target_classe_id,
                'target_classe' => $target->targetClasse?->name,
                'target_filiere_id' => $target->targetClasse?->filiere_id,
                'target_filiere' => $target->targetClasse?->filiere?->name,
                'semestre_activation' => $target->semestre_activation,
                'sort_order' => $target->sort_order,
            ])
            ->values();
    }

    private function detectOrientationTargetDrifts($classes): array
    {
        return $classes
            ->groupBy(fn ($item) => $item['filiere_id'].'|'.$item['niveau_etude_id'])
            ->flatMap(function ($group) {
                $targetSignatures = $group->mapWithKeys(fn ($item) => [
                    $item['name'] => collect($item['active_target_labels'])->pluck('target_classe_id')->sort()->values()->join(','),
                ]);

                return $targetSignatures->unique()->count() <= 1
                    ? []
                    : [[
                        'type' => 'inconsistent_targets_for_same_filiere_level',
                        'message' => 'Des classes tronc commun de même filière et même niveau n’ont pas les mêmes sorties.',
                        'classes' => $targetSignatures,
                    ]];
            })
            ->values()
            ->all();
    }

    public function markFiliereTroncCommun(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'is_tronc_commun' => 'sometimes|boolean',
            'semestres_tronc_commun' => 'sometimes|integer|min:1|max:6',
        ]);

        $filiere = ESBTPFiliere::find($id);
        if (! $filiere) {
            return $this->errorResponse('Filiere not found', [], 404);
        }

        $filiere->update([
            'is_tronc_commun' => $validated['is_tronc_commun'] ?? true,
            'semestres_tronc_commun' => $validated['semestres_tronc_commun'] ?? ($filiere->semestres_tronc_commun ?: 1),
        ]);

        return $this->successResponse([
            'filiere' => [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'is_tronc_commun' => (bool) $filiere->is_tronc_commun,
                'semestres_tronc_commun' => (int) $filiere->semestres_tronc_commun,
            ],
        ], 'BTS TC filiere updated');
    }

    public function addOrientationTarget(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'target_classe_id' => 'required|integer|exists:esbtp_classes,id',
            'semestre_activation' => 'sometimes|integer|min:1|max:6',
            'sort_order' => 'sometimes|integer|min:0|max:65535',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $sourceClasse = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->find($id);
        $targetClasse = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->find((int) $validated['target_classe_id']);

        if (! $sourceClasse || ! $targetClasse) {
            return $this->errorResponse('Source or target class not found', [], 404);
        }

        // Les classes KLASSCI sont universelles (cf rule classes-universelles-pas-annee.md) :
        // on ne contraint PAS la même année universitaire — seul le niveau d'études est requis.
        if ((int) $sourceClasse->niveau_etude_id !== (int) $targetClasse->niveau_etude_id) {
            return $this->errorResponse('Target class must share the same study level', [], 422);
        }

        $target = ESBTPClasseOrientationTarget::updateOrCreate(
            [
                'source_classe_id' => $sourceClasse->id,
                'target_classe_id' => $targetClasse->id,
            ],
            [
                'semestre_activation' => $validated['semestre_activation'] ?? 2,
                'sort_order' => $validated['sort_order'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return $this->successResponse([
            'target' => [
                'id' => $target->id,
                'source_classe_id' => $target->source_classe_id,
                'target_classe_id' => $target->target_classe_id,
                'semestre_activation' => $target->semestre_activation,
                'is_active' => (bool) $target->is_active,
            ],
        ], 'BTS TC orientation target created/updated');
    }

    public function orientInscription(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'target_classe_id' => 'required|integer|exists:esbtp_classes,id',
        ]);

        $inscription = ESBTPInscription::with([
            'filiere',
            'classe.orientationTargets.targetClasse.filiere',
            'phases.classe.filiere',
        ])->find($id);

        if (! $inscription) {
            return $this->errorResponse('Inscription not found', [], 404);
        }

        $inscription = DB::transaction(fn () => $this->orientationService->orient($inscription, (int) $validated['target_classe_id']));
        $journey = $this->phaseResolver->buildJourney($inscription);

        return $this->successResponse([
            'status' => 'ok',
            'source_model' => $journey['source_model'],
            'current_phase' => $journey['current_phase'],
            'timeline' => $journey['timeline'],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
        ], 'BTS TC orientation completed');
    }

    public function syncInscription(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $inscription = ESBTPInscription::with([
            'etudiant',
            'filiere',
            'classe.filiere',
            'phases.classe.filiere',
            'inscriptionOrigine.classe.filiere',
            'inscriptionSpecialisation.classe.filiere',
        ])->find($id);

        if (! $inscription) {
            return $this->errorResponse('Inscription not found', [], 404);
        }

        $result = $this->orientationService->syncSingleInscription($inscription);

        return $this->successResponse($result, 'BTS TC inscription sync executed');
    }

    public function syncAll(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'annee_universitaire_id' => 'sometimes|nullable|integer|exists:esbtp_annee_universitaires,id',
        ]);

        $stats = $this->orientationService->bulkSyncAll($validated['annee_universitaire_id'] ?? null);

        return $this->successResponse($stats, 'BTS TC bulk sync executed');
    }

    public function seedAcademicSample(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'semestre1_note' => 'sometimes|numeric|min:0|max:20',
            'semestre2_note' => 'sometimes|numeric|min:0|max:20',
        ]);

        $inscription = ESBTPInscription::with([
            'etudiant',
            'filiere',
            'niveau',
            'classe',
            'phases.classe.filiere',
        ])->find($id);

        if (! $inscription) {
            return $this->errorResponse('Inscription not found', [], 404);
        }

        $journey = $this->phaseResolver->buildJourney($inscription);
        $semestre1Phase = $this->phaseResolver->resolveSemesterPhase($inscription, 1);
        $semestre2Phase = $this->phaseResolver->resolveSemesterPhase($inscription, 2);

        if (! $semestre1Phase || ! $semestre2Phase) {
            return $this->errorResponse('Both semestre1 and semestre2 phases are required', [], 422);
        }

        $etudiant = $inscription->etudiant;
        if (! $etudiant) {
            return $this->errorResponse('Student not found', [], 404);
        }

        $payload = DB::transaction(function () use ($request, $inscription, $etudiant, $semestre1Phase, $semestre2Phase, $validated) {
            $userId = (int) ($request->user()->id ?? 1);
            $anneeId = (int) $inscription->annee_universitaire_id;
            $semestre1Classe = ESBTPClasse::findOrFail((int) $semestre1Phase['classe_id']);
            $semestre2Classe = ESBTPClasse::findOrFail((int) $semestre2Phase['classe_id']);

            $matiereS1 = $this->upsertSampleMatiere(
                inscriptionId: $inscription->id,
                suffix: 'S1',
                name: 'Culture générale TC',
                classe: $semestre1Classe,
                userId: $userId
            );
            $matiereS2 = $this->upsertSampleMatiere(
                inscriptionId: $inscription->id,
                suffix: 'S2',
                name: 'Pratique professionnelle',
                classe: $semestre2Classe,
                userId: $userId
            );

            $this->upsertCoefficient($matiereS1, $semestre1Classe, $anneeId, $userId);
            $this->upsertCoefficient($matiereS2, $semestre2Classe, $anneeId, $userId);

            $noteS1 = round((float) ($validated['semestre1_note'] ?? 12), 2);
            $noteS2 = round((float) ($validated['semestre2_note'] ?? 16), 2);

            $evaluationS1 = $this->upsertEvaluation($inscription->id, 'S1', $matiereS1, $semestre1Classe, $anneeId, $userId);
            $evaluationS2 = $this->upsertEvaluation($inscription->id, 'S2', $matiereS2, $semestre2Classe, $anneeId, $userId);

            $this->upsertNote($evaluationS1, $etudiant->id, $semestre1Classe->id, $matiereS1->id, 1, $noteS1, $userId);
            $this->upsertNote($evaluationS2, $etudiant->id, $semestre2Classe->id, $matiereS2->id, 2, $noteS2, $userId);

            $resultatS1 = $this->upsertResultat($etudiant->id, $semestre1Classe->id, $matiereS1->id, $anneeId, 'semestre1', $noteS1, $userId);
            $resultatS2 = $this->upsertResultat($etudiant->id, $semestre2Classe->id, $matiereS2->id, $anneeId, 'semestre2', $noteS2, $userId);

            $bulletinS1 = $this->upsertBulletin(
                etudiantId: $etudiant->id,
                classe: $semestre1Classe,
                anneeId: $anneeId,
                periode: 'semestre1',
                matiere: $matiereS1,
                moyenne: $noteS1,
                coefficient: (float) $resultatS1->coefficient,
                userId: $userId
            );
            $bulletinS2 = $this->upsertBulletin(
                etudiantId: $etudiant->id,
                classe: $semestre2Classe,
                anneeId: $anneeId,
                periode: 'semestre2',
                matiere: $matiereS2,
                moyenne: $noteS2,
                coefficient: (float) $resultatS2->coefficient,
                userId: $userId
            );

            $annualAverage = round(
                (float) $this->bulletinService->calculateAnnualAverage(
                    $noteS1 + $this->resolveAttendanceNote($etudiant->id, $semestre1Classe->id, $anneeId, 'semestre1'),
                    $noteS2 + $this->resolveAttendanceNote($etudiant->id, $semestre2Classe->id, $anneeId, 'semestre2'),
                    $this->bulletinService->getSemesterWeights($semestre2Classe)
                ),
                2
            );

            return [
                'student_id' => $etudiant->id,
                'inscription_id' => $inscription->id,
                'source_model' => $journey['source_model'] ?? 'phase_based',
                'current_phase' => $journey['current_phase'] ?? null,
                'timeline' => $journey['timeline'] ?? [],
                'seeded' => [
                    'matieres' => [
                        ['id' => $matiereS1->id, 'name' => $matiereS1->name, 'periode' => 'semestre1', 'classe_id' => $semestre1Classe->id],
                        ['id' => $matiereS2->id, 'name' => $matiereS2->name, 'periode' => 'semestre2', 'classe_id' => $semestre2Classe->id],
                    ],
                    'evaluations' => [
                        ['id' => $evaluationS1->id, 'periode' => 'semestre1', 'classe_id' => $semestre1Classe->id],
                        ['id' => $evaluationS2->id, 'periode' => 'semestre2', 'classe_id' => $semestre2Classe->id],
                    ],
                    'bulletins' => [
                        ['id' => $bulletinS1->id, 'periode' => 'semestre1', 'classe_id' => $semestre1Classe->id, 'moyenne_generale' => $bulletinS1->moyenne_generale],
                        ['id' => $bulletinS2->id, 'periode' => 'semestre2', 'classe_id' => $semestre2Classe->id, 'moyenne_generale' => $bulletinS2->moyenne_generale],
                    ],
                    'annual_expected_effective' => $annualAverage,
                ],
            ];
        });

        return $this->successResponse($payload, 'BTS TC academic sample seeded');
    }

    private function upsertSampleMatiere(int $inscriptionId, string $suffix, string $name, ESBTPClasse $classe, int $userId): ESBTPMatiere
    {
        $code = "BTSTC-{$inscriptionId}-{$suffix}";
        // Un echantillon supprime puis re-seme garderait son code dans l'index
        // unique : `firstOrCreate` ne le voit pas et l'insertion leverait.
        app(\App\Services\LMD\CodeDeMatiere::class)->libererSiArchive($code);
        $matiere = ESBTPMatiere::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'coefficient' => 1,
                'niveau_etude_id' => $classe->niveau_etude_id,
                'type_formation' => 'generale',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        $matiere->filieres()->syncWithoutDetaching([$classe->filiere_id => ['is_active' => true]]);
        $matiere->niveaux()->syncWithoutDetaching([$classe->niveau_etude_id => ['coefficient' => 1, 'heures_cours' => 20, 'is_active' => true]]);
        $matiere->classes()->syncWithoutDetaching([$classe->id => ['coefficient' => 1, 'total_heures' => 20, 'is_active' => true]]);

        return $matiere->fresh();
    }

    private function upsertCoefficient(ESBTPMatiere $matiere, ESBTPClasse $classe, int $anneeId, int $userId): void
    {
        ESBTPMatiereCoefficient::updateOrCreate(
            [
                'matiere_id' => $matiere->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
                'annee_universitaire_id' => $anneeId,
            ],
            [
                'coefficient' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertEvaluation(
        int $inscriptionId,
        string $suffix,
        ESBTPMatiere $matiere,
        ESBTPClasse $classe,
        int $anneeId,
        int $userId
    ): ESBTPEvaluation {
        return ESBTPEvaluation::updateOrCreate(
            [
                'classe_id' => $classe->id,
                'matiere_id' => $matiere->id,
                'titre' => "Seed BTS TC {$inscriptionId} {$suffix}",
            ],
            [
                'description' => 'Jeu de diagnostic BTS TC',
                'type' => ESBTPEvaluation::TYPE_DEVOIR,
                'date_evaluation' => now(),
                'coefficient' => 1,
                'bareme' => 20,
                'duree_minutes' => 60,
                'periode' => $suffix === 'S1' ? 'semestre1' : 'semestre2',
                'annee_universitaire_id' => $anneeId,
                'status' => ESBTPEvaluation::STATUS_COMPLETED,
                'is_published' => true,
                'notes_published' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'enseignant_id' => $userId,
            ]
        );
    }

    private function upsertNote(
        ESBTPEvaluation $evaluation,
        int $etudiantId,
        int $classeId,
        int $matiereId,
        int $semestre,
        float $note,
        int $userId
    ): void {
        ESBTPNote::updateOrCreate(
            [
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $etudiantId,
            ],
            [
                'matiere_id' => $matiereId,
                'classe_id' => $classeId,
                'semestre' => $semestre,
                'note' => $note,
                'valeur' => $note,
                'type_evaluation' => $evaluation->type,
                'is_absent' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertResultat(
        int $etudiantId,
        int $classeId,
        int $matiereId,
        int $anneeId,
        string $periode,
        float $moyenne,
        int $userId
    ): ESBTPResultat {
        return ESBTPResultat::updateOrCreate(
            [
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'matiere_id' => $matiereId,
                'periode' => $periode,
                'annee_universitaire_id' => $anneeId,
            ],
            [
                'moyenne' => $moyenne,
                'coefficient' => 1,
                'rang' => 1,
                'appreciation' => $this->bulletinService->getAppreciation($moyenne),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertBulletin(
        int $etudiantId,
        ESBTPClasse $classe,
        int $anneeId,
        string $periode,
        ESBTPMatiere $matiere,
        float $moyenne,
        float $coefficient,
        int $userId
    ): ESBTPBulletin {
        $attendance = $this->resolveAttendanceNote($etudiantId, $classe->id, $anneeId, $periode);

        $bulletin = ESBTPBulletin::firstOrNew([
            'etudiant_id' => $etudiantId,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $anneeId,
            'periode' => $periode,
        ]);

        $bulletin->moyenne_generale = $moyenne;
        $bulletin->rang = 1;
        $bulletin->effectif_classe = 1;
        $bulletin->mention = $this->bulletinService->getAppreciation($moyenne);
        $bulletin->appreciation_generale = 'Dossier seed BTS TC';
        $bulletin->decision_conseil = $moyenne >= 10 ? 'Admis' : 'Ajourné';
        $bulletin->config_matieres = ['generales' => [$matiere->id], 'techniques' => []];
        $bulletin->professeurs = json_encode([$matiere->id => 'Professeur démo'], JSON_UNESCAPED_UNICODE);
        $bulletin->is_published = true;
        $bulletin->absences_justifiees = 0;
        $bulletin->absences_non_justifiees = 0;
        $bulletin->total_absences = 0;
        $bulletin->note_assiduite = $attendance;
        $bulletin->details_absences = ['justifiees' => 0, 'non_justifiees' => 0];
        $bulletin->user_id = $userId;
        $bulletin->save();

        ESBTPResultatMatiere::poserSurLeBulletin(
            (int) $bulletin->id,
            (int) $matiere->id,
            [
                'moyenne' => $moyenne,
                'coefficient' => $coefficient,
                'rang' => 1,
                'appreciation' => $this->bulletinService->getAppreciation($moyenne),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        return $bulletin->fresh();
    }

    private function resolveAttendanceNote(int $etudiantId, int $classeId, int $anneeId, string $periode): float
    {
        return round(
            $this->bulletinService->calculateEffectiveAttendanceNoteForStudent(
                $etudiantId,
                $classeId,
                $anneeId,
                $periode
            ),
            2
        );
    }
}
