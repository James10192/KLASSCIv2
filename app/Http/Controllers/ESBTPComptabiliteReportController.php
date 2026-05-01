<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPComptabiliteConfiguration;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPBourse;
use App\Models\ESBTPTransactionFinanciere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPClasse;
use App\Models\User;
use App\Services\ComptabiliteService;
use App\Services\PerformanceMonitoringService;
use App\Services\AnalyticsPredictifService;
use App\Services\AIAnalyticsService;
use App\Services\BonDepenseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPComptabiliteReportController extends Controller
{
    /**
     * Constructeur avec injection des services optimisés
     */
    public function __construct(
        ComptabiliteService $comptabiliteService,
        PerformanceMonitoringService $performanceMonitor,
        AnalyticsPredictifService $analyticsPredictifService,
        AIAnalyticsService $aiAnalyticsService,
        BonDepenseService $bonDepenseService
    ) {
        $this->comptabiliteService = $comptabiliteService;
        $this->performanceMonitor = $performanceMonitor;
        $this->analyticsPredictifService = $analyticsPredictifService;
        $this->aiAnalyticsService = $aiAnalyticsService;
        $this->bonDepenseService = $bonDepenseService;

        $this->middleware('auth');
        $this->middleware('comptabilite.access');
    }


    /**
     * Affiche les rapports financiers
     */
    public function rapports()
    {
        $statsRecettes = $this->getStatsRecettes();
        $statsDepenses = $this->getStatsDepenses();
        $statsPaiements = $this->getStatsPaiements();
        $recettesParMois = $this->getRecettesParMois();
        $depensesParMois = $this->getDepensesParMois();

        return view('esbtp.comptabilite.rapports', compact(
            'statsRecettes',
            'statsDepenses',
            'statsPaiements',
            'recettesParMois',
            'depensesParMois'
        ));
    }

    /**
     * Récupère les statistiques des recettes.
     */
    private function getStatsRecettes()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return ['total' => 0, 'mensuel' => 0, 'annuel' => 0, 'previsionnel' => 0];
        }

        $totalPaiements = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'validé')
            ->sum('montant');

        $paiementsMensuels = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'validé')
            ->whereMonth('date_paiement', Carbon::now()->month)
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        $paiementsAnnuels = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'validé')
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        $totalPrevisionnel = ESBTPFraisScolarite::where('annee_universitaire_id', $anneeEnCours->id)
            ->sum('montant_total');

        return [
            'total' => $totalPaiements,
            'mensuel' => $paiementsMensuels,
            'annuel' => $paiementsAnnuels,
            'previsionnel' => $totalPrevisionnel,
        ];
    }

    /**
     * Récupère les statistiques des dépenses (module supprimé, valeurs vides).
     */
    private function getStatsDepenses()
    {
        return ['total' => 0, 'mensuel' => 0, 'salaires' => 0, 'fournitures' => 0];
    }

    /**
     * Récupère les statistiques des paiements (taux de recouvrement).
     */
    private function getStatsPaiements()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return ['total' => 0, 'complets' => 0, 'partiels' => 0, 'impayés' => 0, 'taux_recouvrement' => 0];
        }

        $totalInscriptions = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)->count();

        $etudiantsPayeComplet = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) >= (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_etude_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        $etudiantsPayePartiel = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) > 0 AND SUM(esbtp_paiements.montant) < (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_etude_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        $etudiantsImpaye = $totalInscriptions - $etudiantsPayeComplet - $etudiantsPayePartiel;
        $tauxRecouvrement = $totalInscriptions > 0
            ? round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2)
            : 0;

        return [
            'total' => $totalInscriptions,
            'complets' => $etudiantsPayeComplet,
            'partiels' => $etudiantsPayePartiel,
            'impayés' => $etudiantsImpaye,
            'taux_recouvrement' => $tauxRecouvrement,
        ];
    }

    /**
     * Récupère les recettes par mois pour l'année en cours (12 derniers mois).
     */
    private function getRecettesParMois()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return ['labels' => [], 'data' => []];
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $dates = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $dates[] = $date->copy();
        }
        $dates = array_slice($dates, -12);

        $mois = [];
        $recettes = [];
        foreach ($dates as $date) {
            $mois[] = $date->translatedFormat('F Y');
            $recettes[] = ESBTPPaiement::whereMonth('date_paiement', $date->month)
                ->whereYear('date_paiement', $date->year)
                ->where('status', 'validé')
                ->sum('montant');
        }

        return ['labels' => $mois, 'data' => $recettes];
    }

    /**
     * Dépenses par mois (module supprimé, données vides mais shape conservé).
     */
    private function getDepensesParMois()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return ['labels' => [], 'data' => []];
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $dates = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $dates[] = $date->copy();
        }
        $dates = array_slice($dates, -12);

        $mois = [];
        foreach ($dates as $date) {
            $mois[] = $date->translatedFormat('F Y');
        }

        return ['labels' => $mois, 'data' => array_fill(0, count($mois), 0)];
    }


    /**
     * Génère un rapport financier personnalisé
     */
    public function generateReport(Request $request)
    {
        // Logique pour générer un rapport personnalisé selon les paramètres de la requête
        $dateDebut = $request->input('date_debut', now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', 'general');

        // Récupération des données selon le type de rapport
        $data = [];

        switch ($type) {
            case 'paiements':
                $data['paiements'] = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
                    ->with(['etudiant', 'anneeUniversitaire'])
                    ->get();
                break;

            case 'depenses':
                $data['depenses'] = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
                    ->with(['categorie', 'createur'])
                    ->get();
                break;

            case 'general':
            default:
                $data['paiements'] = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
                    ->with(['etudiant', 'anneeUniversitaire'])
                    ->get();

                $data['depenses'] = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
                    ->with(['categorie', 'createur'])
                    ->get();

                $data['totalRecettes'] = $data['paiements']->sum('montant');
                $data['totalDepenses'] = $data['depenses']->sum('montant');
                $data['balance'] = $data['totalRecettes'] - $data['totalDepenses'];
                break;
        }

        $data['dateDebut'] = $dateDebut;
        $data['dateFin'] = $dateFin;
        $data['type'] = $type;

        return view('esbtp.comptabilite.rapports', compact('data'));
    }


    /**
     * Exporte un rapport financier
     */
    public function exportReport(Request $request)
    {
        // Logique pour exporter un rapport au format PDF, Excel ou CSV
        $format = $request->input('format', 'pdf');

        // Exemple simple, dans un cas réel, vous utiliseriez une bibliothèque comme Dompdf ou Laravel Excel
        return redirect()->back()->with('success', 'Fonctionnalité d\'export en cours de développement.');
    }


    /**
     * Affiche la liste des frais de scolarité
     */
    public function fraisScolarite()
    {
        $query = ESBTPFraisScolarite::with(['filiere', 'niveau', 'anneeUniversitaire']);

        // Filtres
        if (request()->has('filiere') && !empty(request('filiere'))) {
            $query->where('filiere_id', request('filiere'));
        }

        if (request()->has('niveau') && !empty(request('niveau'))) {
            $query->where('niveau_id', request('niveau'));
        }

        if (request()->has('annee') && !empty(request('annee'))) {
            $query->where('annee_universitaire_id', request('annee'));
        }

        $fraisScolarites = $query->orderBy('created_at', 'desc')->paginate(15);

        // Récupérer les données pour les filtres
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        return view('esbtp.comptabilite.frais-scolarite.index', compact('fraisScolarites', 'filieres', 'niveaux', 'annees'));
    }

}
