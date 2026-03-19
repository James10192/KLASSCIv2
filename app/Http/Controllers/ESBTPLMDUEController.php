<?php

namespace App\Http\Controllers;

use App\Models\ESBTPUniteEnseignement;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ESBTPLMDUEController extends Controller
{
    /**
     * Afficher la liste des Unités d'Enseignement avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPUniteEnseignement::query()
            ->withCount('matieres')
            ->with(['filiere', 'niveau', 'parcours', 'parcoursMultiple', 'ecues', 'matieres']);

        // Filtres optionnels
        if ($request->filled('parcours_id')) {
            $query->where('parcours_id', $request->parcours_id);
        }

        if ($request->filled('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        // Also filter by search (name or code)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
        }

        // Also filter by parcours via pivot
        if ($request->filled('parcours_id')) {
            $pId = $request->parcours_id;
            $query->whereHas('parcoursMultiple', fn($q) => $q->where('esbtp_lmd_parcours.id', $pId));
        }

        if ($request->filled('type_ue')) {
            $query->where('type_ue', $request->type_ue);
        }

        $perPage = $request->integer('per_page', 20);
        $ues = $query->orderBy('code')->orderBy('name')->paginate($perPage)->withQueryString();

        // JSON response for AJAX
        if ($request->ajax() || $request->wantsJson() || $request->format === 'json') {
            return response()->json([
                'ues' => $ues->map(function ($ue) {
                    $ecues = $ue->getEcuesEffectifs();
                    return [
                        'id' => $ue->id,
                        'code' => $ue->code,
                        'name' => $ue->name,
                        'type_ue' => $ue->type_ue,
                        'credit' => $ue->credit,
                        'description' => $ue->description,
                        'filiere_id' => $ue->filiere_id,
                        'niveau_id' => $ue->niveau_id,
                        'matieres_count' => $ue->matieres_count,
                        'parcours' => $ue->parcoursMultiple->groupBy('id')->map(fn($pivots) => [
                            'id' => $pivots->first()->id,
                            'code' => $pivots->first()->code,
                            'name' => $pivots->first()->name,
                            'semestres' => $pivots->pluck('pivot.semestre')->sort()->values(),
                        ])->values(),
                        'ecues' => $ecues->map(fn($e) => [
                            'id' => $e->id,
                            'code' => $e->code,
                            'name' => $e->name,
                            'coefficient' => $e->pivot->coefficient_ecue ?? $e->coefficient_ecue ?? null,
                            'credit' => $e->pivot->credit_ecue ?? $e->credit_ecue ?? null,
                            'ordre' => $e->pivot->ordre_bulletin ?? $e->ordre_bulletin ?? 0,
                        ]),
                    ];
                }),
                'pagination' => [
                    'current_page' => $ues->currentPage(),
                    'last_page' => $ues->lastPage(),
                    'per_page' => $ues->perPage(),
                    'total' => $ues->total(),
                ],
            ]);
        }

        // Données pour les filtres
        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.index', compact('ues', 'parcours', 'filieres', 'niveaux'));
    }

    /**
     * Formulaire de création d'une UE.
     */
    public function create()
    {
        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.create', compact('parcours', 'filieres', 'niveaux'));
    }

    /**
     * Retourner les données d'une UE en JSON (pour modal edit).
     */
    public function getJson(ESBTPUniteEnseignement $ue)
    {
        $ue->load('matieres', 'parcoursMultiple');

        $data = $ue->toArray();

        // Ajouter l'ordre du pivot (premier parcours lié)
        $pivot = $ue->parcoursMultiple->first();
        $data['ordre'] = $pivot?->pivot?->ordre ?? 0;

        return response()->json($data);
    }

    /**
     * Enregistrer une nouvelle UE.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:esbtp_unites_enseignement,code',
            'description' => 'nullable|string',
            'credit'      => 'nullable|integer|min:1',
            'semestre'    => 'required|integer|min:1|max:10',
            'type_ue'     => 'required|in:' . implode(',', [
                \App\Models\ESBTPUniteEnseignement::TYPE_FONDAMENTALE,
                \App\Models\ESBTPUniteEnseignement::TYPE_METHODOLOGIQUE,
                \App\Models\ESBTPUniteEnseignement::TYPE_DECOUVERTE,
                \App\Models\ESBTPUniteEnseignement::TYPE_TRANSVERSALE,
            ]),
            'filiere_id'  => 'nullable|exists:esbtp_filieres,id',
            'niveau_id'   => 'nullable|exists:esbtp_niveau_etudes,id',
            'parcours_id' => 'nullable|exists:esbtp_lmd_parcours,id',
            'ordre'       => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $parcoursId = $validated['parcours_id'] ?? null;
        $ordre = $validated['ordre'] ?? 0;
        unset($validated['ordre']); // ordre is on the pivot, not on the UE table

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);

        $ue = ESBTPUniteEnseignement::create($validated);

        // Lier au parcours via pivot si parcours_id fourni
        if ($parcoursId) {
            $ue->parcoursMultiple()->attach($parcoursId, [
                'semestre' => $validated['semestre'],
                'is_optional' => false,
                'ordre' => $ordre,
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE créée avec succès.', 'ue' => $ue]);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'Unité d\'Enseignement créée avec succès.');
    }

    /**
     * Afficher le détail d'une UE avec ses ECUEs (matières).
     */
    public function show(ESBTPUniteEnseignement $ue)
    {
        $ue->load(['matieres', 'filiere', 'niveau', 'parcours', 'createdBy', 'updatedBy']);

        return view('esbtp.lmd.ue.show', compact('ue'));
    }

    /**
     * Formulaire d'édition d'une UE.
     */
    public function edit(ESBTPUniteEnseignement $ue)
    {
        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.edit', compact('ue', 'parcours', 'filieres', 'niveaux'));
    }

    /**
     * Mettre à jour une UE existante.
     */
    public function update(Request $request, ESBTPUniteEnseignement $ue)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:esbtp_unites_enseignement,code,' . $ue->id,
            'description' => 'nullable|string',
            'credit'      => 'nullable|integer|min:1',
            'semestre'    => 'required|integer|min:1|max:10',
            'type_ue'     => 'required|in:' . implode(',', [
                \App\Models\ESBTPUniteEnseignement::TYPE_FONDAMENTALE,
                \App\Models\ESBTPUniteEnseignement::TYPE_METHODOLOGIQUE,
                \App\Models\ESBTPUniteEnseignement::TYPE_DECOUVERTE,
                \App\Models\ESBTPUniteEnseignement::TYPE_TRANSVERSALE,
            ]),
            'filiere_id'  => 'nullable|exists:esbtp_filieres,id',
            'niveau_id'   => 'nullable|exists:esbtp_niveau_etudes,id',
            'parcours_id' => 'nullable|exists:esbtp_lmd_parcours,id',
            'ordre'       => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $parcoursId = $validated['parcours_id'] ?? null;
        $ordre = $validated['ordre'] ?? 0;
        unset($validated['ordre']);

        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);

        $ue->update($validated);

        // Sync pivot parcours
        if ($parcoursId) {
            $ue->parcoursMultiple()->syncWithoutDetaching([
                $parcoursId => [
                    'semestre' => $validated['semestre'],
                    'is_optional' => false,
                    'ordre' => $ordre,
                ],
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE mise à jour avec succès.', 'ue' => $ue]);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'Unité d\'Enseignement mise à jour avec succès.');
    }

    /**
     * Supprimer une UE (si aucun résultat attaché).
     */
    public function destroy(Request $request, ESBTPUniteEnseignement $ue)
    {
        // Vérifier qu'aucun résultat LMD n'est attaché
        if ($ue->resultatsLMD()->exists()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer cette UE : des résultats y sont rattachés.'], 422);
            }
            return redirect()->route('esbtp.lmd.ue.index')
                ->with('error', 'Impossible de supprimer cette UE : des résultats y sont rattachés.');
        }

        // Détacher les ECUEs (matières) avant suppression
        $ue->matieres()->update(['unite_enseignement_id' => null]);
        $ue->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE supprimée avec succès.']);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'Unité d\'Enseignement supprimée avec succès.');
    }

    // -------------------------------------------------------------------------
    //  Gestion des ECUEs (matières rattachées à une UE)
    // -------------------------------------------------------------------------

    /**
     * Ajouter un ECUE à une UE.
     *
     * Soit on rattache une matière existante (matiere_id fourni),
     * soit on crée une nouvelle matière directement.
     */
    public function storeECUE(Request $request, ESBTPUniteEnseignement $ue)
    {
        $validated = $request->validate([
            'matiere_id'       => 'nullable|exists:esbtp_matieres,id',
            // Champs pour création d'une nouvelle matière
            'name'             => 'required_without:matiere_id|nullable|string|max:255',
            'code'             => 'required_without:matiere_id|nullable|string|max:50',
            'credit_ecue'     => 'nullable|integer|min:1',
            'coefficient_ecue' => 'nullable|numeric|min:0',
            'ordre_bulletin'  => 'nullable|integer|min:0',
        ]);

        $coeffEcue = $validated['coefficient_ecue'] ?? null;
        $creditEcue = $validated['credit_ecue'] ?? null;
        $ordreBulletin = $validated['ordre_bulletin'] ?? 0;

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE
        if ($error = $this->checkCreditOverflow($ue, $creditEcue, null, $request)) {
            return $error;
        }

        if (!empty($validated['matiere_id'])) {
            $matiere = ESBTPMatiere::findOrFail($validated['matiere_id']);
        } else {
            // Créer une nouvelle matière
            $matiere = ESBTPMatiere::create([
                'name'                  => $validated['name'],
                'code'                  => $validated['code'],
                'unite_enseignement_id' => $ue->id, // FK direct (rétro-compat)
                'credit_ecue'           => $creditEcue,
                'coefficient_ecue'      => $coeffEcue,
                'ordre_bulletin'        => $ordreBulletin,
                'is_active'             => true,
                'created_by'            => auth()->id(),
                'updated_by'            => auth()->id(),
            ]);
        }

        // Toujours garder le FK direct (rétro-compat)
        if ($matiere->unite_enseignement_id !== $ue->id) {
            $matiere->update(['unite_enseignement_id' => $ue->id, 'updated_by' => auth()->id()]);
        }

        // Écrire dans le pivot (many-to-many) avec coeff/credit contextuels
        $ue->ecues()->syncWithoutDetaching([
            $matiere->id => [
                'coefficient_ecue' => $coeffEcue,
                'credit_ecue' => $creditEcue,
                'ordre_bulletin' => $ordreBulletin,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE ajouté avec succès.']);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE ajouté avec succès à l\'UE.');
    }

    /**
     * Mettre à jour un ECUE rattaché à une UE.
     */
    public function updateECUE(Request $request, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'code'             => 'sometimes|required|string|max:50',
            'credit_ecue'     => 'nullable|integer|min:1',
            'coefficient_ecue' => 'nullable|numeric|min:0',
            'ordre_bulletin'  => 'nullable|integer|min:0',
        ]);

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE
        if ($error = $this->checkCreditOverflow($ue, $validated['credit_ecue'] ?? null, $ecue->id, $request)) {
            return $error;
        }

        // Mettre à jour la matière en un seul UPDATE (nom, code, coeff, credit, ordre)
        $ecue->update([
            'name' => $validated['name'] ?? $ecue->name,
            'code' => $validated['code'] ?? $ecue->code,
            'coefficient_ecue' => $validated['coefficient_ecue'] ?? $ecue->coefficient_ecue,
            'credit_ecue' => $validated['credit_ecue'] ?? $ecue->credit_ecue,
            'ordre_bulletin' => $validated['ordre_bulletin'] ?? $ecue->ordre_bulletin,
            'updated_by' => auth()->id(),
        ]);

        // Mettre à jour le pivot avec les valeurs contextuelles à cette UE
        $ue->ecues()->syncWithoutDetaching([
            $ecue->id => [
                'coefficient_ecue' => $validated['coefficient_ecue'] ?? null,
                'credit_ecue' => $validated['credit_ecue'] ?? null,
                'ordre_bulletin' => $validated['ordre_bulletin'] ?? 0,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE mis à jour.']);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE mis à jour avec succès.');
    }

    /**
     * Détacher un ECUE de l'UE (ne supprime pas la matière).
     */
    public function destroyECUE(Request $request, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        // Détacher du pivot many-to-many
        $ue->ecues()->detach($ecue->id);

        // Aussi nettoyer le FK direct si c'est cette UE
        if ($ecue->unite_enseignement_id === $ue->id) {
            $ecue->update(['unite_enseignement_id' => null, 'updated_by' => auth()->id()]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE détaché avec succès.']);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE détaché de l\'UE avec succès.');
    }

    /**
     * Liste des matières disponibles pour rattachement à une UE (non déjà liées).
     */
    public function matieresDisponibles(ESBTPUniteEnseignement $ue)
    {
        $matieres = ESBTPMatiere::where('is_active', true)
            ->whereDoesntHave('unitesEnseignementMultiple', fn($q) => $q->where('esbtp_ue_matiere.unite_enseignement_id', $ue->id))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'coefficient_ecue', 'credit_ecue']);

        return response()->json($matieres);
    }

    /**
     * Liste des parcours disponibles pour une UE (liés + non liés).
     */
    public function parcoursDisponibles(ESBTPUniteEnseignement $ue)
    {
        $pivotRows = $ue->parcoursMultiple()
            ->select('esbtp_lmd_parcours.id', 'esbtp_lmd_parcours.code', 'esbtp_lmd_parcours.name')
            ->get();

        // Group by parcours id → collect semestres
        $liesMap = [];
        foreach ($pivotRows as $p) {
            if (!isset($liesMap[$p->id])) {
                $liesMap[$p->id] = [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'semestres' => [],
                ];
            }
            $liesMap[$p->id]['semestres'][] = $p->pivot->semestre;
        }
        $lies = array_values($liesMap);
        $liesIds = array_keys($liesMap);

        $disponibles = ESBTPLMDParcours::whereNotIn('id', $liesIds)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'semestres' => [],
            ])->values();

        return response()->json(['lies' => $lies, 'disponibles' => $disponibles]);
    }

    /**
     * Synchroniser les parcours d'une UE (multi-semestres via pivot).
     */
    public function syncParcours(Request $request, ESBTPUniteEnseignement $ue)
    {
        $request->validate([
            'parcours' => 'present|array',
            'parcours.*.id' => 'required|exists:esbtp_lmd_parcours,id',
            'parcours.*.semestres' => 'required|array|min:1',
            'parcours.*.semestres.*' => 'integer|between:1,10',
        ]);

        $count = DB::transaction(function () use ($request, $ue) {
            $ue->parcoursMultiple()->detach();
            $count = 0;
            foreach ($request->input('parcours', []) as $item) {
                foreach ($item['semestres'] as $sem) {
                    $ue->parcoursMultiple()->attach($item['id'], ['semestre' => $sem]);
                    $count++;
                }
            }
            return $count;
        });

        return response()->json(['success' => true, 'message' => $count . ' lien(s) parcours-semestre créé(s).']);
    }

    /**
     * Vérifier que l'ajout/modification d'un crédit ECUE ne dépasse pas le crédit de l'UE.
     * Retourne une response d'erreur si dépassement, null sinon.
     */
    private function checkCreditOverflow(ESBTPUniteEnseignement $ue, $creditEcue, ?int $excludeMatiereId, Request $request)
    {
        if (!$ue->credit || !$creditEcue) {
            return null;
        }

        $query = DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id);
        if ($excludeMatiereId) {
            $query->where('matiere_id', '!=', $excludeMatiereId);
        }
        $creditsAutres = (int) $query->sum('credit_ecue');

        if ($creditsAutres + (int) $creditEcue <= (int) $ue->credit) {
            return null;
        }

        $restant = (int) $ue->credit - $creditsAutres;
        $message = "La somme des crédits ECUE ({$creditsAutres} + {$creditEcue} = " . ($creditsAutres + (int) $creditEcue) . ") "
            . "dépasse le crédit de l'UE ({$ue->credit}). Il reste {$restant} crédit(s) disponible(s).";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message);
    }
}
