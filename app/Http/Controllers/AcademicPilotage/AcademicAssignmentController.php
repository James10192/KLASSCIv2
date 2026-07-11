<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Services\AcademicAssignmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicPilotage\StoreAcademicAssignmentRequest;
use App\Http\Resources\AcademicPilotage\AcademicAssignmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicAssignmentController extends Controller
{
    public function store(
        StoreAcademicAssignmentRequest $request,
        AcademicAssignmentService $service,
    ): JsonResponse {
        $this->authorize('create', AcademicActorAssignment::class);
        $assignment = $service->assign(
            $request->integer('user_id'),
            $request->integer('classe_id'),
            $request->integer('annee_universitaire_id'),
            $request->responsibility(),
            $request->user(),
            $request->validated('metadata', []),
        );

        return response()->json([
            'ok' => true,
            'message' => 'L’affectation académique a été enregistrée.',
            'assignment' => new AcademicAssignmentResource($assignment),
        ], 201);
    }

    public function deactivate(
        AcademicActorAssignment $assignment,
        AcademicAssignmentService $service,
        Request $request,
    ): JsonResponse {
        $this->authorize('delete', $assignment);
        $updated = $service->deactivate($assignment, $request->user());

        return response()->json([
            'ok' => true,
            'message' => 'L’affectation académique a été désactivée.',
            'assignment' => new AcademicAssignmentResource($updated),
        ]);
    }
}
