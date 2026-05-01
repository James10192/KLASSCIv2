<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPNiveauEtude;

class ESBTPComptabiliteReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('comptabilite.access');
    }

    /**
     * Affiche la liste des frais de scolarité (module legacy, schéma désync —
     * voir docs/COMPTABILITE_CLEANUP_PLAN.md pour la migration vers ESBTPFraisConfiguration).
     */
    public function fraisScolarite()
    {
        $query = ESBTPFraisScolarite::with(['filiere', 'niveau', 'anneeUniversitaire']);

        if (request()->filled('filiere')) {
            $query->where('filiere_id', request('filiere'));
        }

        if (request()->filled('niveau')) {
            $query->where('niveau_etude_id', request('niveau'));
        }

        if (request()->filled('annee')) {
            $query->where('annee_universitaire_id', request('annee'));
        }

        $fraisScolarites = $query->orderByDesc('created_at')->paginate(15);

        return view('esbtp.comptabilite.frais-scolarite.index', [
            'fraisScolarites' => $fraisScolarites,
            'filieres' => ESBTPFiliere::orderBy('name')->get(),
            'niveaux' => ESBTPNiveauEtude::orderBy('name')->get(),
            'annees' => ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get(),
        ]);
    }
}
