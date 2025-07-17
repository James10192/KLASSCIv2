<?php

namespace App\Http\Controllers;

use App\Models\ESBTPInscription;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Services\ESBTPInscriptionService;
use App\Services\InscriptionWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTP\Fee;
use App\Services\ComptabiliteService;

class ESBTPInscriptionController extends Controller
{
    protected $inscriptionService;
    protected $comptabiliteService;
    protected $workflowService;

    /**
     * Constructeur avec injection du service d'inscription
     */
    public function __construct(
        ESBTPInscriptionService $inscriptionService, 
        ComptabiliteService $comptabiliteService,
        InscriptionWorkflowService $workflowService
    )
    {
        $this->inscriptionService = $inscriptionService;
        $this->comptabiliteService = $comptabiliteService;
        $this->workflowService = $workflowService;
        $this->middleware('auth');
        $this->middleware('permission:inscriptions.view', ['only' => ['index', 'show']]);
        $this->middleware('permission:inscriptions.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:inscriptions.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:inscriptions.delete', ['only' => ['destroy']]);
        $this->middleware('permission:inscriptions.validate', ['only' => ['valider', 'annuler']]);
    }

    /**
     * Afficher la liste des inscriptions.
     */
    public function index(Request $request)
    {
        // Récupérer les filtres de recherche
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status', 'active');

        // Construire la requête avec les filtres
        $query = ESBTPInscription::query()
            ->with(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire']);

        // Appliquer les filtres
        if ($search) {
            $query->whereHas('etudiant', function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%");
            });
        }

        if ($filiere) {
            $query->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $query->where('niveau_id', $niveau);
        }

        if ($annee) {
            $query->where('annee_universitaire_id', $annee);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Récupérer les inscriptions paginées
        $inscriptions = $query->latest()->paginate(15);

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Calculer les statistiques
        $statsQuery = ESBTPInscription::query();

        if ($filiere) {
            $statsQuery->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $statsQuery->where('niveau_id', $niveau);
        }

        if ($annee) {
            $statsQuery->where('annee_universitaire_id', $annee);
        } elseif ($anneeEnCours) {
            $statsQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }

        $stats = [
            'total' => $statsQuery->count(),
            'actives' => (clone $statsQuery)->where('status', 'active')->count(),
            'en_attente' => (clone $statsQuery)->where('status', 'en_attente')->count(),
            'annulees' => (clone $statsQuery)->where('status', 'annulée')->count(),
            'terminees' => (clone $statsQuery)->where('status', 'terminée')->count(),
        ];

        return view('esbtp.inscriptions.index', compact(
            'inscriptions',
            'filieres',
            'niveaux',
            'annees',
            'search',
            'filiere',
            'niveau',
            'annee',
            'status',
            'stats',
            'anneeEnCours'
        ));
    }

    /**
     * Afficher le formulaire de création d'inscription.
     */
    public function create()
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $academicYears = ESBTPAnneeUniversitaire::where('is_active', true)->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        // Renommer les variables pour les utiliser dans le modal
        $anneeUniversitaires = $academicYears;
        $niveauEtudes = $niveaux;

        // Ajouter $annees pour la compatibilité avec la vue
        $annees = $academicYears;
        
        return view('esbtp.inscriptions.create', compact(
            'filieres', 'niveaux', 'academicYears', 'anneeEnCours',
            'anneeUniversitaires', 'niveauEtudes', 'annees'
        ));
    }

    /**
     * Enregistrer une nouvelle inscription.
     */
    public function store(Request $request)
    {
        // Créer un fichier de log dédié pour le debug
        $debugFile = storage_path('logs/inscription_debug.log');
        $debugData = [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_input' => $request->all(),
            'parents_input' => $request->input('parents', [])
        ];
        
        file_put_contents($debugFile, 
            "=== INSCRIPTION DEBUG " . now() . " ===\n" .
            json_encode($debugData, JSON_PRETTY_PRINT) . "\n\n", 
            FILE_APPEND | LOCK_EX
        );
        // Construction dynamique des règles de validation
        $rules = [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'nom' => 'required|string|max:100',
            'prenoms' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'nullable|email|max:100',
            'ville' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'matricule' => 'required|string|max:20|unique:esbtp_etudiants,matricule',
        ];
        $messages = [
            'classe_id.required' => 'Veuillez sélectionner une classe',
            'nom.required' => 'Le nom est obligatoire',
            'prenoms.required' => 'Le(s) prénom(s) est/sont obligatoire(s)',
            'sexe.required' => 'Le genre est obligatoire',
            'date_naissance.required' => 'La date de naissance est obligatoire',
            'telephone.required' => 'Le numéro de téléphone est obligatoire',
            'matricule.required' => 'Le matricule est obligatoire',
            'matricule.unique' => 'Ce matricule existe déjà',
        ];
        $parents = $request->input('parents', []);
        
        // Debug: Log des données parents reçues
        Log::info('Debug Parents - Données reçues:', [
            'parents' => $parents,
            'request_all' => $request->all()
        ]);
        
        // Nettoyer les données parents - supprimer le template et nettoyer les parents existants
        foreach ($parents as $index => $parent) {
            // Supprimer complètement le template
            if ($index === 'template') {
                unset($parents[$index]);
                file_put_contents($debugFile, "Template supprimé\n", FILE_APPEND | LOCK_EX);
                continue;
            }
            
            if (isset($parent['type']) && $parent['type'] === 'existant') {
                // Pour un parent existant, ne garder que parent_id, relation et type
                $parents[$index] = [
                    'type' => 'existant',
                    'parent_id' => $parent['parent_id'] ?? null,
                    'relation' => $parent['relation'] ?? null
                ];
                file_put_contents($debugFile, "Parent $index nettoyé pour type existant: " . json_encode($parents[$index]) . "\n", FILE_APPEND | LOCK_EX);
            }
        }
        
        // Log des parents après nettoyage
        file_put_contents($debugFile, "Parents après nettoyage: " . json_encode($parents, JSON_PRETTY_PRINT) . "\n", FILE_APPEND | LOCK_EX);
        
        foreach ($parents as $index => $parent) {
            Log::info("Debug Parent $index:", [
                'parent' => $parent,
                'type' => $parent['type'] ?? 'non défini',
                'has_nom' => isset($parent['nom']),
                'has_prenoms' => isset($parent['prenoms']),
                'has_telephone' => isset($parent['telephone']),
                'has_parent_id' => isset($parent['parent_id'])
            ]);
            
            if (isset($parent['type']) && $parent['type'] === 'nouveau') {
                Log::info("Parent $index: Type NOUVEAU détecté - Ajout des règles de validation");
                $rules["parents.$index.nom"] = 'required|string|max:100';
                $rules["parents.$index.prenoms"] = 'required|string|max:100';
                $rules["parents.$index.telephone"] = 'required|string|max:20';
                $rules["parents.$index.relation"] = 'required|string';
                $messages["parents.$index.nom.required"] = 'Le nom du parent/tuteur est obligatoire';
                $messages["parents.$index.prenoms.required"] = 'Le(s) prénom(s) du parent/tuteur est/sont obligatoire(s)';
                $messages["parents.$index.telephone.required"] = 'Le téléphone du parent/tuteur est obligatoire';
                $messages["parents.$index.relation.required"] = 'La relation avec le parent/tuteur est obligatoire';
            } else if (isset($parent['type']) && $parent['type'] === 'existant') {
                Log::info("Parent $index: Type EXISTANT détecté - Ajout des règles pour parent existant");
                $rules["parents.$index.parent_id"] = 'required|exists:esbtp_parents,id';
                $rules["parents.$index.relation"] = 'required|string';
                $messages["parents.$index.parent_id.required"] = 'Veuillez sélectionner un parent existant';
                $messages["parents.$index.parent_id.exists"] = 'Le parent sélectionné n\'existe pas';
                $messages["parents.$index.relation.required"] = 'La relation avec le parent/tuteur est obligatoire';
                // NE PAS ajouter de règle sur nom/prenoms/telephone pour un parent existant
            } else {
                Log::warning("Parent $index: Type non reconnu ou manquant", [
                    'parent' => $parent
                ]);
            }
        }
        
        // Debug: Log des règles finales
        Log::info('Debug Validation - Règles appliquées:', [
            'rules' => $rules,
            'messages' => $messages
        ]);
        
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            Log::error('Validation échouée:', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }



        try {
            // Log des données soumises pour débogage
            Log::info('Données reçues:', $request->all());

            DB::beginTransaction();

            // Récupérer les informations complètes de la classe sélectionnée
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'annee'])
                ->findOrFail($request->classe_id);

            // Préparer les données de l'étudiant
            $etudiantData = [
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'sexe' => $request->sexe,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'email_personnel' => $request->email_personnel,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'commune' => $request->commune,
                'statut' => 'actif',
                'creer_compte_utilisateur' => true,
                'matricule' => $request->matricule,
            ];

            // Ajouter un log pour déboguer
            \Log::info('Données de l\'étudiant', [
                'etudiantData' => $etudiantData,
                'matriculeFromRequest' => $request->matricule
            ]);

            // Ajouter un log supplémentaire pour les champs ville et commune
            \Log::info('Champs de résidence', [
                'ville' => $request->ville,
                'commune' => $request->commune,
                'lieu_naissance' => $request->lieu_naissance,
                'adresse' => $request->adresse
            ]);

            // Traiter la photo si fournie
            if ($request->hasFile('photo')) {
                $etudiantData['photo'] = $this->handlePhotoUpload($request->file('photo'));
            }

            // Préparer les données d'inscription
            $inscriptionData = [
                'date_inscription' => $request->date_inscription ?? now()->format('Y-m-d'),
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $classe->annee_universitaire_id,
                'status' => 'en_attente',
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'type_inscription' => 'première_inscription',
                'montant_scolarite' => $request->montant_scolarite ?? 0,
                'frais_inscription' => $request->frais_inscription ?? 0,
            ];

            // Si la classe a des relations filière et niveau, les ajouter aux données de l'étudiant
            if ($classe->filiere_id) {
                $etudiantData['filiere_id'] = $classe->filiere_id;
            }

            if ($classe->niveau_etude_id) {
                $etudiantData['niveau_etude_id'] = $classe->niveau_etude_id;
            }

            // Les paiements seront gérés lors de la validation de l'inscription

            // Préparer les données des parents
            $parentsData = [];

            // Traiter les parents du formulaire
            if ($request->has('parents')) {
                foreach ($request->parents as $parent) {
                    if (isset($parent['type']) && $parent['type'] === 'existant' && !empty($parent['parent_id'])) {
                        // Parent existant sélectionné
                        $parentsData[] = [
                            'parent_id' => $parent['parent_id'],
                            'relation' => $parent['relation'] ?? 'Autre'
                        ];
                    }
                    elseif (isset($parent['type']) && $parent['type'] === 'nouveau' && !empty($parent['nom']) && !empty($parent['prenoms'])) {
                        // Nouveau parent
                        $parentsData[] = [
                            'nom' => $parent['nom'],
                            'prenoms' => $parent['prenoms'],
                            'email' => $parent['email'] ?? null,
                            'telephone' => $parent['telephone'] ?? null,
                            'profession' => $parent['profession'] ?? null,
                            'relation' => $parent['relation'] ?? 'Autre',
                            'adresse' => $parent['adresse'] ?? null
                        ];
                    }
                }
            }

            $selectedOptionals = $request->input('fee_optionals', []);

            // Traitement des variants de frais sélectionnés
            $fraisVariants = $request->input('frais', []);
            $fraisSubscriptions = [];
            
            // Traiter chaque catégorie de frais avec ses variants
            foreach ($fraisVariants as $categoryId => $fraisData) {
                if (!empty($fraisData['variant_id'])) {
                    $category = ESBTPFraisCategory::find($categoryId);
                    if ($category) {
                        $variantId = $fraisData['variant_id'];
                        $amount = 0;
                        
                        if ($variantId === 'default') {
                            // Option standard - utiliser la règle configurée ou le montant par défaut
                            $rule = \App\Models\ESBTPFraisRule::where('frais_category_id', $categoryId)
                                ->where('filiere_id', $classe->filiere_id)
                                ->where('niveau_id', $classe->niveau_etude_id)
                                ->first();
                            $amount = $rule ? $rule->amount : $category->default_amount;
                        } else {
                            // Variant spécifique
                            $variant = \App\Models\ESBTPFraisVariant::find($variantId);
                            if ($variant && $variant->frais_category_id == $categoryId) {
                                $amount = $variant->amount;
                            }
                        }
                        
                        // Préparer les données de souscription
                        $fraisSubscriptions[] = [
                            'frais_category_id' => $categoryId,
                            'variant_id' => $variantId === 'default' ? null : $variantId,
                            'amount' => $amount,
                            'status' => 'active',
                            'subscribed_at' => now(),
                        ];
                    }
                }
            }

            // Ajouter un log plus détaillé en cas d'erreur
            \Log::info('Données de l\'inscription avec variants', [
                'etudiantData' => $etudiantData,
                'inscriptionData' => $inscriptionData,
                'parentsData' => $parentsData,
                'selectedOptionals' => $selectedOptionals,
                'fraisVariants' => $fraisVariants,
                'fraisSubscriptions' => $fraisSubscriptions
            ]);

            // Créer l'inscription (sans paiement - sera géré lors de la validation)
            $inscription = $this->inscriptionService->createInscription(
                $etudiantData,
                $inscriptionData,
                $parentsData,
                null, // Pas de paiement pour l'instant
                auth()->id(),
                [] // Pas de frais optionnels pour l'instant
            );

            // Créer les souscriptions aux frais avec variants
            if ($inscription && !empty($fraisSubscriptions)) {
                foreach ($fraisSubscriptions as $subscriptionData) {
                    $subscriptionData['inscription_id'] = $inscription->id;
                    ESBTPFraisSubscription::create($subscriptionData);
                }
            }

            DB::commit();

            // Stocker les informations du compte dans la session
            if ($inscription && $inscription->etudiant && $inscription->etudiant->user) {
                $user = $inscription->etudiant->user;
                session()->flash('account_info', [
                    'username' => $user->username,
                    'password' => session('generated_password'),
                    'role' => 'Étudiant'
                ]);
            }

            return redirect()->route('esbtp.inscriptions.show', $inscription->id)
                ->with('success', 'Inscription enregistrée avec succès. L\'administration pourra valider l\'inscription en associant un paiement.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de l\'inscription: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'inscription. Détails : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Afficher les détails d'une inscription.
     */
    public function show(ESBTPInscription $inscription)
    {
        // Charger toutes les relations nécessaires, y compris payments
        $inscription->load([
            'etudiant.parents',
            'filiere',
            'niveau',
            'classe',
            'anneeUniversitaire',
            'paiements',
            'payments',
        ]);

        // Frais/échéances liés à l'inscription
        $fees = \App\Models\ESBTP\Fee::where('inscription_id', $inscription->id)->orderBy('due_date')->get();
        // Paiements validés liés à l'inscription
        $soldeRestant = $fees->sum(function($fee) {
            return $fee->amount - $fee->totalPaidAmount();
        });

        // Récupérer les catégories de frais avec règles pour cette inscription
        $mandatoryCategories = \App\Models\ESBTPFraisCategory::where('is_mandatory', true)->active()->ordered()->get();
        $optionalCategories = \App\Models\ESBTPFraisCategory::where('is_mandatory', false)->active()->ordered()->get();
        
        // Récupérer les souscriptions actives pour cette inscription
        $subscriptions = \App\Models\ESBTPFraisSubscription::getActiveSubscriptions($inscription->id);
        $subscribedCategoryIds = $subscriptions->pluck('frais_category_id')->toArray();
        
        $feeCategoriesWithRules = [];
        
        // Traiter les frais obligatoires (tous affichés)
        foreach ($mandatoryCategories as $category) {
            $rule = $category->getApplicableRule($inscription->filiere_id, $inscription->niveau_id, $inscription->annee_universitaire_id);
            
            // Calculer les paiements pour cette catégorie
            $paiements = $inscription->paiements()
                ->where('frais_category_id', $category->id)
                ->where('status', 'validated')
                ->get();
            
            $totalPaye = $paiements->sum('montant');
            $montantAttendu = $rule ? $rule->amount : $category->default_amount;
            $solde = $montantAttendu - $totalPaye;
            
            $feeCategoriesWithRules[] = [
                'category' => $category,
                'rule' => $rule,
                'montant_attendu' => $montantAttendu,
                'total_paye' => $totalPaye,
                'solde' => $solde,
                'paiements' => $paiements,
                'is_configured' => $rule !== null,
                'is_mandatory' => true,
                'is_subscribed' => true, // Les frais obligatoires sont automatiquement "souscrits"
                'subscription' => null,
                'status' => $solde <= 0 ? 'paid' : ($totalPaye > 0 ? 'partial' : 'unpaid')
            ];
        }
        
        // Traiter les frais optionnels (seulement ceux souscrits)
        foreach ($optionalCategories as $category) {
            $subscription = $subscriptions->where('frais_category_id', $category->id)->first();
            
            if ($subscription) {
                $rule = $category->getApplicableRule($inscription->filiere_id, $inscription->niveau_id, $inscription->annee_universitaire_id);
                
                // Calculer les paiements pour cette catégorie
                $paiements = $inscription->paiements()
                    ->where('frais_category_id', $category->id)
                    ->where('status', 'validated')
                    ->get();
                
                $totalPaye = $paiements->sum('montant');
                $montantAttendu = $subscription->amount; // Utiliser le montant de la souscription
                $solde = $montantAttendu - $totalPaye;
                
                $feeCategoriesWithRules[] = [
                    'category' => $category,
                    'rule' => $rule,
                    'montant_attendu' => $montantAttendu,
                    'total_paye' => $totalPaye,
                    'solde' => $solde,
                    'paiements' => $paiements,
                    'is_configured' => $rule !== null,
                    'is_mandatory' => false,
                    'is_subscribed' => true,
                    'subscription' => $subscription,
                    'status' => $solde <= 0 ? 'paid' : ($totalPaye > 0 ? 'partial' : 'unpaid')
                ];
            }
        }
        
        // Récupérer les frais optionnels non souscrits (pour permettre la souscription)
        $availableOptionalCategories = $optionalCategories->filter(function($category) use ($subscribedCategoryIds) {
            return !in_array($category->id, $subscribedCategoryIds);
        });

        // Récupérer les catégories de frais pour la modal de paiement
        $categoriesfrais = collect($feeCategoriesWithRules)->pluck('category');
        
        // Filtrer les catégories obligatoires pour le debug
        $mandatoryFeeCategoriesWithRules = collect($feeCategoriesWithRules)->filter(function($item) {
            return $item['is_mandatory'];
        });
        
        return view('esbtp.inscriptions.show', compact(
            'inscription', 
            'fees', 
            'soldeRestant', 
            'feeCategoriesWithRules', 
            'categoriesfrais', 
            'mandatoryFeeCategoriesWithRules',
            'availableOptionalCategories'
        ));
    }

    /**
     * Afficher le formulaire de modification d'une inscription.
     */
    public function edit(ESBTPInscription $inscription)
    {
        // Vérifier si l'inscription peut être modifiée
        if ($inscription->status === 'terminée') {
            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('error', 'Les inscriptions terminées ne peuvent pas être modifiées.');
        }

        // Charger les relations nécessaires
        $inscription->load(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire']);

        // Récupérer les données pour les selects
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)
            ->where('filiere_id', $inscription->filiere_id)
            ->where('niveau_etude_id', $inscription->niveau_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.inscriptions.edit', compact(
            'inscription',
            'filieres',
            'niveaux',
            'classes',
            'annees'
        ));
    }

    /**
     * Mettre à jour une inscription.
     */
    public function update(Request $request, ESBTPInscription $inscription)
    {
        // Vérifier si l'inscription peut être modifiée
        if ($inscription->status === 'terminée') {
            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('error', 'Les inscriptions terminées ne peuvent pas être modifiées.');
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'date_inscription' => 'required|date',
            'type_inscription' => 'required|in:première_inscription,réinscription,transfert',
            'montant_scolarite' => 'required|numeric|min:0',
            'frais_inscription' => 'required|numeric|min:0',
            'observations' => 'nullable|string',
            'status' => 'required|in:en_attente,active,annulée,terminée',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            if ($inscription->status !== 'en_attente') {
                // Empêcher la modification de la classe
                unset($data['classe_id']);
                \Log::warning('Tentative de modification de la classe après validation', [
                    'inscription_id' => $inscription->id,
                    'user_id' => Auth::id()
                ]);
            }

            // Mettre à jour l'inscription
            $inscription->filiere_id = $data['filiere_id'];
            $inscription->niveau_id = $data['niveau_id'];
            $inscription->classe_id = $data['classe_id'];
            $inscription->date_inscription = $data['date_inscription'];
            $inscription->type_inscription = $data['type_inscription'];
            $inscription->montant_scolarite = $data['montant_scolarite'];
            $inscription->frais_inscription = $data['frais_inscription'];
            $inscription->observations = $data['observations'];

            // Mettre à jour le statut et les champs associés
            $nouveauStatut = $data['status'];
            $ancienStatut = $inscription->status;

            if ($nouveauStatut !== $ancienStatut) {
                $inscription->status = $nouveauStatut;

                if ($nouveauStatut === 'active' && $ancienStatut !== 'active') {
                    $inscription->date_validation = now();
                    $inscription->validated_by = Auth::id();
                }

                // Si l'inscription devient inactive ou annulée, mettre à jour l'étudiant si nécessaire
                if (in_array($nouveauStatut, ['annulée', 'terminée'])) {
                    $etudiant = $inscription->etudiant;
                    $autresInscriptionsActives = $etudiant->inscriptions()
            ->where('id', '!=', $inscription->id)
                        ->whereIn('status', ['active', 'en_attente'])
                        ->exists();

                    if (!$autresInscriptionsActives && $etudiant->statut === 'actif') {
                        if ($nouveauStatut === 'terminée') {
                            $etudiant->statut = 'diplômé';
                        } else {
                            $etudiant->statut = 'inactif';
                        }
                        $etudiant->save();
                    }
                }
            }

            $inscription->updated_by = Auth::id();
            $inscription->save();

            DB::commit();

            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('success', 'Inscription mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Valider une inscription.
     */
    public function valider(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'montant_paye' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $this->inscriptionService->validerInscription($inscription, $request->input('observations'));

            $montantPaye = $request->input('montant_paye', 0);
            if ($montantPaye > 0) {
                $this->comptabiliteService->validerPaiementInscription($inscription, $montantPaye);
                    }

            DB::commit();

            return redirect()->route('esbtp.inscriptions.show', $inscription->id)
                    ->with('success', 'Inscription validée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la validation: ' . $e->getMessage());
        }
    }

    /**
     * Annuler une inscription.
     */
    public function annuler(Request $request, ESBTPInscription $inscription)
    {
        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $motif = $request->input('motif');
            $result = $this->inscriptionService->annulerInscription($inscription->id, $motif, Auth::id());

            if ($result['success']) {
                return redirect()
                    ->route('esbtp.inscriptions.show', $inscription->id)
                    ->with('success', 'Inscription annulée avec succès.');
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les classes disponibles pour une filière, un niveau et une année donnés.
     */
    public function getClasses(Request $request)
    {
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id') ?? $request->input('niveau_etude_id');
        $anneeId = $request->input('annee_id') ?? $request->input('annee_universitaire_id');
        $formationId = $request->input('formation_id');

        // Ajouter des logs pour debug
        \Illuminate\Support\Facades\Log::info('Récupération des classes (Inscription)', [
            'filiere_id' => $filiereId,
            'niveau_id' => $niveauId,
            'annee_id' => $anneeId,
            'formation_id' => $formationId,
            'request' => $request->all()
        ]);

        $query = ESBTPClasse::select(
                'esbtp_classes.*',
                'f.name as filiere_name',
                'n.name as niveau_name',
                'a.name as annee_name'
            )
            ->leftJoin('esbtp_filieres as f', 'esbtp_classes.filiere_id', '=', 'f.id')
            ->leftJoin('esbtp_niveau_etudes as n', 'esbtp_classes.niveau_etude_id', '=', 'n.id')
            ->leftJoin('esbtp_annee_universitaires as a', 'esbtp_classes.annee_universitaire_id', '=', 'a.id')
            ->where('esbtp_classes.is_active', true);

        // Appliquer les filtres seulement s'ils sont fournis
        if ($filiereId) {
            $query->where('esbtp_classes.filiere_id', $filiereId);
        }

        if ($niveauId) {
            $query->where('esbtp_classes.niveau_etude_id', $niveauId);
        }

        if ($anneeId) {
            $query->where('esbtp_classes.annee_universitaire_id', $anneeId);
        }

        if ($formationId) {
            $query->where('esbtp_classes.formation_id', $formationId);
        }

        // Log pour vérifier la requête SQL générée
        \Illuminate\Support\Facades\Log::info('Requête SQL pour les classes (Inscription)', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $classes = $query->get();

        // Log pour vérifier les résultats
        \Illuminate\Support\Facades\Log::info('Classes trouvées (Inscription)', [
            'count' => $classes->count(),
            'first_few' => $classes->take(3)
        ]);

        return response()->json($classes);
    }

    /**
     * Interface d'administration pour la validation des inscriptions.
     */
    public function administration(Request $request)
    {
        // Récupérer les filtres
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $workflow_step = $request->input('workflow_step', 'prospect');
        $has_payment = $request->input('has_payment');

        // Construire la requête pour les inscriptions en attente de validation
        $query = ESBTPInscription::query()
            ->with([
                'etudiant', 
                'filiere', 
                'niveau', 
                'classe', 
                'anneeUniversitaire',
                'paiements' => function($q) {
                    $q->where('status', 'validated');
                }
            ])
            ->where('status', 'en_attente');

        // Appliquer les filtres
        if ($search) {
            $query->whereHas('etudiant', function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%");
            });
        }

        if ($filiere) {
            $query->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $query->where('niveau_id', $niveau);
        }

        if ($annee) {
            $query->where('annee_universitaire_id', $annee);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }
        }

        if ($workflow_step) {
            $query->where('workflow_step', $workflow_step);
        }

        // Filtrer par statut de paiement
        if ($has_payment === 'yes') {
            $query->whereHas('paiements', function($q) {
                $q->where('status', 'validated');
            });
        } elseif ($has_payment === 'no') {
            $query->whereDoesntHave('paiements', function($q) {
                $q->where('status', 'validated');
            });
        }

        // Récupérer les inscriptions
        $inscriptions = $query->latest()->paginate(20);

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Calculer les statistiques
        $stats = [
            'total_en_attente' => ESBTPInscription::where('status', 'en_attente')->count(),
            'avec_paiement' => ESBTPInscription::where('status', 'en_attente')
                ->whereHas('paiements', function($q) {
                    $q->where('status', 'validated');
                })->count(),
            'sans_paiement' => ESBTPInscription::where('status', 'en_attente')
                ->whereDoesntHave('paiements', function($q) {
                    $q->where('status', 'validated');
                })->count(),
            'prospects' => ESBTPInscription::where('status', 'en_attente')
                ->where('workflow_step', 'prospect')->count(),
            'documents_complets' => ESBTPInscription::where('status', 'en_attente')
                ->where('workflow_step', 'documents_complets')->count(),
            'en_validation' => ESBTPInscription::where('status', 'en_attente')
                ->where('workflow_step', 'en_validation')->count(),
        ];

        // Récupérer les catégories de frais pour la modal de paiement
        $categoriesfrais = \App\Models\ESBTPFraisCategory::active()->ordered()->get();

        return view('esbtp.inscriptions.administration', compact(
            'inscriptions',
            'filieres',
            'niveaux',
            'annees',
            'search',
            'filiere',
            'niveau',
            'annee',
            'workflow_step',
            'has_payment',
            'stats',
            'anneeEnCours',
            'categoriesfrais'
        ));
    }

    /**
     * Valider une inscription avec paiement associé.
     */
    public function validerAvecPaiement(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'montant' => 'required|numeric|min:0',
            'fee_category_id' => 'required|exists:esbtp_fee_categories,id',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
            'date_paiement' => 'required|date',
            'observations' => 'nullable|string|max:500',
        ]);

        try {
            $paiementData = [
                'montant' => $request->montant,
                'fee_category_id' => $request->fee_category_id,
                'mode_paiement' => $request->mode_paiement,
                'reference_paiement' => $request->reference_paiement,
                'date_paiement' => $request->date_paiement,
                'observations' => $request->observations,
            ];

            $result = $this->workflowService->associerPaiement($inscription, $paiementData);

            if ($result['success']) {
                return redirect()->route('esbtp.inscriptions.administration')
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'association du paiement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de l\'association du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Valider définitivement une inscription (conversion prospect -> étudiant).
     */
    public function validerDefinitivement(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'observations' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->workflowService->convertProspectToStudent($inscription, $request->input('observations'));

            if ($result['success']) {
                return redirect()->route('esbtp.inscriptions.administration')
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation finale: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la validation: ' . $e->getMessage());
        }
    }

    /**
     * Générer un numéro de reçu unique.
     */
    private function genererNumeroRecu()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "REC-{$year}{$month}-";
        
        $lastPayment = ESBTPPaiement::where('numero_recu', 'like', $prefix . '%')
            ->orderBy('numero_recu', 'desc')
            ->first();
        
        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->numero_recu, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Supprimer une inscription.
     */
    public function destroy(ESBTPInscription $inscription)
    {
        try {
            $inscription->delete();

            return redirect()
                ->route('esbtp.inscriptions.index')
                ->with('success', 'Inscription supprimée avec succès.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Gère l'upload de la photo de l'étudiant.
     *
     * @param \Illuminate\Http\UploadedFile $photo
     * @return string
     */
    private function handlePhotoUpload($photo)
    {
        $filename = time() . '_' . Str::random(10) . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs('public/photos/etudiants', $filename);
        return $filename;
    }

    /**
     * API pour rechercher les parents existants
     */
    public function searchParents(Request $request)
    {
        try {
            $search = $request->input('search', '');
            
            $query = ESBTPParent::query();
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenoms', 'like', "%{$search}%")
                      ->orWhere('telephone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $parents = $query->select('id', 'nom', 'prenoms', 'telephone', 'email', 'profession')
                           ->orderBy('nom')
                           ->limit(50)
                           ->get();
            
            return response()->json([
                'success' => true,
                'parents' => $parents
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche de parents: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'parents' => []
            ], 500);
        }
    }

    /**
     * Souscrire à un frais optionnel
     */
    public function subscribeToOptionalFee(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Vérifier que c'est bien un frais optionnel
            $category = ESBTPFraisCategory::findOrFail($request->frais_category_id);
            if ($category->is_mandatory) {
                return redirect()->back()->with('error', 'Impossible de souscrire à un frais obligatoire.');
            }

            // Créer la souscription
            ESBTPFraisSubscription::subscribe(
                $inscription->id,
                $request->frais_category_id,
                $request->amount,
                Auth::id(),
                $request->notes
            );

            return redirect()->route('esbtp.inscriptions.show', $inscription->id)
                ->with('success', 'Souscription au frais optionnel réussie !');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la souscription au frais optionnel: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la souscription au frais optionnel.');
        }
    }

    /**
     * Se désabonner d'un frais optionnel
     */
    public function unsubscribeFromOptionalFee(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
        ]);

        try {
            ESBTPFraisSubscription::unsubscribe($inscription->id, $request->frais_category_id);

            return redirect()->route('esbtp.inscriptions.show', $inscription->id)
                ->with('success', 'Désabonnement du frais optionnel réussi !');

        } catch (\Exception $e) {
            Log::error('Erreur lors du désabonnement du frais optionnel: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors du désabonnement du frais optionnel.');
        }
    }

    /**
     * Récupérer les frais applicables pour une classe donnée
     */
    public function getFraisByClasse($classeId)
    {
        try {
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->findOrFail($classeId);
            
            // Récupérer les catégories de frais
            $mandatoryCategories = ESBTPFraisCategory::where('is_mandatory', true)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();
            
            $optionalCategories = ESBTPFraisCategory::where('is_mandatory', false)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();
            
            $fraisData = [];
            
            // Traiter les frais obligatoires
            foreach ($mandatoryCategories as $category) {
                $rule = $category->getApplicableRule($classe->filiere_id, $classe->niveau_etude_id, $classe->annee_universitaire_id);
                $defaultAmount = $rule ? $rule->amount : $category->default_amount;
                
                // Récupérer les variants pour cette catégorie
                $variants = \App\Models\ESBTPFraisVariant::where('frais_category_id', $category->id)
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->get();
                
                $fraisData[] = [
                    'category' => $category,
                    'default_amount' => $defaultAmount,
                    'variants' => $variants,
                    'is_mandatory' => true,
                    'rule' => $rule
                ];
            }
            
            // Traiter les frais optionnels
            foreach ($optionalCategories as $category) {
                $rule = $category->getApplicableRule($classe->filiere_id, $classe->niveau_etude_id, $classe->annee_universitaire_id);
                $defaultAmount = $rule ? $rule->amount : $category->default_amount;
                
                // Récupérer les variants pour cette catégorie
                $variants = \App\Models\ESBTPFraisVariant::where('frais_category_id', $category->id)
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->get();
                
                $fraisData[] = [
                    'category' => $category,
                    'default_amount' => $defaultAmount,
                    'variants' => $variants,
                    'is_mandatory' => false,
                    'rule' => $rule
                ];
            }
            
            return response()->json([
                'success' => true,
                'classe' => $classe,
                'frais' => $fraisData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des frais pour la classe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des frais'
            ], 500);
        }
    }
}
