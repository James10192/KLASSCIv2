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
