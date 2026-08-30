<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CLIClasseController extends BaseApiController
{
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'classes' => 'required|array|min:1|max:200',
            'classes.*.name' => 'required|string|max:255',
            'classes.*.code' => 'required|string|max:50',
            'classes.*.filiere_id' => 'required|integer|exists:esbtp_filieres,id',
            'classes.*.niveau_etude_id' => 'required|integer|exists:esbtp_niveau_etudes,id',
            'classes.*.annee_universitaire_id' => 'nullable|integer|exists:esbtp_annee_universitaires,id',
            'classes.*.places_totales' => 'nullable|integer|min:1',
            'dry_run' => 'nullable|boolean',
        ]);

        $codes = array_map(static fn (array $c): string => strtoupper(trim($c['code'])), $validated['classes']);
        if (count($codes) !== count(array_unique($codes))) {
            return $this->errorResponse('Codes en double dans le lot.', [], 422);
        }

        $anneeId = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $existants = ESBTPClasse::whereIn('code', $codes)->pluck('name', 'code');

        $plan = [];
        foreach ($validated['classes'] as $c) {
            $code = strtoupper(trim($c['code']));
            $plan[] = [
                'code' => $code,
                'name' => trim($c['name']),
                'action' => $existants->has($code) ? 'mise a jour' : 'creation',
            ];
        }

        if ($dryRun) {
            return $this->successResponse(['dry_run' => true, 'plan' => $plan]);
        }

        $crees = 0;
        $misAJour = 0;

        DB::transaction(function () use ($validated, $anneeId, &$crees, &$misAJour) {
            foreach ($validated['classes'] as $c) {
                $code = strtoupper(trim($c['code']));
                $donnees = [
                    'name' => trim($c['name']),
                    'code' => $code,
                    'filiere_id' => (int) $c['filiere_id'],
                    'niveau_etude_id' => (int) $c['niveau_etude_id'],
                    'annee_universitaire_id' => $c['annee_universitaire_id'] ?? $anneeId,
                    'places_totales' => $c['places_totales'] ?? 30,
                    'is_active' => true,
                ];
                $classe = ESBTPClasse::where('code', $code)->first();
                if ($classe) {
                    $classe->update($donnees);
                    $misAJour++;
                } else {
                    ESBTPClasse::create($donnees);
                    $crees++;
                }
            }
        });

        return $this->successResponse([
            'crees' => $crees,
            'mis_a_jour' => $misAJour,
            'plan' => $plan,
        ], 'Classes importees.');
    }
}
