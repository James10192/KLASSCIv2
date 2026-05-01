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
use App\Services\BonDepenseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPComptabiliteAnalyticsController extends Controller
{
    /**
     * Constructeur avec injection des services optimisés
     */
    public function __construct(
        ComptabiliteService $comptabiliteService,
        BonDepenseService $bonDepenseService
    ) {
        $this->comptabiliteService = $comptabiliteService;
        $this->bonDepenseService = $bonDepenseService;

        $this->middleware('auth');
        $this->middleware('comptabilite.access');
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


















































































}
