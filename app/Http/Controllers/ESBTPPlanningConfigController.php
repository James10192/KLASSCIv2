<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPTeacher;
use App\Services\PlanningConfigurationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPPlanningConfigController extends Controller
{
    public function __construct(
        PlanningConfigurationService $planningConfigService,
    ) {
        $this->middleware("auth");
        $this->planningConfigService = $planningConfigService;
    }


    /**
     * Récupérer les matières disponibles pour configuration par AJAX
     */
    public function getMatieresPourConfiguration(Request $request)
    {
        $filiereId = $request->input("filiere_id");
        $niveauId = $request->input("niveau_id");
        $anneeId = $request->input("annee_id");
        $semestre = (int) $request->input("semestre", 1);
        $semestre = in_array($semestre, [1, 2], true) ? $semestre : 1;

        if (!$filiereId || !$niveauId) {
            return response()->json([
                "success" => false,
                "message" => "Filière et niveau requis",
            ]);
        }

        // Récupérer les matières liées à cette combinaison filière/niveau
        $matieresLiees = ESBTPMatiere::where("is_active", true)
            ->whereHas("filieres", function ($query) use ($filiereId) {
                $query->where("esbtp_filieres.id", $filiereId);
            })
            ->whereHas("niveaux", function ($query) use ($niveauId) {
                $query->where("esbtp_niveau_etudes.id", $niveauId);
            })
            ->orderBy("name")
            ->get();

        // Si aucune matière liée, proposer toutes les matières disponibles pour association
        if ($matieresLiees->isEmpty()) {
            $matieres = ESBTPMatiere::where("is_active", true)
                ->orderBy("name")
                ->get();
            $modeAssociation = true;
        } else {
            $matieres = $matieresLiees;
            $modeAssociation = false;
        }

        // Récupérer les planifications existantes pour cette combinaison
        $planificationsExistantes = ESBTPPlanificationAcademique::where(
            "filiere_id",
            $filiereId,
        )
            ->where("niveau_etude_id", $niveauId)
            ->where(function ($query) use ($semestre) {
                $query->where("semestre", $semestre)->orWhereNull("semestre");
            });

        if ($anneeId) {
            $planificationsExistantes->where(
                "annee_universitaire_id",
                $anneeId,
            );
        }

        $planificationsExistantes = $planificationsExistantes
            ->with("matiere")
            ->get()
            ->keyBy("matiere_id");

        $html = "";

        if ($modeAssociation) {
            // Mode association : permettre de sélectionner les matières à associer
            $html .= '<div class="alert alert-info mb-4">';
            $html .= '<i class="fas fa-info-circle me-2"></i>';
            $html .= "<strong>Aucune matière associée</strong><br>";
            $html .=
                "Sélectionnez les matières que vous souhaitez associer à cette combinaison, puis configurez leurs volumes horaires.";
            $html .= "</div>";

            $html .= '<div class="mb-3">';
            $html .=
                '<button type="button" class="btn btn-secondary btn-sm" id="select-all-matieres">Tout sélectionner</button> ';
            $html .=
                '<button type="button" class="btn btn-secondary btn-sm" id="deselect-all-matieres">Tout désélectionner</button>';
            $html .= "</div>";
        }

        foreach ($matieres as $matiere) {
            $planificationExistante = $planificationsExistantes->get(
                $matiere->id,
            );
            $volumeActuel = $planificationExistante
                ? $planificationExistante->volume_horaire_total
                : 0;
            $isConfigured = $volumeActuel > 0;
            $isAssociated = !$modeAssociation; // En mode normal, toutes les matières sont associées

            $cardClass = "config-matiere-card";
            if ($modeAssociation) {
                $cardClass .= " association-mode";
            }
            if ($isConfigured) {
                $cardClass .= " configured";
            }

            $html .=
                '<div class="' .
                $cardClass .
                '" data-matiere-id="' .
                $matiere->id .
                '">';

            if ($modeAssociation) {
                // Checkbox pour sélectionner la matière à associer
                $html .= '<div class="matiere-selection">';
                $html .=
                    '<input type="checkbox" class="form-check-input matiere-checkbox" id="matiere_' .
                    $matiere->id .
                    '" name="associations[' .
                    $matiere->id .
                    ']" value="1">';
                $html .=
                    '<label class="form-check-label" for="matiere_' .
                    $matiere->id .
                    '"></label>';
                $html .= "</div>";
            }

            $html .= '<div class="matiere-details">';
            $html .=
                '<div class="matiere-name">' .
                htmlspecialchars($matiere->name) .
                "</div>";
            if ($matiere->description) {
                $html .=
                    '<div class="matiere-description">' .
                    htmlspecialchars($matiere->description) .
                    "</div>";
            }

            // Afficher info sur les associations existantes
            if ($modeAssociation) {
                $filieres = $matiere->filieres->pluck("name")->toArray();
                $niveaux = $matiere->niveaux->pluck("name")->toArray();
                if (!empty($filieres) || !empty($niveaux)) {
                    $html .=
                        '<div class="matiere-associations text-muted small">';
                    if (!empty($filieres)) {
                        $html .=
                            "<span>Filières: " .
                            implode(", ", array_slice($filieres, 0, 2)) .
                            (count($filieres) > 2 ? "..." : "") .
                            "</span><br>";
                    }
                    if (!empty($niveaux)) {
                        $html .=
                            "<span>Niveaux: " .
                            implode(", ", array_slice($niveaux, 0, 2)) .
                            (count($niveaux) > 2 ? "..." : "") .
                            "</span>";
                    }
                    $html .= "</div>";
                }
            }

            $html .= "</div>";
            $html .= '<div class="matiere-config">';

            // Section volume horaire
            $html .= '<div class="config-section volume-config-section">';
            $html .=
                '<label class="config-label"><i class="fas fa-clock"></i>Volume horaire</label>';
            $html .= '<div class="volume-config">';
            if ($modeAssociation) {
                // En mode association, le volume n'est configurable que si la matière est sélectionnée
                $html .=
                    '<input type="number" name="volumes[' .
                    $matiere->id .
                    ']" value="' .
                    $volumeActuel .
                    '" min="0" max="200" class="form-control volume-input" placeholder="0" disabled>';
            } else {
                $html .=
                    '<input type="number" name="volumes[' .
                    $matiere->id .
                    ']" value="' .
                    $volumeActuel .
                    '" min="0" max="200" class="form-control volume-input" placeholder="0">';
            }
            $html .= '<span class="volume-unit">heures</span>';
            $html .= "</div>";
            $html .= "</div>";

            // Section assignation de professeurs avec tableau structuré
            $html .= '<div class="config-section teacher-config-section">';
            $html .=
                '<div class="d-flex justify-content-between align-items-center mb-2">';
            $html .=
                '<label class="config-label mb-0"><i class="fas fa-users"></i>Professeur(s) assigné(s)</label>';
            $html .=
                '<button type="button" class="btn btn-sm btn-outline-primary create-teacher-btn" data-matiere-id="' .
                $matiere->id .
                '" data-bs-toggle="modal" data-bs-target="#teacherCreateModal">' .
                '<i class="fas fa-user-plus me-1"></i>Créer enseignant' .
                "</button>";
            $html .= "</div>";

            // Récupérer les professeurs déjà assignés à cette matière
            $assignedTeachers = [];
            if (!$modeAssociation && $planificationExistante) {
                $assignedTeacherIds = DB::table("esbtp_planification_teachers")
                    ->where("planification_id", $planificationExistante->id)
                    ->pluck("teacher_id")
                    ->toArray();
                $assignedTeachers = $assignedTeacherIds;
            }

            // Récupérer les professeurs actifs
            $teachers = \App\Models\ESBTPTeacher::where("is_active", true)
                ->with("user")
                ->orderBy("user_id")
                ->get();

            if ($teachers->isEmpty()) {
                $html .= '<div class="text-muted text-center py-3">';
                $html .=
                    '<i class="fas fa-info-circle me-2"></i>Aucun enseignant disponible';
                $html .= "</div>";
            } else {
                // Champ de recherche
                $html .=
                    '<div class="teacher-search-wrapper mb-2" style="position: relative;">';
                $html .=
                    '<input type="text" class="form-control teacher-search-input" data-matiere-id="' .
                    $matiere->id .
                    '" placeholder="Rechercher un enseignant (nom ou spécialisation)..." style="padding-left: 35px; border-radius: 6px; border: 1px solid #ddd;">';
                $html .=
                    '<i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>';
                $html .= "</div>";

                // Conteneur du tableau
                $html .=
                    '<div class="teacher-table-container" data-matiere-id="' .
                    $matiere->id .
                    '" style="max-height: 400px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 6px;">';

                // Tableau HTML
                $html .=
                    '<table class="table table-sm table-hover mb-0 teacher-selection-table" style="margin-bottom: 0 !important;">';

                // Header avec checkbox global
                $html .=
                    '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10; border-bottom: 2px solid #dee2e6;">';
                $html .= "<tr>";
                $html .=
                    '<th style="width: 50px; text-align: center; padding: 10px;">';
                $html .=
                    '<input type="checkbox" class="teacher-select-all-checkbox" data-matiere-id="' .
                    $matiere->id .
                    '" title="Tout sélectionner / Tout désélectionner">';
                $html .= "</th>";
                $html .= '<th style="padding: 10px;">Nom complet</th>';
                $html .=
                    '<th style="padding: 10px; width: 30%;">Spécialisation</th>';
                $html .= "</tr>";
                $html .= "</thead>";

                // Body
                $html .= "<tbody>";
                foreach ($teachers as $teacher) {
                    $teacherName = $teacher->user
                        ? $teacher->user->name
                        : $teacher->matricule;
                    $specialization = $teacher->specialization ?: "-";
                    $checked = in_array($teacher->id, $assignedTeachers)
                        ? " checked"
                        : "";

                    $html .=
                        '<tr class="teacher-row" data-teacher-name="' .
                        htmlspecialchars(strtolower($teacherName)) .
                        '" data-teacher-spec="' .
                        htmlspecialchars(strtolower($specialization)) .
                        '">';

                    // Colonne checkbox
                    $html .= '<td style="text-align: center; padding: 8px;">';
                    $html .=
                        '<input type="checkbox" name="teachers[' .
                        $matiere->id .
                        '][]" value="' .
                        $teacher->id .
                        '"' .
                        $checked .
                        ' class="teacher-checkbox">';
                    $html .= "</td>";

                    // Colonne nom
                    $html .= '<td style="padding: 8px;">';
                    $html .=
                        "<strong>" .
                        htmlspecialchars($teacherName) .
                        "</strong>";
                    $html .= "</td>";

                    // Colonne spécialisation
                    $html .= '<td style="padding: 8px; color: #666;">';
                    $html .= htmlspecialchars($specialization);
                    $html .= "</td>";

                    $html .= "</tr>";
                }
                $html .= "</tbody>";
                $html .= "</table>";

                $html .= "</div>"; // Fin teacher-table-container

                // Message aucun résultat (caché par défaut)
                $html .=
                    '<div class="teacher-no-results text-muted text-center py-3" data-matiere-id="' .
                    $matiere->id .
                    '" style="display: none;">';
                $html .=
                    '<i class="fas fa-search me-2"></i>Aucun enseignant trouvé';
                $html .= "</div>";
            }

            // Compteur dynamique
            $html .=
                '<div class="teacher-selection-count" data-matiere-id="' .
                $matiere->id .
                '" style="margin-top: 10px; padding: 8px; background: #f0f8ff; border-radius: 6px; text-align: center; font-size: 13px;">';
            $html .= '<i class="fas fa-info-circle text-primary"></i> ';
            $html .=
                '<span class="count-text">Sélectionnez un ou plusieurs enseignants</span>';
            $html .= "</div>";

            $html .= "</div>"; // Fin teacher-config-section

            $html .= "</div>";
            $html .= "</div>";
        }

        return response()->json([
            "success" => true,
            "html" => $html,
        ]);
    }


    /**
     * Sauvegarder la configuration des volumes horaires
     */
    public function saveVolumeConfiguration(Request $request)
    {
        \Log::info(
            "🚀 ========== DÉBUT SAUVEGARDE PLANNING GÉNÉRAL (BACKEND) ==========",
        );
        \Log::info("📥 Données reçues:", $request->all());

        $request->validate([
            "filiere_id" => "required|exists:esbtp_filieres,id",
            "niveau_id" => "required|exists:esbtp_niveau_etudes,id",
            "annee_id" => "required|exists:esbtp_annee_universitaires,id",
            "semestre" => "required|integer|in:1,2",
            "volumes" => "nullable|array",
            "volumes.*" => "nullable|integer|min:0|max:200",
            "teachers" => "nullable|array",
            "teachers.*" => "nullable|array",
            "teachers.*.*" => "exists:esbtp_teachers,id",
        ]);

        \Log::info("✅ Validation passée");
        \Log::info("📊 Volumes à sauvegarder:", $request->volumes);
        \Log::info("👨‍🏫 Professeurs à assigner:", $request->teachers ?? []);

        $volumes = $request->input("volumes", []);
        if (empty($volumes) && empty($request->teachers ?? [])) {
            return response()->json([
                "success" => true,
                "message" => "Aucune modification à enregistrer.",
            ]);
        }

        DB::beginTransaction();

        try {
            $savedCount = 0;
            $updatedCount = 0;
            $teachersAssignedCount = 0;

            foreach ($volumes as $matiereId => $volume) {
                if ($volume === null || $volume === "") {
                    continue;
                }

                $volumeValue = (int) $volume;

                if ($volumeValue > 0) {
                    \Log::info(
                        "📚 Traitement Matière ID: {$matiereId}, Volume: {$volume}h",
                    );

                    $planification = ESBTPPlanificationAcademique::withTrashed()->firstOrNew(
                        [
                            "annee_universitaire_id" => $request->annee_id,
                            "filiere_id" => $request->filiere_id,
                            "niveau_etude_id" => $request->niveau_id,
                            "matiere_id" => $matiereId,
                            "semestre" => $request->semestre,
                        ],
                    );

                    $wasTrashed = $planification->trashed();
                    if ($wasTrashed) {
                        $planification->restore();
                    }

                    $planification->fill([
                        "volume_horaire_total" => $volumeValue,
                        "volume_horaire_cm" => 0,
                        "volume_horaire_td" => 0,
                        "volume_horaire_tp" => 0,
                        "coefficient" => 1,
                        "credits_ects" => 0,
                        "statut" =>
                            ESBTPPlanificationAcademique::STATUT_PLANIFIE,
                        "updated_by" => Auth::id(),
                        "created_by" => $planification->exists
                            ? $planification->created_by
                            : Auth::id(),
                    ]);

                    $planification->save();

                    if ($planification->wasRecentlyCreated) {
                        \Log::info(
                            "  ➕ Planification créée (ID: {$planification->id})",
                        );
                        $savedCount++;
                    } elseif ($wasTrashed) {
                        \Log::info(
                            "  ♻️ Planification restaurée (ID: {$planification->id})",
                        );
                        $updatedCount++;
                    } else {
                        \Log::info(
                            "  🔄 Planification mise à jour (ID: {$planification->id})",
                        );
                        $updatedCount++;
                    }

                    // Gérer les assignations de professeurs pour cette planification
                    if (
                        isset($request->teachers[$matiereId]) &&
                        !empty($request->teachers[$matiereId])
                    ) {
                        $teachersForThisMatiere =
                            $request->teachers[$matiereId];
                        \Log::info(
                            "  👨‍🏫 Assignation de " .
                                count($teachersForThisMatiere) .
                                " professeur(s) pour matière {$matiereId}",
                        );
                        \Log::info(
                            "  📋 IDs professeurs: " .
                                json_encode($teachersForThisMatiere),
                        );

                        // Supprimer les anciennes assignations
                        $deletedCount = DB::table(
                            "esbtp_planification_teachers",
                        )
                            ->where("planification_id", $planification->id)
                            ->delete();

                        \Log::info(
                            "  🗑️ {$deletedCount} ancienne(s) assignation(s) supprimée(s)",
                        );

                        // Récupérer le premier enseignant pour le définir comme principal
                        $firstTeacherId = null;

                        // Ajouter les nouvelles assignations
                        foreach (
                            $teachersForThisMatiere
                            as $index => $teacherId
                        ) {
                            if (!empty($teacherId)) {
                                DB::table(
                                    "esbtp_planification_teachers",
                                )->insert([
                                    "planification_id" => $planification->id,
                                    "teacher_id" => $teacherId,
                                    "created_at" => now(),
                                    "updated_at" => now(),
                                ]);

                                \Log::info(
                                    "    ✅ Teacher ID {$teacherId} assigné à planification {$planification->id}",
                                );
                                $teachersAssignedCount++;

                                // Définir le premier enseignant comme principal
                                if ($firstTeacherId === null) {
                                    $firstTeacherId = $teacherId;
                                }
                            }
                        }

                        // Mettre à jour l'enseignant principal dans la planification
                        if ($firstTeacherId !== null) {
                            // Récupérer le user_id depuis la table esbtp_teachers
                            $teacher = \App\Models\ESBTPTeacher::find(
                                $firstTeacherId,
                            );
                            if ($teacher && $teacher->user_id) {
                                $planification->update([
                                    "enseignant_principal_id" =>
                                        $teacher->user_id,
                                ]);
                                \Log::info(
                                    "    ⭐ Enseignant principal défini: Teacher ID {$firstTeacherId} (User ID {$teacher->user_id})",
                                );
                            } else {
                                \Log::warning(
                                    "    ⚠️ Impossible de définir enseignant principal: Teacher {$firstTeacherId} non trouvé ou sans user_id",
                                );
                            }
                        }
                    } else {
                        \Log::info(
                            "  ❌ Aucun professeur sélectionné pour matière {$matiereId}",
                        );

                        // Si aucun professeur sélectionné, supprimer les assignations existantes
                        $deletedCount = DB::table(
                            "esbtp_planification_teachers",
                        )
                            ->where("planification_id", $planification->id)
                            ->delete();

                        \Log::info(
                            "  🗑️ {$deletedCount} assignation(s) supprimée(s)",
                        );

                        // Supprimer l'enseignant principal
                        $planification->update([
                            "enseignant_principal_id" => null,
                        ]);
                        \Log::info("  🚫 Enseignant principal retiré");
                    }
                } elseif ($volumeValue === 0) {
                    // Supprimer uniquement si l'utilisateur a explicitement mis 0
                    $planificationsToDelete = ESBTPPlanificationAcademique::where(
                        "annee_universitaire_id",
                        $request->annee_id,
                    )
                        ->where("filiere_id", $request->filiere_id)
                        ->where("niveau_etude_id", $request->niveau_id)
                        ->where("matiere_id", $matiereId)
                        ->where("semestre", $request->semestre)
                        ->get();

                    // Supprimer les assignations de professeurs associées
                    foreach ($planificationsToDelete as $planification) {
                        DB::table("esbtp_planification_teachers")
                            ->where("planification_id", $planification->id)
                            ->delete();
                    }

                    // Supprimer les planifications
                    ESBTPPlanificationAcademique::where(
                        "annee_universitaire_id",
                        $request->annee_id,
                    )
                        ->where("filiere_id", $request->filiere_id)
                        ->where("niveau_etude_id", $request->niveau_id)
                        ->where("matiere_id", $matiereId)
                        ->where("semestre", $request->semestre)
                        ->delete();
                }
            }

            DB::commit();

            \Log::info("💾 Transaction committée avec succès");
            \Log::info("📊 Résumé de la sauvegarde:", [
                "Planifications créées" => $savedCount,
                "Planifications mises à jour" => $updatedCount,
                "Professeurs assignés au total" => $teachersAssignedCount,
            ]);

            $message = "Configuration sauvegardée avec succès.";
            if ($savedCount > 0) {
                $message .= " {$savedCount} nouvelle(s) planification(s) créée(s).";
            }
            if ($updatedCount > 0) {
                $message .= " {$updatedCount} planification(s) mise(s) à jour.";
            }
            if ($teachersAssignedCount > 0) {
                $message .= " {$teachersAssignedCount} professeur(s) assigné(s).";
            }

            \Log::info("✅ Message de succès: " . $message);
            \Log::info(
                "========== FIN SAUVEGARDE PLANNING GÉNÉRAL (SUCCESS) ==========",
            );

            return response()->json([
                "success" => true,
                "message" => $message,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            \Log::error(
                "❌ ========== ERREUR SAUVEGARDE PLANNING GÉNÉRAL ==========",
            );
            \Log::error('Message d\'erreur: ' . $e->getMessage());
            \Log::error("Fichier: " . $e->getFile());
            \Log::error("Ligne: " . $e->getLine());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            \Log::error("========== FIN ERREUR ==========");

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la sauvegarde: " . $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Gérer les enseignants d'une planification (associer/désassocier) via AJAX.
     */
    public function manageTeachers(Request $request, ESBTPPlanificationAcademique $planification)
    {
        $request->validate([
            'action' => 'required|in:associate,dissociate',
            'teacher_id' => 'required|exists:esbtp_teachers,id',
        ]);

        $teacherId = $request->teacher_id;
        $action = $request->action;

        if ($action === 'associate') {
            $exists = DB::table('esbtp_planification_teachers')
                ->where('planification_id', $planification->id)
                ->where('teacher_id', $teacherId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant est déjà associé à cette planification.',
                ], 422);
            }

            DB::table('esbtp_planification_teachers')->insert([
                'planification_id' => $planification->id,
                'teacher_id' => $teacherId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set as principal if none exists
            if (!$planification->enseignant_principal_id) {
                $teacher = ESBTPTeacher::find($teacherId);
                if ($teacher && $teacher->user_id) {
                    $planification->update([
                        'enseignant_principal_id' => $teacher->user_id,
                    ]);
                }
            }

            $message = 'Enseignant associé avec succès.';
        } else {
            // Check if teacher has existing seances on this planification's classe
            $seanceCount = ESBTPSeanceCours::where('teacher_id', $teacherId)
                ->where('matiere_id', $planification->matiere_id)
                ->whereHas('emploiTemps', function ($q) use ($planification) {
                    $q->whereHas('classe', function ($cq) use ($planification) {
                        $cq->where('filiere_id', $planification->filiere_id)
                            ->where('niveau_etude_id', $planification->niveau_etude_id);
                    });
                })
                ->where('is_active', true)
                ->count();

            if ($seanceCount > 0) {
                return response()->json([
                    'success' => false,
                    'blocked' => true,
                    'seance_count' => $seanceCount,
                    'message' => "Impossible de retirer cet enseignant : il a {$seanceCount} séance(s) de cours programmée(s) sur cette matière.",
                ], 422);
            }

            DB::table('esbtp_planification_teachers')
                ->where('planification_id', $planification->id)
                ->where('teacher_id', $teacherId)
                ->delete();

            // Reassign principal if removed teacher was the principal
            $teacher = ESBTPTeacher::find($teacherId);
            if ($teacher && $planification->enseignant_principal_id === $teacher->user_id) {
                $nextTeacher = DB::table('esbtp_planification_teachers')
                    ->where('planification_id', $planification->id)
                    ->first();

                if ($nextTeacher) {
                    $next = ESBTPTeacher::find($nextTeacher->teacher_id);
                    $planification->update([
                        'enseignant_principal_id' => $next?->user_id,
                    ]);
                } else {
                    $planification->update(['enseignant_principal_id' => null]);
                }
            }

            $message = 'Enseignant retiré avec succès.';
        }

        // Return updated list of linked teachers
        $linkedTeachers = ESBTPTeacher::whereIn('id', function ($q) use ($planification) {
            $q->select('teacher_id')
                ->from('esbtp_planification_teachers')
                ->where('planification_id', $planification->id);
        })->with('user')->get();

        // Count seances per teacher for the UI
        $teachersWithSeances = $linkedTeachers->map(function ($t) use ($planification) {
            $seances = ESBTPSeanceCours::where('teacher_id', $t->id)
                ->where('matiere_id', $planification->matiere_id)
                ->whereHas('emploiTemps', function ($q) use ($planification) {
                    $q->whereHas('classe', function ($cq) use ($planification) {
                        $cq->where('filiere_id', $planification->filiere_id)
                            ->where('niveau_etude_id', $planification->niveau_etude_id);
                    });
                })
                ->where('is_active', true)
                ->count();

            return [
                'id' => $t->id,
                'name' => $t->user?->name ?? $t->matricule,
                'specialization' => $t->specialization,
                'seance_count' => $seances,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $message,
            'linked_teachers' => $teachersWithSeances,
            'linked_ids' => $linkedTeachers->pluck('id')->toArray(),
        ]);
    }

    /**
     * Retourner la liste des enseignants pour le modal de gestion.
     */
    public function getTeachersForManagement(ESBTPPlanificationAcademique $planification)
    {
        $linkedIds = DB::table('esbtp_planification_teachers')
            ->where('planification_id', $planification->id)
            ->pluck('teacher_id')
            ->toArray();

        $linkedTeachers = ESBTPTeacher::whereIn('id', $linkedIds)
            ->with('user')
            ->get()
            ->map(function ($t) use ($planification) {
                $seances = ESBTPSeanceCours::where('teacher_id', $t->id)
                    ->where('matiere_id', $planification->matiere_id)
                    ->whereHas('emploiTemps', function ($q) use ($planification) {
                        $q->whereHas('classe', function ($cq) use ($planification) {
                            $cq->where('filiere_id', $planification->filiere_id)
                                ->where('niveau_etude_id', $planification->niveau_etude_id);
                        });
                    })
                    ->where('is_active', true)
                    ->count();

                return [
                    'id' => $t->id,
                    'name' => $t->user?->name ?? $t->matricule,
                    'specialization' => $t->specialization,
                    'seance_count' => $seances,
                ];
            });

        $availableTeachers = ESBTPTeacher::whereNotIn('id', $linkedIds)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->user?->name ?? $t->matricule,
                    'specialization' => $t->specialization,
                ];
            });

        return response()->json([
            'success' => true,
            'linked_teachers' => $linkedTeachers,
            'available_teachers' => $availableTeachers,
        ]);
    }

    /**
     * Configuration rapide d'une planification via AJAX
     */
    public function configureRapide(Request $request)
    {
        try {
            // Convertir la période du format frontend vers le format service
            $requestData = $request->all();
            if (isset($requestData["periode"])) {
                $requestData["semestre"] = match ($requestData["periode"]) {
                    "semestre1", "S1" => 1,
                    "semestre2", "S2" => 2,
                    "annee", "Annuel" => 1, // Par défaut semestre 1 pour annuel
                    default => 1,
                };
            }

            // Utiliser le service de configuration
            if ($request->filiere_id && $request->niveau_id) {
                // Configuration spécifique
                $planification = $this->planningConfigService->configureRapide(
                    $requestData,
                );
                $matiere = ESBTPMatiere::find($request->matiere_id);

                return response()->json([
                    "success" => true,
                    "message" => "Configuration du planning de {$matiere->name} pour la filière/niveau spécifié enregistrée avec succès !",
                    "planification" => $planification,
                ]);
            } else {
                // Configuration en lot pour toutes les combinaisons existantes
                $selections = $this->getExistingCombinations(
                    $request->annee_id,
                );
                $baseConfig = $requestData;

                $results = $this->planningConfigService->configureBulk(
                    $selections,
                    $baseConfig,
                );
                $successCount = $results->where("success", true)->count();
                $matiere = ESBTPMatiere::find($request->matiere_id);

                return response()->json([
                    "success" => true,
                    "message" => "Configuration du planning de {$matiere->name} appliquée à {$successCount} combinaison(s) filière/niveau",
                    "results" => $results,
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Données invalides : " .
                        implode(", ", $e->validator->errors()->all()),
                ],
                422,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la configuration : " . $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Méthode pour la configuration avancée utilisant le service
     */
    public function configureAvance(Request $request)
    {
        try {
            $planification = $this->planningConfigService->configureAvance(
                $request->all(),
            );

            return redirect()
                ->back()
                ->with(
                    "success",
                    "Configuration avancée enregistrée avec succès !",
                );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la configuration : " . $e->getMessage(),
                );
        }
    }


    /**
     * API pour obtenir les options de configuration d'une matière
     */
    public function getConfigurationOptions(int $matiereId, Request $request)
    {
        try {
            $options = $this->planningConfigService->getConfigurationOptions(
                $matiereId,
                $request->input("annee_id"),
            );

            return response()->json([
                "success" => true,
                "options" => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Obtenir les combinaisons existantes pour configuration en lot
     */
    private function getExistingCombinations(int $anneeId): array
    {
        $combinaisons = ESBTPPlanificationAcademique::where(
            "annee_universitaire_id",
            $anneeId,
        )
            ->select("filiere_id", "niveau_id")
            ->distinct()
            ->get();

        return $combinaisons
            ->map(function ($combinaison) use ($anneeId) {
                return [
                    "filiere_id" => $combinaison->filiere_id,
                    "niveau_id" => $combinaison->niveau_id,
                    "annee_id" => $anneeId,
                ];
            })
            ->toArray();
    }

}
