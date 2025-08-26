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
        $anneeAcademique = $request->get('annee_academique', date('Y') . '-' . (date('Y') + 1));
        
        try {
            $resultats = $this->reinscriptionService->getEtudiantsParDecision($anneeAcademique);
            
            // Ajouter les étudiants qui ont abandonné
            $abandons = ESBTPEtudiant::where('statut', 'abandon')
                ->with(['paiements', 'inscriptions.filiere', 'inscriptions.niveauEtude', 'inscriptions.classe'])
                ->get();
                
            $resultats['abandons'] = $abandons;
            
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
        foreach (['passages', 'rattrapages', 'redoublements', 'abandons'] as $categorie) {
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
                            // (si il a payé au moins 50% de ses frais obligatoires)
                            $etudiant->peut_reinscrire = $soldeRestant <= ($totalAttendu * 0.5);
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
        ]);

        try {
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
            $etudiant->update([
                'statut' => 'abandon',
                'motif_abandon' => $request->motif_abandon,
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
            
            // Ajouter l'inscription pour l'accès aux données de classe dans la vue
            $analyse['inscription'] = $inscription;
            
            return view('esbtp.reinscription.show', compact('analyse', 'classesProposees', 'anneeAcademique'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $etudiantId)
    {
        $request->validate([
            'nouvelle_classe_id' => 'required|exists:esbtp_classes,id',
            'decision' => 'required|in:passage,redoublement,rattrapage',
            'observations' => 'nullable|string'
        ]);

        try {
            $etudiant = $this->reinscriptionService->effectuerReinscription(
                $etudiantId,
                $request->nouvelle_classe_id,
                $request->decision,
                $request->observations
            );

            return redirect()->route('esbtp.reinscription.index')
                ->with('success', 'Réinscription effectuée avec succès pour ' . $etudiant->nom . ' ' . $etudiant->prenom);
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