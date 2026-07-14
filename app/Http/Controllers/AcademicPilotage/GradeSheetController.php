<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\GradeSheetTransitionCommand;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Presenters\GradeSheetAllowedActionsPresenter;
use App\Domain\AcademicPilotage\Services\ExpectedGradeSheetEntrySynchronizer;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentDownloadService;
use App\Domain\AcademicPilotage\Services\GradeSheetProvisioningService;
use App\Domain\AcademicPilotage\Services\GradeSheetWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicPilotage\StoreGradeSheetRequest;
use App\Http\Requests\AcademicPilotage\SyncGradeSheetEntriesRequest;
use App\Http\Requests\AcademicPilotage\TransitionGradeSheetRequest;
use App\Http\Resources\AcademicPilotage\GradeSheetResource;
use App\Models\ESBTPEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeSheetController extends Controller
{
    public function show(
        Request $request,
        GradeSheet $sheet,
        GradeSheetAllowedActionsPresenter $allowedActions,
        GradeSheetDocumentDownloadService $downloads,
    ): JsonResponse {
        $this->authorize('view', $sheet);

        $this->loadDetail($sheet);

        $resource = (new GradeSheetResource($sheet))
            ->withAllowedActions($allowedActions->for($request->user(), $sheet))
            ->withDocumentDownloadUrls($this->documentDownloadUrls($sheet, $downloads));
        $payload = $resource->resolve($request);

        return response()->json([
            'ok' => true,
            'grade_sheet' => $payload,
            'allowed_actions' => $payload['allowed_actions'],
            'progress' => $payload['progress'],
            'entries' => $payload['entries'],
            'documents' => $payload['documents'],
            'events' => $payload['events'],
        ]);
    }

    public function store(
        StoreGradeSheetRequest $request,
        GradeSheetProvisioningService $provisioning,
    ): JsonResponse {
        $evaluation = ESBTPEvaluation::query()->findOrFail($request->integer('evaluation_id'));
        $this->authorize('createForEvaluation', [GradeSheet::class, $evaluation]);

        $result = $provisioning->provision(
            $evaluation,
            $request->entryMode(),
            $request->user(),
            $request->input('expected_at'),
        );

        return response()->json([
            'ok' => true,
            'message' => 'La fiche de notes est prête.',
            'grade_sheet' => new GradeSheetResource($result->gradeSheet),
            'entry_sync' => $result->entrySync?->toArray(),
        ], $result->created ? 201 : 200);
    }

    public function transition(
        TransitionGradeSheetRequest $request,
        GradeSheet $sheet,
        GradeSheetWorkflowService $workflow,
    ): JsonResponse {
        $action = $request->action();
        $this->authorize($action->authorizationAbility(), $sheet);

        $updated = $workflow->transition(new GradeSheetTransitionCommand(
            $sheet->id,
            $action,
            $request->integer('expected_lock_version'),
            $request->user()->id,
            $request->input('reason'),
            $request->validated('metadata', []),
        ));

        return response()->json([
            'ok' => true,
            'message' => 'Le statut de la fiche a été mis à jour.',
            'grade_sheet' => new GradeSheetResource($updated),
        ]);
    }

    public function syncEntries(
        SyncGradeSheetEntriesRequest $request,
        GradeSheet $sheet,
        ExpectedGradeSheetEntrySynchronizer $synchronizer,
    ): JsonResponse {
        $this->authorize('syncEntries', $sheet);
        $result = $synchronizer->sync(
            $sheet,
            $request->integer('expected_lock_version'),
            $request->user(),
        );

        return response()->json([
            'ok' => true,
            'message' => 'Les étudiants attendus ont été synchronisés.',
            'entry_sync' => $result->toArray(),
        ]);
    }

    private function loadDetail(GradeSheet $sheet): void
    {
        $sheet->load([
            'assignedProcessor:id,name,email',
            'classe:id,name,code',
            'controlledBy:id,name,email',
            'documents' => fn ($query) => $query->orderByDesc('uploaded_at'),
            'documents.uploadedBy:id,name,email',
            'enteredBy:id,name,email',
            'entries' => fn ($query) => $query->orderBy('id'),
            'entries.enteredBy:id,name,email',
            'entries.etudiant:id,nom,prenoms,matricule',
            'entries.note:id,evaluation_id,etudiant_id,note,is_absent,observation,commentaire,created_by,updated_by,created_at,updated_at',
            'entries.note.createdBy:id,name,email',
            'entries.note.updatedBy:id,name,email',
            'entries.validatedBy:id,name,email',
            'events' => fn ($query) => $query->orderByDesc('occurred_at')->orderByDesc('id'),
            'events.actor:id,name,email',
            'matiere:id,name,code',
            'receivedBy:id,name,email',
            'submittedBy:id,name,email',
            'teacher:id,user_id',
            'teacher.user:id,first_name,last_name,name',
            'validatedBy:id,name,email',
        ]);
    }

    private function documentDownloadUrls(
        GradeSheet $sheet,
        GradeSheetDocumentDownloadService $downloads,
    ): array {
        return $sheet->documents
            ->mapWithKeys(fn ($document): array => [$document->id => $downloads->signedUrl($document)])
            ->all();
    }
}
