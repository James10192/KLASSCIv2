<?php

namespace App\Http\Controllers;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ESBTPPaiementController extends Controller
{
    /**
     * Constructeur du contrôleur.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:paiements.view', ['only' => ['index', 'show', 'paiementsEtudiant']]);
        $this->middleware('permission:paiements.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:paiements.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:paiements.delete', ['only' => ['destroy']]);
        $this->middleware('permission:paiements.validate', ['only' => ['valider', 'rejeter', 'genererRecu']]);
    }

    /**
     * Affiche la liste des paiements.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Récupérer les paramètres de filtrage
        $search = $request->input('search');
        $status = $request->input('status');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $anneeId = $request->input('annee_id');

        // Récupérer les années universitaires pour le filtre
        $annees = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Construire la requête avec toutes les relations nécessaires
        $query = ESBTPPaiement::with([
            'etudiant.user', 
            'inscription.anneeUniversitaire', 
            'inscription.filiere',
            'inscription.niveauEtude', 
            'validatedBy', 
            'fraisCategory',
            'categorie' // Ancien système pour compatibilité
        ])->orderBy('created_at', 'desc');

        // Appliquer les filtres
        if ($search) {
            $query->whereHas('etudiant', function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('matricule', 'like', "%{$search}%");
            })
            ->orWhere('numero_recu', 'like', "%{$search}%")
            ->orWhere('reference_paiement', 'like', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateDebut) {
            $query->whereDate('date_paiement', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_paiement', '<=', $dateFin);
        }

        if ($anneeId) {
            $query->whereHas('inscription', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            });
        } else {
            // Par défaut, afficher les paiements de l'année en cours
            $query->anneeEnCours();
        }

        // Paginer les résultats
        $paiements = $query->paginate(15);

        // Calculer les vraies statistiques basées sur les inscriptions et leurs frais attendus
        $categoriesStats = $this->calculateCategoryStats(null);
        
        // Calculer le total attendu et payé à partir des catégories
        $totalAttendu = $categoriesStats['academic_total'] + $categoriesStats['service_total'] + $categoriesStats['administrative_total'];
        $totalPaye = $categoriesStats['academic_paid'] + $categoriesStats['service_paid'] + $categoriesStats['administrative_paid'];
        $totalEnAttente = $categoriesStats['academic_pending'] + $categoriesStats['service_pending'] + $categoriesStats['administrative_pending'];
        
        $stats = [
            // Statistiques générales basées sur les vraies données
            'total' => $query->count(),
            'montant_total' => $totalAttendu,
            'valides' => $query->where('status', 'validé')->count(),
            'montant_valide' => $totalPaye,
            'en_attente' => $query->where('status', 'en_attente')->count(),
            'montant_en_attente' => $totalEnAttente,
        ];
        
        // Ajouter les statistiques par catégorie
        $stats = array_merge($stats, $categoriesStats);
        
        // Calcul du taux de recouvrement global corrigé
        $stats['recovery_rate'] = $totalAttendu > 0 ? 
            round(($totalPaye / $totalAttendu) * 100, 1) : 0;

        return view('esbtp.paiements.index', compact('paiements', 'annees', 'stats'));
    }

    /**
     * Calcule les vraies statistiques basées sur les inscriptions et leurs frais attendus.
     */
    private function calculateCategoryStats($baseQuery = null)
    {
        // Obtenir l'année en cours pour les calculs
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        // Si aucune année courante, prendre la plus récente
        if (!$anneeEnCours) {
            $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->first();
        }
        
        if (!$anneeEnCours) {
            return $this->getEmptyStats();
        }

        // Récupérer toutes les inscriptions actives de l'année en cours
        $inscriptions = \App\Models\ESBTPInscription::with(['filiere', 'niveauEtude'])
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', 'active')
            ->get();

        $stats = [
            'academic_paid' => 0,
            'service_paid' => 0,
            'administrative_paid' => 0,
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];

        // Pour chaque inscription, calculer les montants attendus et payés
        foreach ($inscriptions as $inscription) {
            $fraisStats = $this->calculateFraisForInscription($inscription);
            
            foreach (['academic', 'service', 'administrative'] as $type) {
                $stats[$type . '_total'] += $fraisStats[$type]['expected'];
                $stats[$type . '_paid'] += $fraisStats[$type]['paid'];
                $stats[$type . '_pending'] += $fraisStats[$type]['expected'] - $fraisStats[$type]['paid'];
            }
        }

        // S'assurer que les pending ne sont jamais négatifs
        foreach (['academic', 'service', 'administrative'] as $type) {
            $stats[$type . '_pending'] = max(0, $stats[$type . '_pending']);
        }

        return $stats;
    }

    /**
     * Calcule les frais attendus et payés pour une inscription donnée.
     */
    private function calculateFraisForInscription($inscription)
    {
        $fraisStats = [
            'academic' => ['expected' => 0, 'paid' => 0],
            'service' => ['expected' => 0, 'paid' => 0],
            'administrative' => ['expected' => 0, 'paid' => 0],
        ];

        // Récupérer toutes les catégories de frais actives
        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        foreach ($categories as $category) {
            $categoryType = $category->category_type ?? 'academic';
            $expectedAmount = 0;

            if ($category->is_mandatory) {
                // Frais obligatoire : vérifier s'il y a une configuration pour cette classe
                $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->where('is_active', true)
                    ->where('is_valid', true)
                    ->first();

                if ($configuration) {
                    $expectedAmount = $configuration->amount;
                } else {
                    // Utiliser le montant par défaut si pas de configuration spécifique
                    $expectedAmount = $category->default_amount ?? 0;
                }
            } else {
                // Service optionnel : vérifier s'il y a une souscription active
                $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('is_active', true)
                    ->first();

                if ($subscription) {
                    $expectedAmount = $subscription->amount;
                }
            }

            // Si un montant est attendu, l'ajouter aux stats
            if ($expectedAmount > 0) {
                $fraisStats[$categoryType]['expected'] += $expectedAmount;

                // Calculer le montant payé pour cette catégorie
                $paidAmount = ESBTPPaiement::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('status', 'validé')
                    ->sum('montant');

                $fraisStats[$categoryType]['paid'] += $paidAmount;
            }
        }

        return $fraisStats;
    }

    /**
     * Retourne des stats vides en cas de problème.
     */
    private function getEmptyStats()
    {
        return [
            'academic_paid' => 0,
            'service_paid' => 0,
            'administrative_paid' => 0,
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];
    }

    /**
     * Détermine le type de catégorie d'un paiement (nouveau système + fallback ancien).
     */
    private function determineCategoryType($paiement)
    {
        // D'abord essayer avec le nouveau système
        if ($paiement->fraisCategory) {
            return $paiement->fraisCategory->category_type ?? 'academic';
        }

        // Fallback sur l'ancien système
        if ($paiement->categorie) {
            return $this->mapOldCategoryToType($paiement->categorie->nom ?? '');
        }

        // Fallback basé sur le motif ou type_paiement
        if ($paiement->motif || $paiement->type_paiement) {
            return $this->inferCategoryFromMotif($paiement->motif ?? $paiement->type_paiement ?? '');
        }

        // Par défaut, considérer comme academic
        return 'academic';
    }

    /**
     * Mappe les anciennes catégories vers les nouveaux types.
     */
    private function mapOldCategoryToType($categoryName)
    {
        $name = strtolower($categoryName);
        
        if (str_contains($name, 'cantine') || str_contains($name, 'transport')) {
            return 'service';
        }
        
        if (str_contains($name, 'documentation') || str_contains($name, 'examen')) {
            return 'administrative'; 
        }
        
        return 'academic'; // inscription, scolarité par défaut
    }

    /**
     * Infère le type de catégorie à partir du motif.
     */
    private function inferCategoryFromMotif($motif)
    {
        $motif = strtolower($motif);
        
        if (str_contains($motif, 'cantine') || str_contains($motif, 'transport')) {
            return 'service';
        }
        
        if (str_contains($motif, 'documentation') || str_contains($motif, 'examen')) {
            return 'administrative';
        }
        
        return 'academic';
    }

    /**
     * Affiche le formulaire de création d'un paiement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $etudiantId = $request->input('etudiant_id');
        $inscriptionId = $request->input('inscription_id');

        $etudiant = null;
        $inscription = null;

        // Si un étudiant est spécifié, récupérer ses informations
        if ($etudiantId) {
            $etudiant = ESBTPEtudiant::with(['user', 'inscriptions.anneeUniversitaire', 'inscriptions.filiere', 'inscriptions.niveauEtude'])
                ->findOrFail($etudiantId);

            // Si aucune inscription n'est spécifiée, prendre la plus récente
            if (!$inscriptionId && $etudiant->inscriptions->count() > 0) {
                $inscription = $etudiant->inscriptions->sortByDesc('created_at')->first();
            }
        }

        // Si une inscription est spécifiée, la récupérer
        if ($inscriptionId) {
            $inscription = ESBTPInscription::with(['etudiant.user', 'anneeUniversitaire', 'filiere', 'niveauEtude'])
                ->findOrFail($inscriptionId);

            // Si aucun étudiant n'est spécifié, prendre celui de l'inscription
            if (!$etudiant) {
                $etudiant = $inscription->etudiant;
            }
        }

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return view('esbtp.paiements.create', compact('etudiant', 'inscription', 'anneeEnCours'));
    }

    /**
     * Enregistre un nouveau paiement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validated = $request->validate([
            'inscription_id' => 'required|exists:esbtp_inscriptions,id',
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ]);

        // Vérifier que l'étudiant correspond à l'inscription
        $inscription = ESBTPInscription::findOrFail($validated['inscription_id']);
        if ($inscription->etudiant_id != $validated['etudiant_id']) {
            return redirect()->back()->withErrors(['etudiant_id' => 'L\'étudiant ne correspond pas à l\'inscription sélectionnée.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Récupérer la catégorie de frais pour définir le motif
            $fraisCategory = \App\Models\ESBTPFraisCategory::find($validated['frais_category_id']);

            // Générer un numéro de reçu
            $numeroRecu = ESBTPPaiement::genererNumeroRecu();

            // Créer le paiement
            $paiement = new ESBTPPaiement($validated);
            $paiement->numero_recu = $numeroRecu;
            $paiement->status = 'en_attente';
            $paiement->motif = $fraisCategory ? $fraisCategory->name : 'Paiement de frais'; // Pour compatibilité
            $paiement->created_by = Auth::id();
            $paiement->save();

            DB::commit();

            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('success', 'Paiement enregistré avec succès. Numéro de reçu : ' . $numeroRecu);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement du paiement : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'enregistrement du paiement.'])
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un paiement.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

        return view('esbtp.paiements.show', compact('paiement'));
    }

    /**
     * Affiche le formulaire de modification d'un paiement.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude'
        ])->findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        return view('esbtp.paiements.edit', compact('paiement'));
    }

    /**
     * Met à jour un paiement existant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        // Valider les données du formulaire
        $validated = $request->validate([
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'motif' => 'required|string',
            'commentaire' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Mettre à jour le paiement
            $paiement->fill($validated);
            $paiement->updated_by = Auth::id();
            $paiement->save();

            DB::commit();

            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('success', 'Paiement mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du paiement : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la mise à jour du paiement.'])
                ->withInput();
        }
    }

    /**
     * Valide un paiement.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function valider($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être validé
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('info', 'Ce paiement a déjà été validé.');
        }

        try {
            DB::beginTransaction();

            // Mettre à jour le statut du paiement
            $paiement->status = 'validé';
            $paiement->date_validation = Carbon::now();
            $paiement->validated_by = Auth::id();
            $paiement->updated_by = Auth::id();
            $paiement->save();

            DB::commit();

            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('success', 'Paiement validé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la validation du paiement : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la validation du paiement.'])
                ->withInput();
        }
    }

    /**
     * Rejette un paiement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function rejeter(Request $request, $id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être rejeté
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', 'Ce paiement a déjà été validé et ne peut pas être rejeté.');
        }

        // Valider les données du formulaire
        $validated = $request->validate([
            'commentaire' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Mettre à jour le statut du paiement
            $paiement->status = 'rejeté';
            $paiement->commentaire = $validated['commentaire'];
            $paiement->updated_by = Auth::id();
            $paiement->save();

            DB::commit();

            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('success', 'Paiement rejeté avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du rejet du paiement : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors du rejet du paiement.'])
                ->withInput();
        }
    }

    /**
     * Génère un reçu de paiement au format PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function genererRecu($id)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy'
        ])->findOrFail($id);

        // Générer le PDF
        $pdf = PDF::loadView('esbtp.paiements.recu', compact('paiement'));

        // Définir le nom du fichier
        $filename = 'Recu_' . $paiement->numero_recu . '.pdf';

        // Retourner le PDF pour téléchargement
        return $pdf->download($filename);
    }

    /**
     * Affiche le suivi des paiements par catégorie de frais.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function suiviCategories(Request $request)
    {
        // Récupérer les paramètres de filtrage
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $anneeId = $request->input('annee_id');
        $categoryId = $request->input('category_id');

        // Récupérer les années universitaires pour le filtre
        $annees = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->get();
        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        // Année par défaut (année en cours)
        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }

        // Construire la requête pour les inscriptions actives
        $inscriptionsQuery = \App\Models\ESBTPInscription::with([
            'etudiant.user', 
            'filiere', 
            'niveauEtude', 
            'anneeUniversitaire'
        ])->where('status', 'active');

        // Appliquer les filtres
        if ($anneeId) {
            $inscriptionsQuery->where('annee_universitaire_id', $anneeId);
        }
        if ($filiereId) {
            $inscriptionsQuery->where('filiere_id', $filiereId);
        }
        if ($niveauId) {
            $inscriptionsQuery->where('niveau_id', $niveauId);
        }

        $inscriptions = $inscriptionsQuery->get();

        // Si une catégorie spécifique est sélectionnée, analyser en détail
        $detailsCategorie = null;
        if ($categoryId) {
            $category = \App\Models\ESBTPFraisCategory::find($categoryId);
            if ($category) {
                $detailsCategorie = $this->analyserCategorieDetaille($category, $inscriptions);
            }
        }

        // Statistiques globales par catégorie
        $statistiquesCategories = $this->calculerStatistiquesCategories($inscriptions);

        // Vue d'ensemble des étudiants par statut de paiement
        $vueEnsemble = $this->calculerVueEnsemble($inscriptions);

        return view('esbtp.paiements.suivi-categories', compact(
            'inscriptions',
            'annees',
            'filieres', 
            'niveaux',
            'categories',
            'statistiquesCategories',
            'vueEnsemble',
            'detailsCategorie',
            'anneeId',
            'filiereId',
            'niveauId',
            'categoryId'
        ));
    }

    /**
     * Analyser une catégorie en détail
     */
    private function analyserCategorieDetaille($category, $inscriptions)
    {
        $details = [
            'category' => $category,
            'etudiants_a_jour' => collect(),
            'etudiants_en_retard' => collect(),
            'etudiants_non_payes' => collect(),
            'montant_total_attendu' => 0,
            'montant_total_recu' => 0,
        ];

        foreach ($inscriptions as $inscription) {
            // Vérifier si l'étudiant est concerné par ce frais
            $estConcerne = false;
            $montantAttendu = 0;

            if ($category->is_mandatory) {
                // Frais obligatoire : tous les étudiants sont concernés
                $estConcerne = true;
                $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->where('is_active', true)
                    ->first();
                $montantAttendu = $configuration ? $configuration->amount : $category->default_amount;
            } else {
                // Service optionnel : vérifier s'il y a une souscription active
                $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($subscription) {
                    $estConcerne = true;
                    $montantAttendu = $subscription->amount;
                }
            }

            // Traiter seulement les étudiants concernés
            if ($estConcerne) {
                $details['montant_total_attendu'] += $montantAttendu;

                // Vérifier les paiements de l'étudiant pour cette catégorie
                $paiements = ESBTPPaiement::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('status', 'validé')
                    ->get();

                $montantPaye = $paiements->sum('montant');
                $details['montant_total_recu'] += $montantPaye;

                $statutEtudiant = [
                    'inscription' => $inscription,
                    'montant_attendu' => $montantAttendu,
                    'montant_paye' => $montantPaye,
                    'solde' => $montantAttendu - $montantPaye,
                    'pourcentage' => $montantAttendu > 0 ? round(($montantPaye / $montantAttendu) * 100, 1) : 0,
                    'derniers_paiements' => $paiements->sortByDesc('date_paiement')->take(3),
                ];

                // Catégoriser l'étudiant
                if ($montantPaye >= $montantAttendu) {
                    $details['etudiants_a_jour']->push($statutEtudiant);
                } elseif ($montantPaye > 0) {
                    $details['etudiants_en_retard']->push($statutEtudiant);
                } else {
                    $details['etudiants_non_payes']->push($statutEtudiant);
                }
            }
        }

        return $details;
    }

    /**
     * Calculer les statistiques par catégorie
     */
    private function calculerStatistiquesCategories($inscriptions)
    {
        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();
        $statistiques = [];

        foreach ($categories as $category) {
            $stats = [
                'category' => $category,
                'total_etudiants' => $inscriptions->count(),
                'etudiants_concernes' => 0, // Nouveaux: étudiants concernés par ce frais
                'etudiants_a_jour' => 0,
                'etudiants_en_retard' => 0,
                'etudiants_non_payes' => 0,
                'montant_total_attendu' => 0,
                'montant_total_recu' => 0,
                'taux_recouvrement' => 0,
            ];

            foreach ($inscriptions as $inscription) {
                // Vérifier si l'étudiant est concerné par ce frais
                $estConcerne = false;
                $montantAttendu = 0;

                if ($category->is_mandatory) {
                    // Frais obligatoire : tous les étudiants sont concernés
                    $estConcerne = true;
                    $rule = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                        ->where('filiere_id', $inscription->filiere_id)
                        ->where('niveau_id', $inscription->niveau_id)
                        ->first();
                    $montantAttendu = $rule ? $rule->amount : $category->default_amount;
                } else {
                    // Service optionnel : vérifier s'il y a une souscription active
                    $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($subscription) {
                        $estConcerne = true;
                        $montantAttendu = $subscription->amount;
                    }
                }

                // Traiter seulement les étudiants concernés
                if ($estConcerne) {
                    $stats['etudiants_concernes']++;
                    $stats['montant_total_attendu'] += $montantAttendu;

                    // Paiements de l'étudiant pour cette catégorie
                    $montantPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('status', 'validé')
                        ->sum('montant');

                    $stats['montant_total_recu'] += $montantPaye;

                    // Catégorisation
                    if ($montantPaye >= $montantAttendu) {
                        $stats['etudiants_a_jour']++;
                    } elseif ($montantPaye > 0) {
                        $stats['etudiants_en_retard']++;
                    } else {
                        $stats['etudiants_non_payes']++;
                    }
                }
            }

            // Calcul du taux de recouvrement basé sur les montants attendus réels
            $stats['taux_recouvrement'] = $stats['montant_total_attendu'] > 0 
                ? round(($stats['montant_total_recu'] / $stats['montant_total_attendu']) * 100, 1) 
                : 0;

            $statistiques[] = $stats;
        }

        return collect($statistiques);
    }

    /**
     * Calculer la vue d'ensemble globale
     */
    private function calculerVueEnsemble($inscriptions)
    {
        $totalEtudiants = $inscriptions->count();
        $etudiantsEnRegle = 0;
        $etudiantsEnRetard = 0;
        $etudiantsNonPayes = 0;
        $montantTotalAttendu = 0;
        $montantTotalRecu = 0;

        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        foreach ($inscriptions as $inscription) {
            $etudiantEnRegle = true;
            $etudiantAPayeQuelqueChose = false;
            $montantEtudiantAttendu = 0;
            $montantEtudiantPaye = 0;

            foreach ($categories as $category) {
                // Vérifier si l'étudiant est concerné par ce frais
                $estConcerne = false;
                $montantAttendu = 0;

                if ($category->is_mandatory) {
                    // Frais obligatoire : tous les étudiants sont concernés
                    $estConcerne = true;
                    $rule = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                        ->where('filiere_id', $inscription->filiere_id)
                        ->where('niveau_id', $inscription->niveau_id)
                        ->first();
                    $montantAttendu = $rule ? $rule->amount : $category->default_amount;
                } else {
                    // Service optionnel : vérifier s'il y a une souscription active
                    $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($subscription) {
                        $estConcerne = true;
                        $montantAttendu = $subscription->amount;
                    }
                }

                // Traiter seulement si l'étudiant est concerné
                if ($estConcerne) {
                    $montantEtudiantAttendu += $montantAttendu;
                    $montantTotalAttendu += $montantAttendu;

                    // Paiements de l'étudiant
                    $montantPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('status', 'validé')
                        ->sum('montant');

                    $montantEtudiantPaye += $montantPaye;
                    $montantTotalRecu += $montantPaye;

                    if ($montantPaye < $montantAttendu) {
                        $etudiantEnRegle = false;
                    }
                    
                    if ($montantPaye > 0) {
                        $etudiantAPayeQuelqueChose = true;
                    }
                }
            }

            // Catégorisation globale de l'étudiant (seulement s'il a des frais attendus)
            if ($montantEtudiantAttendu > 0) {
                if ($etudiantEnRegle) {
                    $etudiantsEnRegle++;
                } elseif ($etudiantAPayeQuelqueChose) {
                    $etudiantsEnRetard++;
                } else {
                    $etudiantsNonPayes++;
                }
            }
        }

        return [
            'total_etudiants' => $totalEtudiants,
            'etudiants_en_regle' => $etudiantsEnRegle,
            'etudiants_en_retard' => $etudiantsEnRetard,
            'etudiants_non_payes' => $etudiantsNonPayes,
            'montant_total_attendu' => $montantTotalAttendu,
            'montant_total_recu' => $montantTotalRecu,
            'taux_recouvrement_global' => $montantTotalAttendu > 0 
                ? round(($montantTotalRecu / $montantTotalAttendu) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Récupère les paiements d'un étudiant.
     *
     * @param  int  $etudiantId
     * @return \Illuminate\Http\Response
     */
    public function paiementsEtudiant($etudiantId)
    {
        $etudiant = ESBTPEtudiant::with(['user', 'inscriptions.anneeUniversitaire'])->findOrFail($etudiantId);

        $paiements = ESBTPPaiement::with(['inscription.anneeUniversitaire'])
            ->where('etudiant_id', $etudiantId)
            ->orderBy('date_paiement', 'desc')
            ->get();

        // Calculer le total des paiements validés
        $totalValide = $paiements->where('status', 'validé')->sum('montant');

        return view('esbtp.paiements.etudiant', compact('etudiant', 'paiements', 'totalValide'));
    }
}
