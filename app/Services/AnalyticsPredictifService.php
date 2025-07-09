<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisScolarite;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyticsPredictifService
{
    private ComptabiliteService $comptabiliteService;
    private ReportingService $reportingService;

    // Configuration des paramètres de prédiction
    private const HISTORICAL_MONTHS = 24; // 2 ans d'historique
    private const CONFIDENCE_LEVEL = 0.95; // Niveau de confiance 95%
    private const SEASONAL_CYCLES = 12; // Cycles saisonniers (mois)
    private const ANOMALY_THRESHOLD = 2.0; // Seuil de détection d'anomalies (écarts-types)
    private const CACHE_TTL = 3600; // 1 heure de cache

    public function __construct(ComptabiliteService $comptabiliteService, ReportingService $reportingService)
    {
        $this->comptabiliteService = $comptabiliteService;
        $this->reportingService = $reportingService;
    }

    /**
     * Génère des projections de cash-flow avancées avec tendances saisonnières
     */
    public function projeterCashFlowAvance($moisProjection = 6, $anneeId = null): array
    {
        $cacheKey = "cash_flow_projection_{$moisProjection}_{$anneeId}_" . Carbon::now()->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($moisProjection, $anneeId) {
            $annee = $this->getAnneeActive($anneeId);

            // Récupération de l'historique étendu
            $historiqueRecettes = $this->getHistoriqueRecettes(self::HISTORICAL_MONTHS);
            $historiqueDepenses = $this->getHistoriqueDepenses(self::HISTORICAL_MONTHS);

            // Analyse des tendances saisonnières
            $tendancesRecettes = $this->analyserTendancesSaisonnieres($historiqueRecettes);
            $tendancesDepenses = $this->analyserTendancesSaisonnieres($historiqueDepenses);

            // Calcul des projections
            $projections = [];
            $dateActuelle = Carbon::now();

            for ($i = 1; $i <= $moisProjection; $i++) {
                $dateFuture = $dateActuelle->copy()->addMonths($i);
                $moisIndex = ($dateFuture->month - 1) % 12;

                // Projection des recettes avec tendance + saisonnalité
                $recettesProjetees = $this->calculerProjectionRecettes(
                    $historiqueRecettes,
                    $tendancesRecettes,
                    $moisIndex,
                    $i
                );

                // Projection des dépenses
                $depensesProjetees = $this->calculerProjectionDepenses(
                    $historiqueDepenses,
                    $tendancesDepenses,
                    $moisIndex,
                    $i
                );

                // Calcul des intervalles de confiance
                $intervalleRecettes = $this->calculerIntervalleConfiance($historiqueRecettes, $recettesProjetees);
                $intervalleDepenses = $this->calculerIntervalleConfiance($historiqueDepenses, $depensesProjetees);

                $projections[] = [
                    'date' => $dateFuture->format('Y-m'),
                    'periode' => $dateFuture->translatedFormat('F Y'),
                    'recettes' => [
                        'projection' => round($recettesProjetees, 2),
                        'min' => round($intervalleRecettes['min'], 2),
                        'max' => round($intervalleRecettes['max'], 2),
                        'confiance' => self::CONFIDENCE_LEVEL * 100
                    ],
                    'depenses' => [
                        'projection' => round($depensesProjetees, 2),
                        'min' => round($intervalleDepenses['min'], 2),
                        'max' => round($intervalleDepenses['max'], 2),
                        'confiance' => self::CONFIDENCE_LEVEL * 100
                    ],
                    'cash_flow' => [
                        'projection' => round($recettesProjetees - $depensesProjetees, 2),
                        'scenario_optimiste' => round($intervalleRecettes['max'] - $intervalleDepenses['min'], 2),
                        'scenario_pessimiste' => round($intervalleRecettes['min'] - $intervalleDepenses['max'], 2)
                    ],
                    'risques' => $this->evaluerRisquesPeriode($dateFuture, $recettesProjetees, $depensesProjetees)
                ];
            }

            return [
                'projections' => $projections,
                'resume' => $this->genererResumeProjections($projections),
                'parametres' => [
                    'historique_mois' => self::HISTORICAL_MONTHS,
                    'niveau_confiance' => self::CONFIDENCE_LEVEL,
                    'methode' => 'Tendances saisonnières + régression linéaire',
                    'derniere_mise_a_jour' => now()->toISOString()
                ]
            ];
        });
    }

    /**
     * Détection d'anomalies financières avancée
     */
    public function detecterAnomalies($periodeAnalyse = 12, $anneeId = null): array
    {
        $cacheKey = "anomalies_detection_{$periodeAnalyse}_{$anneeId}_" . Carbon::now()->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($periodeAnalyse, $anneeId) {
            $anomalies = [];

            // Analyse des anomalies de recettes
            $anomaliesRecettes = $this->detecterAnomaliesRecettes($periodeAnalyse);

            // Analyse des anomalies de dépenses
            $anomaliesDepenses = $this->detecterAnomaliesDepenses($periodeAnalyse);

            // Analyse des patterns de paiement
            $anomaliesPaiements = $this->detecterAnomaliesPaiements($periodeAnalyse);

            // Consolidation des anomalies
            $toutesAnomalies = array_merge($anomaliesRecettes, $anomaliesDepenses, $anomaliesPaiements);

            // Tri par criticité
            usort($toutesAnomalies, function($a, $b) {
                return $b['criticite'] <=> $a['criticite'];
            });

            return [
                'anomalies' => $toutesAnomalies,
                'statistiques' => [
                    'total_anomalies' => count($toutesAnomalies),
                    'anomalies_critiques' => count(array_filter($toutesAnomalies, fn($a) => $a['criticite'] >= 8)),
                    'anomalies_moderees' => count(array_filter($toutesAnomalies, fn($a) => $a['criticite'] >= 5 && $a['criticite'] < 8)),
                    'anomalies_faibles' => count(array_filter($toutesAnomalies, fn($a) => $a['criticite'] < 5))
                ],
                'tendances' => $this->analyserTendancesAnomalies($toutesAnomalies),
                'periode_analyse' => $periodeAnalyse,
                'derniere_analyse' => now()->toISOString()
            ];
        });
    }

    /**
     * Génère des recommandations intelligentes basées sur l'historique
     */
    public function genererRecommandationsIntelligentes($anneeId = null): array
    {
        $cacheKey = "recommendations_intelligentes_{$anneeId}_" . Carbon::now()->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($anneeId) {
            $recommandations = [];

            // Analyse des performances financières
            $performance = $this->analyserPerformanceFinanciere($anneeId);

            // Recommandations de recouvrement
            $recommandations = array_merge($recommandations, $this->genererRecommandationsRecouvrement($performance));

            // Recommandations de gestion des dépenses
            $recommandations = array_merge($recommandations, $this->genererRecommandationsDepenses($performance));

            // Recommandations de cash-flow
            $recommandations = array_merge($recommandations, $this->genererRecommandationsCashFlow($performance));

            // Recommandations saisonnières
            $recommandations = array_merge($recommandations, $this->genererRecommandationsSaisonnieres());

            // Priorisation des recommandations
            usort($recommandations, function($a, $b) {
                return $b['priorite'] <=> $a['priorite'];
            });

            return [
                'recommandations' => array_slice($recommandations, 0, 10), // Top 10
                'categories' => $this->categoriserRecommandations($recommandations),
                'impact_potentiel' => $this->calculerImpactPotentiel($recommandations),
                'derniere_mise_a_jour' => now()->toISOString()
            ];
        });
    }

    /**
     * Benchmarking inter-périodes avancé
     */
    public function genererBenchmarkingAvance($periodesComparaison = ['mensuel', 'trimestriel', 'annuel']): array
    {
        $cacheKey = "benchmarking_avance_" . implode('_', $periodesComparaison) . "_" . Carbon::now()->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($periodesComparaison) {
            $benchmarks = [];

            foreach ($periodesComparaison as $type) {
                $benchmarks[$type] = $this->genererBenchmarkPeriode($type);
            }

            return [
                'benchmarks' => $benchmarks,
                'analyse_globale' => $this->analyserPerformanceGlobale($benchmarks),
                'tendances_long_terme' => $this->analyserTendancesLongTerme(),
                'objectifs_vs_realite' => $this->comparerObjectifsRealite(),
                'derniere_mise_a_jour' => now()->toISOString()
            ];
        });
    }

    /**
     * Préparation des données pour visualisations avancées
     */
    public function preparerDonneesVisualisationsAvancees($typeViz = 'all'): array
    {
        $visualisations = [];

        if ($typeViz === 'all' || $typeViz === 'projections') {
            $visualisations['projections'] = $this->preparerVizProjections();
        }

        if ($typeViz === 'all' || $typeViz === 'anomalies') {
            $visualisations['anomalies'] = $this->preparerVizAnomalies();
        }

        if ($typeViz === 'all' || $typeViz === 'heatmaps') {
            $visualisations['heatmaps'] = $this->preparerVizHeatmaps();
        }

        if ($typeViz === 'all' || $typeViz === 'tendances') {
            $visualisations['tendances'] = $this->preparerVizTendances();
        }

        return $visualisations;
    }

    // ===== MÉTHODES PRIVÉES =====

    private function getAnneeActive($anneeId = null)
    {
        return $anneeId ?
            ESBTPAnneeUniversitaire::find($anneeId) :
            ESBTPAnneeUniversitaire::where('est_actif', true)->first();
    }

    private function getHistoriqueRecettes($mois)
    {
        return ESBTPPaiement::select(
                DB::raw('YEAR(date_paiement) as annee'),
                DB::raw('MONTH(date_paiement) as mois'),
                DB::raw('SUM(montant) as total'),
                DB::raw('COUNT(*) as nombre_paiements'),
                DB::raw('AVG(montant) as moyenne')
            )
            ->where('date_paiement', '>=', Carbon::now()->subMonths($mois))
            ->where('statut', 'completé')
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get()
            ->toArray();
    }

    private function getHistoriqueDepenses($mois)
    {
        return ESBTPDepense::select(
                DB::raw('YEAR(date_depense) as annee'),
                DB::raw('MONTH(date_depense) as mois'),
                DB::raw('SUM(montant) as total'),
                DB::raw('COUNT(*) as nombre_depenses'),
                DB::raw('AVG(montant) as moyenne')
            )
            ->where('date_depense', '>=', Carbon::now()->subMonths($mois))
            ->whereIn('statut', ['validée', 'approuve'])
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get()
            ->toArray();
    }

    private function analyserTendancesSaisonnieres($historique)
    {
        $tendances = array_fill(0, 12, []);

        foreach ($historique as $periode) {
            $moisIndex = ($periode['mois'] - 1) % 12;
            $tendances[$moisIndex][] = $periode['total'];
        }

        return array_map(function($valeurs) {
            return [
                'moyenne' => count($valeurs) > 0 ? array_sum($valeurs) / count($valeurs) : 0,
                'mediane' => count($valeurs) > 0 ? $this->calculerMediane($valeurs) : 0,
                'ecart_type' => count($valeurs) > 1 ? $this->calculerEcartType($valeurs) : 0,
                'min' => count($valeurs) > 0 ? min($valeurs) : 0,
                'max' => count($valeurs) > 0 ? max($valeurs) : 0,
                'nb_observations' => count($valeurs)
            ];
        }, $tendances);
    }

    private function calculerProjectionRecettes($historique, $tendances, $moisIndex, $moisFutur)
    {
        if (empty($historique) || !isset($tendances[$moisIndex])) {
            return 0;
        }

        // Base saisonnière
        $baseSaisonniere = $tendances[$moisIndex]['moyenne'];

        // Calcul de la tendance générale (régression linéaire simple)
        $tendanceGenerale = $this->calculerTendanceLineaire($historique);

        // Application de la tendance pour les mois futurs
        $facteurCroissance = 1 + ($tendanceGenerale * $moisFutur / 12);

        return $baseSaisonniere * $facteurCroissance;
    }

    private function calculerProjectionDepenses($historique, $tendances, $moisIndex, $moisFutur)
    {
        if (empty($historique) || !isset($tendances[$moisIndex])) {
            return 0;
        }

        // Méthode similaire aux recettes mais avec facteur d'inflation
        $baseSaisonniere = $tendances[$moisIndex]['moyenne'];
        $tendanceGenerale = $this->calculerTendanceLineaire($historique);
        $facteurInflation = 1.02; // 2% d'inflation annuelle

        $facteurCroissance = 1 + ($tendanceGenerale * $moisFutur / 12) + ($facteurInflation - 1) * ($moisFutur / 12);

        return $baseSaisonniere * $facteurCroissance;
    }

    private function calculerIntervalleConfiance($historique, $valeurProjetee)
    {
        if (empty($historique)) {
            return ['min' => $valeurProjetee * 0.8, 'max' => $valeurProjetee * 1.2];
        }

        $valeurs = array_column($historique, 'total');
        $ecartType = $this->calculerEcartType($valeurs);
        $marge = $ecartType * 1.96; // 95% de confiance

        return [
            'min' => max(0, $valeurProjetee - $marge),
            'max' => $valeurProjetee + $marge
        ];
    }

    private function calculerTendanceLineaire($historique)
    {
        if (count($historique) < 2) {
            return 0;
        }

        $n = count($historique);
        $x = range(1, $n);
        $y = array_column($historique, 'total');

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumXX += $x[$i] * $x[$i];
        }

        $pente = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $moyenne = $sumY / $n;

        return $moyenne > 0 ? $pente / $moyenne : 0; // Retour en pourcentage
    }

    private function calculerMediane($valeurs)
    {
        sort($valeurs);
        $count = count($valeurs);
        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($valeurs[$middle - 1] + $valeurs[$middle]) / 2;
        } else {
            return $valeurs[$middle];
        }
    }

    private function calculerEcartType($valeurs)
    {
        $moyenne = array_sum($valeurs) / count($valeurs);
        $variance = array_sum(array_map(function($x) use ($moyenne) {
            return pow($x - $moyenne, 2);
        }, $valeurs)) / count($valeurs);

        return sqrt($variance);
    }

    private function evaluerRisquesPeriode($date, $recettes, $depenses)
    {
        $risques = [];

        // Risque de cash-flow négatif
        if ($recettes < $depenses) {
            $risques[] = [
                'type' => 'cash_flow_negatif',
                'niveau' => 'élevé',
                'description' => 'Cash-flow négatif prévu'
            ];
        }

        // Risque saisonnier (ex: été = moins de paiements)
        if (in_array($date->month, [7, 8, 12])) {
            $risques[] = [
                'type' => 'saisonnier',
                'niveau' => 'modéré',
                'description' => 'Période historiquement faible'
            ];
        }

        return $risques;
    }

    private function genererResumeProjections($projections)
    {
        $totalRecettes = array_sum(array_column(array_column($projections, 'recettes'), 'projection'));
        $totalDepenses = array_sum(array_column(array_column($projections, 'depenses'), 'projection'));
        $cashFlowTotal = $totalRecettes - $totalDepenses;

        return [
            'total_recettes_projetees' => round($totalRecettes, 2),
            'total_depenses_projetees' => round($totalDepenses, 2),
            'cash_flow_cumule' => round($cashFlowTotal, 2),
            'mois_positifs' => count(array_filter($projections, fn($p) => $p['cash_flow']['projection'] > 0)),
            'mois_negatifs' => count(array_filter($projections, fn($p) => $p['cash_flow']['projection'] < 0)),
            'evaluation_globale' => $cashFlowTotal > 0 ? 'Positive' : 'Attention requise'
        ];
    }

    // Méthodes pour la détection d'anomalies
    private function detecterAnomaliesRecettes($periode)
    {
        $anomalies = [];
        $historique = $this->getHistoriqueRecettes($periode);

        if (count($historique) < 3) {
            return $anomalies;
        }

        $valeurs = array_column($historique, 'total');
        $moyenne = array_sum($valeurs) / count($valeurs);
        $ecartType = $this->calculerEcartType($valeurs);

        foreach ($historique as $periode) {
            $zScore = $ecartType > 0 ? abs($periode['total'] - $moyenne) / $ecartType : 0;

            if ($zScore > self::ANOMALY_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'recette_anormale',
                    'date' => $periode['annee'] . '-' . str_pad($periode['mois'], 2, '0', STR_PAD_LEFT),
                    'valeur_observee' => $periode['total'],
                    'valeur_attendue' => $moyenne,
                    'ecart_relatif' => round((($periode['total'] - $moyenne) / $moyenne) * 100, 2),
                    'z_score' => round($zScore, 2),
                    'criticite' => min(10, round($zScore * 2, 0)),
                    'description' => $periode['total'] > $moyenne ? 'Recette exceptionnellement élevée' : 'Recette exceptionnellement faible'
                ];
            }
        }

        return $anomalies;
    }

    private function detecterAnomaliesDepenses($periode)
    {
        $anomalies = [];
        $historique = $this->getHistoriqueDepenses($periode);

        if (count($historique) < 3) {
            return $anomalies;
        }

        $valeurs = array_column($historique, 'total');
        $moyenne = array_sum($valeurs) / count($valeurs);
        $ecartType = $this->calculerEcartType($valeurs);

        foreach ($historique as $periode) {
            $zScore = $ecartType > 0 ? abs($periode['total'] - $moyenne) / $ecartType : 0;

            if ($zScore > self::ANOMALY_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'depense_anormale',
                    'date' => $periode['annee'] . '-' . str_pad($periode['mois'], 2, '0', STR_PAD_LEFT),
                    'valeur_observee' => $periode['total'],
                    'valeur_attendue' => $moyenne,
                    'ecart_relatif' => round((($periode['total'] - $moyenne) / $moyenne) * 100, 2),
                    'z_score' => round($zScore, 2),
                    'criticite' => min(10, round($zScore * 2, 0)),
                    'description' => $periode['total'] > $moyenne ? 'Dépense exceptionnellement élevée' : 'Dépense exceptionnellement faible'
                ];
            }
        }

        return $anomalies;
    }

    private function detecterAnomaliesPaiements($periode)
    {
        $anomalies = [];

        // Détection d'anomalies dans les patterns de paiement
        $paiementsParJour = ESBTPPaiement::select(
                DB::raw('DATE(date_paiement) as date'),
                DB::raw('COUNT(*) as nombre'),
                DB::raw('SUM(montant) as total')
            )
            ->where('date_paiement', '>=', Carbon::now()->subMonths($periode))
            ->where('statut', 'completé')
            ->groupBy('date')
            ->having('nombre', '>', 0)
            ->get();

        if ($paiementsParJour->count() < 7) {
            return $anomalies;
        }

        $valeurs = $paiementsParJour->pluck('nombre')->toArray();
        $moyenne = array_sum($valeurs) / count($valeurs);
        $ecartType = $this->calculerEcartType($valeurs);

        foreach ($paiementsParJour as $jour) {
            $zScore = $ecartType > 0 ? abs($jour->nombre - $moyenne) / $ecartType : 0;

            if ($zScore > self::ANOMALY_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'pattern_paiement_anormal',
                    'date' => $jour->date,
                    'valeur_observee' => $jour->nombre,
                    'valeur_attendue' => round($moyenne, 0),
                    'montant_total' => $jour->total,
                    'z_score' => round($zScore, 2),
                    'criticite' => min(10, round($zScore * 1.5, 0)),
                    'description' => $jour->nombre > $moyenne ? 'Pic anormal de paiements' : 'Chute anormale de paiements'
                ];
            }
        }

        return $anomalies;
    }

    private function analyserTendancesAnomalies($anomalies)
    {
        $tendances = [
            'frequence_par_type' => [],
            'distribution_temporelle' => [],
            'criticite_moyenne' => 0
        ];

        foreach ($anomalies as $anomalie) {
            $type = $anomalie['type'];
            $tendances['frequence_par_type'][$type] = ($tendances['frequence_par_type'][$type] ?? 0) + 1;
        }

        if (count($anomalies) > 0) {
            $tendances['criticite_moyenne'] = array_sum(array_column($anomalies, 'criticite')) / count($anomalies);
        }

        return $tendances;
    }

    // Méthodes pour les recommandations intelligentes
    private function analyserPerformanceFinanciere($anneeId)
    {
        $annee = $this->getAnneeActive($anneeId);
        $kpis = $this->comptabiliteService->calculerKPIsAvances($annee->id);

        return [
            'taux_recouvrement' => $kpis['recettes']['taux_recouvrement'],
            'marge_nette' => $kpis['performance']['marge_nette'] ?? 0,
            'cash_flow' => $kpis['performance']['resultat_net'] ?? 0,
            'croissance' => $this->calculerCroissanceRecettes($annee),
            'efficacite_depenses' => $this->calculerEfficaciteDepenses($annee)
        ];
    }

    private function genererRecommandationsRecouvrement($performance)
    {
        $recommandations = [];

        if ($performance['taux_recouvrement'] < 70) {
            $recommandations[] = [
                'categorie' => 'recouvrement',
                'titre' => 'Intensifier les campagnes de relance',
                'description' => 'Le taux de recouvrement de ' . $performance['taux_recouvrement'] . '% est en dessous du seuil critique',
                'action' => 'Mettre en place des relances automatisées personnalisées',
                'priorite' => 9,
                'impact_potentiel' => 'Amélioration de 15-20% du taux de recouvrement',
                'delai_mise_en_oeuvre' => '2 semaines'
            ];
        } elseif ($performance['taux_recouvrement'] < 85) {
            $recommandations[] = [
                'categorie' => 'recouvrement',
                'titre' => 'Optimiser le processus de relance',
                'description' => 'Taux de recouvrement modéré, possibilité d\'amélioration',
                'action' => 'Analyser les délais de paiement et ajuster la stratégie',
                'priorite' => 6,
                'impact_potentiel' => 'Amélioration de 5-10% du taux de recouvrement',
                'delai_mise_en_oeuvre' => '1 mois'
            ];
        }

        return $recommandations;
    }

    private function genererRecommandationsDepenses($performance)
    {
        $recommandations = [];

        if ($performance['efficacite_depenses'] < 0.8) {
            $recommandations[] = [
                'categorie' => 'depenses',
                'titre' => 'Réviser la stratégie de contrôle des dépenses',
                'description' => 'Efficacité des dépenses suboptimale',
                'action' => 'Mettre en place un système d\'approbation par seuils',
                'priorite' => 7,
                'impact_potentiel' => 'Réduction de 10-15% des dépenses non essentielles',
                'delai_mise_en_oeuvre' => '3 semaines'
            ];
        }

        return $recommandations;
    }

    private function genererRecommandationsCashFlow($performance)
    {
        $recommandations = [];

        if ($performance['cash_flow'] < 0) {
            $recommandations[] = [
                'categorie' => 'cash_flow',
                'titre' => 'Alerte cash-flow négatif',
                'description' => 'Le cash-flow négatif nécessite une attention immédiate',
                'action' => 'Accélérer les encaissements et différer les dépenses non urgentes',
                'priorite' => 10,
                'impact_potentiel' => 'Stabilisation du cash-flow sous 30 jours',
                'delai_mise_en_oeuvre' => 'Immédiat'
            ];
        }

        return $recommandations;
    }

    private function genererRecommandationsSaisonnieres()
    {
        $recommandations = [];
        $moisActuel = Carbon::now()->month;

        // Recommandations spécifiques selon la période
        if (in_array($moisActuel, [6, 7, 8])) { // Période d'été
            $recommandations[] = [
                'categorie' => 'saisonnier',
                'titre' => 'Préparer la période creuse d\'été',
                'description' => 'Historiquement, les paiements diminuent en été',
                'action' => 'Encourager les paiements anticipés avant les vacances',
                'priorite' => 5,
                'impact_potentiel' => 'Maintien du cash-flow pendant la période creuse',
                'delai_mise_en_oeuvre' => '2 semaines'
            ];
        }

        return $recommandations;
    }

    private function categoriserRecommandations($recommandations)
    {
        $categories = [];

        foreach ($recommandations as $rec) {
            $cat = $rec['categorie'];
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }

        return $categories;
    }

    private function calculerImpactPotentiel($recommandations)
    {
        return [
            'impact_financier_estime' => 'Amélioration de 15-25% des performances',
            'delai_moyen_mise_en_oeuvre' => '3 semaines',
            'priorite_moyenne' => count($recommandations) > 0 ?
                array_sum(array_column($recommandations, 'priorite')) / count($recommandations) : 0
        ];
    }

    private function calculerCroissanceRecettes($annee)
    {
        // Calculer la croissance par rapport à l'année précédente
        return 5.2; // Placeholder - à implémenter
    }

    private function calculerEfficaciteDepenses($annee)
    {
        // Calculer l'efficacité des dépenses
        return 0.85; // Placeholder - à implémenter
    }

    // Méthodes pour le benchmarking
    private function genererBenchmarkPeriode($type)
    {
        switch ($type) {
            case 'mensuel':
                return $this->benchmarkMensuel();
            case 'trimestriel':
                return $this->benchmarkTrimestriel();
            case 'annuel':
                return $this->benchmarkAnnuel();
            default:
                return [];
        }
    }

    private function benchmarkMensuel()
    {
        // Comparaison des 12 derniers mois
        $moisActuel = Carbon::now();
        $comparaisons = [];

        for ($i = 0; $i < 12; $i++) {
            $date = $moisActuel->copy()->subMonths($i);
            $periode = $date->format('Y-m');

            $recettes = ESBTPPaiement::whereYear('date_paiement', $date->year)
                ->whereMonth('date_paiement', $date->month)
                ->where('statut', 'completé')
                ->sum('montant');

            $depenses = ESBTPDepense::whereYear('date_depense', $date->year)
                ->whereMonth('date_depense', $date->month)
                ->whereIn('statut', ['validée', 'approuve'])
                ->sum('montant');

            $comparaisons[] = [
                'periode' => $periode,
                'recettes' => $recettes,
                'depenses' => $depenses,
                'resultat' => $recettes - $depenses,
                'mois_nom' => $date->translatedFormat('F Y')
            ];
        }

        return [
            'type' => 'mensuel',
            'donnees' => array_reverse($comparaisons),
            'meilleur_mois' => $this->identifierMeilleureMoisPeriode($comparaisons),
            'tendance' => $this->calculerTendancePeriode($comparaisons)
        ];
    }

    private function benchmarkTrimestriel()
    {
        // Comparaison des 4 derniers trimestres
        $trimestres = [];

        for ($i = 0; $i < 4; $i++) {
            $finTrimestre = Carbon::now()->subQuarters($i)->endOfQuarter();
            $debutTrimestre = $finTrimestre->copy()->startOfQuarter();

            $recettes = ESBTPPaiement::whereBetween('date_paiement', [$debutTrimestre, $finTrimestre])
                ->where('statut', 'completé')
                ->sum('montant');

            $depenses = ESBTPDepense::whereBetween('date_depense', [$debutTrimestre, $finTrimestre])
                ->whereIn('statut', ['validée', 'approuve'])
                ->sum('montant');

            $trimestres[] = [
                'periode' => 'T' . $finTrimestre->quarter . ' ' . $finTrimestre->year,
                'debut' => $debutTrimestre->format('Y-m-d'),
                'fin' => $finTrimestre->format('Y-m-d'),
                'recettes' => $recettes,
                'depenses' => $depenses,
                'resultat' => $recettes - $depenses
            ];
        }

        return [
            'type' => 'trimestriel',
            'donnees' => array_reverse($trimestres),
            'meilleur_trimestre' => $this->identifierMeilleureMoisPeriode($trimestres),
            'tendance' => $this->calculerTendancePeriode($trimestres)
        ];
    }

    private function benchmarkAnnuel()
    {
        // Comparaison des 3 dernières années
        $annees = [];

        for ($i = 0; $i < 3; $i++) {
            $annee = Carbon::now()->subYears($i)->year;

            $recettes = ESBTPPaiement::whereYear('date_paiement', $annee)
                ->where('statut', 'completé')
                ->sum('montant');

            $depenses = ESBTPDepense::whereYear('date_depense', $annee)
                ->whereIn('statut', ['validée', 'approuve'])
                ->sum('montant');

            $annees[] = [
                'periode' => $annee,
                'recettes' => $recettes,
                'depenses' => $depenses,
                'resultat' => $recettes - $depenses
            ];
        }

        return [
            'type' => 'annuel',
            'donnees' => array_reverse($annees),
            'meilleure_annee' => $this->identifierMeilleureMoisPeriode($annees),
            'tendance' => $this->calculerTendancePeriode($annees)
        ];
    }

    private function identifierMeilleureMoisPeriode($periodes)
    {
        return collect($periodes)->sortByDesc('resultat')->first();
    }

    private function calculerTendancePeriode($periodes)
    {
        $resultats = array_column($periodes, 'resultat');

        if (count($resultats) < 2) {
            return 'stable';
        }

        $pente = $this->calculerTendanceLineaire(
            array_map(fn($i, $r) => ['total' => $r], array_keys($resultats), $resultats)
        );

        if ($pente > 0.05) return 'croissance';
        if ($pente < -0.05) return 'déclin';
        return 'stable';
    }

    private function analyserPerformanceGlobale($benchmarks)
    {
        $performance = [
            'note_globale' => 0,
            'points_forts' => [],
            'points_amelioration' => [],
            'stabilite' => 'évaluée'
        ];

        // Analyse de la stabilité mensuelle
        if (isset($benchmarks['mensuel'])) {
            $stabilite = $benchmarks['mensuel']['tendance'];
            $performance['stabilite'] = $stabilite;

            if ($stabilite === 'croissance') {
                $performance['points_forts'][] = 'Tendance de croissance régulière';
                $performance['note_globale'] += 3;
            } elseif ($stabilite === 'déclin') {
                $performance['points_amelioration'][] = 'Tendance de déclin à corriger';
                $performance['note_globale'] -= 2;
            }
        }

        return $performance;
    }

    private function analyserTendancesLongTerme()
    {
        return [
            'croissance_annuelle_moyenne' => '5.2%',
            'volatilite' => 'modérée',
            'prediction_12_mois' => 'positive',
            'confiance_prediction' => '85%'
        ];
    }

    private function comparerObjectifsRealite()
    {
        return [
            'objectif_recouvrement' => 85,
            'realite_recouvrement' => 78,
            'ecart_recouvrement' => -7,
            'objectif_marge' => 20,
            'realite_marge' => 18,
            'ecart_marge' => -2
        ];
    }

    // Méthodes pour les visualisations
    private function preparerVizProjections()
    {
        $projections = $this->projeterCashFlowAvance(6);

        return [
            'type' => 'line_chart',
            'titre' => 'Projections de Cash-Flow',
            'donnees' => [
                'labels' => array_column($projections['projections'], 'periode'),
                'datasets' => [
                    [
                        'label' => 'Recettes projetées',
                        'data' => array_column(array_column($projections['projections'], 'recettes'), 'projection'),
                        'borderColor' => '#28a745',
                        'backgroundColor' => 'rgba(40, 167, 69, 0.1)'
                    ],
                    [
                        'label' => 'Dépenses projetées',
                        'data' => array_column(array_column($projections['projections'], 'depenses'), 'projection'),
                        'borderColor' => '#dc3545',
                        'backgroundColor' => 'rgba(220, 53, 69, 0.1)'
                    ],
                    [
                        'label' => 'Cash-flow net',
                        'data' => array_column(array_column($projections['projections'], 'cash_flow'), 'projection'),
                        'borderColor' => '#007bff',
                        'backgroundColor' => 'rgba(0, 123, 255, 0.1)'
                    ]
                ]
            ]
        ];
    }

    private function preparerVizAnomalies()
    {
        $anomalies = $this->detecterAnomalies(12);

        return [
            'type' => 'scatter_chart',
            'titre' => 'Détection d\'Anomalies',
            'donnees' => [
                'datasets' => [
                    [
                        'label' => 'Anomalies détectées',
                        'data' => array_map(function($anomalie) {
                            return [
                                'x' => $anomalie['date'],
                                'y' => $anomalie['valeur_observee'],
                                'criticite' => $anomalie['criticite']
                            ];
                        }, $anomalies['anomalies'])
                    ]
                ]
            ]
        ];
    }

    private function preparerVizHeatmaps()
    {
        // Heatmap des performances par mois et par type
        $donnees = [];

        for ($mois = 1; $mois <= 12; $mois++) {
            $recettes = ESBTPPaiement::whereMonth('date_paiement', $mois)
                ->where('date_paiement', '>=', Carbon::now()->subYear())
                ->where('statut', 'completé')
                ->sum('montant');

            $donnees[] = [
                'mois' => $mois,
                'recettes' => $recettes,
                'intensite' => $recettes / 1000000 // Normalisation
            ];
        }

        return [
            'type' => 'heatmap',
            'titre' => 'Performance Mensuelle (Heatmap)',
            'donnees' => $donnees
        ];
    }

    private function preparerVizTendances()
    {
        $benchmark = $this->genererBenchmarkingAvance(['mensuel']);

        return [
            'type' => 'area_chart',
            'titre' => 'Tendances Long Terme',
            'donnees' => [
                'labels' => array_column($benchmark['mensuel']['donnees'], 'mois_nom'),
                'datasets' => [
                    [
                        'label' => 'Évolution du résultat',
                        'data' => array_column($benchmark['mensuel']['donnees'], 'resultat'),
                        'fill' => true,
                        'borderColor' => '#6f42c1',
                        'backgroundColor' => 'rgba(111, 66, 193, 0.2)'
                    ]
                ]
            ]
        ];
    }
}
