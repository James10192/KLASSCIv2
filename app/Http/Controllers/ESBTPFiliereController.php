<?php

namespace App\Http\Controllers;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPMatiere;
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
            'classes' => function($query) {
                $query->withCount('inscriptions');
            }
        ])->findOrFail($id);

        return view('esbtp.filieres.show', compact('filiere'));
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
