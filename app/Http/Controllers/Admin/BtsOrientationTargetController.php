<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPFiliere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UI admin pour configurer les sorties Tronc Commun → Spécialités.
 *
 * Permission requise : `bts_tronc_commun.manage_targets`.
 *
 * Workflow :
 *  1. Lister les classes TC actives (filiere->is_tronc_commun = true)
 *  2. Pour chaque classe TC, afficher ses targets configurés
 *  3. CRUD : ajout (POST), update (PATCH sort_order/semestre/is_active), suppression (DELETE)
 *  4. Suggérer les classes de spécialité candidates (filieres enfants du même niveau)
 *
 * Sans config, le bouton "Orienter" sur fiche inscription affiche empty state.
 */
class BtsOrientationTargetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:bts_tronc_commun.manage_targets');
    }

    public function index(Request $request): View
    {
        // Bug 8b (juin 2026) : ne lister que les classes dont la filière est
        // un VRAI tronc commun (is_tronc_commun=true ET parent_id IS NULL).
        // Cohérent avec ESBTPFiliere::isTroncCommun() qui exige parent_id NULL
        // pour se protéger contre les données corrompues où une filière OPTION
        // serait marquée is_tronc_commun=true (ex: yakro filière id=8 GBAT).
        $sourceClasses = ESBTPClasse::query()
            ->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', true)->whereNull('parent_id'))
            ->with([
                'filiere',
                'niveauEtude',
                'anneeUniversitaire',
                'orientationTargets.targetClasse.filiere',
                'orientationTargets.targetClasse.niveauEtude',
            ])
            ->where('is_active', true)
            ->orderBy('annee_universitaire_id', 'desc')
            ->orderBy('name')
            ->get();

        // Classes candidates pour ajout. Convention métier (Marcel, juin 2026) :
        //   Quand une filière TC a des filles déclarées (parent_id = TC.id), restreindre
        //   les candidates aux classes de ces filles. Sinon, fallback non-TC même niveau.
        // Les classes KLASSCI sont universelles (cf rule classes-universelles-pas-annee.md) :
        // ne JAMAIS filtrer par annee_universitaire_id sur esbtp_classes.
        //
        // On précompute par TC source filiere_id la liste des filles pour éviter N queries.
        $tcFiliereIds = $sourceClasses->pluck('filiere_id')->unique()->filter()->values();
        $fillesByTcFiliere = ESBTPFiliere::query()
            ->where('is_active', true)
            ->where('is_tronc_commun', false)
            ->whereIn('parent_id', $tcFiliereIds)
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id')
            ->map(fn ($group) => $group->pluck('id'));

        $candidatesByClasse = [];
        $hasFillesByClasse = [];
        foreach ($sourceClasses as $source) {
            $existingTargetIds = $source->orientationTargets->pluck('target_classe_id')->all();
            $fillesIds = $fillesByTcFiliere->get($source->filiere_id) ?? collect();
            $hasFillesByClasse[$source->id] = $fillesIds->isNotEmpty();

            $query = ESBTPClasse::query()
                ->where('niveau_etude_id', $source->niveau_etude_id)
                ->whereNotIn('id', $existingTargetIds)
                ->where('is_active', true)
                ->with('filiere:id,name,code,parent_id');

            if ($fillesIds->isNotEmpty()) {
                $query->whereIn('filiere_id', $fillesIds);
            } else {
                $query->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', false));
            }

            $candidatesByClasse[$source->id] = $query
                ->orderBy('name')
                ->get(['id', 'name', 'filiere_id']);
        }

        return view('esbtp.admin.orientation-targets.index', compact(
            'sourceClasses', 'candidatesByClasse', 'hasFillesByClasse'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'source_classe_id' => ['required', 'exists:esbtp_classes,id'],
            'target_classe_id' => ['required', 'exists:esbtp_classes,id', 'different:source_classe_id'],
            'semestre_activation' => ['nullable', 'integer', 'between:1,8'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'target' => $target->load('targetClasse.filiere'),
            ]);
        }

        return redirect()->route('esbtp.admin.orientation-targets.index')
            ->with('success', 'Cible d\'orientation ajoutée.');
    }

    public function update(Request $request, ESBTPClasseOrientationTarget $target): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'semestre_activation' => ['nullable', 'integer', 'between:1,8'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $target->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'target' => $target->fresh()]);
        }

        return redirect()->back()->with('success', 'Cible mise à jour.');
    }

    public function destroy(Request $request, ESBTPClasseOrientationTarget $target): JsonResponse|RedirectResponse
    {
        $target->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Cible supprimée.');
    }
}
