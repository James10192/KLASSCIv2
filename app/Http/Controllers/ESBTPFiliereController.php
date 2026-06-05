<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPMatiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ESBTPFiliereController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filieres = ESBTPFiliere::with(['niveaux', 'matieres', 'parent', 'options'])
            ->orderBy('name')
            ->get();

        return view('esbtp.filieres.index', compact('filieres'));
    }

    /**
     * Affiche le formulaire de création d'une nouvelle filière.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::all();
        $matieres = ESBTPMatiere::where('is_active', true)->orderBy('name')->get();

        return view('esbtp.filieres.create', compact('filieres', 'niveaux', 'matieres'));
    }

    /**
     * Enregistre une nouvelle filière dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate input
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_filieres,code',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'parent_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_ids' => 'nullable|array',
            'niveau_ids.*' => 'exists:esbtp_niveau_etudes,id',
            'matiere_ids' => 'nullable|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
        ]);

        // Create record
        $filiere = new ESBTPFiliere();
        $filiere->name = $request->name;
        $filiere->code = $request->code;
        $filiere->description = $request->description;
        $filiere->is_active = $request->is_active;
        $filiere->parent_id = $request->parent_id;
        $filiere->is_tronc_commun = $request->boolean('is_tronc_commun');
        $filiere->semestres_tronc_commun = $request->input('semestres_tronc_commun', 1);
        $filiere->save();

        // Handle relations
        if ($request->has('niveau_ids')) {
            $filiere->niveaux()->sync($request->niveau_ids);
        }

        if ($request->has('matiere_ids')) {
            $filiere->matieres()->sync(collect($request->matiere_ids)->mapWithKeys(function ($id) {
                return [$id => ['is_active' => true]];
            }));
        }

        return redirect()->route('esbtp.filieres.index')
            ->with('success', 'Filière créée avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $filiere = ESBTPFiliere::with([
            'niveaux',
            'matieres',
            'options',
            'parent',
            'classes' => function ($query) {
                $query->with(['niveauEtude:id,name', 'anneeUniversitaire:id,name'])
                    ->withCount('inscriptions');
            },
        ])->findOrFail($id);

        // Sorties BTS Tronc Commun — uniquement si filière marquée TC (et principale)
        $sourceClasses = collect();
        $candidatesByClasse = [];
        $hasFillesConfigured = false;

        if ($filiere->isTroncCommun()) {
            $sourceClasses = ESBTPClasse::query()
                ->where('filiere_id', $filiere->id)
                ->where('is_active', true)
                ->with([
                    'niveauEtude:id,name',
                    'anneeUniversitaire:id,name',
                    'orientationTargets.targetClasse.filiere:id,name,code',
                    'orientationTargets.targetClasse.niveauEtude:id,name',
                ])
                ->orderBy('annee_universitaire_id', 'desc')
                ->orderBy('name')
                ->get();

            // Pour chaque classe TC, lister les classes candidates issues des
            // filières-filles de ce tronc commun (rattachées via parent_id).
            // Convention métier (Marcel, juin 2026) :
            //   Quand une filière est marquée tronc commun, ses sorties candidates par
            //   défaut sont les classes des filières qui ont parent_id = TC.id.
            // Si aucune fille n'est déclarée, on retombe sur les classes de toute filière
            // non-TC du même niveau (compatibilité écoles qui n'ont pas encore configuré
            // la hiérarchie filière). Le hint UX en aval indique quoi faire dans chaque cas.
            //
            // Les classes KLASSCI sont universelles (cf rule classes-universelles-pas-annee.md) :
            // ne JAMAIS filtrer par annee_universitaire_id sur esbtp_classes.
            $fillesFiliereIds = ESBTPFiliere::query()
                ->where('is_active', true)
                ->where('is_tronc_commun', false)
                ->where('parent_id', $filiere->id)
                ->pluck('id');
            $hasFillesConfigured = $fillesFiliereIds->isNotEmpty();

            foreach ($sourceClasses as $source) {
                $existingTargetIds = $source->orientationTargets->pluck('target_classe_id')->all();

                $query = ESBTPClasse::query()
                    ->where('niveau_etude_id', $source->niveau_etude_id)
                    ->whereNotIn('id', $existingTargetIds)
                    ->where('is_active', true)
                    ->with('filiere:id,name,code,parent_id');

                if ($hasFillesConfigured) {
                    // Filles déclarées → restreindre strictement aux classes de ces filles
                    $query->whereIn('filiere_id', $fillesFiliereIds);
                } else {
                    // Fallback : toute filière non-TC du même niveau
                    $query->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', false));
                }

                $candidatesByClasse[$source->id] = $query
                    ->orderBy('name')
                    ->get(['id', 'name', 'filiere_id', 'niveau_etude_id']);
            }
        }

        return view('esbtp.filieres.show', compact(
            'filiere',
            'sourceClasses',
            'candidatesByClasse',
            'hasFillesConfigured'
        ));
    }

    /**
     * AJAX — Ajoute une classe-sortie à une classe TC source.
     * Endpoint : POST /esbtp/filieres/{filiere}/sorties-tc
     * Permission : bts_tronc_commun.manage_targets
     */
    public function addSortieTC(Request $request, ESBTPFiliere $filiere): JsonResponse
    {
        abort_unless(auth()->user()->can('bts_tronc_commun.manage_targets'), 403);
        abort_unless($filiere->isTroncCommun(), 422, 'Cette filière n\'est pas un tronc commun.');

        $data = $request->validate([
            'source_classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'target_classe_id' => ['required', 'integer', 'exists:esbtp_classes,id', 'different:source_classe_id'],
            'semestre_activation' => ['nullable', 'integer', 'between:1,8'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // La classe source doit appartenir à cette filière TC (sécurité)
        $sourceClasse = ESBTPClasse::findOrFail($data['source_classe_id']);
        abort_unless($sourceClasse->filiere_id === $filiere->id, 422, 'La classe source n\'appartient pas à cette filière.');

        $target = ESBTPClasseOrientationTarget::updateOrCreate(
            [
                'source_classe_id' => $data['source_classe_id'],
                'target_classe_id' => $data['target_classe_id'],
            ],
            [
                'semestre_activation' => $data['semestre_activation'] ?? 2,
                'is_active' => true,
                'sort_order' => ESBTPClasseOrientationTarget::where('source_classe_id', $data['source_classe_id'])->count(),
                'notes' => $data['notes'] ?? null,
            ]
        );

        $target->load('targetClasse.filiere:id,name,code', 'targetClasse.niveauEtude:id,name');

        return response()->json([
            'success' => true,
            'target' => [
                'id' => $target->id,
                'target_classe_id' => $target->target_classe_id,
                'target_name' => $target->targetClasse?->name,
                'target_filiere_name' => $target->targetClasse?->filiere?->name,
                'semestre_activation' => $target->semestre_activation,
                'is_active' => (bool) $target->is_active,
                'notes' => $target->notes,
            ],
        ]);
    }

    /**
     * AJAX — Active/désactive une sortie configurée.
     * Endpoint : PATCH /esbtp/filieres/{filiere}/sorties-tc/{target}/toggle
     */
    public function toggleSortieTC(Request $request, ESBTPFiliere $filiere, ESBTPClasseOrientationTarget $target): JsonResponse
    {
        abort_unless(auth()->user()->can('bts_tronc_commun.manage_targets'), 403);
        abort_unless($filiere->isTroncCommun(), 422);
        abort_unless($target->sourceClasse?->filiere_id === $filiere->id, 404);

        // Si is_active fourni explicitement, on l'applique. Sinon, on bascule.
        $newValue = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $target->is_active;

        $target->update(['is_active' => $newValue]);

        return response()->json([
            'success' => true,
            'is_active' => (bool) $target->is_active,
        ]);
    }

    /**
     * AJAX — Supprime une sortie configurée.
     * Endpoint : DELETE /esbtp/filieres/{filiere}/sorties-tc/{target}
     */
    public function removeSortieTC(Request $request, ESBTPFiliere $filiere, ESBTPClasseOrientationTarget $target): JsonResponse
    {
        abort_unless(auth()->user()->can('bts_tronc_commun.manage_targets'), 403);
        abort_unless($filiere->isTroncCommun(), 422);
        abort_unless($target->sourceClasse?->filiere_id === $filiere->id, 404);

        $target->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Affiche le formulaire de modification d'une filière.
     *
     * @param  \App\Models\ESBTPFiliere  $filiere
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $filiere = ESBTPFiliere::with(['niveaux', 'matieres'])->findOrFail($id);
        $filieres = ESBTPFiliere::where('id', '!=', $id)
            ->where('is_active', true)
            ->get();
        $niveaux = ESBTPNiveauEtude::all();
        $matieres = ESBTPMatiere::where('is_active', true)->orderBy('name')->get();

        return view('esbtp.filieres.edit', compact('filiere', 'filieres', 'niveaux', 'matieres'));
    }

    /**
     * Met à jour la filière spécifiée dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPFiliere  $filiere
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validate input
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'parent_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_ids' => 'nullable|array',
            'niveau_ids.*' => 'exists:esbtp_niveau_etudes,id',
            'matiere_ids' => 'nullable|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
        ]);

        $filiere = ESBTPFiliere::findOrFail($id);

        // Prevent circular parent reference
        if ($request->parent_id && $request->parent_id != $filiere->parent_id) {
            $proposedParent = ESBTPFiliere::find($request->parent_id);
            if ($proposedParent && $proposedParent->isDescendantOf($filiere)) {
                return redirect()->back()
                    ->with('error', 'Impossible : la filière parent sélectionnée est une spécialisation de cette filière.')
                    ->withInput();
            }
        }

        // Update attributes
        $filiere->name = $request->name;
        $filiere->code = $request->code;
        $filiere->description = $request->description;
        $filiere->is_active = $request->is_active;
        $filiere->parent_id = $request->parent_id;
        $filiere->is_tronc_commun = $request->boolean('is_tronc_commun');
        $filiere->semestres_tronc_commun = $request->input('semestres_tronc_commun', 1);
        $filiere->save();

        // Update relations
        if ($request->has('niveau_ids')) {
            $filiere->niveaux()->sync($request->niveau_ids);
        }

        if ($request->has('matiere_ids')) {
            $filiere->matieres()->sync(collect($request->matiere_ids)->mapWithKeys(function ($id) {
                return [$id => ['is_active' => true]];
            }));
        }

        return redirect()->route('esbtp.filieres.index')
            ->with('success', 'Filière mise à jour avec succès.');
    }

    /**
     * Supprime la filière spécifiée de la base de données.
     *
     * @param  \App\Models\ESBTPFiliere  $filiere
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPFiliere $filiere)
    {
        $filiere->delete();
        return redirect()->route('esbtp.filieres.index')
            ->with('success', 'Filière supprimée avec succès.');
    }
}
