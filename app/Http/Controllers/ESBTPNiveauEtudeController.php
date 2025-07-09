<?php

namespace App\Http\Controllers;

use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ESBTPNiveauEtudeController extends Controller
{
    /**
     * Affiche la liste des niveaux d'études.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $niveauxEtudes = ESBTPNiveauEtude::with(['filieres', 'matieres', 'classes.etudiants'])
            ->orderBy('year')
            ->orderBy('name')
            ->get();

        return view('esbtp.niveaux-etudes.index', compact('niveauxEtudes'));
    }

    /**
     * Affiche le formulaire de création d'un nouveau niveau d'études.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('esbtp.niveaux-etudes.create');
    }

    /**
     * Enregistre un nouveau niveau d'études dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_niveau_etudes,code',
            'type' => 'required|string|max:255',
            'year' => 'required|integer|between:1,7',
            'libelle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // S'assurer que is_active est défini
        $validatedData['is_active'] = $request->has('is_active') ? true : false;

        // Créer le nouveau niveau d'études
        ESBTPNiveauEtude::create($validatedData);

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.niveaux-etudes.index')
            ->with('success', 'Le niveau d\'études a été créé avec succès.');
    }

    /**
     * Affiche les détails d'un niveau d'études spécifique.
     *
     * @param  \App\Models\ESBTPNiveauEtude  $niveauxEtude
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPNiveauEtude $niveauxEtude)
    {
        // Charger les relations avec eager loading
        $niveauxEtude->load([
            'filieres',
            'matieres',
            'classes.filiere',
            'classes.etudiants'
        ]);

        return view('esbtp.niveaux-etudes.show', compact('niveauxEtude'));
    }

    /**
     * Affiche le formulaire de modification d'un niveau d'études.
     *
     * @param  \App\Models\ESBTPNiveauEtude  $niveauxEtude
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPNiveauEtude $niveauxEtude)
    {
        return view('esbtp.niveaux-etudes.edit', compact('niveauxEtude'));
    }

    /**
     * Met à jour le niveau d'études spécifié dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPNiveauEtude  $niveauxEtude
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPNiveauEtude $niveauxEtude)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_niveau_etudes,code,' . $niveauxEtude->id,
            'type' => 'required|string|max:255',
            'year' => 'required|integer|between:1,7',
            'libelle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // S'assurer que is_active est défini
        $validatedData['is_active'] = $request->has('is_active') ? true : false;

        // Mettre à jour le niveau d'études
        $niveauxEtude->update($validatedData);

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.niveaux-etudes.index')
            ->with('success', 'Le niveau d\'études a été mis à jour avec succès.');
    }

    /**
     * Supprime le niveau d'études spécifié de la base de données.
     *
     * @param  \App\Models\ESBTPNiveauEtude  $niveauxEtude
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPNiveauEtude $niveauxEtude)
    {
        // Vérifier si le niveau d'études a des éléments liés
        $hasClasses = $niveauxEtude->classes()->count() > 0;
        $hasFilieres = $niveauxEtude->filieres()->count() > 0;
        $hasMatieres = $niveauxEtude->matieres()->count() > 0;

        if ($hasClasses || $hasFilieres || $hasMatieres) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer ce niveau d\'études car il est lié à des classes, filières ou matières.');
        }

        // Supprimer le niveau d'études
        $niveauxEtude->delete();

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.niveaux-etudes.index')
            ->with('success', 'Le niveau d\'études a été supprimé avec succès.');
    }
}
