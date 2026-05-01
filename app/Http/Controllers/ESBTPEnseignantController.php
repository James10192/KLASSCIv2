<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPTeacherAvailability;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ESBTPEnseignantController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the teachers.
     */
    public function index(Request $request)
    {
        $query = ESBTPTeacher::with("user");

        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }

        if ($request->filled("specialization")) {
            $query->where("specialization", "like", "%" . $request->specialization . "%");
        }

        if ($request->filled("search")) {
            $search = $request->search;
            $query->whereHas("user", function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")
                  ->orWhere("email", "like", "%{$search}%");
            });
        }

        $teachers = $query->paginate(15);

        $specializations = ESBTPTeacher::distinct()->pluck("specialization")->filter();

        $stats = [
            "total" => ESBTPTeacher::count(),
            "active" => ESBTPTeacher::where("status", "active")->count(),
            "inactive" => ESBTPTeacher::where("status", "inactive")->count(),
            "permanent" => 0,
            "temporary" => 0,
        ];

        if (Schema::hasTable("esbtp_enseignant_profiles")) {
            $stats["permanent"] = DB::table("esbtp_enseignant_profiles")
                ->where("type_contrat", "permanent")->count();
            $stats["temporary"] = DB::table("esbtp_enseignant_profiles")
                ->where("type_contrat", "vacataire")->count();
        }

        return view(
            "esbtp.enseignants.index",
            compact("teachers", "specializations", "stats"),
        );
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
    {
        return view(
            "esbtp.enseignants.create",
            [
                "titres_academiques" => $this->titresAcademiques(),
                "grades_academiques" => $this->gradesAcademiques(),
            ],
        );
    }

    private function titresAcademiques(): array
    {
        return [
            "M." => "Monsieur",
            "Mme" => "Madame",
            "Dr." => "Docteur",
            "Pr." => "Professeur",
        ];
    }

    private function gradesAcademiques(): array
    {
        return [
            "assistant" => "Assistant",
            "maitre_assistant" => "Maître Assistant",
            "maitre_conferences" => "Maître de Conférences",
            "professeur" => "Professeur",
        ];
    }

    /**
     * Map le champ unifié "regime" vers les colonnes legacy
     * type_contrat + statut_emploi de esbtp_enseignant_profiles.
     */
    private function regimeToContrat(string $regime): array
    {
        return match ($regime) {
            'permanent' => ['type_contrat' => 'permanent', 'statut_emploi' => 'temps_plein'],
            'consultant' => ['type_contrat' => 'consultant', 'statut_emploi' => 'temps_partiel'],
            default => ['type_contrat' => 'vacataire', 'statut_emploi' => 'vacations'],
        };
    }

    /**
     * Mapping inverse pour rétrocompat : type_contrat legacy → regime canonique.
     */
    private function normalizeRegime(string $typeContrat): string
    {
        return match ($typeContrat) {
            'permanent' => 'permanent',
            'consultant' => 'consultant',
            default => 'vacataire',
        };
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "name" => "required|string|max:255",
            "phone" => "required|string|max:20",
            "specialization" => "required|string|max:255",

            "email" => "nullable|string|email|max:255|unique:users,email",
            "titre_academique" => "nullable|string|max:10",
            "grade_academique" => "nullable|string|max:50",

            "regime" => "nullable|in:permanent,vacataire,consultant",
            "taux_horaire" => "nullable|numeric|min:0",
            "charge_horaire_max_semaine" => "nullable|integer|min:1|max:60",
            "date_debut_activite" => "nullable|date",

            "diplome_principal" => "nullable|string|max:255",
            "universite_diplome" => "nullable|string|max:255",
            "annee_diplome" => "nullable|integer|min:1950|max:" . date("Y"),
        ]);

        $regime = $request->input('regime') ?: 'vacataire';
        $contrat = $this->regimeToContrat($regime);

        DB::beginTransaction();

        try {
            $user = $this->userService->createUserWithAutoCredentials(
                [
                    "name" => $request->name,
                    "email" => $request->email ?: null,
                    "phone" => $request->phone,
                ],
                "enseignant",
            );
            $user->assignRole("enseignant");

            $teacher = ESBTPTeacher::create([
                "user_id" => $user->id,
                "matricule" => $this->generateMatricule(),
                "title" => $request->titre_academique,
                "specialization" => $request->specialization,
                "grade" => $request->grade_academique,
                "status" => "active",
                "teaching_hours_due" => $regime === 'permanent'
                    ? ($request->charge_horaire_max_semaine ?? 18)
                    : 0,
                "created_by" => auth()->id(),
            ]);

            if (Schema::hasTable("esbtp_enseignant_profiles")) {
                $dateDebut = $request->date_debut_activite
                    ? Carbon::parse($request->date_debut_activite)->format('Y-m-d')
                    : now()->toDateString();

                DB::table("esbtp_enseignant_profiles")->insert(array_filter([
                    "user_id" => $user->id,
                    "matricule_enseignant" => $teacher->matricule,
                    "titre_academique" => $request->titre_academique,
                    "grade_academique" => $request->grade_academique,
                    "diplome_principal" => $request->diplome_principal,
                    "universite_diplome" => $request->universite_diplome,
                    "annee_diplome" => $request->annee_diplome,
                    "charge_horaire_max_semaine" => $request->charge_horaire_max_semaine ?? 18,
                    "type_contrat" => $contrat['type_contrat'],
                    "statut_emploi" => $contrat['statut_emploi'],
                    "date_embauche" => $dateDebut,
                    "taux_horaire" => $request->taux_horaire,
                    "statut" => "actif",
                    "created_by" => auth()->id(),
                    "created_at" => now(),
                    "updated_at" => now(),
                ], fn($v) => $v !== null));
            }

            DB::commit();

            $credentials = $this->userService->getCredentialsInfo(
                $user->username,
                $this->userService->generateDefaultPassword(),
            );

            $redirectRoute = auth()->user()->can('personnel.manage')
                ? "esbtp.personnel.unified.index"
                : "esbtp.enseignants.index";

            return redirect()
                ->route($redirectRoute)
                ->with("success", "Enseignant {$user->name} créé avec succès.")
                ->with("credentials", $credentials)
                ->with("created_teacher_id", $teacher->id);
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()
                ->back()
                ->with("error", "Erreur lors de la création de l'enseignant : " . $e->getMessage())
                ->withInput();
        }
    }

    public function quickStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "email" => "nullable|string|email|max:255|unique:users,email",
            "phone" => "nullable|string|max:20",
            "titre_academique" => "nullable|string|max:10",
            "grade_academique" => "nullable|string|max:50",
            "specialization" => "required|string|max:255",
            "regime" =>
                "nullable|in:permanent,vacataire,consultant",
            "type_contrat" =>
                "nullable|in:permanent,temporaire,vacataire,consultant",
            "statut_emploi" =>
                "nullable|in:temps_plein,temps_partiel,vacations",
            "date_debut_activite" => "nullable|date",
            "date_embauche" => "nullable|date",
            "taux_horaire" => "nullable|numeric|min:0",
            "charge_horaire_max_semaine" => "nullable|integer|min:1|max:60",
            "planification_id" =>
                "nullable|exists:esbtp_planifications_academiques,id",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        DB::beginTransaction();

        try {
            $user = $this->userService->createUserWithAutoCredentials(
                [
                    "name" => $request->name,
                    "email" => $request->email ?: null,
                    "phone" => $request->phone,
                ],
                "enseignant",
            );

            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'enseignant']);
            $user->assignRole($role);

            // Régime unifié (préféré) ou retombée sur type_contrat legacy.
            $regime = $request->input('regime')
                ?: ($request->input('type_contrat') ? $this->normalizeRegime($request->input('type_contrat')) : 'vacataire');
            $contrat = $this->regimeToContrat($regime);

            $teacher = ESBTPTeacher::create([
                "user_id" => $user->id,
                "matricule" => $this->generateMatricule(),
                "title" => $request->titre_academique,
                "specialization" => $request->specialization,
                "grade" => $request->grade_academique,
                "status" => "active",
                "teaching_hours_due" => $regime === 'permanent'
                    ? ($request->charge_horaire_max_semaine ?? 18)
                    : 0,
                "created_by" => auth()->id(),
            ]);

            if (Schema::hasTable("esbtp_enseignant_profiles")) {
                try {
                    $dateDebut = $request->date_debut_activite
                        ?: $request->date_embauche;
                    $dateDebut = $dateDebut
                        ? Carbon::parse($dateDebut)->format('Y-m-d')
                        : now()->toDateString();

                    $profileData = [
                        "user_id" => $user->id,
                        "matricule_enseignant" => $teacher->matricule,
                        "titre_academique" => $request->titre_academique,
                        "grade_academique" => $request->grade_academique,
                        "charge_horaire_max_semaine" =>
                            $request->charge_horaire_max_semaine ?? 18,
                        "type_contrat" => $contrat['type_contrat'],
                        "statut_emploi" => $contrat['statut_emploi'],
                        "date_embauche" => $dateDebut,
                        "taux_horaire" => $request->taux_horaire,
                        "statut" => "actif",
                        "created_by" => auth()->id(),
                        "created_at" => now(),
                        "updated_at" => now(),
                    ];

                    // Colonnes optionnelles — vérifier leur existence avant insertion
                    $optionalColumns = [
                        'diplome_principal' => null,
                        'universite_diplome' => null,
                        'annee_diplome' => null,
                        'annees_experience_enseignement' => 0,
                        'annees_experience_professionnelle' => 0,
                        'fin_contrat' => null,
                        'taux_horaire' => null,
                        'accepte_enseignement_distance' => false,
                        'accepte_cours_weekend' => false,
                        'accepte_cours_soir' => false,
                        'motivation' => null,
                        'objectifs_pedagogiques' => null,
                    ];

                    foreach ($optionalColumns as $col => $default) {
                        if (Schema::hasColumn('esbtp_enseignant_profiles', $col)) {
                            $profileData[$col] = $default;
                        }
                    }

                    DB::table("esbtp_enseignant_profiles")->insert($profileData);
                } catch (\Exception $profileEx) {
                    \Log::warning(
                        "Profil enseignant non créé (non-bloquant): " . $profileEx->getMessage(),
                    );
                }
            }

            if ($request->has("availability")) {
                ESBTPTeacherAvailability::where("teacher_id", $teacher->id)->delete();

                foreach ($request->availability as $key => $status) {
                    if ($status !== "unavailable") {
                        [$dayIndex, $hour] = explode("_", $key);
                        $dayIndex = (int) $dayIndex;
                        $hour = (int) $hour;

                        $startTime = sprintf("%02d:00", $hour);
                        $endTime = sprintf("%02d:00", $hour + 1);

                        ESBTPTeacherAvailability::create([
                            "teacher_id" => $teacher->id,
                            "day_of_week" => $dayIndex,
                            "start_time" => $startTime,
                            "end_time" => $endTime,
                            "availability_type" => $status,
                        ]);
                    }
                }
            }

            if ($request->filled("planification_id")) {
                $planification = ESBTPPlanificationAcademique::find(
                    $request->planification_id,
                );
                if ($planification) {
                    $exists = DB::table("esbtp_planification_teachers")
                        ->where("planification_id", $planification->id)
                        ->where("teacher_id", $teacher->id)
                        ->exists();

                    if (!$exists) {
                        DB::table("esbtp_planification_teachers")->insert([
                            "planification_id" => $planification->id,
                            "teacher_id" => $teacher->id,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);
                    }

                    if (
                        !$planification->enseignant_principal_id &&
                        $teacher->user_id
                    ) {
                        $planification->update([
                            "enseignant_principal_id" => $teacher->user_id,
                        ]);
                    }
                }
            }

            $teacher->loadMissing(["user", "availabilities"]);
            $availabilityData = $this->prepareAvailabilityData($teacher);

            DB::commit();

            return response()->json([
                "success" => true,
                "teacher" => $teacher,
                "availability" => $availabilityData,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error(
                "Erreur création enseignant rapide: " . $e->getMessage(),
                [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ],
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => $e->getMessage(),
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                ],
                500,
            );
        }
    }

    /**
     * Check for duplicate teachers based on name and specialization.
     */
    public function duplicates(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255",
            "specialization" => "nullable|string|max:255",
        ]);

        $name = $request->input("name");
        $specialization = $request->input("specialization");

        // Simple duplicate detection based on similar name and specialization
        $duplicates = ESBTPTeacher::with("user")
            ->whereHas("user", function ($query) use ($name) {
                // Similar name detection (simple LIKE for now)
                $query->where("name", "LIKE", "%" . $name . "%");
            })
            ->when($specialization, function ($query) use ($specialization) {
                $query->where(
                    "specialization",
                    "LIKE",
                    "%" . $specialization . "%",
                );
            })
            ->limit(10)
            ->get()
            ->map(function ($teacher) {
                return [
                    "id" => $teacher->id,
                    "name" => $teacher->user->name ?? "",
                    "email" => $teacher->user->email ?? "",
                    "specialization" => $teacher->specialization,
                    "matricule" => $teacher->matricule,
                    "status" => $teacher->status,
                    "show_url" => route("esbtp.enseignants.show", $teacher->id),
                ];
            });

        return response()->json([
            "duplicates" => $duplicates,
        ]);
    }

    /**
     * Display the specified teacher.
     */
    public function show(Request $request, ESBTPTeacher $enseignant)
    {
        $enseignant->load([
            "user",
            "createdBy",
            "updatedBy",
            "availabilities",
        ]);

        $anneeCourante = ESBTPAnneeUniversitaire::where(
            "is_current",
            true,
        )->first();
        $periode = $request->input("periode", "annee");
        $teachingPlanning = $this->buildPlanningPourEnseignant(
            $enseignant,
            $anneeCourante,
            $periode,
        );

        // Récupérer les informations additionnelles si elles existent
        $profileData = null;
        if (Schema::hasTable("esbtp_enseignant_profiles")) {
            $profileData = DB::table("esbtp_enseignant_profiles")
                ->where("user_id", $enseignant->user_id)
                ->first();
        }

        // Préparer les données de disponibilité réelles
        $realAvailability = $this->prepareAvailabilityData($enseignant);

        // Passer $enseignant en tant que $teacher pour la compatibilité avec la vue
        $teacher = $enseignant;
        return view(
            "esbtp.enseignants.show",
            compact(
                "teacher",
                "profileData",
                "realAvailability",
                "teachingPlanning",
                "anneeCourante",
                "periode",
            ),
        );
    }

    private function buildPlanningPourEnseignant(
        ESBTPTeacher $enseignant,
        ?ESBTPAnneeUniversitaire $anneeCourante,
        string $periode,
    ) {
        if (!$anneeCourante) {
            return [
                "classes" => collect(),
                "stats" => [
                    "classes" => 0,
                    "heures_planifiees" => 0,
                    "heures_realisees" => 0,
                    "nb_seances" => 0,
                    "taux_realisation" => 0,
                ],
            ];
        }

        $seancesQuery = ESBTPSeanceCours::query()
            ->join(
                "esbtp_emploi_temps",
                "esbtp_seance_cours.emploi_temps_id",
                "=",
                "esbtp_emploi_temps.id",
            )
            ->leftJoin(
                DB::raw('(
                SELECT ta1.course_id, ta1.status
                FROM esbtp_teacher_attendances ta1
                INNER JOIN (
                    SELECT course_id,
                           MAX(CASE
                               WHEN DATE(date) = CURDATE() THEN CONCAT("1_", created_at)
                               WHEN DATE(date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = course_id) THEN CONCAT("2_", created_at)
                               ELSE CONCAT("3_", created_at)
                           END) as max_priority
                    FROM esbtp_teacher_attendances
                    WHERE type = "start"
                    GROUP BY course_id
                ) ta2 ON ta1.course_id = ta2.course_id
                     AND CONCAT(
                         CASE
                             WHEN DATE(ta1.date) = CURDATE() THEN "1_"
                             WHEN DATE(ta1.date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = ta1.course_id) THEN "2_"
                             ELSE "3_"
                         END, ta1.created_at
                     ) = ta2.max_priority
                WHERE ta1.type = "start"
            ) as latest_attendance'),
                "latest_attendance.course_id",
                "=",
                "esbtp_seance_cours.id",
            )
            ->where(function ($query) {
                $query
                    ->whereNull("latest_attendance.status")
                    ->orWhere("latest_attendance.status", "!=", "absent");
            })
            ->where("esbtp_seance_cours.teacher_id", $enseignant->id)
            ->where(
                "esbtp_emploi_temps.annee_universitaire_id",
                $anneeCourante->id,
            )
            ->select(
                "esbtp_seance_cours.matiere_id",
                "esbtp_seance_cours.classe_id",
                DB::raw("COUNT(DISTINCT esbtp_seance_cours.id) as nb_seances"),
                DB::raw(
                    "SUM(TIME_TO_SEC(TIMEDIFF(esbtp_seance_cours.heure_fin, esbtp_seance_cours.heure_debut))/3600) as total_heures",
                ),
            )
            ->groupBy(
                "esbtp_seance_cours.matiere_id",
                "esbtp_seance_cours.classe_id",
            )
            ->whereRaw("(
                esbtp_seance_cours.date_seance < CURDATE()
                OR (
                    esbtp_seance_cours.date_seance = CURDATE()
                    AND TIME(esbtp_seance_cours.heure_fin) <= TIME(NOW())
                )
            )");

        if ($periode === "semestre1") {
            $seancesQuery->whereIn("esbtp_emploi_temps.semestre", [
                "1",
                1,
                "S1",
                "Semestre 1",
                "semestre1",
                "SEMESTRE 1",
                "Semestre1",
                "s1",
            ]);
        } elseif ($periode === "semestre2") {
            $seancesQuery->whereIn("esbtp_emploi_temps.semestre", [
                "2",
                2,
                "S2",
                "Semestre 2",
                "semestre2",
                "SEMESTRE 2",
                "Semestre2",
                "s2",
            ]);
        }

        $seancesRealisees = $seancesQuery->get();
        $classIds = $seancesRealisees->pluck("classe_id")->filter()->unique();

        $classes = ESBTPClasse::with(["filiere", "niveau"])
            ->whereIn("id", $classIds)
            ->get()
            ->keyBy("id");

        $comboKeys = $classes
            ->map(function ($classe) {
                return $classe->filiere_id . "_" . $classe->niveau_etude_id;
            })
            ->unique();

        $planificationsQuery = ESBTPPlanificationAcademique::with(["matiere"])
            ->where("annee_universitaire_id", $anneeCourante->id)
            ->whereIn("filiere_id", $classes->pluck("filiere_id")->unique())
            ->whereIn(
                "niveau_etude_id",
                $classes->pluck("niveau_etude_id")->unique(),
            )
            ->select(
                "matiere_id",
                "filiere_id",
                "niveau_etude_id",
                DB::raw("SUM(volume_horaire_total) as heures_planifiees"),
            )
            ->groupBy("matiere_id", "filiere_id", "niveau_etude_id");

        if ($periode === "semestre1") {
            $planificationsQuery->where(function ($query) {
                $query->where("semestre", 1)->orWhereNull("semestre");
            });
        } elseif ($periode === "semestre2") {
            $planificationsQuery->where(function ($query) {
                $query->where("semestre", 2)->orWhereNull("semestre");
            });
        }

        $planifications = $planificationsQuery->get();

        $planificationsByCombo = $planifications->groupBy(function (
            $planification,
        ) {
            return $planification->filiere_id .
                "_" .
                $planification->niveau_etude_id;
        });

        $matiereIds = $planifications
            ->pluck("matiere_id")
            ->merge($seancesRealisees->pluck("matiere_id"))
            ->filter()
            ->unique();
        $matieres = ESBTPMatiere::whereIn("id", $matiereIds)
            ->get()
            ->keyBy("id");

        $classesData = $classes
            ->values()
            ->map(function ($classe) use (
                $seancesRealisees,
                $planificationsByCombo,
                $matieres,
            ) {
                $comboKey =
                    $classe->filiere_id . "_" . $classe->niveau_etude_id;
                $planificationsCombo = $planificationsByCombo
                    ->get($comboKey, collect())
                    ->keyBy("matiere_id");
                $seancesClasse = $seancesRealisees->where(
                    "classe_id",
                    $classe->id,
                );

                $matiereIdsClasse = $seancesClasse
                    ->pluck("matiere_id")
                    ->filter()
                    ->unique();

                $matieresData = $matiereIdsClasse
                    ->map(function ($matiereId) use (
                        $planificationsCombo,
                        $seancesClasse,
                        $matieres,
                    ) {
                        $planification = $planificationsCombo->get($matiereId);
                        $heuresPlanifiees = $planification
                            ? (float) $planification->heures_planifiees
                            : 0;

                        $seancesMatiere = $seancesClasse->where(
                            "matiere_id",
                            $matiereId,
                        );
                        $totalHeures = (float) $seancesMatiere->sum(
                            "total_heures",
                        );
                        $nbSeances = (int) $seancesMatiere->sum("nb_seances");

                        $heuresRestantes = max(
                            0,
                            $heuresPlanifiees - $totalHeures,
                        );

                        return [
                            "matiere" => $matieres->get($matiereId),
                            "heures_planifiees" => round($heuresPlanifiees, 2),
                            "heures_realisees" => round($totalHeures, 2),
                            "heures_restantes" => round($heuresRestantes, 2),
                            "nb_seances" => $nbSeances,
                            "pourcentage_realise" =>
                                $heuresPlanifiees > 0
                                    ? round(
                                        ($totalHeures / $heuresPlanifiees) *
                                            100,
                                        1,
                                    )
                                    : 0,
                            "est_configure" => $heuresPlanifiees > 0,
                        ];
                    })
                    ->filter()
                    ->sortBy(function ($item) {
                        return $item["matiere"]->name ?? "";
                    })
                    ->values();

                $totalPlanifiees = $matieresData->sum("heures_planifiees");
                $totalRealisees = $matieresData->sum("heures_realisees");
                $totalSeances = $matieresData->sum("nb_seances");
                $taux =
                    $totalPlanifiees > 0
                        ? round(($totalRealisees / $totalPlanifiees) * 100, 1)
                        : 0;

                return [
                    "classe" => $classe,
                    "matieres" => $matieresData,
                    "stats" => [
                        "heures_planifiees" => round($totalPlanifiees, 2),
                        "heures_realisees" => round($totalRealisees, 2),
                        "nb_seances" => (int) $totalSeances,
                        "taux_realisation" => $taux,
                    ],
                ];
            })
            ->values();

        $totalPlanifiees = $classesData->sum(function ($item) {
            return $item["stats"]["heures_planifiees"] ?? 0;
        });
        $totalRealisees = $classesData->sum(function ($item) {
            return $item["stats"]["heures_realisees"] ?? 0;
        });
        $totalSeances = $classesData->sum(function ($item) {
            return $item["stats"]["nb_seances"] ?? 0;
        });
        $tauxGlobal =
            $totalPlanifiees > 0
                ? round(($totalRealisees / $totalPlanifiees) * 100, 1)
                : 0;

        return [
            "classes" => $classesData,
            "stats" => [
                "classes" => $classesData->count(),
                "heures_planifiees" => round($totalPlanifiees, 2),
                "heures_realisees" => round($totalRealisees, 2),
                "nb_seances" => (int) $totalSeances,
                "taux_realisation" => $tauxGlobal,
            ],
        ];
    }

    /**
     * Préparer les données de disponibilité pour l'affichage
     */
    private function prepareAvailabilityData($teacher)
    {
        // Utiliser des créneaux par heure comme la page EDIT pour cohérence
        $hours = range(8, 18); // 8h à 18h = 11 heures
        $days = [
            "monday",
            "tuesday",
            "wednesday",
            "thursday",
            "friday",
            "saturday",
            "sunday",
        ];

        // Initialiser avec 'unavailable' par défaut
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), "unavailable");
        }

        // Remplir avec les vraies données - traitement par heure
        foreach ($teacher->availabilities as $avail) {
            $dayName = $days[$avail->day_of_week] ?? null;

            // Parser l'heure de début et de fin
            if ($avail->start_time instanceof \Carbon\Carbon) {
                $startHour = $avail->start_time->hour;
                $endHour = $avail->end_time->hour;
            } elseif (is_string($avail->start_time)) {
                $startHour = (int) substr($avail->start_time, 0, 2);
                $endHour = (int) substr($avail->end_time, 0, 2);
            } else {
                $startHour = (int) substr((string) $avail->start_time, 0, 2);
                $endHour = (int) substr((string) $avail->end_time, 0, 2);
            }

            if ($dayName) {
                for ($hour = $startHour; $hour < $endHour; $hour++) {
                    $hourIndex = $hour - 8;
                    if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                        $availability[$dayName][$hourIndex] = $avail->availability_type;
                    }
                }
            }
        }

        return $availability;
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(ESBTPTeacher $enseignant)
    {
        $enseignant->load(["user", "availabilities"]);

        $profileData = null;
        if (Schema::hasTable("esbtp_enseignant_profiles")) {
            $profileData = DB::table("esbtp_enseignant_profiles")
                ->where("user_id", $enseignant->user_id)
                ->first();
        }

        // Régime canonique reconstruit depuis le profil legacy.
        $currentRegime = $profileData && !empty($profileData->type_contrat)
            ? $this->normalizeRegime($profileData->type_contrat)
            : 'vacataire';

        $availabilityData = $this->prepareAvailabilityData($enseignant);

        // Compatibilité vue (variable $teacher).
        $teacher = $enseignant;

        return view(
            "esbtp.enseignants.edit",
            [
                "teacher" => $teacher,
                "profileData" => $profileData,
                "currentRegime" => $currentRegime,
                "availabilityData" => $availabilityData,
                "titres_academiques" => $this->titresAcademiques(),
                "grades_academiques" => $this->gradesAcademiques(),
            ],
        );
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, ESBTPTeacher $enseignant)
    {
        $validated = $request->validate([
            "name" => "required|string|max:255",
            "specialization" => "required|string|max:255",

            "phone" => "nullable|string|max:20",
            "email" => "nullable|string|email|max:255|unique:users,email," . $enseignant->user_id,
            "titre_academique" => "nullable|string|max:10",
            "grade_academique" => "nullable|string|max:50",

            "regime" => "nullable|in:permanent,vacataire,consultant",
            "taux_horaire" => "nullable|numeric|min:0",
            "charge_horaire_max_semaine" => "nullable|integer|min:1|max:60",
            "date_debut_activite" => "nullable|date",

            "diplome_principal" => "nullable|string|max:255",
            "universite_diplome" => "nullable|string|max:255",
            "annee_diplome" => "nullable|integer|min:1950|max:" . date("Y"),

            "bio" => "nullable|string|max:1000",
            "website" => "nullable|url",
            "status" => "nullable|in:active,inactive",

            "availability" => "nullable|array",
        ]);

        $regime = $request->input('regime') ?: 'vacataire';
        $contrat = $this->regimeToContrat($regime);

        DB::beginTransaction();

        try {
            $enseignant->user->update([
                "name" => $request->name,
                "email" => $request->email ?: null,
                "phone" => $request->phone,
            ]);

            $enseignant->update([
                "title" => $request->titre_academique,
                "specialization" => $request->specialization,
                "grade" => $request->grade_academique,
                "bio" => $request->bio,
                "website" => $request->website,
                "status" => $request->status ?? $enseignant->status,
                "teaching_hours_due" => $regime === 'permanent'
                    ? ($request->charge_horaire_max_semaine ?? $enseignant->teaching_hours_due ?? 18)
                    : 0,
                "updated_by" => auth()->id(),
            ]);

            // Mise à jour profil étendu (legacy table conservée jusqu'à PR fusion).
            if (Schema::hasTable("esbtp_enseignant_profiles")) {
                $dateDebut = $request->date_debut_activite
                    ? Carbon::parse($request->date_debut_activite)->format('Y-m-d')
                    : null;

                $profileUpdate = array_filter([
                    "titre_academique" => $request->titre_academique,
                    "grade_academique" => $request->grade_academique,
                    "diplome_principal" => $request->diplome_principal,
                    "universite_diplome" => $request->universite_diplome,
                    "annee_diplome" => $request->annee_diplome,
                    "type_contrat" => $contrat['type_contrat'],
                    "statut_emploi" => $contrat['statut_emploi'],
                    "taux_horaire" => $request->taux_horaire,
                    "charge_horaire_max_semaine" => $request->charge_horaire_max_semaine,
                    "date_embauche" => $dateDebut,
                    "updated_at" => now(),
                ], fn($v) => $v !== null);

                $exists = DB::table("esbtp_enseignant_profiles")
                    ->where("user_id", $enseignant->user_id)
                    ->exists();

                if ($exists) {
                    DB::table("esbtp_enseignant_profiles")
                        ->where("user_id", $enseignant->user_id)
                        ->update($profileUpdate);
                } else {
                    DB::table("esbtp_enseignant_profiles")->insert(array_merge($profileUpdate, [
                        "user_id" => $enseignant->user_id,
                        "matricule_enseignant" => $enseignant->matricule,
                        "statut" => "actif",
                        "created_by" => auth()->id(),
                        "created_at" => now(),
                    ]));
                }
            }

            // Disponibilités : reset + recréation si fournies.
            if ($request->has("availability")) {
                $enseignant->availabilities()->delete();

                foreach ($request->availability as $key => $status) {
                    if ($status === "unavailable") {
                        continue;
                    }
                    [$dayIndex, $hour] = explode("_", $key);
                    ESBTPTeacherAvailability::create([
                        "teacher_id" => $enseignant->id,
                        "day_of_week" => (int) $dayIndex,
                        "start_time" => sprintf("%02d:00", (int) $hour),
                        "end_time" => sprintf("%02d:00", (int) $hour + 1),
                        "availability_type" => $status,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route("esbtp.enseignants.show", $enseignant->id)
                ->with("success", "Enseignant mis à jour avec succès.");
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()
                ->back()
                ->with("error", "Erreur lors de la mise à jour : " . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(ESBTPTeacher $teacher)
    {
        // Empêcher la suppression de son propre compte
        if ($teacher->user_id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            DB::beginTransaction();

            // Marquer l'enseignant comme inactif (soft deactivation)
            $teacher->update(['status' => 'inactive']);

            // Désactiver le compte utilisateur associé
            if ($teacher->user) {
                $teacher->user->update([
                    'is_active' => false,
                    'email' => $teacher->user->email . '_deleted_' . time(),
                ]);
                $teacher->user->removeRole('enseignant');
                $teacher->user->removeRole('teacher');
            }

            DB::commit();

            return redirect()
                ->route("esbtp.personnel.unified.index")
                ->with("success", "Enseignant désactivé avec succès");
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Une erreur est survenue lors de la suppression: " .
                        $e->getMessage(),
                );
        }
    }

    /**
     * Generate a unique matricule for the teacher.
     */
    private function generateMatricule()
    {
        $year = date("Y");
        $lastTeacher = ESBTPTeacher::whereYear("created_at", $year)
            ->orderBy("id", "desc")
            ->first();

        $sequence = $lastTeacher
            ? (int) substr($lastTeacher->matricule, -4) + 1
            : 1;

        return sprintf("ENS-%s-%04d", $year, $sequence);
    }

    /**
     * Toggle teacher status.
     */
    public function toggleStatus(Request $request, ESBTPTeacher $teacher)
    {
        $newStatus = $teacher->status === "active" ? "inactive" : "active";

        $teacher->update([
            "status" => $newStatus,
            "updated_by" => auth()->id(),
        ]);

        // Si c'est une requête AJAX, retourner du JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                "success" => true,
                "message" => "Statut mis à jour avec succès",
                "new_status" => $newStatus,
            ]);
        }

        return redirect()
            ->back()
            ->with("success", "Statut mis à jour avec succès");
    }

    /**
     * Afficher la page de gestion des matières d'un enseignant
     */
    public function matieres(ESBTPTeacher $teacher)
    {
        $this->authorize("teachers.edit");

        // Récupérer toutes les matières disponibles
        $matieres = ESBTPMatiere::with(["niveauEtude", "filieres"])
            ->orderBy("name")
            ->get();

        // Récupérer les matières actuellement assignées à l'enseignant
        $matieresAssignees = $teacher->user
            ->matieres()
            ->with(["niveauEtude", "filieres"])
            ->get();

        return view(
            "esbtp.enseignants.matieres",
            compact("teacher", "matieres", "matieresAssignees"),
        );
    }

    /**
     * Assigner/Désassigner des matières à un enseignant
     */
    public function assignMatieres(Request $request, ESBTPTeacher $teacher)
    {
        $this->authorize("teachers.edit");

        $request->validate([
            "matieres" => "array",
            "matieres.*" => "exists:esbtp_matieres,id",
        ]);

        DB::beginTransaction();

        try {
            // Récupérer l'année universitaire actuelle
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where(
                "is_active",
                true,
            )->first();

            if (!$anneeUniversitaire) {
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        "Aucune année universitaire active trouvée.",
                    );
            }

            // Préparer les données pour la table pivot
            $matieresData = [];
            foreach ($request->matieres ?? [] as $matiereId) {
                $matieresData[$matiereId] = [
                    "annee_universitaire_id" => $anneeUniversitaire->id,
                    "is_active" => true,
                    "created_at" => now(),
                    "updated_at" => now(),
                ];
            }

            // Synchroniser les matières (supprime les anciennes et ajoute les nouvelles)
            $teacher->user->matieres()->syncWithoutDetaching($matieresData);

            DB::commit();

            return redirect()
                ->back()
                ->with("success", "Matières assignées avec succès.");
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'assignation : ' . $e->getMessage(),
                );
        }
    }

    /**
     * Afficher la page de modification groupée des disponibilités
     */
    public function bulkAvailability(Request $request)
    {
        // Parser les IDs depuis la query string
        $ids = $request->input("ids", []);
        if (is_string($ids)) {
            $ids = array_filter(explode(",", $ids));
        }

        // Normaliser et valider les IDs
        $ids = collect($ids)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        // Rediriger si pas d'IDs fournis
        if (empty($ids)) {
            return redirect()
                ->route("esbtp.enseignants.index")
                ->with("error", "Sélectionnez au moins un enseignant.");
        }

        // Récupérer les enseignants sélectionnés
        $enseignants = ESBTPTeacher::with(["user", "availabilities"])
            ->whereIn("id", $ids)
            ->orderBy("id")
            ->get();

        // Construire les données de vue pour chaque enseignant
        $enseignantsData = $enseignants->map(function ($enseignant) {
            return $this->buildAvailabilityViewData($enseignant);
        });

        return view(
            "esbtp.enseignants.bulk-availability",
            compact("enseignants", "enseignantsData"),
        );
    }

    /**
     * Retourner le HTML d'un bloc de disponibilité pour un enseignant (AJAX)
     */
    public function availabilitySection(ESBTPTeacher $enseignant)
    {
        $enseignant->load(["user", "availabilities"]);
        $data = $this->buildAvailabilityViewData($enseignant);

        $html = view(
            "esbtp.enseignants.partials.availability-block",
            $data,
        )->render();

        return response()->json([
            "enseignant_id" => $enseignant->id,
            "html" => $html,
        ]);
    }

    /**
     * Retourner les données de disponibilité en JSON (pour polling AJAX depuis seances.create)
     */
    public function availabilityData(ESBTPTeacher $enseignant)
    {
        $enseignant->load(['availabilities']);
        $data = $this->buildAvailabilityViewData($enseignant);

        $updatedAt = $enseignant->availabilities->max('updated_at');

        return response()->json([
            'success' => true,
            'data' => $data['availability'],
            'updated_at' => $updatedAt ? $updatedAt->timestamp : now()->timestamp,
        ]);
    }

    /**
     * Construire les données de disponibilité pour la vue
     */
    private function buildAvailabilityViewData(ESBTPTeacher $enseignant): array
    {
        $hours = range(8, 18); // 8h à 18h = 11 heures
        $days = [
            "monday",
            "tuesday",
            "wednesday",
            "thursday",
            "friday",
            "saturday",
            "sunday",
        ];
        $joursNoms = [
            "monday" => "Lundi",
            "tuesday" => "Mardi",
            "wednesday" => "Mercredi",
            "thursday" => "Jeudi",
            "friday" => "Vendredi",
            "saturday" => "Samedi",
            "sunday" => "Dimanche",
        ];

        // Initialiser avec 'unavailable' par défaut
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), "unavailable");
        }

        // Remplir avec les vraies données
        foreach ($enseignant->availabilities as $avail) {
            $dayName = $days[$avail->day_of_week] ?? null;

            // Parser l'heure de début et de fin
            if ($avail->start_time instanceof \Carbon\Carbon) {
                $startHour = $avail->start_time->hour;
                $endHour = $avail->end_time->hour;
            } elseif (is_string($avail->start_time)) {
                $startHour = (int) substr($avail->start_time, 0, 2);
                $endHour = (int) substr($avail->end_time, 0, 2);
            } else {
                $startHour = (int) substr((string) $avail->start_time, 0, 2);
                $endHour = (int) substr((string) $avail->end_time, 0, 2);
            }

            // Remplir toutes les heures entre start_time et end_time
            if ($dayName) {
                for ($hour = $startHour; $hour < $endHour; $hour++) {
                    $hourIndex = $hour - 8; // Index dans le tableau (8h = index 0)
                    if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                        $availability[$dayName][$hourIndex] =
                            $avail->availability_type;
                    }
                }
            }
        }

        // Calculer les stats
        $stats = [
            "available" => 0,
            "preferred" => 0,
            "unavailable" => 0,
        ];
        foreach ($availability as $daySlots) {
            foreach ($daySlots as $status) {
                $stats[$status]++;
            }
        }

        return [
            "enseignant" => $enseignant,
            "availability" => $availability,
            "hours" => $hours,
            "days" => $days,
            "joursNoms" => $joursNoms,
            "stats" => $stats,
        ];
    }

    /**
     * Mettre à jour les disponibilités de l'enseignant via AJAX
     */
    public function updateAvailability(
        Request $request,
        ESBTPTeacher $enseignant,
    ) {
        try {
            $request->validate([
                "changes" => "required|array",
                "changes.*.day" => "required|integer|min:0|max:6",
                "changes.*.startTime" => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
                "changes.*.endTime" => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
                "changes.*.status" => "required|in:available,preferred,unavailable",
            ]);

            DB::beginTransaction();

            foreach ($request->changes as $change) {
                $day = $change["day"];
                $startTime = $change["startTime"];
                $endTime = $change["endTime"];
                $status = $change["status"];

                $clickedStart = (int) substr($startTime, 0, 2);
                $clickedEnd = (int) substr($endTime, 0, 2);

                $existingAvailabilities = ESBTPTeacherAvailability::where([
                    "teacher_id" => $enseignant->id,
                    "day_of_week" => $day,
                ])->get();

                foreach ($existingAvailabilities as $existing) {
                    if ($existing->start_time instanceof \Carbon\Carbon) {
                        $existingStart = $existing->start_time->hour;
                        $existingEnd = $existing->end_time->hour;
                    } else {
                        $existingStart = (int) substr($existing->start_time, 11, 2);
                        $existingEnd = (int) substr($existing->end_time, 11, 2);
                    }

                    $hasOverlap = $clickedStart < $existingEnd && $clickedEnd > $existingStart;

                    if ($hasOverlap) {
                        $existing->delete();
                    }
                }

                if ($status !== "unavailable") {
                    ESBTPTeacherAvailability::create([
                        "teacher_id" => $enseignant->id,
                        "day_of_week" => $day,
                        "start_time" => $startTime,
                        "end_time" => $endTime,
                        "availability_type" => $status,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Disponibilités mises à jour avec succès",
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(
                [
                    "success" => false,
                    "message" => "Erreur lors de la mise à jour : " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Reset teacher password to default (Bonjour@2025) and force change on first login
     */
    public function resetPassword(Request $request, ESBTPTeacher $enseignant)
    {
        try {
            if (!$enseignant->user_id) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" =>
                                'Cet enseignant n\'a pas de compte utilisateur.',
                        ],
                        400,
                    );
                }
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        'Cet enseignant n\'a pas de compte utilisateur.',
                    );
            }

            $user = User::find($enseignant->user_id);
            if (!$user) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "Compte utilisateur introuvable.",
                        ],
                        404,
                    );
                }
                return redirect()
                    ->back()
                    ->with("error", "Compte utilisateur introuvable.");
            }

            // Mot de passe par défaut
            $defaultPassword = "Bonjour@2025";

            // Mettre à jour le mot de passe et forcer le changement à la première connexion
            $user->password = Hash::make($defaultPassword);
            $user->must_change_password = true; // Force le changement de mot de passe
            $user->save();

            // Log de l'action
            \Log::info("🔑 Password reset for teacher to default", [
                "teacher_id" => $enseignant->id,
                "user_id" => $enseignant->user_id,
                "teacher_name" => $user->name,
                "reset_by" => auth()->user()->name,
                "timestamp" => now(),
                "must_change_password" => true,
            ]);

            // Retourner JSON si requête AJAX, sinon redirect
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    "success" => true,
                    "message" => "Mot de passe réinitialisé avec succès!",
                    "password" => $defaultPassword,
                ]);
            }

            return redirect()
                ->back()
                ->with(
                    "success",
                    'Mot de passe réinitialisé à Bonjour@2025 avec succès! L\'enseignant devra changer son mot de passe à la première connexion.',
                )
                ->with("new_password", $defaultPassword);
        } catch (\Exception $e) {
            \Log::error("❌ Password reset failed", [
                "teacher_id" => $enseignant->id,
                "error" => $e->getMessage(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Erreur lors de la réinitialisation du mot de passe: " .
                            $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la réinitialisation du mot de passe: " .
                        $e->getMessage(),
                );
        }
    }
}
