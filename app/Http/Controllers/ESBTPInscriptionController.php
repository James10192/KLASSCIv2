<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Models\Setting;
use App\Services\ComptabiliteService;
use App\Services\ESBTPInscriptionService;
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
    protected $searchService;

    public function __construct(
        ESBTPInscriptionService $inscriptionService,
        ComptabiliteService $comptabiliteService,
        InscriptionWorkflowService $workflowService,
        \App\Services\InscriptionSearchService $searchService,
    ) {
        $this->inscriptionService = $inscriptionService;
        $this->comptabiliteService = $comptabiliteService;
        $this->workflowService = $workflowService;
        $this->searchService = $searchService;
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
    public function index(Request $request)
    {
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
            $anneeEnCours = ESBTPAnneeUniversitaire::where("is_current", true)->first();
            if ($anneeEnCours) {
                $baseQuery->where("annee_universitaire_id", $anneeEnCours->id);
            }
        }

        if ($status && $status !== "all") {
            if ($status === "non_validee") {
                $baseQuery->where(function ($q) {
                    $q->where("status", "en_attente")->orWhere(function ($subQ) {
                        $subQ->where("status", "active")
                            ->where(function ($wq) {
                                $wq->whereIn("workflow_step", ["prospect", "documents_complets", "en_validation"])
                                    ->orWhereNull("workflow_step");
                            });
                    });
                });
            } else {
                $baseQuery->where("status", $status);
            }
        }

        $perPage = 15;

        if ($search) {
            $inscriptions = $this->searchService->search(
                $baseQuery,
                $search,
                $perPage,
                $request->url(),
                $request->query(),
            );
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

        if ($request->ajax()) {
            return response()->json([
                "html" => view("esbtp.inscriptions.partials.results", [
                    "inscriptions" => $inscriptions,
                ])->render(),
                "url" => $request->fullUrl(),
            ]);
        }

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
        \App\Http\Requests\Inscription\StoreInscriptionRequest $request,
        StudentDuplicateDetector $duplicateDetector,
    ) {

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

        // Vérification des places disponibles dans la classe
        if ($request->filled('classe_id')) {
            $classe = \App\Models\ESBTPClasse::find($request->input('classe_id'));
            if ($classe && $classe->places_disponibles <= 0) {
                return redirect()
                    ->back()
                    ->with('error', 'La classe sélectionnée est complète. Veuillez choisir une autre classe.')
                    ->withInput();
            }
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

        try {
            // Récupérer les informations complètes de la classe sélectionnée
            $classe = ESBTPClasse::with([
                "filiere",
                "niveau",
                "annee",
            ])->findOrFail($request->classe_id);

            // Préparer les données via le service
            $photoFilename = $request->hasFile('photo')
                ? $this->handlePhotoUpload($request->file('photo'))
                : null;

            $etudiantData = $this->inscriptionService->prepareEtudiantData(
                $request->validated(),
                $photoFilename,
            );

            $affectationStatus = $request->input(
                "affectation_status",
                ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            );

            $inscriptionData = $this->inscriptionService->prepareInscriptionData(
                $classe,
                $request->validated(),
            );

            // Ajouter filière/niveau à l'étudiant depuis la classe
            if ($classe->filiere_id) {
                $etudiantData["filiere_id"] = $classe->filiere_id;
            }
            if ($classe->niveau_etude_id) {
                $etudiantData["niveau_etude_id"] = $classe->niveau_etude_id;
            }

            // Préparer les données des parents
            $parentsData = $request->has('parents')
                ? $this->inscriptionService->prepareParentsData($request->input('parents'))
                : [];

            // Préparer les frais optionnels
            $selectedOptionals = $this->inscriptionService->prepareFraisOptionals(
                $request->input('frais', []),
            );

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
        $this->authorize('view', $inscription);

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
        $affectationStatus = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

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

            // Priorité: souscription > règle > défaut catégorie
            if ($subscription) {
                $montantAttendu = $subscription->amount;
                $isConfigured = true;
            } elseif ($rule) {
                $montantAttendu = $rule->getMontantByStatus($affectationStatus);
                $isConfigured = true;
            } else {
                $montantAttendu = $category->default_amount;
                $isConfigured = false;
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

        $canViewFinancials = auth()->user()->can('viewFinancials', $inscription);

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
                "canViewFinancials",
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

            $data = $request->only([
                'filiere_id', 'niveau_id', 'classe_id', 'date_inscription',
                'type_inscription', 'montant_scolarite', 'frais_inscription',
                'observations', 'status', 'affectation_status',
                'est_transfert', 'etablissement_origine',
            ]);

            // Stocker les anciennes valeurs pour détecter les changements
            $ancienneFiliere = $inscription->filiere_id;
            $ancienNiveau = $inscription->niveau_id;
            $ancienneClasse = $inscription->classe_id;
            $ancienAffectationStatus = $inscription->affectation_status;

            if (
                $inscription->status === "active" &&
                !Auth::user()->can("inscriptions.manage")
            ) {
                // Empêcher la modification de la filière, niveau et classe pour les inscriptions actives (sauf utilisateurs autorisés)
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

            if (! $result['success']) {
                throw new \Exception($result['message']);
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

        try {
            DB::beginTransaction();

            $stats = $this->inscriptionService->processBulkValidation(
                $inscriptionIds,
                $forceValidation,
                $this->workflowService,
                auth()->id(),
            );

            DB::commit();

            $message = $this->inscriptionService->buildBulkValidationMessage($stats);
            $inscriptionsAvecProblemes = $this->inscriptionService->extractBulkProblems($stats);

            if ($request->ajax()) {
                return response()->json([
                    "success" => true,
                    "message" => $message ?: 'Aucune inscription n\'a été traitée.',
                    "stats" => $stats,
                    "inscriptions_problemes" => $inscriptionsAvecProblemes,
                ]);
            }

            return redirect()
                ->back()
                ->with("success", $message ?: 'Aucune inscription n\'a été traitée.')
                ->with("inscriptions_problemes", $inscriptionsAvecProblemes);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur validation groupée inscriptions: " . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Erreur lors de la validation groupée: " . $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with("error", "Erreur lors de la validation groupée: " . $e->getMessage());
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

            $result = $this->inscriptionService->changerClasse(
                $inscription,
                $request->input("nouvelle_classe_id"),
                $request->input("affectation_status"),
                $this->workflowService,
            );

            if (!$result['success']) {
                DB::rollBack();
                return response()->json(
                    ['success' => false, 'message' => $result['message']],
                    400,
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'inscription' => $result['data'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erreur changerClasseRapide: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "nouvelle_classe_id" => $request->input("nouvelle_classe_id"),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Erreur lors du changement de classe: " . $e->getMessage(),
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

            return response()->json([
                "success" => true,
                "html" => $html,
                "inscription_id" => $inscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur refreshLigne: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "trace" => config('app.debug') ? $e->getTraceAsString() : null,
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
     * Régénérer les frais obligatoires après changement de classe/filière/niveau.
     * Délègue au service.
     */
    private function regenererFraisInscription(\App\Models\ESBTPInscription $inscription)
    {
        $this->inscriptionService->regenererFraisInscription($inscription);
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
    public function storePreInscription(\App\Http\Requests\Inscription\StorePreInscriptionRequest $request)
    {
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
                'affectation_status' => $isReinscription ? ESBTPInscription::DEFAULT_AFFECTATION_STATUS : null,
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
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la pré-inscription: ' . $e->getMessage())
                ->withInput();
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

    /**
     * Afficher la page de gestion des inscriptions sous réserve.
     */
    public function sousReserveIndex(Request $request)
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $annees = ESBTPAnneeUniversitaire::where('is_active', true)
            ->orderByDesc('start_date')
            ->get();

        $anneeFilterId = $request->input('annee_id', $anneeEnCours?->id);

        $inscriptions = ESBTPInscription::with([
                'etudiant', 'classe', 'filiere', 'niveauEtude', 'anneeUniversitaire', 'paiements'
            ])
            ->where('is_sous_reserve', true)
            ->when($anneeFilterId, fn($q) => $q->where('annee_universitaire_id', $anneeFilterId))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('esbtp.inscriptions.sous-reserve', compact(
            'inscriptions', 'annees', 'anneeEnCours', 'anneeFilterId'
        ));
    }

    /**
     * Lever la réserve d'une inscription individuelle.
     */
    public function leverReserve(ESBTPInscription $inscription)
    {
        if (!$inscription->is_sous_reserve) {
            return redirect()->back()->with('info', 'Cette inscription n\'est pas sous réserve.');
        }

        $inscription->leverReserve();

        return redirect()->back()->with('success',
            'La réserve a été levée pour l\'inscription de ' .
            ($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? '') . '.'
        );
    }

    /**
     * Lever les réserves en bulk pour les inscriptions sélectionnées.
     */
    public function leverReservesBulk(Request $request)
    {
        $ids = $request->input('inscription_ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Aucune inscription sélectionnée.');
        }

        $count = ESBTPInscription::whereIn('id', $ids)
            ->where('is_sous_reserve', true)
            ->update(['is_sous_reserve' => false, 'condition_reserve' => null]);

        return redirect()->back()->with('success',
            $count . ' inscription(s) ont été confirmée(s). La réserve a été levée.'
        );
    }
}
