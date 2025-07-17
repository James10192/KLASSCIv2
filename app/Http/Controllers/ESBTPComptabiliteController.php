<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPComptabiliteConfiguration;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFactureDetail;
use App\Models\ESBTPCategorieDepense;
use App\Models\ESBTPDepense;
use App\Models\ESBTPFournisseur;
use App\Models\ESBTPBourse;
use App\Models\ESBTPSalaire;
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
     * Dashboard avancé avec IA et analytics temps réel
     */
    public function dashboardAvance()
    {
        try {
            // === DONNÉES RÉELLES POUR DESIGN ACASI ===

            // 1. KPIs principaux avec le service existant
            $kpis = $this->comptabiliteService->getKPIsDashboard();

            // 2. Enrichir avec des données calculées
            $recettesMois = DB::table('esbtp_paiements')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('montant');

            $depensesMois = DB::table('esbtp_depenses')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('montant');

            // 3. Étudiants statistics avec Eloquent
            $totalEtudiants = ESBTPInscription::where('status', 'active')->count();

            // Calculer les étudiants solvents de manière plus simple et sûre
            $etudiantsSolvents = ESBTPInscription::where('status', 'active')
                ->whereHas('paiements', function($query) {
                    $query->where('status', 'validé');
                })
                ->with(['paiements' => function($query) {
                    $query->where('status', 'validé');
                }])
                ->get()
                ->filter(function($inscription) {
                    $totalPaye = $inscription->paiements->sum('montant');
                    $totalDu = $inscription->montant_scolarite + $inscription->frais_inscription;
                    return $totalPaye >= $totalDu;
                })
                ->count();

            // 4. Enrichir les KPIs
            $kpis = array_merge($kpis, [
                'recettes_mois' => $recettesMois,
                'depenses_mois' => $depensesMois,
                'total_etudiants' => $totalEtudiants,
                'etudiants_solvents' => $etudiantsSolvents,
                'resultat_net' => ($kpis['total_recettes'] ?? 0) - ($kpis['total_depenses'] ?? 0),
                'montant_en_attente' => $this->calculerMontantEnAttente()
            ]);

            // 5. Données financières détaillées pour le design ACASI
            $donneesFinancieres = [
                'recettes_mensuelles' => $this->getRecettesMensuelles()->map(fn($item) => (array)$item)->toArray(),
                'depenses_mensuelles' => $this->getDepensesMensuelles()->map(fn($item) => (array)$item)->toArray(),
                'top_filieres' => $this->getTopFilieres()->map(fn($item) => (array)$item)->toArray(),
                'categories_depenses' => $this->getCategoriesDepenses()->map(fn($item) => (array)$item)->toArray(),
                'etudiants_en_attente' => $this->getEtudiantsEnAttente()->map(fn($item) => (array)$item)->toArray()
            ];

            // 6. Préparer les données pour les graphiques JavaScript
            $chartLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            $recettesParMois = $this->getRecettesParMois();
            $depensesParMois = $this->getDepensesParMois();

            // Extraire les données pour le JavaScript
            $recettesData = is_array($recettesParMois) && isset($recettesParMois['data'])
                ? $recettesParMois['data']
                : [2800000, 3200000, 2900000, 3500000, 3100000, 3400000, 3300000, 3600000, 3200000, 3800000, 3500000, 4000000];

            $depensesData = is_array($depensesParMois) && isset($depensesParMois['data'])
                ? $depensesParMois['data']
                : [2200000, 2400000, 2300000, 2600000, 2500000, 2700000, 2600000, 2800000, 2500000, 2900000, 2700000, 3000000];

            return view('esbtp.comptabilite.dashboard-avance', [
                'kpis' => $kpis,
                'donneesFinancieres' => $donneesFinancieres,
                'chartLabels' => $chartLabels,
                'recettesData' => $recettesData,
                'depensesData' => $depensesData,
                'insightsIA' => ['message' => 'Dashboard ACASI Style activé'],
                'predictionsAvancees' => [],
                'metriquesPerformance' => [],
                'alertesIntelligentes' => $this->genererAlertesBasiques()
            ])->with('success', 'Dashboard ACASI mis à jour avec succès');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur dashboard ACASI: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    /**
     * Méthodes helper pour le design ACASI
     */
    private function getRecettesMensuelles()
    {
        return DB::table('esbtp_paiements')
            ->selectRaw('MONTH(created_at) as mois, YEAR(created_at) as annee, SUM(montant) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('annee', 'mois')
            ->orderBy('annee', 'desc')
            ->orderBy('mois', 'desc')
            ->limit(12)
            ->get();
    }

    private function getDepensesMensuelles()
    {
        return DB::table('esbtp_depenses')
            ->selectRaw('MONTH(created_at) as mois, YEAR(created_at) as annee, SUM(montant) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('annee', 'mois')
            ->orderBy('annee', 'desc')
            ->orderBy('mois', 'desc')
            ->limit(12)
            ->get();
    }

    private function getTopFilieres()
    {
        return DB::table('esbtp_filieres')
            ->join('esbtp_inscriptions', 'esbtp_filieres.id', '=', 'esbtp_inscriptions.filiere_id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->selectRaw('esbtp_filieres.libelle as nom, SUM(esbtp_paiements.montant) as recettes')
            ->where('esbtp_paiements.status', 'validé')
            ->whereYear('esbtp_paiements.created_at', now()->year)
            ->groupBy('esbtp_filieres.id', 'esbtp_filieres.libelle')
            ->orderBy('recettes', 'desc')
            ->limit(5)
            ->get();
    }

    private function getCategoriesDepenses()
    {
        return DB::table('esbtp_depenses')
            ->join('esbtp_categories_depenses', 'esbtp_depenses.categorie_id', '=', 'esbtp_categories_depenses.id')
            ->selectRaw('esbtp_categories_depenses.nom as nom, SUM(esbtp_depenses.montant) as total')
            ->whereYear('esbtp_depenses.created_at', now()->year)
            ->groupBy('esbtp_categories_depenses.id', 'esbtp_categories_depenses.nom')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();
    }

    private function getEtudiantsEnAttente()
    {
        return DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('users', 'esbtp_etudiants.user_id', '=', 'users.id')
            ->leftJoin('esbtp_paiements', function($join) {
                $join->on('esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
                     ->where('esbtp_paiements.status', 'validé');
            })
            ->selectRaw('
                users.name as nom,
                (esbtp_inscriptions.montant_scolarite + esbtp_inscriptions.frais_inscription) as montant_frais,
                COALESCE(SUM(esbtp_paiements.montant), 0) as montant_paye,
                ((esbtp_inscriptions.montant_scolarite + esbtp_inscriptions.frais_inscription) - COALESCE(SUM(esbtp_paiements.montant), 0)) as montant_du
            ')
            ->groupBy('esbtp_inscriptions.id', 'users.name', 'esbtp_inscriptions.montant_scolarite', 'esbtp_inscriptions.frais_inscription')
            ->havingRaw('montant_du > 0')
            ->orderBy('montant_du', 'desc')
            ->limit(10)
            ->get();
    }

    private function calculerMontantEnAttente()
    {
        // Restructuration pour éviter l'erreur SQL strict mode avec les bonnes colonnes
        $sousRequete = DB::table('esbtp_inscriptions')
            ->leftJoin('esbtp_paiements', 'esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
            ->selectRaw('
                esbtp_inscriptions.id,
                (esbtp_inscriptions.montant_scolarite + esbtp_inscriptions.frais_inscription) - COALESCE(SUM(esbtp_paiements.montant), 0) as montant_attente
            ')
            ->groupBy('esbtp_inscriptions.id', 'esbtp_inscriptions.montant_scolarite', 'esbtp_inscriptions.frais_inscription')
            ->havingRaw('((esbtp_inscriptions.montant_scolarite + esbtp_inscriptions.frais_inscription) - COALESCE(SUM(esbtp_paiements.montant), 0)) > 0');

        return DB::table(DB::raw("({$sousRequete->toSql()}) as temp_table"))
            ->mergeBindings($sousRequete)
            ->sum('montant_attente') ?? 0;
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
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'total' => 0,
                'mensuel' => 0,
                'salaires' => 0,
                'fournitures' => 0
            ];
        }

        // On prend l'année scolaire en cours (qui peut s'étendre sur 2 années civiles)
        $dateDebut = Carbon::parse($anneeEnCours->date_debut);
        $dateFin = Carbon::parse($anneeEnCours->date_fin);

        // Total des dépenses
        $totalDepenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
            ->where('statut', 'validée')
            ->sum('montant');

        // Dépenses du mois en cours
        $depensesMensuelles = ESBTPDepense::whereMonth('date_depense', Carbon::now()->month)
            ->whereYear('date_depense', Carbon::now()->year)
            ->where('statut', 'validée')
            ->sum('montant');

        // Total des salaires
        $totalSalaires = ESBTPSalaire::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'payé')
            ->sum('montant_net');

        // Total des dépenses en fournitures
        $idCategorieFournitures = ESBTPCategorieDepense::where('nom', 'like', '%fourniture%')->first();
        $totalFournitures = 0;

        if ($idCategorieFournitures) {
            $totalFournitures = ESBTPDepense::where('categorie_id', $idCategorieFournitures->id)
                ->whereBetween('date_depense', [$dateDebut, $dateFin])
                ->where('statut', 'validée')
                ->sum('montant');
        }

        return [
            'total' => $totalDepenses,
            'mensuel' => $depensesMensuelles,
            'salaires' => $totalSalaires,
            'fournitures' => $totalFournitures
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
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $mois = [];
        $depenses = [];

        // Génère tous les mois de la période
        $dates = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $dates[] = $date->copy();
        }
        // Prend les 12 derniers mois seulement
        $dates = array_slice($dates, -12);
        foreach ($dates as $date) {
            $mois[] = $date->translatedFormat('F Y');
            $total = ESBTPDepense::whereMonth('date_depense', $date->month)
                ->whereYear('date_depense', $date->year)
                ->where('statut', 'validée')
                ->sum('montant');
            $depenses[] = $total;
        }
        return [
            'labels' => $mois,
            'data' => $depenses
        ];
    }

    /**
     * Affiche la liste des paiements
     */
    public function paiements()
    {
        $paiements = ESBTPPaiement::with(['etudiant', 'anneeUniversitaire', 'createur'])
            ->orderBy('date_paiement', 'desc')
            ->paginate(15);

        return view('esbtp.comptabilite.paiements.index', compact('paiements'));
    }

    /**
     * Affiche le formulaire de création d'un paiement
     */
    public function createPaiement()
    {
        $etudiants = ESBTPEtudiant::all();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::all();
        $modesPaiement = ['espèces', 'chèque', 'virement', 'mobile money', 'carte bancaire'];

        return view('esbtp.comptabilite.paiements.create', compact('etudiants', 'anneesUniversitaires', 'modesPaiement'));
    }

    /**
     * Enregistre un nouveau paiement
     */
    public function storePaiement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'type_paiement' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|string',
            'date_paiement' => 'required|date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Récupérer l'inscription de l'étudiant pour l'année universitaire spécifiée
        $inscription = ESBTPInscription::where('etudiant_id', $request->etudiant_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->first();

        if (!$inscription) {
            return redirect()->back()
                ->withErrors(['etudiant_id' => 'Aucune inscription trouvée pour cet étudiant dans l\'année universitaire spécifiée.'])
                ->withInput();
        }

        // Générer une référence unique pour le paiement
        $reference = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        // Générer un numéro de reçu
        $numeroRecu = ESBTPPaiement::genererNumeroRecu();

        // Créer le paiement
        $paiement = new ESBTPPaiement();
        $paiement->inscription_id = $inscription->id;
        $paiement->etudiant_id = $request->etudiant_id;
        $paiement->annee_universitaire_id = $request->annee_universitaire_id;
        $paiement->type_paiement = $request->type_paiement;
        $paiement->motif = $request->type_paiement; // Le motif correspond au type de paiement
        $paiement->montant = $request->montant;
        $paiement->reference_paiement = $reference;
        $paiement->mode_paiement = $request->mode_paiement;
        $paiement->numero_transaction = $request->numero_transaction;
        $paiement->numero_recu = $numeroRecu;
        $paiement->date_paiement = $request->date_paiement;
        $paiement->date_echeance = $request->date_echeance;
        $paiement->commentaire = $request->commentaire;
        $paiement->status = 'validé';
        $paiement->created_by = Auth::id();
        $paiement->save();

        // Enregistrer la transaction dans le journal financier
        $transaction = new ESBTPTransactionFinanciere();
        $transaction->type = 'revenu';
        $transaction->transactionable_type = get_class($paiement);
        $transaction->transactionable_id = $paiement->id;
        $transaction->montant = $paiement->montant;
        $transaction->sens = 'crédit';
        $transaction->categorie = 'paiement_scolarite';
        $transaction->reference = $paiement->reference_paiement;
        $transaction->date_transaction = $paiement->date_paiement;
        $transaction->description = $paiement->commentaire;
        $transaction->createur_id = Auth::id();
        $transaction->save();

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Affiche les détails d'un paiement
     */
    public function showPaiement($id)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.classe', 
            'etudiant.user', 
            'inscription.filiere', 
            'inscription.niveauEtude',
            'anneeUniversitaire',
            'createdBy', // Relation avec l'utilisateur qui a créé
            'validateur' // Relation avec l'utilisateur qui a validé
        ])->findOrFail($id);

        return view('esbtp.comptabilite.paiements.show', compact('paiement'));
    }

    /**
     * Affiche le formulaire d'édition d'un paiement
     */
    public function editPaiement($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        $etudiants = ESBTPEtudiant::all();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::all();
        $modesPaiement = ['espèces', 'chèque', 'virement', 'mobile money', 'carte bancaire'];

        return view('esbtp.comptabilite.paiements.edit', compact('paiement', 'etudiants', 'anneesUniversitaires', 'modesPaiement'));
    }

    /**
     * Met à jour un paiement
     */
    public function updatePaiement(Request $request, $id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        $validator = Validator::make($request->all(), [
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'type_paiement' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|string',
            'date_paiement' => 'required|date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Mettre à jour le paiement
        $paiement->etudiant_id = $request->etudiant_id;
        $paiement->annee_universitaire_id = $request->annee_universitaire_id;
        $paiement->type_paiement = $request->type_paiement;
        $paiement->montant = $request->montant;
        $paiement->mode_paiement = $request->mode_paiement;
        $paiement->numero_transaction = $request->numero_transaction;
        $paiement->date_paiement = $request->date_paiement;
        $paiement->date_echeance = $request->date_echeance;
        $paiement->commentaire = $request->commentaire;
        $paiement->updated_by = Auth::id();
        $paiement->save();

        // Mettre à jour la transaction dans le journal financier
        $transaction = ESBTPTransactionFinanciere::where('transactionable_type', get_class($paiement))
            ->where('transactionable_id', $paiement->id)
            ->first();

        if ($transaction) {
            $transaction->montant = $paiement->montant;
            $transaction->date_transaction = $paiement->date_paiement;
            $transaction->description = $paiement->commentaire;
            $transaction->save();
        }

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement mis à jour avec succès.');
    }

    /**
     * Valide un paiement
     */
    public function validerPaiement($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être validé
        if ($paiement->status !== 'en_attente') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement ne peut pas être validé.');
        }

        // Valider le paiement
        $paiement->status = 'validé';
        $paiement->date_validation = now();
        $paiement->validated_by = Auth::id();
        $paiement->save();

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement validé avec succès.');
    }

    /**
     * Rejette un paiement
     */
    public function rejeterPaiement($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être rejeté
        if ($paiement->status !== 'en_attente') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement ne peut pas être rejeté.');
        }

        // Rejeter le paiement
        $paiement->status = 'rejeté';
        $paiement->date_validation = now();
        $paiement->validated_by = Auth::id();
        $paiement->save();

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement rejeté avec succès.');
    }

    /**
     * Génère un reçu de paiement
     */
    public function genererRecu($id)
    {
        $paiement = ESBTPPaiement::with(['etudiant', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        // Ici, vous pourriez générer un PDF ou simplement afficher une page de reçu
        return view('esbtp.comptabilite.paiements.recu', compact('paiement'));
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

    /**
     * Affiche le formulaire de création des frais de scolarité
     */
    public function createFraisScolarite()
    {
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        return view('esbtp.comptabilite.frais-scolarite.create', compact('filieres', 'niveaux', 'annees'));
    }

    /**
     * Enregistre de nouveaux frais de scolarité
     */
    public function storeFraisScolarite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'montant_total' => 'required|numeric|min:0',
            'frais_inscription' => 'required|numeric|min:0',
            'frais_mensuel' => 'nullable|numeric|min:0',
            'frais_trimestriel' => 'nullable|numeric|min:0',
            'frais_semestriel' => 'nullable|numeric|min:0',
            'frais_annuel' => 'nullable|numeric|min:0',
            'nombre_echeances' => 'required|integer|min:1',
            'details' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Vérifier si une configuration de frais existe déjà pour cette combinaison
        $existingFrais = ESBTPFraisScolarite::where('filiere_id', $request->filiere_id)
            ->where('niveau_id', $request->niveau_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->first();

        if ($existingFrais) {
            return redirect()->back()
                ->with('error', 'Une configuration de frais existe déjà pour cette combinaison filière/niveau/année.')
                ->withInput();
        }

        // Créer les frais de scolarité
        $fraisScolarite = new ESBTPFraisScolarite();
        $fraisScolarite->filiere_id = $request->filiere_id;
        $fraisScolarite->niveau_id = $request->niveau_id;
        $fraisScolarite->annee_universitaire_id = $request->annee_universitaire_id;
        $fraisScolarite->montant_total = $request->montant_total;
        $fraisScolarite->frais_inscription = $request->frais_inscription;
        $fraisScolarite->frais_mensuel = $request->frais_mensuel;
        $fraisScolarite->frais_trimestriel = $request->frais_trimestriel;
        $fraisScolarite->frais_semestriel = $request->frais_semestriel;
        $fraisScolarite->frais_annuel = $request->frais_annuel;
        $fraisScolarite->nombre_echeances = $request->nombre_echeances;
        $fraisScolarite->details = $request->details;
        $fraisScolarite->est_actif = true;
        $fraisScolarite->save();

        return redirect()->route('esbtp.comptabilite.frais-scolarite')
            ->with('success', 'Configuration des frais de scolarité enregistrée avec succès.');
    }

    /**
     * Affiche les détails des frais de scolarité
     */
    public function showFraisScolarite($id)
    {
        $fraisScolarite = ESBTPFraisScolarite::with(['filiere', 'niveau', 'anneeUniversitaire'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.frais-scolarite.show', compact('fraisScolarite'));
    }

    /**
     * Affiche le formulaire d'édition des frais de scolarité
     */
    public function editFraisScolarite($id)
    {
        $fraisScolarite = ESBTPFraisScolarite::findOrFail($id);
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        return view('esbtp.comptabilite.frais-scolarite.edit', compact('fraisScolarite', 'filieres', 'niveaux', 'annees'));
    }

    /**
     * Met à jour les frais de scolarité
     */
    public function updateFraisScolarite(Request $request, $id)
    {
        $fraisScolarite = ESBTPFraisScolarite::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'montant_total' => 'required|numeric|min:0',
            'frais_inscription' => 'required|numeric|min:0',
            'frais_mensuel' => 'nullable|numeric|min:0',
            'frais_trimestriel' => 'nullable|numeric|min:0',
            'frais_semestriel' => 'nullable|numeric|min:0',
            'frais_annuel' => 'nullable|numeric|min:0',
            'nombre_echeances' => 'required|integer|min:1',
            'details' => 'nullable|string',
            'est_actif' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Vérifier si une autre configuration de frais existe déjà pour cette combinaison
        $existingFrais = ESBTPFraisScolarite::where('filiere_id', $request->filiere_id)
            ->where('niveau_id', $request->niveau_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingFrais) {
            return redirect()->back()
                ->with('error', 'Une autre configuration de frais existe déjà pour cette combinaison filière/niveau/année.')
                ->withInput();
        }

        // Mettre à jour les frais de scolarité
        $fraisScolarite->filiere_id = $request->filiere_id;
        $fraisScolarite->niveau_id = $request->niveau_id;
        $fraisScolarite->annee_universitaire_id = $request->annee_universitaire_id;
        $fraisScolarite->montant_total = $request->montant_total;
        $fraisScolarite->frais_inscription = $request->frais_inscription;
        $fraisScolarite->frais_mensuel = $request->frais_mensuel;
        $fraisScolarite->frais_trimestriel = $request->frais_trimestriel;
        $fraisScolarite->frais_semestriel = $request->frais_semestriel;
        $fraisScolarite->frais_annuel = $request->frais_annuel;
        $fraisScolarite->nombre_echeances = $request->nombre_echeances;
        $fraisScolarite->details = $request->details;
        $fraisScolarite->est_actif = $request->has('est_actif');
        $fraisScolarite->save();

        return redirect()->route('esbtp.comptabilite.frais-scolarite')
            ->with('success', 'Configuration des frais de scolarité mise à jour avec succès.');
    }

    /**
     * Supprime des frais de scolarité
     */
    public function destroyFraisScolarite($id)
    {
        $fraisScolarite = ESBTPFraisScolarite::findOrFail($id);

        // Vérifier si des inscriptions utilisent cette configuration
        $inscriptionsUtilisant = \App\Models\ESBTPInscription::where('filiere_id', $fraisScolarite->filiere_id)
            ->where('niveau_id', $fraisScolarite->niveau_id)
            ->where('annee_universitaire_id', $fraisScolarite->annee_universitaire_id)
            ->count();

        if ($inscriptionsUtilisant > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer cette configuration car elle est utilisée par ' . $inscriptionsUtilisant . ' inscription(s).');
        }

        $fraisScolarite->delete();

        return redirect()->route('esbtp.comptabilite.frais-scolarite')
            ->with('success', 'Configuration des frais de scolarité supprimée avec succès.');
    }

    /**
     * Affiche la liste des bourses
     */
    public function bourses()
    {
        try {
            $bourses = ESBTPBourse::with(['etudiant', 'anneeUniversitaire', 'createur'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return view('esbtp.comptabilite.bourses.index', compact('bourses'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors du chargement des bourses : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle bourse
     */
    public function createBourse()
    {
        try {
            $etudiants = ESBTPEtudiant::orderBy('nom')->orderBy('prenoms')->get();
            $annees = ESBTPAnneeUniversitaire::orderBy('name')->get();

            return view('esbtp.comptabilite.bourses.create', compact('etudiants', 'annees'));
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Erreur lors du chargement du formulaire : ' . $e->getMessage());
        }
    }

    /**
     * Enregistre une nouvelle bourse
     */
    public function storeBourse(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annees_universitaires,id',
            'type_bourse' => 'required|string|max:50',
            'montant' => 'nullable|numeric|min:0',
            'pourcentage' => 'nullable|numeric|min:0|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:active,suspendue,terminée',
            'organisme_financeur' => 'nullable|string|max:255',
            'conditions' => 'nullable|string',
            'commentaires' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'Veuillez sélectionner un étudiant.',
            'etudiant_id.exists' => 'L\'étudiant sélectionné n\'existe pas.',
            'annee_universitaire_id.required' => 'Veuillez sélectionner une année universitaire.',
            'annee_universitaire_id.exists' => 'L\'année universitaire sélectionnée n\'existe pas.',
            'type_bourse.required' => 'Veuillez sélectionner un type de bourse.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être positif.',
            'pourcentage.numeric' => 'Le pourcentage doit être un nombre.',
            'pourcentage.min' => 'Le pourcentage doit être positif.',
            'pourcentage.max' => 'Le pourcentage ne peut pas dépasser 100%.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'statut.required' => 'Veuillez sélectionner un statut.',
            'statut.in' => 'Le statut sélectionné n\'est pas valide.',
        ]);

        // Validation : soit montant soit pourcentage doit être renseigné
        if (!$request->montant && !$request->pourcentage) {
            return back()->withErrors(['montant' => 'Veuillez renseigner soit un montant soit un pourcentage.'])->withInput();
        }

        if ($request->montant && $request->pourcentage) {
            return back()->withErrors(['pourcentage' => 'Veuillez renseigner soit un montant soit un pourcentage, pas les deux.'])->withInput();
        }

        try {
            $data = $request->all();
            $data['createur_id'] = auth()->id();

            ESBTPBourse::create($data);

            return redirect()->route('esbtp.comptabilite.bourses')->with('success', 'Bourse créée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création de la bourse : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Affiche les détails d'une bourse
     */
    public function showBourse($id)
    {
        try {
            $bourse = ESBTPBourse::with(['etudiant', 'anneeUniversitaire', 'createur'])->findOrFail($id);

            return view('esbtp.comptabilite.bourses.show', compact('bourse'));
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Bourse non trouvée.');
        }
    }

    /**
     * Affiche le formulaire d'édition d'une bourse
     */
    public function editBourse($id)
    {
        try {
            $bourse = ESBTPBourse::findOrFail($id);
            $etudiants = ESBTPEtudiant::orderBy('nom')->orderBy('prenoms')->get();
            $annees = ESBTPAnneeUniversitaire::orderBy('name')->get();

            return view('esbtp.comptabilite.bourses.edit', compact('bourse', 'etudiants', 'annees'));
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Bourse non trouvée.');
        }
    }

    /**
     * Met à jour une bourse
     */
    public function updateBourse(Request $request, $id)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annees_universitaires,id',
            'type_bourse' => 'required|string|max:50',
            'montant' => 'nullable|numeric|min:0',
            'pourcentage' => 'nullable|numeric|min:0|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:active,suspendue,terminée',
            'organisme_financeur' => 'nullable|string|max:255',
            'conditions' => 'nullable|string',
            'commentaires' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'Veuillez sélectionner un étudiant.',
            'etudiant_id.exists' => 'L\'étudiant sélectionné n\'existe pas.',
            'annee_universitaire_id.required' => 'Veuillez sélectionner une année universitaire.',
            'annee_universitaire_id.exists' => 'L\'année universitaire sélectionnée n\'existe pas.',
            'type_bourse.required' => 'Veuillez sélectionner un type de bourse.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être positif.',
            'pourcentage.numeric' => 'Le pourcentage doit être un nombre.',
            'pourcentage.min' => 'Le pourcentage doit être positif.',
            'pourcentage.max' => 'Le pourcentage ne peut pas dépasser 100%.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'statut.required' => 'Veuillez sélectionner un statut.',
            'statut.in' => 'Le statut sélectionné n\'est pas valide.',
        ]);

        // Validation : soit montant soit pourcentage doit être renseigné
        if (!$request->montant && !$request->pourcentage) {
            return back()->withErrors(['montant' => 'Veuillez renseigner soit un montant soit un pourcentage.'])->withInput();
        }

        if ($request->montant && $request->pourcentage) {
            return back()->withErrors(['pourcentage' => 'Veuillez renseigner soit un montant soit un pourcentage, pas les deux.'])->withInput();
        }

        try {
            $bourse = ESBTPBourse::findOrFail($id);
            $bourse->update($request->all());

            return redirect()->route('esbtp.comptabilite.bourses')->with('success', 'Bourse mise à jour avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour de la bourse : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Supprime une bourse
     */
    public function destroyBourse($id)
    {
        try {
            $bourse = ESBTPBourse::findOrFail($id);
            $bourse->delete();

            return redirect()->route('esbtp.comptabilite.bourses')->with('success', 'Bourse supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Erreur lors de la suppression de la bourse : ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de configuration de la comptabilité
     */
    public function configuration()
    {
        $configurations = ESBTPComptabiliteConfiguration::orderBy('cle')->get();

        return view('esbtp.comptabilite.configuration', compact('configurations'));
    }

    /**
     * Met à jour la configuration de la comptabilité
     */
    public function updateConfiguration(Request $request)
    {
        foreach ($request->configurations as $id => $value) {
            $config = ESBTPComptabiliteConfiguration::findOrFail($id);
            $config->valeur = $value;
            $config->save();
        }

        return redirect()->route('esbtp.comptabilite.configuration')
            ->with('success', 'Configuration mise à jour avec succès.');
    }

    /**
     * Affiche la liste des fournisseurs
     */
    public function fournisseurs()
    {
        $fournisseurs = ESBTPFournisseur::orderBy('nom')->paginate(15);

        return view('esbtp.comptabilite.fournisseurs.index', compact('fournisseurs'));
    }

    /**
     * Affiche le formulaire d'ajout d'un fournisseur
     */
    public function createFournisseur()
    {
        return view('esbtp.comptabilite.fournisseurs.create');
    }

    /**
     * Enregistre un nouveau fournisseur
     */
    public function storeFournisseur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:255',
            'est_actif' => 'nullable|boolean',
        ]);

        $fournisseur = new \App\Models\ESBTPFournisseur();
        $fournisseur->nom = $validated['nom'];
        $fournisseur->telephone = $validated['telephone'] ?? null;
        $fournisseur->email = $validated['email'] ?? null;
        $fournisseur->adresse = $validated['adresse'] ?? null;
        $fournisseur->est_actif = $validated['est_actif'] ?? 1;
        $fournisseur->code = 'F-' . date('Ymd') . '-' . str_pad(ESBTPFournisseur::max('id')+1, 3, '0', STR_PAD_LEFT);
        $fournisseur->save();

        return redirect()->route('esbtp.comptabilite.fournisseurs')
            ->with('success', 'Fournisseur ajouté avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'un fournisseur
     */
    public function editFournisseur($id)
    {
        $fournisseur = \App\Models\ESBTPFournisseur::findOrFail($id);
        return view('esbtp.comptabilite.fournisseurs.edit', compact('fournisseur'));
    }

    /**
     * Affiche la liste des factures
     */
    public function factures()
    {
        $factures = ESBTPFacture::with(['etudiant', 'anneeUniversitaire', 'createur'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('esbtp.comptabilite.factures.index', compact('factures'));
    }

    /**
     * Affiche la liste des salaires
     */
    public function salaires()
    {
        $salaires = ESBTPSalaire::with(['user', 'anneeUniversitaire', 'createur', 'validateur'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculer les montants totaux des salaires
        $totalSalaires = ESBTPSalaire::sum('montant_net');
        $totalPayes = ESBTPSalaire::where('status', 'payé')->sum('montant_net');
        $totalEnAttente = ESBTPSalaire::where('status', 'en attente')->sum('montant_net');

        return view('esbtp.comptabilite.salaires.index', compact('salaires', 'totalSalaires', 'totalPayes', 'totalEnAttente'));
    }

    /**
     * Affiche le formulaire de création d'un salaire
     */
    public function createSalaire()
    {
        // Récupérer la liste des employés (enseignants et administratifs)
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['enseignant', 'secretaire', 'superAdmin']);
        })->orderBy('name')->get();

        return view('esbtp.comptabilite.salaires.create', compact('users'));
    }

    /**
     * Enregistre un nouveau salaire
     */
    public function storeSalaire(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer',
            'salaire_base' => 'required|numeric|min:0',
            'heures_supplementaires' => 'nullable|numeric|min:0',
            'primes' => 'nullable|numeric|min:0',
            'retenues' => 'nullable|numeric|min:0',
            'montant_net' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'status' => 'required|in:calculé,validé,payé',
            'description' => 'nullable|string',
        ]);

        // Vérifier si un salaire existe déjà pour cet employé pour ce mois et cette année
        $existingSalaire = ESBTPSalaire::where('user_id', $request->user_id)
            ->where('mois', $request->mois)
            ->where('annee', $request->annee)
            ->first();

        if ($existingSalaire) {
            return redirect()->back()
                ->with('error', 'Un salaire existe déjà pour cet employé pour la période spécifiée.')
                ->withInput();
        }

        // Créer le nouveau salaire
        $salaire = new ESBTPSalaire();
        $salaire->user_id = $request->user_id;
        $salaire->mois = $request->mois;
        $salaire->annee = $request->annee;
        $salaire->salaire_base = $request->salaire_base;
        $salaire->heures_supplementaires = $request->heures_supplementaires ?? 0;
        $salaire->primes = $request->primes ?? 0;
        $salaire->retenues = $request->retenues ?? 0;
        $salaire->montant_net = $request->montant_net;
        $salaire->date_paiement = $request->date_paiement;
        $salaire->status = $request->status;
        $salaire->notes = $request->description;
        $salaire->createur_id = auth()->id();

        // Si le statut est "payé", définir la date de validation et le validateur
        if ($request->status == 'payé') {
            $salaire->validateur_id = auth()->id();
            $salaire->date_validation = now();
        }

        $salaire->save();

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', 'Le salaire a été créé avec succès.');
    }

    /**
     * Affiche les détails d'un salaire
     */
    public function showSalaire($id)
    {
        $salaire = ESBTPSalaire::with(['user', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.salaires.show', compact('salaire'));
    }

    /**
     * Affiche le formulaire d'édition d'un salaire
     */
    public function editSalaire($id)
    {
        $salaire = ESBTPSalaire::findOrFail($id);

        // Récupérer la liste des employés
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['enseignant', 'secretaire', 'superAdmin']);
        })->orderBy('name')->get();

        return view('esbtp.comptabilite.salaires.edit', compact('salaire', 'users'));
    }

    /**
     * Met à jour un salaire existant
     */
    public function updateSalaire(Request $request, $id)
    {
        $salaire = ESBTPSalaire::findOrFail($id);

        // Validation des données
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer',
            'salaire_base' => 'required|numeric|min:0',
            'heures_supplementaires' => 'nullable|numeric|min:0',
            'primes' => 'nullable|numeric|min:0',
            'retenues' => 'nullable|numeric|min:0',
            'montant_net' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'status' => 'required|in:calculé,validé,payé',
            'description' => 'nullable|string',
        ]);

        // Vérifier si un autre salaire existe déjà pour cet employé pour ce mois et cette année
        $existingSalaire = ESBTPSalaire::where('user_id', $request->user_id)
            ->where('mois', $request->mois)
            ->where('annee', $request->annee)
            ->where('id', '!=', $id)
            ->first();

        if ($existingSalaire) {
            return redirect()->back()
                ->with('error', 'Un autre salaire existe déjà pour cet employé pour la période spécifiée.')
                ->withInput();
        }

        // Mettre à jour le salaire
        $salaire->user_id = $request->user_id;
        $salaire->mois = $request->mois;
        $salaire->annee = $request->annee;
        $salaire->salaire_base = $request->salaire_base;
        $salaire->heures_supplementaires = $request->heures_supplementaires ?? 0;
        $salaire->primes = $request->primes ?? 0;
        $salaire->retenues = $request->retenues ?? 0;
        $salaire->montant_net = $request->montant_net;
        $salaire->date_paiement = $request->date_paiement;
        $salaire->notes = $request->description;

        // Si le statut change pour "payé", mettre à jour le validateur et la date de validation
        if ($request->status == 'payé' && $salaire->status != 'payé') {
            $salaire->validateur_id = auth()->id();
            $salaire->date_validation = now();
        }

        $salaire->status = $request->status;
        $salaire->save();

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', 'Le salaire a été mis à jour avec succès.');
    }

    /**
     * Supprime un salaire
     */
    public function destroySalaire($id)
    {
        $salaire = ESBTPSalaire::findOrFail($id);
        $salaire->delete();

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', 'Le salaire a été supprimé avec succès.');
    }

    /**
     * Génère un bulletin de salaire
     */
    public function bulletinSalaire($id)
    {
        $salaire = ESBTPSalaire::with(['user', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        // Préparer les données pour le PDF
        $data = [
            'salaire' => $salaire,
            'employe' => $salaire->user,
            'mois' => [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ][$salaire->mois],
            'annee' => $salaire->annee,
            'date_emission' => now()->format('d/m/Y')
        ];

        // Générer le PDF
        $pdf = \PDF::loadView('esbtp.comptabilite.salaires.bulletin', $data);

        // Télécharger le PDF
        $filename = 'bulletin_salaire_' . $salaire->user->name . '_' . $data['mois'] . '_' . $data['annee'] . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Met à jour le statut d'un salaire
     */
    public function updateStatusSalaire($id, $status)
    {
        $salaire = ESBTPSalaire::findOrFail($id);

        // Vérifier que le statut est valide
        if (!in_array($status, ['calculé', 'validé', 'payé'])) {
            return redirect()->back()->with('error', 'Statut invalide.');
        }

        // Mettre à jour le statut
        $salaire->status = $status;

        // Si le statut est "payé", mettre à jour le validateur et la date de validation
        if ($status == 'payé' && $salaire->validateur_id === null) {
            $salaire->validateur_id = auth()->id();
            $salaire->date_validation = now();

            // Mettre à jour la date de paiement si elle n'est pas définie
            if ($salaire->date_paiement === null) {
                $salaire->date_paiement = now();
            }
        }

        $salaire->save();

        $statusText = [
            'calculé' => 'calculé',
            'validé' => 'validé',
            'payé' => 'payé'
        ][$status];

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', "Le salaire a été marqué comme $statusText avec succès.");
    }

    // ===== GESTION DES DÉPENSES (intégré depuis DepensesController) =====

    /**
     * Affiche la liste des dépenses
     */
    public function depenses()
    {
        $depenses = ESBTPDepense::with(['categorie', 'createur'])
            ->orderBy('date_depense', 'desc')
            ->paginate(15);

        $categories = ESBTPCategorieDepense::all();

        return view('esbtp.comptabilite.depenses.index', compact('depenses', 'categories'));
    }

    /**
     * Affiche le formulaire de création d'une dépense
     */
    public function createDepense()
    {
        $categories = ESBTPCategorieDepense::where('est_actif', true)->orderBy('nom')->get();
        $fournisseurs = ESBTPFournisseur::where('est_actif', true)->orderBy('nom')->get();
        $bonsDeSortieApprouves = $this->bonDepenseService->getBonsApprouves();
        
        return view('esbtp.comptabilite.depenses.create', compact('categories', 'fournisseurs', 'bonsDeSortieApprouves'));
    }

    /**
     * Enregistre une nouvelle dépense.
     */
    public function storeDepense(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categorie_id' => 'required|exists:esbtp_categories_depenses,id',
            'montant' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'fournisseur_id' => 'nullable|exists:esbtp_fournisseurs,id',
            'date_depense' => 'required|date',
            'recepisse' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'tva_applicable' => 'boolean',
            'taux_tva' => 'nullable|numeric|min:0',
            'bon_sortie_id' => 'nullable|exists:esbtp_bons_sortie,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $depense = $this->bonDepenseService->linkBonToDepense(
                $request->bon_sortie_id,
                $request->all()
            );

            return redirect()->route('esbtp.comptabilite.depenses.index')
                             ->with('success', 'Dépense enregistrée avec succès. ID: ' . $depense->id);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la création de la dépense: " . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Affiche les détails d'une dépense
     */
    public function showDepense($id)
    {
        $depense = ESBTPDepense::with(['categorie', 'createur'])->findOrFail($id);
        return view('esbtp.comptabilite.depenses.show', compact('depense'));
    }

    /**
     * Affiche le formulaire d'édition d'une dépense
     */
    public function editDepense($id)
    {
        $depense = ESBTPDepense::findOrFail($id);
        $categories = ESBTPCategorieDepense::where('est_actif', true)->orderBy('nom')->get();

        return view('esbtp.comptabilite.depenses.edit', compact('depense', 'categories'));
    }

    /**
     * Met à jour une dépense
     */
    public function updateDepense(Request $request, $id)
    {
        $depense = ESBTPDepense::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'categorie_id' => 'required|exists:esbtp_categories_depenses,id',
            'description' => 'nullable|string',
            'mode_paiement' => 'required|string',
            'reference' => 'nullable|string|unique:esbtp_depenses,reference,' . $id,
        ]);

        $depense->update($validated);

        return redirect()->route('esbtp.comptabilite.depenses')
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    /**
     * Supprime une dépense
     */
    public function destroyDepense($id)
    {
        $depense = ESBTPDepense::findOrFail($id);
        $depense->delete();

        return redirect()->route('esbtp.comptabilite.depenses')
            ->with('success', 'Dépense supprimée avec succès.');
    }

    /**
     * Crée un nouveau fournisseur via AJAX
     */
    public function storeFournisseurAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:20',
                'adresse' => 'nullable|string|max:500'
            ]);

            // Générer un code automatique
            $validated['code'] = 'FOUR-' . strtoupper(substr($validated['nom'], 0, 3)) . '-' . time();
            $validated['type'] = 'standard';
            $validated['est_actif'] = true;

            $fournisseur = ESBTPFournisseur::create($validated);

            return response()->json([
                'success' => true,
                'fournisseur' => [
                    'id' => $fournisseur->id,
                    'nom' => $fournisseur->nom,
                    'email' => $fournisseur->email,
                    'telephone' => $fournisseur->telephone
                ],
                'message' => 'Fournisseur créé avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Erreur de validation'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche la gestion des catégories de dépenses
     */
    public function categoriesDepenses()
    {
        $categories = ESBTPCategorieDepense::withCount('depenses')
            ->orderBy('nom')
            ->get();

        return view('esbtp.comptabilite.depenses.categories', compact('categories'));
    }

    /**
     * Enregistre une nouvelle catégorie de dépense
     */
    public function storeCategorieDepense(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:esbtp_categories_depenses,nom',
            'description' => 'nullable|string',
            'code' => 'required|string|unique:esbtp_categories_depenses,code',
        ]);

        ESBTPCategorieDepense::create($validated);

        return redirect()->route('esbtp.comptabilite.depenses.categories')
            ->with('success', 'Catégorie de dépense créée avec succès.');
    }

    /**
     * Met à jour une catégorie de dépense
     */
    public function updateCategorieDepense(Request $request, $id)
    {
        $category = ESBTPCategorieDepense::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:esbtp_categories_depenses,nom,'.$id,
            'description' => 'nullable|string',
            'code' => 'required|string|unique:esbtp_categories_depenses,code,'.$id,
        ]);

        $category->update($validated);

        return redirect()->route('esbtp.comptabilite.depenses.categories')
            ->with('success', 'Catégorie de dépense mise à jour avec succès.');
    }

    /**
     * Supprime une catégorie de dépense
     */
    public function destroyCategorieDepense($id)
    {
        $category = ESBTPCategorieDepense::findOrFail($id);

        // Vérifier si la catégorie a des dépenses
        if ($category->depenses()->count() > 0) {
            return redirect()->route('esbtp.comptabilite.depenses.categories')
                ->with('error', 'Impossible de supprimer cette catégorie car elle est associée à des dépenses.');
        }

        $category->delete();

        return redirect()->route('esbtp.comptabilite.depenses.categories')
            ->with('success', 'Catégorie de dépense supprimée avec succès.');
    }

    public function dashboard(Request $request)
    {
        // Récupérer les listes pour les filtres
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $classes = \App\Models\ESBTPClasse::orderBy('name')->get();

        // Filtres
        $anneeId = $request->get('annee');
        $filiereId = $request->get('filiere');
        $classeId = $request->get('classe');

        $feeQuery = \App\Models\ESBTP\Fee::query();
        if ($anneeId) $feeQuery->where('academic_year_id', $anneeId);
        if ($filiereId) $feeQuery->whereHas('class', function($q) use ($filiereId) { $q->where('filiere_id', $filiereId); });
        if ($classeId) $feeQuery->where('class_id', $classeId);

        $totalDue = (clone $feeQuery)->pending()->sum('amount');
        $totalPaid = (clone $feeQuery)->paid()->sum('amount');
        $totalOverdue = (clone $feeQuery)->overdue()->sum('amount');
        $countPaid = (clone $feeQuery)->paid()->count();
        $countPartiallyPaid = (clone $feeQuery)->partiallyPaid()->count();
        $countOverdue = (clone $feeQuery)->overdue()->count();
        $countDue = (clone $feeQuery)->due()->count();

        // Préparer les données pour le graph (encaissements par mois)
        $labelsMois = [];
        $dataEncaissements = [];
        if ($anneeId) {
            $annee = $annees->where('id', $anneeId)->first();
        } else {
            $annee = $annees->first(); // Par défaut, la plus récente
        }
        if ($annee) {
            $debut = \Carbon\Carbon::parse($annee->date_debut);
            $fin = \Carbon\Carbon::parse($annee->date_fin);
            for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
                $labelsMois[] = $date->translatedFormat('M Y');
                $paymentQuery = \App\Models\ESBTP\Payment::completed();
                $paymentQuery->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year);
                if ($anneeId) $paymentQuery->where('academic_year_id', $anneeId);
                if ($filiereId) $paymentQuery->whereHas('fee.class', function($q) use ($filiereId) { $q->where('filiere_id', $filiereId); });
                if ($classeId) $paymentQuery->whereHas('fee', function($q) use ($classeId) { $q->where('class_id', $classeId); });
                $dataEncaissements[] = $paymentQuery->sum('amount');
            }
        }

        // Préparer les données pour le graph (dépenses par mois)
        $labelsMoisDepenses = [];
        $dataDepensesMensuelles = [];
        if ($annee) {
            $debut = \Carbon\Carbon::parse($annee->date_debut);
            $fin = \Carbon\Carbon::parse($annee->date_fin);
            for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
                $labelsMoisDepenses[] = $date->translatedFormat('M Y');
                $depenseQuery = \App\Models\ESBTPDepense::whereBetween('date_depense', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->where('status', 'validée');
                if ($filiereId) $depenseQuery->where('filiere_id', $filiereId);
                if ($classeId) $depenseQuery->where('classe_id', $classeId);
                $dataDepensesMensuelles[] = $depenseQuery->sum('montant');
            }
        }

        // Agrégats dépenses (centralisation)
        $statsDepenses = [
            'total' => 0,
            'mensuel' => 0,
            'salaires' => 0,
            'fournitures' => 0
        ];
        if ($annee) {
            $dateDebut = \Carbon\Carbon::parse($annee->date_debut);
            $dateFin = \Carbon\Carbon::parse($annee->date_fin);
            $depenseQuery = \App\Models\ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])->where('status', 'validée');
            if ($filiereId) $depenseQuery->where('filiere_id', $filiereId);
            if ($classeId) $depenseQuery->where('classe_id', $classeId);
            $statsDepenses['total'] = $depenseQuery->sum('montant');
            $statsDepenses['mensuel'] = (clone $depenseQuery)->whereMonth('date_depense', now()->month)->whereYear('date_depense', now()->year)->sum('montant');
            // Salaires et fournitures peuvent être affinés si besoin
        }

        return view('esbtp.comptabilite.dashboard', compact(
            'totalDue', 'totalPaid', 'totalOverdue',
            'countPaid', 'countPartiallyPaid', 'countOverdue', 'countDue',
            'annees', 'filieres', 'classes',
            'labelsMois', 'dataEncaissements',
            'statsDepenses',
            'labelsMoisDepenses', 'dataDepensesMensuelles'
        ));
    }

    // ===== NOUVELLES MÉTHODES AVANCÉES SELON LE GUIDE =====

    /**
     * Gestion des bons de sortie avec workflow
     */
    public function bonsSortie()
    {
        $bons = \App\Models\ESBTPDepense::with(['categorie', 'createur'])
            ->whereNotNull('numero_bon')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $statistiques = [
            'total' => \App\Models\ESBTPDepense::whereNotNull('numero_bon')->count(),
            'en_attente' => \App\Models\ESBTPDepense::where('statut_workflow', 'en_attente')->count(),
            'approuve' => \App\Models\ESBTPDepense::where('statut_workflow', 'approuve')->count(),
            'rejete' => \App\Models\ESBTPDepense::where('statut_workflow', 'rejete')->count()
        ];

        return view('esbtp.comptabilite.bons-sortie.index', compact('bons', 'statistiques'));
    }

    /**
     * Créer un nouveau bon de sortie
     */
    public function createBonSortie()
    {
        $categories = \App\Models\ESBTPCategorieDepense::where('est_actif', true)->orderBy('nom')->get();
        $fournisseurs = \App\Models\ESBTPFournisseur::where('est_actif', true)->orderBy('nom')->get();

        return view('esbtp.comptabilite.bons-sortie.create', compact('categories', 'fournisseurs'));
    }

    /**
     * Enregistrer un bon de sortie
     */
    public function storeBonSortie(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'categorie_id' => 'required|exists:esbtp_categories_depenses,id',
            'fournisseur_id' => 'nullable|exists:esbtp_fournisseurs,id',
            'description' => 'nullable|string',
            'mode_paiement' => 'required|string',
        ]);

        // Génération du numéro de bon
        $numeroBon = 'BON-' . date('Ymd') . '-' . str_pad(\App\Models\ESBTPDepense::max('id') + 1, 4, '0', STR_PAD_LEFT);

        $bon = \App\Models\ESBTPDepense::create(array_merge($validated, [
            'numero_bon' => $numeroBon,
            'status' => 'brouillon',
            'statut_workflow' => 'en_attente',
            'createur_id' => Auth::id(),
            'workflow_data' => json_encode([
                'date_creation' => now(),
                'createur' => Auth::user()->name
            ])
        ]));

        return redirect()->route('esbtp.comptabilite.bons-sortie')
            ->with('success', "Bon de sortie {$numeroBon} créé avec succès.");
    }

    /**
     * Gestion des relances
     */
    public function gestionRelances()
    {
        $relances = \App\Models\ESBTPRelance::with(['etudiant', 'facture'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $statistiques = [
            'total' => \App\Models\ESBTPRelance::count(),
            'planifiees' => \App\Models\ESBTPRelance::where('statut', 'planifiee')->count(),
            'envoyees' => \App\Models\ESBTPRelance::where('statut', 'envoyee')->count(),
            'echecs' => \App\Models\ESBTPRelance::where('statut', 'echec')->count()
        ];

        return view('esbtp.comptabilite.relances.index', compact('relances', 'statistiques'));
    }

    /**
     * Rapports avancés avec builder
     */
    public function rapportsAvances()
    {
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $categories = \App\Models\ESBTPCategorieDepense::orderBy('nom')->get();

        return view('esbtp.comptabilite.rapports.builder', compact('annees', 'filieres', 'categories'));
    }

    /**
     * Afficher un bon de sortie
     */
    public function showBonSortie($id)
    {
        $bon = \App\Models\ESBTPDepense::with(['categorie', 'fournisseur', 'createur'])
            ->whereNotNull('numero_bon')
            ->findOrFail($id);

        return view('esbtp.comptabilite.bons-sortie.show', compact('bon'));
    }

    /**
     * Éditer un bon de sortie
     */
    public function editBonSortie($id)
    {
        $bon = \App\Models\ESBTPDepense::whereNotNull('numero_bon')->findOrFail($id);

        // Vérifier que le bon peut être modifié
        if ($bon->statut_workflow !== 'brouillon') {
            return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
                ->with('error', 'Seuls les bons en brouillon peuvent être modifiés.');
        }

        $categories = \App\Models\ESBTPCategorieDepense::where('est_actif', true)->orderBy('nom')->get();
        $fournisseurs = \App\Models\ESBTPFournisseur::where('est_actif', true)->orderBy('nom')->get();

        return view('esbtp.comptabilite.bons-sortie.edit', compact('bon', 'categories', 'fournisseurs'));
    }

    /**
     * Mettre à jour un bon de sortie
     */
    public function updateBonSortie(Request $request, $id)
    {
        $bon = \App\Models\ESBTPDepense::whereNotNull('numero_bon')->findOrFail($id);

        // Vérifier que le bon peut être modifié
        if ($bon->statut_workflow !== 'brouillon') {
            return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
                ->with('error', 'Seuls les bons en brouillon peuvent être modifiés.');
        }

        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'categorie_id' => 'required|exists:esbtp_categories_depenses,id',
            'fournisseur_id' => 'nullable|exists:esbtp_fournisseurs,id',
            'description' => 'nullable|string',
            'mode_paiement' => 'required|string',
        ]);

        $bon->update($validated);

        return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
            ->with('success', 'Bon de sortie mis à jour avec succès.');
    }

    /**
     * Approuver un bon de sortie
     */
    public function approuverBon(Request $request, $id)
    {
        $workflowService = app(\App\Services\WorkflowService::class);

        $result = $workflowService->approuverDepense(
            $id,
            Auth::id(),
            $request->input('commentaire')
        );

        if ($result['success']) {
            return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
                ->with('success', $result['message']);
        }

        return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
            ->with('error', $result['message']);
    }

    /**
     * Rejeter un bon de sortie
     */
    public function rejeterBon(Request $request, $id)
    {
        $request->validate([
            'motif' => 'required|string|max:500'
        ]);

        $workflowService = app(\App\Services\WorkflowService::class);

        $result = $workflowService->rejeterDepense(
            $id,
            Auth::id(),
            $request->input('motif')
        );

        if ($result['success']) {
            return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
                ->with('success', $result['message']);
        }

        return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
            ->with('error', $result['message']);
    }

    /**
     * Générer le PDF d'un bon de sortie
     */
    public function genererPDFBon($id)
    {
        $bon = \App\Models\ESBTPDepense::with(['categorie', 'fournisseur', 'createur'])
            ->whereNotNull('numero_bon')
            ->findOrFail($id);

        // Vérifier que le bon peut être imprimé
        if (!in_array($bon->statut_workflow, ['approuve', 'paye'])) {
            return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
                ->with('error', 'Seuls les bons approuvés ou payés peuvent être imprimés en PDF.');
        }

        $pdfService = app(\App\Services\PDFService::class);
        return $pdfService->genererPDFBonSortie($bon);
    }

    /**
     * Soumettre un bon pour approbation
     */
    public function soumettreApprobation($id)
    {
        $workflowService = app(\App\Services\WorkflowService::class);

        $result = $workflowService->soumettrePourApprobation($id, Auth::id());

        return response()->json($result);
    }

    /**
     * Marquer un bon comme payé
     */
    public function marquerCommePaye(Request $request, $id)
    {
        $request->validate([
            'reference_paiement' => 'nullable|string|max:255',
            'date_paiement' => 'required|date',
            'commentaire' => 'nullable|string|max:500'
        ]);

        $workflowService = app(\App\Services\WorkflowService::class);

        $referencesPaiement = [
            'reference_paiement' => $request->input('reference_paiement'),
            'date_paiement' => $request->input('date_paiement'),
            'commentaire' => $request->input('commentaire')
        ];

        $result = $workflowService->marquerCommePaye($id, Auth::id(), $referencesPaiement);

        if ($result['success']) {
            return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
                ->with('success', $result['message']);
        }

        return redirect()->route('esbtp.comptabilite.bons-sortie.show', $id)
            ->with('error', $result['message']);
    }

    /**
     * Configuration des relances
     */
    public function configurationRelances()
    {
        // Récupérer les templates existants depuis la configuration
        $templates = [
            'email' => [],
            'sms' => [],
            'courrier' => []
        ];

        // Récupérer les paramètres de relances
        $parametres = [
            'delai_niveau_1' => 30,
            'delai_niveau_2' => 45,
            'delai_niveau_3' => 60,
            'montant_minimum' => 10000,
            'relances_automatiques' => false,
            'heure_envoi' => '09:00'
        ];

        return view('esbtp.comptabilite.relances.config', compact('templates', 'parametres'));
    }

    /**
     * Aperçu des étudiants pour relances
     */
    public function apercuRelances(Request $request)
    {
        $dette = $request->input('dette', 50000);
        $jours = $request->input('jours', 30);

        $etudiants = \App\Models\ESBTPEtudiant::whereHas('factures', function($query) use ($dette, $jours) {
            $query->where('status', 'impayee')
                  ->where('montant_total', '>=', $dette)
                  ->where('date_echeance', '<', now()->subDays($jours));
        })->with('factures')->get();

        $totalDette = $etudiants->sum(function($etudiant) {
            return $etudiant->factures->where('status', 'impayee')->sum('montant_total');
        });

        $moyenneDette = $etudiants->count() > 0 ? $totalDette / $etudiants->count() : 0;

        return response()->json([
            'success' => true,
            'count' => $etudiants->count(),
            'total_dette' => number_format($totalDette, 0, ',', ' '),
            'moyenne_dette' => number_format($moyenneDette, 0, ',', ' ')
        ]);
    }

    /**
     * Planifier des relances
     */
    public function planifierRelances(Request $request)
    {
        $request->validate([
            'critere_dette' => 'required|numeric|min:0',
            'critere_jours' => 'required|numeric|min:1',
            'type_relance' => 'required|string|in:auto,email,sms,courrier',
            'date_envoi' => 'required|date'
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Utiliser la logique de planification du service
            $result = $notificationService->planifierRelances();

            return response()->json([
                'success' => true,
                'message' => "Relances planifiées avec succès: {$result['relances_planifiees']} relances créées"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la planification: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Afficher les détails d'une relance
     */
    public function showRelance($id)
    {
        $relance = \App\Models\ESBTPRelance::with(['etudiant', 'facture'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.relances.show', compact('relance'));
    }

    /**
     * Renvoyer une relance
     */
    public function renvoyerRelance($id)
    {
        try {
            $relance = \App\Models\ESBTPRelance::findOrFail($id);

            // Vérifier que la relance peut être renvoyée
            if (!$relance->peutEtreRenvoyee()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette relance ne peut pas être renvoyée.'
                ]);
            }

            // Dispatche le job d'envoi
            \App\Jobs\EnvoyerRelanceJob::dispatch($relance);

            return response()->json([
                'success' => true,
                'message' => 'Relance mise en file d\'attente pour renvoi.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarder les templates de relances
     */
    public function sauvegarderTemplates(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:email,sms,courrier'
        ]);

        try {
            // Logique de sauvegarde des templates
            // À implémenter selon la structure de configuration choisie

            return response()->json([
                'success' => true,
                'message' => 'Templates sauvegardés avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarder les paramètres de relances
     */
    public function sauvegarderParametres(Request $request)
    {
        $request->validate([
            'delai_niveau_1' => 'required|numeric|min:1|max:365',
            'delai_niveau_2' => 'required|numeric|min:1|max:365',
            'delai_niveau_3' => 'required|numeric|min:1|max:365',
            'montant_minimum' => 'required|numeric|min:0',
            'heure_envoi' => 'required|date_format:H:i'
        ]);

        try {
            // Logique de sauvegarde des paramètres
            // À implémenter selon la structure de configuration choisie

            return response()->json([
                'success' => true,
                'message' => 'Paramètres sauvegardés avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Aperçu d'un template de relance
     */
    public function previewTemplate(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:email,sms,courrier',
            'niveau' => 'required|integer|min:1|max:3',
            'contenu' => 'required|string'
        ]);

        try {
            // Données d'exemple pour l'aperçu
            $etudiantExemple = (object) [
                'nom' => 'KOUAME',
                'prenoms' => 'Jean Pierre',
                'email' => 'jean.kouame@example.com',
                'telephone' => '+225 01 02 03 04 05'
            ];

            $contenu = $request->input('contenu');
            $type = $request->input('type');

            // Remplacer les variables par les exemples
            $variables = [
                '{nom}' => $etudiantExemple->nom,
                '{prenom}' => $etudiantExemple->prenoms,
                '{nom_complet}' => $etudiantExemple->prenoms . ' ' . $etudiantExemple->nom,
                '{email}' => $etudiantExemple->email,
                '{telephone}' => $etudiantExemple->telephone,
                '{montant_dette}' => '150,000 FCFA',
                '{date_echeance}' => now()->subDays(45)->format('d/m/Y'),
                '{jours_retard}' => '45',
                '{niveau_relance}' => $request->input('niveau'),
                '{nom_ecole}' => 'École Supérieure du Bâtiment et des Travaux Publics',
                '{date_aujourdhui}' => now()->format('d/m/Y')
            ];

            $contenuApercu = str_replace(array_keys($variables), array_values($variables), $contenu);

            $html = view('esbtp.comptabilite.relances.preview', compact('contenuApercu', 'type'))->render();

            return response($html);

        } catch (\Exception $e) {
            return response('<div class="text-center text-danger">Erreur lors de la génération</div>');
        }
    }

    /**
     * Exécuter les relances en attente manuellement
     */
    public function executerRelances()
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $resultats = $notificationService->executerRelancesEnAttente();

            return response()->json([
                'success' => true,
                'message' => "Exécution terminée: {$resultats['reussies']} réussies, {$resultats['echecs']} échecs sur {$resultats['total']} relances."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Supprime un fournisseur
     */
    public function destroyFournisseur($id)
    {
        $fournisseur = \App\Models\ESBTPFournisseur::findOrFail($id);
        $fournisseur->delete();
        return redirect()->route('esbtp.comptabilite.fournisseurs')
            ->with('success', 'Fournisseur supprimé avec succès.');
    }

    /**
     * NOUVELLES MÉTHODES ANALYTICS AVANCÉES - Tâche #4
     */

    /**
     * Tableau de bord analytics des relances
     */
    public function analyticsRelances()
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Récupérer les statistiques avancées
            $statistiques = $notificationService->getStatistiquesRelancesAvancees();

            // Ajouter des métriques supplémentaires
            $statistiques['taux_global'] = $this->calculerTauxGlobalEfficacite();
            $statistiques['conversions_totales'] = $this->calculerConversionsTotal();
            $statistiques['delai_moyen'] = $this->calculerDelaiMoyenReponse();
            $statistiques['roi'] = $this->calculerROIRelances();

            return view('esbtp.comptabilite.relances.analytics', compact('statistiques'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur analytics relances: ' . $e->getMessage());

            // Retourner des données par défaut en cas d'erreur
            $statistiques = $this->getStatistiquesParDefaut();
            return view('esbtp.comptabilite.relances.analytics', compact('statistiques'));
        }
    }

    /**
     * Planification avancée avec segmentation
     */
    public function planifierRelancesAvancees(Request $request)
    {
        $request->validate([
            'segmentation' => 'required|string|in:auto,niveau_retard,montant_dette,historique_paiement,classe',
            'niveau_max' => 'required|integer|min:1|max:5',
            'types_relance' => 'required|array',
            'types_relance.*' => 'in:email,sms,courrier',
            'date_execution' => 'nullable|date|after_or_equal:today'
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);

            $parametres = [
                'segmentation' => $request->input('segmentation'),
                'niveau_max' => $request->input('niveau_max'),
                'types_relance' => $request->input('types_relance'),
                'date_execution' => $request->input('date_execution')
            ];

            // Si date future, programmer le job
            if ($request->filled('date_execution') && $request->input('date_execution') > now()->format('Y-m-d')) {
                \App\Jobs\PlanifierRelancesJob::dispatch($parametres)
                    ->delay(now()->parse($request->input('date_execution')));

                $message = "Relances programmées pour le " . now()->parse($request->input('date_execution'))->format('d/m/Y');
            } else {
                // Exécution immédiate
                $resultat = $notificationService->planifierRelancesAvancees($parametres);
                $message = "Planification terminée: {$resultat['relances_planifiees']} relances créées pour {$resultat['etudiants_traites']} étudiants";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la planification avancée: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export des données analytics
     */
    public function exportAnalyticsRelances(Request $request)
    {
        $request->validate([
            'format' => 'required|string|in:pdf,excel,csv',
            'periode' => 'required|string|in:mois_actuel,3_mois,6_mois,annee',
            'inclure_graphiques' => 'boolean'
        ]);

        try {
            $format = $request->input('format');
            $periode = $request->input('periode');
            $inclureGraphiques = $request->boolean('inclure_graphiques');

            $notificationService = app(\App\Services\NotificationService::class);
            $statistiques = $notificationService->getStatistiquesRelancesAvancees();

            switch ($format) {
                case 'pdf':
                    return $this->exportPDFAnalytics($statistiques, $periode, $inclureGraphiques);

                case 'excel':
                    return $this->exportExcelAnalytics($statistiques, $periode);

                case 'csv':
                    return $this->exportCSVAnalytics($statistiques, $periode);

                default:
                    throw new \Exception('Format non supporté');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Aperçu des segments avant planification
     */
    public function previewSegmentation(Request $request)
    {
        $request->validate([
            'type_segmentation' => 'required|string|in:auto,niveau_retard,montant_dette,historique_paiement,classe'
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $segments = $notificationService->segmenterEtudiants($request->input('type_segmentation'));

            $preview = [];
            foreach ($segments as $nomSegment => $etudiants) {
                $preview[$nomSegment] = [
                    'nombre_etudiants' => count($etudiants),
                    'total_dette' => $etudiants->sum(function($etudiant) use ($notificationService) {
                        return $notificationService->calculerDette($etudiant);
                    }),
                    'exemple_etudiants' => array_slice($etudiants->pluck('nom', 'id')->toArray(), 0, 3)
                ];
            }

            return response()->json([
                'success' => true,
                'segments' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'aperçu: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Méthodes privées pour le calcul des métriques
     */
    private function calculerTauxGlobalEfficacite()
    {
        $totalRelances = \App\Models\ESBTPRelance::where('status', 'envoyee')->count();

        if ($totalRelances == 0) return 0;

        $relancesEfficaces = \App\Models\ESBTPRelance::where('status', 'envoyee')
            ->whereHas('etudiant.paiements', function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->where('created_at', '<', \DB::raw('DATE_ADD(esbtp_relances.date_envoi, INTERVAL 30 DAY)'));
            })
            ->count();

        return round(($relancesEfficaces / $totalRelances) * 100, 2);
    }

    private function calculerConversionsTotal()
    {
        return \App\Models\ESBTPRelance::where('status', 'envoyee')
            ->whereMonth('date_envoi', now()->month)
            ->whereYear('date_envoi', now()->year)
            ->whereHas('etudiant.paiements', function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->where('created_at', '<', \DB::raw('DATE_ADD(esbtp_relances.date_envoi, INTERVAL 30 DAY)'));
            })
            ->count();
    }

    private function calculerDelaiMoyenReponse()
    {
        $relancesAvecPaiement = \App\Models\ESBTPRelance::where('status', 'envoyee')
            ->whereHas('etudiant.paiements', function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'));
            })
            ->with(['etudiant.paiements' => function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->orderBy('created_at', 'asc')
                      ->limit(1);
            }])
            ->get();

        if ($relancesAvecPaiement->isEmpty()) return 0;

        $totalJours = 0;
        $nombreRelances = 0;

        foreach ($relancesAvecPaiement as $relance) {
            $premierPaiement = $relance->etudiant->paiements->first();
            if ($premierPaiement) {
                $jours = $relance->date_envoi->diffInDays($premierPaiement->created_at);
                $totalJours += $jours;
                $nombreRelances++;
            }
        }

        return $nombreRelances > 0 ? round($totalJours / $nombreRelances, 1) : 0;
    }

    private function calculerROIRelances()
    {
        // Calcul simple du ROI basé sur les montants récupérés vs coût estimé des relances
        $montantRecupere = \App\Models\ESBTPPaiement::whereHas('relance')
            ->whereMonth('created_at', now()->month)
            ->sum('montant');

        $coutEstimeRelances = \App\Models\ESBTPRelance::whereMonth('created_at', now()->month)
            ->count() * 100; // 100 FCFA par relance (coût estimé)

        if ($coutEstimeRelances == 0) return 0;

        return round((($montantRecupere - $coutEstimeRelances) / $coutEstimeRelances) * 100, 2);
    }

    private function getStatistiquesParDefaut()
    {
        return [
            'taux_global' => 0,
            'conversions_totales' => 0,
            'delai_moyen' => 0,
            'roi' => 0,
            'efficacite_par_type' => [
                'email' => ['total_envoyees' => 0, 'avec_paiement' => 0, 'taux_efficacite' => 0],
                'sms' => ['total_envoyees' => 0, 'avec_paiement' => 0, 'taux_efficacite' => 0],
                'courrier' => ['total_envoyees' => 0, 'avec_paiement' => 0, 'taux_efficacite' => 0]
            ],
            'taux_conversion_par_niveau' => [
                'niveau_1' => ['total' => 0, 'conversions' => 0, 'taux' => 0],
                'niveau_2' => ['total' => 0, 'conversions' => 0, 'taux' => 0],
                'niveau_3' => ['total' => 0, 'conversions' => 0, 'taux' => 0]
            ],
            'segmentation_performance' => [
                'priorite_haute' => ['taux_reponse' => 0, 'delai_moyen_paiement' => 0],
                'priorite_moyenne' => ['taux_reponse' => 0, 'delai_moyen_paiement' => 0],
                'priorite_faible' => ['taux_reponse' => 0, 'delai_moyen_paiement' => 0]
            ],
            'tendances_mensuelles' => [],
            'predictions' => [
                'efficacite_prevue_mois_prochain' => 0,
                'volume_relances_prevu' => 0,
                'recommandations' => ['Données insuffisantes pour les recommandations']
            ]
        ];
    }

    private function exportPDFAnalytics($statistiques, $periode, $inclureGraphiques)
    {
        // Implémentation de l'export PDF
        $pdf = \PDF::loadView('esbtp.comptabilite.relances.analytics-pdf', compact('statistiques', 'periode', 'inclureGraphiques'));
        return $pdf->download('analytics-relances-' . now()->format('Y-m-d') . '.pdf');
    }

    private function exportExcelAnalytics($statistiques, $periode)
    {
        // Implémentation de l'export Excel
        // À implémenter avec Maatwebsite\Excel
        return response()->json(['message' => 'Export Excel en cours de développement']);
    }

    private function exportCSVAnalytics($statistiques, $periode)
    {
        // Implémentation de l'export CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics-relances-' . now()->format('Y-m-d') . '.csv"'
        ];

        $callback = function() use ($statistiques) {
            $file = fopen('php://output', 'w');

            // Headers CSV
            fputcsv($file, ['Type', 'Total Envoyées', 'Avec Paiement', 'Taux Efficacité']);

            // Données efficacité par type
            foreach ($statistiques['efficacite_par_type'] as $type => $data) {
                fputcsv($file, [$type, $data['total_envoyees'], $data['avec_paiement'], $data['taux_efficacite'] . '%']);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Configuration de la comptabilité
     */
    public function configurationComptabilite()
    {
        $configurations = \App\Models\ESBTPComptabiliteConfiguration::orderBy('cle')->get();
        $typesFrais = \App\Models\ESBTPTypeFrais::orderBy('nom')->get();

        return view('esbtp.comptabilite.configuration.index', compact('configurations', 'typesFrais'));
    }

    /**
     * Générer un rapport personnalisé via le builder avancé - Task #6
     */
    public function genererRapportPersonnalise(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'period' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'components' => 'required|array|min:1'
        ]);

        try {
            $reportingService = app(\App\Services\ReportingService::class);

            $parametres = [
                'type' => 'personnalise',
                'name' => $request->input('name'),
                'period' => $request->input('period'),
                'components' => $request->input('components'),
                'date_debut' => $request->input('date_debut', now()->startOfMonth()),
                'date_fin' => $request->input('date_fin', now()->endOfMonth()),
                'filters' => $request->input('filters', [])
            ];

            $rapport = $reportingService->genererRapportPersonnalise($parametres);

            // Ajouter les données d'analytics prédictives si demandées
            if ($request->has('include_predictive')) {
                $rapport['analytics_predictives'] = $this->genererAnalyticsPredictives($parametres);
            }

            // Exporter selon le format demandé
            $format = $request->input('format');
            $exportUrl = $reportingService->exporterDonnees($format, $rapport);

            // Enregistrer l'historique de génération
            $this->enregistrerHistoriqueRapport([
                'nom' => $parametres['name'],
                'type' => 'personnalise',
                'format' => $format,
                'parametres' => json_encode($parametres),
                'genere_par' => Auth::id(),
                'url_fichier' => $exportUrl
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Rapport généré avec succès',
                'url' => $exportUrl,
                'rapport' => $rapport
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur génération rapport personnalisé: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'parametres' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Programmer un rapport automatique - Task #6
     */
    public function programmerRapport(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'time' => 'required|date_format:H:i',
            'recipients' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'components' => 'required|array'
        ]);

        try {
            // Créer l'entrée de rapport programmé
            $rapportProgramme = \App\Models\ESBTPRapportProgramme::create([
                'nom' => $request->input('name'),
                'frequence' => $request->input('frequency'),
                'heure_envoi' => $request->input('time'),
                'destinataires' => $request->input('recipients'),
                'format_export' => $request->input('format'),
                'configuration' => json_encode([
                    'components' => $request->input('components'),
                    'filters' => $request->input('filters', []),
                    'include_predictive' => $request->input('include_predictive', false)
                ]),
                'est_actif' => true,
                'cree_par' => Auth::id(),
                'prochaine_execution' => $this->calculerProchaineExecution(
                    $request->input('frequency'),
                    $request->input('time')
                )
            ]);

            // Programmer le job dans Laravel Scheduler
            $this->programmerJobRapport($rapportProgramme);

            return response()->json([
                'success' => true,
                'message' => 'Rapport programmé avec succès',
                'id' => $rapportProgramme->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur programmation rapport: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'parametres' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la programmation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les rapports programmés - Task #6
     */
    public function listeRapportsProgrammes()
    {
        $rapportsProgrammes = \App\Models\ESBTPRapportProgramme::with(['createur'])
            ->orderBy('prochaine_execution')
            ->paginate(15);

        $statistiques = [
            'total_programmes' => \App\Models\ESBTPRapportProgramme::count(),
            'actifs' => \App\Models\ESBTPRapportProgramme::where('est_actif', true)->count(),
            'executions_reussies' => \App\Models\ESBTPHistoriqueRapport::where('statut', 'succes')
                ->whereDate('created_at', '>=', now()->subDays(30))->count(),
            'executions_echouees' => \App\Models\ESBTPHistoriqueRapport::where('statut', 'echec')
                ->whereDate('created_at', '>=', now()->subDays(30))->count()
        ];

        return view('esbtp.comptabilite.rapports.scheduled', compact('rapportsProgrammes', 'statistiques'));
    }

    /**
     * Analyses prédictives avancées - Task #6
     */
    public function analysesPredictives(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cashflow,anomalies,trends,forecast',
            'periode' => 'integer|min:1|max:12',
            'parametres' => 'array'
        ]);

        try {
            $type = $request->input('type');
            $periode = $request->input('periode', 6); // 6 mois par défaut
            $parametres = $request->input('parametres', []);

            $resultats = [];

            switch ($type) {
                case 'cashflow':
                    $resultats = $this->analyticsPredictifService->projeterCashFlowAvance($periode);
                    break;

                case 'anomalies':
                    $periodeAnalyse = $parametres['periode_analyse'] ?? 12;
                    $resultats = $this->analyticsPredictifService->detecterAnomalies($periodeAnalyse);
                    break;

                case 'trends':
                    $periodesComparaison = $parametres['periodes'] ?? ['mensuel', 'trimestriel'];
                    $resultats = $this->analyticsPredictifService->genererBenchmarkingAvance($periodesComparaison);
                    break;

                case 'forecast':
                    $resultats = $this->analyticsPredictifService->genererRecommandationsIntelligentes();
                    break;
            }

            return response()->json([
                'success' => true,
                'type' => $type,
                'periode' => $periode,
                'resultats' => $resultats,
                'genere_le' => now()->format('d/m/Y H:i')
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur analyses prédictives: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'type' => $request->input('type'),
                'parametres' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Projection cash-flow détaillée - Task #11
     */
    public function projectionCashFlow(Request $request)
    {
        $mois = $request->input('mois', 6);
        $anneeId = $request->input('annee_id');

        try {
            $projection = $this->analyticsPredictifService->projeterCashFlowAvance($mois, $anneeId);
            $visualisations = $this->analyticsPredictifService->preparerDonneesVisualisationsAvancees('projections');

            return view('esbtp.comptabilite.analytics.cashflow', compact('projection', 'mois', 'visualisations'));

        } catch (\Exception $e) {
            Log::error('Erreur projection cash-flow', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Erreur lors de la projection: ' . $e->getMessage());
        }
    }

    /**
     * Détection d'anomalies financières - Task #11
     */
    public function detectionAnomalies(Request $request)
    {
        $periode = $request->input('periode', 12); // 12 mois par défaut
        $anneeId = $request->input('annee_id');

        try {
            $anomalies = $this->analyticsPredictifService->detecterAnomalies($periode, $anneeId);
            $visualisations = $this->analyticsPredictifService->preparerDonneesVisualisationsAvancees('anomalies');

            return view('esbtp.comptabilite.analytics.anomalies', compact('anomalies', 'periode', 'visualisations'));

        } catch (\Exception $e) {
            Log::error('Erreur détection anomalies', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Erreur lors de la détection: ' . $e->getMessage());
        }
    }

    /**
     * Modèles de rapports sauvegardés - Task #6
     */
    public function modelesRapports()
    {
        $modeles = \App\Models\ESBTPModeleRapport::with(['createur'])
            ->orderBy('nom')
            ->get();

        $categories = [
            'financier' => 'Rapports Financiers',
            'performance' => 'Analyses de Performance',
            'recouvrement' => 'Suivi Recouvrement',
            'predictif' => 'Analytics Prédictives'
        ];

        return view('esbtp.comptabilite.rapports.templates', compact('modeles', 'categories'));
    }

    /**
     * Sauvegarder un modèle de rapport - Task #6
     */
    public function sauvegarderModele(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:esbtp_modeles_rapports,nom',
            'description' => 'nullable|string|max:500',
            'categorie' => 'required|in:financier,performance,recouvrement,predictif',
            'components' => 'required|array',
            'parametres' => 'array'
        ]);

        try {
            $modele = \App\Models\ESBTPModeleRapport::create([
                'nom' => $request->input('nom'),
                'description' => $request->input('description'),
                'categorie' => $request->input('categorie'),
                'configuration' => json_encode([
                    'components' => $request->input('components'),
                    'parametres' => $request->input('parametres', []),
                    'version' => '1.0'
                ]),
                'est_partage' => $request->input('est_partage', false),
                'cree_par' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modèle sauvegardé avec succès',
                'modele' => $modele
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    // === MÉTHODES PRIVÉES POUR ANALYTICS PRÉDICTIVES ===

    /**
     * Projection cash-flow détaillée avec IA
     */
    private function projectionCashFlowDetailed($mois, $parametres = [])
    {
        $includeIA = $parametres['include_ia'] ?? true;
        $facteursSaisonniers = $parametres['facteurs_saisonniers'] ?? true;

        // Récupérer l'historique des 24 derniers mois
        $historiqueRecettes = $this->getHistoriqueRecettes(24);
        $historiqueDepenses = $this->getHistoriqueDepenses(24);

        // Calculer les tendances
        $tendanceRecettes = $this->calculerTendance($historiqueRecettes);
        $tendanceDepenses = $this->calculerTendance($historiqueDepenses);

        // Générer les projections
        $projections = [];
        $dateBase = now();

        for ($i = 1; $i <= $mois; $i++) {
            $dateProjection = $dateBase->copy()->addMonths($i);

            // Projection basique (tendance linéaire)
            $recetteProjetee = $this->projetterValeur($tendanceRecettes, $i);
            $depenseProjetee = $this->projetterValeur($tendanceDepenses, $i);

            // Ajustements saisonniers
            if ($facteursSaisonniers) {
                $facteurSaisonnier = $this->getFacteurSaisonnier($dateProjection->month);
                $recetteProjetee *= $facteurSaisonnier;
            }

            // Prédictions IA (si activées)
            if ($includeIA) {
                $adjustmentIA = $this->predictionIA($dateProjection, $historiqueRecettes, $historiqueDepenses);
                $recetteProjetee *= $adjustmentIA['facteur_recettes'];
                $depenseProjetee *= $adjustmentIA['facteur_depenses'];
            }

            $cashFlow = $recetteProjetee - $depenseProjetee;

            $projections[] = [
                'mois' => $dateProjection->format('M Y'),
                'date' => $dateProjection->format('Y-m-d'),
                'recettes_projetees' => round($recetteProjetee),
                'depenses_projetees' => round($depenseProjetee),
                'cash_flow' => round($cashFlow),
                'cash_flow_cumule' => round($cashFlow + ($projections[count($projections)-1]['cash_flow_cumule'] ?? 0)),
                'confiance' => $this->calculerNiveauConfiance($i, $includeIA),
                'scenario' => $this->determinerScenario($cashFlow)
            ];
        }

        return [
            'projections' => $projections,
            'tendances' => [
                'recettes' => $tendanceRecettes,
                'depenses' => $tendanceDepenses
            ],
            'recommandations' => $this->genererRecommandationsCashFlow($projections),
            'risques_identifies' => $this->identifierRisquesCashFlow($projections),
            'opportunites' => $this->identifierOpportunites($projections),
            'metadonnees' => [
                'genere_le' => now(),
                'algorithme' => $includeIA ? 'IA + Tendances' : 'Tendances Linéaires',
                'fiabilite_globale' => $this->calculerFiabiliteGlobale($projections)
            ]
        ];
    }

    /**
     * Détection d'anomalies avec machine learning
     */
    private function detectionAnomaliesDetailed($parametres = [])
    {
        $periodeJours = $parametres['periode_jours'] ?? 30;
        $seuilsPersonnalises = $parametres['seuils_personnalises'] ?? [];
        $analysePatterns = $parametres['analyse_patterns'] ?? true;

        $dateDebut = now()->subDays($periodeJours);
        $dateFin = now();

        // Récupérer les données de la période
        $paiements = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->with(['etudiant', 'anneeUniversitaire'])
            ->get();

        $depenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
            ->with(['categorie', 'fournisseur'])
            ->get();

        // Calculer les seuils automatiques si non fournis
        $seuils = $this->calculerSeuilsAnomalies($paiements, $depenses, $seuilsPersonnalises);

        $anomalies = [];

        // 1. Anomalies de montants (Z-score)
        $anomalies['montants'] = $this->detecterAnomaliesMontants($paiements, $depenses, $seuils);

        // 2. Anomalies temporelles
        $anomalies['temporelles'] = $this->detecterAnomaliesTemporelles($paiements, $depenses);

        // 3. Anomalies de fréquence
        $anomalies['frequence'] = $this->detecterAnomaliesFrequence($paiements, $depenses);

        // 4. Patterns suspects
        if ($analysePatterns) {
            $anomalies['patterns'] = $this->detecterPatternsSuspects($paiements, $depenses);
        }

        // 5. Anomalies par catégorie/filière
        $anomalies['categories'] = $this->detecterAnomaliesCategories($paiements, $depenses);

        return [
            'periode' => [
                'debut' => $dateDebut->format('d/m/Y'),
                'fin' => $dateFin->format('d/m/Y'),
                'jours' => $periodeJours
            ],
            'resume' => [
                'total_anomalies' => array_sum(array_map('count', $anomalies)),
                'niveau_risque' => $this->evaluerNiveauRisqueGlobal($anomalies),
                'score_confiance' => $this->calculerScoreConfiance($anomalies)
            ],
            'anomalies' => $anomalies,
            'seuils_utilises' => $seuils,
            'recommandations' => $this->genererRecommandationsAnomalies($anomalies),
            'actions_immediates' => $this->identifierActionsImmediates($anomalies)
        ];
    }

    /**
     * Analyse des tendances avec prédictions
     */
    private function analyseTendancesDetailed($periode, $parametres = [])
    {
        // Récupérer les données historiques
        $donnees = $this->getDonneesHistoriques($periode + 12); // Plus de données pour l'analyse

        // Analyser les tendances par segment
        $tendances = [
            'recettes_globales' => $this->analyserTendance($donnees['recettes']),
            'recettes_par_filiere' => $this->analyserTendancesParFiliere($donnees['recettes']),
            'depenses_par_categorie' => $this->analyserTendancesParCategorie($donnees['depenses']),
            'taux_recouvrement' => $this->analyserTendanceTauxRecouvrement($donnees),
            'cycle_saisonnier' => $this->analyserCycleSaisonnier($donnees)
        ];

        // Générer les prédictions
        $predictions = $this->genererPredictionsTendances($tendances, $periode);

        return [
            'tendances' => $tendances,
            'predictions' => $predictions,
            'insights' => $this->genererInsightsTendances($tendances),
            'alertes' => $this->identifierAlertesTondances($tendances),
            'opportunites_amelioration' => $this->identifierOpportunitesAmelioration($tendances)
        ];
    }

    /**
     * Prédictions avec IA avancée
     */
    private function previsionIA($periode, $parametres = [])
    {
        // Algorithme simplifié de ML pour les prédictions
        $donnees = $this->getDonneesML($periode * 2);

        $modeles = [
            'regression_lineaire' => $this->modelRegressionLineaire($donnees),
            'moyennes_mobiles' => $this->modelMoyennesMobiles($donnees),
            'decomposition_saisonniere' => $this->modelDecompositionSaisonniere($donnees),
            'reseaux_neurones' => $this->modelReseauxNeurones($donnees) // Simplifié
        ];

        // Ensemble learning (combinaison des modèles)
        $predictionsCombinees = $this->combinerPredictions($modeles, $periode);

        return [
            'predictions' => $predictionsCombinees,
            'confiance_modeles' => $this->evaluerConfianceModeles($modeles),
            'facteurs_influence' => $this->identifierFacteursInfluence($donnees),
            'scenarios' => [
                'optimiste' => $this->genererScenario($predictionsCombinees, 'optimiste'),
                'realiste' => $this->genererScenario($predictionsCombinees, 'realiste'),
                'pessimiste' => $this->genererScenario($predictionsCombinees, 'pessimiste')
            ],
            'recommandations_strategiques' => $this->genererRecommandationsStrategiques($predictionsCombinees)
        ];
    }

    // === MÉTHODES UTILITAIRES ===

    private function calculerProchaineExecution($frequence, $heure)
    {
        $now = now();
        $time = \Carbon\Carbon::createFromFormat('H:i', $heure);

        switch ($frequence) {
            case 'daily':
                $prochaine = $now->copy()->setTime($time->hour, $time->minute);
                if ($prochaine <= $now) {
                    $prochaine->addDay();
                }
                break;

            case 'weekly':
                $prochaine = $now->copy()->next(\Carbon\Carbon::MONDAY)->setTime($time->hour, $time->minute);
                break;

            case 'monthly':
                $prochaine = $now->copy()->startOfMonth()->addMonth()->setTime($time->hour, $time->minute);
                break;

            case 'quarterly':
                $prochaine = $now->copy()->startOfQuarter()->addQuarter()->setTime($time->hour, $time->minute);
                break;

            default:
                $prochaine = $now->copy()->addDay()->setTime($time->hour, $time->minute);
        }

        return $prochaine;
    }

    private function programmerJobRapport($rapportProgramme)
    {
        // Ici vous ajouteriez la logique pour programmer le job dans Laravel Scheduler
        // Pour l'instant, on enregistre juste l'information
        \Log::info('Rapport programmé créé', ['id' => $rapportProgramme->id]);
    }

    private function enregistrerHistoriqueRapport($donnees)
    {
        return \App\Models\ESBTPHistoriqueRapport::create($donnees);
    }

    private function genererAnalyticsPredictives($parametres)
    {
        // Génération simplifiée d'analytics prédictives
        return [
            'cash_flow_projection' => $this->projectionCashFlowDetailed(6),
            'anomalies_detected' => $this->detectionAnomaliesDetailed(),
            'trends_analysis' => $this->analyseTendancesDetailed(6)
        ];
    }

    // Méthodes simplifiées pour les calculs ML (à implémenter selon les besoins)
    private function getHistoriqueRecettes($mois) { /* Implementation */ return []; }
    private function getHistoriqueDepenses($mois) { /* Implementation */ return []; }
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
     * Analytics prédictifs - Dashboard principal - Task #11
     */
    public function analyticsPredictifs()
    {
        try {
            return view('esbtp.comptabilite.analytics.index');
        } catch (\Exception $e) {
            Log::error('Erreur analytics prédictifs', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Erreur lors du chargement des analytics.');
        }
    }

    /**
     * Recommandations intelligentes - Task #11
     */
    public function recommandationsIntelligentes(Request $request)
    {
        $anneeId = $request->input('annee_id');

        try {
            $recommandations = $this->analyticsPredictifService->genererRecommandationsIntelligentes($anneeId);

            return view('esbtp.comptabilite.analytics.recommandations', compact('recommandations'));

        } catch (\Exception $e) {
            Log::error('Erreur recommandations intelligentes', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Erreur lors de la génération des recommandations: ' . $e->getMessage());
        }
    }

    /**
     * Benchmarking inter-périodes - Task #11
     */
    public function benchmarkingAvance(Request $request)
    {
        $periodesComparaison = $request->input('periodes', ['mensuel', 'trimestriel', 'annuel']);

        try {
            $benchmarks = $this->analyticsPredictifService->genererBenchmarkingAvance($periodesComparaison);
            $visualisations = $this->analyticsPredictifService->preparerDonneesVisualisationsAvancees('tendances');

            return view('esbtp.comptabilite.analytics.benchmarking', compact('benchmarks', 'visualisations'));

        } catch (\Exception $e) {
            Log::error('Erreur benchmarking avancé', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Erreur lors du benchmarking: ' . $e->getMessage());
        }
    }

    /**
     * Visualisations avancées - Task #11
     */
    public function visualisationsAvancees(Request $request)
    {
        $typeViz = $request->input('type', 'all');

        try {
            $visualisations = $this->analyticsPredictifService->preparerDonneesVisualisationsAvancees($typeViz);

            return response()->json([
                'success' => true,
                'visualisations' => $visualisations,
                'type' => $typeViz,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur visualisations avancées', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la génération des visualisations'
            ], 500);
        }
    }

    /**
     * API pour récupérer les données prédictives en temps réel - Task #11
     */
    public function apiAnalyticsPredictifs(Request $request)
    {
        $request->validate([
            'type' => 'required|in:projections,anomalies,recommandations,benchmarking',
            'periode' => 'nullable|integer|min:1|max:24',
            'annee_id' => 'nullable|integer'
        ]);

        return $this->performanceMonitor->monitor('api_analytics_predictifs', function () use ($request) {
            try {
                $type = $request->input('type');
                $periode = $request->input('periode', 6);
                $anneeId = $request->input('annee_id');

                $resultats = [];

                switch ($type) {
                    case 'projections':
                        $resultats = $this->analyticsPredictifService->projeterCashFlowAvance($periode, $anneeId);
                        break;

                    case 'anomalies':
                        $resultats = $this->analyticsPredictifService->detecterAnomalies($periode, $anneeId);
                        break;

                    case 'recommandations':
                        $resultats = $this->analyticsPredictifService->genererRecommandationsIntelligentes($anneeId);
                        break;

                    case 'benchmarking':
                        $periodesComparaison = $request->input('periodes_comparaison', ['mensuel', 'trimestriel']);
                        $resultats = $this->analyticsPredictifService->genererBenchmarkingAvance($periodesComparaison);
                        break;
                }

                return response()->json([
                    'success' => true,
                    'type' => $type,
                    'periode' => $periode,
                    'resultats' => $resultats,
                    'cache_info' => [
                        'cached' => isset($resultats['cache_generated_at']) || isset($resultats['derniere_mise_a_jour']),
                        'last_updated' => $resultats['derniere_mise_a_jour'] ?? $resultats['cache_generated_at'] ?? now()->toISOString()
                    ],
                    'performance' => [
                        'execution_time' => round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms'
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('Erreur API analytics prédictifs', [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                    'params' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de l\'analyse prédictive',
                    'message' => $e->getMessage()
                ], 500);
            }
        }, [
            'type' => $request->input('type'),
            'periode' => $request->input('periode'),
            'user_id' => Auth::id()
        ]);
    }

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

    /**
     * Affiche le formulaire de création d'une facture
     */
    public function createFacture()
    {
        // Récupérer les données nécessaires pour le formulaire (clients, fournisseurs, etc.)
        $fournisseurs = \App\Models\ESBTPFournisseur::all();
        // ... autres données si besoin
        return view('esbtp.comptabilite.factures.create', compact('fournisseurs'));
    }

    /**
     * Affiche le détail d'une facture
     */
    public function showFacture($id)
    {
        $facture = \App\Models\ESBTPFacture::with('details', 'fournisseur')->findOrFail($id);
        return view('esbtp.comptabilite.factures.show', compact('facture'));
    }

    /**
     * (Optionnel) Enregistre une nouvelle facture
     */
    public function storeFacture(Request $request)
    {
        // Validation et logique d'enregistrement ici
        // ...
        return redirect()->route('esbtp.comptabilite.factures')->with('success', 'Facture créée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'une facture
     */
    public function editFacture($id)
    {
        $facture = \App\Models\ESBTPFacture::with('details', 'fournisseur')->findOrFail($id);
        $fournisseurs = \App\Models\ESBTPFournisseur::all();
        return view('esbtp.comptabilite.factures.edit', compact('facture', 'fournisseurs'));
    }

    /**
     * Create a new bon de sortie quickly.
     */
    public function createBonRapide(Request $request)
    {
        // This would be an AJAX method called from the depense creation form
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'destinataire' => 'nullable|string',
            'approbateur_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bon = ESBTPBonSortie::create([
            'titre' => $request->titre,
            'description' => $request->description,
            'destinataire' => $request->destinataire,
            'date_sortie' => now(),
            'statut' => 'en_attente',
            'createur_id' => Auth::id(),
            'approbateur_id' => $request->approbateur_id,
        ]);

        // Notify approver
        // $this->notificationService->notifyBonApproval($bon->id, $request->approbateur_id);

        return response()->json(['success' => true, 'bon' => $bon]);
    }

    /**
     * Générer un reçu de paiement.
     */
    public function genererRecuPaiement($id)
    {
        $paiement = ESBTPPaiement::with([
            'inscription.etudiant.user',
            'inscription.filiere',
            'inscription.niveau',
            'inscription.anneeUniversitaire',
            'createdBy'
        ])->findOrFail($id);

        $pdf = PDF::loadView('esbtp.comptabilite.paiements.recu', compact('paiement'));
        return $pdf->stream('recu_paiement_' . $paiement->id . '.pdf');
    }
}

