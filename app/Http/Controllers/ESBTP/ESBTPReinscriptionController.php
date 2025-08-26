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
                foreach ($resultats[$categorie] as &$etudiant) {
                    if (is_object($etudiant) && isset($etudiant->paiements)) {
                        $etudiant->solde_valide = $etudiant->paiements->where('status', 'validé')->sum('montant');
                        $etudiant->solde_attente = $etudiant->paiements->where('status', 'en_attente')->sum('montant');
                        $etudiant->solde_total = $etudiant->solde_valide + $etudiant->solde_attente;
                        
                        // Déterminer si l'étudiant peut se réinscrire (solde minimum requis par exemple)
                        $etudiant->peut_reinscrire = $etudiant->solde_valide >= 200000; // Exemple: 200 000 FCFA minimum
                    }
                }
            }
        }
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