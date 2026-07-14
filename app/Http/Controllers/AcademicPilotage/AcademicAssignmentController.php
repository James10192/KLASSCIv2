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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('create', AcademicActorAssignment::class);
        $filters = $request->validate([
            'year_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
        ]);
        $assignments = AcademicActorAssignment::query()
            ->with([
                'anneeUniversitaire',
                'classe:id,name,code',
                'user:id,name,email',
            ])
            ->where('is_active', true)
            ->when($filters['year_id'] ?? null, fn ($query, $yearId) => $query->where('annee_universitaire_id', $yearId))
            ->when($filters['class_id'] ?? null, fn ($query, $classId) => $query->where('classe_id', $classId))
            ->latest('updated_at')
            ->limit(100)
            ->get();

        return response()->json([
            'ok' => true,
            'assignments' => AcademicAssignmentResource::collection($assignments)->resolve($request),
        ]);
    }

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
