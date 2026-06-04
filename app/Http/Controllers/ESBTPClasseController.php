<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classe\AddStudentsRequest;
use App\Http\Requests\Classe\RemoveStudentsRequest;
use App\Http\Requests\Classe\StoreClasseRequest;
use App\Http\Requests\Classe\UpdateClasseRequest;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\Setting;
use App\Services\ClassPlanningService;
use App\Services\ClassStudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use Illuminate\Support\Str;

class ESBTPClasseController extends Controller
{
    use \App\Http\Controllers\Concerns\RespondsWithInlinePdf;

    public function __construct(
        private readonly ClassPlanningService $planningService,
        private readonly ClassStudentService $studentService,
    ) {
    }
    /**
     * Affiche la liste des classes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        $baseLogContext = [
            "timestamp" => $startTimestamp,
            "url" => $request->fullUrl(),
            "query" => $request->query(),
            "user_id" => optional($request->user())->id,
        ];
        \Log::info("ESBTPClasseController@index start", $baseLogContext);

        $user = Auth::user();

        // Récupérer l'année universitaire courante pour l'affichage
        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();
        // Pas de fallback calendaire : si aucune année n'est définie comme courante,
        // la vue affiche explicitement « Aucune année universitaire définie ».
        $anneeAcademique = $anneeCourante?->name;

        // Construction de la requête avec filtres.
        // Eager-load parcours.mention.domaine pour le tree LMD compact dans les cards
        // (cf classe-card.blade.php). Sans ça, chaque card LMD déclenche 3 queries N+1.
        $query = ESBTPClasse::with(["filiere", "niveau", "annee", "parcours.mention.domaine"]);

        // Filtres disponibles
        if ($request->filled("filiere_id")) {
            $query->where("filiere_id", $request->filiere_id);
        }

        if ($request->filled("niveau_id")) {
            $query->where("niveau_etude_id", $request->niveau_id);
        }

        if ($request->filled("statut")) {
            $query->where("is_active", $request->statut === "active");
        }

        if ($request->filled("capacite")) {
            if ($request->capacite === "disponible") {
                $query->whereRaw(
                    'places_totales > (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status = "active" AND esbtp_inscriptions.workflow_step = "etudiant_cree")',
                );
            } elseif ($request->capacite === "pleine") {
                $query->whereRaw(
                    'places_totales <= (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status = "active" AND esbtp_inscriptions.workflow_step = "etudiant_cree")',
                );
            }
        }

        // Recherche par nom ou code
        if ($request->filled("search")) {
            $search = "%" . $request->search . "%";
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", $search)->orWhere(
                    "code",
                    "like",
                    $search,
                );
            });
        }

        // Enseignant : ne voir que les classes où il a des séances dans l'emploi du temps
        if ($user && $user->can('can_teach')) {
            $teacher = $user->teacherProfile;
            if ($teacher && $anneeCourante) {
                $classeIds = ESBTPSeanceCours::query()
                    ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
                    ->where('esbtp_seance_cours.teacher_id', $teacher->id)
                    ->where('esbtp_emploi_temps.annee_universitaire_id', $anneeCourante->id)
                    ->distinct()
                    ->pluck('esbtp_seance_cours.classe_id');
                $query->whereIn('id', $classeIds);
            } elseif ($teacher) {
                $classeIds = ESBTPSeanceCours::where('teacher_id', $teacher->id)
                    ->distinct()->pluck('classe_id');
                $query->whereIn('id', $classeIds);
            }
        }

        \Log::info(
            "ESBTPClasseController@index processing",
            array_merge($baseLogContext, [
                "has_search" => $request->filled("search"),
                "filters" => [
                    "filiere_id" => $request->input("filiere_id"),
                    "niveau_id" => $request->input("niveau_id"),
                    "statut" => $request->input("statut"),
                    "capacite" => $request->input("capacite"),
                ],
            ]),
        );

        // Utiliser get() pour charger toutes les classes d'un coup
        $allClasses = $query->get();

        // Pour le chargement progressif via AJAX
        $perPage = 12;
        $page = $request->input("page", 1);
        $offset = ($page - 1) * $perPage;

        // Simuler la pagination manuelle
        $classes = $allClasses->slice($offset, $perPage)->values();
        $hasMore = $allClasses->count() > $offset + $perPage;
        $totalCount = $allClasses->count();

        // Données pour les filtres
        $filieres = ESBTPFiliere::where("is_active", true)->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)->get();

        // Calculer les KPI globaux sur TOUTES les classes actives (pas seulement celles filtrées)
        // En tenant compte uniquement des inscriptions de l'année courante
        $kpiQuery = ESBTPClasse::where("is_active", true);

        // Charger les relations avec comptage des étudiants de l'année courante
        if ($anneeCourante) {
            $kpiQuery->withCount([
                "inscriptions as nombre_etudiants_annee_courante" => function (
                    $q,
                ) use ($anneeCourante) {
                    $q->where(
                        "annee_universitaire_id",
                        $anneeCourante->id,
                    )->where("status", "active")
                      ->where("workflow_step", "etudiant_cree");
                },
            ]);
        }

        $allActiveClasses = $kpiQuery->get();

        // Calculer les statistiques globales
        $kpiStats = [
            "totalClasses" => $allActiveClasses->count(),
            "classesActives" => $allActiveClasses
                ->where("is_active", true)
                ->count(),
            "totalEtudiants" => $anneeCourante
                ? $allActiveClasses->sum("nombre_etudiants_annee_courante")
                : $allActiveClasses->sum("nombre_etudiants"),
            "totalPlaces" => $allActiveClasses->sum("places_totales"),
        ];

        $totalEtudiants = $kpiStats["totalEtudiants"];
        $totalPlaces = $kpiStats["totalPlaces"];

        $kpiStats["placesDisponibles"] = $totalPlaces - $totalEtudiants;
        $kpiStats["tauxOccupation"] =
            $totalPlaces > 0
                ? round(($totalEtudiants / $totalPlaces) * 100, 1)
                : 0;

        $classesSurcapacite = $allActiveClasses->filter(function ($classe) use (
            $anneeCourante,
        ) {
            $nombreEtudiants = $anneeCourante
                ? $classe->nombre_etudiants_annee_courante ?? 0
                : $classe->nombre_etudiants ?? 0;
            $placesTotales = $classe->places_totales ?? 0;

            return $placesTotales > 0 && $nombreEtudiants > $placesTotales;
        });

        $kpiStats["classesSurcapacite"] = $classesSurcapacite->count();
        $kpiStats["depassementTotal"] = $classesSurcapacite->sum(function (
            $classe,
        ) use ($anneeCourante) {
            $nombreEtudiants = $anneeCourante
                ? $classe->nombre_etudiants_annee_courante ?? 0
                : $classe->nombre_etudiants ?? 0;
            $placesTotales = $classe->places_totales ?? 0;

            return max(0, $nombreEtudiants - $placesTotales);
        });

        $duration = round((microtime(true) - $startMicrotime) * 1000, 2);
        \Log::info(
            "ESBTPClasseController@index completed",
            array_merge($baseLogContext, [
                "duration_ms" => $duration,
                "results_count" => $totalCount,
                "page" => $page,
                "has_more" => $hasMore,
                "kpi_stats" => $kpiStats,
            ]),
        );

        // Support AJAX pour "Charger plus"
        if ($request->ajax()) {
            $html = view(
                "esbtp.classes.partials.items",
                compact("classes"),
            )->render();
            return response()->json([
                "html" => $html,
                "hasMore" => $hasMore,
                "currentPage" => $page,
                "total" => $totalCount,
            ]);
        }

        // Different view rendering based on user role
        if ($user->hasRole('etudiant')) {
            // For students - read-only view
            return view(
                "esbtp.classes.student_index",
                compact(
                    "classes",
                    "anneeAcademique",
                    "anneeCourante",
                    "filieres",
                    "niveaux",
                    "hasMore",
                    "totalCount",
                    "kpiStats",
                ),
            );
        } else {
            // For admin and secretary - full functionality view
            return view(
                "esbtp.classes.index",
                compact(
                    "classes",
                    "anneeAcademique",
                    "anneeCourante",
                    "filieres",
                    "niveaux",
                    "hasMore",
                    "totalCount",
                    "kpiStats",
                ),
            );
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle classe.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $filieres = ESBTPFiliere::where("is_active", true)->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)->get();
        $annees = ESBTPAnneeUniversitaire::where("is_active", true)->get();
        $mentions = ESBTPLMDMention::with('domaine')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $parcours = ESBTPLMDParcours::with('mention.domaine', 'filiere')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Si c'est une requête AJAX (pour le modal), retourner seulement le partial
        if ($request->ajax() || $request->input("ajax") === "1") {
            return view("esbtp.classes.partials.form", [
                "filieres" => $filieres,
                "niveaux" => $niveaux,
                "annees" => $annees,
                "mentions" => $mentions,
                "parcours" => $parcours,
                "isModal" => true,
                "classe" => null,
            ]);
        }

        return view(
            "esbtp.classes.create",
            compact("filieres", "niveaux", "annees", "mentions", "parcours"),
        );
    }

    /**
     * Enregistre une nouvelle classe dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClasseRequest $request)
    {
        $validatedData = $request->validated();

        // Mode LMD : dériver filiere_id depuis le parcours sélectionné
        if (!empty($validatedData['parcours_id'])) {
            $parcours = ESBTPLMDParcours::findOrFail($validatedData['parcours_id']);
            $validatedData['filiere_id'] = $parcours->filiere_id;
        }

        // Ajouter les champs de traçabilité
        $validatedData["created_by"] = Auth::id();
        $validatedData["updated_by"] = Auth::id();

        // systeme_academique est auto-determine par le model event saving

        // Créer la nouvelle classe
        $classe = ESBTPClasse::create($validatedData);

        // Récupérer les matières associées aux niveaux sélectionnés
        $matieres = ESBTPMatiere::whereHas("niveaux", function ($query) use (
            $request,
        ) {
            $query->where("esbtp_niveau_etudes.id", $request->niveau_etude_id);
        })->get();

        // Associer les matières à la classe avec leurs coefficients et heures par défaut
        foreach ($matieres as $matiere) {
            $classe->matieres()->attach($matiere->id, [
                "coefficient" => $matiere->coefficient_default,
                "total_heures" => $matiere->total_heures_default,
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ]);
        }

        // Charger les relations pour la réponse JSON
        $classe->load(["filiere", "niveau", "annee"]);

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->ajax() || $request->input("is_ajax") === "1") {
            return response()->json([
                "success" => true,
                "message" => "La classe a été créée avec succès.",
                "classe" => [
                    "id" => $classe->id,
                    "name" => $classe->name,
                    "code" => $classe->code,
                    "filiere" => $classe->filiere
                        ? $classe->filiere->name
                        : null,
                    "niveau" => $classe->niveau ? $classe->niveau->name : null,
                    "annee" => $classe->annee ? $classe->annee->name : null,
                    "places_totales" => $classe->places_totales,
                    "is_active" => $classe->is_active,
                ],
            ]);
        }

        return redirect()
            ->route("esbtp.classes.index")
            ->with("success", "La classe a été créée avec succès.");
    }

    /**
     * Affiche les détails d'une classe spécifique.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ESBTPClasse $classe)
    {
        $user = Auth::user();

        // Récupérer l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        // Charger les relations de base (+ hierarchie LMD si classe LMD)
        $classe->load([
            "filiere",
            "niveau",
            "annee",
            "matieres",
            "emploisDuTemps",
            "parcours.mention.domaine",
        ]);

        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        // Periode du toggle Suivi des heures (Semestre 1 / Semestre 2 / Année).
        // Lu en amont pour pouvoir filtrer $lmdVolumeBudget côté serveur — sinon les blocs
        // "Répartition par catégorie" et "Détail par UE" affichent l'année entière même
        // quand l'utilisateur a sélectionné un seul semestre.
        $periode = $request->input("periode", "annee");
        if (!in_array($periode, ['annee', 'semestre1', 'semestre2'], true)) {
            $periode = 'annee';
        }

        // LMD volume budget : compare planifie vs realise par type_seance (CM/TD/TP)
        // Reutilise le service deja en place pour LMD planning. Renvoie array vide pour BTS.
        $lmdVolumeBudget = [];
        $lmdMatieres = collect();
        $lmdSemestres = [];
        if ($classe->systeme_academique === 'LMD' && $anneeCourante) {
            // UEMOA : 2 semestres par annee LMD. L1=S1+S2, L2=S3+S4, L3=S5+S6,
            // M1=S7+S8, M2=S9+S10. Le mapping decoule de niveau.type + niveau.year.
            $niveauType = optional($classe->niveau)->type ?? '';
            $niveauYear = (int) (optional($classe->niveau)->year ?? 1);
            $baseSem = 0;
            if ($niveauType === 'Licence')   $baseSem = ($niveauYear - 1) * 2;
            elseif ($niveauType === 'Master')   $baseSem = 6 + ($niveauYear - 1) * 2;
            elseif ($niveauType === 'Doctorat') $baseSem = 10 + ($niveauYear - 1) * 2;
            $lmdSemestres = [$baseSem + 1, $baseSem + 2];

            // Semestres effectivement chargés selon periode :
            //  - 'annee'      → tous les semestres du niveau (ex L2 → [3,4])
            //  - 'semestre1'  → 1er semestre du niveau (ex L2 → [3])
            //  - 'semestre2'  → 2e semestre du niveau (ex L2 → [4])
            $semestresToLoad = match ($periode) {
                'semestre1' => isset($lmdSemestres[0]) ? [$lmdSemestres[0]] : [],
                'semestre2' => isset($lmdSemestres[1]) ? [$lmdSemestres[1]] : [],
                default => $lmdSemestres,
            };

            try {
                $volumeBudgetService = app(\App\Services\VolumeBudgetService::class);
                foreach ($semestresToLoad as $sem) {
                    $semBudget = $volumeBudgetService->forClasse(
                        $classe,
                        $classe->niveau_etude_id,
                        $sem,
                        $anneeCourante->id,
                    );
                    foreach ($semBudget as $matiereId => $budget) {
                        if (!isset($lmdVolumeBudget[$matiereId])) {
                            $lmdVolumeBudget[$matiereId] = $budget;
                        } else {
                            foreach (['cm','td','tp'] as $k) {
                                $lmdVolumeBudget[$matiereId][$k]['planifie'] = ($lmdVolumeBudget[$matiereId][$k]['planifie'] ?? 0) + ($budget[$k]['planifie'] ?? 0);
                                $lmdVolumeBudget[$matiereId][$k]['realise']  = ($lmdVolumeBudget[$matiereId][$k]['realise']  ?? 0) + ($budget[$k]['realise']  ?? 0);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('VolumeBudgetService failed on classes.show: ' . $e->getMessage());
                $lmdVolumeBudget = [];
            }

            // Charger les ECUE de la classe :
            // - Si parcours_id existe (LMD avec parcours) : pattern Planning LMD strict
            //   (parcours.unitesEnseignement -> getEcuesEffectifs) = scope precis sur le parcours
            // - Si pas de parcours_id (LMD tronc commun) : fallback filiere+niveau
            try {
                $lmdMatieres = app(\App\Services\LMD\MatiereTreeBuilder::class)
                    ->loadLmdMatieresForClasse($classe);
            } catch (\Throwable $e) {
                \Log::warning('LMD matieres loader failed on classes.show: '.$e->getMessage());
                $lmdMatieres = collect();
            }
        }

        // Pour la tab "Suivi des heures" LMD : grouper les ECUEs par UE avec agregats
        // CM/TD/TP par UE + bucket "Hors UE" pour les ECUEs sans unite_enseignement_id.
        // Service partage avec ESBTPEmploiTempsController::show() (rule of three).
        $lmdUesAvecEcues = collect();
        if ($classe->systeme_academique === 'LMD' && $lmdMatieres->isNotEmpty()) {
            $lmdUesAvecEcues = app(\App\Services\LMD\MatiereTreeBuilder::class)
                ->forClasse($lmdMatieres, $lmdVolumeBudget);
        }

        $combinationMatieres = ESBTPMatiere::with([
            "filieres:id,name,code",
            "niveaux:id,name,code",
        ])
            ->where("is_active", true)
            ->when($classeFiliereId, function ($query) use ($classeFiliereId) {
                $query->whereHas("filieres", function ($q) use (
                    $classeFiliereId,
                ) {
                    $q->where("esbtp_filieres.id", $classeFiliereId);
                });
            })
            ->when($classeNiveauId, function ($query) use ($classeNiveauId) {
                $query->whereHas("niveaux", function ($q) use (
                    $classeNiveauId,
                ) {
                    $q->where("esbtp_niveau_etudes.id", $classeNiveauId);
                });
            })
            ->orderBy("name")
            ->get()
            ->map(function (ESBTPMatiere $matiere) {
                $matiere->setAttribute(
                    "classe_coefficient",
                    $matiere->coefficient ??
                        ($matiere->coefficient_default ?? 1),
                );
                return $matiere;
            });

        $planningMatiere = $this->planningService->buildPlanningMatierePourClasse(
            $classe,
            $anneeCourante,
            $periode,
            // Pour LMD : passe les vrais semestres applicables au niveau (ex: L3 = [5,6]) pour que
            // le service query bien semestre=5/6 en DB au lieu du fallback BTS [1,2]. Sinon
            // la vue "Semestre 5" retourne 0 ECUE car les données sont stockées avec semestre=5.
            !empty($lmdSemestres) ? $lmdSemestres : null,
        );

        // Charger les étudiants et inscriptions FILTRÉS par année courante
        if ($anneeCourante) {
            $classe->load([
                "etudiants" => function ($query) use ($anneeCourante, $classe) {
                    $query
                        ->distinct()
                        ->whereHas("inscriptions", function (
                            $inscriptionQuery,
                        ) use ($anneeCourante, $classe) {
                            $inscriptionQuery
                                ->where(
                                    "annee_universitaire_id",
                                    $anneeCourante->id,
                                )
                                ->where("status", "active")
                                ->where("workflow_step", "etudiant_cree")
                                ->where("classe_id", $classe->id);
                        })
                        ->orderBy("nom")
                        ->orderBy("prenoms");
                },
                "inscriptions" => function ($query) use ($anneeCourante) {
                    $query
                        ->where("annee_universitaire_id", $anneeCourante->id)
                        ->where("status", "active")
                        ->with("etudiant");
                },
            ]);
        } else {
            // Si aucune année courante définie, charger normalement (éviter les erreurs)
            $classe->load(["etudiants", "inscriptions"]);
        }

        // Préparer l'année académique pour l'affichage (null si aucune année définie)
        $anneeAcademique = $anneeCourante?->name;

        // Different view rendering based on user role
        if ($user->hasRole('etudiant')) {
            // For students - read-only view
            return view(
                "esbtp.classes.student_show",
                compact(
                    "classe",
                    "anneeCourante",
                    "anneeAcademique",
                    "combinationMatieres",
                    "planningMatiere",
                    "periode",
                ),
            );
        } else {
            // Charger les autres classes actives pour le modal de transfert
            $autresClasses = ESBTPClasse::where('is_active', true)
                ->where('id', '!=', $classe->id)
                ->with(['filiere:id,name', 'niveau:id,name'])
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'filiere_id', 'niveau_etude_id', 'places_totales']);

            // For admin and secretary - full functionality view
            return view(
                "esbtp.classes.show",
                compact(
                    "classe",
                    "anneeCourante",
                    "anneeAcademique",
                    "combinationMatieres",
                    "planningMatiere",
                    "periode",
                    "autresClasses",
                    "lmdVolumeBudget",
                    "lmdMatieres",
                    "lmdUesAvecEcues",
                    "lmdSemestres",
                ),
            );
        }
    }

    /**
     * Affiche le formulaire de modification d'une classe existante.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, ESBTPClasse $classe)
    {
        $filieres = ESBTPFiliere::where("is_active", true)->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)->get();
        $annees = ESBTPAnneeUniversitaire::where("is_active", true)->get();
        $mentions = ESBTPLMDMention::with('domaine')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $parcours = ESBTPLMDParcours::with('mention.domaine', 'filiere')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Si c'est une requête AJAX (pour le modal), retourner seulement le partial
        if ($request->ajax() || $request->input("ajax") === "1") {
            return view("esbtp.classes.partials.form", [
                "filieres" => $filieres,
                "niveaux" => $niveaux,
                "annees" => $annees,
                "mentions" => $mentions,
                "parcours" => $parcours,
                "isModal" => true,
                "classe" => $classe,
            ]);
        }

        return view(
            "esbtp.classes.edit",
            compact("classe", "filieres", "niveaux", "annees", "mentions", "parcours"),
        );
    }

    /**
     * Met à jour la classe spécifiée dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateClasseRequest $request, ESBTPClasse $classe)
    {
        // Validation centralisee dans UpdateClasseRequest (LMD-aware).
        $validatedData = $request->validated();

        // Mode LMD : dériver filiere_id depuis le parcours sélectionné
        if (!empty($validatedData['parcours_id'])) {
            $parcoursModel = ESBTPLMDParcours::findOrFail($validatedData['parcours_id']);
            $validatedData['filiere_id'] = $parcoursModel->filiere_id;
        }

        // Mettre à jour les champs de traçabilité
        $validatedData["updated_by"] = Auth::id();

        // systeme_academique est auto-determine par le model event saving

        // Mettre à jour la classe
        $classe->update($validatedData);

        // Si le niveau a changé, mettre à jour les matières
        if ($classe->isDirty("niveau_etude_id")) {
            // Récupérer les matières associées au niveau sélectionné
            $matieres = ESBTPMatiere::whereHas("niveaux", function (
                $query,
            ) use ($request) {
                $query->where(
                    "esbtp_niveau_etudes.id",
                    $request->niveau_etude_id,
                );
            })->get();

            // Réinitialiser les matières associées à la classe
            $classe->matieres()->detach();

            // Associer les nouvelles matières à la classe
            foreach ($matieres as $matiere) {
                $classe->matieres()->attach($matiere->id, [
                    "coefficient" => $matiere->coefficient_default,
                    "total_heures" => $matiere->total_heures_default,
                    "is_active" => true,
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);
            }
        }

        // Charger les relations pour la réponse JSON
        $classe->load(["filiere", "niveau", "annee"]);

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->ajax() || $request->input("is_ajax") === "1") {
            return response()->json([
                "success" => true,
                "message" => "La classe a été mise à jour avec succès.",
                "classe" => [
                    "id" => $classe->id,
                    "name" => $classe->name,
                    "code" => $classe->code,
                    "filiere" => $classe->filiere
                        ? $classe->filiere->name
                        : null,
                    "niveau" => $classe->niveau ? $classe->niveau->name : null,
                    "annee" => $classe->annee ? $classe->annee->name : null,
                    "places_totales" => $classe->places_totales,
                    "is_active" => $classe->is_active,
                ],
            ]);
        }

        // Récupérer et valider le return_url
        $returnUrl = $this->validateReturnUrl($request->input("return_url"));

        return redirect($returnUrl)->with(
            "success",
            "La classe a été mise à jour avec succès.",
        );
    }

    /**
     * Rafraîchir une carte de classe spécifique (AJAX pour mise à jour partielle)
     * Pattern identique à paiements.refreshLigne
     */
    public function refreshLigne(ESBTPClasse $classe)
    {
        try {
            // Charger toutes les relations nécessaires
            // parcours.mention.domaine pour le tree LMD compact dans la card (cf classe-card.blade.php)
            $classe->load(["filiere.parent", "niveau", "annee", "parcours.mention.domaine"]);

            // Permissions hoisted ici comme dans items.blade.php — sinon classe-card.blade.php
            // crash avec "Undefined variable $canManageSchool" (bug pré-existant exposé par
            // le refresh AJAX d'une ligne après action).
            $u = auth()->user();
            $cardPerms = [
                'canAdmin'         => $u->can('admin.access'),
                'canEditClasse'    => $u->can('classes.edit'),
                'canDeleteClasse'  => $u->can('classes.delete'),
                'canManageSchool'  => $u->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.coordinate']),
                'canTeach'         => $u->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.teach', 'identity.coordinate']),
            ];

            // Rendu de la partial classe-card
            $html = view("esbtp.classes.partials.classe-card", array_merge([
                "classe" => $classe,
            ], $cardPerms))->render();

            \Log::info("Carte classe rafraîchie avec succès", [
                "classe_id" => $classe->id,
                "user_id" => auth()->id(),
                "is_active" => $classe->is_active,
            ]);

            return response()->json([
                "success" => true,
                "html" => $html,
                "classe_id" => $classe->id,
                "is_active" => $classe->is_active,
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur refreshLigne classe: " . $e->getMessage(), [
                "classe_id" => $classe->id,
                "trace" => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors du rafraîchissement de la carte: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Valide et nettoie l'URL de retour pour éviter les attaques d'open redirect.
     *
     * @param  string|null  $url
     * @return string
     */
    private function validateReturnUrl($url)
    {
        // Si pas d'URL fournie, retourner la page show de la classe par défaut (Option B)
        if (!$url) {
            return route("esbtp.classes.show", [
                "classe" => request()->route("classe")->id,
            ]);
        }

        // Parser l'URL fournie
        $parsedUrl = parse_url($url);

        // Si l'URL n'est pas valide, retourner le fallback
        if ($parsedUrl === false) {
            return route("esbtp.classes.show", [
                "classe" => request()->route("classe")->id,
            ]);
        }

        // Vérifier que l'URL est interne (pas de domaine externe)
        if (isset($parsedUrl["host"])) {
            $appUrl = parse_url(config("app.url"));

            // Si l'URL a un host différent de notre app, c'est une tentative de redirect externe
            if ($parsedUrl["host"] !== ($appUrl["host"] ?? "")) {
                \Log::warning("Tentative de redirect externe bloquée", [
                    "url" => $url,
                    "host" => $parsedUrl["host"],
                    "expected_host" => $appUrl["host"] ?? "",
                    "user_id" => auth()->id(),
                ]);

                return route("esbtp.classes.show", [
                    "classe" => request()->route("classe")->id,
                ]);
            }
        }

        // URL valide et interne, on la retourne
        return $url;
    }

    /**
     * Supprime la classe spécifiée de la base de données.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPClasse $classe)
    {
        // Vérifier si des étudiants sont inscrits dans cette classe
        if ($classe->inscriptions()->count() > 0) {
            return redirect()
                ->route("esbtp.classes.index")
                ->with(
                    "error",
                    'Impossible d\'archiver cette classe car elle contient encore des étudiants inscrits pour l\'année en cours.',
                );
        }

        // Détacher toutes les matières
        $classe->matieres()->detach();

        // Supprimer la classe
        $classe->delete();

        return redirect()
            ->route("esbtp.classes.index")
            ->with(
                "success",
                'La classe a été archivée avec succès. L\'historique des inscriptions est préservé.',
            );
    }

    /**
     * Affiche la page de gestion des matières associées à une classe.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function matieres(ESBTPClasse $classe)
    {
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $matieres = ESBTPMatiere::with([
            "filieres:id,name,code",
            "niveaux:id,name,code",
            "liaisonsFilieresNiveaux.filiere:id,name,code",
            "liaisonsFilieresNiveaux.niveauEtude:id,name,code",
        ])
            ->where("is_active", true)
            ->orderBy("name")
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use (
                $classeFiliereId,
                $classeNiveauId,
            ) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }
                return $matiere->liaisonsFilieresNiveaux
                    ->where('filiere_id', $classeFiliereId)
                    ->where('niveau_etude_id', $classeNiveauId)
                    ->isNotEmpty();
            })
            ->values()
            ->map(function (ESBTPMatiere $matiere) {
                $matiere->setAttribute("matches_combination", true);
                $matiere->setAttribute(
                    "classe_coefficient",
                    $matiere->coefficient ??
                        ($matiere->coefficient_default ?? 1),
                );
                return $matiere;
            });

        $availableMatieres = ESBTPMatiere::with([
            "filieres:id,name,code",
            "niveaux:id,name,code",
            "liaisonsFilieresNiveaux.filiere:id,name,code",
            "liaisonsFilieresNiveaux.niveauEtude:id,name,code",
        ])
            ->where("is_active", true)
            ->orderBy("name")
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use (
                $classeFiliereId,
                $classeNiveauId,
            ) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }

                return $matiere->liaisonsFilieresNiveaux
                    ->where('filiere_id', $classeFiliereId)
                    ->where('niveau_etude_id', $classeNiveauId)
                    ->isEmpty();
            })
            ->values()
            ->map(function (ESBTPMatiere $matiere) {
                $matiere->setAttribute("matches_combination", false);
                $matiere->setAttribute(
                    "classe_coefficient",
                    $matiere->coefficient ??
                        ($matiere->coefficient_default ?? 1),
                );
                return $matiere;
            });

        $stats = [
            "used_by_class" => $matieres->count(),
            "suggested_total" => $matieres->count(),
            "suggested_available" => 0,
            "catalog_available" => $availableMatieres->count(),
        ];

        $filieres = ESBTPFiliere::where("is_active", true)
            ->orderBy("name")
            ->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)
            ->orderBy("name")
            ->get();

        return view("esbtp.classes.matieres", [
            "classe" => $classe,
            "matieres" => $matieres,
            "availableMatieres" => $availableMatieres,
            "stats" => $stats,
            "filieres" => $filieres,
            "niveaux" => $niveaux,
        ]);
    }

    /**
     * Met à jour les matières et leurs coefficients pour une classe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function updateMatieres(Request $request, ESBTPClasse $classe)
    {
        // Valider les données du formulaire
        $request->validate([
            "matiere_ids" => "nullable|array",
            "matiere_ids.*" => "exists:esbtp_matieres,id",
            "coefficients" => "nullable|array",
            "coefficients.*" => "numeric|min:0",
            "heures" => "nullable|array",
            "heures.*" => "integer|min:0",
        ]);

        // Réinitialiser les matières existantes
        $classe->matieres()->detach();

        // Récupérer les IDs des matières sélectionnées
        $matiereIds = $request->input("matiere_ids", []);

        // Ajouter les matières sélectionnées avec leurs coefficients et heures
        foreach ($matiereIds as $matiereId) {
            $classe->matieres()->attach($matiereId, [
                "coefficient" => $request->input(
                    "coefficients.{$matiereId}",
                    1,
                ),
                "total_heures" => $request->input("heures.{$matiereId}", 0),
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ]);
        }

        return redirect()
            ->route("esbtp.classes.show", ["classe" => $classe->id])
            ->with("success", "Les matières ont été mises à jour avec succès.");
    }

    /**
     * Récupère les matières d'une classe pour l'API JavaScript.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function getMatieresForApi(ESBTPClasse $classe)
    {
        try {
            \Log::info(
                "API matières appelée pour la classe ID: " . $classe->id,
            );
            \Log::info(
                "Classe: " .
                    ($classe->name ?? "N/A") .
                    ", Filière ID: " .
                    ($classe->filiere_id ?? "N/A") .
                    ", Niveau ID: " .
                    ($classe->niveau_etude_id ?? "N/A"),
            );

            // Méthode 1: Matières directement liées à la classe via table pivot
            $matieres = $classe
                ->matieres()
                ->where("esbtp_matieres.is_active", true)
                ->get();
            \Log::info("Matières directement liées: " . $matieres->count());

            // Méthode 2: Recherche par relations many-to-many filière et niveau
            if ($matieres->isEmpty()) {
                \Log::info("Recherche par relations many-to-many...");
                $query = \App\Models\ESBTPMatiere::where("is_active", true);

                if ($classe->filiere_id) {
                    $query->whereHas("filieres", function ($q) use ($classe) {
                        $q->where("esbtp_filieres.id", $classe->filiere_id);
                    });
                }

                if ($classe->niveau_etude_id) {
                    $query->whereHas("niveaux", function ($q) use ($classe) {
                        $q->where(
                            "esbtp_niveau_etudes.id",
                            $classe->niveau_etude_id,
                        );
                    });
                }

                $matieres = $query->get();
                \Log::info(
                    "Matières trouvées par relations many-to-many: " .
                        $matieres->count(),
                );
            }

            // Méthode 3: Recherche par colonnes directes (deprecated mais peut être utilisé)
            if ($matieres->isEmpty()) {
                \Log::info("Recherche par colonnes directes...");
                $query = \App\Models\ESBTPMatiere::where("is_active", true);

                if ($classe->filiere_id) {
                    $query->where("filiere_id", $classe->filiere_id);
                }

                if ($classe->niveau_etude_id) {
                    $query->where("niveau_etude_id", $classe->niveau_etude_id);
                }

                $matieres = $query->get();
                \Log::info(
                    "Matières trouvées par colonnes directes: " .
                        $matieres->count(),
                );
            }

            // Méthode 4: Toutes les matières actives comme fallback
            if ($matieres->isEmpty()) {
                \Log::info("Fallback: toutes les matières actives...");
                $matieres = \App\Models\ESBTPMatiere::where(
                    "is_active",
                    true,
                )->get();
                \Log::info(
                    "Toutes les matières actives: " . $matieres->count(),
                );
            }

            // Si encore vide, toutes les matières
            if ($matieres->isEmpty()) {
                \Log::info("Fallback final: toutes les matières...");
                $matieres = \App\Models\ESBTPMatiere::all();
                \Log::info("Toutes les matières: " . $matieres->count());
            }

            // Formatage pour l'API
            $formattedMatieres = $matieres->map(function ($matiere) {
                return [
                    "id" => $matiere->id,
                    "name" =>
                        $matiere->nom ??
                        ($matiere->name ?? "Matière " . $matiere->id),
                    "code" => $matiere->code ?? "",
                    "coefficient" => $matiere->coefficient ?? 1,
                ];
            });

            \Log::info(
                "Total matières renvoyées: " . $formattedMatieres->count(),
            );
            return response()->json($formattedMatieres);
        } catch (\Exception $e) {
            \Log::error("Erreur dans getMatieresForApi: " . $e->getMessage());
            \Log::error("Trace: " . $e->getTraceAsString());

            return response()->json(
                [
                    "error" => "Erreur lors de la récupération des matières",
                    "message" => $e->getMessage(),
                    "debug" => config("app.debug")
                        ? $e->getTraceAsString()
                        : null,
                ],
                500,
            );
        }
    }

    /**
     * Get subjects for a specific class.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatieres($id)
    {
        try {
            $classe = ESBTPClasse::findOrFail($id);
            $matieres = $classe
                ->matieres()
                ->where("is_active", true)
                ->orderBy("name")
                ->get(["id", "name", "code"]);

            return response()->json($matieres);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Erreur lors de la récupération des matières"],
                500,
            );
        }
    }

    /**
     * Récupère les détails d'une classe pour l'API JavaScript.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getClasseById($id)
    {
        try {
            $classe = ESBTPClasse::with([
                "filiere",
                "niveau",
                "anneeUniversitaire",
            ])->findOrFail($id);

            return response()->json($classe);
        } catch (\Exception $e) {
            \Log::error(
                "Erreur lors de la récupération de la classe: " .
                    $e->getMessage(),
            );
            return response()->json(["error" => "Classe non trouvée"], 404);
        }
    }

    /**
     * Returns all active classes for API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApi()
    {
        $classes = ESBTPClasse::with(["filiere", "niveau", "annee"])
            ->where("is_active", true)
            ->get();
        return response()->json($classes);
    }

    /**
     * Récupère le nombre de places disponibles pour une classe.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailablePlaces($id)
    {
        try {
            \Log::info("Début de getAvailablePlaces pour la classe ID: {$id}");

            // Trouver l'année universitaire active
            $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where(
                "is_active",
                true,
            )->first();
            if (!$anneeActive) {
                \Log::error("Aucune année universitaire active trouvée");
                return response()->json(
                    ["error" => "Aucune année universitaire active."],
                    400,
                );
            }

            $classe = ESBTPClasse::find($id);

            if (!$classe) {
                \Log::error("Classe non trouvée pour l'ID: {$id}");
                return response()->json(
                    ["error" => "Classe non trouvée."],
                    404,
                );
            }

            \Log::info("Classe trouvée: {$classe->name}");

            $capacity = $classe->places_totales ?? 0;
            \Log::info("Capacité (places_totales) lue: {$capacity}");

            // Compter seulement les inscriptions de l'année universitaire active
            $inscriptions_count = $classe
                ->inscriptions()
                ->where("annee_universitaire_id", $anneeActive->id)
                ->where("status", "active")
                ->where("workflow_step", "etudiant_cree")
                ->count();
            \Log::info(
                "Nombre d'inscriptions pour l'année active {$anneeActive->name}: {$inscriptions_count}",
            );

            $availablePlaces = $capacity - $inscriptions_count;
            \Log::info(
                "Calcul des places disponibles: {$capacity} - {$inscriptions_count} = {$availablePlaces}",
            );

            $responseData = [
                "available_places" => $availablePlaces,
                "capacity" => $capacity,
                "inscriptions_count" => $inscriptions_count,
            ];

            \Log::info("Réponse JSON envoyée: " . json_encode($responseData));

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error(
                "Erreur dans getAvailablePlaces pour la classe ID {$id}: " .
                    $e->getMessage(),
            );
            return response()->json(
                [
                    "error" =>
                        "Une erreur est survenue lors de la récupération des données.",
                ],
                500,
            );
        }
    }

    /**
     * Récupère les étudiants d'une classe pour l'API JavaScript.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function getEtudiants(ESBTPClasse $classe, \Illuminate\Http\Request $request)
    {
        $query = $classe
            ->etudiants()
            ->select("esbtp_etudiants.id", "esbtp_etudiants.nom", "esbtp_etudiants.prenoms", "esbtp_etudiants.matricule")
            ->where("esbtp_inscriptions.status", "active")
            ->where("esbtp_inscriptions.workflow_step", "etudiant_cree")
            ->orderBy("esbtp_etudiants.nom")
            ->orderBy("esbtp_etudiants.prenoms");

        // Filtre optionnel : inscriptions de l'année universitaire spécifiée.
        // Les classes sont universelles dans KLASSCI, donc une même classe peut avoir
        // des inscriptions sur plusieurs années. Préciser annee_universitaire_id renvoie
        // uniquement les étudiants inscrits cette année-là dans cette classe.
        if ($request->filled('annee_universitaire_id')) {
            $query->where('esbtp_inscriptions.annee_universitaire_id', (int) $request->input('annee_universitaire_id'));
        }

        $etudiants = $query->get();

        return response()->json([
            "success" => true,
            "etudiants" => $etudiants,
        ]);
    }

    /**
     * Get students for a class (API for new notes system)
     * Used in the new notes grid for displaying student rows
     *
     * @param Request $request
     * @param ESBTPClasse $classe
     * @return \Illuminate\Http\JsonResponse
     */
    public function students(Request $request, ESBTPClasse $classe)
    {
        try {
            \Log::info("👥 [API] students - Request received", [
                "class_id" => $classe->id,
                "class_name" => $classe->name,
                "user_id" => auth()->id(),
            ]);

            // Get current academic year
            $anneeCourante = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();
            if (!$anneeCourante) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Aucune année universitaire active.",
                    ],
                    400,
                );
            }

            // Get active students for this class in current academic year
            $etudiants = $classe
                ->inscriptions()
                ->with(["etudiant"])
                ->where("status", "active")
                ->where("workflow_step", "etudiant_cree")
                ->where("annee_universitaire_id", $anneeCourante->id)
                ->get()
                ->map(function ($inscription) {
                    $etudiant = $inscription->etudiant;
                    return [
                        "id" => $etudiant->id,
                        "nom" => $etudiant->nom,
                        "prenoms" => $etudiant->prenoms,
                        "matricule" => $etudiant->matricule,
                        "nom_complet" =>
                            $etudiant->nom . " " . $etudiant->prenoms,
                        "photo_url" => $etudiant->photo_url,
                        "inscription_id" => $inscription->id,
                    ];
                })
                ->sortBy(function ($student) {
                    return mb_strtolower($student['nom'] . ' ' . $student['prenoms']);
                })
                ->values();

            \Log::info("✅ [API] students - Success", [
                "class_id" => $classe->id,
                "class_name" => $classe->name,
                "student_count" => $etudiants->count(),
            ]);

            return response()->json([
                "success" => true,
                "class" => [
                    "id" => $classe->id,
                    "name" => $classe->name,
                    "code" => $classe->code,
                    "filiere" => $classe->filiere->name ?? null,
                    "niveau" => $classe->niveau->name ?? null,
                ],
                "students" => $etudiants,
                "student_count" => $etudiants->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ [API] students - Error", [
                "class_id" => $classe->id,
                "error" => $e->getMessage(),
                "trace" => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la récupération des étudiants.",
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Récupère la configuration matricule pour le niveau d'études d'une classe
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getNiveauConfig($id)
    {
        try {
            $classe = ESBTPClasse::with("niveau")->findOrFail($id);

            if (!$classe->niveau) {
                return response()->json([
                    "success" => false,
                    "message" =>
                        'Niveau d\'études non trouvé pour cette classe',
                    "niveau_config" => null,
                ]);
            }

            // Rechercher la configuration matricule pour ce niveau
            $currentEtablissementId = \App\Models\ESBTPSystemSetting::getCurrentEtablissementId();

            // Construire le code depuis type + year (même logique que MatriculeGenerator::buildNiveauCode)
            $niveauType = $classe->niveau->type ?? null;
            $niveauYear = $classe->niveau->year ?? null;

            $configCode = null;
            if ($niveauType && $niveauYear !== null) {
                $configCode = match (strtolower(trim($niveauType))) {
                    'bts'       => $niveauYear . 'A',
                    'licence'   => 'L' . $niveauYear,
                    'master'    => 'M' . $niveauYear,
                    'bachelor'  => 'B' . $niveauYear,
                    'doctorat'  => 'D' . $niveauYear,
                    'diplome', 'diplôme' => 'DIP' . $niveauYear,
                    'certificat' => 'CER' . $niveauYear,
                    default     => null,
                };
            }

            // Chercher la config : d'abord par type+year, puis fallback sur niveau->code
            $matriculeConfig = null;

            if ($configCode) {
                $matriculeConfig = \App\Models\ESBTPMatriculeConfig::where(
                    "etablissement_id", $currentEtablissementId,
                )
                    ->where("niveau_etude_code", $configCode)
                    ->where("is_active", true)
                    ->first();
            }

            // Fallback : niveau->code (pour les cas spéciaux comme L3Pro)
            if (!$matriculeConfig && $classe->niveau->code) {
                $matriculeConfig = \App\Models\ESBTPMatriculeConfig::where(
                    "etablissement_id", $currentEtablissementId,
                )
                    ->where("niveau_etude_code", $classe->niveau->code)
                    ->where("is_active", true)
                    ->first();
            }

            if (!$matriculeConfig && !$configCode && !$classe->niveau->code) {
                return response()->json([
                    "success" => true,
                    "niveau_config" => null,
                    "message" => "Type de niveau non défini pour cette classe",
                ]);
            }

            if (!$matriculeConfig) {
                return response()->json([
                    "success" => true,
                    "niveau_config" => null,
                    "message" =>
                        "Configuration matricule non trouvée pour ce niveau",
                ]);
            }

            return response()->json([
                "success" => true,
                "niveau_config" => [
                    "id" => $matriculeConfig->id,
                    "code" => $matriculeConfig->niveau_etude_code,
                    "nom" => $matriculeConfig->niveau_etude_name,
                    "prefixe" => $matriculeConfig->prefixe,
                    "annee_format" => $matriculeConfig->annee_format,
                    "etablissement_code" =>
                        $matriculeConfig->etablissement_code,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Erreur: " . $e->getMessage(),
                    "niveau_config" => null,
                ],
                500,
            );
        }
    }

    /**
     * Affiche la liste d'appel pour une classe (preview web)
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeAppel(ESBTPClasse $classe)
    {
        $classe->load(["filiere", "niveau", "annee"]);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        $etudiants = $classe
            ->inscriptions()
            ->with(["etudiant.accessibilityProfile"])
            ->where("status", "active")
            ->where("workflow_step", "etudiant_cree")
            ->when($anneeCourante, function ($query) use ($anneeCourante) {
                return $query->where(
                    "annee_universitaire_id",
                    $anneeCourante->id,
                );
            })
            ->get()
            ->map(function ($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            // Trier alpha et réindexer pour obtenir 1, 2, 3... dans la vue
            ->sortBy(function ($etudiant) {
                return Str::lower($etudiant->nom . " " . $etudiant->prenoms);
            })
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "KLASSCI"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", ""),
        ];

        return view(
            "esbtp.classes.liste-appel",
            compact("classe", "etudiants", "anneeCourante", "etablissement"),
        );
    }

    /**
     * Génère le PDF de la liste d'appel pour une classe
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeAppelPDF(ESBTPClasse $classe, Request $request)
    {
        $classe->load(["filiere", "niveau", "annee"]);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        $etudiants = $classe
            ->inscriptions()
            ->with(["etudiant.accessibilityProfile"])
            ->where("status", "active")
            ->where("workflow_step", "etudiant_cree")
            ->when($anneeCourante, function ($query) use ($anneeCourante) {
                return $query->where(
                    "annee_universitaire_id",
                    $anneeCourante->id,
                );
            })
            ->get()
            ->map(function ($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            // Tri identique à la version web afin de conserver la numérotation
            ->sortBy(function ($etudiant) {
                return Str::lower($etudiant->nom . " " . $etudiant->prenoms);
            })
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "KLASSCI"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", ""),
        ];

        $pdf = PDF::loadView(
            "esbtp.classes.liste-appel-pdf",
            compact("classe", "etudiants", "anneeCourante", "etablissement"),
        );

        $filename =
            "liste-appel-" .
            Str::slug($classe->name) .
            "-" .
            date("Y-m-d") .
            ".pdf";

        return $this->respondWithPdf($pdf, $filename, $request);
    }

    /**
     * Affiche la liste complète des étudiants pour une classe (preview web)
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeComplete(ESBTPClasse $classe)
    {
        $classe->load(["filiere", "niveau", "annee"]);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        $etudiants = $classe
            ->inscriptions()
            ->with(["etudiant.accessibilityProfile"])
            ->where("status", "active")
            ->where("workflow_step", "etudiant_cree")
            ->when($anneeCourante, function ($query) use ($anneeCourante) {
                return $query->where(
                    "annee_universitaire_id",
                    $anneeCourante->id,
                );
            })
            ->get()
            ->map(function ($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            ->sortBy(["nom", "prenoms"])
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "KLASSCI"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", ""),
        ];

        return view(
            "esbtp.classes.liste-complete",
            compact("classe", "etudiants", "anneeCourante", "etablissement"),
        );
    }

    /**
     * Génère le PDF de la liste complète des étudiants pour une classe
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeCompletePDF(ESBTPClasse $classe, Request $request)
    {
        $classe->load(["filiere", "niveau", "annee"]);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        $etudiants = $classe
            ->inscriptions()
            ->with(["etudiant.accessibilityProfile"])
            ->where("status", "active")
            ->where("workflow_step", "etudiant_cree")
            ->when($anneeCourante, function ($query) use ($anneeCourante) {
                return $query->where(
                    "annee_universitaire_id",
                    $anneeCourante->id,
                );
            })
            ->get()
            ->map(function ($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            ->sortBy(["nom", "prenoms"])
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "KLASSCI"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", ""),
        ];

        $pdf = PDF::loadView(
            "esbtp.classes.liste-complete-pdf",
            compact("classe", "etudiants", "anneeCourante", "etablissement"),
        );

        $filename =
            "liste-complete-" .
            Str::slug($classe->name) .
            "-" .
            date("Y-m-d") .
            ".pdf";

        return $this->respondWithPdf($pdf, $filename, $request);
    }

    /**
     * Génère le fichier Excel de la liste complète des étudiants pour une classe
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeCompleteExcel(ESBTPClasse $classe)
    {
        $classe->load(["filiere", "niveau", "annee"]);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();

        $etudiants = $classe
            ->inscriptions()
            ->with(["etudiant.accessibilityProfile"])
            ->where("status", "active")
            ->where("workflow_step", "etudiant_cree")
            ->when($anneeCourante, function ($query) use ($anneeCourante) {
                return $query->where(
                    "annee_universitaire_id",
                    $anneeCourante->id,
                );
            })
            ->get()
            ->map(function ($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            ->sortBy(["nom", "prenoms"])
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "KLASSCI"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", ""),
        ];

        $filename =
            "liste-complete-" .
            Str::slug($classe->name) .
            "-" .
            date("Y-m-d") .
            ".xlsx";

        return Excel::download(
            new \App\Exports\ClasseEtudiantsExport(
                $classe,
                $etudiants,
                $anneeCourante,
                $etablissement,
            ),
            $filename,
        );
    }

    /**
     * Récupérer toutes les classes filtrées (pour les exports)
     */
    private function getAllFilteredClasses(Request $request)
    {
        $query = ESBTPClasse::with(["filiere", "niveau", "annee"]);

        // Appliquer les mêmes filtres que dans index()
        if ($request->filled("filiere_id")) {
            $query->where("filiere_id", $request->filiere_id);
        }

        if ($request->filled("niveau_id")) {
            $query->where("niveau_etude_id", $request->niveau_id);
        }

        if ($request->filled("statut")) {
            $query->where("is_active", $request->statut === "active");
        }

        if ($request->filled("capacite")) {
            if ($request->capacite === "disponible") {
                $query->whereRaw(
                    'places_totales > (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status = "active" AND esbtp_inscriptions.workflow_step = "etudiant_cree")',
                );
            } elseif ($request->capacite === "pleine") {
                $query->whereRaw(
                    'places_totales <= (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status = "active" AND esbtp_inscriptions.workflow_step = "etudiant_cree")',
                );
            }
        }

        // Recherche par nom ou code
        if ($request->filled("search")) {
            $search = "%" . $request->search . "%";
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", $search)->orWhere(
                    "code",
                    "like",
                    $search,
                );
            });
        }

        // Récupérer TOUS les résultats (pas de pagination)
        return $query->orderBy("name")->get();
    }

    /**
     * Exporter les classes au format Excel (XLSX)
     */
    public function exportExcel(Request $request)
    {
        try {
            // Récupérer TOUTES les classes filtrées
            $classes = $this->getAllFilteredClasses($request);

            // Récupérer l'année courante
            $anneeCourante = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();

            // Préparer les filtres pour l'export
            $filters = [
                "search" => $request->input("search"),
                "filiere_id" => $request->input("filiere_id"),
                "niveau_id" => $request->input("niveau_id"),
                "statut" => $request->input("statut"),
                "capacite" => $request->input("capacite"),
            ];

            // Créer l'export
            $export = new \App\Exports\ClassesExport(
                $classes,
                $anneeCourante,
                $filters,
            );

            // Générer le nom du fichier
            $filename = "classes_" . now()->format("Y-m-d_His") . ".xlsx";

            \Log::info("Export Excel classes généré", [
                "user_id" => auth()->id(),
                "total_classes" => $classes->count(),
                "filename" => $filename,
            ]);

            return Excel::download($export, $filename);
        } catch (\Exception $e) {
            \Log::error("Erreur export Excel classes: " . $e->getMessage(), [
                "trace" => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'export Excel: ' . $e->getMessage(),
                );
        }
    }

    /**
     * Exporter les classes au format CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            // Récupérer TOUTES les classes filtrées
            $classes = $this->getAllFilteredClasses($request);

            // Récupérer l'année courante
            $anneeCourante = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();

            // Préparer les filtres
            $filters = [
                "search" => $request->input("search"),
                "filiere_id" => $request->input("filiere_id"),
                "niveau_id" => $request->input("niveau_id"),
                "statut" => $request->input("statut"),
                "capacite" => $request->input("capacite"),
            ];

            // Créer l'export
            $export = new \App\Exports\ClassesExport(
                $classes,
                $anneeCourante,
                $filters,
            );

            // Générer le nom du fichier
            $filename = "classes_" . now()->format("Y-m-d_His") . ".csv";

            \Log::info("Export CSV classes généré", [
                "user_id" => auth()->id(),
                "total_classes" => $classes->count(),
                "filename" => $filename,
            ]);

            return Excel::download(
                $export,
                $filename,
                \Maatwebsite\Excel\Excel::CSV,
                [
                    "Content-Type" => "text/csv",
                ],
            );
        } catch (\Exception $e) {
            \Log::error("Erreur export CSV classes: " . $e->getMessage(), [
                "trace" => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'export CSV: ' . $e->getMessage(),
                );
        }
    }

    /**
     * Exporter les classes au format PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            // Récupérer TOUTES les classes filtrées
            $classes = $this->getAllFilteredClasses($request);

            // Récupérer l'année courante
            $anneeCourante = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();

            // Préparer les filtres
            $filters = [
                "search" => $request->input("search"),
                "filiere_id" => $request->input("filiere_id"),
                "niveau_id" => $request->input("niveau_id"),
                "statut" => $request->input("statut"),
                "capacite" => $request->input("capacite"),
            ];

            // Récupérer les paramètres de l'école
            $settings = [
                "nom" => Setting::get("school_name", "KLASSCI"),
                "adresse" => Setting::get("school_address", ""),
                "telephone" => Setting::get("school_phone", ""),
                "email" => Setting::get("school_email", ""),
                "logo" => Setting::get("school_logo", ""),
            ];

            \Log::info("Export PDF classes généré", [
                "user_id" => auth()->id(),
                "total_classes" => $classes->count(),
            ]);

            // Générer le PDF — setPaper() + `size`/`margin` dans @page du template
            // (pattern strictement identique à bulletins/pdf-configurable, seul
            // combo vérifié rendant des marges visibles en prod sur cet hébergeur).
            $pdf = PDF::loadView("esbtp.classes.export-pdf", [
                "classes" => $classes,
                "anneeCourante" => $anneeCourante,
                "filters" => $filters,
                "settings" => $settings,
                "dateExport" => now(),
            ])->setPaper("a4", "landscape");

            // Télécharger le PDF
            $filename = "classes_" . now()->format("Y-m-d_His") . ".pdf";
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error("Erreur export PDF classes: " . $e->getMessage(), [
                "trace" => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'export PDF: ' . $e->getMessage(),
                );
        }
    }

    /**
     * Récupérer les classes en surcapacité pour le modal d'avertissement
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOvercapacityClasses()
    {
        try {
            // Récupérer l'année universitaire courante
            $anneeCourante = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();

            if (!$anneeCourante) {
                return response()->json([
                    "success" => false,
                    "message" => "Aucune année universitaire courante définie",
                    "classes" => [],
                ]);
            }

            // Récupérer les classes en surcapacité (>= 100% d'occupation)
            $classesOvercapacity = ESBTPClasse::with(["filiere", "niveauEtude"])
                ->select("esbtp_classes.*")
                ->selectRaw(
                    '(
                    SELECT COUNT(*)
                    FROM esbtp_inscriptions
                    WHERE esbtp_inscriptions.classe_id = esbtp_classes.id
                    AND esbtp_inscriptions.status = "active"
                    AND esbtp_inscriptions.workflow_step = "etudiant_cree"
                    AND esbtp_inscriptions.annee_universitaire_id = ?
                ) as inscriptions_actives',
                    [$anneeCourante->id],
                )
                ->selectRaw(
                    '(
                    CASE
                        WHEN places_totales > 0 THEN
                            ROUND((
                                SELECT COUNT(*)
                                FROM esbtp_inscriptions
                                WHERE esbtp_inscriptions.classe_id = esbtp_classes.id
                                AND esbtp_inscriptions.status = "active"
                                AND esbtp_inscriptions.workflow_step = "etudiant_cree"
                                AND esbtp_inscriptions.annee_universitaire_id = ?
                            ) * 100.0 / places_totales, 1)
                        ELSE 0
                    END
                ) as taux_occupation',
                    [$anneeCourante->id],
                )
                ->whereRaw("places_totales > 0")
                // FIX MySQL ONLY_FULL_GROUP_BY : on ne peut pas référencer places_totales
                // (champ non agrégé) dans HAVING sans GROUP BY. Bascule en WHERE avec
                // la sous-requête expanded pour comparer directement au champ.
                ->whereRaw(
                    '(
                        SELECT COUNT(*)
                        FROM esbtp_inscriptions
                        WHERE esbtp_inscriptions.classe_id = esbtp_classes.id
                        AND esbtp_inscriptions.status = "active"
                        AND esbtp_inscriptions.workflow_step = "etudiant_cree"
                        AND esbtp_inscriptions.annee_universitaire_id = ?
                    ) >= esbtp_classes.places_totales',
                    [$anneeCourante->id],
                )
                ->orderBy("taux_occupation", "desc")
                ->get();

            // Formater les données pour le modal
            $classesFormatees = $classesOvercapacity->map(function ($classe) {
                return [
                    "id" => $classe->id,
                    "nom" => $classe->name,
                    "filiere" => $classe->filiere->name ?? "N/A",
                    "niveau" => $classe->niveauEtude->name ?? "N/A",
                    "places_totales" => $classe->places_totales,
                    "inscriptions_actives" => $classe->inscriptions_actives,
                    "taux_occupation" => $classe->taux_occupation,
                    "depassement" =>
                        $classe->inscriptions_actives - $classe->places_totales,
                    "statut" => $classe->is_active ? "Actif" : "Inactif",
                ];
            });

            return response()->json([
                "success" => true,
                "message" =>
                    $classesFormatees->count() .
                    " classe(s) en surcapacité détectée(s)",
                "classes" => $classesFormatees,
                "total_classes" => $classesFormatees->count(),
                "annee_universitaire" => $anneeCourante->name,
            ]);
        } catch (\Exception $e) {
            \Log::error(
                "Erreur lors de la récupération des classes en surcapacité: " .
                    $e->getMessage(),
                [
                    "trace" => config('app.debug') ? $e->getTraceAsString() : null,
                ],
            );

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la récupération des données: " .
                        $e->getMessage(),
                    "classes" => [],
                ],
                500,
            );
        }
    }

    /**
     * Recherche les étudiants disponibles pour être ajoutés à cette classe.
     * Retourne les étudiants ayant une inscription active pour l'année courante
     * mais qui ne sont PAS dans cette classe.
     */
    public function searchAvailableStudents(Request $request, ESBTPClasse $classe)
    {
        try {
            $result = $this->studentService->searchAvailableStudents(
                $classe,
                (string) $request->input('q', ''),
            );

            return response()->json([
                'success' => true,
                'etudiants' => $result['etudiants'],
                'count' => $result['count'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            \Log::error('Erreur searchAvailableStudents: ' . $e->getMessage(), [
                'classe_id' => $classe->id,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Ajoute des étudiants à cette classe en mettant à jour leurs inscriptions.
     */
    public function addStudents(AddStudentsRequest $request, ESBTPClasse $classe)
    {
        try {
            $result = $this->studentService->addStudents(
                $classe,
                $request->validated()['etudiant_ids'],
            );

            return response()->json([
                'success' => true,
                'message' => "{$result['added']} étudiant(s) ajouté(s) à la classe {$classe->name}.",
                'added' => $result['added'],
                'errors' => $result['errors'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            \Log::error('Erreur addStudents: ' . $e->getMessage(), [
                'classe_id' => $classe->id,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Retire des étudiants de cette classe.
     * Deux modes : transfert vers une autre classe OU marquage comme non affecté.
     */
    public function removeStudents(RemoveStudentsRequest $request, ESBTPClasse $classe)
    {
        try {
            $validated = $request->validated();
            $result = $this->studentService->removeStudents(
                $classe,
                $validated['etudiant_ids'],
                $validated['destination_classe_id'] ?? null,
            );

            return response()->json([
                'success' => true,
                'message' => "{$result['removed']} étudiant(s) {$result['action_message']}.",
                'removed' => $result['removed'],
                'errors' => $result['errors'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            \Log::error('Erreur removeStudents: ' . $e->getMessage(), [
                'classe_id' => $classe->id,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Vérifie les données académiques des étudiants avant retrait/transfert.
     */
    public function checkStudentData(Request $request, ESBTPClasse $classe)
    {
        $request->validate([
            'etudiant_ids' => 'required|array|min:1',
            'etudiant_ids.*' => 'integer',
        ]);

        $result = $this->studentService->checkStudentData(
            $classe,
            $request->input('etudiant_ids'),
        );

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Retourne le HTML de la table étudiants pour rafraîchissement AJAX.
     */
    public function studentTableHtml(ESBTPClasse $classe)
    {
        try {
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if ($anneeCourante) {
                $classe->load([
                    'etudiants' => function ($query) use ($anneeCourante, $classe) {
                        $query->distinct()
                            ->whereHas('inscriptions', function ($inscriptionQuery) use ($anneeCourante, $classe) {
                                $inscriptionQuery
                                    ->where('annee_universitaire_id', $anneeCourante->id)
                                    ->where('status', 'active')
                                    ->where('workflow_step', 'etudiant_cree')
                                    ->where('classe_id', $classe->id);
                            })
                            ->orderBy('nom')
                            ->orderBy('prenoms');
                    },
                ]);
            } else {
                $classe->load(['etudiants']);
            }

            $html = view('esbtp.classes.partials.student-table-rows', [
                'classe' => $classe,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'count' => $classe->etudiants->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur studentTableHtml: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Synchroniser le systeme academique de toutes les classes
     * depuis leur niveau d'etudes (Licence/Master/Doctorat → LMD, sinon BTS).
     */
    public function syncSystemeAcademique()
    {
        $service = app(\App\Services\ClasseManagementService::class);
        $result = $service->syncSystemeAcademique();

        if ($result['updated'] > 0) {
            $names = collect($result['details'])->pluck('name')->implode(', ');
            return redirect()->back()->with('success',
                "{$result['updated']} classe(s) mise(s) à jour : {$names}"
            );
        }

        return redirect()->back()->with('info',
            "Toutes les {$result['total']} classe(s) sont déjà correctement configurées."
        );
    }
}
