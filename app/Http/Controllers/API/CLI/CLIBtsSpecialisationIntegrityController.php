<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\BtsTroncCommun\BtsSpecialisationIntegrityService;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CLIBtsSpecialisationIntegrityController extends BaseApiController
{
    public function __construct(
        private BtsSpecialisationIntegrityService $integrityService
    ) {
        parent::__construct();
    }

    public function diagnose(Request $request, int $id): JsonResponse
    {
        if (! $this->hasCliAbility($request, 'cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $inscription = ESBTPInscription::find($id);

        if (! $inscription) {
            return $this->errorResponse('Inscription introuvable.', [], 404);
        }

        return $this->successResponse(
            $this->integrityService->diagnose($inscription),
            'BTS specialisation integrity diagnosis generated'
        );
    }

    public function repair(Request $request, int $id): JsonResponse
    {
        if (! $this->hasCliAbility($request, 'cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $inscription = ESBTPInscription::find($id);

        if (! $inscription) {
            return $this->errorResponse('Inscription introuvable.', [], 404);
        }

        try {
            $result = $this->integrityService->repairPrimaryPointer($inscription, (int) $request->user()->id);

            return $this->successResponse($result, 'BTS specialisation primary pointer repaired');
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), [], 422);
        }
    }

    private function hasCliAbility(Request $request, string $ability): bool
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        return $token instanceof PersonalAccessToken
            && $token->can($ability)
            && $user->can('inscriptions.specialisation.manage');
    }
}
