<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Models\Setting;
use App\Services\ComptabiliteService;
use App\Services\ESBTPInscriptionService;
use App\Services\FuzzyNameMatcher;
use App\Services\InscriptionWorkflowService;
use App\Services\StudentDuplicateDetector;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use App\Http\Requests\Inscription\AnnulerInscriptionRequest;
use App\Http\Requests\Inscription\BulkValiderRequest;
use App\Http\Requests\Inscription\ChangerClasseRequest;
use App\Http\Requests\Inscription\CheckDuplicatesRequest;
use App\Http\Requests\Inscription\PayerFraisCategorieRequest;
use App\Http\Requests\Inscription\SubscribeToOptionalFeeRequest;
use App\Http\Requests\Inscription\TransferOverpaymentRequest;
use App\Http\Requests\Inscription\UnsubscribeFromOptionalFeeRequest;
use App\Http\Requests\Inscription\UpdateSubscriptionRequest;
use App\Http\Requests\Inscription\ValiderAvecPaiementRequest;
use App\Http\Requests\Inscription\ValiderDefinitivementRequest;
use App\Http\Requests\Inscription\ValiderInscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ESBTPInscriptionController extends Controller
{
    // Seuil pour afficher l'alerte doublon à l'utilisateur (score ≥ 55 = "possible")
    private const DUPLICATE_BLOCKING_SCORE = 55;

    protected $inscriptionService;

    protected $comptabiliteService;

    protected $workflowService;

    /**
     * Constructeur avec injection du service d'inscription
     */
    public function __construct(
        ESBTPInscriptionService $inscriptionService,
        ComptabiliteService $comptabiliteService,
        InscriptionWorkflowService $workflowService,
    ) {
        $this->inscriptionService = $inscriptionService;
        $this->comptabiliteService = $comptabiliteService;
        $this->workflowService = $workflowService;
        $this->middleware("auth");
        $this->middleware("permission:inscriptions.view", [
            "only" => ["index", "show"],
        ]);
        $this->middleware("permission:inscriptions.create", [
            "only" => ["create", "store"],
        ]);
        $this->middleware("permission:inscriptions.edit", [
            "only" => ["edit", "update"],
        ]);
        $this->middleware("permission:inscriptions.delete", [
            "only" => ["destroy"],
        ]);
        $this->middleware("permission:inscriptions.validate", [
            "only" => ["valider", "annuler"],
        ]);
    }

    /**
     * Afficher la liste des inscriptions.
     */
    public function index(Request $request, FuzzyNameMatcher $matcher)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        $baseLogContext = [
            "timestamp" => $startTimestamp,
            "url" => $request->fullUrl(),
            "query" => $request->query(),
            "user_id" => optional($request->user())->id,
        ];
        \Log::info("ESBTPInscriptionController@index start", $baseLogContext);

        // Récupérer les filtres de recherche
        $search = $request->input("search");
        $filiere = $request->input("filiere");
        $niveau = $request->input("niveau");
        $annee = $request->input("annee");
        $status = $request->input("status", "active");

        // Construire la requête avec les filtres
        $baseQuery = ESBTPInscription::query()->with([
            "etudiant",
            "filiere",
            "niveau",
            "classe",
            "anneeUniversitaire",
        ]);

        if ($filiere) {
            $baseQuery->where("filiere_id", $filiere);
        }

        if ($niveau) {
            $baseQuery->where("niveau_id", $niveau);
        }

        if ($annee) {
            $baseQuery->where("annee_universitaire_id", $annee);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();
            if ($anneeEnCours) {
                $baseQuery->where("annee_universitaire_id", $anneeEnCours->id);
            }
        }

        if ($status && $status !== "all") {
            if ($status === "non_validee") {
                // Inscriptions non validées : en_attente OU (active mais workflow pas finalisé)
                $baseQuery->where(function ($q) {
                    $q->where("status", "en_attente")->orWhere(function ($subQ) {
                        $subQ
                            ->where("status", "active")
                            ->where(function ($wq) {
                                $wq->whereIn("workflow_step", [
                                    "prospect",
                                    "documents_complets",
                                    "en_validation",
                                ])->orWhereNull("workflow_step");
                            });
                    });
                });
            } else {
                $baseQuery->where("status", $status);
            }
        }

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        \Log::info(
            "ESBTPInscriptionController@index processing",
            array_merge($baseLogContext, [
                "has_search" => (bool) $search,
                "filters" => [
                    "filiere" => $filiere,
                    "niveau" => $niveau,
                    "annee" => $annee,
                    "status" => $status,
                ],
                "page" => $currentPage,
                "per_page" => $perPage,
            ]),
        );

        $escapeLike = static fn(string $value): string => str_replace(
            ["\\", "%", "_"],
            ["\\\\", "\\%", "\\_"],
            $value,
        );

        if ($search) {
            $candidatesQuery = clone $baseQuery;

            $searchTokens = collect(
                preg_split("/[\s,]+/u", $search ?: "", -1, PREG_SPLIT_NO_EMPTY),
            )
                ->map(fn($token) => trim($token))
                ->filter();

            $candidatesQuery->where(function ($q) use (
                $search,
                $searchTokens,
                $escapeLike,
            ) {
                $escapedSearch = $escapeLike($search);
                $likeSearch = "%{$escapedSearch}%";

                $q->whereHas("etudiant", function ($etudiantQuery) use (
                    $likeSearch,
                    $searchTokens,
                    $escapeLike,
                ) {
                    $etudiantQuery
                        ->where("matricule", "like", $likeSearch)
                        ->orWhere("nom", "like", $likeSearch)
                        ->orWhere("prenoms", "like", $likeSearch)
                        ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", [
                            $likeSearch,
                        ])
                        ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [
                            $likeSearch,
                        ]);

                    if ($searchTokens->isNotEmpty()) {
                        $etudiantQuery->orWhere(function ($subQuery) use (
                            $searchTokens,
                            $escapeLike,
                        ) {
                            foreach ($searchTokens as $token) {
                                $escapedToken = $escapeLike($token);
                                $likeToken = "%{$escapedToken}%";
                                $subQuery
                                    ->orWhere("nom", "like", $likeToken)
                                    ->orWhere("prenoms", "like", $likeToken)
                                    ->orWhere("matricule", "like", $likeToken)
                                    ->orWhere("telephone", "like", $likeToken)
                                    ->orWhere(
                                        "email_personnel",
                                        "like",
                                        $likeToken,
                                    );
                            }
                        });
                    }
                })
                    ->orWhere("numero_recu", "like", $likeSearch)
                    ->orWhereHas("classe", function ($classeQuery) use (
                        $likeSearch,
                        $searchTokens,
                        $escapeLike,
                    ) {
                        $classeQuery->where("name", "like", $likeSearch);

                        if ($searchTokens->isNotEmpty()) {
                            $classeQuery->orWhere(function ($subQuery) use (
                                $searchTokens,
                                $escapeLike,
                            ) {
                                foreach ($searchTokens as $token) {
                                    $escapedToken = $escapeLike($token);
                                    $likeToken = "%{$escapedToken}%";
                                    $subQuery->orWhere(
                                        "name",
                                        "like",
                                        $likeToken,
                                    );
                                }
                            });
                        }
                    });
            });

            try {
                $candidates = $candidatesQuery->limit(200)->get();
            } catch (QueryException $exception) {
                \Log::warning(
                    "ESBTPInscriptionController@index fallback search triggered",
                    array_merge($baseLogContext, [
                        "message" => $exception->getMessage(),
                    ]),
                );

                $fallbackQuery = clone $baseQuery;
                $fallbackQuery->where(function ($q) use ($search, $escapeLike) {
                    $escapedSearch = $escapeLike($search);
                    $likeSearch = "%{$escapedSearch}%";

                    $q->whereHas("etudiant", function ($etudiantQuery) use (
                        $likeSearch,
                    ) {
                        $etudiantQuery
                            ->where("matricule", "like", $likeSearch)
                            ->orWhere("nom", "like", $likeSearch)
                            ->orWhere("prenoms", "like", $likeSearch);
                    })->orWhere("numero_recu", "like", $likeSearch);
                });

                $candidates = $fallbackQuery->limit(200)->get();
            }

            $scored = $matcher->match(
                $search,
                $candidates,
                function ($inscription) {
                    $etudiant = $inscription->etudiant;

                    return [
                        "matricule" => $etudiant?->matricule,
                        "nom" => $etudiant?->nom,
                        "prenoms" => $etudiant?->prenoms,
                        "full_name" => $etudiant
                            ? trim($etudiant->prenoms . " " . $etudiant->nom)
                            : null,
                        "classe" => $inscription->classe?->name,
                        "numero_inscription" =>
                            $inscription->numero_inscription,
                        "numero_recu" => $inscription->numero_recu,
                    ];
                },
                [
                    "threshold" => 35,
                    "limit" => 150,
                    "boosts" => [
                        "matricule" => 18,
                        "numero_inscription" => 12,
                        "numero_recu" => 10,
                        "full_name" => 6,
                    ],
                ],
            );

            $total = $scored->count();
            $items = $scored->forPage($currentPage, $perPage)->values();

            $inscriptions = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    "path" => $request->url(),
                    "query" => $request->query(),
                ],
            );
            $inscriptions->appends($request->query());
        } else {
            $inscriptions = $baseQuery
                ->latest()
                ->paginate($perPage)
                ->appends($request->query());
        }

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where("is_active", true)->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        // Calculer les statistiques
        $statsQuery = ESBTPInscription::query();

        if ($filiere) {
            $statsQuery->where("filiere_id", $filiere);
        }

        if ($niveau) {
            $statsQuery->where("niveau_id", $niveau);
        }

        if ($annee) {
            $statsQuery->where("annee_universitaire_id", $annee);
        } elseif ($anneeEnCours) {
            $statsQuery->where("annee_universitaire_id", $anneeEnCours->id);
        }

        $stats = [
            "total" => $statsQuery->count(),
            "actives" => (clone $statsQuery)
                ->where("status", "active")
                ->where("workflow_step", "etudiant_cree")
                ->count(),
            "en_attente" => (clone $statsQuery)
                ->where("status", "en_attente")
                ->count(),
            "non_validees" => (clone $statsQuery)
                ->where(function ($q) {
                    $q->where("status", "en_attente")->orWhere(function ($subQ) {
                        $subQ
                            ->where("status", "active")
                            ->where(function ($wq) {
                                $wq->whereIn("workflow_step", [
                                    "prospect",
                                    "documents_complets",
                                    "en_validation",
                                ])->orWhereNull("workflow_step");
                            });
                    });
                })
                ->count(),
            "annulees" => (clone $statsQuery)
                ->where("status", "annulée")
                ->count(),
            "terminees" => (clone $statsQuery)
                ->where("status", "terminée")
                ->count(),
        ];

        \Log::info(
            "ESBTPInscriptionController@index completed",
            array_merge($baseLogContext, [
                "timestamp" => now()->toIso8601String(),
                "total" => $inscriptions->total(),
                "page" => $inscriptions->currentPage(),
                "per_page" => $inscriptions->perPage(),
                "duration_ms" => round(
                    (microtime(true) - $startMicrotime) * 1000,
                    2,
                ),
            ]),
        );

        if ($request->ajax()) {
            \Log::info(
                "ESBTPInscriptionController@index returning AJAX response",
                array_merge($baseLogContext, [
                    "timestamp" => now()->toIso8601String(),
                    "duration_ms" => round(
                        (microtime(true) - $startMicrotime) * 1000,
                        2,
                    ),
                ]),
            );

            return response()->json([
                "html" => view("esbtp.inscriptions.partials.results", [
                    "inscriptions" => $inscriptions,
                ])->render(),
                "url" => $request->fullUrl(),
            ]);
        }

        \Log::info(
            "ESBTPInscriptionController@index returning view",
            array_merge($baseLogContext, [
                "timestamp" => now()->toIso8601String(),
                "duration_ms" => round(
                    (microtime(true) - $startMicrotime) * 1000,
                    2,
                ),
            ]),
        );

        return view(
            "esbtp.inscriptions.index",
            compact(
                "inscriptions",
                "filieres",
                "niveaux",
                "annees",
                "search",
                "filiere",
                "niveau",
                "annee",
                "status",
                "stats",
                "anneeEnCours",
            ),
        );
    }

    /**
     * Afficher le formulaire de création d'inscription.
     */
    public function create()
    {
        $filieres = ESBTPFiliere::where("is_active", true)->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)->get();
        $academicYears = ESBTPAnneeUniversitaire::where(
            "is_active",
            true,
        )->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        // Renommer les variables pour les utiliser dans le modal
        $anneeUniversitaires = $academicYears;
        $niveauEtudes = $niveaux;

        // Ajouter $annees pour la compatibilité avec la vue
        $annees = $academicYears;

        return view(
            "esbtp.inscriptions.create",
            compact(
                "filieres",
                "niveaux",
                "academicYears",
                "anneeEnCours",
                "anneeUniversitaires",
                "niveauEtudes",
                "annees",
            ),
        );
    }

    /**
     * Valide les paramètres de recherche de doublons.
     */
    private function validateDuplicateRequest(Request $request): array
    {
    }

    /**
     * Détermine si une exception SQL correspond à un conflit d'unicité sur le matricule.
     */
    private function isMatriculeUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        $driverCode = $exception->errorInfo[1] ?? null;

        if ($sqlState === "23000" && (int) $driverCode === 1062) {
            return Str::contains(
                strtolower($exception->getMessage()),
                "matricule",
            );
        }

        return false;
    }

    /**
     * Enregistrer une nouvelle inscription.
     */
    public function store(
        Request $request,
        StudentDuplicateDetector $duplicateDetector,
    ) {
        // Créer un fichier de log dédié pour le debug
        $debugFile = storage_path("logs/inscription_debug.log");
        $debugData = [
            "timestamp" => now()->toISOString(),
            "method" => $request->method(),
            "url" => $request->fullUrl(),
            "all_input" => $request->all(),
            "parents_input" => $request->input("parents", []),
        ];

        file_put_contents(
            $debugFile,
            "=== INSCRIPTION DEBUG " .
                now() .
                " ===\n" .
                json_encode($debugData, JSON_PRETTY_PRINT) .
                "\n\n",
            FILE_APPEND | LOCK_EX,
        );

        // Vérification du paywall avant de permettre une nouvelle inscription
        if ($this->checkPaywallLimitsForInscription()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    'Limite d\'inscriptions atteinte pour l\'année courante. Contactez African Digit Consulting pour augmenter votre quota.',
                )
                ->with("paywall_contact", "klassci@africandigitconsulting.com")
                ->withInput();
        }

        // Détection de doublons (blocage tant que non confirmé)
        if (!$request->boolean("duplicate_override")) {
            $duplicates = $duplicateDetector->find(
                $request->input("nom", ""),
                $request->input("prenoms", ""),
                $request->input("date_naissance"),
                $request->input("sexe"),
            );

            $blockingDuplicates = $duplicates->filter(function ($duplicate) {
                return ($duplicate["score"] ?? 0) >=
                    self::DUPLICATE_BLOCKING_SCORE;
            });

            if ($blockingDuplicates->isNotEmpty()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        "duplicate" =>
                            "Un étudiant avec des informations similaires existe déjà. Veuillez confirmer avant de créer une nouvelle inscription.",
                    ])
                    ->with(
                        "duplicate_suggestions",
                        $blockingDuplicates->toArray(),
                    );
            }
        }

        // Construction dynamique des règles de validation
        $rules = [
            "classe_id" => "required|exists:esbtp_classes,id",
            "nom" => "required|string|max:100",
            "prenoms" => "required|string|max:100",
            "sexe" => "required|in:M,F",
            "date_naissance" => "required|date",
            "lieu_naissance" => "nullable|string|max:100",
            "telephone" => "required|string|max:20",
            "email_personnel" => "nullable|email|max:100",
            "ville" => "nullable|string|max:100",
            "commune" => "nullable|string|max:100",
            "photo" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
            "matricule" => empty(trim((string) $request->matricule))
                ? "nullable"
                : "required|string|max:20|unique:esbtp_etudiants,matricule",
        ];
        $messages = [
            "classe_id.required" => "Veuillez sélectionner une classe",
            "nom.required" => "Le nom est obligatoire",
            "prenoms.required" => "Le(s) prénom(s) est/sont obligatoire(s)",
            "sexe.required" => "Le genre est obligatoire",
            "date_naissance.required" => "La date de naissance est obligatoire",
            "telephone.required" => "Le numéro de téléphone est obligatoire",
            "matricule.required" => "Le matricule est obligatoire",
            "matricule.unique" => "Ce matricule existe déjà",
            "photo.image" => "Le fichier photo doit être une image valide.",
            "photo.mimes" =>
                "La photo doit être au format JPEG, PNG, JPG ou GIF.",
            "photo.max" => "La photo ne doit pas dépasser 2 Mo.",
            "photo.uploaded" =>
                'La photo n\'a pas pu être téléchargée. Vérifiez la taille du fichier, le format et les limites d\'upload du serveur.',
        ];
        $parents = $request->input("parents", []);

        // Debug: Log des données parents reçues
        Log::info("Debug Parents - Données reçues:", [
            "parents" => $parents,
            "request_all" => $request->all(),
        ]);

        // Nettoyer les données parents - supprimer le template et nettoyer les parents existants
        foreach ($parents as $index => $parent) {
            // Supprimer complètement le template
            if ($index === "template") {
                unset($parents[$index]);
                file_put_contents(
                    $debugFile,
                    "Template supprimé\n",
                    FILE_APPEND | LOCK_EX,
                );

                continue;
            }

            if (isset($parent["type"]) && $parent["type"] === "existant") {
                // Pour un parent existant, ne garder que parent_id, relation et type
                $parents[$index] = [
                    "type" => "existant",
                    "parent_id" => $parent["parent_id"] ?? null,
                    "relation" => $parent["relation"] ?? null,
                ];
                file_put_contents(
                    $debugFile,
                    "Parent $index nettoyé pour type existant: " .
                        json_encode($parents[$index]) .
                        "\n",
                    FILE_APPEND | LOCK_EX,
                );
            }
        }

        // Log des parents après nettoyage
        file_put_contents(
            $debugFile,
            "Parents après nettoyage: " .
                json_encode($parents, JSON_PRETTY_PRINT) .
                "\n",
            FILE_APPEND | LOCK_EX,
        );

        foreach ($parents as $index => $parent) {
            Log::info("Debug Parent $index:", [
                "parent" => $parent,
                "type" => $parent["type"] ?? "non défini",
                "has_nom" => isset($parent["nom"]),
                "has_prenoms" => isset($parent["prenoms"]),
                "has_telephone" => isset($parent["telephone"]),
                "has_parent_id" => isset($parent["parent_id"]),
            ]);

            if (isset($parent["type"]) && $parent["type"] === "nouveau") {
                $hasParentData =
                    !empty($parent["nom"]) ||
                    !empty($parent["prenoms"]) ||
                    !empty($parent["telephone"]) ||
                    !empty($parent["email"]) ||
                    !empty($parent["profession"]) ||
                    !empty($parent["adresse"]);

                if ($hasParentData) {
                    Log::info(
                        "Parent $index: Type NOUVEAU détecté - Ajout des règles de validation",
                    );
                    $rules["parents.$index.nom"] = "required|string|max:100";
                    $rules["parents.$index.prenoms"] =
                        "required|string|max:100";
                    $rules["parents.$index.telephone"] =
                        "required|string|max:20";
                    $rules["parents.$index.relation"] = "required|string";
                    $messages["parents.$index.nom.required"] =
                        "Le nom du parent/tuteur est obligatoire";
                    $messages["parents.$index.prenoms.required"] =
                        "Le(s) prénom(s) du parent/tuteur est/sont obligatoire(s)";
                    $messages["parents.$index.telephone.required"] =
                        "Le téléphone du parent/tuteur est obligatoire";
                    $messages["parents.$index.relation.required"] =
                        "La relation avec le parent/tuteur est obligatoire";
                }
            } elseif (
                isset($parent["type"]) &&
                $parent["type"] === "existant"
            ) {
                if (!empty($parent["parent_id"])) {
                    Log::info(
                        "Parent $index: Type EXISTANT détecté - Ajout des règles pour parent existant",
                    );
                    $rules["parents.$index.parent_id"] =
                        "required|exists:esbtp_parents,id";
                    $rules["parents.$index.relation"] = "required|string";
                    $messages["parents.$index.parent_id.required"] =
                        "Veuillez sélectionner un parent existant";
                    $messages["parents.$index.parent_id.exists"] =
                        'Le parent sélectionné n\'existe pas';
                    $messages["parents.$index.relation.required"] =
                        "La relation avec le parent/tuteur est obligatoire";
                }
                // NE PAS ajouter de règle sur nom/prenoms/telephone pour un parent existant
            } else {
                Log::warning("Parent $index: Type non reconnu ou manquant", [
                    "parent" => $parent,
                ]);
            }
        }

        // Debug: Log des règles finales
        Log::info("Debug Validation - Règles appliquées:", [
            "rules" => $rules,
            "messages" => $messages,
        ]);

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            Log::error("Validation échouée:", [
                "errors" => $validator->errors()->toArray(),
                "input" => $request->all(),
            ]);

            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Log des données soumises pour débogage
            Log::info("Données reçues:", $request->all());
            // Récupérer les informations complètes de la classe sélectionnée
            $classe = ESBTPClasse::with([
                "filiere",
                "niveau",
                "annee",
            ])->findOrFail($request->classe_id);

            // Préparer les données de l'étudiant
            $etudiantData = [
                "nom" => $request->nom,
                "prenoms" => $request->prenoms,
                "sexe" => $request->sexe,
                "date_naissance" => $request->date_naissance,
                "lieu_naissance" => $request->lieu_naissance,
                "nationalite" => $request->nationalite,
                "email_personnel" => $request->email_personnel,
                "telephone" => $request->telephone,
                "adresse" => $request->adresse,
                "ville" => $request->ville,
                "commune" => $request->commune,
                "statut" => "actif",
                "creer_compte_utilisateur" => true,
                "matricule" => $request->matricule,
            ];

            // Ajouter un log pour déboguer
            \Log::info('Données de l\'étudiant', [
                "etudiantData" => $etudiantData,
                "matriculeFromRequest" => $request->matricule,
            ]);

            // Ajouter un log supplémentaire pour les champs ville et commune
            \Log::info("Champs de résidence", [
                "ville" => $request->ville,
                "commune" => $request->commune,
                "lieu_naissance" => $request->lieu_naissance,
                "adresse" => $request->adresse,
            ]);

            // Traiter la photo si fournie
            if ($request->hasFile("photo")) {
                $etudiantData["photo"] = $this->handlePhotoUpload(
                    $request->file("photo"),
                );
            }

            // Récupérer le statut d'affectation depuis le request
            $affectationStatus = $request->input(
                "affectation_status",
                "affecté",
            );

            // CORRECTION: Utiliser l'année courante au lieu de l'année de la classe
            $anneeCourante = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();
            if (!$anneeCourante) {
                throw new \Exception(
                    "Aucune année universitaire courante définie. Veuillez configurer l'année courante.",
                );
            }

            // Préparer les données d'inscription
            $inscriptionData = [
                "date_inscription" =>
                    $request->date_inscription ?? now()->format("Y-m-d"),
                "classe_id" => $classe->id,
                "annee_universitaire_id" => $anneeCourante->id, // Utiliser l'année courante
                "status" => "en_attente",
                "filiere_id" => $classe->filiere_id,
                "niveau_id" => $classe->niveau_etude_id,
                "type_inscription" => "première_inscription",
                "montant_scolarite" => $request->montant_scolarite ?? 0,
                "frais_inscription" => $request->frais_inscription ?? 0,
                "affectation_status" => $affectationStatus, // Sauvegarder le statut d'affectation
                "est_transfert" => $request->boolean("est_transfert", false), // Transfert d'établissement
                "etablissement_origine" => $request->input(
                    "etablissement_origine",
                ), // Nom de l'établissement d'origine
            ];

            // Si la classe a des relations filière et niveau, les ajouter aux données de l'étudiant
            if ($classe->filiere_id) {
                $etudiantData["filiere_id"] = $classe->filiere_id;
            }

            if ($classe->niveau_etude_id) {
                $etudiantData["niveau_etude_id"] = $classe->niveau_etude_id;
            }

            // Les paiements seront gérés lors de la validation de l'inscription

            // Préparer les données des parents
            $parentsData = [];

            // Traiter les parents du formulaire
            if ($request->has("parents")) {
                foreach ($request->parents as $parent) {
                    if (
                        isset($parent["type"]) &&
                        $parent["type"] === "existant" &&
                        !empty($parent["parent_id"])
                    ) {
                        // Parent existant sélectionné
                        $parentsData[] = [
                            "parent_id" => $parent["parent_id"],
                            "relation" => $parent["relation"] ?? "Autre",
                        ];
                    } elseif (
                        isset($parent["type"]) &&
                        $parent["type"] === "nouveau" &&
                        !empty($parent["nom"]) &&
                        !empty($parent["prenoms"]) &&
                        !empty($parent["telephone"])
                    ) {
                        // Nouveau parent
                        $parentsData[] = [
                            "nom" => $parent["nom"],
                            "prenoms" => $parent["prenoms"],
                            "email" => $parent["email"] ?? null,
                            "telephone" => $parent["telephone"] ?? null,
                            "profession" => $parent["profession"] ?? null,
                            "relation" => $parent["relation"] ?? "Autre",
                            "adresse" => $parent["adresse"] ?? null,
                        ];
                    }
                }
            }

            $selectedOptionals = $request->input("fee_optionals", []);

            // Traitement des frais sélectionnés selon la nouvelle architecture
            $fraisVariants = $request->input("frais", []);
            $selectedOptionals = []; // Format pour la nouvelle méthode ESBTPInscriptionService

            // Convertir le format des frais pour la nouvelle architecture
            foreach ($fraisVariants as $categoryId => $fraisData) {
                if (!empty($fraisData["variant_id"])) {
                    $selectedOptionals[$categoryId] = $fraisData;
                }
            }

            // Ajouter un log plus détaillé en cas d'erreur
            \Log::info('Données de l\'inscription avec variants', [
                "etudiantData" => $etudiantData,
                "inscriptionData" => $inscriptionData,
                "parentsData" => $parentsData,
                "selectedOptionals" => $selectedOptionals,
                "fraisVariants" => $fraisVariants,
            ]);

            $autoGenerateMatricule = empty(trim((string) $request->matricule));
            if ($autoGenerateMatricule) {
                $etudiantData["matricule"] = null;
            }

            $inscription = null;
            $maxAttempts = 3;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                DB::beginTransaction();
                try {
                    if ($autoGenerateMatricule) {
                        $etudiantData["matricule"] = null;
                    }

                    $inscription = $this->inscriptionService->createInscription(
                        $etudiantData,
                        $inscriptionData,
                        $parentsData,
                        null,
                        auth()->id(),
                        $selectedOptionals,
                        $affectationStatus,
                    );

                    DB::commit();
                    break;
                } catch (QueryException $exception) {
                    DB::rollBack();

                    if (
                        $autoGenerateMatricule &&
                        $this->isMatriculeUniqueViolation($exception) &&
                        $attempt < $maxAttempts
                    ) {
                        Log::warning(
                            "Conflit de matricule détecté, nouvelle tentative de génération.",
                            [
                                "attempt" => $attempt,
                                "etudiant_nom" => $etudiantData["nom"],
                                "etudiant_prenoms" => $etudiantData["prenoms"],
                            ],
                        );

                        continue;
                    }

                    throw $exception;
                } catch (\Exception $exception) {
                    DB::rollBack();
                    throw $exception;
                }
            }

            if (!$inscription) {
                throw new \RuntimeException(
                    "Impossible de générer un matricule unique pour cet étudiant.",
                );
            }

            // Envoyer les notifications aux admins, coordonnateurs et secrétaires
            try {
                $notificationService = app(
                    \App\Services\NotificationService::class,
                );
                $notificationService->notifyInscriptionCreated(
                    $inscription,
                    auth()->user(),
                );

                // Envoyer notification aux parents avec identifiants
                if (
                    $inscription->etudiant &&
                    $inscription->etudiant->user &&
                    session("generated_password")
                ) {
                    $credentials = [
                        "username" => $inscription->etudiant->user->username,
                        "password" => session("generated_password"),
                    ];
                    $notificationService->notifyParentsInscriptionCreated(
                        $inscription,
                        $credentials,
                    );
                }
            } catch (\Exception $e) {
                Log::error(
                    "Erreur envoi notification inscription: " .
                        $e->getMessage(),
                );
            }

            // Stocker les informations du compte dans la session
            if (
                $inscription &&
                $inscription->etudiant &&
                $inscription->etudiant->user
            ) {
                $user = $inscription->etudiant->user;
                session()->flash("account_info", [
                    "username" => $user->username,
                    "password" => session("generated_password"),
                    "role" => "Étudiant",
                ]);
            }

            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with(
                    "success",
                    'Inscription enregistrée avec succès. L\'administration pourra valider l\'inscription en associant un paiement.',
                );
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::error('Erreur lors de l\'inscription: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Une erreur est survenue lors de l\'inscription. Détails : ' .
                        $e->getMessage(),
                )
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
            "etudiant.parents",
            "filiere",
            "niveau",
            "classe",
            "anneeUniversitaire",
            "paiements",
            "payments",
        ]);

        // Frais/échéances liés à l'inscription
        $fees = \App\Models\ESBTP\Fee::where("inscription_id", $inscription->id)
            ->orderBy("due_date")
            ->get();
        // Paiements validés liés à l'inscription
        $soldeRestant = $fees->sum(function ($fee) {
            return $fee->amount - $fee->totalPaidAmount();
        });

        // Récupérer les catégories de frais avec règles pour cette inscription
        $mandatoryCategories = \App\Models\ESBTPFraisCategory::where(
            "is_mandatory",
            true,
        )
            ->active()
            ->ordered()
            ->get();
        $optionalCategories = \App\Models\ESBTPFraisCategory::where(
            "is_mandatory",
            false,
        )
            ->active()
            ->ordered()
            ->with(['options' => fn($q) => $q->where('esbtp_frais_options.is_active', true)->orderBy('esbtp_frais_options.sort_order')])
            ->get();

        // Récupérer les souscriptions actives pour cette inscription
        $subscriptions = \App\Models\ESBTPFraisSubscription::getActiveSubscriptions(
            $inscription->id,
        );
        $subscribedCategoryIds = $subscriptions
            ->pluck("frais_category_id")
            ->toArray();

        $feeCategoriesWithRules = [];

        // Récupérer le statut d'affectation de l'inscription
        $affectationStatus = $inscription->affectation_status ?? "affecté";

        // Log pour debugging du statut d'affectation
        \Log::info('Affichage inscription - Statut d\'affectation', [
            "inscription_id" => $inscription->id,
            "affectation_status" => $affectationStatus,
            "matricule" => $inscription->etudiant->matricule ?? "N/A",
        ]);

        // Traiter les frais obligatoires (utiliser les souscriptions individuelles)
        foreach ($mandatoryCategories as $category) {
            $rule = $category->getApplicableRule(
                $inscription->filiere_id,
                $inscription->niveau_id,
                $inscription->annee_universitaire_id,
            );

            // Récupérer la souscription pour ce frais obligatoire
            $subscription = $subscriptions
                ->where("frais_category_id", $category->id)
                ->first();

            // Calculer les paiements pour cette catégorie (exclure les paiements de reliquats)
            $paiements = $inscription
                ->paiements()
                ->where("frais_category_id", $category->id)
                ->where("status", "validé")
                ->where(function ($query) {
                    $query
                        ->where("type_paiement", "!=", "reliquat")
                        ->orWhereNull("type_paiement");
                })
                ->get();

            $totalPaye = $paiements->sum("montant");

            // PRIORITÉ: Utiliser d'abord le montant de la souscription (modifiable par admin)
            if ($subscription) {
                $montantAttendu = $subscription->amount;
                $isConfigured = true;

                \Log::info("Calcul frais obligatoire - utilise souscription", [
                    "category" => $category->name,
                    "montant_attendu" => $montantAttendu,
                    "subscription_amount" => $subscription->amount,
                    "source" => "souscription_prioritaire",
                ]);
            } elseif ($rule) {
                // Fallback: utiliser les règles selon le statut d'affectation
                $montantAttendu = $rule->getMontantByStatus($affectationStatus);
                $isConfigured = true;

                \Log::info("Calcul frais obligatoire - utilise règle", [
                    "category" => $category->name,
                    "affectation_status" => $affectationStatus,
                    "montant_attendu" => $montantAttendu,
                    "has_rule" => true,
                    "rule_amounts" => $rule->getAllAmounts(),
                    "source" => "regle_fallback",
                ]);
            } else {
                // Dernière solution: montant par défaut de la catégorie
                $montantAttendu = $category->default_amount;
                $isConfigured = false;

                \Log::info("Calcul frais obligatoire - utilise défaut", [
                    "category" => $category->name,
                    "montant_attendu" => $montantAttendu,
                    "default_amount" => $category->default_amount,
                    "source" => "defaut_category",
                ]);
            }
            $isSubscribed = $subscription !== null;

            $solde = $montantAttendu - $totalPaye;

            $feeCategoriesWithRules[] = [
                "category" => $category,
                "rule" => $rule,
                "montant_attendu" => $montantAttendu,
                "total_paye" => $totalPaye,
                "solde" => $solde,
                "paiements" => $paiements,
                "is_configured" => $isConfigured,
                "is_mandatory" => true,
                "is_subscribed" => $isSubscribed,
                "subscription" => $subscription,
                "status" =>
                    $solde <= 0
                        ? "paid"
                        : ($totalPaye > 0
                            ? "partial"
                            : "unpaid"),
            ];
        }

        // Traiter les frais optionnels (seulement ceux souscrits)
        foreach ($optionalCategories as $category) {
            $subscription = $subscriptions
                ->where("frais_category_id", $category->id)
                ->first();

            if ($subscription) {
                $rule = $category->getApplicableRule(
                    $inscription->filiere_id,
                    $inscription->niveau_id,
                    $inscription->annee_universitaire_id,
                );

                // Calculer les paiements pour cette catégorie (exclure les paiements de reliquats)
                $paiements = $inscription
                    ->paiements()
                    ->where("frais_category_id", $category->id)
                    ->where("status", "validé")
                    ->where(function ($query) {
                        $query
                            ->where("type_paiement", "!=", "reliquat")
                            ->orWhereNull("type_paiement");
                    })
                    ->get();

                $totalPaye = $paiements->sum("montant");
                $montantAttendu = $subscription->amount; // Utiliser le montant de la souscription
                $solde = $montantAttendu - $totalPaye;

                $feeCategoriesWithRules[] = [
                    "category" => $category,
                    "rule" => $rule,
                    "montant_attendu" => $montantAttendu,
                    "total_paye" => $totalPaye,
                    "solde" => $solde,
                    "paiements" => $paiements,
                    "is_configured" => true, // Pour les frais optionnels souscrits, considérer comme configuré
                    "is_mandatory" => false,
                    "is_subscribed" => true,
                    "subscription" => $subscription,
                    "status" =>
                        $solde <= 0
                            ? "paid"
                            : ($totalPaye > 0
                                ? "partial"
                                : "unpaid"),
                ];
            }
        }

        // Récupérer les frais optionnels non souscrits (pour permettre la souscription)
        $availableOptionalCategories = $optionalCategories->filter(function (
            $category,
        ) use ($subscribedCategoryIds) {
            return !in_array($category->id, $subscribedCategoryIds);
        });

        // Récupérer les catégories de frais pour la modal de paiement
        $categoriesfrais = collect($feeCategoriesWithRules)->pluck("category");

        // Filtrer les catégories obligatoires pour le debug
        $mandatoryFeeCategoriesWithRules = collect(
            $feeCategoriesWithRules,
        )->filter(function ($item) {
            return $item["is_mandatory"];
        });

        // Récupérer les reliquats pour cette inscription
        // Reliquats entrants (provenant d'inscriptions précédentes)
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where(
            "inscription_destination_id",
            $inscription->id,
        )
            ->with([
                "inscriptionSource.anneeUniversitaire",
                "fraisSubscription.fraisCategory",
                "fraisSubscription.selectedOption",
            ])
            ->actifs()
            ->get();

        // Reliquats sortants (transférés vers des inscriptions futures)
        $reliquatsSortants = \App\Models\ESBTPReliquatDetail::where(
            "inscription_source_id",
            $inscription->id,
        )
            ->with([
                "inscriptionDestination.anneeUniversitaire",
                "fraisSubscription.fraisCategory",
                "fraisSubscription.selectedOption",
            ])
            ->get();

        // Statistiques reliquats
        $statistiquesReliquats = [
            "total_reliquats_entrants" => $reliquatsEntrants->sum(
                "solde_restant",
            ),
            "total_reliquats_sortants" => $reliquatsSortants->sum(
                "solde_restant",
            ),
            "nombre_reliquats_actifs" => $reliquatsEntrants
                ->where("statut", "actif")
                ->count(),
        ];

        // Formater les données de réinscription si c'est une réinscription
        $reinscriptionData = null;
        if (
            $inscription->type_inscription === "réinscription" ||
            $inscription->type_inscription === "reinscription"
        ) {
            $reinscriptionData = [
                "affectation_status" => $inscription->affectation_status,
                "affectation_label" => ucfirst(
                    str_replace(
                        "_",
                        " ",
                        $inscription->affectation_status ?? "Non renseigné",
                    ),
                ),
                "observations" => $inscription->reinscription_observations,
            ];

            // Parser la décision depuis observations (format: "passage - observations")
            if ($reinscriptionData["observations"]) {
                $parts = explode(" - ", $reinscriptionData["observations"], 2);
                $reinscriptionData["decision"] = ucfirst(trim($parts[0]));
                $reinscriptionData["decision_label"] = match (
                    strtolower(trim($parts[0]))
                ) {
                    "passage" => "Passage au niveau supérieur",
                    "redoublement" => "Redoublement",
                    "rattrapage" => "Session de rattrapage",
                    default => ucfirst(trim($parts[0])),
                };
                $reinscriptionData["notes"] = isset($parts[1])
                    ? trim($parts[1])
                    : null;
            }

            // Pour une réinscription, le "reliquat" = uniquement les reliquats entrants de l'année précédente
            // (pas le solde de l'inscription actuelle qui est juste les frais de l'année en cours)
            $reliquatMontant =
                $statistiquesReliquats["total_reliquats_entrants"] ?? 0;

            $reinscriptionData["reliquat_montant"] = $reliquatMontant;
            $reinscriptionData["reliquat_gere"] = $reliquatMontant <= 0;
        }

        // Charger les classes compatibles pour le modal d'affectation (si classe manquante)
        $classesDisponibles = collect();
        if (!$inscription->classe_id) {
            $anneeCouranteId = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

            $classesDisponibles = ESBTPClasse::where("is_active", true)
                ->where("filiere_id", $inscription->filiere_id)
                ->where("niveau_etude_id", $inscription->niveau_id)
                ->when($anneeCouranteId, fn($q) => $q->withCount([
                    'inscriptions as nombre_etudiants' => fn($sub) => $sub
                        ->where('status', 'active')
                        ->where('annee_universitaire_id', $anneeCouranteId),
                ]))
                ->orderBy("name")
                ->get()
                ->map(fn($c) => [
                    "id" => $c->id,
                    "name" => $c->name,
                    "places_totales" => $c->places_totales ?? 0,
                    "places_disponibles" => max(0, ($c->places_totales ?? 0) - ($c->nombre_etudiants ?? 0)),
                ]);
        }

        return view(
            "esbtp.inscriptions.show",
            compact(
                "inscription",
                "fees",
                "soldeRestant",
                "feeCategoriesWithRules",
                "categoriesfrais",
                "mandatoryFeeCategoriesWithRules",
                "availableOptionalCategories",
                "reliquatsEntrants",
                "reliquatsSortants",
                "statistiquesReliquats",
                "reinscriptionData",
                "classesDisponibles",
            ),
        );
    }

    /**
     * Afficher le formulaire de modification d'une inscription.
     */
    public function edit(Request $request, ESBTPInscription $inscription)
    {
        // Vérifier si l'inscription peut être modifiée
        if ($inscription->status === "terminée") {
            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with(
                    "error",
                    "Les inscriptions terminées ne peuvent pas être modifiées.",
                );
        }

        // Charger les relations nécessaires
        $inscription->load([
            "etudiant",
            "filiere",
            "niveau",
            "classe",
            "anneeUniversitaire",
        ]);

        // Récupérer les données pour les selects (pas de relation directe filière-niveau)
        $filieres = ESBTPFiliere::where("is_active", true)
            ->orderBy("name")
            ->get();

        $niveaux = ESBTPNiveauEtude::where("is_active", true)
            ->orderBy("name")
            ->get();

        // Charger toutes les classes actives pour permettre le changement de filière/niveau
        $classes = ESBTPClasse::where("is_active", true)
            ->with(["filiere", "niveauEtude"])
            ->orderBy("name")
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();

        if ($request->boolean("embedded")) {
            return view(
                "esbtp.inscriptions.embed.edit",
                compact(
                    "inscription",
                    "filieres",
                    "niveaux",
                    "classes",
                    "annees",
                ),
            );
        }

        return view(
            "esbtp.inscriptions.edit",
            compact("inscription", "filieres", "niveaux", "classes", "annees"),
        );
    }

    /**
     * Mettre à jour une inscription.
     */
    public function update(Request $request, ESBTPInscription $inscription)
    {
        // Vérifier si l'inscription peut être modifiée
        if ($inscription->status === "terminée") {
            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with(
                    "error",
                    "Les inscriptions terminées ne peuvent pas être modifiées.",
                );
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            "filiere_id" => "required|exists:esbtp_filieres,id",
            "niveau_id" => "required|exists:esbtp_niveau_etudes,id",
            "classe_id" => "nullable|exists:esbtp_classes,id",
            "date_inscription" => "required|date",
            "type_inscription" =>
                "required|in:première_inscription,réinscription,transfert",
            "montant_scolarite" => "required|numeric|min:0",
            "frais_inscription" => "nullable|numeric|min:0",
            "observations" => "nullable|string",
            "status" => "required|in:en_attente,active,annulée,terminée",
            "affectation_status" => "nullable|in:affecté,réaffecté,non_affecté",
            "est_transfert" => "nullable|boolean",
            "etablissement_origine" => "nullable|string|max:255",
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            // Stocker les anciennes valeurs pour détecter les changements
            $ancienneFiliere = $inscription->filiere_id;
            $ancienNiveau = $inscription->niveau_id;
            $ancienneClasse = $inscription->classe_id;
            $ancienAffectationStatus = $inscription->affectation_status;

            if (
                $inscription->status === "active" &&
                !Auth::user()->hasRole("superAdmin")
            ) {
                // Empêcher la modification de la filière, niveau et classe pour les inscriptions actives (sauf superAdmin)
                unset($data["filiere_id"]);
                unset($data["niveau_id"]);
                unset($data["classe_id"]);
                \Log::warning(
                    "Tentative de modification de la classe/filière/niveau après activation",
                    [
                        "inscription_id" => $inscription->id,
                        "user_id" => Auth::id(),
                    ],
                );
            } elseif (
                $inscription->status === "active" &&
                Auth::user()->hasRole("superAdmin")
            ) {
                \Log::info("SuperAdmin modifie une inscription active", [
                    "inscription_id" => $inscription->id,
                    "user_id" => Auth::id(),
                ]);
            }

            // Mettre à jour l'inscription
            $inscription->filiere_id =
                $data["filiere_id"] ?? $inscription->filiere_id;
            $inscription->niveau_id =
                $data["niveau_id"] ?? $inscription->niveau_id;
            $inscription->classe_id =
                $data["classe_id"] ?? $inscription->classe_id;
            $inscription->date_inscription = $data["date_inscription"];
            $inscription->type_inscription = $data["type_inscription"];
            $inscription->montant_scolarite = $data["montant_scolarite"];
            $inscription->frais_inscription =
                $data["frais_inscription"] ??
                ($inscription->frais_inscription ?? 0);
            $inscription->observations = $data["observations"];
            $inscription->affectation_status =
                $data["affectation_status"] ?? null;

            // Mettre à jour les champs de transfert (seulement si type_inscription = 'première_inscription')
            if ($inscription->type_inscription === "première_inscription") {
                $inscription->est_transfert = $request->boolean(
                    "est_transfert",
                    false,
                );
                $inscription->etablissement_origine = $request->input(
                    "etablissement_origine",
                );
            } else {
                // Réinitialiser si ce n'est pas une première inscription
                $inscription->est_transfert = false;
                $inscription->etablissement_origine = null;
            }

            // Mettre à jour le statut et les champs associés
            $nouveauStatut = $data["status"];
            $ancienStatut = $inscription->status;

            if ($nouveauStatut !== $ancienStatut) {
                $inscription->status = $nouveauStatut;

                if ($nouveauStatut === "active" && $ancienStatut !== "active") {
                    $inscription->date_validation = now();
                    $inscription->validated_by = Auth::id();
                }

                // Si l'inscription devient inactive ou annulée, mettre à jour l'étudiant si nécessaire
                if (in_array($nouveauStatut, ["annulée", "terminée"])) {
                    $etudiant = $inscription->etudiant;
                    $autresInscriptionsActives = $etudiant
                        ->inscriptions()
                        ->where("id", "!=", $inscription->id)
                        ->whereIn("status", ["active", "en_attente"])
                        ->exists();

                    if (
                        !$autresInscriptionsActives &&
                        $etudiant->statut === "actif"
                    ) {
                        if ($nouveauStatut === "terminée") {
                            $etudiant->statut = "diplômé";
                        } else {
                            $etudiant->statut = "inactif";
                        }
                        $etudiant->save();
                    }
                }
            }

            $inscription->updated_by = Auth::id();
            $inscription->save();

            // Mettre à jour les souscriptions de frais si la filière, niveau, classe ou statut d'affectation a changé
            if (
                $ancienneFiliere != $inscription->filiere_id ||
                $ancienNiveau != $inscription->niveau_id ||
                $ancienneClasse != $inscription->classe_id ||
                $ancienAffectationStatus != $inscription->affectation_status
            ) {
                \Log::info(
                    "Mise à jour des frais après changement de classe/filière/niveau/affectation",
                    [
                        "inscription_id" => $inscription->id,
                        "ancienne_filiere" => $ancienneFiliere,
                        "nouvelle_filiere" => $inscription->filiere_id,
                        "ancien_niveau" => $ancienNiveau,
                        "nouveau_niveau" => $inscription->niveau_id,
                        "ancienne_classe" => $ancienneClasse,
                        "nouvelle_classe" => $inscription->classe_id,
                        "ancien_affectation_status" => $ancienAffectationStatus,
                        "nouveau_affectation_status" =>
                            $inscription->affectation_status,
                        "user_id" => Auth::id(),
                    ],
                );

                // Régénérer les frais pour cette inscription avec les nouvelles configurations
                $this->regenererFraisInscription($inscription);
            }

            DB::commit();

            $successMessage = "Inscription mise à jour avec succès.";

            if ($request->boolean("embedded_mode")) {
                return redirect()
                    ->route("esbtp.inscriptions.edit", [
                        "inscription" => $inscription->id,
                        "embedded" => 1,
                    ])
                    ->with("embedded_success_inscription", $successMessage);
            }

            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with("success", $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la mise à jour: " . $e->getMessage(),
                )
                ->withInput();
        }
    }

    /**
     * Valider une inscription.
     */
    public function valider(ValiderInscriptionRequest $request, ESBTPInscription $inscription)
    {
        try {
            DB::beginTransaction();

            $result = $this->inscriptionService->validerInscription(
                $inscription->id,
                auth()->id(),
            );

            if (! $result[success]) {
                throw new \Exception($result[message]);
            }

            $montantPaye = $request->input("montant_paye", 0);
            if ($montantPaye > 0) {
                $this->comptabiliteService->validerPaiementInscription(
                    $inscription,
                    $montantPaye,
                );
            }

            DB::commit();

            // Rediriger vers la fiche étudiant si on vient de etudiants.show
            if ($request->input('redirect_to') === 'etudiant' && $inscription->etudiant_id) {
                return redirect()
                    ->route("esbtp.etudiants.show", $inscription->etudiant_id)
                    ->with("success", "Inscription validée avec succès.");
            }

            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with("success", "Inscription validée avec succès.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la validation: " . $e->getMessage(),
                );
        }
    }

    /**
     * Annuler une inscription.
     */
    public function annuler(AnnulerInscriptionRequest $request, ESBTPInscription $inscription)
    {
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $motif = $request->input("motif");
            $result = $this->inscriptionService->annulerInscription(
                $inscription->id,
                $motif,
                Auth::id(),
            );

            if ($result["success"]) {
                if ($request->ajax()) {
                    return response()->json([
                        "success" => true,
                        "message" => "Inscription annulée avec succès.",
                    ]);
                }

                return redirect()
                    ->route("esbtp.inscriptions.show", $inscription->id)
                    ->with("success", "Inscription annulée avec succès.");
            } else {
                throw new \Exception($result["message"]);
            }
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            'Erreur lors de l\'annulation: ' . $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'annulation: ' . $e->getMessage(),
                );
        }
    }

    /**
     * Interface d'administration pour la validation des inscriptions.
     */
    public function administration(Request $request)
    {
        // Récupérer les filtres
        $search = $request->input("search");
        $filiere = $request->input("filiere");
        $niveau = $request->input("niveau");
        $annee = $request->input("annee");
        $workflow_step = $request->input("workflow_step");
        $has_payment = $request->input("has_payment");

        // Construire la requête pour les inscriptions en attente de validation
        $query = ESBTPInscription::query()
            ->with([
                "etudiant",
                "filiere",
                "niveau",
                "classe",
                "anneeUniversitaire",
                "paiements",
            ])
            // CORRECTION: inclure les inscriptions en cours de validation (pas seulement 'en_attente')
            ->where(function ($q) {
                $q->where("status", "en_attente")->orWhere(function ($subQ) {
                    $subQ
                        ->where("status", "active")
                        ->whereIn("workflow_step", [
                            "prospect",
                            "documents_complets",
                            "en_validation",
                        ]);
                });
            });

        // Appliquer les filtres
        if ($search) {
            $query->whereHas("etudiant", function ($q) use ($search) {
                $q->where("matricule", "like", "%{$search}%")
                    ->orWhere("nom", "like", "%{$search}%")
                    ->orWhere("prenoms", "like", "%{$search}%");
            });
        }

        if ($filiere) {
            $query->where("filiere_id", $filiere);
        }

        if ($niveau) {
            $query->where("niveau_id", $niveau);
        }

        if ($annee) {
            $query->where("annee_universitaire_id", $annee);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();
            if ($anneeEnCours) {
                $query->where("annee_universitaire_id", $anneeEnCours->id);
            }
        }

        if ($workflow_step) {
            $query->where("workflow_step", $workflow_step);
        }

        // Filtrer par statut de paiement
        if ($has_payment === "yes") {
            $query->whereHas("paiements", function ($q) {
                $q->where("status", "validé");
            });
        } elseif ($has_payment === "no") {
            $query->whereDoesntHave("paiements", function ($q) {
                $q->where("status", "validé");
            });
        }

        // Récupérer les inscriptions
        $inscriptions = $query->latest()->paginate(20);

        $inscriptions->getCollection()->transform(function ($inscription) {
            $availability = $this->workflowService->checkClassAvailability(
                $inscription->classe_id,
            );
            $inscription->class_availability = $availability;

            return $inscription;
        });

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where("is_active", true)->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        // Calculer les statistiques (CORRECTION: filtrer par année en cours comme la liste)
        $anneeStatsFilter = $annee
            ? $annee
            : ($anneeEnCours
                ? $anneeEnCours->id
                : null);

        // Requête de base pour les statistiques (même logique que la liste)
        $baseStatsQuery = function () use ($anneeStatsFilter) {
            return ESBTPInscription::where(function ($q) {
                $q->where("status", "en_attente")->orWhere(function ($subQ) {
                    $subQ
                        ->where("status", "active")
                        ->whereIn("workflow_step", [
                            "prospect",
                            "documents_complets",
                            "en_validation",
                        ]);
                });
            })->when($anneeStatsFilter, function ($q) use ($anneeStatsFilter) {
                $q->where("annee_universitaire_id", $anneeStatsFilter);
            });
        };

        $stats = [
            "total_en_attente" => $baseStatsQuery()->count(),
            "avec_paiement" => $baseStatsQuery()
                ->whereHas("paiements", function ($q) {
                    $q->where("status", "validé");
                })
                ->count(),
            "sans_paiement" => $baseStatsQuery()
                ->whereDoesntHave("paiements", function ($q) {
                    $q->where("status", "validé");
                })
                ->count(),
            "prospects" => $baseStatsQuery()
                ->where("workflow_step", "prospect")
                ->count(),
            "documents_complets" => $baseStatsQuery()
                ->where("workflow_step", "documents_complets")
                ->count(),
            "en_validation" => $baseStatsQuery()
                ->where("workflow_step", "en_validation")
                ->count(),
        ];

        // Récupérer les catégories de frais pour la modal de paiement
        $categoriesfrais = \App\Models\ESBTPFraisCategory::where(
            "is_active",
            true,
        )
            ->orderBy("name")
            ->get();

        if ($request->ajax()) {
            return response()->json([
                "html" => view(
                    "esbtp.inscriptions.partials.administration-results",
                    [
                        "inscriptions" => $inscriptions,
                    ],
                )->render(),
                "url" => $request->fullUrl(),
            ]);
        }

        return view(
            "esbtp.inscriptions.administration",
            compact(
                "inscriptions",
                "filieres",
                "niveaux",
                "annees",
                "search",
                "filiere",
                "niveau",
                "annee",
                "workflow_step",
                "has_payment",
                "stats",
                "anneeEnCours",
                "categoriesfrais",
            ),
        );
    }

    /**
     * Supprimer une inscription.
     */
    public function destroy(ESBTPInscription $inscription)
    {
        try {
            $inscription->delete();

            return redirect()
                ->route("esbtp.inscriptions.index")
                ->with("success", "Inscription supprimée avec succès.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la suppression: " . $e->getMessage(),
                );
        }
    }

    /**
     * Gère l'upload de la photo de l'étudiant.
     *
     * @param  \Illuminate\Http\UploadedFile  $photo
     * @return string
     */
    private function handlePhotoUpload($photo)
    {
        $filename =
            time() .
            "_" .
            Str::random(10) .
            "." .
            $photo->getClientOriginalExtension();
        $photo->storeAs("public/photos/etudiants", $filename);

        return $filename;
    }

    /**
     * Validation groupée d'inscriptions avec gestion intelligente des paiements
     */
    public function bulkValider(BulkValiderRequest $request)
    {
        $inscriptionIds = $request->input("inscription_ids", []);
        $forceValidation = $request->input("force", false);

        $stats = [
            "validees_direct" => 0,
            "paiements_valides" => 0,
            "validees_apres_paiement" => 0,
            "inscriptions_deja_validees" => 0,
            "ignorees" => [],
            "erreurs" => [],
            "raisons_ignorees" => [
                "sans_paiement" => 0,
                "paiement_non_valide" => 0,
                "classe_pleine" => 0,
                "inscription_existante" => 0,
            ],
        ];

        try {
            DB::beginTransaction();

            foreach ($inscriptionIds as $id) {
                try {
                    $inscription = ESBTPInscription::with([
                        "paiements",
                        "etudiant",
                    ])->find($id);

                    if (!$inscription) {
                        $stats["erreurs"][] = [
                            "id" => $id,
                            "erreur" => "Inscription introuvable",
                        ];

                        continue;
                    }

                    // Skip si déjà validée (status = active ET workflow_step = etudiant_cree)
                    if (
                        $inscription->status === "active" &&
                        $inscription->workflow_step === "etudiant_cree"
                    ) {
                        $stats["inscriptions_deja_validees"]++;

                        continue;
                    }

                    $etudiantNom =
                        $inscription->etudiant->nom .
                        " " .
                        $inscription->etudiant->prenoms;

                    // Cas 1: A déjà un paiement validé ET workflow = en_validation
                    if (
                        $inscription->paiement_validation_id &&
                        $inscription->workflow_step === "en_validation"
                    ) {
                        // Vérifications en amont pour éviter les erreurs

                        // 1. Vérifier le paiement — chercher un paiement validé (ne bloque pas la validation)
                        $paiement = ESBTPPaiement::find(
                            $inscription->paiement_validation_id,
                        );
                        if (!$paiement || $paiement->status !== "validé") {
                            // Chercher un autre paiement validé sur cette inscription
                            $autrePaiementValide = $inscription->paiements->where("status", "validé")->first();
                            if ($autrePaiementValide) {
                                $inscription->update(["paiement_validation_id" => $autrePaiementValide->id]);
                                $paiement = $autrePaiementValide;
                            }
                            // Si aucun paiement validé, on continue quand même la validation (aligné sur valider())
                        }

                        // 2. Vérifier la disponibilité de la classe (sauf si force = true)
                        if (!$forceValidation) {
                            $classAvailability = $this->workflowService->checkClassAvailability(
                                $inscription->classe_id,
                            );
                            if (!$classAvailability["available"]) {
                                $stats["ignorees"][] = [
                                    "id" => $inscription->id,
                                    "etudiant" => $etudiantNom,
                                    "raison" =>
                                        "Classe pleine - " .
                                        $classAvailability["message"],
                                ];
                                $stats["raisons_ignorees"]["classe_pleine"]++;

                                continue;
                            }
                        }

                        // 3. Vérifier inscription active existante
                        $existingInscription = ESBTPInscription::where(
                            "etudiant_id",
                            $inscription->etudiant_id,
                        )
                            ->where(
                                "annee_universitaire_id",
                                $inscription->annee_universitaire_id,
                            )
                            ->where("status", "active")
                            ->where("id", "!=", $inscription->id)
                            ->first();

                        if ($existingInscription) {
                            $stats["ignorees"][] = [
                                "id" => $inscription->id,
                                "etudiant" => $etudiantNom,
                                "raison" =>
                                    'L\'étudiant a déjà une inscription active pour cette année',
                            ];
                            $stats["raisons_ignorees"][
                                "inscription_existante"
                            ]++;

                            continue;
                        }

                        // Toutes les vérifications passées, on peut valider
                        $result = $this->workflowService->convertProspectToStudent(
                            $inscription,
                            "Validation groupée",
                        );

                        if ($result["success"]) {
                            $stats["validees_direct"]++;

                            // Envoyer notification à l'étudiant
                            if (
                                $inscription->etudiant &&
                                $inscription->etudiant->user
                            ) {
                                $notificationService = app(
                                    \App\Services\NotificationService::class,
                                );
                                $notificationService->createNotification(
                                    $inscription->etudiant->user,
                                    "Inscription validée",
                                    "Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.",
                                    "success",
                                    route(
                                        "esbtp.inscriptions.show",
                                        $inscription->id,
                                    ),
                                    auth()->user(),
                                );
                            }

                            // Désactiver les rappels
                            $this->desactiverRappelsInscription(
                                $inscription->id,
                            );
                        } else {
                            // Si malgré tout ça échoue, on ignore au lieu de créer une erreur bloquante
                            $stats["ignorees"][] = [
                                "id" => $id,
                                "etudiant" => $etudiantNom,
                                "raison" => $result["message"],
                            ];
                        }

                        continue;
                    }

                    // Cas 2: A un/des paiement(s) validé(s) mais pas encore en workflow "en_validation"
                    $paiementsValides = $inscription->paiements->where(
                        "status",
                        "validé",
                    );
                    if ($paiementsValides->count() > 0) {
                        $premierPaiement = $paiementsValides->first();

                        // Vérifications en amont

                        // 1. Vérifier la disponibilité de la classe (sauf si force = true)
                        if (!$forceValidation) {
                            $classAvailability = $this->workflowService->checkClassAvailability(
                                $inscription->classe_id,
                            );
                            if (!$classAvailability["available"]) {
                                $stats["ignorees"][] = [
                                    "id" => $inscription->id,
                                    "etudiant" => $etudiantNom,
                                    "raison" =>
                                        "Classe pleine - " .
                                        $classAvailability["message"],
                                ];
                                $stats["raisons_ignorees"]["classe_pleine"]++;

                                continue;
                            }
                        }

                        // 2. Vérifier inscription active existante
                        $existingInscription = ESBTPInscription::where(
                            "etudiant_id",
                            $inscription->etudiant_id,
                        )
                            ->where(
                                "annee_universitaire_id",
                                $inscription->annee_universitaire_id,
                            )
                            ->where("status", "active")
                            ->where("id", "!=", $inscription->id)
                            ->first();

                        if ($existingInscription) {
                            $stats["ignorees"][] = [
                                "id" => $inscription->id,
                                "etudiant" => $etudiantNom,
                                "raison" =>
                                    'L\'étudiant a déjà une inscription active pour cette année',
                            ];
                            $stats["raisons_ignorees"][
                                "inscription_existante"
                            ]++;

                            continue;
                        }

                        // Associer le paiement via le workflow
                        $inscription->update([
                            "paiement_validation_id" => $premierPaiement->id,
                            "workflow_step" => "en_validation",
                        ]);

                        // Enregistrer dans l'historique workflow
                        \App\Models\ESBTPInscriptionWorkflowHistory::createEntry(
                            $inscription->id,
                            $inscription->workflow_step,
                            "en_validation",
                            "paiement_associe",
                            auth()->id(),
                            "Paiement associé lors de validation groupée",
                            ["paiement_id" => $premierPaiement->id],
                        );

                        // Puis valider définitivement
                        $result = $this->workflowService->convertProspectToStudent(
                            $inscription,
                            "Validation groupée",
                        );

                        if ($result["success"]) {
                            $stats["validees_direct"]++;

                            // Notification
                            if (
                                $inscription->etudiant &&
                                $inscription->etudiant->user
                            ) {
                                $notificationService = app(
                                    \App\Services\NotificationService::class,
                                );
                                $notificationService->createNotification(
                                    $inscription->etudiant->user,
                                    "Inscription validée",
                                    "Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.",
                                    "success",
                                    route(
                                        "esbtp.inscriptions.show",
                                        $inscription->id,
                                    ),
                                    auth()->user(),
                                );
                            }

                            // Désactiver les rappels
                            $this->desactiverRappelsInscription(
                                $inscription->id,
                            );
                        } else {
                            // Si ça échoue, on ignore au lieu de créer une erreur
                            $stats["ignorees"][] = [
                                "id" => $id,
                                "etudiant" => $etudiantNom,
                                "raison" => $result["message"],
                            ];
                        }

                        continue;
                    }

                    // Cas 3: A des paiements EN ATTENTE -> ne pas valider automatiquement
                    $paiementsEnAttente = $inscription->paiements->where(
                        "status",
                        "en_attente",
                    );
                    if ($paiementsEnAttente->count() > 0) {
                        $stats["ignorees"][] = [
                            "id" => $inscription->id,
                            "etudiant" => $etudiantNom,
                            "raison" => "Le paiement associe n'est pas encore valide",
                        ];
                        $stats["raisons_ignorees"]["paiement_non_valide"]++;

                        continue;
                    }
                    // Cas 4: Aucun paiement -> ignorer
                    if ($inscription->paiements->count() === 0) {
                        $stats["ignorees"][] = [
                            "id" => $inscription->id,
                            "etudiant" => $etudiantNom,
                            "raison" => "Aucun paiement associe a cette inscription",
                        ];
                        $stats["raisons_ignorees"]["sans_paiement"]++;

                        continue;
                    }

                    // Cas 4: Paiement present -> valider (aligne sur valider())
                    // Vérifier la disponibilité de la classe (sauf si force = true)
                    if (!$forceValidation) {
                        $classAvailability = $this->workflowService->checkClassAvailability(
                            $inscription->classe_id,
                        );
                        if (!$classAvailability["available"]) {
                            $stats["ignorees"][] = [
                                "id" => $inscription->id,
                                "etudiant" => $etudiantNom,
                                "raison" =>
                                    "Classe pleine - " .
                                    $classAvailability["message"],
                            ];
                            $stats["raisons_ignorees"]["classe_pleine"]++;

                            continue;
                        }
                    }

                    // Vérifier inscription active existante
                    $existingInscription = ESBTPInscription::where(
                        "etudiant_id",
                        $inscription->etudiant_id,
                    )
                        ->where(
                            "annee_universitaire_id",
                            $inscription->annee_universitaire_id,
                        )
                        ->where("status", "active")
                        ->where("workflow_step", "etudiant_cree")
                        ->where("id", "!=", $inscription->id)
                        ->first();

                    if ($existingInscription) {
                        $stats["ignorees"][] = [
                            "id" => $inscription->id,
                            "etudiant" => $etudiantNom,
                            "raison" =>
                                'L\'étudiant a déjà une inscription active pour cette année',
                        ];
                        $stats["raisons_ignorees"][
                            "inscription_existante"
                        ]++;

                        continue;
                    }

                    // Sans paiement : utiliser inscriptionService (aligné sur valider() unitaire)
                    $result = $this->inscriptionService->validerInscription(
                        $inscription->id,
                        auth()->id(),
                    );

                    if ($result["success"]) {
                        $stats["validees_direct"]++;

                        if (
                            $inscription->etudiant &&
                            $inscription->etudiant->user
                        ) {
                            $notificationService = app(
                                \App\Services\NotificationService::class,
                            );
                            $notificationService->createNotification(
                                $inscription->etudiant->user,
                                "Inscription validée",
                                "Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.",
                                "success",
                                route(
                                    "esbtp.inscriptions.show",
                                    $inscription->id,
                                ),
                                auth()->user(),
                            );
                        }

                        $this->desactiverRappelsInscription(
                            $inscription->id,
                        );
                    } else {
                        $stats["ignorees"][] = [
                            "id" => $id,
                            "etudiant" => $etudiantNom,
                            "raison" => $result["message"],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error(
                        "Erreur validation inscription bulk #" .
                            $id .
                            ": " .
                            $e->getMessage(),
                    );
                    $stats["erreurs"][] = [
                        "id" => $id,
                        "erreur" => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            $totalValidees =
                $stats["validees_direct"] + $stats["validees_apres_paiement"];
            $totalIgnorees = count($stats["ignorees"]);
            $totalErreurs = count($stats["erreurs"]);
            $totalTraitees =
                count($inscriptionIds) - $stats["inscriptions_deja_validees"];

            Log::info("Validation groupée inscriptions terminée", [
                "user_id" => auth()->id(),
                "total_selectionnees" => count($inscriptionIds),
                "stats" => $stats,
            ]);

            // Construire le message de retour enrichi
            $message = "";
            if ($stats["validees_direct"] > 0) {
                $message .= "{$stats["validees_direct"]} inscription(s) validée(s) directement. ";
            }
            if ($stats["paiements_valides"] > 0) {
                $message .= "{$stats["paiements_valides"]} paiement(s) auto-validé(s). ";
            }
            if ($stats["validees_apres_paiement"] > 0) {
                $message .= "{$stats["validees_apres_paiement"]} inscription(s) validée(s) après validation du paiement. ";
            }
            if ($stats["inscriptions_deja_validees"] > 0) {
                $message .= "{$stats["inscriptions_deja_validees"]} inscription(s) déjà validée(s) (ignorée(s)). ";
            }

            // Détail des inscriptions ignorées par raison
            if (count($stats["ignorees"]) > 0) {
                $message .=
                    count($stats["ignorees"]) . " inscription(s) ignorée(s) : ";
                $raisons = [];
                if ($stats["raisons_ignorees"]["sans_paiement"] > 0) {
                    $raisons[] = "{$stats["raisons_ignorees"]["sans_paiement"]} sans paiement";
                }
                if ($stats["raisons_ignorees"]["paiement_non_valide"] > 0) {
                    $raisons[] = "{$stats["raisons_ignorees"]["paiement_non_valide"]} paiement non validé";
                }
                if ($stats["raisons_ignorees"]["classe_pleine"] > 0) {
                    $raisons[] = "{$stats["raisons_ignorees"]["classe_pleine"]} classe pleine";
                }
                if ($stats["raisons_ignorees"]["inscription_existante"] > 0) {
                    $raisons[] = "{$stats["raisons_ignorees"]["inscription_existante"]} inscription existante";
                }
                $message .= implode(", ", $raisons) . ". ";
            }

            if (count($stats["erreurs"]) > 0) {
                $message .=
                    count($stats["erreurs"]) . " erreur(s) techniques. ";
            }

            // Stocker les détails des erreurs et inscriptions ignorées en session pour affichage visuel
            $inscriptionsAvecProblemes = [];

            // Ajouter les erreurs avec leur message
            if (is_array($stats["erreurs"])) {
                foreach ($stats["erreurs"] as $erreur) {
                    if (
                        is_array($erreur) &&
                        isset($erreur["id"]) &&
                        isset($erreur["erreur"])
                    ) {
                        $inscriptionsAvecProblemes[$erreur["id"]] = [
                            "type" => "error",
                            "message" => $erreur["erreur"],
                        ];
                    }
                }
            }

            // Ajouter les ignorées avec leur raison
            if (is_array($stats["ignorees"])) {
                foreach ($stats["ignorees"] as $ignoree) {
                    if (
                        is_array($ignoree) &&
                        isset($ignoree["id"]) &&
                        isset($ignoree["raison"])
                    ) {
                        $inscriptionsAvecProblemes[$ignoree["id"]] = [
                            "type" => "warning",
                            "message" => $ignoree["raison"],
                        ];
                    }
                }
            }

            // Debug: Log pour vérifier les données
            Log::info("Inscriptions avec problèmes:", [
                "problemes" => $inscriptionsAvecProblemes,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    "success" => true,
                    "message" =>
                        $message ?: 'Aucune inscription n\'a été traitée.',
                    "stats" => $stats,
                    "inscriptions_problemes" => $inscriptionsAvecProblemes,
                ]);
            }

            return redirect()
                ->back()
                ->with(
                    "success",
                    $message ?: 'Aucune inscription n\'a été traitée.',
                )
                ->with("inscriptions_problemes", $inscriptionsAvecProblemes);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                "Erreur validation groupée inscriptions: " . $e->getMessage(),
            );

            if ($request->ajax()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Erreur lors de la validation groupée: " .
                            $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la validation groupée: " . $e->getMessage(),
                );
        }
    }

    /**
     * Changer la classe d'une inscription rapidement (depuis modal AJAX)
     */
    public function changerClasseRapide(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        $request->validate([
            "nouvelle_classe_id" => "required|integer|exists:esbtp_classes,id",
            "affectation_status" => "nullable|in:affecté,réaffecté,non_affecté",
        ]);

        try {
            DB::beginTransaction();

            $nouvelleClasseId = $request->input("nouvelle_classe_id");
            $ancienneClasseId = $inscription->classe_id;

            // Vérifier la disponibilité de la nouvelle classe
            $availability = $this->workflowService->checkClassAvailability(
                $nouvelleClasseId,
                $inscription->annee_universitaire_id,
            );

            if (!$availability["available"]) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => $availability["message"],
                    ],
                    400,
                );
            }

            // Vérifier que ce n'est pas la même classe (sauf si ancienne est null = première affectation)
            if ($ancienneClasseId && $ancienneClasseId == $nouvelleClasseId) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            'La nouvelle classe est identique à l\'ancienne.',
                    ],
                    400,
                );
            }

            // Archiver les notes/résultats/bulletins de l'ancienne classe
            if ($ancienneClasseId) {
                $etudiantId = $inscription->etudiant_id;
                $now = now();
                ESBTPNote::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $ancienneClasseId)
                    ->update(['archived_at' => $now]);
                ESBTPResultat::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $ancienneClasseId)
                    ->update(['archived_at' => $now]);
                ESBTPBulletin::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $ancienneClasseId)
                    ->update(['archived_at' => $now]);
            }

            // Restaurer les notes/résultats/bulletins archivés si l'étudiant revient dans la nouvelle classe
            $etudiantId = $inscription->etudiant_id;
            ESBTPNote::withoutGlobalScope('not_archived')
                ->where('etudiant_id', $etudiantId)
                ->where('classe_id', $nouvelleClasseId)
                ->whereNotNull('archived_at')
                ->update(['archived_at' => null]);
            ESBTPResultat::withoutGlobalScope('not_archived')
                ->where('etudiant_id', $etudiantId)
                ->where('classe_id', $nouvelleClasseId)
                ->whereNotNull('archived_at')
                ->update(['archived_at' => null]);
            ESBTPBulletin::withoutGlobalScope('not_archived')
                ->where('etudiant_id', $etudiantId)
                ->where('classe_id', $nouvelleClasseId)
                ->whereNotNull('archived_at')
                ->update(['archived_at' => null]);

            // Mettre à jour la classe et le statut d'affectation
            $affectationStatus = $request->input("affectation_status", $ancienneClasseId ? "réaffecté" : "affecté");
            $inscription->update([
                "classe_id" => $nouvelleClasseId,
                "affectation_status" => $affectationStatus,
                "updated_at" => now(),
            ]);

            // Régénérer les souscriptions de frais avec la nouvelle classe/statut
            $this->regenererFraisInscription($inscription);

            DB::commit();

            Log::info("Changement de classe rapide effectué", [
                "inscription_id" => $inscription->id,
                "ancienne_classe_id" => $ancienneClasseId,
                "nouvelle_classe_id" => $nouvelleClasseId,
                "affectation_status" => $affectationStatus,
                "frais_regeneres" => true,
                "user_id" => auth()->id(),
            ]);

            // Charger les relations pour retourner les infos
            $inscription->load("classe");

            return response()->json([
                "success" => true,
                "message" => $ancienneClasseId
                    ? "Classe changée avec succès. Les frais ont été recalculés."
                    : "Étudiant affecté à la classe avec succès. Les frais ont été générés.",
                "inscription" => [
                    "id" => $inscription->id,
                    "affectation_status" => $affectationStatus,
                    "nouvelle_classe" => [
                        "id" => $inscription->classe->id,
                        "name" => $inscription->classe->name,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erreur changerClasseRapide: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "nouvelle_classe_id" => $request->input("nouvelle_classe_id"),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors du changement de classe: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Rafraîchir une ligne d'inscription spécifique (AJAX pour mise à jour partielle)
     */
    public function refreshLigne(ESBTPInscription $inscription)
    {
        try {
            // Charger toutes les relations nécessaires
            $inscription->load([
                "etudiant",
                "classe",
                "filiere",
                "niveau",
                "anneeUniversitaire",
                "paiements",
            ]);

            // Recalculer les problèmes pour cette inscription (comme dans bulkValider)
            $inscriptionsProblemes = [];

            // Si statut pending, vérifier les problèmes potentiels
            if ($inscription->workflow_step !== 'etudiant_cree') {
                // Vérifier paiement
                $paiement = $inscription
                    ->paiements()
                    ->whereIn("status", ["validé", "en_attente"])
                    ->first();

                if (!$paiement) {
                    $inscriptionsProblemes[$inscription->id] = [
                        "type" => "warning",
                        "message" =>
                            "Aucun paiement associé à cette inscription",
                    ];
                } elseif ($paiement->status == "en_attente") {
                    $inscriptionsProblemes[$inscription->id] = [
                        "type" => "warning",
                        "message" => 'Le paiement n\'est pas encore validé',
                    ];
                } else {
                    // Vérifier disponibilité classe
                    $classeId = $inscription->classe_id;
                    $anneeId = $inscription->annee_universitaire_id;

                    if ($classeId && $anneeId) {
                        $availability = $this->workflowService->checkClassAvailability(
                            $classeId,
                            $anneeId,
                        );

                        if (!$availability["available"]) {
                            $inscriptionsProblemes[$inscription->id] = [
                                "type" => "warning",
                                "message" =>
                                    "Classe pleine - " .
                                    $availability["message"],
                            ];
                        }
                    }
                }
            }

            // Mettre les problèmes en session flash pour cette requête uniquement
            session()->flash("inscriptions_problemes", $inscriptionsProblemes);

            $context = request()->query("context", "index");
            $rowView =
                $context === "administration"
                    ? "esbtp.inscriptions.partials.administration-ligne"
                    : "esbtp.inscriptions.partials.ligne-inscription";

            // Rendu de la partial ligne-inscription
            $html = view($rowView, [
                "inscription" => $inscription,
            ])->render();

            Log::info("Ligne inscription rafraîchie avec succès", [
                "inscription_id" => $inscription->id,
                "user_id" => auth()->id(),
                "has_problem" => isset(
                    $inscriptionsProblemes[$inscription->id],
                ),
            ]);

            return response()->json([
                "success" => true,
                "html" => $html,
                "inscription_id" => $inscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur refreshLigne: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors du rafraîchissement de la ligne: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Vérifier les limites du paywall pour les inscriptions
     */
    private function checkPaywallLimitsForInscription()
    {
        $isPaywallActive = \App\Models\ESBTPSystemSetting::getValue(
            "paywall_active",
            false,
        );

        if (!$isPaywallActive) {
            return false;
        }

        $maxInscriptionsPerYear = \App\Models\ESBTPSystemSetting::getValue(
            "paywall_max_inscriptions_per_year",
            500,
        );

        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where(
            "is_current",
            1,
        )->first();

        if (!$anneeCourante) {
            return false;
        }

        $inscriptionsActuelles = \App\Models\ESBTPInscription::where(
            "annee_universitaire_id",
            $anneeCourante->id,
        )
            ->where("status", "active")
            ->count();

        if ($inscriptionsActuelles >= $maxInscriptionsPerYear) {
            \Log::warning('Paywall: Limite d\'inscriptions atteinte', [
                "inscriptions_actuelles" => $inscriptionsActuelles,
                "limite_configuree" => $maxInscriptionsPerYear,
                "annee_courante" => $anneeCourante->nom,
                "user_id" => auth()->id(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Régénérer les frais obligatoires après changement de classe/filière/niveau
     */
    private function regenererFraisInscription(\App\Models\ESBTPInscription $inscription)
    {
        \Log::info("Régénération des frais pour inscription", [
            "inscription_id" => $inscription->id,
            "filiere_id" => $inscription->filiere_id,
            "niveau_id" => $inscription->niveau_id,
            "classe_id" => $inscription->classe_id,
        ]);

        $categoriesObligatoires = ESBTPFraisCategory::where("is_mandatory", true)
            ->where("is_active", true)
            ->orderBy("sort_order")
            ->get();

        // Pré-charger toutes les configurations en une seule requête (évite N+1)
        $configurations = ESBTPFraisConfiguration::where("is_active", true)
            ->whereIn("frais_category_id", $categoriesObligatoires->pluck("id"))
            ->get()
            ->groupBy(fn($config) => "{$config->frais_category_id}_{$config->filiere_id}_{$config->niveau_id}");

        $affectationStatus = $inscription->affectation_status ?? \App\Models\ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        foreach ($categoriesObligatoires as $category) {
            $configKey = "{$category->id}_{$inscription->filiere_id}_{$inscription->niveau_id}";
            $fraisConfig = $configurations->get($configKey, collect())->first();

            if ($fraisConfig) {
                $montant = $fraisConfig->getMontantByStatus($affectationStatus);

                ESBTPFraisSubscription::updateOrCreate(
                    [
                        "inscription_id" => $inscription->id,
                        "frais_category_id" => $category->id,
                    ],
                    [
                        "selected_option_id" => null,
                        "amount" => $montant,
                        "is_active" => true,
                        "subscribed_at" => now(),
                        "created_by" => \Auth::id(),
                        "notes" => "Régénéré automatiquement après changement de classe/filière/niveau",
                    ],
                );
            }
        }
    }

    /**
     * Formulaire de pré-inscription simplifié (caissier)
     */
    public function createPreInscription()
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $classes = ESBTPClasse::with(['filiere', 'niveau'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('esbtp.inscriptions.create-pre-inscription', compact(
            'anneeCourante', 'classes'
        ));
    }

    /**
     * Analyse académique d'un étudiant pour réinscription caissier (AJAX)
     */
    public function analyseEtudiant(Request $request, $etudiantId)
    {
        try {
            $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            // Inscription active de l'année précédente
            $inscriptionActive = $etudiant->inscriptions()
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->latest()
                ->first();

            if (!$inscriptionActive) {
                return response()->json([
                    'success' => true,
                    'has_analysis' => false,
                    'message' => 'Aucune inscription active trouvée pour cet étudiant.',
                ]);
            }

            // Analyse académique
            $reinscriptionService = app(\App\Services\ReeinscriptionService::class);
            $anneeAnalyse = $inscriptionActive->anneeUniversitaire?->name ?? ($anneeCourante?->name ?? date('Y'));

            $analysis = null;
            try {
                $analysis = $reinscriptionService->analyserSituationEtudiant($etudiantId, $anneeAnalyse);
            } catch (\Exception $e) {
                // Pas de notes = pas d'analyse possible
            }

            // Calcul du solde (relicat)
            $totalAttendu = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscriptionActive->id)
                ->where('is_active', true)->sum('amount');
            $totalPaye = $inscriptionActive->paiements()->where('status', 'validé')->sum('montant');
            $soldeRestant = max(0, $totalAttendu - $totalPaye);

            // Classes proposées
            $decision = $analysis['decision'] ?? 'passage';
            $classesProposees = [];
            try {
                $classesProposees = $reinscriptionService->proposerNouvellesClasses($etudiantId, $decision);
            } catch (\Exception $e) {
                // Fallback : toutes les classes actives
            }

            return response()->json([
                'success' => true,
                'has_analysis' => true,
                'decision' => $decision,
                'moyenne_generale' => $analysis['moyenne_generale'] ?? null,
                'matieres_echouees' => count($analysis['matieres_echouees'] ?? []),
                'classe_actuelle' => $inscriptionActive->classe?->name ?? '—',
                'solde_restant' => $soldeRestant,
                'solde_status' => $soldeRestant <= 0 ? 'solde' : 'impaye',
                'classes_proposees' => collect($classesProposees)->map(fn($c) => [
                    'id' => $c->id ?? $c['id'] ?? null,
                    'name' => $c->name ?? $c['name'] ?? '',
                    'filiere' => $c->filiere->name ?? $c['filiere']['name'] ?? '',
                    'niveau' => $c->niveau->name ?? $c['niveau']['name'] ?? '',
                ])->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Recherche d'étudiants existants pour pré-inscription (AJAX)
     */
    public function searchEtudiants(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $etudiants = \App\Models\ESBTPEtudiant::where(function ($query) use ($q) {
                $query->where('nom', 'like', "%{$q}%")
                      ->orWhere('prenoms', 'like', "%{$q}%")
                      ->orWhere('matricule', 'like', "%{$q}%")
                      ->orWhere('telephone', 'like', "%{$q}%");
            })
            ->where('matricule', 'NOT LIKE', 'PRE-%')
            ->with(['inscriptions' => function ($query) {
                $query->latest()->with('classe')->take(1);
            }])
            ->orderBy('nom')
            ->take(10)
            ->get()
            ->map(function ($e) {
                $derniereInscription = $e->inscriptions->first();
                return [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'prenoms' => $e->prenoms,
                    'matricule' => $e->matricule,
                    'telephone' => $e->telephone,
                    'derniere_classe' => $derniereInscription?->classe?->name ?? '—',
                ];
            });

        return response()->json($etudiants);
    }

    /**
     * Générer un matricule unique pour pré-inscription
     */
    private function generatePreInscriptionMatricule(): string
    {
        do {
            $matricule = 'PRE-' . strtoupper(Str::random(8));
        } while (\App\Models\ESBTPEtudiant::where('matricule', $matricule)->exists());

        return $matricule;
    }

    /**
     * Enregistrer une pré-inscription (caissier)
     * Crée un étudiant minimal + inscription prospect + paiement optionnel
     */
    public function storePreInscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'etudiant_existant_id' => 'nullable|integer|exists:esbtp_etudiants,id',
            'nom' => 'required_without:etudiant_existant_id|string|max:100',
            'prenoms' => 'required_without:etudiant_existant_id|string|max:100',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'telephone' => 'nullable|string|max:20',
            'frais' => 'nullable|array',
            'frais.*.variant_id' => 'nullable|string',
            'frais.*.amount' => 'nullable|numeric|min:0',
            'paiement_categories' => 'nullable|array',
            'paiement_categories.*' => 'integer',
            'paiement_montants' => 'nullable|array',
            'paiement_montants.*' => 'numeric|min:0',
            'mode_paiement' => 'nullable|string|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
        ], [
            'nom.required' => 'Le nom est obligatoire',
            'prenoms.required' => 'Le(s) prénom(s) est/sont obligatoire(s)',
            'classe_id.required' => 'Veuillez sélectionner une classe',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->firstOrFail();

            // 1. Étudiant existant (réinscription) ou nouveau (première inscription)
            $isReinscription = !empty($request->etudiant_existant_id);

            if ($isReinscription) {
                $etudiant = \App\Models\ESBTPEtudiant::findOrFail($request->etudiant_existant_id);
            } else {
                $etudiant = \App\Models\ESBTPEtudiant::create([
                    'nom' => $request->nom,
                    'prenoms' => $request->prenoms,
                    'telephone' => $request->telephone,
                    'statut' => 'inactif',
                    'matricule' => $this->generatePreInscriptionMatricule(),
                    'created_by' => Auth::id(),
                ]);
            }

            // 2. Créer l'inscription en mode prospect
            $inscription = ESBTPInscription::create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'annee_universitaire_id' => $anneeCourante->id,
                'date_inscription' => now()->format('Y-m-d'),
                'status' => 'en_attente',
                'workflow_step' => 'prospect',
                'type_inscription' => $isReinscription ? 'réinscription' : 'première_inscription',
                'affectation_status' => $isReinscription ? 'affecté' : null,
                'montant_scolarite' => 0,
                'frais_inscription' => 0,
                'created_by' => Auth::id(),
            ]);

            // 3. Créer les souscriptions de frais
            $fraisData = $request->input('frais', []);
            $totalSouscrit = 0;
            foreach ($fraisData as $categoryId => $fraisInfo) {
                $amount = floatval($fraisInfo['amount'] ?? 0);
                if ($amount <= 0) continue;

                $variantId = ($fraisInfo['variant_id'] ?? null);
                $selectedOptionId = ($variantId !== 'default' && $variantId !== null) ? $variantId : null;

                ESBTPFraisSubscription::create([
                    'inscription_id' => $inscription->id,
                    'frais_category_id' => $categoryId,
                    'selected_option_id' => $selectedOptionId,
                    'amount' => $amount,
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                $totalSouscrit += $amount;
            }

            // 4. Créer les paiements par catégorie cochée (montant partiel possible)
            $paiementCategories = $request->input('paiement_categories', []);
            $paiementMontants = $request->input('paiement_montants', []);
            $totalPaye = 0;
            $firstPaiement = null;

            foreach ($paiementCategories as $categoryId) {
                // Utiliser le montant partiel saisi, sinon le montant total de la catégorie
                $maxAmount = floatval($fraisData[$categoryId]['amount'] ?? 0);
                $amount = isset($paiementMontants[$categoryId]) ? floatval($paiementMontants[$categoryId]) : $maxAmount;
                $amount = min($amount, $maxAmount); // Ne pas dépasser le montant dû
                if ($amount <= 0) continue;

                // Récupérer le nom de la catégorie pour le motif
                $categoryName = \App\Models\ESBTPFraisCategory::find($categoryId)?->name ?? 'Paiement';

                $paiement = ESBTPPaiement::create([
                    'inscription_id' => $inscription->id,
                    'etudiant_id' => $etudiant->id,
                    'frais_category_id' => $categoryId,
                    'montant' => $amount,
                    'motif' => $categoryName,
                    'numero_recu' => 'REC-' . strtoupper(Str::random(8)),
                    'date_paiement' => now(),
                    'mode_paiement' => $request->mode_paiement ?? 'especes',
                    'reference_paiement' => $request->reference_paiement,
                    'type_paiement' => 'inscription',
                    'status' => 'validé',
                    'annee_universitaire_id' => $anneeCourante->id,
                    'validateur_id' => Auth::id(),
                    'created_by' => Auth::id(),
                ]);

                $totalPaye += $amount;
                if (!$firstPaiement) $firstPaiement = $paiement;
            }

            if ($firstPaiement) {
                $inscription->update(['paiement_validation_id' => $firstPaiement->id]);
            }

            DB::commit();

            $message = "Pré-inscription créée avec succès pour {$request->nom} {$request->prenoms}.";
            if ($totalSouscrit > 0) {
                $message .= " Frais souscrits : " . number_format($totalSouscrit, 0, ',', ' ') . " FCFA.";
            }
            if ($totalPaye > 0) {
                $message .= " Paiement encaissé : " . number_format($totalPaye, 0, ',', ' ') . " FCFA.";
            }

            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur pré-inscription caissier: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la pré-inscription: ' . $e->getMessage())
                ->withInput();
        }
    }

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
