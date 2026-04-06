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

class ESBTPComptabiliteFraisController extends Controller
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
            $data = $request->only([
                'etudiant_id', 'annee_universitaire_id', 'type_bourse',
                'montant', 'pourcentage', 'date_debut', 'date_fin',
                'statut', 'organisme_financeur', 'conditions', 'commentaires',
            ]);
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
            $bourse->update($request->only([
                'etudiant_id', 'annee_universitaire_id', 'type_bourse',
                'montant', 'pourcentage', 'date_debut', 'date_fin',
                'statut', 'organisme_financeur', 'conditions', 'commentaires',
            ]));

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

}
