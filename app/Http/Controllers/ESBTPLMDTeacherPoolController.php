<?php

namespace App\Http\Controllers;

use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Lecture légère du pool de professeurs d'une ligne de planning LMD. */
final class ESBTPLMDTeacherPoolController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('lmd.planning.view'), 403);

        $data = $request->validate([
            'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
            'filiere_id' => 'required|integer|exists:esbtp_filieres,id',
            'niveau_id' => 'required|integer|exists:esbtp_niveau_etudes,id',
            'semestre' => 'required|integer|min:1|max:10',
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
        ]);

        $planif = ESBTPPlanificationAcademique::query()
            ->where('matiere_id', $data['matiere_id'])
            ->where('filiere_id', $data['filiere_id'])
            ->where('niveau_etude_id', $data['niveau_id'])
            ->where('semestre', $data['semestre'])
            ->where('annee_universitaire_id', $data['annee_universitaire_id'])
            ->where('is_active', true)
            ->with(['enseignantPrincipal:id,name,email', 'teachers.user:id,name,email'])
            ->first();

        if (! $planif) {
            return response()->json(['exists' => false, 'ids' => [], 'teachers' => []]);
        }

        $ids = collect([$planif->enseignant_principal_id])
            ->merge($planif->enseignants_secondaires ?? [])
            ->merge($planif->teachers->pluck('user_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $users = User::whereIn('id', $ids)->get(['id', 'name', 'email'])->sortBy('name')->values();

        return response()->json([
            'exists' => true,
            'planification_id' => $planif->id,
            'ids' => $ids,
            'teachers' => $users,
        ]);
    }
}
