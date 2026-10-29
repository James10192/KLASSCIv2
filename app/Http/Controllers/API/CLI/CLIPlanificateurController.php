<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Exploitation\PoulsPlanificateur;
use App\Domain\Support\Models\SupportOutbox;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le planificateur de l'école tourne-t-il ? Et la boîte d'envoi KLASSCI Care
 * se vide-t-elle ? Lecture seule. Voir docs/api/CLI_PLANIFICATEUR.md.
 */
class CLIPlanificateurController extends BaseApiController
{
    public function etat(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $etat = PoulsPlanificateur::etat();
        $enAttente = SupportOutbox::query()->enAttente();

        return $this->successResponse($etat + [
            'boite_envoi_care' => [
                'en_attente' => (clone $enAttente)->count(),
                'plus_ancienne' => (clone $enAttente)->oldest()->first(['created_at'])?->created_at?->toIso8601String(),
            ],
        ], $etat['actif'] ? 'Planificateur actif' : 'Planificateur arrêté ou jamais lancé');
    }
}
