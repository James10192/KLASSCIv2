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

class ESBTPComptabilitePaiementController extends Controller
{
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

}
