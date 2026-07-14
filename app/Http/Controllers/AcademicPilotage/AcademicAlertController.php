<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicAlertEngineService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicAlertController extends Controller
{
    public function transition(
        Request $request,
        AcademicAlert $alert,
        AcademicAlertEngineService $engine,
        AcademicActorScopeService $scope,
    ): JsonResponse {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                AcademicAlertStatus::ACKNOWLEDGED->value,
                AcademicAlertStatus::IN_PROGRESS->value,
                AcademicAlertStatus::RESOLVED->value,
                AcademicAlertStatus::DISMISSED->value,
            ])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $target = AcademicAlertStatus::from($data['status']);
        $permission = match ($target) {
            AcademicAlertStatus::ACKNOWLEDGED, AcademicAlertStatus::IN_PROGRESS => 'academic_alerts.acknowledge',
            AcademicAlertStatus::RESOLVED, AcademicAlertStatus::DISMISSED => 'academic_alerts.resolve',
            default => 'academic_alerts.view',
        };
        abort_unless($request->user()->can($permission), 403);

        $classIds = $scope->dashboardClassIds($request->user(), $alert->annee_universitaire_id);
        abort_if($classIds !== null && ! $classIds->contains($alert->classe_id), 403);

        $updated = $engine->transition($alert, $target, (int) $request->user()->id, $data['reason']);

        return response()->json([
            'ok' => true,
            'message' => 'Le statut de l’alerte a été mis à jour.',
            'alert' => ['id' => $updated->id, 'status' => $updated->status->value, 'status_label' => $updated->status->label()],
        ]);
    }
}
