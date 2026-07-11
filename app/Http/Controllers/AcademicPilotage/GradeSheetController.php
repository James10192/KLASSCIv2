<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\GradeSheetTransitionCommand;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\ExpectedGradeSheetEntrySynchronizer;
use App\Domain\AcademicPilotage\Services\GradeSheetProvisioningService;
use App\Domain\AcademicPilotage\Services\GradeSheetWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicPilotage\StoreGradeSheetRequest;
use App\Http\Requests\AcademicPilotage\SyncGradeSheetEntriesRequest;
use App\Http\Requests\AcademicPilotage\TransitionGradeSheetRequest;
use App\Http\Resources\AcademicPilotage\GradeSheetResource;
use App\Models\ESBTPEvaluation;
use Illuminate\Http\JsonResponse;

class GradeSheetController extends Controller
{
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
}
