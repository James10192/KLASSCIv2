<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Exploitation\TracesLentes\AgregatDesTraces;
use App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces;
use App\Domain\Exploitation\TracesLentes\SeuilsDesTraces;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * GET /api/cli/traces/lentes — les actions lentes de l'école, agrégées.
 * Lecture seule. Voir docs/api/CLI_TRACES_LENTES.md.
 */
class CLITracesLentesController extends BaseApiController
{
    public function index(Request $request, AgregatDesTraces $agregat, SeuilsDesTraces $seuils): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $v = $request->validate([
            'jours' => ['nullable', 'integer', 'min:1', 'max:30'],
            'depuis' => ['nullable', 'date'],
            'jusqua' => ['nullable', 'date', 'after_or_equal:depuis'],
            'type' => ['nullable', 'in:'.implode(',', [EnregistreurDeTraces::REQUETE, EnregistreurDeTraces::TRAVAIL, EnregistreurDeTraces::COMMANDE])],
            'nom' => ['nullable', 'string', 'max:191'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $jusqua = isset($v['jusqua']) ? Carbon::parse($v['jusqua'])->endOfDay() : now();
        $depuis = isset($v['depuis']) ? Carbon::parse($v['depuis'])->startOfDay() : $jusqua->copy()->subDays((int) ($v['jours'] ?? 7));

        $resultat = $agregat->calculer($depuis, $jusqua, $v['type'] ?? null, $v['nom'] ?? null, (int) ($v['limite'] ?? 20));

        return $this->successResponse([
            'periode' => ['depuis' => $depuis->toIso8601String(), 'jusqua' => $jusqua->toIso8601String()],
            'seuils' => ['duree_ms' => $seuils->dureeMs(), 'requetes_sql' => $seuils->requetes()],
            'actif' => EnregistreurDeTraces::actif(),
        ] + $resultat, count($resultat['actions']).' action(s) lente(s)');
    }
}
