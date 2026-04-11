<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CLIAcademicController extends BaseApiController
{
    /**
     * GET /api/cli/annee — Current academic year + list all
     */
    public function annee(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $current = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $all = ESBTPAnneeUniversitaire::orderBy('id', 'desc')->get();

        return $this->successResponse([
            'current' => $current ? [
                'id' => $current->id,
                'name' => $current->name ?? $current->libelle,
                'annee_debut' => $current->annee_debut,
                'annee_fin' => $current->annee_fin,
                'start_date' => $current->start_date?->format('Y-m-d'),
                'end_date' => $current->end_date?->format('Y-m-d'),
                'is_current' => true,
            ] : null,
            'all' => $all->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name ?? $a->libelle,
                'annee_debut' => $a->annee_debut,
                'annee_fin' => $a->annee_fin,
                'start_date' => $a->start_date?->format('Y-m-d'),
                'end_date' => $a->end_date?->format('Y-m-d'),
                'is_current' => (bool) $a->is_current,
                'is_active' => (bool) $a->is_active,
            ]),
        ]);
    }

    /**
     * POST /api/cli/annee/set/{id} — Set current academic year
     */
    public function anneeSet(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $annee = ESBTPAnneeUniversitaire::find($id);
        if (!$annee) {
            return $this->errorResponse("Academic year #{$id} not found", [], 404);
        }

        // Unset all current + set the new one atomically
        DB::transaction(function () use ($annee) {
            ESBTPAnneeUniversitaire::where('is_current', true)->update(['is_current' => false]);
            $annee->update(['is_current' => true]);
        });

        return $this->successResponse([
            'id' => $annee->id,
            'name' => $annee->name ?? $annee->libelle,
            'is_current' => true,
        ], "Academic year '{$annee->name}' is now current");
    }

    /**
     * POST /api/cli/annee/create — Create a new academic year
     */
    public function anneeCreate(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'set_current' => 'nullable|boolean',
        ]);

        try {
            $setCurrent = $validated['set_current'] ?? true;

            $annee = DB::transaction(function () use ($validated, $setCurrent) {
                // Si set_current, retirer le flag des autres
                if ($setCurrent) {
                    ESBTPAnneeUniversitaire::where('is_current', true)->update(['is_current' => false]);
                }

                return ESBTPAnneeUniversitaire::create([
                    'name' => $validated['name'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'is_current' => $setCurrent,
                    'is_active' => true,
                ]);
            });

            return $this->successResponse([
                'id' => $annee->id,
                'name' => $annee->name,
                'start_date' => $annee->start_date->format('Y-m-d'),
                'end_date' => $annee->end_date->format('Y-m-d'),
                'is_current' => (bool) $annee->is_current,
            ], "Academic year '{$annee->name}' created" . ($setCurrent ? ' and set as current' : ''));
        } catch (\Exception $e) {
            Log::error('CLI: annee creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }
}
