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
                    $this->bulletinService->getSemesterWeights()
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
        $matiere = ESBTPMatiere::firstOrCreate(
            ['code' => "BTSTC-{$inscriptionId}-{$suffix}"],
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

        ESBTPResultatMatiere::updateOrCreate(
            [
                'bulletin_id' => $bulletin->id,
                'matiere_id' => $matiere->id,
            ],
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
