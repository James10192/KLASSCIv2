<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatUE;
use Illuminate\Http\Request;

class ESBTPLMDResultatController extends Controller
{
    /**
     * Dashboard resultats LMD — vue d'ensemble par classe.
     */
    public function index(Request $request)
    {
        $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
        $anneeId = $request->annee_universitaire_id
            ?? $annees->firstWhere('is_current', true)?->id;

        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->where('is_active', true)
            ->when($anneeId, fn($q, $id) => $q->where('annee_universitaire_id', $id))
            ->withCount([
                'inscriptions as total_etudiants' => fn($q) => $q
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->when($anneeId, fn($q2, $id) => $q2->where('annee_universitaire_id', $id)),
            ])
            ->orderBy('name')
            ->get();

        // Stats par classe: nombre de bulletins generes
        $bulletinCounts = ESBTPLMDBulletin::where('annee_universitaire_id', $anneeId)
            ->select('classe_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(moyenne_generale) as moy_classe')
            ->selectRaw('SUM(CASE WHEN credits_capitalises = credits_totaux THEN 1 ELSE 0 END) as total_valides')
            ->groupBy('classe_id')
            ->get()
            ->keyBy('classe_id');

        return view('esbtp.lmd.resultats.index', compact('classes', 'annees', 'anneeId', 'bulletinCounts'));
    }

    /**
     * Resultats d'une classe LMD — detail par semestre.
     */
    public function classe(Request $request, ESBTPClasse $classe)
    {
        $semestre = $request->semestre ?? 1;
        $anneeId = $request->annee_universitaire_id
            ?? $classe->annee_universitaire_id;

        $bulletins = ESBTPLMDBulletin::where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $anneeId)
            ->where('semestre', $semestre)
            ->with(['etudiant', 'resultatsUEs.uniteEnseignement'])
            ->orderBy('rang')
            ->get();

        // Stats globales
        $stats = [
            'effectif' => $bulletins->count(),
            'moyenne_classe' => $bulletins->avg('moyenne_generale'),
            'min' => $bulletins->min('moyenne_generale'),
            'max' => $bulletins->max('moyenne_generale'),
            'taux_validation' => $bulletins->count() > 0
                ? round($bulletins->filter(fn($b) => $b->credits_totaux > 0 && $b->credits_capitalises >= $b->credits_totaux)->count() / $bulletins->count() * 100, 1)
                : 0,
        ];

        $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();

        return view('esbtp.lmd.resultats.classe', compact('classe', 'bulletins', 'stats', 'semestre', 'anneeId', 'annees'));
    }

    /**
     * Resultats individuels d'un etudiant LMD — tous semestres.
     */
    public function etudiant(ESBTPEtudiant $etudiant)
    {
        $bulletins = ESBTPLMDBulletin::where('etudiant_id', $etudiant->id)
            ->with([
                'resultatsUEs.uniteEnseignement',
                'resultatsUEs.resultatsECUEs.matiere',
                'resultatsUEs.resultatsECUEs.enseignant',
                'classe',
                'anneeUniversitaire',
                'deliberation',
            ])
            ->orderByDesc('annee_universitaire_id')
            ->orderBy('semestre')
            ->get();

        // Calculer le cumul des credits sur toutes les annees
        $creditsCumules = $bulletins->sum('credits_capitalises');
        $creditsTotauxCumules = $bulletins->sum('credits_totaux');

        return view('esbtp.lmd.resultats.etudiant', compact(
            'etudiant', 'bulletins', 'creditsCumules', 'creditsTotauxCumules'
        ));
    }
}
