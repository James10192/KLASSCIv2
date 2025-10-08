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
use Illuminate\Database\QueryException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTP\Fee;
use App\Models\Setting;
use App\Services\ComptabiliteService;
use App\Services\StudentDuplicateDetector;
use App\Services\FuzzyNameMatcher;
use Illuminate\Pagination\LengthAwarePaginator;

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
    public function index(Request $request, FuzzyNameMatcher $matcher)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        $baseLogContext = [
            'timestamp' => $startTimestamp,
            'url' => $request->fullUrl(),
            'query' => $request->query(),
            'user_id' => optional($request->user())->id,
        ];
        \Log::info('ESBTPInscriptionController@index start', $baseLogContext);

        // Récupérer les filtres de recherche
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status', 'active');

        // Construire la requête avec les filtres
        $baseQuery = ESBTPInscription::query()
            ->with(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire']);

        if ($filiere) {
            $baseQuery->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $baseQuery->where('niveau_id', $niveau);
        }

        if ($annee) {
            $baseQuery->where('annee_universitaire_id', $annee);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if ($anneeEnCours) {
                $baseQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
        }

        if ($status && $status !== 'all') {
            $baseQuery->where('status', $status);
        }

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        \Log::info('ESBTPInscriptionController@index processing', array_merge($baseLogContext, [
            'has_search' => (bool) $search,
            'filters' => [
                'filiere' => $filiere,
                'niveau' => $niveau,
                'annee' => $annee,
                'status' => $status,
            ],
            'page' => $currentPage,
            'per_page' => $perPage,
        ]));

        $escapeLike = static fn (string $value): string => str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );

        if ($search) {
            $candidatesQuery = clone $baseQuery;

            $searchTokens = collect(preg_split('/[\s,]+/u', $search ?: '', -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($token) => trim($token))
                ->filter();

            $candidatesQuery->where(function ($q) use ($search, $searchTokens, $escapeLike) {
                $escapedSearch = $escapeLike($search);
                $likeSearch = "%{$escapedSearch}%";

                $q->whereHas('etudiant', function ($etudiantQuery) use ($likeSearch, $searchTokens, $escapeLike) {
                    $etudiantQuery->where('matricule', 'like', $likeSearch)
                        ->orWhere('nom', 'like', $likeSearch)
                        ->orWhere('prenoms', 'like', $likeSearch)
                        ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [$likeSearch]);

                    if ($searchTokens->isNotEmpty()) {
                        $etudiantQuery->orWhere(function ($subQuery) use ($searchTokens, $escapeLike) {
                            foreach ($searchTokens as $token) {
                                $escapedToken = $escapeLike($token);
                                $likeToken = "%{$escapedToken}%";
                                $subQuery->orWhere('nom', 'like', $likeToken)
                                         ->orWhere('prenoms', 'like', $likeToken)
                                         ->orWhere('matricule', 'like', $likeToken)
                                         ->orWhere('telephone', 'like', $likeToken)
                                         ->orWhere('email_personnel', 'like', $likeToken);
                            }
                        });
                    }
                })
                ->orWhere('numero_recu', 'like', $likeSearch)
                ->orWhereHas('classe', function ($classeQuery) use ($likeSearch, $searchTokens, $escapeLike) {
                    $classeQuery->where('name', 'like', $likeSearch);

                    if ($searchTokens->isNotEmpty()) {
                        $classeQuery->orWhere(function ($subQuery) use ($searchTokens, $escapeLike) {
                            foreach ($searchTokens as $token) {
                                $escapedToken = $escapeLike($token);
                                $likeToken = "%{$escapedToken}%";
                                $subQuery->orWhere('name', 'like', $likeToken);
                            }
                        });
                    }
                });
            });

            try {
                $candidates = $candidatesQuery
                    ->limit(200)
                    ->get();
            } catch (QueryException $exception) {
                \Log::warning('ESBTPInscriptionController@index fallback search triggered', array_merge($baseLogContext, [
                    'message' => $exception->getMessage(),
                ]));

                $fallbackQuery = clone $baseQuery;
                $fallbackQuery->where(function ($q) use ($search, $escapeLike) {
                    $escapedSearch = $escapeLike($search);
                    $likeSearch = "%{$escapedSearch}%";

                    $q->whereHas('etudiant', function ($etudiantQuery) use ($likeSearch) {
                        $etudiantQuery->where('matricule', 'like', $likeSearch)
                            ->orWhere('nom', 'like', $likeSearch)
                            ->orWhere('prenoms', 'like', $likeSearch);
                    })
                    ->orWhere('numero_recu', 'like', $likeSearch);
                });

                $candidates = $fallbackQuery
                    ->limit(200)
                    ->get();
            }

            $scored = $matcher->match($search, $candidates, function ($inscription) {
                $etudiant = $inscription->etudiant;

                return [
                    'matricule' => $etudiant?->matricule,
                    'nom' => $etudiant?->nom,
                    'prenoms' => $etudiant?->prenoms,
                    'full_name' => $etudiant ? trim($etudiant->prenoms . ' ' . $etudiant->nom) : null,
                    'classe' => $inscription->classe?->name,
                    'numero_inscription' => $inscription->numero_inscription,
                    'numero_recu' => $inscription->numero_recu,
                ];
            }, [
                'threshold' => 35,
                'limit' => 150,
                'boosts' => [
                    'matricule' => 18,
                    'numero_inscription' => 12,
                    'numero_recu' => 10,
                    'full_name' => 6,
                ],
            ]);

            $total = $scored->count();
            $items = $scored->forPage($currentPage, $perPage)->values();

            $inscriptions = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            $inscriptions->appends($request->query());
        } else {
            $inscriptions = $baseQuery->latest()->paginate($perPage)->appends($request->query());
        }

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

        \Log::info('ESBTPInscriptionController@index completed', array_merge($baseLogContext, [
            'timestamp' => now()->toIso8601String(),
            'total' => $inscriptions->total(),
            'page' => $inscriptions->currentPage(),
            'per_page' => $inscriptions->perPage(),
            'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
        ]));

        if ($request->ajax()) {
            \Log::info('ESBTPInscriptionController@index returning AJAX response', array_merge($baseLogContext, [
                'timestamp' => now()->toIso8601String(),
                'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
            ]));
            return response()->json([
                'html' => view('esbtp.inscriptions.partials.results', [
                    'inscriptions' => $inscriptions,
                ])->render(),
                'url' => $request->fullUrl(),
            ]);
        }

        \Log::info('ESBTPInscriptionController@index returning view', array_merge($baseLogContext, [
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
        ]));

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
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

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
     * Vérifie la présence potentielle de doublons étudiants (route historique).
     */
    public function checkDuplicates(Request $request, StudentDuplicateDetector $detector)
    {
        return $this->duplicates($request, $detector);
    }

    /**
     * Nouvelle route de recherche de doublons étudiants.
     */
    public function duplicates(Request $request, StudentDuplicateDetector $detector)
    {
        $validated = $this->validateDuplicateRequest($request);

        $duplicates = $detector->find(
            $validated['nom'],
            $validated['prenoms'],
            $validated['date_naissance'] ?? null,
            $validated['sexe'] ?? null,
            6
        )->filter(function (array $item) {
            return ($item['score'] ?? 0) >= 80;
        })->map(function (array $item) {
            $item['show_url'] = route('esbtp.etudiants.show', $item['id']);
            return $item;
        })->values();

        return response()->json([
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * Valide les paramètres de recherche de doublons.
     */
    private function validateDuplicateRequest(Request $request): array
    {
        return $request->validate([
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'date_naissance' => 'nullable|date',
            'sexe' => 'nullable|in:M,F',
        ]);
    }

    /**
     * Détermine si une exception SQL correspond à un conflit d'unicité sur le matricule.
     */
    private function isMatriculeUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        $driverCode = $exception->errorInfo[1] ?? null;

        if ($sqlState === '23000' && (int) $driverCode === 1062) {
            return Str::contains(strtolower($exception->getMessage()), 'matricule');
        }

        return false;
    }

    /**
     * Enregistrer une nouvelle inscription.
     */
    public function store(Request $request, StudentDuplicateDetector $duplicateDetector)
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

        // Vérification du paywall avant de permettre une nouvelle inscription
        if ($this->checkPaywallLimitsForInscription()) {
            return redirect()->back()
                ->with('error', 'Limite d\'inscriptions atteinte pour l\'année courante. Contactez African Digit Consulting pour augmenter votre quota.')
                ->with('paywall_contact', 'klassci@africandigitconsulting.com')
                ->withInput();
        }

        // Détection de doublons (blocage tant que non confirmé)
        if (!$request->boolean('duplicate_override')) {
            $duplicates = $duplicateDetector->find(
                $request->input('nom', ''),
                $request->input('prenoms', ''),
                $request->input('date_naissance'),
                $request->input('sexe')
            );

            if ($duplicates->isNotEmpty()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['duplicate' => 'Un étudiant avec des informations similaires existe déjà. Veuillez confirmer avant de créer une nouvelle inscription.'])
                    ->with('duplicate_suggestions', $duplicates->toArray());
            }
        }

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
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

            // Récupérer le statut d'affectation depuis le request
            $affectationStatus = $request->input('affectation_status', 'affecté');

            // CORRECTION: Utiliser l'année courante au lieu de l'année de la classe
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if (!$anneeCourante) {
                throw new \Exception("Aucune année universitaire courante définie. Veuillez configurer l'année courante.");
            }

            // Préparer les données d'inscription
            $inscriptionData = [
                'date_inscription' => $request->date_inscription ?? now()->format('Y-m-d'),
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $anneeCourante->id, // Utiliser l'année courante
                'status' => 'en_attente',
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'type_inscription' => 'première_inscription',
                'montant_scolarite' => $request->montant_scolarite ?? 0,
                'frais_inscription' => $request->frais_inscription ?? 0,
                'affectation_status' => $affectationStatus, // Sauvegarder le statut d'affectation
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

            // Traitement des frais sélectionnés selon la nouvelle architecture
            $fraisVariants = $request->input('frais', []);
            $selectedOptionals = []; // Format pour la nouvelle méthode ESBTPInscriptionService
            
            // Convertir le format des frais pour la nouvelle architecture
            foreach ($fraisVariants as $categoryId => $fraisData) {
                if (!empty($fraisData['variant_id'])) {
                    $selectedOptionals[$categoryId] = $fraisData;
                }
            }

            // Ajouter un log plus détaillé en cas d'erreur
            \Log::info('Données de l\'inscription avec variants', [
                'etudiantData' => $etudiantData,
                'inscriptionData' => $inscriptionData,
                'parentsData' => $parentsData,
                'selectedOptionals' => $selectedOptionals,
                'fraisVariants' => $fraisVariants
            ]);

            $autoGenerateMatricule = empty(trim((string) $request->matricule));
            if ($autoGenerateMatricule) {
                $etudiantData['matricule'] = null;
            }

            $inscription = null;
            $maxAttempts = 3;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                DB::beginTransaction();
                try {
                    if ($autoGenerateMatricule) {
                        $etudiantData['matricule'] = null;
                    }

                    $inscription = $this->inscriptionService->createInscription(
                        $etudiantData,
                        $inscriptionData,
                        $parentsData,
                        null,
                        auth()->id(),
                        $selectedOptionals,
                        $affectationStatus
                    );

                    DB::commit();
                    break;
                } catch (QueryException $exception) {
                    DB::rollBack();

                    if ($autoGenerateMatricule && $this->isMatriculeUniqueViolation($exception) && $attempt < $maxAttempts) {
                        Log::warning('Conflit de matricule détecté, nouvelle tentative de génération.', [
                            'attempt' => $attempt,
                            'etudiant_nom' => $etudiantData['nom'],
                            'etudiant_prenoms' => $etudiantData['prenoms'],
                        ]);
                        continue;
                    }

                    throw $exception;
                } catch (\Exception $exception) {
                    DB::rollBack();
                    throw $exception;
                }
            }

            if (!$inscription) {
                throw new \RuntimeException('Impossible de générer un matricule unique pour cet étudiant.');
            }

            // Envoyer les notifications aux admins, coordonnateurs et secrétaires
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyInscriptionCreated($inscription, auth()->user());
            } catch (\Exception $e) {
                Log::error('Erreur envoi notification inscription: ' . $e->getMessage());
            }

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
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
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
        
        // Récupérer le statut d'affectation de l'inscription
        $affectationStatus = $inscription->affectation_status ?? 'affecté';

        // Log pour debugging du statut d'affectation
        \Log::info('Affichage inscription - Statut d\'affectation', [
            'inscription_id' => $inscription->id,
            'affectation_status' => $affectationStatus,
            'matricule' => $inscription->etudiant->matricule ?? 'N/A'
        ]);

        // Traiter les frais obligatoires (utiliser les souscriptions individuelles)
        foreach ($mandatoryCategories as $category) {
            $rule = $category->getApplicableRule($inscription->filiere_id, $inscription->niveau_id, $inscription->annee_universitaire_id);

            // Récupérer la souscription pour ce frais obligatoire
            $subscription = $subscriptions->where('frais_category_id', $category->id)->first();

            // Calculer les paiements pour cette catégorie (exclure les paiements de reliquats)
            $paiements = $inscription->paiements()
                ->where('frais_category_id', $category->id)
                ->where('status', 'validé')
                ->where(function($query) {
                    $query->where('type_paiement', '!=', 'reliquat')
                          ->orWhereNull('type_paiement');
                })
                ->get();

            $totalPaye = $paiements->sum('montant');

            // PRIORITÉ: Utiliser d'abord le montant de la souscription (modifiable par admin)
            if ($subscription) {
                $montantAttendu = $subscription->amount;
                $isConfigured = true;

                \Log::info('Calcul frais obligatoire - utilise souscription', [
                    'category' => $category->name,
                    'montant_attendu' => $montantAttendu,
                    'subscription_amount' => $subscription->amount,
                    'source' => 'souscription_prioritaire'
                ]);
            } else if ($rule) {
                // Fallback: utiliser les règles selon le statut d'affectation
                $montantAttendu = $rule->getMontantByStatus($affectationStatus);
                $isConfigured = true;

                \Log::info('Calcul frais obligatoire - utilise règle', [
                    'category' => $category->name,
                    'affectation_status' => $affectationStatus,
                    'montant_attendu' => $montantAttendu,
                    'has_rule' => true,
                    'rule_amounts' => $rule->getAllAmounts(),
                    'source' => 'regle_fallback'
                ]);
            } else {
                // Dernière solution: montant par défaut de la catégorie
                $montantAttendu = $category->default_amount;
                $isConfigured = false;

                \Log::info('Calcul frais obligatoire - utilise défaut', [
                    'category' => $category->name,
                    'montant_attendu' => $montantAttendu,
                    'default_amount' => $category->default_amount,
                    'source' => 'defaut_category'
                ]);
            }
            $isSubscribed = $subscription !== null;

            $solde = $montantAttendu - $totalPaye;

            $feeCategoriesWithRules[] = [
                'category' => $category,
                'rule' => $rule,
                'montant_attendu' => $montantAttendu,
                'total_paye' => $totalPaye,
                'solde' => $solde,
                'paiements' => $paiements,
                'is_configured' => $isConfigured,
                'is_mandatory' => true,
                'is_subscribed' => $isSubscribed,
                'subscription' => $subscription,
                'status' => $solde <= 0 ? 'paid' : ($totalPaye > 0 ? 'partial' : 'unpaid')
            ];
        }
        
        // Traiter les frais optionnels (seulement ceux souscrits)
        foreach ($optionalCategories as $category) {
            $subscription = $subscriptions->where('frais_category_id', $category->id)->first();
            
            if ($subscription) {
                $rule = $category->getApplicableRule($inscription->filiere_id, $inscription->niveau_id, $inscription->annee_universitaire_id);
                
                // Calculer les paiements pour cette catégorie (exclure les paiements de reliquats)
                $paiements = $inscription->paiements()
                    ->where('frais_category_id', $category->id)
                    ->where('status', 'validé')
                    ->where(function($query) {
                        $query->where('type_paiement', '!=', 'reliquat')
                              ->orWhereNull('type_paiement');
                    })
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
                    'is_configured' => true, // Pour les frais optionnels souscrits, considérer comme configuré
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

        // Récupérer les reliquats pour cette inscription
        // Reliquats entrants (provenant d'inscriptions précédentes)
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->actifs()
            ->get();

        // Reliquats sortants (transférés vers des inscriptions futures)
        $reliquatsSortants = \App\Models\ESBTPReliquatDetail::where('inscription_source_id', $inscription->id)
            ->with(['inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->get();

        // Statistiques reliquats
        $statistiquesReliquats = [
            'total_reliquats_entrants' => $reliquatsEntrants->sum('solde_restant'),
            'total_reliquats_sortants' => $reliquatsSortants->sum('solde_restant'),
            'nombre_reliquats_actifs' => $reliquatsEntrants->where('statut', 'actif')->count(),
        ];

        return view('esbtp.inscriptions.show', compact(
            'inscription',
            'fees',
            'soldeRestant',
            'feeCategoriesWithRules',
            'categoriesfrais',
            'mandatoryFeeCategoriesWithRules',
            'availableOptionalCategories',
            'reliquatsEntrants',
            'reliquatsSortants',
            'statistiquesReliquats'
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

        // Récupérer les données pour les selects (pas de relation directe filière-niveau)
        $filieres = ESBTPFiliere::where('is_active', true)
            ->orderBy('name')
            ->get();

        $niveaux = ESBTPNiveauEtude::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Charger toutes les classes actives pour permettre le changement de filière/niveau
        $classes = ESBTPClasse::where('is_active', true)
            ->with(['filiere', 'niveauEtude'])
            ->orderBy('name')
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
            'frais_inscription' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
            'status' => 'required|in:en_attente,active,annulée,terminée',
            'affectation_status' => 'nullable|in:affecté,réaffecté,non_affecté',
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

            // Stocker les anciennes valeurs pour détecter les changements
            $ancienneFiliere = $inscription->filiere_id;
            $ancienNiveau = $inscription->niveau_id;
            $ancienneClasse = $inscription->classe_id;
            $ancienAffectationStatus = $inscription->affectation_status;

            if ($inscription->status === 'active' && !Auth::user()->hasRole('superAdmin')) {
                // Empêcher la modification de la filière, niveau et classe pour les inscriptions actives (sauf superAdmin)
                unset($data['filiere_id']);
                unset($data['niveau_id']);
                unset($data['classe_id']);
                \Log::warning('Tentative de modification de la classe/filière/niveau après activation', [
                    'inscription_id' => $inscription->id,
                    'user_id' => Auth::id()
                ]);
            } elseif ($inscription->status === 'active' && Auth::user()->hasRole('superAdmin')) {
                \Log::info('SuperAdmin modifie une inscription active', [
                    'inscription_id' => $inscription->id,
                    'user_id' => Auth::id()
                ]);
            }

            // Mettre à jour l'inscription
            $inscription->filiere_id = $data['filiere_id'] ?? $inscription->filiere_id;
            $inscription->niveau_id = $data['niveau_id'] ?? $inscription->niveau_id;
            $inscription->classe_id = $data['classe_id'] ?? $inscription->classe_id;
            $inscription->date_inscription = $data['date_inscription'];
            $inscription->type_inscription = $data['type_inscription'];
            $inscription->montant_scolarite = $data['montant_scolarite'];
            $inscription->frais_inscription = $data['frais_inscription'] ?? $inscription->frais_inscription ?? 0;
            $inscription->observations = $data['observations'];
            $inscription->affectation_status = $data['affectation_status'] ?? null;

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

            // Mettre à jour les souscriptions de frais si la filière, niveau, classe ou statut d'affectation a changé
            if ($ancienneFiliere != $inscription->filiere_id ||
                $ancienNiveau != $inscription->niveau_id ||
                $ancienneClasse != $inscription->classe_id ||
                $ancienAffectationStatus != $inscription->affectation_status) {

                \Log::info('Mise à jour des frais après changement de classe/filière/niveau/affectation', [
                    'inscription_id' => $inscription->id,
                    'ancienne_filiere' => $ancienneFiliere,
                    'nouvelle_filiere' => $inscription->filiere_id,
                    'ancien_niveau' => $ancienNiveau,
                    'nouveau_niveau' => $inscription->niveau_id,
                    'ancienne_classe' => $ancienneClasse,
                    'nouvelle_classe' => $inscription->classe_id,
                    'ancien_affectation_status' => $ancienAffectationStatus,
                    'nouveau_affectation_status' => $inscription->affectation_status,
                    'user_id' => Auth::id()
                ]);

                // Régénérer les frais pour cette inscription avec les nouvelles configurations
                $this->regenererFraisInscription($inscription);
            }

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
            // CORRECTION: inclure les inscriptions en cours de validation (pas seulement 'en_attente')
            ->where(function($q) {
                $q->where('status', 'en_attente')
                  ->orWhere(function($subQ) {
                      $subQ->where('status', 'active')
                           ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                  });
            });

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

        // Calculer les statistiques (CORRECTION: filtrer par année en cours comme la liste)
        $anneeStatsFilter = $annee ? $annee : ($anneeEnCours ? $anneeEnCours->id : null);

        // Requête de base pour les statistiques (même logique que la liste)
        $baseStatsQuery = function() use ($anneeStatsFilter) {
            return ESBTPInscription::where(function($q) {
                    $q->where('status', 'en_attente')
                      ->orWhere(function($subQ) {
                          $subQ->where('status', 'active')
                               ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                      });
                })
                ->when($anneeStatsFilter, function($q) use ($anneeStatsFilter) {
                    $q->where('annee_universitaire_id', $anneeStatsFilter);
                });
        };

        $stats = [
            'total_en_attente' => $baseStatsQuery()->count(),
            'avec_paiement' => $baseStatsQuery()
                ->whereHas('paiements', function($q) {
                    $q->where('status', 'validated');
                })->count(),
            'sans_paiement' => $baseStatsQuery()
                ->whereDoesntHave('paiements', function($q) {
                    $q->where('status', 'validated');
                })->count(),
            'prospects' => $baseStatsQuery()
                ->where('workflow_step', 'prospect')->count(),
            'documents_complets' => $baseStatsQuery()
                ->where('workflow_step', 'documents_complets')->count(),
            'en_validation' => $baseStatsQuery()
                ->where('workflow_step', 'en_validation')->count(),
        ];

        // Récupérer les catégories de frais pour la modal de paiement
        $categoriesfrais = \App\Models\ESBTPFraisCategory::where('is_active', true)->orderBy('name')->get();

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
            'fee_category_id' => 'required|exists:esbtp_frais_categories,id',
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
                return redirect()->route('esbtp.inscriptions.show', $inscription->id)
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
                return redirect()->route('esbtp.inscriptions.show', $inscription->id)
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
     * Effectuer un paiement pour une catégorie de frais spécifique.
     */
    public function payerFraisCategorie(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
            'date_paiement' => 'required|date',
            'commentaire' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Vérifier que la catégorie de frais est bien configurée pour cette inscription
            $category = ESBTPFraisCategory::findOrFail($request->frais_category_id);
            
            // Pour les frais optionnels, vérifier qu'il y a une souscription active
            if (!$category->is_mandatory) {
                $subscription = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$subscription) {
                    return redirect()->back()->with('error', 'Vous n\'êtes pas souscrit à ce frais optionnel.');
                }
            }

            // Créer le paiement
            $paiement = ESBTPPaiement::create([
                'inscription_id' => $inscription->id,
                'etudiant_id' => $inscription->etudiant_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'frais_category_id' => $request->frais_category_id,
                'type_paiement' => $category->is_mandatory ? 'frais_obligatoire' : 'frais_optionnel',
                'motif' => 'Paiement ' . $category->name,
                'montant' => $request->montant,
                'mode_paiement' => $request->mode_paiement,
                'reference_paiement' => $request->reference_paiement,
                'date_paiement' => $request->date_paiement,
                'commentaire' => $request->commentaire,
                'numero_recu' => ESBTPPaiement::genererNumeroRecu(),
                'status' => 'en_attente',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 
                'Paiement de ' . number_format($request->montant, 0, ',', ' ') . ' FCFA enregistré avec succès pour ' . $category->name);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du paiement de frais: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Transférer un trop-perçu d'une catégorie de frais vers une autre.
     */
    public function transferOverpayment(Request $request, ESBTPInscription $inscription)
    {
        $request->validate([
            'source_category_id' => 'required|exists:esbtp_frais_categories,id',
            'amount' => 'required|numeric|min:0',
            'destinations' => 'required|array|min:1',
            'destinations.*.category_id' => 'required|exists:esbtp_frais_categories,id|different:source_category_id',
            'destinations.*.amount' => 'required|numeric|min:1',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $sourceCategory = ESBTPFraisCategory::findOrFail($request->source_category_id);
            
            // Calculer le solde source
            $sourceBalanceInfo = $this->calculerSoldeCategorie($inscription, $sourceCategory);
            
            // Vérifier qu'il y a bien un trop-perçu sur la source
            if ($sourceBalanceInfo['solde'] >= 0) {
                return redirect()->back()->with('error', 'Aucun trop-perçu disponible pour cette catégorie de frais.');
            }
            
            $availableAmount = abs($sourceBalanceInfo['solde']);
            
            // Calculer le total à transférer
            $totalToTransfer = 0;
            $destinationCategories = [];
            
            foreach ($request->destinations as $destination) {
                $totalToTransfer += $destination['amount'];
                $destinationCategories[] = ESBTPFraisCategory::findOrFail($destination['category_id']);
            }
            
            // Vérifier que le total ne dépasse pas le trop-perçu disponible
            if ($totalToTransfer > $availableAmount) {
                return redirect()->back()->with('error', 
                    'Le montant total à transférer (' . number_format($totalToTransfer, 0, ',', ' ') . ' FCFA) ' .
                    'dépasse le trop-perçu disponible (' . number_format($availableAmount, 0, ',', ' ') . ' FCFA).');
            }
            
            // Vérifier qu'il n'y a pas de doublons dans les destinations
            $categoryIds = array_column($request->destinations, 'category_id');
            if (count($categoryIds) !== count(array_unique($categoryIds))) {
                return redirect()->back()->with('error', 'Impossible de transférer vers la même catégorie plusieurs fois.');
            }
            
            // Créer une référence unique pour ce transfert multiple
            $transferReference = 'MULTI-TRANSFER-' . time();
            $createdPayments = [];
            
            // Créer les paiements sortants (un seul retrait global)
            $retrait = ESBTPPaiement::create([
                'inscription_id' => $inscription->id,
                'etudiant_id' => $inscription->etudiant_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'frais_category_id' => $sourceCategory->id,
                'type_paiement' => 'transfert_sortant_multi',
                'motif' => 'Transfert vers ' . count($destinationCategories) . ' destinations',
                'montant' => -$totalToTransfer, // Montant négatif pour réduire le trop-perçu
                'mode_paiement' => 'transfert',
                'reference_paiement' => $transferReference . '-OUT',
                'date_paiement' => now(),
                'commentaire' => $request->comment ?: 'Transfert multiple automatique de trop-perçu',
                'numero_recu' => ESBTPPaiement::genererNumeroRecu(),
                'status' => 'en_attente',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            $createdPayments[] = $retrait;
            
            // Créer les paiements entrants pour chaque destination
            foreach ($request->destinations as $index => $destination) {
                $destinationCategory = ESBTPFraisCategory::findOrFail($destination['category_id']);
                $amount = $destination['amount'];
                
                $credit = ESBTPPaiement::create([
                    'inscription_id' => $inscription->id,
                    'etudiant_id' => $inscription->etudiant_id,
                    'annee_universitaire_id' => $inscription->annee_universitaire_id,
                    'frais_category_id' => $destinationCategory->id,
                    'type_paiement' => 'transfert_entrant_multi',
                    'motif' => 'Transfert depuis ' . $sourceCategory->name . ' (partie ' . ($index + 1) . ')',
                    'montant' => $amount, // Montant positif pour créditer
                    'mode_paiement' => 'transfert',
                    'reference_paiement' => $transferReference . '-IN-' . ($index + 1),
                    'date_paiement' => now(),
                    'commentaire' => $request->comment ?: 'Réception transfert multiple de trop-perçu',
                    'numero_recu' => ESBTPPaiement::genererNumeroRecu(),
                    'status' => 'en_attente',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                
                $createdPayments[] = $credit;
            }

            DB::commit();
            
            // Préparer le message de succès
            $destinationNames = collect($request->destinations)->map(function($dest) {
                $category = ESBTPFraisCategory::find($dest['category_id']);
                return $category->name . ' (' . number_format($dest['amount'], 0, ',', ' ') . ' FCFA)';
            })->join(', ');

            return redirect()->back()->with('success', 
                "Transfert multiple de " . number_format($totalToTransfer, 0, ',', ' ') . " FCFA effectué avec succès " .
                "de '{$sourceCategory->name}' vers: " . $destinationNames . "."
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du transfert multiple de trop-perçu', [
                'inscription_id' => $inscription->id,
                'source_category_id' => $request->source_category_id,
                'destinations' => $request->destinations ?? null,
                'total_amount' => $totalToTransfer ?? null,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Erreur lors du transfert: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour le montant d'une souscription (SuperAdmin uniquement).
     */
    public function updateSubscription(Request $request, ESBTPInscription $inscription, ESBTPFraisSubscription $subscription)
    {
        // Vérifier que la souscription appartient bien à cette inscription
        if ($subscription->inscription_id !== $inscription->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette souscription n\'appartient pas à cette inscription.'
            ], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $oldAmount = $subscription->amount;
            $newAmount = $request->amount;

            // Mettre à jour la souscription
            $subscription->update([
                'amount' => $newAmount,
                'updated_at' => now(),
            ]);

            // Créer un log de l'activité pour audit
            Log::info('Modification de souscription par SuperAdmin', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'inscription_id' => $inscription->id,
                'subscription_id' => $subscription->id,
                'etudiant_matricule' => $inscription->etudiant->matricule ?? 'N/A',
                'frais_category' => $subscription->fraisCategory->name ?? 'N/A',
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'difference' => $newAmount - $oldAmount,
                'reason' => $request->reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent')
            ]);

            // Optionnel: créer une entrée dans une table d'audit si elle existe
            // ESBTPSubscriptionAudit::create([...]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Souscription mise à jour avec succès. Montant: %s FCFA → %s FCFA (différence: %s%s FCFA)',
                    number_format($oldAmount, 0, ',', ' '),
                    number_format($newAmount, 0, ',', ' '),
                    $newAmount >= $oldAmount ? '+' : '',
                    number_format($newAmount - $oldAmount, 0, ',', ' ')
                ),
                'data' => [
                    'subscription_id' => $subscription->id,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'difference' => $newAmount - $oldAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la mise à jour de souscription', [
                'user_id' => auth()->id(),
                'inscription_id' => $inscription->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la souscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le solde d'une catégorie de frais pour une inscription.
     */
    private function calculerSoldeCategorie(ESBTPInscription $inscription, ESBTPFraisCategory $category)
    {
        // Récupérer la configuration ou le montant par défaut
        $configuration = ESBTPFraisConfiguration::where('frais_category_id', $category->id)
            ->where('filiere_id', $inscription->filiere_id)
            ->where('niveau_id', $inscription->niveau_id)
            ->where('is_active', true)
            ->first();

        $montantAttendu = $configuration ? $configuration->amount : $category->default_amount;

        // Calculer le total payé pour cette catégorie
        $totalPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
            ->where('frais_category_id', $category->id)
            ->where('status', 'validé')
            ->sum('montant');

        $solde = $montantAttendu - $totalPaye;

        return [
            'montant_attendu' => $montantAttendu,
            'total_paye' => $totalPaye,
            'solde' => $solde,
            'is_configured' => (bool) $configuration,
        ];
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
     * Architecture corrigée utilisant ESBTPFraisOption avec distinction class-based vs global
     */
    public function getFraisByClasse($classeId, Request $request)
    {
        try {
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->findOrFail($classeId);
            $affectationStatus = $request->get('affectation_status', 'affecté');
            
            \Log::info('getFraisByClasse appelé', [
                'classe_id' => $classeId,
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
                'annee_universitaire_id' => $classe->annee_universitaire_id
            ]);
            
            // Récupérer TOUTES les catégories de frais actives
            $allCategories = ESBTPFraisCategory::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            
            $fraisData = [];
            $hasUnconfiguredFees = false;
            
            foreach ($allCategories as $category) {
                \Log::info('Traitement catégorie', [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_type' => $category->category_type,
                    'is_mandatory' => $category->is_mandatory
                ]);
                
                $defaultAmount = $category->default_amount;
                $isConfigured = false;
                $configurationType = 'default';
                $options = collect();
                
                if ($category->is_mandatory) {
                    // FRAIS OBLIGATOIRES : Recherche configuration par classe
                    
                    // 1. Chercher une configuration spécifique pour cette filière/niveau
                    $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                        ->where('filiere_id', $classe->filiere_id)
                        ->where('niveau_id', $classe->niveau_etude_id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($configuration) {
                        $defaultAmount = $configuration->getMontantByStatus($affectationStatus);
                        $isConfigured = true;
                        $configurationType = 'configuration';
                        \Log::info("Configuration trouvée pour catégorie {$category->name}", [
                            'affectation_status' => $affectationStatus, 
                            'amount' => $defaultAmount
                        ]);
                    }
                    
                    // 2. Chercher des variants/options class-based pour cette catégorie
                    $classBasedOptions = \App\Models\ESBTPFraisOption::classBased()
                        ->forFraisCategory($category->id)
                        ->active()
                        ->ordered()
                        ->get();
                    
                    if ($classBasedOptions->count() > 0) {
                        $options = $classBasedOptions;
                        $isConfigured = true;
                        if ($configurationType === 'default') {
                            $configurationType = 'class_variants';
                        }
                        \Log::info("Options class-based trouvées pour {$category->name}", ['count' => $classBasedOptions->count()]);
                    }
                    
                } else {
                    // SERVICES OPTIONNELS : Utiliser EXACTEMENT la même logique qu'optional-config
                    $categoryWithOptions = ESBTPFraisCategory::with(['options.assignments.filiere', 'options.assignments.niveau'])
                        ->where('id', $category->id)
                        ->first();
                    
                    if ($categoryWithOptions && $categoryWithOptions->options->count() > 0) {
                        $options = $categoryWithOptions->options;
                        $isConfigured = true;
                        $configurationType = 'global_options';
                        \Log::info("Options trouvées pour {$category->name} (logique optional-config)", ['count' => $options->count()]);
                    }
                }
                
                if (!$isConfigured) {
                    $hasUnconfiguredFees = true;
                    \Log::warning("Catégorie non configurée: {$category->name}");
                }
                
                $fraisData[] = [
                    'category' => $category,
                    'default_amount' => $defaultAmount,
                    'configured_amount' => $defaultAmount,
                    'variants' => $options, // Compatibilité avec interface existante
                    'options' => $options,
                    'is_mandatory' => $category->is_mandatory,
                    'is_configured' => $isConfigured,
                    'configuration_type' => $configurationType,
                    'category_default_amount' => $category->default_amount,
                    'category_type' => $category->category_type ?? 'academic',
                    'affectation_status' => $affectationStatus // Pour le debug
                ];
            }
            
            \Log::info('Frais processés', [
                'frais_count' => count($fraisData),
                'has_unconfigured_fees' => $hasUnconfiguredFees
            ]);
            
            return response()->json([
                'success' => true,
                'classe' => $classe,
                'frais' => $fraisData,
                'has_unconfigured_fees' => $hasUnconfiguredFees,
                'configure_url' => route('esbtp.frais.configure')
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur getFraisByClasse: ' . $e->getMessage(), [
                'classe_id' => $classeId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des frais: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Régénérer les frais d'inscription après changement de classe/filière/niveau
     */
    private function regenererFraisInscription(ESBTPInscription $inscription)
    {
        try {
            \Log::info('Régénération des frais pour inscription', [
                'inscription_id' => $inscription->id,
                'filiere_id' => $inscription->filiere_id,
                'niveau_id' => $inscription->niveau_id,
                'classe_id' => $inscription->classe_id
            ]);

            // Charger les relations nécessaires
            $inscription->load(['filiere', 'niveau', 'classe']);

            // Récupérer les catégories de frais obligatoires actives
            $categoriesObligatoires = ESBTPFraisCategory::where('is_mandatory', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($categoriesObligatoires as $category) {
                // Chercher une configuration de frais pour cette catégorie et cette filière/niveau
                $fraisConfig = ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->where('is_active', true)
                    ->first();

                if ($fraisConfig) {
                    // Déterminer le montant selon le statut d'affectation
                    $affectationStatus = $inscription->affectation_status ?? 'affecté';
                    $montant = $fraisConfig->getMontantByStatus($affectationStatus);

                    // Créer ou mettre à jour la souscription (évite la duplication)
                    ESBTPFraisSubscription::updateOrCreate(
                        [
                            'inscription_id' => $inscription->id,
                            'frais_category_id' => $category->id,
                        ],
                        [
                            'selected_option_id' => null,
                            'amount' => $montant,
                            'is_active' => true,
                            'subscribed_at' => now(),
                            'created_by' => Auth::id(),
                            'notes' => 'Régénéré automatiquement après changement de classe/filière/niveau'
                        ]
                    );

                    \Log::info('Souscription créée/mise à jour', [
                        'inscription_id' => $inscription->id,
                        'category_id' => $category->id,
                        'amount' => $montant,
                        'affectation_status' => $affectationStatus
                    ]);
                }
            }

            // Note: Les frais optionnels ne sont pas automatiquement régénérés
            // L'utilisateur devra les resouscrire manuellement si nécessaire

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la régénération des frais d\'inscription', [
                'inscription_id' => $inscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Prévisualiser la situation financière de l'étudiant pour cette inscription
     */
    public function previewSituationFinanciere(ESBTPInscription $inscription)
    {
        // Charger toutes les données nécessaires
        $inscription->load([
            'etudiant.user',
            'etudiant.parents',
            'filiere',
            'niveau',
            'classe',
            'anneeUniversitaire',
            'paiements' => function($query) {
                $query->where('status', 'validé')
                      ->orderBy('date_paiement', 'desc');
            }
        ]);

        // Récupérer les frais souscrits pour cette inscription
        $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with(['fraisCategory'])
            ->get();

        // Récupérer les reliquats liés à cette inscription (entrants) - comme inscriptions.show
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->actifs()
            ->get();

        $totalReliquats = $reliquatsEntrants->sum('solde_restant');

        // Calculer les totaux
        $totalFraisAnnee = $fraisSouscrits->sum('amount'); // Frais année courante seulement
        $totalAttendu = $totalFraisAnnee + $totalReliquats; // Total = Année courante + Reliquats

        // Inclure TOUS les paiements validés (y compris reliquats)
        $totalPaye = $inscription->paiements
            ->where('status', 'validé')
            ->sum('montant');

        $soldeRestant = $totalAttendu - $totalPaye;

        // Statistiques
        $statistiques = [
            'total_frais_annee' => $totalFraisAnnee, // Frais année courante uniquement
            'total_attendu' => $totalAttendu, // Frais année + reliquats
            'total_paye' => $totalPaye, // Tous les paiements validés
            'total_reliquats' => $totalReliquats,
            'solde_restant' => $soldeRestant,
            'pourcentage_paye' => $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 2) : 0,
        ];

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', ''),
        ];

        return view('esbtp.inscriptions.situation-financiere-preview', compact(
            'inscription',
            'fraisSouscrits',
            'reliquatsEntrants',
            'statistiques',
            'etablissement'
        ));
    }

    /**
     * Exporter la situation financière en PDF
     */
    public function exportSituationFinanciere(ESBTPInscription $inscription)
    {
        // Récupérer les mêmes données que pour la preview
        $inscription->load([
            'etudiant.user',
            'etudiant.parents',
            'filiere',
            'niveau',
            'classe',
            'anneeUniversitaire',
            'paiements' => function($query) {
                $query->where('status', 'validé')
                      ->orderBy('date_paiement', 'desc');
            }
        ]);

        $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with(['fraisCategory'])
            ->get();

        // Récupérer les reliquats liés à cette inscription (entrants) - comme inscriptions.show
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->actifs()
            ->get();

        $totalReliquats = $reliquatsEntrants->sum('solde_restant');

        // Utiliser la même logique que la page show: PRIORITÉ à la souscription
        $totalFraisAnnee = $fraisSouscrits->sum('amount'); // Frais année courante seulement
        $totalAttendu = $totalFraisAnnee + $totalReliquats; // Total = Année courante + Reliquats

        // Inclure TOUS les paiements validés (y compris reliquats)
        $totalPaye = $inscription->paiements
            ->where('status', 'validé')
            ->sum('montant');

        $soldeRestant = $totalAttendu - $totalPaye;

        $statistiques = [
            'total_frais_annee' => $totalFraisAnnee, // Frais année courante uniquement
            'total_attendu' => $totalAttendu, // Frais année + reliquats
            'total_paye' => $totalPaye, // Tous les paiements validés
            'total_reliquats' => $totalReliquats,
            'solde_restant' => $soldeRestant,
            'pourcentage_paye' => $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 2) : 0,
        ];

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', null),
        ];

        // Augmenter le temps d'exécution pour le PDF
        set_time_limit(120);
        ini_set('memory_limit', '512M');

        // Générer le PDF
        $pdf = Pdf::loadView('esbtp.inscriptions.situation-financiere-pdf', compact(
            'inscription',
            'fraisSouscrits',
            'reliquatsEntrants',
            'statistiques',
            'etablissement'
        ));

        $pdf->setPaper('A4', 'portrait');

        // Optimiser les options DomPDF pour les images
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 96,
            'defaultMediaType' => 'print',
            'isFontSubsettingEnabled' => true,
            'isPhpEnabled' => true,
            'margin-top' => 10,
            'margin-right' => 10,
            'margin-bottom' => 10,
            'margin-left' => 10,
        ]);

        $filename = 'situation_financiere_' .
                   $inscription->etudiant->matricule . '_' .
                   $inscription->anneeUniversitaire->name . '_' .
                   now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Vérifier les limites du paywall pour les inscriptions
     */
    private function checkPaywallLimitsForInscription()
    {
        // Vérifier si le paywall est actif
        $isPaywallActive = \App\Models\ESBTPSystemSetting::getValue('paywall_active', false);

        if (!$isPaywallActive) {
            return false; // Pas de limitation
        }

        // Obtenir les limites configurées
        $maxInscriptionsPerYear = \App\Models\ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500);

        // Compter les inscriptions actuelles pour l'année courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', 1)->first();

        if (!$anneeCourante) {
            return false; // Pas d'année courante, on laisse passer
        }

        $inscriptionsActuelles = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeCourante->id)
            ->where('status', 'active')
            ->count();

        // Vérifier si on dépasse la limite
        if ($inscriptionsActuelles >= $maxInscriptionsPerYear) {
            \Log::warning('Paywall: Limite d\'inscriptions atteinte', [
                'inscriptions_actuelles' => $inscriptionsActuelles,
                'limite_configuree' => $maxInscriptionsPerYear,
                'annee_courante' => $anneeCourante->nom,
                'user_id' => auth()->id()
            ]);

            return true; // Limite atteinte, bloquer
        }

        return false; // Limite pas atteinte, autoriser
    }

    /**
     * Validation groupée d'inscriptions avec gestion intelligente des paiements
     */
    public function bulkValider(Request $request)
    {
        $request->validate([
            'inscription_ids' => 'required|array',
            'inscription_ids.*' => 'exists:esbtp_inscriptions,id'
        ]);

        $inscriptionIds = $request->input('inscription_ids', []);

        $stats = [
            'validees_direct' => 0,
            'paiements_valides' => 0,
            'validees_apres_paiement' => 0,
            'inscriptions_deja_validees' => 0,
            'ignorees' => [],
            'erreurs' => []
        ];

        try {
            DB::beginTransaction();

            foreach ($inscriptionIds as $id) {
                try {
                    $inscription = ESBTPInscription::with(['paiements', 'etudiant'])->find($id);

                    if (!$inscription) {
                        $stats['erreurs'][] = [
                            'id' => $id,
                            'erreur' => 'Inscription introuvable'
                        ];
                        continue;
                    }

                    // Skip si déjà validée
                    if ($inscription->status === 'active') {
                        $stats['inscriptions_deja_validees']++;
                        continue;
                    }

                    $etudiantNom = $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms;

                    // Cas 1: A déjà un paiement validé ET workflow = en_validation
                    if ($inscription->paiement_validation_id && $inscription->workflow_step === 'en_validation') {
                        $result = $this->workflowService->convertProspectToStudent($inscription, 'Validation groupée');

                        if ($result['success']) {
                            $stats['validees_direct']++;

                            // Envoyer notification à l'étudiant
                            if ($inscription->etudiant && $inscription->etudiant->user) {
                                $notificationService = app(\App\Services\NotificationService::class);
                                $notificationService->createNotification(
                                    $inscription->etudiant->user,
                                    "Inscription validée",
                                    "Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.",
                                    'success',
                                    route('esbtp.inscriptions.show', $inscription->id),
                                    auth()->user()
                                );
                            }

                            // Désactiver les rappels
                            $this->desactiverRappelsInscription($inscription->id);
                        } else {
                            $stats['erreurs'][] = [
                                'id' => $id,
                                'erreur' => $result['message']
                            ];
                        }
                        continue;
                    }

                    // Cas 2: A un/des paiement(s) validé(s) mais pas encore en workflow "en_validation"
                    $paiementsValides = $inscription->paiements->where('status', 'validé');
                    if ($paiementsValides->count() > 0) {
                        $premierPaiement = $paiementsValides->first();

                        // Associer le paiement via le workflow
                        $inscription->update([
                            'paiement_validation_id' => $premierPaiement->id,
                            'workflow_step' => 'en_validation'
                        ]);

                        // Enregistrer dans l'historique workflow
                        \App\Models\ESBTPInscriptionWorkflowHistory::createEntry(
                            $inscription->id,
                            $inscription->workflow_step,
                            'en_validation',
                            'paiement_associe',
                            auth()->id(),
                            'Paiement associé lors de validation groupée',
                            ['paiement_id' => $premierPaiement->id]
                        );

                        // Puis valider définitivement
                        $result = $this->workflowService->convertProspectToStudent($inscription, 'Validation groupée');

                        if ($result['success']) {
                            $stats['validees_direct']++;

                            // Notification
                            if ($inscription->etudiant && $inscription->etudiant->user) {
                                $notificationService = app(\App\Services\NotificationService::class);
                                $notificationService->createNotification(
                                    $inscription->etudiant->user,
                                    "Inscription validée",
                                    "Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.",
                                    'success',
                                    route('esbtp.inscriptions.show', $inscription->id),
                                    auth()->user()
                                );
                            }

                            // Désactiver les rappels
                            $this->desactiverRappelsInscription($inscription->id);
                        }
                        continue;
                    }

                    // Cas 3: A des paiements EN ATTENTE → Valider automatiquement le premier
                    $paiementsEnAttente = $inscription->paiements->where('status', 'en_attente');
                    if ($paiementsEnAttente->count() > 0) {
                        $premierPaiement = $paiementsEnAttente->first();

                        // Valider le paiement
                        $premierPaiement->update([
                            'status' => 'validé',
                            'date_validation' => now(),
                            'validateur_id' => auth()->id()
                        ]);
                        $stats['paiements_valides']++;

                        // Notifier l'étudiant de la validation du paiement
                        try {
                            $notificationService = app(\App\Services\NotificationService::class);
                            $notificationService->notifyPaiementValide($premierPaiement, auth()->user());
                        } catch (\Exception $e) {
                            Log::error('Erreur notification paiement validé (bulk): ' . $e->getMessage());
                        }

                        // Désactiver les rappels du paiement
                        $this->desactiverRappelsPaiement($premierPaiement->id);

                        // Associer le paiement à l'inscription
                        $inscription->update([
                            'paiement_validation_id' => $premierPaiement->id,
                            'workflow_step' => 'en_validation'
                        ]);

                        // Enregistrer dans l'historique
                        \App\Models\ESBTPInscriptionWorkflowHistory::createEntry(
                            $inscription->id,
                            $inscription->workflow_step,
                            'en_validation',
                            'paiement_associe',
                            auth()->id(),
                            'Paiement validé et associé lors de validation groupée',
                            ['paiement_id' => $premierPaiement->id]
                        );

                        // Valider définitivement l'inscription
                        $result = $this->workflowService->convertProspectToStudent($inscription, 'Validation groupée avec paiement auto-validé');

                        if ($result['success']) {
                            $stats['validees_apres_paiement']++;

                            // Notification inscription validée
                            if ($inscription->etudiant && $inscription->etudiant->user) {
                                $notificationService->createNotification(
                                    $inscription->etudiant->user,
                                    "Inscription validée",
                                    "Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.",
                                    'success',
                                    route('esbtp.inscriptions.show', $inscription->id),
                                    auth()->user()
                                );
                            }

                            // Désactiver les rappels de l'inscription
                            $this->desactiverRappelsInscription($inscription->id);
                        }
                        continue;
                    }

                    // Cas 4: Aucun paiement
                    $stats['ignorees'][] = [
                        'id' => $inscription->id,
                        'etudiant' => $etudiantNom,
                        'raison' => 'Aucun paiement associé'
                    ];

                } catch (\Exception $e) {
                    Log::error('Erreur validation inscription bulk #' . $id . ': ' . $e->getMessage());
                    $stats['erreurs'][] = [
                        'id' => $id,
                        'erreur' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $totalValidees = $stats['validees_direct'] + $stats['validees_apres_paiement'];
            $totalIgnorees = count($stats['ignorees']);
            $totalErreurs = count($stats['erreurs']);
            $totalTraitees = count($inscriptionIds) - $stats['inscriptions_deja_validees'];

            Log::info('Validation groupée inscriptions terminée', [
                'user_id' => auth()->id(),
                'total_selectionnees' => count($inscriptionIds),
                'stats' => $stats
            ]);

            // Construire le message de retour
            $message = '';
            if ($stats['validees_direct'] > 0) {
                $message .= "{$stats['validees_direct']} inscription(s) validée(s) directement. ";
            }
            if ($stats['paiements_valides'] > 0) {
                $message .= "{$stats['paiements_valides']} paiement(s) auto-validé(s). ";
            }
            if ($stats['validees_apres_paiement'] > 0) {
                $message .= "{$stats['validees_apres_paiement']} inscription(s) validée(s) après validation du paiement. ";
            }
            if ($stats['inscriptions_deja_validees'] > 0) {
                $message .= "{$stats['inscriptions_deja_validees']} inscription(s) déjà validée(s) (ignorée(s)). ";
            }
            if (count($stats['ignorees']) > 0) {
                $message .= count($stats['ignorees']) . " inscription(s) ignorée(s) (sans paiements). ";
            }
            if (count($stats['erreurs']) > 0) {
                $message .= count($stats['erreurs']) . " erreur(s). ";
            }

            // Stocker les détails des erreurs et inscriptions ignorées en session pour affichage visuel
            $inscriptionsAvecProblemes = [];

            // Ajouter les erreurs avec leur message
            if (is_array($stats['erreurs'])) {
                foreach ($stats['erreurs'] as $erreur) {
                    if (is_array($erreur) && isset($erreur['id']) && isset($erreur['erreur'])) {
                        $inscriptionsAvecProblemes[$erreur['id']] = [
                            'type' => 'error',
                            'message' => $erreur['erreur']
                        ];
                    }
                }
            }

            // Ajouter les ignorées avec leur raison
            if (is_array($stats['ignorees'])) {
                foreach ($stats['ignorees'] as $ignoree) {
                    if (is_array($ignoree) && isset($ignoree['id']) && isset($ignoree['raison'])) {
                        $inscriptionsAvecProblemes[$ignoree['id']] = [
                            'type' => 'warning',
                            'message' => $ignoree['raison']
                        ];
                    }
                }
            }

            // Debug: Log pour vérifier les données
            Log::info('Inscriptions avec problèmes:', ['problemes' => $inscriptionsAvecProblemes]);

            return redirect()->back()
                ->with('success', $message ?: 'Aucune inscription n\'a été traitée.')
                ->with('inscriptions_problemes', $inscriptionsAvecProblemes);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur validation groupée inscriptions: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Erreur lors de la validation groupée: ' . $e->getMessage());
        }
    }

    /**
     * Désactiver les rappels pour une inscription
     */
    private function desactiverRappelsInscription($inscriptionId)
    {
        try {
            $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPInscription')
                ->where('remindable_id', $inscriptionId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
        } catch (\Exception $e) {
            Log::error('Erreur désactivation reminder inscription: ' . $e->getMessage());
        }
    }

    /**
     * Désactiver les rappels pour un paiement
     */
    private function desactiverRappelsPaiement($paiementId)
    {
        try {
            $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                ->where('remindable_id', $paiementId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
        } catch (\Exception $e) {
            Log::error('Erreur désactivation reminder paiement: ' . $e->getMessage());
        }
    }
}
