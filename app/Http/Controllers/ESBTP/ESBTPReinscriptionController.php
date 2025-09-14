<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Services\ReeinscriptionService;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPFiliere;
use Illuminate\Http\Request;

class ESBTPReinscriptionController extends Controller
{
    protected $reinscriptionService;

    public function __construct(ReeinscriptionService $reinscriptionService)
    {
        $this->reinscriptionService = $reinscriptionService;
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
     * Calculer le total attendu pour une inscription
     */
    private function calculerTotalAttendu($inscription)
    {
        // Récupérer les catégories de frais obligatoires
        $mandatoryCategories = \App\Models\ESBTPFraisCategory::where('is_mandatory', true)
            ->where('is_active', true)
            ->get();
        
        $totalAttendu = 0;
        
        foreach ($mandatoryCategories as $category) {
            $rule = $category->getApplicableRule(
                $inscription->filiere_id, 
                $inscription->niveau_id, 
                $inscription->annee_universitaire_id
            );
            
            $montant = $rule ? $rule->amount : $category->default_amount;
            $totalAttendu += $montant;
        }
        
        // Ajouter les frais optionnels souscrits
        $subscriptions = \App\Models\ESBTPFraisSubscription::getActiveSubscriptions($inscription->id);
        foreach ($subscriptions as $subscription) {
            $totalAttendu += $subscription->amount;
        }
        
        return $totalAttendu;
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

            // Logique d'éligibilité selon le rôle
            $isSuperAdmin = auth()->user() && auth()->user()->isSuperAdmin();
            $etudiant->peut_reinscrire = $soldeRestant <= 0 || $isSuperAdmin;
            $etudiant->reliquat_possible = $isSuperAdmin && $soldeRestant > 0;
            $etudiant->reliquat_montant = $isSuperAdmin ? max(0, $soldeRestant) : 0;
            
            // Ajouter l'inscription pour l'accès aux données de classe dans la vue
            $analyse['inscription'] = $inscription;
            
            return view('esbtp.reinscription.show', compact('analyse', 'classesProposees', 'anneeAcademique'));
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
            // Récupérer l'analyse de l'étudiant (même logique que show)
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

            $analyse['inscription'] = $inscription;

            return view('esbtp.reinscription.create', compact(
                'analyse',
                'classesParDecision',
                'fraisParClasse',
                'anneeAcademique',
                'fraisNonSoldes',
                'isSuperAdmin'
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
                    $etudiants = collect($resultats[$category] ?? []);
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
                    $etudiants = collect($resultats['errors'] ?? []);
                    break;
                    
                default:
                    return response()->json(['error' => 'Catégorie inconnue'], 400);
            }
            
            // Appliquer les filtres sur la collection d'étudiants
            $etudiants = $this->applyFiltersToEtudiants($etudiants, $request);
            
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
            
            return response()->json([
                'html' => $html,
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => ($page * $perPage) < $total
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function getEtudiantsValides($anneeUniversitaire)
    {
        return \App\Models\ESBTPInscription::where('reinscription_status', 'validated')
            ->when($anneeUniversitaire, function($query) use ($anneeUniversitaire) {
                return $query->where('annee_universitaire_id', $anneeUniversitaire->id);
            })
            ->with(['etudiant.paiements', 'classe.filiere', 'classe.niveau', 'reinscriptionValidatedBy'])
            ->get()
            ->map(function($inscription) {
                return [
                    'etudiant' => $inscription->etudiant,
                    'inscription' => $inscription,
                    'classe' => $inscription->classe,
                    'decision' => $inscription->reinscription_observations ?? 'Validée',
                    'moyenne_generale' => 0,
                    'matieres_echouees' => [],
                    'validated_at' => $inscription->reinscription_validated_at,
                    'validated_by' => $inscription->reinscriptionValidatedBy,
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
            'nouvelle_classe_id' => 'required|exists:esbtp_classes,id',
            'decision' => 'required|in:passage,redoublement,rattrapage',
            'observations' => 'nullable|string',
            'selected_optionals' => 'nullable|string' // JSON des frais optionnels
        ]);

        try {
            // Décoder les frais optionnels sélectionnés
            $selectedOptionals = [];
            if ($request->selected_optionals) {
                $selectedOptionals = json_decode($request->selected_optionals, true) ?: [];
            }

            \Log::info('Début réinscription avec frais optionnels', [
                'etudiant_id' => $etudiantId,
                'nouvelle_classe_id' => $request->nouvelle_classe_id,
                'decision' => $request->decision,
                'selected_optionals' => $selectedOptionals
            ]);

            $nouvelleInscription = $this->reinscriptionService->effectuerReinscription(
                $etudiantId,
                $request->nouvelle_classe_id,
                $request->decision,
                $request->observations,
                $selectedOptionals
            );

            return redirect()->route('esbtp.inscriptions.show', $nouvelleInscription->id)
                ->with('success', 'Réinscription effectuée avec succès ! Nouvelle inscription créée pour l\'année universitaire en cours.');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la réinscription', [
                'etudiant_id' => $etudiantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
        $request->validate([
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
            ESBTPRegleAcademique::updateOrCreate(
                [
                    'niveau' => $request->niveau,
                    'filiere' => $request->filiere
                ],
                $request->all()
            );

            return back()->with('success', 'Règle académique enregistrée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
        }
    }

    public function updateRegle(Request $request, $id)
    {
        $request->validate([
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
            $regle->update($request->all());

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
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $etudiants = $etudiants->filter(function($item) use ($search) {
                $etudiant = is_array($item) && isset($item['etudiant']) ? $item['etudiant'] : $item;
                
                if (!$etudiant || !is_object($etudiant)) return false;
                
                $nom = strtolower($etudiant->nom ?? '');
                $prenoms = strtolower($etudiant->prenoms ?? '');
                $matricule = strtolower($etudiant->matricule ?? '');
                
                return str_contains($nom, $search) || 
                       str_contains($prenoms, $search) || 
                       str_contains($matricule, $search);
            });
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
        
        return $etudiants;
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
}