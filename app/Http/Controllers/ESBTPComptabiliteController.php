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

class ESBTPComptabiliteController extends Controller
{
    protected $comptabiliteService;
    protected $performanceMonitor;
    protected $analyticsPredictifService;
    protected $aiAnalyticsService;
    protected $bonDepenseService;

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
     * Affiche le tableau de bord de la comptabilité avec cache optimisé
     */
    public function index()
    {
        return $this->performanceMonitor->monitor('dashboard_index', function () {
            // Utiliser le cache pour les données du dashboard
            $dashboardData = Cache::store('dashboard_queries')->remember('dashboard_main', 15, function () {
                return [
                    'statsRecettes' => $this->getStatsRecettes(),
                    'statsDepenses' => $this->getStatsDepenses(),
                    'statsPaiements' => $this->getStatsPaiements(),
                    'topEtudiants' => $this->getTopEtudiants(),
                    'topDettes' => $this->getTopDettes(),
                    'recettesParMois' => $this->getRecettesParMois(),
                    'depensesParMois' => $this->getDepensesParMois()
                ];
            });

            return view('esbtp.comptabilite.index', $dashboardData);
        }, ['user_id' => Auth::id()]);
    }


    /**
     * Prépare les données financières détaillées en temps réel
     */
    private function preparerDonneesDetailleesTempsReel(): array
    {
        $dateDebut = Carbon::now()->subYear();
        $dateFin = Carbon::now();

        // Évolution mensuelle des recettes (vraies données)
        $recettesMensuelles = ESBTPPaiement::selectRaw('
                YEAR(date_paiement) as annee,
                MONTH(date_paiement) as mois,
                SUM(montant) as total,
                COUNT(*) as nombre_paiements,
                AVG(montant) as moyenne_paiement
            ')
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->where('status', 'validé')
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();

        // Évolution mensuelle des dépenses (vraies données)
        $depensesMensuelles = ESBTPDepense::selectRaw('
                YEAR(date_depense) as annee,
                MONTH(date_depense) as mois,
                SUM(montant) as total,
                COUNT(*) as nombre_depenses,
                AVG(montant) as moyenne_depense
            ')
            ->whereBetween('date_depense', [$dateDebut, $dateFin])
            ->where('statut', 'validée')
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();

        // Répartition par filière/niveau (vraies données)
        $repartitionFilieres = ESBTPInscription::join('esbtp_filieres', 'esbtp_inscriptions.filiere_id', '=', 'esbtp_filieres.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->selectRaw('
                esbtp_filieres.libelle as filiere,
                esbtp_inscriptions.niveau_id as niveau,
                SUM(esbtp_paiements.montant) as total_recettes,
                COUNT(DISTINCT esbtp_inscriptions.etudiant_id) as nombre_etudiants
            ')
            ->where('esbtp_paiements.status', 'validé')
            ->where('esbtp_paiements.date_paiement', '>=', $dateDebut)
            ->groupBy('esbtp_filieres.id', 'esbtp_filieres.libelle', 'esbtp_inscriptions.niveau_id')
            ->get();

        // Analyse du taux de recouvrement en temps réel
        $tauxRecouvrementDetaille = $this->calculerTauxRecouvrementDetailleTempsReel();

        return [
            'recettes_mensuelles' => $recettesMensuelles,
            'depenses_mensuelles' => $depensesMensuelles,
            'repartition_filieres' => $repartitionFilieres,
            'taux_recouvrement_detaille' => $tauxRecouvrementDetaille,
            'derniere_mise_a_jour' => now()->toISOString()
        ];
    }

    /**
     * Calcule les métriques de performance en temps réel
     */
    private function calculerMetriquesPerformanceTempsReel(): array
    {
        $anneeActive = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeActive) {
            return [];
        }

        // Performance des recouvrements
        $performanceRecouvrement = ESBTPPaiement::selectRaw('
                AVG(DATEDIFF(date_paiement, date_echeance)) as delai_moyen_paiement,
                SUM(CASE WHEN date_paiement <= date_echeance THEN 1 ELSE 0 END) / COUNT(*) * 100 as taux_ponctualite,
                COUNT(*) as total_paiements
            ')
            ->where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'validé')
            ->first();

        // Évolution du cash flow
        $cashFlow = $this->calculerCashFlowTempsReel($anneeActive);

        // Performance des relances
        $performanceRelances = $this->calculerPerformanceRelancesTempsReel();

        // ROI des investissements
        $roiInvestissements = $this->calculerROIInvestissementsTempsReel($anneeActive);

        return [
            'performance_recouvrement' => $performanceRecouvrement,
            'cash_flow' => $cashFlow,
            'performance_relances' => $performanceRelances,
            'roi_investissements' => $roiInvestissements,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Génère des alertes intelligentes basées sur l'IA et les données réelles
     */
    private function genererAlertesIntelligentes(array $kpis, array $insightsIA): array
    {
        $alertes = [];

        // Alertes basées sur les KPIs réels
        if (($kpis['taux_recouvrement'] ?? 0) < 75) {
            $alertes[] = [
                'niveau' => 'critique',
                'type' => 'recouvrement',
                'titre' => 'Taux de recouvrement critique',
                'message' => "Le taux de recouvrement ({$kpis['taux_recouvrement']}%) est en dessous du seuil critique (75%)",
                'valeur' => $kpis['taux_recouvrement'],
                'seuil' => 75,
                'action_recommandee' => 'Intensifier les relances automatiques et réviser la stratégie de recouvrement',
                'impact_estime' => 'Risque de déséquilibre financier imminent'
            ];
        }

        // Alertes IA
        if (isset($insightsIA['alertes_automatiques']) && !empty($insightsIA['alertes_automatiques'])) {
            foreach ($insightsIA['alertes_automatiques'] as $alerte) {
                $alertes[] = [
                    'niveau' => $alerte['type'] ?? 'info',
                    'type' => 'ia_detection',
                    'titre' => 'Détection automatique IA',
                    'message' => $alerte['message'] ?? 'Anomalie détectée par l\'IA',
                    'action_recommandee' => $alerte['action'] ?? 'Analyser en détail',
                    'ia_generated' => true
                ];
            }
        }

        // Alertes de cash flow prédictif
        $predictionsAIA = $insightsIA['predictions'] ?? [];
        foreach ($predictionsAIA as $prediction) {
            if (($prediction['resultat_predit'] ?? 0) < 0 && ($prediction['confiance'] ?? 0) > 70) {
                $alertes[] = [
                    'niveau' => 'warning',
                    'type' => 'cash_flow_prediction',
                    'titre' => 'Prédiction cash flow négatif',
                    'message' => "Cash flow négatif prévu pour {$prediction['mois_nom']} ({$prediction['resultat_predit']} FCFA)",
                    'confiance' => $prediction['confiance'],
                    'action_recommandee' => 'Planifier des mesures correctives dès maintenant'
                ];
            }
        }

        return $alertes;
    }

    /**
     * Prépare les données pour les visualisations avancées
     */
    private function preparerDonneesVisualisations(): array
    {
        return [
            'graphique_evolution_finances' => $this->getDonneesGraphiqueEvolution(),
            'graphique_repartition_filieres' => $this->getDonneesRepartitionFilieres(),
            'graphique_comparaison_objectifs' => $this->getDonneesComparaisonObjectifs(),
            'graphique_tendances_paiements' => $this->getDonneesTendancesPaiements(),
            'graphique_performance_mensuelle' => $this->getDonneesPerformanceMensuelle()
        ];
    }

    /**
     * Calcule le taux de recouvrement détaillé en temps réel
     */
    private function calculerTauxRecouvrementDetailleTempsReel(): array
    {
        $anneeActive = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeActive) {
            return [];
        }

        // Par filière
        $parFiliere = ESBTPInscription::join('esbtp_filieres', 'esbtp_inscriptions.filiere_id', '=', 'esbtp_filieres.id')
            ->leftJoin('esbtp_paiements', function($join) {
                $join->on('esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
                     ->where('esbtp_paiements.status', 'validé');
            })
            ->selectRaw('
                esbtp_filieres.libelle as filiere,
                COUNT(DISTINCT esbtp_inscriptions.etudiant_id) as total_etudiants,
                COUNT(DISTINCT esbtp_paiements.etudiant_id) as etudiants_ayant_paye,
                COALESCE(SUM(esbtp_paiements.montant), 0) as total_paye,
                (COUNT(DISTINCT esbtp_paiements.etudiant_id) / COUNT(DISTINCT esbtp_inscriptions.etudiant_id) * 100) as taux_recouvrement
            ')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeActive->id)
            ->groupBy('esbtp_filieres.id', 'esbtp_filieres.libelle')
            ->get();

        // Par mois
        $parMois = ESBTPPaiement::selectRaw('
                MONTH(date_paiement) as mois,
                YEAR(date_paiement) as annee,
                COUNT(*) as nombre_paiements,
                SUM(montant) as total_paye
            ')
            ->where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'validé')
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();

        return [
            'par_filiere' => $parFiliere,
            'par_mois' => $parMois,
            'derniere_mise_a_jour' => now()->toISOString()
        ];
    }

    /**
     * Génère des alertes basées sur les KPIs
     */
    private function genererAlertes($kpis)
    {
        $alertes = [];

        // Alerte sur le taux de recouvrement
        if (($kpis['taux_recouvrement'] ?? 0) < 70) {
            $alertes[] = [
                'niveau' => 'warning',
                'titre' => 'Taux de recouvrement bas',
                'message' => 'Le taux de recouvrement est inférieur à 70%',
                'valeur' => $kpis['taux_recouvrement'] ?? 0,
                'pourcentage' => $kpis['taux_recouvrement'] ?? 0
            ];
        }

        // Alerte sur le résultat net
        if (($kpis['resultat_net'] ?? 0) < 0) {
            $alertes[] = [
                'niveau' => 'critique',
                'titre' => 'Résultat net négatif',
                'message' => 'Les dépenses dépassent les recettes',
                'valeur' => $kpis['resultat_net'] ?? 0
            ];
        }

        return $alertes;
    }

    /**
     * Données KPIs par défaut
     */
    private function getDefaultKPIs()
    {
        return [
            'total_recettes' => 0,
            'total_depenses' => 0,
            'resultat_net' => 0,
            'taux_recouvrement' => 0,
            'marge_nette' => 0,
            'objectif_atteint' => 0,
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * API pour les KPIs en temps réel avec cache optimisé
     */
    public function kpisTempsReel(Request $request)
    {
        return $this->performanceMonitor->monitor('kpis_temps_reel', function () use ($request) {
            try {
                $anneeId = $request->get('annee_id');
                $kpis = $this->comptabiliteService->getKPIsDashboard($anneeId);

                return response()->json([
                    'success' => true,
                    'kpis' => $kpis,
                    'cache_info' => [
                        'cached' => isset($kpis['cache_generated_at']),
                        'last_updated' => $kpis['last_updated'] ?? now()->toISOString()
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur KPIs temps réel', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de la récupération des KPIs'
                ], 500);
            }
        }, ['annee_id' => $request->get('annee_id')]);
    }

    /**
     * Récupère les statistiques des recettes
     */
    private function getStatsRecettes()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'total' => 0,
                'mensuel' => 0,
                'annuel' => 0,
                'previsionnel' => 0
            ];
        }

        // Total des paiements reçus
        $totalPaiements = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'validé')
            ->sum('montant');

        // Paiements du mois en cours
        $paiementsMensuels = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'validé')
            ->whereMonth('date_paiement', Carbon::now()->month)
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        // Paiements de l'année en cours
        $paiementsAnnuels = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'validé')
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        // Montant prévisionnel (total des frais de scolarité configurés)
        $totalPrevisionnel = ESBTPFraisScolarite::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('est_actif', true)
            ->sum('montant_total');

        return [
            'total' => $totalPaiements,
            'mensuel' => $paiementsMensuels,
            'annuel' => $paiementsAnnuels,
            'previsionnel' => $totalPrevisionnel
        ];
    }

    /**
     * Récupère les statistiques des dépenses
     */
    private function getStatsDepenses()
    {
        // NOTE: Les modules Dépenses et Salaires ont été supprimés
        // Cette méthode retourne des valeurs vides pour maintenir la compatibilité
        return [
            'total' => 0,
            'mensuel' => 0,
            'salaires' => 0,
            'fournitures' => 0
        ];
    }

    /**
     * Récupère les statistiques des paiements
     */
    private function getStatsPaiements()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'total' => 0,
                'complets' => 0,
                'partiels' => 0,
                'impayés' => 0,
                'taux_recouvrement' => 0
            ];
        }

        // Nombre total d'inscriptions
        $totalInscriptions = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)->count();

        // Nombre d'étudiants ayant payé complètement
        $etudiantsPayeComplet = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) >= (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        // Nombre d'étudiants ayant payé partiellement
        $etudiantsPayePartiel = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) > 0 AND SUM(esbtp_paiements.montant) < (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        // Nombre d'étudiants n'ayant rien payé
        $etudiantsImpaye = $totalInscriptions - $etudiantsPayeComplet - $etudiantsPayePartiel;

        // Taux de recouvrement
        $tauxRecouvrement = $totalInscriptions > 0 ?
            round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2) : 0;

        return [
            'total' => $totalInscriptions,
            'complets' => $etudiantsPayeComplet,
            'partiels' => $etudiantsPayePartiel,
            'impayés' => $etudiantsImpaye,
            'taux_recouvrement' => $tauxRecouvrement
        ];
    }

    /**
     * Récupère les meilleurs payeurs (top 5)
     */
    private function getTopEtudiants()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        return DB::table('esbtp_paiements')
            ->join('esbtp_etudiants', 'esbtp_paiements.etudiant_id', '=', 'esbtp_etudiants.id')
            ->select('esbtp_etudiants.id', 'esbtp_etudiants.nom', 'esbtp_etudiants.prenom', DB::raw('SUM(esbtp_paiements.montant) as total_paye'))
            ->where('esbtp_paiements.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id', 'esbtp_etudiants.nom', 'esbtp_etudiants.prenom')
            ->orderByDesc('total_paye')
            ->limit(5)
            ->get();
    }

    /**
     * Récupère les plus grands débiteurs (top 5)
     */
    private function getTopDettes()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        return DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_frais_scolarite', function($join) {
                $join->on('esbtp_frais_scolarite.filiere_id', '=', 'esbtp_inscriptions.filiere_id');
                $join->on('esbtp_frais_scolarite.niveau_id', '=', 'esbtp_inscriptions.niveau_id');
                $join->on('esbtp_frais_scolarite.annee_universitaire_id', '=', 'esbtp_inscriptions.annee_universitaire_id');
            })
            ->leftJoin('esbtp_paiements', function($join) {
                $join->on('esbtp_paiements.etudiant_id', '=', 'esbtp_inscriptions.etudiant_id');
                $join->on('esbtp_paiements.annee_universitaire_id', '=', 'esbtp_inscriptions.annee_universitaire_id');
            })
            ->select(
                'esbtp_etudiants.id',
                'esbtp_etudiants.nom',
                'esbtp_etudiants.prenom',
                'esbtp_frais_scolarite.montant_total',
                DB::raw('COALESCE(SUM(esbtp_paiements.montant), 0) as montant_paye'),
                DB::raw('esbtp_frais_scolarite.montant_total - COALESCE(SUM(esbtp_paiements.montant), 0) as dette')
            )
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy(
                'esbtp_etudiants.id',
                'esbtp_etudiants.nom',
                'esbtp_etudiants.prenom',
                'esbtp_frais_scolarite.montant_total'
            )
            ->having(DB::raw('esbtp_frais_scolarite.montant_total - COALESCE(SUM(esbtp_paiements.montant), 0)'), '>', 0)
            ->orderByDesc('dette')
            ->limit(5)
            ->get();
    }

    /**
     * Récupère les recettes par mois pour l'année en cours
     */
    private function getRecettesParMois()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $mois = [];
        $recettes = [];

        // Génère tous les mois de la période
        $dates = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $dates[] = $date->copy();
        }
        // Prend les 12 derniers mois seulement
        $dates = array_slice($dates, -12);
        foreach ($dates as $date) {
            $mois[] = $date->translatedFormat('F Y');
            $total = ESBTPPaiement::whereMonth('date_paiement', $date->month)
                ->whereYear('date_paiement', $date->year)
                ->where('status', 'validé')
                ->sum('montant');
            $recettes[] = $total;
        }
        return [
            'labels' => $mois,
            'data' => $recettes
        ];
    }

    /**
     * Récupère les dépenses par mois pour l'année en cours
     */
    private function getDepensesParMois()
    {
        // NOTE: Le module Dépenses a été supprimé
        // Cette méthode retourne des données vides pour maintenir la compatibilité
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'labels' => [],
                'data' => []
            ];
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $mois = [];
        $depenses = [];

        // Génère tous les mois de la période avec montants à zéro
        $dates = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $dates[] = $date->copy();
        }
        // Prend les 12 derniers mois seulement
        $dates = array_slice($dates, -12);
        foreach ($dates as $date) {
            $mois[] = $date->translatedFormat('F Y');
            $depenses[] = 0; // Toujours zéro car module supprimé
        }

        return [
            'labels' => $mois,
            'data' => $depenses
        ];
    }

    /**
     * Affiche le dashboard de la comptabilité
     */
    public function dashboard(Request $request)
    {
        // Récupérer les listes pour les filtres
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $classes = \App\Models\ESBTPClasse::orderBy('name')->get();

        // Année courante (pour pill badge — indépendant du filtre)
        $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Filtres
        $anneeId = $request->get('annee');
        $filiereId = $request->get('filiere');
        $classeId = $request->get('classe');

        // Déterminer l'année de visualisation (filtrée ou courante par défaut)
        if ($anneeId) {
            $annee = $annees->where('id', $anneeId)->first();
        } else {
            $annee = $anneeActive ?? $annees->first();
            // Synchroniser l'anneeId pour les requêtes qui suivent
            if ($annee) {
                $anneeId = $annee->id;
            }
        }

        // Stats paiements via ESBTPPaiement (système actif)
        $paiementsQuery = \App\Models\ESBTPPaiement::query()->whereNull('deleted_at');

        if ($anneeId) {
            $paiementsQuery->whereHas('inscription', fn($q) => $q->where('annee_universitaire_id', $anneeId));
        }
        if ($filiereId) {
            $paiementsQuery->whereHas('inscription.classe', fn($q) => $q->where('filiere_id', $filiereId));
        }
        if ($classeId) {
            $paiementsQuery->whereHas('inscription', fn($q) => $q->where('classe_id', $classeId));
        }

        $totalPaid = (clone $paiementsQuery)->whereIn('status', ['validé'])->sum('montant');

        // Stats frais — calcul cohérent avec suivi-catégories (inscriptions × catégories + fallback config)
        ['totalDue' => $totalDue, 'countDue' => $countDue] = $this->calculerTotalDu($anneeId, $filiereId, $classeId);
        $totalOverdue = max(0, $totalDue - $totalPaid);

        // Nombre paiements en attente / validés
        $countPaid = (clone $paiementsQuery)->where('status', 'validé')->count();
        $countPartiallyPaid = (clone $paiementsQuery)->where('status', 'en_attente')->count();
        $countOverdue = \App\Models\ESBTPInscription::query()
            ->when($anneeId, fn($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when($filiereId, fn($q) => $q->whereHas('classe', fn($q2) => $q2->where('filiere_id', $filiereId)))
            ->when($classeId, fn($q) => $q->where('classe_id', $classeId))
            ->whereIn('status', ['active', 'en_attente', 'validée'])
            ->count();

        // Dépenses (module supprimé — valeurs à zéro)
        $statsDepenses = ['total' => 0, 'mensuel' => 0, 'salaires' => 0, 'fournitures' => 0];

        // Graphique encaissements par mois
        $labelsMois = [];
        $dataEncaissements = [];
        $labelsMoisDepenses = [];
        $dataDepensesMensuelles = [];

        if ($annee && $annee->start_date) {
            $debut = \Carbon\Carbon::parse($annee->start_date);
            $fin = \Carbon\Carbon::parse($annee->end_date ?? now());
            for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
                $labelsMois[] = $date->translatedFormat('M Y');
                $labelsMoisDepenses[] = $date->translatedFormat('M Y');
                $moisPaiements = \App\Models\ESBTPPaiement::where('status', 'validé')
                    ->whereNull('deleted_at')
                    ->whereMonth('date_paiement', $date->month)
                    ->whereYear('date_paiement', $date->year)
                    ->when($anneeId, fn($q) => $q->whereHas('inscription', fn($q2) => $q2->where('annee_universitaire_id', $anneeId)))
                    ->when($filiereId, fn($q) => $q->whereHas('inscription.classe', fn($q2) => $q2->where('filiere_id', $filiereId)))
                    ->when($classeId, fn($q) => $q->whereHas('inscription', fn($q2) => $q2->where('classe_id', $classeId)))
                    ->sum('montant');
                $dataEncaissements[] = $moisPaiements;
                $dataDepensesMensuelles[] = 0; // module supprimé
            }
        }

        // Aging buckets — étudiants avec impayés par ancienneté d'inscription
        $agingBuckets = $this->getImpayesAging($anneeId, $filiereId, $classeId);

        // Paiements récents en attente de validation
        $paiementsEnAttente = \App\Models\ESBTPPaiement::with(['inscription.etudiant', 'fraisCategory'])
            ->where('status', 'en_attente')
            ->whereNull('deleted_at')
            ->when($anneeId, fn($q) => $q->whereHas('inscription', fn($q2) => $q2->where('annee_universitaire_id', $anneeId)))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('esbtp.comptabilite.dashboard', compact(
            'totalDue', 'totalPaid', 'totalOverdue',
            'countPaid', 'countPartiallyPaid', 'countOverdue', 'countDue',
            'annees', 'filieres', 'classes', 'annee', 'anneeActive',
            'labelsMois', 'dataEncaissements',
            'statsDepenses',
            'labelsMoisDepenses', 'dataDepensesMensuelles',
            'agingBuckets', 'paiementsEnAttente'
        ));
    }

    /**
     * Endpoint AJAX pour les données du dashboard (filtres dynamiques sans reload)
     */
    public function dashboardData(Request $request)
    {
        $anneeId   = $request->get('annee');
        $filiereId = $request->get('filiere');
        $classeId  = $request->get('classe');

        // Résoudre l'année (défaut = courante)
        if ($anneeId) {
            $annee = \App\Models\ESBTPAnneeUniversitaire::find($anneeId);
        } else {
            $annee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first()
                ?? \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->first();
            if ($annee) $anneeId = $annee->id;
        }

        // KPIs paiements
        $paiementsQuery = \App\Models\ESBTPPaiement::query()->whereNull('deleted_at');
        if ($anneeId)   $paiementsQuery->whereHas('inscription', fn($q) => $q->where('annee_universitaire_id', $anneeId));
        if ($filiereId) $paiementsQuery->whereHas('inscription.classe', fn($q) => $q->where('filiere_id', $filiereId));
        if ($classeId)  $paiementsQuery->whereHas('inscription', fn($q) => $q->where('classe_id', $classeId));

        $totalPaid = (clone $paiementsQuery)->where('status', 'validé')->sum('montant');

        ['totalDue' => $totalDue, 'countDue' => $countDue] = $this->calculerTotalDu($anneeId, $filiereId, $classeId);
        $totalOverdue = max(0, $totalDue - $totalPaid);
        $countPaid    = (clone $paiementsQuery)->where('status', 'validé')->count();
        $countPartiallyPaid = (clone $paiementsQuery)->where('status', 'en_attente')->count();
        $countOverdue = \App\Models\ESBTPInscription::query()
            ->when($anneeId,   fn($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when($filiereId, fn($q) => $q->whereHas('classe', fn($q2) => $q2->where('filiere_id', $filiereId)))
            ->when($classeId,  fn($q) => $q->where('classe_id', $classeId))
            ->whereIn('status', ['active', 'en_attente', 'validée'])
            ->count();

        // Graphique mensuel
        $labelsMois = [];
        $dataEncaissements = [];
        if ($annee && $annee->start_date) {
            $debut = \Carbon\Carbon::parse($annee->start_date);
            $fin   = \Carbon\Carbon::parse($annee->end_date ?? now());
            for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
                $labelsMois[] = $date->translatedFormat('M Y');
                $dataEncaissements[] = (float) \App\Models\ESBTPPaiement::where('status', 'validé')
                    ->whereNull('deleted_at')
                    ->whereMonth('date_paiement', $date->month)
                    ->whereYear('date_paiement', $date->year)
                    ->when($anneeId,   fn($q) => $q->whereHas('inscription', fn($q2) => $q2->where('annee_universitaire_id', $anneeId)))
                    ->when($filiereId, fn($q) => $q->whereHas('inscription.classe', fn($q2) => $q2->where('filiere_id', $filiereId)))
                    ->when($classeId,  fn($q) => $q->whereHas('inscription', fn($q2) => $q2->where('classe_id', $classeId)))
                    ->sum('montant');
            }
        }

        // Aging
        $agingBuckets = $this->getImpayesAging($anneeId, $filiereId, $classeId);

        // Paiements en attente (10 derniers)
        $paiementsEnAttente = \App\Models\ESBTPPaiement::with(['inscription.etudiant', 'fraisCategory'])
            ->where('status', 'en_attente')
            ->whereNull('deleted_at')
            ->when($anneeId, fn($q) => $q->whereHas('inscription', fn($q2) => $q2->where('annee_universitaire_id', $anneeId)))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'nom'       => $p->inscription->etudiant->nom ?? 'N/A',
                    'prenoms'   => $p->inscription->etudiant->prenoms ?? '',
                    'categorie' => $p->fraisCategory->name ?? $p->motif ?? '—',
                    'montant'   => (float) $p->montant,
                    'date'      => \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y'),
                    'url'       => route('esbtp.paiements.show', $p->id),
                ];
            });

        return response()->json([
            'totalDue'          => (float) $totalDue,
            'totalPaid'         => (float) $totalPaid,
            'totalOverdue'      => (float) $totalOverdue,
            'countPaid'         => $countPaid,
            'countPartiallyPaid'=> $countPartiallyPaid,
            'countOverdue'      => $countOverdue,
            'countDue'          => $countDue,
            'labelsMois'        => $labelsMois,
            'dataEncaissements' => $dataEncaissements,
            'agingBuckets'      => $agingBuckets,
            'paiementsEnAttente'=> $paiementsEnAttente,
            'anneeLabel'        => $annee ? ($annee->name ?? $annee->libelle ?? '') : '',
        ]);
    }

    /**
     * Calcule les impayés par ancienneté (aging buckets)
     */
    /**
     * Calcule le total dû de manière cohérente avec suivi-catégories :
     * itère inscriptions actives × catégories, avec fallback config/default pour frais obligatoires.
     */
    private function calculerTotalDu($anneeId, $filiereId, $classeId): array
    {
        $inscriptions = \App\Models\ESBTPInscription::whereIn('status', ['active', 'en_attente', 'validée'])
            ->when($anneeId,   fn($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when($filiereId, fn($q) => $q->whereHas('classe', fn($q2) => $q2->where('filiere_id', $filiereId)))
            ->when($classeId,  fn($q) => $q->where('classe_id', $classeId))
            ->get(['id', 'filiere_id', 'niveau_id', 'affectation_status']);

        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        $subscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->get()
            ->groupBy('inscription_id');

        $configurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $categories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $totalDue = 0;
        $countDue = 0;

        foreach ($inscriptions as $inscription) {
            $inscriptionSubs = $subscriptions->get($inscription->id, collect());
            foreach ($categories as $category) {
                $sub = $inscriptionSubs->where('frais_category_id', $category->id)->first();
                if ($category->is_mandatory) {
                    if ($sub) {
                        $montant = $sub->amount;
                    } else {
                        $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                        $config = $configurations->get($configKey, collect())->first();
                        $montant = $config
                            ? $config->getMontantByStatus($inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
                            : $category->default_amount;
                    }
                } else {
                    $montant = $sub ? $sub->amount : 0;
                }
                if ($montant > 0) {
                    $totalDue += $montant;
                    $countDue++;
                }
            }
        }

        return ['totalDue' => $totalDue, 'countDue' => $countDue];
    }

    /**
     * Calcule le total dû pour UNE inscription (catégories/configs pré-chargées).
     * Même logique que calculerTotalDu() mais par inscription individuelle — évite le N+1.
     */
    private function calculerTotalDuParInscription($inscription, $categories, $subscriptionsByInscription, $configurations): float
    {
        $inscriptionSubs = $subscriptionsByInscription->get($inscription->id, collect());
        $totalDu = 0;

        foreach ($categories as $category) {
            $sub = $inscriptionSubs->where('frais_category_id', $category->id)->first();
            if ($category->is_mandatory) {
                if ($sub) {
                    $montant = $sub->amount;
                } else {
                    $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                    $config = $configurations->get($configKey, collect())->first();
                    $montant = $config
                        ? $config->getMontantByStatus($inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
                        : $category->default_amount;
                }
            } else {
                $montant = $sub ? $sub->amount : 0;
            }
            if ($montant > 0) {
                $totalDu += $montant;
            }
        }

        return (float) $totalDu;
    }

    private function getImpayesAging($anneeId = null, $filiereId = null, $classeId = null): array
    {
        // Pré-charger catégories et configurations pour éviter le N+1
        $allCategories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        $inscriptions = \App\Models\ESBTPInscription::with([
            'etudiant',
            'fraisSubscriptions',
            'paiements' => fn($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ])
            ->when($anneeId,   fn($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when($filiereId, fn($q) => $q->whereHas('classe', fn($q2) => $q2->where('filiere_id', $filiereId)))
            ->when($classeId,  fn($q) => $q->where('classe_id', $classeId))
            ->get();

        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        $allSubscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->get()
            ->groupBy('inscription_id');

        $allConfigurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $allCategories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $buckets = [
            '0-30'  => ['count' => 0, 'amount' => 0, 'students' => []],
            '31-60' => ['count' => 0, 'amount' => 0, 'students' => []],
            '61-90' => ['count' => 0, 'amount' => 0, 'students' => []],
            '90+'   => ['count' => 0, 'amount' => 0, 'students' => []],
        ];

        foreach ($inscriptions as $inscription) {
            // Calcul totalDu aligné avec suivi-catégories (fix bug fraisSubscriptions->sum)
            $totalDu      = $this->calculerTotalDuParInscription($inscription, $allCategories, $allSubscriptions, $allConfigurations);
            $totalPaye    = $inscription->paiements->where('status', 'validé')->sum('montant');
            $soldeRestant = max(0, $totalDu - $totalPaye);

            if ($soldeRestant <= 0) continue;

            // Ancienneté basée sur l'ÉCHÉANCE de paiement, pas la date d'inscription.
            // On prend le délai minimum parmi les catégories souscrites (fallback 30j).
            $inscriptionSubs = $allSubscriptions->get($inscription->id, collect());
            $minDeadlineDays = 30;
            if ($inscriptionSubs->isNotEmpty()) {
                $deadlineDays = $inscriptionSubs->map(function ($sub) use ($allCategories) {
                    $cat = $allCategories->firstWhere('id', $sub->frais_category_id);
                    return $cat ? ($cat->payment_deadline_days ?? 30) : 30;
                })->min();
                if ($deadlineDays !== null) {
                    $minDeadlineDays = (int) $deadlineDays;
                }
            }

            // Date à partir de laquelle le paiement est en retard
            $dateEcheance = $inscription->created_at->copy()->addDays($minDeadlineDays);

            // Étudiant pas encore en retard → ne figure pas dans l'aging
            if ($dateEcheance->isFuture()) continue;

            // Jours de retard depuis l'échéance
            $joursRetard = (int) $dateEcheance->diffInDays(now());

            $etudiantData = [
                'id'             => $inscription->etudiant->id ?? null,
                'inscription_id' => $inscription->id,
                'nom'            => $inscription->etudiant->nom_complet ?? 'N/A',
                'solde'          => $soldeRestant,
                'jours'          => $joursRetard,
            ];

            if ($joursRetard <= 30) {
                $buckets['0-30']['count']++;
                $buckets['0-30']['amount'] += $soldeRestant;
                if (count($buckets['0-30']['students']) < 5) $buckets['0-30']['students'][] = $etudiantData;
            } elseif ($joursRetard <= 60) {
                $buckets['31-60']['count']++;
                $buckets['31-60']['amount'] += $soldeRestant;
                if (count($buckets['31-60']['students']) < 5) $buckets['31-60']['students'][] = $etudiantData;
            } elseif ($joursRetard <= 90) {
                $buckets['61-90']['count']++;
                $buckets['61-90']['amount'] += $soldeRestant;
                if (count($buckets['61-90']['students']) < 5) $buckets['61-90']['students'][] = $etudiantData;
            } else {
                $buckets['90+']['count']++;
                $buckets['90+']['amount'] += $soldeRestant;
                if (count($buckets['90+']['students']) < 5) $buckets['90+']['students'][] = $etudiantData;
            }
        }

        return $buckets;
    }

    private function calculerTendance($donnees) { /* Implementation */ return ['slope' => 0.05, 'intercept' => 100000]; }
    private function projetterValeur($tendance, $periode) { /* Implementation */ return $tendance['intercept'] + ($tendance['slope'] * $periode * 30000); }
    private function getFacteurSaisonnier($mois) { /* Implementation */ return 1.0 + (sin($mois * pi() / 6) * 0.1); }
    private function predictionIA($date, $histRecettes, $histDepenses) { /* Implementation */ return ['facteur_recettes' => 1.05, 'facteur_depenses' => 1.02]; }
    private function calculerNiveauConfiance($periode, $includeIA) { /* Implementation */ return max(0.95 - ($periode * 0.05), 0.6); }
    private function determinerScenario($cashFlow) { return $cashFlow > 0 ? 'positif' : 'negatif'; }
    private function genererRecommandationsCashFlow($projections) { /* Implementation */ return []; }
    private function identifierRisquesCashFlow($projections) { /* Implementation */ return []; }
    private function identifierOpportunites($projections) { /* Implementation */ return []; }
    private function calculerFiabiliteGlobale($projections) { /* Implementation */ return 0.85; }
    private function calculerSeuilsAnomalies($paiements, $depenses, $personnalises) { /* Implementation */ return ['montant_max' => 1000000, 'z_score' => 2.5]; }
    private function detecterAnomaliesMontants($paiements, $depenses, $seuils) { /* Implementation */ return []; }
    private function detecterAnomaliesTemporelles($paiements, $depenses) { /* Implementation */ return []; }
    private function detecterAnomaliesFrequence($paiements, $depenses) { /* Implementation */ return []; }
    private function detecterPatternsSuspects($paiements, $depenses) { /* Implementation */ return []; }
    private function detecterAnomaliesCategories($paiements, $depenses) { /* Implementation */ return []; }
    private function evaluerNiveauRisqueGlobal($anomalies) { /* Implementation */ return 'moyen'; }
    private function calculerScoreConfiance($anomalies) { /* Implementation */ return 0.78; }
    private function genererRecommandationsAnomalies($anomalies) { /* Implementation */ return []; }
    private function identifierActionsImmediates($anomalies) { /* Implementation */ return []; }
    private function getDonneesHistoriques($periode) { /* Implementation */ return ['recettes' => [], 'depenses' => []]; }
    private function analyserTendance($donnees) { /* Implementation */ return ['direction' => 'croissante', 'force' => 0.75]; }
    private function analyserTendancesParFiliere($donnees) { /* Implementation */ return []; }
    private function analyserTendancesParCategorie($donnees) { /* Implementation */ return []; }
    private function analyserTendanceTauxRecouvrement($donnees) { /* Implementation */ return []; }
    private function analyserCycleSaisonnier($donnees) { /* Implementation */ return []; }
    private function genererPredictionsTendances($tendances, $periode) { /* Implementation */ return []; }
    private function genererInsightsTendances($tendances) { /* Implementation */ return []; }
    private function identifierAlertesTondances($tendances) { /* Implementation */ return []; }
    private function identifierOpportunitesAmelioration($tendances) { /* Implementation */ return []; }
    private function getDonneesML($periode) { /* Implementation */ return []; }
    private function modelRegressionLineaire($donnees) { /* Implementation */ return ['r2' => 0.85, 'predictions' => []]; }
    private function modelMoyennesMobiles($donnees) { /* Implementation */ return ['accuracy' => 0.78, 'predictions' => []]; }
    private function modelDecompositionSaisonniere($donnees) { /* Implementation */ return ['seasonal_strength' => 0.65, 'predictions' => []]; }
    private function modelReseauxNeurones($donnees) { /* Implementation */ return ['loss' => 0.15, 'predictions' => []]; }
    private function combinerPredictions($modeles, $periode) { /* Implementation */ return []; }
    private function evaluerConfianceModeles($modeles) { /* Implementation */ return []; }
    private function identifierFacteursInfluence($donnees) { /* Implementation */ return []; }
    private function genererScenario($predictions, $type) { /* Implementation */ return []; }
    private function genererRecommandationsStrategiques($predictions) { /* Implementation */ return []; }
    /**
     * Calcule le cash flow en temps réel
     */
    private function calculerCashFlowTempsReel($anneeActive): array
    {
        $moisActuel = Carbon::now();
        $cashFlowMensuel = [];

        for ($i = -6; $i <= 0; $i++) {
            $mois = $moisActuel->copy()->addMonths($i);

            $recettes = ESBTPPaiement::where('annee_universitaire_id', $anneeActive->id)
                ->whereYear('date_paiement', $mois->year)
                ->whereMonth('date_paiement', $mois->month)
                ->where('status', 'validé')
                ->sum('montant');

            $depenses = ESBTPDepense::whereYear('date_depense', $mois->year)
                ->whereMonth('date_depense', $mois->month)
                ->where('statut', 'validée')
                ->sum('montant');

            $cashFlowMensuel[] = [
                'mois' => $mois->format('Y-m'),
                'mois_nom' => $mois->format('M Y'),
                'recettes' => $recettes,
                'depenses' => $depenses,
                'cash_flow' => $recettes - $depenses
            ];
        }

        return $cashFlowMensuel;
    }

    /**
     * Calcule la performance des relances en temps réel
     */
    private function calculerPerformanceRelancesTempsReel(): array
    {
        $totalRelances = \DB::table('esbtp_relances')->count();
        $relancesReussies = \DB::table('esbtp_relances')
            ->join('esbtp_paiements', 'esbtp_relances.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_paiements.date_paiement', '>', \DB::raw('esbtp_relances.date_envoi'))
            ->count();

        $tauxReussite = $totalRelances > 0 ? ($relancesReussies / $totalRelances) * 100 : 0;

        return [
            'total_relances' => $totalRelances,
            'relances_reussies' => $relancesReussies,
            'taux_reussite' => round($tauxReussite, 2),
            'derniere_relance' => \DB::table('esbtp_relances')->max('date_envoi')
        ];
    }

    /**
     * Calcule le ROI des investissements en temps réel
     */
    private function calculerROIInvestissementsTempsReel($anneeActive): array
    {
        $investissements = ESBTPDepense::where('categorie', 'Investissement')
            ->where('date_depense', '>=', $anneeActive->date_debut)
            ->sum('montant');

        $recettesGenerees = ESBTPPaiement::where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'validé')
            ->sum('montant');

        $roi = $investissements > 0 ? (($recettesGenerees - $investissements) / $investissements) * 100 : 0;

        return [
            'investissements_total' => $investissements,
            'recettes_generees' => $recettesGenerees,
            'roi_pourcentage' => round($roi, 2),
            'benefice_net' => $recettesGenerees - $investissements
        ];
    }

    /**
     * Génère des alertes basiques
     */
    private function genererAlertesBasiques(): array
    {
        $alertes = [];
        $kpis = $this->comptabiliteService->getKPIsDashboard();

        if (($kpis['taux_recouvrement'] ?? 0) < 70) {
            $alertes[] = [
                'niveau' => 'warning',
                'titre' => 'Taux de recouvrement bas',
                'message' => 'Le taux de recouvrement est inférieur à 70%',
                'action_recommandee' => 'Intensifier les relances'
            ];
        }

        return $alertes;
    }

    /**
     * Obtient des données minimales en cas d'erreur
     */
    private function getDonneesMinimales(): array
    {
        return [
            'recettes_mensuelles' => [],
            'depenses_mensuelles' => [],
            'repartition_filieres' => [],
            'derniere_mise_a_jour' => now()->toISOString()
        ];
    }

    /**
     * Prépare les données pour le graphique d'évolution
     */
    private function getDonneesGraphiqueEvolution(): array
    {
        $derniersMois = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mois = Carbon::now()->subMonths($i);

            $recettes = ESBTPPaiement::whereYear('date_paiement', $mois->year)
                ->whereMonth('date_paiement', $mois->month)
                ->where('status', 'validé')
                ->sum('montant');

            $depenses = ESBTPDepense::whereYear('date_depense', $mois->year)
                ->whereMonth('date_depense', $mois->month)
                ->where('statut', 'validée')
                ->sum('montant');

            $derniersMois->push([
                'mois' => $mois->format('M Y'),
                'recettes' => $recettes,
                'depenses' => $depenses
            ]);
        }

        return $derniersMois->toArray();
    }

    /**
     * Données pour la répartition par filières
     */
    private function getDonneesRepartitionFilieres(): array
    {
        return ESBTPInscription::join('esbtp_filieres', 'esbtp_inscriptions.filiere_id', '=', 'esbtp_filieres.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->selectRaw('esbtp_filieres.libelle as filiere, SUM(esbtp_paiements.montant) as total')
            ->where('esbtp_paiements.status', 'validé')
            ->groupBy('esbtp_filieres.id', 'esbtp_filieres.libelle')
            ->get()
            ->toArray();
    }

    /**
     * Données de comparaison avec les objectifs
     */
    private function getDonneesComparaisonObjectifs(): array
    {
        // Implémentation des objectifs vs réalisations
        return [];
    }

    /**
     * Données des tendances de paiements
     */
    private function getDonneesTendancesPaiements(): array
    {
        return ESBTPPaiement::selectRaw('DATE(date_paiement) as date, COUNT(*) as nombre, SUM(montant) as total')
            ->where('date_paiement', '>=', Carbon::now()->subDays(30))
            ->where('status', 'validé')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Données de performance mensuelle
     */
    private function getDonneesPerformanceMensuelle(): array
    {
        $anneeActive = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeActive) {
            return [];
        }

        return ESBTPPaiement::selectRaw('MONTH(date_paiement) as mois, SUM(montant) as total, COUNT(*) as nombre')
            ->where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'validé')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->toArray();
    }

}

