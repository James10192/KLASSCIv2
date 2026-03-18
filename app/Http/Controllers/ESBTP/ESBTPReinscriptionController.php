<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Services\ReeinscriptionService;
use App\Services\FuzzyNameMatcher;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPFiliere;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ESBTPReinscriptionController extends Controller
{
    protected $reinscriptionService;
    protected FuzzyNameMatcher $matcher;

    public function __construct(ReeinscriptionService $reinscriptionService, FuzzyNameMatcher $matcher)
    {
        $this->reinscriptionService = $reinscriptionService;
        $this->matcher = $matcher;
    }

    public function index(Request $request)
    {
        // Toujours utiliser l'année courante (is_current = true)
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);
        
        // Données pour les filtres
        $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->get();
        
        try {
            // OPTIMISATION: Ne charger que les statistiques au départ
            $statistiques = $this->reinscriptionService->getStatistiquesReinscription($anneeAcademique);
            
            return view('esbtp.reinscription.index', compact('statistiques', 'anneeAcademique', 'filieres', 'niveaux'))
                ->withErrors(collect());
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des statistiques vides
            $statistiques = [
                'passages' => 0,
                'rattrapages' => 0, 
                'redoublements' => 0,
                'valides' => 0,
                'abandons_annee' => 0,
                'abandons_ecole' => 0,
                'errors' => 0
            ];
            
            return view('esbtp.reinscription.index', compact('statistiques', 'anneeAcademique', 'filieres', 'niveaux'))
                ->withErrors(['error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculer les soldes pour tous les étudiants
     */
    private function calculerSoldesEtudiants(&$resultats)
    {
        foreach (['passages', 'rattrapages', 'redoublements', 'abandons_annee', 'abandons_ecole', 'valides'] as $categorie) {
            if (isset($resultats[$categorie])) {
                foreach ($resultats[$categorie] as &$etudiantData) {
                    // Récupérer l'étudiant selon le format des données
                    $etudiant = null;
                    if (is_array($etudiantData) && isset($etudiantData['etudiant'])) {
                        $etudiant = $etudiantData['etudiant'];
                    } else if (is_object($etudiantData)) {
                        $etudiant = $etudiantData;
                    }
                    
                    if ($etudiant) {
                        // Récupérer l'inscription active de l'étudiant
                        $inscription = $etudiant->inscriptions()
                            ->whereHas('anneeUniversitaire', function($query) {
                                $query->where('is_current', true);
                            })
                            ->with(['paiements' => function($query) {
                                $query->where('status', 'validé');
                            }])
                            ->first();
                        
                        if ($inscription) {
                            // Calculer le total attendu et payé comme sur la page inscription
                            $totalAttendu = $this->calculerTotalAttendu($inscription);
                            $totalPaye = $this->calculerTotalPaye($inscription);
                            $soldeRestant = $totalAttendu - $totalPaye;
                            
                            $etudiant->montant_attendu = $totalAttendu;
                            $etudiant->montant_paye = $totalPaye;
                            $etudiant->solde_restant = $soldeRestant;
                            
                            // Déterminer si l'étudiant peut se réinscrire
                            // (seulement si tout est soldé - solde restant = 0 ou négatif)
                            $etudiant->peut_reinscrire = $soldeRestant <= 0;
                        } else {
                            // Pas d'inscription active, utiliser les anciennes valeurs par défaut
                            $etudiant->montant_attendu = 0;
                            $etudiant->montant_paye = 0;
                            $etudiant->solde_restant = 0;
                            $etudiant->peut_reinscrire = false;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Calculer le solde pour un seul étudiant (version optimisée pour lazy loading)
     */
    private function calculerSoldeEtudiant(&$etudiantData)
    {
        // Récupérer l'étudiant selon le format des données
        $etudiant = null;
        if (is_array($etudiantData) && isset($etudiantData['etudiant'])) {
            $etudiant = $etudiantData['etudiant'];
        } else if (is_object($etudiantData)) {
            $etudiant = $etudiantData;
        }
        
        if (!$etudiant) return;
        
        // Récupérer l'inscription active de l'étudiant
        $inscription = $etudiant->inscriptions()
            ->whereHas('anneeUniversitaire', function($query) {
                $query->where('is_current', true);
            })
            ->with(['paiements' => function($query) {
                $query->where('status', 'validé');
            }])
            ->first();
        
        if ($inscription) {
            // Calculer le total attendu et payé
            $totalAttendu = $this->calculerTotalAttendu($inscription);
            $totalPaye = $this->calculerTotalPaye($inscription);
            $soldeRestant = $totalAttendu - $totalPaye;
            
            $etudiant->montant_attendu = $totalAttendu;
            $etudiant->montant_paye = $totalPaye;
            $etudiant->solde_restant = $soldeRestant;
            $etudiant->peut_reinscrire = $soldeRestant <= 0;
        } else {
            // Pas d'inscription active
            $etudiant->montant_attendu = 0;
            $etudiant->montant_paye = 0;
            $etudiant->solde_restant = 0;
            $etudiant->peut_reinscrire = false;
        }
    }
    
    /**
     * Calculer le total attendu pour une inscription.
     *
     * Utilise la même logique de priorité que ESBTPInscriptionController::show() :
     * 1. Souscription individuelle (ESBTPFraisSubscription) → montant personnalisé
     * 2. Règle filière/niveau (ESBTPFraisConfiguration) → getMontantByStatus()
     * 3. Ni l'un ni l'autre → frais non compté (is_configured = false)
     *
     * Cela évite de gonfler le total en comptant des frais obligatoires configurés
     * globalement mais auxquels l'étudiant n'est pas souscrit.
     */
    private function calculerTotalAttendu($inscription)
    {
        // Basé UNIQUEMENT sur les frais souscriptions actives.
        // Pas de souscriptions → rien à payer → 0.
        $subscriptions = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->get();

        return $subscriptions->sum('amount');
    }
    
    /**
     * Calculer le total payé pour une inscription
     */
    private function calculerTotalPaye($inscription)
    {
        return $inscription->paiements()
            ->where('status', 'validé')
            ->sum('montant');
    }

    /**
     * Marquer un étudiant comme ayant abandonné
     */
    public function marquerAbandon(Request $request, $etudiantId)
    {
        $request->validate([
            'motif_abandon' => 'nullable|string|max:500',
            'abandon_type' => 'required|in:annee_scolaire,ecole',
        ]);

        try {
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
            $etudiant->update([
                'statut' => 'abandon',
                'motif_abandon' => $request->motif_abandon,
                'abandon_type' => $request->abandon_type,
                'date_abandon' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Étudiant marqué comme ayant abandonné'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurer un étudiant depuis abandon vers actif
     */
    public function restaurerAbandon($etudiantId)
    {
        try {
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
            $etudiant->update([
                'statut' => 'actif',
                'motif_abandon' => null,
                'abandon_type' => null,
                'date_abandon' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Étudiant restauré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($etudiantId, Request $request)
    {
        $anneeAcademique = $request->get('annee_academique', date('Y') . '-' . (date('Y') + 1));

        try {
            // Chercher l'inscription de l'étudiant avec une classe assignée
            $inscription = \App\Models\ESBTPInscription::whereNotNull('classe_id')
                ->whereHas('etudiant', function($query) use ($etudiantId) {
                    $query->where('id', $etudiantId);
                })
                ->with(['etudiant', 'classe.niveau', 'classe.filiere'])
                ->first();

            if (!$inscription) {
                throw new \Exception("Aucune inscription avec classe trouvée pour cet étudiant");
            }

            $analyse = $this->reinscriptionService->analyserSituationEtudiantParInscription($inscription, $anneeAcademique);
            $classesProposees = $this->reinscriptionService->proposerNouvellesClasses($etudiantId, $analyse['decision']);

            // Calculer les soldes financiers pour l'étudiant
            $etudiant = $analyse['etudiant'];
            $totalAttendu = $this->calculerTotalAttendu($inscription);
            $totalPaye = $this->calculerTotalPaye($inscription);
            $soldeRestant = $totalAttendu - $totalPaye;

            // Ajouter les informations financières à l'étudiant
            $etudiant->montant_attendu = $totalAttendu;
            $etudiant->montant_paye = $totalPaye;
            $etudiant->solde_restant = $soldeRestant;

            // Calculer le VRAI reliquat (uniquement les dettes des années précédentes via ESBTPReliquatDetail)
            // comme dans inscriptions.show
            $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
                ->actifs()
                ->get();

            $reliquatMontant = $reliquatsEntrants->sum('solde_restant');

            // Logique d'éligibilité selon le rôle
            $isSuperAdmin = auth()->user() && auth()->user()->isSuperAdmin();
            $etudiant->peut_reinscrire = $soldeRestant <= 0 || $isSuperAdmin;
            $etudiant->reliquat_possible = $isSuperAdmin && $soldeRestant > 0;
            $etudiant->reliquat_montant = $isSuperAdmin ? max(0, $soldeRestant) : 0;

            // IMPORTANT: Le reliquat affiché dans la carte de validation = uniquement années précédentes
            $etudiant->reliquat_reel = $reliquatMontant;
            
            // Ajouter l'inscription pour l'accès aux données de classe dans la vue
            $analyse['inscription'] = $inscription;
            
            $anneeCouranteModel = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $existingReinscription = null;
            $validatedReinscription = null;

            if ($anneeCouranteModel) {
                $existingReinscription = \App\Models\ESBTPInscription::with([
                        'classe.filiere',
                        'classe.niveau',
                        'anneeUniversitaire',
                        'reinscriptionValidatedBy'
                    ])
                    ->where('etudiant_id', $etudiantId)
                    ->where('annee_universitaire_id', $anneeCouranteModel->id)
                    ->where('type_inscription', 'reinscription')
                    ->latest()
                    ->first();

                if ($existingReinscription && $existingReinscription->reinscription_status === 'validated') {
                    $validatedReinscription = $existingReinscription;
                }
            }

            return view('esbtp.reinscription.show', compact('analyse', 'classesProposees', 'anneeAcademique', 'validatedReinscription', 'existingReinscription'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()]);
        }
    }

    /**
     * Afficher la page de finalisation de réinscription
     */
    public function create($etudiantId, Request $request)
    {
        $anneeAcademique = $request->get('annee_academique', date('Y') . '-' . (date('Y') + 1));

        try {
            // Récupérer l'inscription la plus récente de l'étudiant avec classe
            $inscription = \App\Models\ESBTPInscription::whereNotNull('classe_id')
                ->whereHas('etudiant', function($query) use ($etudiantId) {
                    $query->where('id', $etudiantId);
                })
                ->with(['etudiant', 'classe.niveau', 'classe.filiere', 'anneeUniversitaire'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$inscription) {
                throw new \Exception("Aucune inscription avec classe trouvée pour cet étudiant");
            }

            $analyse = $this->reinscriptionService->analyserSituationEtudiantParInscription($inscription, $anneeAcademique);

            // Récupérer TOUTES les classes possibles selon les différentes décisions
            $classesParDecision = [
                'passage' => $this->reinscriptionService->proposerNouvellesClasses($etudiantId, 'passage'),
                'redoublement' => $this->reinscriptionService->proposerNouvellesClasses($etudiantId, 'redoublement'),
                'rattrapage' => $this->reinscriptionService->proposerNouvellesClasses($etudiantId, 'rattrapage')
            ];

            // Charger les relations pour toutes les classes
            foreach ($classesParDecision as $decision => $classes) {
                if (is_array($classes)) {
                    $classes = collect($classes);
                }
                $classesParDecision[$decision] = $classes->map(function($classe) {
                    if ($classe && !$classe->relationLoaded('niveau')) {
                        $classe->load(['niveau', 'filiere']);
                    }
                    return $classe;
                });
            }

            // Calculer les informations financières
            $etudiant = $analyse['etudiant'];
            $totalAttendu = $this->calculerTotalAttendu($inscription);
            $totalPaye = $this->calculerTotalPaye($inscription);
            $soldeRestant = $totalAttendu - $totalPaye;

            // Ajouter les informations financières et de rôle
            $isSuperAdmin = auth()->user() && auth()->user()->isSuperAdmin();
            $etudiant->montant_attendu = $totalAttendu;
            $etudiant->montant_paye = $totalPaye;
            $etudiant->solde_restant = $soldeRestant;
            $etudiant->peut_reinscrire = $soldeRestant <= 0 || $isSuperAdmin;
            $etudiant->reliquat_possible = $isSuperAdmin && $soldeRestant > 0;
            $etudiant->reliquat_montant = $isSuperAdmin ? max(0, $soldeRestant) : 0;

            // Récupérer les détails des frais non soldés pour le reliquat
            $fraisNonSoldes = [];
            if ($isSuperAdmin && $soldeRestant > 0) {
                $fraisNonSoldes = $this->calculerFraisNonSoldes($inscription);
            }

            // Précharger tous les frais pour toutes les combinaisons classe/affectation
            $fraisParClasse = $this->prechargerFraisPourToutesLesClasses($classesParDecision);

            // Récupérer les années universitaires futures pour la sélection
            $anneeUniversitairesFutures = $this->getAnneesUniversitairesFutures();

            $analyse['inscription'] = $inscription;

            // Déterminer les années pour l'affichage cohérent
            $anneeEtudiantActuelle = $inscription->anneeUniversitaire->name ?? 'N/A'; // Année de l'inscription actuelle de l'étudiant
            $anneeDestination = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeDestinationName = $anneeDestination ? $anneeDestination->name : $anneeAcademique;

            return view('esbtp.reinscription.create', compact(
                'analyse',
                'classesParDecision',
                'fraisParClasse',
                'anneeAcademique',
                'fraisNonSoldes',
                'isSuperAdmin',
                'anneeUniversitairesFutures',
                'anneeEtudiantActuelle',
                'anneeDestinationName'
            ));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()]);
        }
    }

    /**
     * Précharger tous les frais pour toutes les classes et statuts d'affectation
     */
    private function prechargerFraisPourToutesLesClasses($classesParDecision)
    {
        $fraisParClasse = [];
        $statutsAffectation = ['affecté', 'non-affecté', 'maintenant-affecté'];

        // Pour chaque décision
        foreach ($classesParDecision as $decision => $classes) {
            if (!$classes || $classes->isEmpty()) {
                continue;
            }

            // Pour chaque classe de cette décision
            foreach ($classes as $classe) {
                if (!$classe) continue;

                // Pour chaque statut d'affectation
                foreach ($statutsAffectation as $statut) {
                    $classeKey = "{$classe->id}_{$statut}";

                    try {
                        // Utiliser la même logique que l'endpoint AJAX existant
                        $frais = $this->getFraisForClasseEtAffectation($classe->id, $statut);
                        $fraisParClasse[$classeKey] = $frais;
                    } catch (\Exception $e) {
                        // Si erreur, continuer avec les autres classes
                        \Log::warning("Erreur préchargement frais classe {$classe->id} statut {$statut}: " . $e->getMessage());
                        $fraisParClasse[$classeKey] = [];
                    }
                }
            }
        }

        return $fraisParClasse;
    }

    /**
     * Récupérer les frais pour une classe et un statut d'affectation donnés
     */
    private function getFraisForClasseEtAffectation($classeId, $statutAffectation = 'affecté')
    {
        // Logique basée sur l'endpoint existant dans ESBTPInscriptionController
        $classe = \App\Models\ESBTPClasse::with(['niveau', 'filiere'])->findOrFail($classeId);

        // Récupérer les frais configurés pour cette filiere/niveau
        $fraisConfigs = \App\Models\ESBTPFraisConfiguration::active()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_id', $classe->niveau_etude_id)
            ->with(['fraisCategory'])
            ->get();

        $fraisData = [];

        foreach ($fraisConfigs as $config) {
            if (!$config->fraisCategory) continue;

            // Convertir le format de statut d'affectation
            $statusForConfig = $this->normaliserStatutAffectation($statutAffectation);

            // Utiliser la méthode du modèle pour obtenir le montant
            $montantCalcule = $config->getMontantByStatus($statusForConfig);

            // Si le montant est 0, on peut ignorer ce frais pour ce statut
            if ($montantCalcule <= 0) {
                continue;
            }

            $fraisData[] = [
                'category' => [
                    'id' => $config->fraisCategory->id,
                    'name' => $config->fraisCategory->name,
                    'libelle' => $config->fraisCategory->libelle ?? $config->fraisCategory->name,
                ],
                'is_mandatory' => $config->fraisCategory->is_mandatory ?? true,
                'default_amount' => $montantCalcule,
                'configured_amount' => $montantCalcule,
                'amount' => $montantCalcule,
            ];
        }

        return $fraisData;
    }

    /**
     * Normaliser le statut d'affectation pour correspondre au format du modèle
     */
    private function normaliserStatutAffectation($statutAffectation)
    {
        return match($statutAffectation) {
            'affecté', 'affecte' => 'affecté',
            'non-affecté', 'non_affecte', 'non-affecte', 'non_affecté' => 'non_affecté',
            'réaffecté', 'reaffecte', 'maintenant-affecté', 'maintenant_affecte' => 'réaffecté',
            default => 'affecté'
        };
    }

    /**
     * Calculer les frais non soldés pour le reliquat
     */
    private function calculerFraisNonSoldes($inscription)
    {
        // Récupérer toutes les souscriptions de frais de cette inscription
        $subscriptions = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with(['fraisCategory'])
            ->get();

        $fraisNonSoldes = [];
        $totalPaye = $this->calculerTotalPaye($inscription);

        foreach ($subscriptions as $subscription) {
            $montantAttendu = $subscription->amount;

            // Pour simplifier, on considère que les paiements sont répartis proportionnellement
            // Une logique plus complexe pourrait être implémentée selon les besoins
            $paiementPourCeFrais = ($montantAttendu / $this->calculerTotalAttendu($inscription)) * $totalPaye;
            $soldeRestant = $montantAttendu - $paiementPourCeFrais;

            if ($soldeRestant > 0.01) { // Éviter les erreurs d'arrondi
                $fraisNonSoldes[] = [
                    'subscription' => $subscription,
                    'category_name' => $subscription->fraisCategory->name ?? 'Frais inconnu',
                    'montant_attendu' => $montantAttendu,
                    'montant_paye' => $paiementPourCeFrais,
                    'solde_restant' => $soldeRestant
                ];
            }
        }

        return $fraisNonSoldes;
    }

    /**
     * Récupérer les classes disponibles selon la décision (AJAX)
     */
    public function getClassesByDecision($etudiantId, Request $request)
    {
        try {
            $decision = $request->get('decision');

            if (!$decision) {
                return response()->json([
                    'success' => false,
                    'message' => 'Décision manquante'
                ]);
            }

            $classesProposees = $this->reinscriptionService->proposerNouvellesClasses($etudiantId, $decision);

            // S'assurer que les relations sont chargées
            if (is_array($classesProposees)) {
                $classesProposees = collect($classesProposees);
            }

            $classesWithRelations = $classesProposees->map(function($classe) {
                if ($classe && !$classe->relationLoaded('niveau')) {
                    $classe->load(['niveau', 'filiere']);
                }
                return $classe;
            });

            return response()->json([
                'success' => true,
                'classes' => $classesWithRelations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Charger une catégorie d'étudiants via AJAX (optimisation lazy loading)
     */
    public function loadCategory(Request $request, $category)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        Log::info('Reinscription loadCategory start', [
            'timestamp' => $startTimestamp,
            'category' => $category,
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 50),
            'filters' => $request->all(),
        ]);

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        
        // Déterminer l'année à utiliser (filtrée ou courante)
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);
        
        
        try {
            switch ($category) {
                case 'passages':
                case 'rattrapages':
                case 'redoublements':
                    $resultats = $this->reinscriptionService->getEtudiantsParDecision($anneeAcademique);
                    $analyses = collect($resultats[$category] ?? []);

                    // CORRECTION: Garder les analyses complètes mais filtrer les étudiants null
                    $etudiants = $analyses->filter(function($analyse) {
                        return isset($analyse['etudiant']) && $analyse['etudiant'] !== null;
                    });
                    break;
                    
                case 'valides':
                    $etudiants = $this->getEtudiantsValides($anneeCourante);
                    break;
                    
                case 'abandons_annee':
                    $etudiants = $this->getEtudiantsAbandons($anneeCourante, 'annee_scolaire');
                    break;
                    
                case 'abandons_ecole':
                    $etudiants = $this->getEtudiantsAbandons($anneeCourante, 'ecole');
                    break;
                    
                case 'errors':
                    $resultats = $this->reinscriptionService->getEtudiantsParDecision($anneeAcademique);
                    $erreurs = collect($resultats['errors'] ?? []);

                    // CORRECTION: Garder la structure erreur mais filtrer les étudiants null
                    $etudiants = $erreurs->filter(function($erreur) {
                        return isset($erreur['etudiant']) && $erreur['etudiant'] !== null;
                    });
                    break;
                    
                default:
                    return response()->json(['error' => 'Catégorie inconnue'], 400);
            }
            
            // Appliquer les filtres sur la collection d'étudiants
            $initialCount = $etudiants instanceof Collection
                ? $etudiants->count()
                : (is_array($etudiants) ? count($etudiants) : 0);

            $etudiants = $this->applyFiltersToEtudiants($etudiants, $request);

            Log::info('Reinscription loadCategory after filters', [
                'timestamp' => now()->toIso8601String(),
                'category' => $category,
                'initial_count' => $initialCount,
                'filtered_count' => $etudiants->count(),
                'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
            ]);
            
            // Pagination manuelle
            $total = $etudiants->count();
            $offset = ($page - 1) * $perPage;
            $etudiantsPagines = $etudiants->slice($offset, $perPage);
            
            // Calculer les soldes pour les étudiants de cette page
            $etudiantsAvecSoldes = $etudiantsPagines->map(function($etudiant) {
                if (is_array($etudiant) && isset($etudiant['etudiant'])) {
                    $this->calculerSoldeEtudiant($etudiant);
                }
                return $etudiant;
            });
            
            // CORRECTION: Utiliser different partial selon page 1 ou pages suivantes  
            if ((int)$page === 1) {
                // Première page : tableau complet avec header
                $html = view('esbtp.reinscription.partials.liste-etudiants', [
                    'etudiants' => $etudiantsAvecSoldes,
                    'type' => $category === 'passages' ? 'passage' : ($category === 'rattrapages' ? 'rattrapage' : 'redoublement')
                ])->render();
            } else {
                // Pages suivantes : seulement les lignes TR
                $html = view('esbtp.reinscription.partials.lignes-etudiants', [
                    'etudiants' => $etudiantsAvecSoldes,
                    'type' => $category === 'passages' ? 'passage' : ($category === 'rattrapages' ? 'rattrapage' : 'redoublement')
                ])->render();
            }

            Log::info('Reinscription loadCategory completed', [
                'timestamp' => now()->toIso8601String(),
                'category' => $category,
                'total' => $total,
                'page_count' => $etudiantsAvecSoldes->count(),
                'has_more' => ($page * $perPage) < $total,
                'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
            ]);
            
            return response()->json([
                'html' => $html,
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => ($page * $perPage) < $total
            ]);
            
        } catch (\Exception $e) {
            Log::error('Reinscription loadCategory error', [
                'timestamp' => now()->toIso8601String(),
                'category' => $category,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function getEtudiantsValides($anneeUniversitaire)
    {
        // CORRECTION: Chercher les inscriptions de type 'réinscription' et status 'active'
        // au lieu de 'reinscription_status' = 'validated' qui n'est pas utilisé
        return \App\Models\ESBTPInscription::where('type_inscription', 'reinscription')
            ->where('status', 'active')
            ->when($anneeUniversitaire, function($query) use ($anneeUniversitaire) {
                return $query->where('annee_universitaire_id', $anneeUniversitaire->id);
            })
            ->with(['etudiant.paiements', 'classe.filiere', 'classe.niveau', 'anneeUniversitaire'])
            ->get()
            ->map(function($inscription) {
                return [
                    'etudiant' => $inscription->etudiant,
                    'inscription' => $inscription,
                    'classe' => $inscription->classe,
                    'decision' => 'Réinscription validée',
                    'moyenne_generale' => 0,
                    'matieres_echouees' => [],
                    'validated_at' => $inscription->created_at,
                    'validated_by' => $inscription->created_by ?? null,
                    'annee_universitaire' => $inscription->anneeUniversitaire->name,
                ];
            });
    }
    
    private function getEtudiantsAbandons($anneeUniversitaire, $type)
    {
        return ESBTPEtudiant::where('statut', 'abandon')
            ->where(function($query) use ($type) {
                if ($type === 'annee_scolaire') {
                    $query->where('abandon_type', 'annee_scolaire')
                          ->orWhereNull('abandon_type');
                } else {
                    $query->where('abandon_type', $type);
                }
            })
            ->whereHas('inscriptions', function($query) use ($anneeUniversitaire) {
                if ($anneeUniversitaire) {
                    $query->where('annee_universitaire_id', $anneeUniversitaire->id);
                }
            })
            ->with(['paiements', 'inscriptions' => function($query) use ($anneeUniversitaire) {
                if ($anneeUniversitaire) {
                    $query->where('annee_universitaire_id', $anneeUniversitaire->id);
                }
                $query->with(['filiere', 'niveauEtude', 'classe']);
            }])
            ->get()
            ->map(function($etudiant) {
                return [
                    'etudiant' => $etudiant,
                    'inscription' => $etudiant->inscriptions->first(),
                    'classe' => $etudiant->inscriptions->first()?->classe,
                    'decision' => 'abandon',
                    'moyenne_generale' => 0,
                    'matieres_echouees' => [],
                ];
            });
    }

    /**
     * Valider la réinscription d'un étudiant (le déplacer vers "validés")
     */
    public function validerReinscription(Request $request, $etudiantId)
    {
        $request->validate([
            'decision' => 'required|in:passage,redoublement,rattrapage',
            'observations' => 'nullable|string|max:500',
        ]);

        try {
            // Trouver l'inscription active de l'étudiant
            $inscription = \App\Models\ESBTPInscription::whereHas('etudiant', function($query) use ($etudiantId) {
                $query->where('id', $etudiantId);
            })
            ->whereHas('anneeUniversitaire', function($query) {
                $query->where('is_current', true);
            })
            ->first();

            if (!$inscription) {
                throw new \Exception("Inscription non trouvée pour cet étudiant");
            }

            // Mettre à jour le statut de réinscription
            $inscription->update([
                'reinscription_status' => 'validated',
                'reinscription_validated_at' => now(),
                'reinscription_validated_by' => auth()->id(),
                'reinscription_observations' => $request->decision . ' - ' . ($request->observations ?? 'Réinscription validée')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réinscription validée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $etudiantId)
    {
        $request->validate([
            'nouvelle_classe_id' => 'nullable|exists:esbtp_classes,id',
            'autre_classe_id' => 'nullable|exists:esbtp_classes,id',
            'decision' => 'required|in:passage,redoublement,rattrapage',
            'observations' => 'nullable|string',
            'selected_optionals' => 'nullable|string', // JSON des frais optionnels
            'affectation_status' => 'nullable|string|in:affecté,réaffecté,non_affecté',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'action_reliquat' => 'nullable|string|in:reporter,abandonner', // Gestion des reliquats pour superAdmin
        ]);

        try {
            // Déterminer quelle classe utiliser (selon le choix "Même filière" ou "Autre filière")
            $classeId = $request->input('autre_classe_id') ?: $request->input('nouvelle_classe_id');

            // Vérifier qu'au moins une classe est fournie
            if (!$classeId) {
                return back()->withErrors(['error' => 'Vous devez sélectionner une classe pour la réinscription.']);
            }

            // Décoder les frais optionnels sélectionnés
            $selectedOptionals = [];
            if ($request->selected_optionals) {
                $selectedOptionals = json_decode($request->selected_optionals, true) ?: [];
            }

            // Récupérer le statut d'affectation
            $affectationStatus = $request->input('affectation_status');

            // Si aucun statut fourni, utiliser 'affecté' par défaut
            if (empty($affectationStatus)) {
                $affectationStatus = $request->input('affectation_status_final');
                if (empty($affectationStatus)) {
                    $affectationStatus = 'affecté';
                }
            }

            $nouvelleInscription = $this->reinscriptionService->effectuerReinscription(
                $etudiantId,
                $classeId,  // Utiliser la classe déterminée ci-dessus
                $request->decision,
                $request->observations,
                $selectedOptionals,
                $affectationStatus,
                $request->annee_universitaire_id,
                $request->action_reliquat // Gestion des reliquats pour superAdmin
            );

            // Envoyer notification aux parents
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $reliquatMontant = $request->action_reliquat === 'report' ? $request->reliquat_montant : 0;
                $notificationService->notifyParentsReinscriptionCreated(
                    $nouvelleInscription,
                    $request->decision ?? 'passage',
                    $reliquatMontant ?? 0
                );
            } catch (\Exception $e) {
                \Log::error('Erreur envoi notification réinscription parent: ' . $e->getMessage());
            }

            return redirect()->route('esbtp.inscriptions.show', $nouvelleInscription->id)
                ->with('success', 'Réinscription effectuée avec succès ! Nouvelle inscription créée pour l\'année universitaire en cours.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la réinscription: ' . $e->getMessage()]);
        }
    }

    public function regles()
    {
        $regles = ESBTPRegleAcademique::all();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        
        return view('esbtp.reinscription.regles', compact('regles', 'niveaux', 'filieres'));
    }

    public function storeRegle(Request $request)
    {
        // Capturer les données validées pour éviter la vulnérabilité mass assignment
        $validated = $request->validate([
            'niveau' => 'required|string',
            'filiere' => 'required|string',
            'moyenne_passage' => 'required|numeric|min:0|max:20',
            'moyenne_rattrapage' => 'required|numeric|min:0|max:20',
            'max_matieres_rattrapage' => 'required|integer|min:1',
            'autoriser_redoublement' => 'boolean',
            'max_redoublements' => 'required|integer|min:1',
            'conditions_speciales' => 'nullable|string'
        ]);

        try {
            // Utiliser uniquement les données validées
            ESBTPRegleAcademique::updateOrCreate(
                [
                    'niveau' => $validated['niveau'],
                    'filiere' => $validated['filiere']
                ],
                $validated
            );

            return back()->with('success', 'Règle académique enregistrée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
        }
    }

    public function updateRegle(Request $request, $id)
    {
        // Capturer les données validées pour éviter la vulnérabilité mass assignment
        $validated = $request->validate([
            'moyenne_passage' => 'required|numeric|min:0|max:20',
            'moyenne_rattrapage' => 'required|numeric|min:0|max:20',
            'max_matieres_rattrapage' => 'required|integer|min:1',
            'autoriser_redoublement' => 'boolean',
            'max_redoublements' => 'required|integer|min:1',
            'conditions_speciales' => 'nullable|string',
            'actif' => 'boolean'
        ]);

        try {
            $regle = ESBTPRegleAcademique::findOrFail($id);
            // Utiliser uniquement les données validées
            $regle->update($validated);

            return back()->with('success', 'Règle académique mise à jour avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    public function destroyRegle($id)
    {
        try {
            $regle = ESBTPRegleAcademique::findOrFail($id);
            $regle->delete();

            return back()->with('success', 'Règle académique supprimée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Appliquer les filtres sur une collection d'étudiants
     */
    private function applyFiltersToEtudiants($etudiants, Request $request)
    {
        if (!$etudiants instanceof Collection) {
            $etudiants = collect($etudiants);
        } else {
            $etudiants = $etudiants->values();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $etudiants = $this->matcher->match($search, $etudiants, function ($item) {
                $etudiant = null;

                if (is_array($item)) {
                    $etudiant = $item['etudiant'] ?? null;
                } elseif (is_object($item) && property_exists($item, 'etudiant')) {
                    $etudiant = $item->etudiant;
                } elseif (is_object($item)) {
                    $etudiant = $item;
                }

                if (!$etudiant || !is_object($etudiant)) {
                    return [];
                }

                return [
                    'matricule' => $etudiant->matricule ?? null,
                    'nom' => $etudiant->nom ?? null,
                    'prenoms' => $etudiant->prenoms ?? null,
                    'full_name' => trim(($etudiant->prenoms ?? '') . ' ' . ($etudiant->nom ?? '')),
                    'reverse_full_name' => trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')),
                ];
            }, [
                'threshold' => 30,
                'limit' => 400,
                'boosts' => [
                    'matricule' => 15,
                    'full_name' => 6,
                    'reverse_full_name' => 6,
                ],
            ]);

            $etudiants = $etudiants->filter(function ($item) {
                $score = null;
                if (is_array($item)) {
                    $score = $item['fuzzy_score'] ?? null;
                } elseif (is_object($item) && property_exists($item, 'fuzzy_score')) {
                    $score = $item->fuzzy_score;
                }
                return $score === null || $score >= 80;
            })->values();
        }
        
        if ($request->filled('filiere_id')) {
            $etudiants = $etudiants->filter(function($item) use ($request) {
                $inscription = null;
                if (is_array($item) && isset($item['inscription'])) {
                    $inscription = $item['inscription'];
                } elseif (is_array($item) && isset($item['etudiant'])) {
                    $etudiant = $item['etudiant'];
                    $inscription = $etudiant->inscriptions()->with(['filiere'])->first();
                }
                
                return $inscription && $inscription->filiere_id == $request->filiere_id;
            });
        }
        
        if ($request->filled('niveau_id')) {
            $etudiants = $etudiants->filter(function($item) use ($request) {
                $inscription = null;
                if (is_array($item) && isset($item['inscription'])) {
                    $inscription = $item['inscription'];
                } elseif (is_array($item) && isset($item['etudiant'])) {
                    $etudiant = $item['etudiant'];
                    $inscription = $etudiant->inscriptions()->with(['niveau'])->first();
                }
                
                return $inscription && $inscription->niveau_id == $request->niveau_id;
            });
        }
        
        if ($request->filled('statut_paiement')) {
            $etudiants = $etudiants->filter(function($item) use ($request) {
                $etudiant = is_array($item) && isset($item['etudiant']) ? $item['etudiant'] : $item;
                
                if (!$etudiant || !is_object($etudiant)) return false;
                
                // Calculer le solde si pas déjà fait
                if (!isset($etudiant->solde_restant)) {
                    $this->calculerSoldeEtudiant($item);
                }
                
                if ($request->statut_paiement === 'solde') {
                    return $etudiant->solde_restant <= 0;
                } elseif ($request->statut_paiement === 'impaye') {
                return $etudiant->solde_restant > 0;
                }
                
                return true;
            });
        }
        
        return $etudiants->values();
    }

    public function exportResults(Request $request)
    {
        $anneeAcademique = $request->get('annee_academique', date('Y') . '-' . (date('Y') + 1));
        
        try {
            $resultats = $this->reinscriptionService->getEtudiantsParDecision($anneeAcademique);
            
            // Générer un fichier Excel ou PDF avec les résultats
            return response()->json([
                'message' => 'Export en cours de développement',
                'data' => $resultats
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer les années universitaires disponibles pour réinscription (courante et futures)
     */
    private function getAnneesUniversitairesFutures()
    {
        // Récupérer l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeCourante) {
            return collect(); // Retourner une collection vide si pas d'année courante
        }

        // Récupérer l'année courante ET les années futures
        // Cela permet de réinscrire les étudiants N-1 vers l'année courante N
        return \App\Models\ESBTPAnneeUniversitaire::where('is_active', true)
            ->where('start_date', '>=', $anneeCourante->start_date)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Enrichir les informations financières d'un étudiant
     */
    private function enrichirInformationsFinancieres($etudiant)
    {
        // Récupérer l'inscription la plus récente de l'étudiant avec classe
        $inscription = \App\Models\ESBTPInscription::whereNotNull('classe_id')
            ->where('etudiant_id', $etudiant->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($inscription) {
            // Calculer le total attendu et payé
            $totalAttendu = $this->calculerTotalAttendu($inscription);
            $totalPaye = $this->calculerTotalPaye($inscription);
            $soldeRestant = $totalAttendu - $totalPaye;

            $etudiant->montant_attendu = $totalAttendu;
            $etudiant->montant_paye = $totalPaye;
            $etudiant->solde_restant = $soldeRestant;
            $etudiant->peut_reinscrire = $soldeRestant <= 0;
        } else {
            // Pas d'inscription active
            $etudiant->montant_attendu = 0;
            $etudiant->montant_paye = 0;
            $etudiant->solde_restant = 0;
            $etudiant->peut_reinscrire = false;
        }
    }

    /**
     * API : Récupérer les niveaux d'étude pour une filière donnée
     * Utilisé par le select en cascade dans reinscription/create.blade.php
     *
     * @param  int  $filiereId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNiveauxByFiliere($filiereId)
    {
        try {
            $niveaux = \App\Models\ESBTPClasse::where('filiere_id', $filiereId)
                ->where('is_active', 1)
                ->with('niveau')
                ->get()
                ->pluck('niveau')
                ->filter()
                ->unique('id')
                ->sortBy('year')
                ->values()
                ->map(function($niveau) {
                    return [
                        'id' => $niveau->id,
                        'name' => $this->formatNiveauLabel($niveau->year, $niveau->type)
                    ];
                });

            return response()->json([
                'success' => true,
                'niveaux' => $niveaux
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur getNiveauxByFiliere: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des niveaux',
                'niveaux' => []
            ], 500);
        }
    }

    /**
     * Formate le label d'un niveau : "1ère année — BTS", "2ème année — Licence", etc.
     */
    private function formatNiveauLabel(int $year, string $type): string
    {
        $suffixes = [1 => 'ère', 2 => 'ème', 3 => 'ème', 4 => 'ème', 5 => 'ème'];
        $suffix = $suffixes[$year] ?? 'ème';
        return "{$year}{$suffix} année — {$type}";
    }

    /**
     * API : Récupérer les classes pour une filière et un niveau donnés
     * Utilisé par le searchable select en cascade dans reinscription/create.blade.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassesByFiliereNiveau(Request $request)
    {
        try {
            $filiereId = $request->input('filiere_id');
            $niveauId = $request->input('niveau_id');

            if (!$filiereId || !$niveauId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Filière et niveau requis',
                    'classes' => []
                ], 400);
            }

            // Récupérer les classes actives pour cette combinaison filière + niveau
            $classes = \App\Models\ESBTPClasse::with(['filiere', 'niveau'])
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $niveauId)
                ->where('is_active', 1)
                ->orderBy('name')
                ->get()
                ->map(function($classe) {
                    return [
                        'id' => $classe->id,
                        'name' => $classe->name,
                        'filiere' => [
                            'id' => $classe->filiere->id ?? null,
                            'name' => $classe->filiere->name ?? 'N/A'
                        ],
                        'niveau' => [
                            'id' => $classe->niveau->id ?? null,
                            'name' => $classe->niveau->name ?? 'N/A'
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'classes' => $classes
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur getClassesByFiliereNiveau: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des classes',
                'classes' => []
            ], 500);
        }
    }
}
