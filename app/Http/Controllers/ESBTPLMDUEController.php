<?php

namespace App\Http\Controllers;

use App\Models\ESBTPUniteEnseignement;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\Request;

class ESBTPLMDUEController extends Controller
{
    /**
     * Afficher la liste des Unités d'Enseignement avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPUniteEnseignement::query()
            ->withCount('matieres')
            ->with(['filiere', 'niveau', 'parcours']);

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

        $ues = $query->orderBy('semestre')->orderBy('name')->paginate(20)->withQueryString();

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
    public function destroy(ESBTPUniteEnseignement $ue)
    {
        // Vérifier qu'aucun résultat LMD n'est attaché
        if ($ue->resultatsLMD()->exists()) {
            return redirect()->route('esbtp.lmd.ue.index')
                ->with('error', 'Impossible de supprimer cette UE : des résultats y sont rattachés.');
        }

        // Détacher les ECUEs (matières) avant suppression
        $ue->matieres()->update(['unite_enseignement_id' => null]);

        $ue->delete();

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

        if (!empty($validated['matiere_id'])) {
            // Rattacher une matière existante
            $matiere = ESBTPMatiere::findOrFail($validated['matiere_id']);
            $matiere->update([
                'unite_enseignement_id' => $ue->id,
                'credit_ecue'           => $validated['credit_ecue'] ?? $matiere->credit_ecue,
                'coefficient_ecue'      => $validated['coefficient_ecue'] ?? $matiere->coefficient_ecue,
                'ordre_bulletin'        => $validated['ordre_bulletin'] ?? $matiere->ordre_bulletin ?? 0,
                'updated_by'            => auth()->id(),
            ]);
        } else {
            // Créer une nouvelle matière rattachée à cette UE
            ESBTPMatiere::create([
                'name'                  => $validated['name'],
                'code'                  => $validated['code'],
                'unite_enseignement_id' => $ue->id,
                'credit_ecue'           => $validated['credit_ecue'] ?? null,
                'coefficient_ecue'      => $validated['coefficient_ecue'] ?? null,
                'ordre_bulletin'        => $validated['ordre_bulletin'] ?? 0,
                'is_active'             => true,
                'created_by'            => auth()->id(),
                'updated_by'            => auth()->id(),
            ]);
        }

        return redirect()->route('esbtp.lmd.ue.show', $ue)
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

        $validated['updated_by'] = auth()->id();

        $ecue->update($validated);

        return redirect()->route('esbtp.lmd.ue.show', $ue)
            ->with('success', 'ECUE mis à jour avec succès.');
    }

    /**
     * Détacher un ECUE de l'UE (ne supprime pas la matière).
     */
    public function destroyECUE(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        $ecue->update([
            'unite_enseignement_id' => null,
            'updated_by'            => auth()->id(),
        ]);

        return redirect()->route('esbtp.lmd.ue.show', $ue)
            ->with('success', 'ECUE détaché de l\'UE avec succès.');
    }

    /**
     * Liste des parcours disponibles pour une UE (liés + non liés).
     */
    public function parcoursDisponibles(ESBTPUniteEnseignement $ue)
    {
        $lies = $ue->parcoursMultiple()
            ->select('esbtp_lmd_parcours.id', 'esbtp_lmd_parcours.code', 'esbtp_lmd_parcours.name')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'semestre' => $p->pivot->semestre,
            ]);

        $liesIds = $lies->pluck('id')->toArray();

        $disponibles = ESBTPLMDParcours::whereNotIn('id', $liesIds)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
            ]);

        return response()->json(['lies' => $lies, 'disponibles' => $disponibles]);
    }

    /**
     * Synchroniser les parcours d'une UE (pivot esbtp_lmd_parcours_ue).
     */
    public function syncParcours(Request $request, ESBTPUniteEnseignement $ue)
    {
        $items = $request->input('parcours', []);

        $syncData = [];
        foreach ($items as $item) {
            $pId = $item['id'] ?? null;
            if (!$pId) continue;
            $syncData[$pId] = [
                'semestre' => $item['semestre'] ?? ($ue->semestre ?? 1),
            ];
        }

        $ue->parcoursMultiple()->sync($syncData);

        return response()->json(['success' => true, 'message' => count($syncData) . ' parcours lié(s).']);
    }
}
