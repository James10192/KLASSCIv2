<?php

namespace App\Http\Controllers;

use App\Enums\TeacherRegime;
use App\Enums\TeacherStatus;
use App\Http\Requests\Enseignant\QuickStoreEnseignantRequest;
use App\Http\Requests\Enseignant\StoreEnseignantRequest;
use App\Http\Requests\Enseignant\UpdateEnseignantRequest;
use App\Enums\TypeSeance;
use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPEnseignantTauxSeance;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPTeacherAvailability;
use App\Services\TeacherPlanningService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPEnseignantController extends Controller
{
    protected $userService;
    protected $planningService;

    public function __construct(UserService $userService, TeacherPlanningService $planningService)
    {
        $this->userService = $userService;
        $this->planningService = $planningService;
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

        $row = ESBTPTeacher::selectRaw(
            "COUNT(*) as total,
             SUM(status = 'active') as active,
             SUM(status = 'inactive') as inactive,
             SUM(regime = 'permanent') as permanent,
             SUM(regime = 'vacataire') as temporary"
        )->first();

        $stats = [
            "total" => (int) ($row->total ?? 0),
            "active" => (int) ($row->active ?? 0),
            "inactive" => (int) ($row->inactive ?? 0),
            "permanent" => (int) ($row->permanent ?? 0),
            "temporary" => (int) ($row->temporary ?? 0),
        ];

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
     * Store a newly created teacher in storage.
     */
    public function store(StoreEnseignantRequest $request)
    {
        $regime = $request->input('regime') ?: TeacherRegime::Vacataire->value;

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
                "status" => TeacherStatus::Active->value,
                "regime" => $regime,
                "taux_horaire" => $request->taux_horaire,
                "date_debut_activite" => $request->date_debut_activite
                    ? Carbon::parse($request->date_debut_activite)->format('Y-m-d')
                    : now()->toDateString(),
                "diplome_principal" => $request->diplome_principal,
                "universite_diplome" => $request->universite_diplome,
                "annee_diplome" => $request->annee_diplome,
                "teaching_hours_due" => $regime === TeacherRegime::Permanent->value
                    ? ($request->charge_horaire_max_semaine ?? 18)
                    : 0,
                "created_by" => auth()->id(),
            ]);

            $this->syncTauxSeances($teacher, $request);

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

    public function quickStore(QuickStoreEnseignantRequest $request)
    {
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

            // @deprecated 2026-Q3 — fallback type_contrat pour clients AJAX legacy d'avant PR #287.
            // Tous les clients modernes envoient `regime` directement. À retirer après audit.
            $regime = $request->input('regime')
                ?: match ($request->input('type_contrat')) {
                    'permanent' => TeacherRegime::Permanent->value,
                    'consultant' => TeacherRegime::Consultant->value,
                    default => TeacherRegime::Vacataire->value,
                };

            $dateDebut = $request->date_debut_activite ?: $request->date_embauche;
            $dateDebut = $dateDebut
                ? Carbon::parse($dateDebut)->format('Y-m-d')
                : now()->toDateString();

            $teacher = ESBTPTeacher::create([
                "user_id" => $user->id,
                "matricule" => $this->generateMatricule(),
                "title" => $request->titre_academique,
                "specialization" => $request->specialization,
                "grade" => $request->grade_academique,
                "status" => TeacherStatus::Active->value,
                "regime" => $regime,
                "taux_horaire" => $request->taux_horaire,
                "date_debut_activite" => $dateDebut,
                "teaching_hours_due" => $regime === TeacherRegime::Permanent->value
                    ? ($request->charge_horaire_max_semaine ?? 18)
                    : 0,
                "created_by" => auth()->id(),
            ]);

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
            $availabilityData = $this->planningService->getAvailabilityMatrix($teacher)['availability'];

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
        $teachingPlanning = $this->planningService->getPlanning(
            $enseignant,
            $anneeCourante,
            $periode,
        );

        $realAvailability = $this->planningService->getAvailabilityMatrix($enseignant)['availability'];

        // Passer $enseignant en tant que $teacher pour la compatibilité avec la vue
        $teacher = $enseignant;
        return view(
            "esbtp.enseignants.show",
            compact(
                "teacher",
                "realAvailability",
                "teachingPlanning",
                "anneeCourante",
                "periode",
            ),
        );
    }



    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(ESBTPTeacher $enseignant)
    {
        $enseignant->load(["user", "availabilities"]);
        $availabilityData = $this->planningService->getAvailabilityMatrix($enseignant)['availability'];

        $teacher = $enseignant;

        return view(
            "esbtp.enseignants.edit",
            [
                "teacher" => $teacher,
                "availabilityData" => $availabilityData,
                "titres_academiques" => $this->titresAcademiques(),
                "grades_academiques" => $this->gradesAcademiques(),
            ],
        );
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(UpdateEnseignantRequest $request, ESBTPTeacher $enseignant)
    {
        $regime = $request->input('regime') ?: TeacherRegime::Vacataire->value;

        DB::beginTransaction();

        try {
            $enseignant->user->update([
                "name" => $request->name,
                "email" => $request->email ?: null,
                "phone" => $request->phone,
            ]);

            $dateDebut = $request->date_debut_activite
                ? Carbon::parse($request->date_debut_activite)->format('Y-m-d')
                : $enseignant->date_debut_activite;

            $enseignant->update([
                "title" => $request->titre_academique,
                "specialization" => $request->specialization,
                "grade" => $request->grade_academique,
                "regime" => $regime,
                "taux_horaire" => $request->taux_horaire,
                "date_debut_activite" => $dateDebut,
                "diplome_principal" => $request->diplome_principal,
                "universite_diplome" => $request->universite_diplome,
                "annee_diplome" => $request->annee_diplome,
                "bio" => $request->bio,
                "website" => $request->website,
                "status" => $request->status ?? $enseignant->status,
                "teaching_hours_due" => $regime === TeacherRegime::Permanent->value
                    ? ($request->charge_horaire_max_semaine ?? $enseignant->teaching_hours_due ?? 18)
                    : 0,
                "updated_by" => auth()->id(),
            ]);

            $this->syncTauxSeances($enseignant, $request);

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
     * Persiste les taux horaires par type de séance (CM/TD/TP) depuis le formulaire.
     *
     * Sécurité : ne touche aux taux QUE si l'utilisateur détient la permission
     * comptabilite.salaires.set_rate (séparation pédagogie / comptabilité). Un
     * coordinateur sans la permission peut enregistrer la fiche sans écraser les taux.
     *
     * Champ attendu : taux_par_type[CM|TD|TP] (vide = retrait de l'override).
     */
    private function syncTauxSeances(ESBTPTeacher $teacher, Request $request): void
    {
        if (!auth()->user()?->can('comptabilite.salaires.set_rate')) {
            return;
        }

        $input = $request->input('taux_par_type', []);
        if (!is_array($input)) {
            return;
        }

        foreach (TypeSeance::cases() as $type) {
            if (!$type->isVolumeTracked()) {
                continue; // seuls CM/TD/TP ont un taux configurable
            }

            $raw = $input[$type->value] ?? null;
            $value = ($raw === null || $raw === '') ? null : (float) $raw;

            if ($value === null) {
                $teacher->tauxSeances()->where('type_seance', $type->value)->delete();
                continue;
            }

            ESBTPEnseignantTauxSeance::updateOrCreate(
                ['teacher_id' => $teacher->id, 'type_seance' => $type->value],
                ['taux_horaire' => $value, 'updated_by' => auth()->id(),
                 'created_by' => auth()->id()],
            );
        }
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(ESBTPTeacher $teacher)
    {
        if ($teacher->user_id === auth()->id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            DB::beginTransaction();

            // Marquer l'enseignant comme inactif (soft deactivation)
            $teacher->update(['status' => TeacherStatus::Inactive->value]);

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
        $newStatus = $teacher->status === TeacherStatus::Active->value
            ? TeacherStatus::Inactive->value
            : TeacherStatus::Active->value;

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
            return $this->planningService->getAvailabilityMatrix($enseignant);
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
        $data = $this->planningService->getAvailabilityMatrix($enseignant);

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
        $data = $this->planningService->getAvailabilityMatrix($enseignant);

        $updatedAt = $enseignant->availabilities->max('updated_at');

        return response()->json([
            'success' => true,
            'data' => $data['availability'],
            'updated_at' => $updatedAt ? $updatedAt->timestamp : now()->timestamp,
        ]);
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
