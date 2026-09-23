<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Emails\DiagnosticEmailsInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /api/cli/emails/diagnostic` : joignabilite des adresses de l'instance
 * et remise reelle des convocations. Lecture seule, `cli:read`.
 *
 * Le corps est le contrat partage avec la commande klassci-cli : il est rendu
 * tel quel, sans l'enveloppe `success/data` des autres routes CLI.
 */
class CLIEmailsController extends BaseApiController
{
    public function diagnostic(Request $request, DiagnosticEmailsInstance $diagnostic): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        return response()->json($diagnostic->rapport($request->boolean('details')));
    }
}
