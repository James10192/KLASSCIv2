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
        
        try {
            $resultats = $this->reinscriptionService->getEtudiantsParDecision($anneeAcademique);
            
            // Utiliser l'année courante
            $anneeUniversitaire = $anneeCourante;
            
            // Ajouter les étudiants qui ont abandonné - séparer par type - FILTRÉS PAR ANNÉE COURANTE
            $abandonsAnneeScolaire = ESBTPEtudiant::where('statut', 'abandon')
                ->where(function($query) {
                    $query->where('abandon_type', 'annee_scolaire')
                          ->orWhereNull('abandon_type'); // Les anciens abandons sans type spécifié
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
                ->get();
                
            $abandonsEcole = ESBTPEtudiant::where('statut', 'abandon')
                ->where('abandon_type', 'ecole')
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
                ->get();
                
            $resultats['abandons_annee'] = $abandonsAnneeScolaire;
            $resultats['abandons_ecole'] = $abandonsEcole;
            
            // Ajouter les étudiants dont la réinscription a été validée - FILTRÉS PAR ANNÉE COURANTE
            $valides = \App\Models\ESBTPInscription::where('reinscription_status', 'validated')
                ->when($anneeUniversitaire, function($query) use ($anneeUniversitaire) {
                    return $query->where('annee_universitaire_id', $anneeUniversitaire->id);
                })
                ->with(['etudiant.paiements', 'classe.filiere', 'classe.niveau', 'reinscriptionValidatedBy'])
                ->get()
                ->map(function($inscription) {
                    // Transformer pour correspondre au format attendu par la vue
                    return [
                        'etudiant' => $inscription->etudiant,
                        'inscription' => $inscription,
                        'classe' => $inscription->classe,
                        'decision' => $inscription->reinscription_observations ?? 'Validée',
                        'moyenne_generale' => 0, // Peut être calculé si nécessaire
                        'matieres_echouees' => [],
                        'validated_at' => $inscription->reinscription_validated_at,
                        'validated_by' => $inscription->reinscriptionValidatedBy,
                    ];
                });
                
            $resultats['valides'] = $valides;
            
            // Calculer les soldes pour tous les étudiants
            $this->calculerSoldesEtudiants($resultats);
            
            return view('esbtp.reinscription.index', compact('resultats', 'anneeAcademique'));
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des données vides pour permettre l'affichage de la page
            $resultats = [
                'passages' => [],
                'rattrapages' => [],
                'redoublements' => [],
                'abandons' => [],
                'errors' => [['error' => $e->getMessage()]]
            ];
            
            return view('esbtp.reinscription.index', compact('resultats', 'anneeAcademique'))
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
            $etudiant->peut_reinscrire = $soldeRestant <= 0;
            
            // Ajouter l'inscription pour l'accès aux données de classe dans la vue
            $analyse['inscription'] = $inscription;
            
            return view('esbtp.reinscription.show', compact('analyse', 'classesProposees', 'anneeAcademique'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()]);
        }
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