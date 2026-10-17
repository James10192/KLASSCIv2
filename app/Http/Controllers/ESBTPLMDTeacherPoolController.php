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
            ->with('teachers:id,user_id')
            ->first();

        if (! $planif) {
            return response()->json(['exists' => false, 'ids' => [], 'teachers' => [], 'unavailable_count' => 0]);
        }

        $ids = $planif->poolUserIds();
        $usersById = User::role('enseignant')->whereIn('id', $ids)->get(['id', 'name', 'email'])->keyBy('id');
        $availableIds = $ids->filter(fn ($id) => $usersById->has($id))->values();
        $users = $availableIds->map(fn ($id) => $usersById->get($id))->values();

        return response()->json([
            'exists' => true,
            'planification_id' => $planif->id,
            'ids' => $availableIds,
            'teachers' => $users,
            'unavailable_count' => $ids->count() - $availableIds->count(),
        ]);
    }
}
