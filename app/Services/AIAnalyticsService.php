<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPFacture;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Carbon\Carbon;

class AIAnalyticsService
{
    private $apiKey;
    private $apiEndpoint;
    private $comptabiliteService;

    public function __construct(ComptabiliteService $comptabiliteService)
    {
        $this->apiKey = env('OPENAI_API_KEY', env('CLAUDE_API_KEY'));
        $this->apiEndpoint = env('AI_ANALYTICS_ENDPOINT', 'https://api.openai.com/v1/chat/completions');
        $this->comptabiliteService = $comptabiliteService;
    }

    /**
     * Génère des insights IA basés sur les données financières réelles
     */
    public function genererInsightsFinanciers($periode = 12): array
    {
        $cacheKey = "ai_insights_financiers_{$periode}";

        return Cache::remember($cacheKey, 300, function() use ($periode) {
            try {
                // Récupérer les données réelles
                $donneesFinancieres = $this->preparerDonneesFinancieres($periode);

                // Analyser avec IA
                $insights = $this->analyserAvecIA($donneesFinancieres);

                return [
                    'insights' => $insights,
                    'predictions' => $this->genererPredictions($donneesFinancieres),
                    'recommendations' => $insights['recommendations'] ?? [],
                    'alertes_automatiques' => $this->detecterAnomalies($donneesFinancieres),
                    'tendances' => $this->analyserTendances($donneesFinancieres),
                    'generated_at' => now()
                ];

            } catch (\Exception $e) {
                Log::error('Erreur génération insights IA', ['error' => $e->getMessage()]);
                return $this->getFallbackInsights();
            }
        });
    }

    /**
     * Prépare les données financières réelles pour l'analyse IA
     */
    private function preparerDonneesFinancieres($periode): array
    {
        $dateDebut = Carbon::now()->subMonths($periode);

        // Récupérer toutes les données réelles
        $recettes = ESBTPPaiement::where('date_paiement', '>=', $dateDebut)
            ->selectRaw('MONTH(date_paiement) as mois, YEAR(date_paiement) as annee, SUM(montant) as total')
            ->groupBy('annee', 'mois')
            ->orderBy('annee', 'mois')
            ->get();

        $depenses = ESBTPDepense::where('date_depense', '>=', $dateDebut)
            ->selectRaw('MONTH(date_depense) as mois, YEAR(date_depense) as annee, SUM(montant) as total')
            ->groupBy('annee', 'mois')
            ->orderBy('annee', 'mois')
            ->get();

        $etudiants = ESBTPEtudiant::join('esbtp_inscriptions', 'esbtp_etudiants.id', '=', 'esbtp_inscriptions.etudiant_id')
            ->where('esbtp_inscriptions.created_at', '>=', $dateDebut)
            ->selectRaw('MONTH(esbtp_inscriptions.created_at) as mois, YEAR(esbtp_inscriptions.created_at) as annee, COUNT(*) as total')
            ->groupBy('annee', 'mois')
            ->get();

        $factures = ESBTPFacture::where('date_facture', '>=', $dateDebut)
            ->selectRaw('MONTH(date_facture) as mois, YEAR(date_facture) as annee, COUNT(*) as nombre, SUM(montant_total) as total')
            ->groupBy('annee', 'mois')
            ->get();

        // Calculer les métriques en temps réel
        $metriques = $this->comptabiliteService->getKPIsDashboard();

        return [
            'periode_analyse' => $periode,
            'recettes_mensuelles' => $recettes->toArray(),
            'depenses_mensuelles' => $depenses->toArray(),
            'nouveaux_etudiants' => $etudiants->toArray(),
            'factures_emises' => $factures->toArray(),
            'kpis_actuels' => $metriques,
            'taux_recouvrement_actuel' => $this->calculerTauxRecouvrement(),
            'croissance_mensuelle' => $this->calculerCroissanceMensuelle($recettes),
            'seasonalite' => $this->analyserSaisonnalite($recettes),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Analyse les données avec l'IA externe
     */
    private function analyserAvecIA(array $donnees): array
    {
        if (!$this->apiKey) {
            return $this->getFallbackAnalysis($donnees);
        }

        try {
            $prompt = $this->construirePromptAnalyse($donnees);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiEndpoint, [
                    'model' => 'gpt-4',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un analyste financier expert spécialisé dans les établissements d\'enseignement. Analyse les données financières et fournis des insights précis et actionnables.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 1500,
                    'temperature' => 0.7
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $analysisText = $result['choices'][0]['message']['content'] ?? '';

                return $this->parseAnalysisResponse($analysisText);
            }

        } catch (\Exception $e) {
            Log::error('Erreur API IA', ['error' => $e->getMessage()]);
        }

        return $this->getFallbackAnalysis($donnees);
    }

    /**
     * Construit le prompt pour l'analyse IA
     */
    private function construirePromptAnalyse(array $donnees): string
    {
        $resume = json_encode([
            'periode' => $donnees['periode_analyse'] . ' mois',
            'recettes_total' => array_sum(array_column($donnees['recettes_mensuelles'], 'total')),
            'depenses_total' => array_sum(array_column($donnees['depenses_mensuelles'], 'total')),
            'nombre_etudiants' => array_sum(array_column($donnees['nouveaux_etudiants'], 'total')),
            'taux_recouvrement' => $donnees['taux_recouvrement_actuel'],
            'tendance_croissance' => $donnees['croissance_mensuelle']
        ], JSON_PRETTY_PRINT);

        return "Analyse ces données financières d'une école et fournis:
1. INSIGHTS: 3-4 observations clés sur la performance financière
2. ALERTES: Points d'attention et risques identifiés
3. RECOMMANDATIONS: 2-3 actions concrètes à prendre
4. TENDANCES: Analyse des patterns et évolution

Données financières:
{$resume}

Format de réponse: JSON avec les clés 'insights', 'alertes', 'recommendations', 'tendances'";
    }

    /**
     * Parse la réponse de l'IA
     */
    private function parseAnalysisResponse(string $response): array
    {
        try {
            // Tenter de parser en JSON
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $json = json_decode($matches[0], true);
                if ($json) {
                    return $json;
                }
            }

            // Fallback: parser le texte
            return $this->parseTextResponse($response);

        } catch (\Exception $e) {
            Log::error('Erreur parsing réponse IA', ['error' => $e->getMessage()]);
            return $this->getFallbackAnalysis([]);
        }
    }

    /**
     * Génère des prédictions financières
     */
    public function genererPredictions(array $donnees): array
    {
        $recettes = collect($donnees['recettes_mensuelles']);
        $depenses = collect($donnees['depenses_mensuelles']);

        // Calcul des tendances
        $tendanceRecettes = $this->calculerTendanceLineaire($recettes->pluck('total')->toArray());
        $tendanceDepenses = $this->calculerTendanceLineaire($depenses->pluck('total')->toArray());

        // Prédictions pour les 3 prochains mois
        $predictions = [];
        for ($i = 1; $i <= 3; $i++) {
            $moisSuivant = Carbon::now()->addMonths($i);

            $recettesPredites = max(0, $tendanceRecettes['slope'] * $i + $tendanceRecettes['last_value']);
            $depensesPredites = max(0, $tendanceDepenses['slope'] * $i + $tendanceDepenses['last_value']);

            $predictions[] = [
                'mois' => $moisSuivant->format('Y-m'),
                'mois_nom' => $moisSuivant->format('F Y'),
                'recettes_predites' => round($recettesPredites),
                'depenses_predites' => round($depensesPredites),
                'resultat_predit' => round($recettesPredites - $depensesPredites),
                'confiance' => $this->calculerNiveauConfiance($i),
                'facteurs' => $this->identifierFacteursInfluence($moisSuivant)
            ];
        }

        return $predictions;
    }

    /**
     * Calcule la tendance linéaire
     */
    private function calculerTendanceLineaire(array $valeurs): array
    {
        $n = count($valeurs);
        if ($n < 2) {
            return ['slope' => 0, 'last_value' => $valeurs[0] ?? 0];
        }

        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($valeurs);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $valeurs[$i];
            $sumXX += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);

        return [
            'slope' => $slope,
            'last_value' => end($valeurs)
        ];
    }

    /**
     * Calcule le taux de recouvrement en temps réel
     */
    private function calculerTauxRecouvrement(): float
    {
        $totalFacture = ESBTPFacture::sum('montant_total');
        $totalPaye = ESBTPPaiement::sum('montant');

        return $totalFacture > 0 ? round(($totalPaye / $totalFacture) * 100, 2) : 0;
    }

    /**
     * Analyse fallback sans IA
     */
    private function getFallbackAnalysis(array $donnees): array
    {
        return [
            'insights' => [
                'Analyse basée sur les données financières en temps réel',
                'Performance calculée automatiquement sans IA externe',
                'Métriques mises à jour en continu'
            ],
            'alertes' => [
                'Système d\'alertes automatiques activé',
                'Surveillance continue des KPIs'
            ],
            'recommendations' => [
                'Continuer le suivi régulier des performances',
                'Optimiser le processus de recouvrement'
            ],
            'tendances' => [
                'Évolution suivie par algorithmes internes',
                'Patterns détectés automatiquement'
            ]
        ];
    }

    /**
     * Retourne des insights par défaut
     */
    private function getFallbackInsights(): array
    {
        return [
            'insights' => $this->getFallbackAnalysis([]),
            'predictions' => [],
            'recommendations' => ['Configuration IA requise pour analyse avancée'],
            'alertes_automatiques' => [],
            'tendances' => [],
            'generated_at' => now()
        ];
    }

    /**
     * Calcule la croissance mensuelle
     */
    private function calculerCroissanceMensuelle($recettes): float
    {
        if ($recettes->count() < 2) return 0;

        $dernier = $recettes->last()['total'] ?? 0;
        $precedent = $recettes->slice(-2, 1)->first()['total'] ?? 1;

        return $precedent > 0 ? round((($dernier - $precedent) / $precedent) * 100, 2) : 0;
    }

    /**
     * Analyse la saisonnalité
     */
    private function analyserSaisonnalite($recettes): array
    {
        $parMois = [];
        foreach ($recettes as $recette) {
            $mois = $recette['mois'];
            $parMois[$mois] = ($parMois[$mois] ?? 0) + $recette['total'];
        }

        return $parMois;
    }

    /**
     * Calcule le niveau de confiance
     */
    private function calculerNiveauConfiance(int $moisDistance): int
    {
        return max(50, 95 - ($moisDistance * 15));
    }

    /**
     * Identifie les facteurs d'influence
     */
    private function identifierFacteursInfluence($mois): array
    {
        $moisNum = $mois->month;

        $facteurs = [
            'Tendance saisonnière',
            'Période d\'inscription'
        ];

        if (in_array($moisNum, [9, 10, 11])) {
            $facteurs[] = 'Rentrée scolaire - forte activité attendue';
        }

        if (in_array($moisNum, [12, 7, 8])) {
            $facteurs[] = 'Période de vacances - activité réduite';
        }

        return $facteurs;
    }

    /**
     * Détecte les anomalies automatiquement
     */
    private function detecterAnomalies(array $donnees): array
    {
        $alertes = [];

        // Vérifier taux de recouvrement
        $tauxRecouvrement = $donnees['taux_recouvrement_actuel'];
        if ($tauxRecouvrement < 70) {
            $alertes[] = [
                'type' => 'critique',
                'message' => "Taux de recouvrement faible: {$tauxRecouvrement}%",
                'action' => 'Renforcer les relances de paiement'
            ];
        }

        // Vérifier croissance
        $croissance = $donnees['croissance_mensuelle'];
        if ($croissance < -10) {
            $alertes[] = [
                'type' => 'warning',
                'message' => "Décroissance significative: {$croissance}%",
                'action' => 'Analyser les causes de la baisse'
            ];
        }

        return $alertes;
    }

    /**
     * Parse la réponse texte
     */
    private function parseTextResponse(string $response): array
    {
        // Parser basique du texte de réponse
        return [
            'insights' => [substr($response, 0, 200) . '...'],
            'alertes' => ['Réponse IA analysée automatiquement'],
            'recommendations' => ['Continuer l\'analyse des données'],
            'tendances' => ['Patterns identifiés dans la réponse']
        ];
    }

    /**
     * Analyse les tendances
     */
    private function analyserTendances(array $donnees): array
    {
        return [
            'evolution_recettes' => $this->calculerEvolution($donnees['recettes_mensuelles']),
            'evolution_depenses' => $this->calculerEvolution($donnees['depenses_mensuelles']),
            'croissance_etudiants' => $this->calculerEvolution($donnees['nouveaux_etudiants']),
            'pattern_saisonnier' => $this->detecterPatternSaisonnier($donnees['recettes_mensuelles'])
        ];
    }

    /**
     * Calcule l'évolution
     */
    private function calculerEvolution(array $donnees): string
    {
        if (count($donnees) < 2) return 'stable';

        $debut = array_slice($donnees, 0, 3);
        $fin = array_slice($donnees, -3);

        $moyenneDebut = collect($debut)->avg('total');
        $moyenneFin = collect($fin)->avg('total');

        $variation = $moyenneDebut > 0 ? (($moyenneFin - $moyenneDebut) / $moyenneDebut) * 100 : 0;

        if ($variation > 10) return 'croissance';
        if ($variation < -10) return 'declin';
        return 'stable';
    }

    /**
     * Détecte les patterns saisonniers
     */
    private function detecterPatternSaisonnier(array $donnees): array
    {
        $patterns = [];

        foreach ($donnees as $donnee) {
            $mois = $donnee['mois'];
            $patterns[$mois] = ($patterns[$mois] ?? 0) + ($donnee['total'] ?? 0);
        }

        arsort($patterns);

        return [
            'meilleur_mois' => array_key_first($patterns),
            'moins_bon_mois' => array_key_last($patterns),
            'variation_saisonniere' => count($patterns) > 1 ?
                round((reset($patterns) - end($patterns)) / end($patterns) * 100, 1) : 0
        ];
    }
}
