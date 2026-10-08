<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\User;
use App\Services\LMD\EnseignantDeClasseLmd;
use App\Services\LMD\EnseignantDePlanificationLmd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ESBTPLMDEvaluationTeacherController extends Controller
{
    public function __construct(
        private readonly EnseignantDePlanificationLmd $enseignants,
        private readonly EnseignantDeClasseLmd $enseignantsClasse,
    ) {
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
        $elements = $this->elementsAvecResolution($classe, $anneeId, $semestre);

        return response()->json([
            'is_lmd' => true,
            'classe' => ['id' => $classe->id, 'name' => $classe->name],
            'annee_universitaire_id' => $anneeId,
            'semestre' => $semestre,
            'elements' => $elements,
            'can_assign' => $canAssign,
            'can_create_teacher' => (bool) $request->user()?->can('teachers.create'),
            'teachers' => $canAssign ? $this->teachers() : [],
            'duplicate_search_url' => route('esbtp.enseignants.duplicates'),
            'quick_create_url' => route('esbtp.enseignants.quick-create'),
            'planning_url' => $canAssign ? route('esbtp.lmd.planning.index', [
                'niveau_id' => $classe->niveau_etude_id,
                'semestre' => $semestre,
                'parcours_id' => $classe->parcours_id,
            ]) : null,
        ]);
    }

    /** Confirme le vrai professeur d'un ECUE pour UNE classe. */
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

        $resultat = $this->enseignantsClasse->confirmer(
            $classe,
            (int) $data['matiere_id'],
            (int) $data['annee_universitaire_id'],
            $data['periode'],
            (int) $data['enseignant_id'],
            (int) $request->user()->id,
        );

        $semestre = $this->semestre($data['periode']);
        $this->rafraichirSnapshotsBrouillons($classe, (int) $data['annee_universitaire_id'], (int) $semestre, (int) $data['matiere_id']);

        $user = User::find($data['enseignant_id']);
        return response()->json([
            'success' => true,
            'enseignant_id' => $user?->id,
            'enseignant_name' => $user?->name,
            'teacher_profile_id' => $resultat['teacher_profile_id'],
            'evaluations_harmonisees' => $resultat['evaluation_count'],
            'seances_harmonisees' => $resultat['seance_count'],
            'added_to_pool' => $resultat['added_to_pool'],
            'message' => 'Professeur confirmé pour '.$classe->name.'. Les évaluations et séances de cette classe utilisent maintenant la même affectation.',
        ]);
    }

    /**
     * Met à jour le POOL de professeurs de l'ECUE sans choisir le professeur
     * d'une classe particulière. Le premier reste le principal historique ; les
     * suivants sont stockés comme secondaires.
     */
    public function savePool(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('lmd.planning.edit'), 403);
        $data = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|string|max:30',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'enseignant_ids' => 'present|array|max:20',
            'enseignant_ids.*' => 'integer|distinct|exists:users,id',
        ]);

        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        abort_unless(($classe->systeme_academique ?? '') === 'LMD', 422);
        $resolution = $this->enseignantsClasse->resoudre($classe, (int) $data['matiere_id'], (int) $data['annee_universitaire_id'], $data['periode']);
        $planif = $resolution['planification'];
        abort_unless($planif, 422, 'La ligne de planification de cet ECUE doit exister avant de définir son pool de professeurs.');

        $ids = collect($data['enseignant_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $invalides = User::whereIn('id', $ids)->get()->reject(fn (User $u) => $u->hasRole('enseignant'));
        abort_if($invalides->isNotEmpty(), 422, 'Le pool ne peut contenir que des utilisateurs ayant le rôle enseignant.');

        $planif->enseignant_principal_id = $ids->first();
        $planif->enseignants_secondaires = $ids->slice(1)->values()->all() ?: null;
        $planif->updated_by = $request->user()->id;
        $planif->save();

        return response()->json([
            'success' => true,
            'enseignant_ids' => $ids,
            'message' => $ids->count().' professeur(s) disponible(s) dans le pool de cet ECUE.',
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

        $elements = $this->elementsAvecResolution($classe, (int) $data['annee_universitaire_id'], (int) $data['semestre']);
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
            $this->enseignantsClasse->confirmer(
                $classe,
                (int) $matiereId,
                (int) $data['annee_universitaire_id'],
                (int) $data['semestre'],
                (int) $enseignantId,
                (int) $request->user()->id,
            );
            $this->rafraichirSnapshotsBrouillons($classe, (int) $data['annee_universitaire_id'], (int) $data['semestre'], (int) $matiereId);
            $saved++;
        }

        return response()->json(['success' => true, 'saved' => $saved, 'message' => $saved.' professeur(s) confirmé(s) pour cette classe.']);
    }

    private function elementsAvecResolution(ESBTPClasse $classe, int $anneeId, int $semestre): Collection
    {
        return $this->enseignants->elementsDuSemestre($classe, $anneeId, $semestre)
            ->map(function (array $element) use ($classe, $anneeId, $semestre): array {
                $r = $this->enseignantsClasse->resoudre($classe, (int) $element['matiere_id'], $anneeId, $semestre);
                $pool = collect($r['pool'] ?? []);
                $candidats = collect($r['candidats'] ?? []);

                return array_merge($element, [
                    'enseignant_id' => $r['enseignant_id'],
                    'enseignant_nom' => $r['enseignant_nom'],
                    'source_enseignant' => $r['source'],
                    'confirmation_requise' => $r['confirmation_requise'],
                    'conflit' => $r['conflit'],
                    'message' => $r['message'],
                    'pool' => $pool->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values(),
                    'pool_ids' => $pool->pluck('id')->map(fn ($id) => (int) $id)->values(),
                    'candidats' => $candidats->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values(),
                    'noms_externes' => collect($r['noms_externes'] ?? [])->values(),
                    'details_resolution' => $r['details'] ?? [],
                ]);
            });
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
