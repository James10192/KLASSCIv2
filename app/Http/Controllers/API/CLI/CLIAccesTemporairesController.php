<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Permissions\AccesTemporaireRefuse;
use App\Domain\Permissions\AccesTemporaires;
use App\Http\Controllers\API\BaseApiController;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Acces temporaires depuis le CLI : les memes refus que l'ecran
 * /esbtp/acces-temporaires, puisque les deux passent par AccesTemporaires.
 * L'auteur enregistre est le titulaire du jeton.
 */
class CLIAccesTemporairesController extends BaseApiController
{
    public function __construct(private readonly AccesTemporaires $acces)
    {
    }

    /** GET /api/cli/user/{id}/acces-temporaires */
    public function index(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $grants = TemporaryPermissionGrant::where('user_id', $id)->latest('id')->get();

        return $this->successResponse([
            'user_id' => $id,
            'acces' => $grants->map(fn (TemporaryPermissionGrant $g) => $this->serialiser($g))->all(),
        ]);
    }

    /**
     * POST /api/cli/user/{id}/acces-temporaires
     * Corps : permission, fin (date) ou duree_heures, debut (optionnel), motif.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'permission' => 'required|string|max:125',
            'debut' => 'nullable|date',
            'fin' => 'required_without:duree_heures|nullable|date',
            'duree_heures' => 'required_without:fin|nullable|integer|min:1',
            'motif' => 'required|string|min:10|max:1000',
        ]);

        $user = User::find($id);
        if (! $user) {
            return $this->errorResponse("Utilisateur {$id} introuvable.", ['code' => 'USER_NOT_FOUND'], 404);
        }

        $debut = isset($valide['debut']) ? Carbon::parse($valide['debut']) : now();
        $fin = isset($valide['fin'])
            ? Carbon::parse($valide['fin'])
            : $debut->copy()->addHours((int) $valide['duree_heures']);

        try {
            $grant = $this->acces->accorder($user, $valide['permission'], $debut, $fin, $valide['motif'], $request->user());
        } catch (AccesTemporaireRefuse $e) {
            return $this->errorResponse($e->getMessage(), ['code' => 'ACCES_REFUSE'], 422);
        }

        return $this->successResponse($this->serialiser($grant), 'Accès temporaire accordé.');
    }

    /** POST /api/cli/acces-temporaires/{grant}/retirer */
    public function retirer(Request $request, int $grant): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $modele = TemporaryPermissionGrant::find($grant);
        if (! $modele) {
            return $this->errorResponse("Accès {$grant} introuvable.", [], 404);
        }

        return $this->successResponse(
            $this->serialiser($this->acces->retirer($modele, $request->user())),
            'Accès temporaire retiré.'
        );
    }

    private function serialiser(TemporaryPermissionGrant $g): array
    {
        return [
            'id' => $g->id,
            'user_id' => $g->user_id,
            'permission' => $g->permission,
            'debut' => $g->starts_at?->toIso8601String(),
            'fin' => $g->expires_at?->toIso8601String(),
            'statut' => $g->statut(),
            'motif' => $g->motif,
            'accorde_par' => $g->granted_by,
            'retire_le' => $g->revoked_at?->toIso8601String(),
        ];
    }
}
