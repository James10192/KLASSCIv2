<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ESBTPAnneeUniversitaireController extends Controller
{
    /**
     * Affiche la liste des années universitaires.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.annees-universitaires.index', compact('anneesUniversitaires'));
    }

    /**
     * Affiche le formulaire de création d'une nouvelle année universitaire.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('esbtp.annees-universitaires.create');
    }

    /**
     * Enregistre une nouvelle année universitaire dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:esbtp_annee_universitaires,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Créer la nouvelle année universitaire
        $anneeUniversitaire = ESBTPAnneeUniversitaire::create($validatedData);

        // Si cette année est définie comme l'année en cours, mettre à jour les autres années
        if ($request->has('is_current') && $request->is_current) {
            $anneeUniversitaire->setAsCurrent();
        }

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.annees-universitaires.index')
            ->with('success', 'L\'année universitaire a été créée avec succès.');
    }

        /**
     * Affiche les détails d'une année universitaire spécifique.
     *
     * @param  \App\Models\ESBTPAnneeUniversitaire  $anneesUniversitaire
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPAnneeUniversitaire $anneesUniversitaire)
    {
        // Charger les étudiants inscrits pour cette année
        $anneesUniversitaire->load('inscriptions.etudiant', 'inscriptions.filiere', 'inscriptions.niveauEtude');

        return view('esbtp.annees-universitaires.show', compact('anneesUniversitaire'));
    }

    /**
     * Affiche le formulaire de modification d'une année universitaire.
     *
     * @param  \App\Models\ESBTPAnneeUniversitaire  $anneesUniversitaire
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPAnneeUniversitaire $anneesUniversitaire)
    {
        return view('esbtp.annees-universitaires.edit', compact('anneesUniversitaire'));
    }

    /**
     * Met à jour l'année universitaire spécifiée dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPAnneeUniversitaire  $anneesUniversitaire
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPAnneeUniversitaire $anneesUniversitaire)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:esbtp_annee_universitaires,name,' . $anneesUniversitaire->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Mettre à jour l'année universitaire
        $anneesUniversitaire->update($validatedData);

        // Si cette année est définie comme l'année en cours, mettre à jour les autres années
        if ($request->has('is_current') && $request->is_current) {
            $anneesUniversitaire->setAsCurrent();
        }

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.annees-universitaires.index')
            ->with('success', 'L\'année universitaire a été mise à jour avec succès.');
    }

    /**
     * Supprime l'année universitaire spécifiée de la base de données.
     *
     * @param  \App\Models\ESBTPAnneeUniversitaire  $anneesUniversitaire
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPAnneeUniversitaire $anneesUniversitaire)
    {
        // Vérifier si l'année universitaire a des étudiants inscrits
        if ($anneesUniversitaire->inscriptions()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer cette année universitaire car des étudiants y sont inscrits.');
        }

        // Supprimer l'année universitaire
        $anneesUniversitaire->delete();

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.annees-universitaires.index')
            ->with('success', 'L\'année universitaire a été supprimée avec succès.');
    }

    /**
     * Définit l'année universitaire spécifiée comme l'année en cours.
     *
     * @param  \App\Models\ESBTPAnneeUniversitaire  $anneesUniversitaire
     * @return \Illuminate\Http\Response
     */
    public function setCurrent(ESBTPAnneeUniversitaire $anneesUniversitaire)
    {
        // Définir cette année comme l'année en cours
        $anneesUniversitaire->setAsCurrent();

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.annees-universitaires.index')
            ->with('success', 'L\'année universitaire a été définie comme l\'année en cours.');
    }
}
