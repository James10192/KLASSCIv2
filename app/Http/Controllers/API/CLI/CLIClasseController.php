<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDParcours;
use App\Services\LMD\FiliereMiroirLmd;
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
            // L'un OU l'autre. Une ecole LMD range ses classes sous un PARCOURS ;
            // une ecole BTS sous une filiere. Exiger la filiere rendait cet
            // endpoint inutilisable pour une universite : il fabriquait des
            // classes BTS que le moteur LMD ignore ensuite.
            'classes.*.filiere_id' => 'required_without:classes.*.parcours_id|nullable|integer|exists:esbtp_filieres,id',
            'classes.*.parcours_id' => 'nullable|integer|exists:esbtp_lmd_parcours,id',
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
        $needsCurrentYear = collect($validated['classes'])->contains(
            static fn (array $c): bool => empty($c['annee_universitaire_id'])
        );
        if ($needsCurrentYear && ! $anneeId) {
            return $this->errorResponse(
                'Aucune annee universitaire courante. Creez-en une ou passez annee_universitaire_id.',
                ['code' => 'NO_ACADEMIC_YEAR'],
                422
            );
        }

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

        if ($validated['dry_run'] ?? false) {
            return $this->successResponse(['dry_run' => true, 'plan' => $plan]);
        }

        $crees = 0;
        $misAJour = 0;

        $miroirs = app(FiliereMiroirLmd::class);

        DB::transaction(function () use ($validated, $anneeId, $miroirs, &$crees, &$misAJour) {
            foreach ($validated['classes'] as $c) {
                $code = strtoupper(trim($c['code']));
                $parcours = empty($c['parcours_id'])
                    ? null
                    : ESBTPLMDParcours::find((int) $c['parcours_id']);

                // `esbtp_classes.filiere_id` est NOT NULL et porte une cle
                // etrangere : une classe LMD doit malgre tout s'y ancrer. Le
                // service rend la filiere du parcours si elle existe, et cree
                // sinon un reflet a son nom — c'est la convention du depot
                // (.claude/rules/classe-lmd-filiere-as-mention.md), et la raison
                // pour laquelle on ne derive PAS `parcours->filiere_id`
                // directement : cette colonne est nullable, et trois parcours
                // d'USAT l'avaient nulle.
                $filiereId = $parcours
                    ? $miroirs->pourParcours($parcours)->id
                    : (int) $c['filiere_id'];

                $classe = ESBTPClasse::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => trim($c['name']),
                        'filiere_id' => $filiereId,
                        'parcours_id' => $parcours?->id,
                        'systeme_academique' => $parcours ? 'LMD' : 'BTS',
                        'niveau_etude_id' => (int) $c['niveau_etude_id'],
                        'annee_universitaire_id' => $c['annee_universitaire_id'] ?? $anneeId,
                        'places_totales' => $c['places_totales'] ?? 30,
                        'is_active' => true,
                    ]
                );
                $classe->wasRecentlyCreated ? $crees++ : $misAJour++;
            }
        });

        return $this->successResponse([
            'crees' => $crees,
            'mis_a_jour' => $misAJour,
            'plan' => $plan,
        ], 'Classes importees.');
    }
}
