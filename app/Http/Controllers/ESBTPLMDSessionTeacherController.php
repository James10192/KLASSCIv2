<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\LMD\EnseignantDeClasseLmd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ESBTPLMDSessionTeacherController extends Controller
{
    public function __construct(private readonly EnseignantDeClasseLmd $enseignants) {}

    public function context(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classe_id' => 'required|integer|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
        ]);

        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        if (($classe->systeme_academique ?? '') !== 'LMD') {
            return response()->json(['is_lmd' => false]);
        }

        $r = $this->enseignants->resoudre(
            $classe,
            (int) $data['matiere_id'],
            (int) $data['annee_universitaire_id'],
            null,
        );

        $users = collect($r['candidats'])->merge($r['pool'])->filter()->unique('id')->values();
        $teacherIds = ESBTPTeacher::whereIn('user_id', $users->pluck('id'))
            ->pluck('id', 'user_id');

        $serialize = static fn ($u) => [
            'id' => (int) $u->id,
            'teacher_id' => $teacherIds->has($u->id) ? (int) $teacherIds->get($u->id) : null,
            'name' => (string) $u->name,
            'email' => (string) ($u->email ?? ''),
        ];

        $resolvedTeacherId = $r['enseignant_id']
            ? ESBTPTeacher::where('user_id', $r['enseignant_id'])->value('id')
            : null;

        return response()->json([
            'is_lmd' => true,
            'classe' => ['id' => $classe->id, 'name' => $classe->name],
            'semestre' => $r['semestre'],
            'matiere_id' => (int) $data['matiere_id'],
            'enseignant_id' => $r['enseignant_id'],
            'teacher_id' => $resolvedTeacherId ? (int) $resolvedTeacherId : null,
            'enseignant_nom' => $r['enseignant_nom'],
            'source' => $r['source'],
            'confirmation_requise' => $r['confirmation_requise'],
            'conflit' => $r['conflit'],
            'message' => $r['message'],
            'pool' => collect($r['pool'])->map($serialize)->values(),
            'candidats' => collect($r['candidats'])->map($serialize)->values(),
            'noms_externes' => collect($r['noms_externes'])->values(),
            'can_assign' => (bool) $request->user()?->can('lmd.planning.edit'),
            'can_create_teacher' => (bool) $request->user()?->can('teachers.create'),
            'teachers' => $request->user()?->can('lmd.planning.edit')
                ? User::role('enseignant')->select('id', 'name', 'email')->orderBy('name')->get()
                : [],
            'duplicate_search_url' => route('esbtp.enseignants.duplicates'),
            'quick_create_url' => route('esbtp.enseignants.quick-create'),
            'assign_url' => route('esbtp.lmd.evaluation-teacher.assign'),
        ]);
    }
}
