<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
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

class ESBTPInscriptionApiController extends Controller
{
    private const DUPLICATE_BLOCKING_SCORE = 55;

    private $inscriptionService;
    private $comptabiliteService;
    private $workflowService;

    public function __construct(
        \App\Services\ESBTPInscriptionService $inscriptionService,
        \App\Services\ComptabiliteService $comptabiliteService,
        \App\Services\InscriptionWorkflowService $workflowService,
    ) {
        $this->inscriptionService = $inscriptionService;
        $this->comptabiliteService = $comptabiliteService;
        $this->workflowService = $workflowService;
        $this->middleware('auth');
    }

    /**
     * Obtenir les classes disponibles pour une filière, un niveau et une année donnés.
     */
    public function getClasses(Request $request)
    {
        $filiereId = $request->input("filiere_id");
        $niveauId =
            $request->input("niveau_id") ?? $request->input("niveau_etude_id");
        $anneeId =
            $request->input("annee_id") ??
            $request->input("annee_universitaire_id");
        $formationId = $request->input("formation_id");

        // Ajouter des logs pour debug
        \Illuminate\Support\Facades\Log::info(
            "Récupération des classes (Inscription)",
            [
                "filiere_id" => $filiereId,
                "niveau_id" => $niveauId,
                "annee_id" => $anneeId,
                "formation_id" => $formationId,
                "request" => $request->all(),
            ],
        );

        $query = ESBTPClasse::select(
            "esbtp_classes.*",
            "f.name as filiere_name",
            "n.name as niveau_name",
            "a.name as annee_name",
        )
            ->leftJoin(
                "esbtp_filieres as f",
                "esbtp_classes.filiere_id",
                "=",
                "f.id",
            )
            ->leftJoin(
                "esbtp_niveau_etudes as n",
                "esbtp_classes.niveau_etude_id",
                "=",
                "n.id",
            )
            ->leftJoin(
                "esbtp_annee_universitaires as a",
                "esbtp_classes.annee_universitaire_id",
                "=",
                "a.id",
            )
            ->where("esbtp_classes.is_active", true);

        // Appliquer les filtres seulement s'ils sont fournis
        if ($filiereId) {
            $query->where("esbtp_classes.filiere_id", $filiereId);
        }

        if ($niveauId) {
            $query->where("esbtp_classes.niveau_etude_id", $niveauId);
        }

        if ($anneeId) {
            $query->where("esbtp_classes.annee_universitaire_id", $anneeId);
        }

        if ($formationId) {
            $query->where("esbtp_classes.formation_id", $formationId);
        }

        // Log pour vérifier la requête SQL générée
        \Illuminate\Support\Facades\Log::info(
            "Requête SQL pour les classes (Inscription)",
            [
                "sql" => $query->toSql(),
                "bindings" => $query->getBindings(),
            ],
        );

        $classes = $query->get();

        // Log pour vérifier les résultats
        \Illuminate\Support\Facades\Log::info(
            "Classes trouvées (Inscription)",
            [
                "count" => $classes->count(),
                "first_few" => $classes->take(3),
            ],
        );

        return response()->json($classes);
    }


    /**
     * Vérifier si une classe nécessite la confirmation de transfert d'établissement
     * (classes de 2ème année ou plus)
     */
    public function checkTransfert($classeId)
    {
        try {
            $classe = ESBTPClasse::with("niveau")->findOrFail($classeId);

            // Hiérarchie des niveaux (même logique que ReeinscriptionService)
            $hierarchie = [
                "1A" => 1,
                "2A" => 2,
                "L1" => 3,
                "L2" => 4,
                "L3" => 5,
                "M1" => 6,
                "M2" => 7,
            ];

            $niveauCode = $classe->niveau->code ?? "";
            $ordreNiveau = $hierarchie[$niveauCode] ?? 0;

            // Si le niveau est > 1 (donc 2A, L1, L2, L3, M1, M2), c'est potentiellement un transfert
            $necessiteConfirmation = $ordreNiveau > 1;

            return response()->json([
                "success" => true,
                "necessite_confirmation" => $necessiteConfirmation,
                "niveau_code" => $niveauCode,
                "niveau_nom" => $classe->niveau->name ?? "N/A",
                "classe_nom" => $classe->name,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification de transfert", [
                "classe_id" => $classeId,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Erreur lors de la vérification",
                ],
                500,
            );
        }
    }


    /**
     * API pour rechercher les parents existants
     */
    public function searchParents(Request $request)
    {
        try {
            $search = $request->input("search", "");

            $query = ESBTPParent::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where("nom", "like", "%{$search}%")
                        ->orWhere("prenoms", "like", "%{$search}%")
                        ->orWhere("telephone", "like", "%{$search}%")
                        ->orWhere("email", "like", "%{$search}%");
                });
            }

            $parents = $query
                ->select(
                    "id",
                    "nom",
                    "prenoms",
                    "telephone",
                    "email",
                    "profession",
                )
                ->orderBy("nom")
                ->limit(50)
                ->get();

            return response()->json([
                "success" => true,
                "parents" => $parents,
            ]);
        } catch (\Exception $e) {
            \Log::error(
                "Erreur lors de la recherche de parents: " . $e->getMessage(),
            );

            return response()->json(
                [
                    "success" => false,
                    "message" => "Erreur lors de la recherche",
                    "parents" => [],
                ],
                500,
            );
        }
    }


    /**
     * Récupérer les frais applicables pour une classe donnée
     * Architecture corrigée utilisant ESBTPFraisOption avec distinction class-based vs global
     */
    public function getFraisByClasse($classeId, Request $request)
    {
        try {
            $classe = ESBTPClasse::with([
                "filiere",
                "niveau",
                "annee",
            ])->findOrFail($classeId);
            $affectationStatus = $request->get("affectation_status", "affecté");

            \Log::info("getFraisByClasse appelé", [
                "classe_id" => $classeId,
                "filiere_id" => $classe->filiere_id,
                "niveau_etude_id" => $classe->niveau_etude_id,
                "annee_universitaire_id" => $classe->annee_universitaire_id,
            ]);

            // Récupérer TOUTES les catégories de frais actives
            $allCategories = ESBTPFraisCategory::where("is_active", true)
                ->orderBy("sort_order")
                ->get();

            $fraisData = [];
            $hasUnconfiguredFees = false;

            foreach ($allCategories as $category) {
                \Log::info("Traitement catégorie", [
                    "category_id" => $category->id,
                    "category_name" => $category->name,
                    "category_type" => $category->category_type,
                    "is_mandatory" => $category->is_mandatory,
                ]);

                $defaultAmount = $category->default_amount;
                $isConfigured = false;
                $configurationType = "default";
                $options = collect();

                if ($category->is_mandatory) {
                    // FRAIS OBLIGATOIRES : Recherche configuration par classe

                    // 1. Chercher une configuration spécifique pour cette filière/niveau
                    $configuration = \App\Models\ESBTPFraisConfiguration::where(
                        "frais_category_id",
                        $category->id,
                    )
                        ->where("filiere_id", $classe->filiere_id)
                        ->where("niveau_id", $classe->niveau_etude_id)
                        ->where("is_active", true)
                        ->first();

                    if ($configuration) {
                        $defaultAmount = $configuration->getMontantByStatus(
                            $affectationStatus,
                        );
                        $isConfigured = true;
                        $configurationType = "configuration";
                        \Log::info(
                            "Configuration trouvée pour catégorie {$category->name}",
                            [
                                "affectation_status" => $affectationStatus,
                                "amount" => $defaultAmount,
                            ],
                        );
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
                        if ($configurationType === "default") {
                            $configurationType = "class_variants";
                        }
                        \Log::info(
                            "Options class-based trouvées pour {$category->name}",
                            ["count" => $classBasedOptions->count()],
                        );
                    }
                } else {
                    // SERVICES OPTIONNELS : Utiliser EXACTEMENT la même logique qu'optional-config
                    $categoryWithOptions = ESBTPFraisCategory::with([
                        "options.assignments.filiere",
                        "options.assignments.niveau",
                    ])
                        ->where("id", $category->id)
                        ->first();

                    if (
                        $categoryWithOptions &&
                        $categoryWithOptions->options->count() > 0
                    ) {
                        $options = $categoryWithOptions->options;
                        $isConfigured = true;
                        $configurationType = "global_options";
                        \Log::info(
                            "Options trouvées pour {$category->name} (logique optional-config)",
                            ["count" => $options->count()],
                        );
                    }
                }

                if (!$isConfigured) {
                    $hasUnconfiguredFees = true;
                    \Log::warning(
                        "Catégorie non configurée: {$category->name}",
                    );
                }

                $fraisData[] = [
                    "category" => $category,
                    "default_amount" => $defaultAmount,
                    "configured_amount" => $defaultAmount,
                    "variants" => $options, // Compatibilité avec interface existante
                    "options" => $options,
                    "is_mandatory" => $category->is_mandatory,
                    "is_configured" => $isConfigured,
                    "configuration_type" => $configurationType,
                    "category_default_amount" => $category->default_amount,
                    "category_type" => $category->category_type ?? "academic",
                    "affectation_status" => $affectationStatus, // Pour le debug
                ];
            }

            \Log::info("Frais processés", [
                "frais_count" => count($fraisData),
                "has_unconfigured_fees" => $hasUnconfiguredFees,
            ]);

            return response()->json([
                "success" => true,
                "classe" => $classe,
                "frais" => $fraisData,
                "has_unconfigured_fees" => $hasUnconfiguredFees,
                "configure_url" => route("esbtp.frais.configure"),
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur getFraisByClasse: " . $e->getMessage(), [
                "classe_id" => $classeId,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la récupération des frais: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Récupérer les infos de l'inscription pour création paiement (pour modal AJAX)
     */
    public function getInscriptionData(ESBTPInscription $inscription)
    {
        try {
            $inscription->load(["etudiant", "classe", "anneeUniversitaire"]);

            return response()->json([
                "success" => true,
                "inscription" => [
                    "id" => $inscription->id,
                    "etudiant_id" => $inscription->etudiant_id,
                    "classe_id" => $inscription->classe_id,
                    "annee_universitaire_id" =>
                        $inscription->annee_universitaire_id,
                    "etudiant" => [
                        "id" => $inscription->etudiant->id,
                        "nom" => $inscription->etudiant->nom,
                        "prenoms" => $inscription->etudiant->prenoms,
                        "matricule" => $inscription->etudiant->matricule,
                    ],
                    "classe" => [
                        "id" => $inscription->classe->id ?? null,
                        "name" => $inscription->classe->name ?? "N/A",
                    ],
                    "annee" => [
                        "id" => $inscription->anneeUniversitaire->id,
                        "name" => $inscription->anneeUniversitaire->name,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur getInscriptionData: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        'Erreur lors de la récupération de l\'inscription: ' .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Récupérer le paiement en attente d'une inscription (pour modal AJAX)
     */
    public function getPaiementEnAttente(ESBTPInscription $inscription)
    {
        try {
            $paiement = ESBTPPaiement::where("inscription_id", $inscription->id)
                ->where("status", "en_attente")
                ->with(["inscription.etudiant", "fraisCategory"])
                ->first();

            if (!$paiement) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Aucun paiement en attente trouvé pour cette inscription.",
                    ],
                    404,
                );
            }

            return response()->json([
                "success" => true,
                "paiement" => [
                    "id" => $paiement->id,
                    "montant" => $paiement->montant,
                    "mode_paiement" => $paiement->mode_paiement,
                    "reference_paiement" => $paiement->reference_paiement,
                    "created_at" => $paiement->created_at->format("d/m/Y H:i"),
                    "etudiant" => [
                        "nom" => $paiement->inscription->etudiant->nom,
                        "prenoms" => $paiement->inscription->etudiant->prenoms,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur getPaiementEnAttente: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la récupération du paiement: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Récupérer les classes alternatives disponibles (pour modal AJAX)
     */
    public function getClassesAlternatives(ESBTPInscription $inscription)
    {
        try {
            // Charger l'inscription avec ses relations
            $inscription->load([
                "classe.filiere",
                "classe.niveauEtude",
                "anneeUniversitaire",
            ]);

            $classeActuelle = $inscription->classe;

            if (!$classeActuelle) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Classe actuelle non trouvée.",
                    ],
                    404,
                );
            }

            // Récupérer les classes alternatives de la même filière et du même niveau
            $classesQuery = ESBTPClasse::where("is_active", true)
                ->where("id", "!=", $classeActuelle->id)
                ->where("filiere_id", $classeActuelle->filiere_id)
                ->where("niveau_etude_id", $classeActuelle->niveau_etude_id);

            // Ajouter le comptage des étudiants inscrits pour l'année courante
            $anneeCourante = $inscription->anneeUniversitaire;

            $classes = $classesQuery
                ->get()
                ->map(function ($classe) use ($anneeCourante) {
                    // Compter les inscriptions actives pour cette classe dans l'année courante
                    $nombreInscrits = ESBTPInscription::where(
                        "classe_id",
                        $classe->id,
                    )
                        ->where("annee_universitaire_id", $anneeCourante->id)
                        ->where("status", "active")
                        ->count();

                    $placesDisponibles =
                        $classe->places_totales - $nombreInscrits;

                    return [
                        "id" => $classe->id,
                        "name" => $classe->name,
                        "places_totales" => $classe->places_totales,
                        "places_disponibles" => max(0, $placesDisponibles),
                        "is_available" => $placesDisponibles > 0,
                    ];
                })
                ->values();

            return response()->json([
                "success" => true,
                "classeActuelle" => [
                    "id" => $classeActuelle->id,
                    "name" => $classeActuelle->name,
                    "filiere" => $classeActuelle->filiere->name ?? "N/A",
                    "niveau" => $classeActuelle->niveauEtude->name ?? "N/A",
                ],
                "classesAlternatives" => $classes,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur getClassesAlternatives: " . $e->getMessage(), [
                "inscription_id" => $inscription->id,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la récupération des classes: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Vérifie la présence potentielle de doublons étudiants (route historique).
     */
    public function checkDuplicates(
        Request $request,
        StudentDuplicateDetector $detector,
    ) {
        return $this->duplicates($request, $detector);
    }


    /**
     * Nouvelle route de recherche de doublons étudiants.
     */
    public function duplicates(
        Request $request,
        StudentDuplicateDetector $detector,
    ) {
        $validated = $this->validateDuplicateRequest($request);

        $duplicates = $detector
            ->find(
                $validated["nom"],
                $validated["prenoms"] ?? '',
                $validated["date_naissance"] ?? null,
                $validated["sexe"] ?? null,
                6,
            )
            ->filter(function (array $item) {
                return ($item["score"] ?? 0) >= self::DUPLICATE_BLOCKING_SCORE;
            })
            ->map(function (array $item) {
                $item["show_url"] = route("esbtp.etudiants.show", $item["id"]);

                return $item;
            })
            ->values();

        return response()->json([
            "duplicates" => $duplicates,
        ]);
    }

}
