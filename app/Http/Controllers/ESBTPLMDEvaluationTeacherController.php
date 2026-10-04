<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\User;
use App\Services\LMD\EnseignantDePlanificationLmd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ESBTPLMDEvaluationTeacherController extends Controller
{
    public function __construct(private readonly EnseignantDePlanificationLmd $enseignants)
    {
        $this->middleware('auth');
    }

    public function context(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|string|max:30',
        ]);

        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        if (($classe->systeme_academique ?? '') !== 'LMD') {
            return response()->json(['is_lmd' => false]);
        }

        $semestre = $this->semestre($data['periode']);
        abort_unless($semestre && in_array($semestre, $classe->getSemestresLMD(), true), 422, 'Semestre LMD invalide pour cette classe.');
        $anneeId = (int) ($data['annee_universitaire_id'] ?? ESBTPAnneeUniversitaire::where('is_current', true)->value('id'));
        abort_if($anneeId === 0, 422, 'Aucune année universitaire courante.');

        $canAssign = (bool) $request->user()?->can('lmd.planning.edit');

        return response()->json([
            'is_lmd' => true,
            'annee_universitaire_id' => $anneeId,
            'semestre' => $semestre,
            'elements' => $this->enseignants->elementsDuSemestre($classe, $anneeId, $semestre),
            'can_assign' => $canAssign,
            'teachers' => $canAssign ? $this->teachers() : [],
            'planning_url' => $canAssign ? route('esbtp.lmd.planning.index', [
                'niveau_id' => $classe->niveau_etude_id,
                'semestre' => $semestre,
                'parcours_id' => $classe->parcours_id,
            ]) : null,
        ]);
    }

    public function assign(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('lmd.planning.edit'), 403);
        $data = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|string|max:30',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'enseignant_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        abort_unless(($classe->systeme_academique ?? '') === 'LMD', 422, 'Cette classe n’est pas LMD.');

        $planif = $this->enseignants->assignerPrincipal(
            $classe,
            (int) $data['matiere_id'],
            (int) $data['annee_universitaire_id'],
            $data['periode'],
            (int) $data['enseignant_id'],
            (int) $request->user()->id,
        );

        $semestre = $this->semestre($data['periode']);
        $this->rafraichirSnapshotsBrouillons($classe, (int) $data['annee_universitaire_id'], (int) $semestre, (int) $data['matiere_id']);

        return response()->json([
            'success' => true,
            'planification_id' => $planif->id,
            'enseignant_id' => $planif->enseignant_principal_id,
            'enseignant_name' => $planif->enseignantPrincipal?->name,
            'message' => 'Enseignant affecté dans le planning LMD. Les évaluations futures et les bulletins brouillons utilisent désormais cette affectation.',
        ]);
    }

    public function professeurs(Request $request): View
    {
        $data = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
        ]);
        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        abort_unless(($classe->systeme_academique ?? '') === 'LMD', 422);
        abort_unless(in_array((int) $data['semestre'], $classe->getSemestresLMD(), true), 422);

        $elements = $this->enseignants->elementsDuSemestre($classe, (int) $data['annee_universitaire_id'], (int) $data['semestre']);
        $annee = ESBTPAnneeUniversitaire::findOrFail($data['annee_universitaire_id']);
        $canEdit = (bool) $request->user()?->can('lmd.planning.edit');
        $teachers = $canEdit ? $this->teachers() : collect();

        return view('esbtp.lmd.bulletins.professeurs', compact('classe', 'annee', 'elements', 'teachers', 'canEdit'))
            ->with('semestre', (int) $data['semestre']);
    }

    public function saveProfesseurs(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('lmd.planning.edit'), 403);
        $data = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
            'professeurs' => 'required|array',
            'professeurs.*' => 'nullable|integer|exists:users,id',
        ]);

        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        abort_unless(($classe->systeme_academique ?? '') === 'LMD', 422);
        $validIds = $this->enseignants->elementsDuSemestre($classe, (int) $data['annee_universitaire_id'], (int) $data['semestre'])
            ->pluck('matiere_id')->map(fn ($id) => (int) $id)->all();

        $saved = 0;
        foreach ($data['professeurs'] as $matiereId => $enseignantId) {
            if (! $enseignantId || ! in_array((int) $matiereId, $validIds, true)) {
                continue;
            }
            $this->enseignants->assignerPrincipal($classe, (int) $matiereId, (int) $data['annee_universitaire_id'], (int) $data['semestre'], (int) $enseignantId, (int) $request->user()->id);
            $this->rafraichirSnapshotsBrouillons($classe, (int) $data['annee_universitaire_id'], (int) $data['semestre'], (int) $matiereId);
            $saved++;
        }

        return response()->json(['success' => true, 'saved' => $saved, 'message' => $saved.' affectation(s) mise(s) à jour.']);
    }

    private function rafraichirSnapshotsBrouillons(ESBTPClasse $classe, int $anneeId, int $semestre, int $matiereId): void
    {
        ESBTPLMDResultatECUE::query()->where('matiere_id', $matiereId)
            ->whereHas('bulletin', fn ($q) => $q->where('classe_id', $classe->id)->where('annee_universitaire_id', $anneeId)->where('semestre', $semestre)->where('is_published', false))
            ->with('bulletin')->get()->each->save();
    }

    private function teachers()
    {
        return User::role('enseignant')->select('id', 'name', 'email')->orderBy('name')->get();
    }

    private function semestre(mixed $periode): ?int
    {
        if (is_numeric($periode)) {
            return (int) $periode;
        }

        return is_string($periode) && preg_match('/(\d{1,2})/', $periode, $m) ? (int) $m[1] : null;
    }
}
