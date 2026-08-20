<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPFiliere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Filieres d'un tenant, en lecture et en creation par lot.
 *
 * Ouvrir une nouvelle ecole imposait jusqu'ici de saisir chaque filiere a la
 * main dans l'interface, donc de disposer d'un compte superAdmin sur le tenant.
 */
class CLIFiliereController extends BaseApiController
{
    /**
     * GET /api/cli/filieres
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $filieres = ESBTPFiliere::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active', 'parent_id', 'is_tronc_commun'])
            ->all();

        return $this->successResponse([
            'total' => count($filieres),
            'filieres' => $filieres,
        ]);
    }

    /**
     * POST /api/cli/filieres — Creation par lot, idempotente.
     *
     * Le code fait foi : rejouer le meme lot ne cree pas de doublon, il met a
     * jour. C'est ce qui permet de corriger un libelle mal saisi en relancant
     * l'import, sans passer par une suppression.
     *
     * Body: { filieres: [{ name, code, description?, is_active? }], dry_run?: bool }
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'filieres' => 'required|array|min:1|max:200',
            'filieres.*.name' => 'required|string|max:255',
            'filieres.*.code' => 'required|string|max:50',
            'filieres.*.description' => 'nullable|string|max:1000',
            'filieres.*.is_active' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
        ]);

        $codes = array_map(
            static fn (array $f): string => strtoupper(trim($f['code'])),
            $validated['filieres']
        );

        if (count($codes) !== count(array_unique($codes))) {
            return $this->errorResponse(
                'Codes en double dans le lot : '.implode(', ', array_diff_assoc($codes, array_unique($codes))),
                [],
                422
            );
        }

        $existants = ESBTPFiliere::whereIn('code', $codes)->pluck('name', 'code');
        $dryRun = (bool) ($validated['dry_run'] ?? false);

        $plan = [];
        foreach ($validated['filieres'] as $f) {
            $code = strtoupper(trim($f['code']));
            $plan[] = [
                'code' => $code,
                'name' => trim($f['name']),
                'action' => $existants->has($code) ? 'mise a jour' : 'creation',
                'nom_actuel' => $existants[$code] ?? null,
            ];
        }

        if ($dryRun) {
            return $this->successResponse([
                'dry_run' => true,
                'plan' => $plan,
            ], 'Aucune ecriture : previsualisation seulement.');
        }

        $crees = 0;
        $misAJour = 0;

        DB::transaction(function () use ($validated, &$crees, &$misAJour) {
            foreach ($validated['filieres'] as $f) {
                $code = strtoupper(trim($f['code']));
                $filiere = ESBTPFiliere::where('code', $code)->first();

                $donnees = [
                    'name' => trim($f['name']),
                    'code' => $code,
                    'description' => $f['description'] ?? null,
                    'is_active' => $f['is_active'] ?? true,
                ];

                if ($filiere) {
                    $filiere->update($donnees);
                    $misAJour++;
                } else {
                    ESBTPFiliere::create($donnees);
                    $crees++;
                }
            }
        });

        Log::info('CLI: filieres importees', [
            'crees' => $crees,
            'mis_a_jour' => $misAJour,
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'crees' => $crees,
            'mis_a_jour' => $misAJour,
            'total' => $crees + $misAJour,
        ], "{$crees} filiere(s) creee(s), {$misAJour} mise(s) a jour.");
    }
}
