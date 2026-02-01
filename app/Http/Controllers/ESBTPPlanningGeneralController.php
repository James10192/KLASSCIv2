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

class ESBTPPlanningGeneralController extends Controller
{
    protected $planningConfigService;

    public function __construct(PlanningConfigurationService $planningConfigService)
    {
        $this->middleware('auth');
        $this->planningConfigService = $planningConfigService;
    }

    /**
     * Interface de test pour planification académique
     */
    public function indexTest(Request $request)
    {
        // Utilise la même logique que index() mais force la vue de test
        $result = $this->index($request);
        $data = $result->getData();
        
        return view('esbtp.planning-general.index-test', $data);
    }

    /**
     * Interface principale de planification académique
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Récupérer l'année universitaire sélectionnée ou celle en cours
        $anneeId = $request->input('annee_id');
        
        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }
        
        // Données de base
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId);
        
        // Statistiques générales pour la vue index
        $stats = $this->calculerStatistiquesGenerales($anneeId);
        
        // Récupérer les filtres
        $filiereFilter = $request->input('filiere_filter');
        $niveauFilter = $request->input('niveau_filter');
        
        // Récupérer toutes les combinaisons filière/niveau avec leurs matières
        $combinaisons = $this->getCombinaisonsAvecMatieres($anneeId, $filiereFilter, $niveauFilter);
        
        return view('esbtp.planning-general.index', compact(
            'annees', 'anneeSelectionnee', 'stats', 'combinaisons'
        ));
    }
    
    /**
     * Récupérer toutes les combinaisons filière/niveau avec leurs statistiques de configuration
     */
    private function getCombinaisonsAvecMatieres($anneeId, $filiereFilter = null, $niveauFilter = null)
    {
        // Récupérer les combinaisons avec filtres optionnels
        $filieres = ESBTPFiliere::where('is_active', true);
        if ($filiereFilter) {
            $filieres->where('id', $filiereFilter);
        }
        $filieres = $filieres->orderBy('name')->get();
        
        $niveaux = ESBTPNiveauEtude::where('is_active', true);
        if ($niveauFilter) {
            $niveaux->where('id', $niveauFilter);
        }
        $niveaux = $niveaux->orderBy('year')->get();
        
        $combinaisons = [];
        
        foreach ($filieres as $filiere) {
            foreach ($niveaux as $niveau) {
                // Compter les planifications pour cette combinaison
                $planifications = ESBTPPlanificationAcademique::where('filiere_id', $filiere->id)
                    ->where('niveau_etude_id', $niveau->id);
                    
                if ($anneeId) {
                    $planifications->where('annee_universitaire_id', $anneeId);
                }
                
                $planifications = $planifications->with('matiere')->get();
                
                // Filtrer les planifications valides (matières réellement liées)
                $planificationsValides = $planifications->filter(function($planification) use ($filiere, $niveau) {
                    // Ignorer si matière supprimée
                    if (!$planification->matiere) {
                        return false;
                    }
                    
                    // Vérifier si la matière est réellement liée à cette combinaison
                    return ESBTPMatiere::where('id', $planification->matiere->id)
                        ->where('is_active', true)
                        ->whereHas('filieres', function($query) use ($filiere) {
                            $query->where('esbtp_filieres.id', $filiere->id);
                        })
                        ->whereHas('niveaux', function($query) use ($niveau) {
                            $query->where('esbtp_niveau_etudes.id', $niveau->id);
                        })
                        ->exists();
                });
                
                // CORRECTION : Compter d'abord toutes les matières liées à cette combinaison
                $matieresLieesALaCombinaisonCount = ESBTPMatiere::where('is_active', true)
                    ->whereHas('filieres', function($query) use ($filiere) {
                        $query->where('esbtp_filieres.id', $filiere->id);
                    })
                    ->whereHas('niveaux', function($query) use ($niveau) {
                        $query->where('esbtp_niveau_etudes.id', $niveau->id);
                    })
                    ->count();
                
                // Calculer les statistiques
                $totalMatieres = $matieresLieesALaCombinaisonCount; // Toutes les matières liées à cette combinaison
                $totalHeures = $planificationsValides->sum('volume_horaire_total');
                $matieresConfigurees = $planificationsValides->where('volume_horaire_total', '>', 0)->count(); // Matières liées ET configurées
                $planificationsS1 = $planificationsValides->where('semestre', 1);
                $planificationsS2 = $planificationsValides->where('semestre', 2);
                $matieresConfigureesS1 = $planificationsS1->where('volume_horaire_total', '>', 0)->count();
                $matieresConfigureesS2 = $planificationsS2->where('volume_horaire_total', '>', 0)->count();
                $totalHeuresS1 = $planificationsS1->sum('volume_horaire_total');
                $totalHeuresS2 = $planificationsS2->sum('volume_horaire_total');
                
                // Déterminer le statut
                $statusClass = '';
                $statusIcon = '';
                $statusText = '';
                
                if ($totalMatieres == 0) {
                    $statusClass = 'not-configured';
                    $statusIcon = 'fa-plus-circle';
                    $statusText = 'Non configuré';
                } elseif ($matieresConfigurees == $totalMatieres) {
                    $statusClass = 'configured';
                    $statusIcon = 'fa-check-circle';
                    $statusText = 'Complet';
                } else {
                    $statusClass = 'partial';
                    $statusIcon = 'fa-exclamation-triangle';
                    $statusText = 'Partiel';
                }
                
                $combinaisons[] = [
                    'filiere' => $filiere,
                    'niveau' => $niveau,
                    'name' => $filiere->name . ' - ' . $niveau->name,
                    'total_matieres' => $totalMatieres,
                    'total_heures' => $totalHeures,
                    'matieres_configurees' => $matieresConfigurees,
                    'matieres_configurees_s1' => $matieresConfigureesS1,
                    'matieres_configurees_s2' => $matieresConfigureesS2,
                    'total_heures_s1' => $totalHeuresS1,
                    'total_heures_s2' => $totalHeuresS2,
                    'status_class' => $statusClass,
                    'status_icon' => $statusIcon,
                    'status_text' => $statusText,
                    'planifications' => $planificationsValides
                ];
            }
        }
        
        return collect($combinaisons)->sortBy('name');
    }
    
    /**
     * Récupérer les matières disponibles pour configuration par AJAX
     */
    public function getMatieresPourConfiguration(Request $request)
    {
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $anneeId = $request->input('annee_id');
        $semestre = (int) $request->input('semestre', 1);
        $semestre = in_array($semestre, [1, 2], true) ? $semestre : 1;
        
        if (!$filiereId || !$niveauId) {
            return response()->json([
                'success' => false,
                'message' => 'Filière et niveau requis'
            ]);
        }
        
        // Récupérer les matières liées à cette combinaison filière/niveau
        $matieresLiees = ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', function($query) use ($filiereId) {
                $query->where('esbtp_filieres.id', $filiereId);
            })
            ->whereHas('niveaux', function($query) use ($niveauId) {
                $query->where('esbtp_niveau_etudes.id', $niveauId);
            })
            ->orderBy('name')
            ->get();

        // Si aucune matière liée, proposer toutes les matières disponibles pour association
        if ($matieresLiees->isEmpty()) {
            $matieres = ESBTPMatiere::where('is_active', true)->orderBy('name')->get();
            $modeAssociation = true;
        } else {
            $matieres = $matieresLiees;
            $modeAssociation = false;
        }
        
        // Récupérer les planifications existantes pour cette combinaison
        $planificationsExistantes = ESBTPPlanificationAcademique::where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where(function ($query) use ($semestre) {
                $query->where('semestre', $semestre)->orWhereNull('semestre');
            });
            
        if ($anneeId) {
            $planificationsExistantes->where('annee_universitaire_id', $anneeId);
        }
        
        $planificationsExistantes = $planificationsExistantes->with('matiere')->get()->keyBy('matiere_id');
        
        $html = '';
        
        if ($modeAssociation) {
            // Mode association : permettre de sélectionner les matières à associer
            $html .= '<div class="alert alert-info mb-4">';
            $html .= '<i class="fas fa-info-circle me-2"></i>';
            $html .= '<strong>Aucune matière associée</strong><br>';
            $html .= 'Sélectionnez les matières que vous souhaitez associer à cette combinaison, puis configurez leurs volumes horaires.';
            $html .= '</div>';
            
            $html .= '<div class="mb-3">';
            $html .= '<button type="button" class="btn btn-secondary btn-sm" id="select-all-matieres">Tout sélectionner</button> ';
            $html .= '<button type="button" class="btn btn-secondary btn-sm" id="deselect-all-matieres">Tout désélectionner</button>';
            $html .= '</div>';
        }

        foreach ($matieres as $matiere) {
            $planificationExistante = $planificationsExistantes->get($matiere->id);
            $volumeActuel = $planificationExistante ? $planificationExistante->volume_horaire_total : 0;
            $isConfigured = $volumeActuel > 0;
            $isAssociated = !$modeAssociation; // En mode normal, toutes les matières sont associées
            
            $cardClass = 'config-matiere-card';
            if ($modeAssociation) {
                $cardClass .= ' association-mode';
            }
            if ($isConfigured) {
                $cardClass .= ' configured';
            }
            
            $html .= '<div class="' . $cardClass . '" data-matiere-id="' . $matiere->id . '">';
            
            if ($modeAssociation) {
                // Checkbox pour sélectionner la matière à associer
                $html .= '<div class="matiere-selection">';
                $html .= '<input type="checkbox" class="form-check-input matiere-checkbox" id="matiere_' . $matiere->id . '" name="associations[' . $matiere->id . ']" value="1">';
                $html .= '<label class="form-check-label" for="matiere_' . $matiere->id . '"></label>';
                $html .= '</div>';
            }
            
            $html .= '<div class="matiere-details">';
            $html .= '<div class="matiere-name">' . htmlspecialchars($matiere->name) . '</div>';
            if ($matiere->description) {
                $html .= '<div class="matiere-description">' . htmlspecialchars($matiere->description) . '</div>';
            }
            
            // Afficher info sur les associations existantes
            if ($modeAssociation) {
                $filieres = $matiere->filieres->pluck('name')->toArray();
                $niveaux = $matiere->niveaux->pluck('name')->toArray();
                if (!empty($filieres) || !empty($niveaux)) {
                    $html .= '<div class="matiere-associations text-muted small">';
                    if (!empty($filieres)) {
                        $html .= '<span>Filières: ' . implode(', ', array_slice($filieres, 0, 2)) . (count($filieres) > 2 ? '...' : '') . '</span><br>';
                    }
                    if (!empty($niveaux)) {
                        $html .= '<span>Niveaux: ' . implode(', ', array_slice($niveaux, 0, 2)) . (count($niveaux) > 2 ? '...' : '') . '</span>';
                    }
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
            $html .= '<div class="matiere-config">';
            
            // Section volume horaire
            $html .= '<div class="config-section volume-config-section">';
            $html .= '<label class="config-label"><i class="fas fa-clock"></i>Volume horaire</label>';
            $html .= '<div class="volume-config">';
            if ($modeAssociation) {
                // En mode association, le volume n'est configurable que si la matière est sélectionnée
                $html .= '<input type="number" name="volumes[' . $matiere->id . ']" value="' . $volumeActuel . '" min="0" max="200" class="form-control volume-input" placeholder="0" disabled>';
            } else {
                $html .= '<input type="number" name="volumes[' . $matiere->id . ']" value="' . $volumeActuel . '" min="0" max="200" class="form-control volume-input" placeholder="0">';
            }
            $html .= '<span class="volume-unit">heures</span>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Section assignation de professeurs avec tableau structuré
            $html .= '<div class="config-section teacher-config-section">';
            $html .= '<div class="d-flex justify-content-between align-items-center mb-2">';
            $html .= '<label class="config-label mb-0"><i class="fas fa-users"></i>Professeur(s) assigné(s)</label>';
            $html .= '</div>';

            // Récupérer les professeurs déjà assignés à cette matière
            $assignedTeachers = [];
            if (!$modeAssociation && $planificationExistante) {
                $assignedTeacherIds = DB::table('esbtp_planification_teachers')
                    ->where('planification_id', $planificationExistante->id)
                    ->pluck('teacher_id')
                    ->toArray();
                $assignedTeachers = $assignedTeacherIds;
            }

            // Récupérer les professeurs actifs
            $teachers = \App\Models\ESBTPTeacher::where('is_active', true)
                ->with('user')
                ->orderBy('user_id')
                ->get();

            if ($teachers->isEmpty()) {
                $html .= '<div class="text-muted text-center py-3">';
                $html .= '<i class="fas fa-info-circle me-2"></i>Aucun enseignant disponible';
                $html .= '</div>';
            } else {
                // Champ de recherche
                $html .= '<div class="teacher-search-wrapper mb-2" style="position: relative;">';
                $html .= '<input type="text" class="form-control teacher-search-input" data-matiere-id="' . $matiere->id . '" placeholder="Rechercher un enseignant (nom ou spécialisation)..." style="padding-left: 35px; border-radius: 6px; border: 1px solid #ddd;">';
                $html .= '<i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>';
                $html .= '</div>';

                // Conteneur du tableau
                $html .= '<div class="teacher-table-container" data-matiere-id="' . $matiere->id . '" style="max-height: 400px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 6px;">';

                // Tableau HTML
                $html .= '<table class="table table-sm table-hover mb-0 teacher-selection-table" style="margin-bottom: 0 !important;">';

                // Header avec checkbox global
                $html .= '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10; border-bottom: 2px solid #dee2e6;">';
                $html .= '<tr>';
                $html .= '<th style="width: 50px; text-align: center; padding: 10px;">';
                $html .= '<input type="checkbox" class="teacher-select-all-checkbox" data-matiere-id="' . $matiere->id . '" title="Tout sélectionner / Tout désélectionner">';
                $html .= '</th>';
                $html .= '<th style="padding: 10px;">Nom complet</th>';
                $html .= '<th style="padding: 10px; width: 30%;">Spécialisation</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                // Body
                $html .= '<tbody>';
                foreach ($teachers as $teacher) {
                    $teacherName = $teacher->user ? $teacher->user->name : $teacher->matricule;
                    $specialization = $teacher->specialization ?: '-';
                    $checked = in_array($teacher->id, $assignedTeachers) ? ' checked' : '';

                    $html .= '<tr class="teacher-row" data-teacher-name="' . htmlspecialchars(strtolower($teacherName)) . '" data-teacher-spec="' . htmlspecialchars(strtolower($specialization)) . '">';

                    // Colonne checkbox
                    $html .= '<td style="text-align: center; padding: 8px;">';
                    $html .= '<input type="checkbox" name="teachers[' . $matiere->id . '][]" value="' . $teacher->id . '"' . $checked . ' class="teacher-checkbox">';
                    $html .= '</td>';

                    // Colonne nom
                    $html .= '<td style="padding: 8px;">';
                    $html .= '<strong>' . htmlspecialchars($teacherName) . '</strong>';
                    $html .= '</td>';

                    // Colonne spécialisation
                    $html .= '<td style="padding: 8px; color: #666;">';
                    $html .= htmlspecialchars($specialization);
                    $html .= '</td>';

                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';

                $html .= '</div>'; // Fin teacher-table-container

                // Message aucun résultat (caché par défaut)
                $html .= '<div class="teacher-no-results text-muted text-center py-3" data-matiere-id="' . $matiere->id . '" style="display: none;">';
                $html .= '<i class="fas fa-search me-2"></i>Aucun enseignant trouvé';
                $html .= '</div>';
            }

            // Compteur dynamique
            $html .= '<div class="teacher-selection-count" data-matiere-id="' . $matiere->id . '" style="margin-top: 10px; padding: 8px; background: #f0f8ff; border-radius: 6px; text-align: center; font-size: 13px;">';
            $html .= '<i class="fas fa-info-circle text-primary"></i> ';
            $html .= '<span class="count-text">Sélectionnez un ou plusieurs enseignants</span>';
            $html .= '</div>';

            $html .= '</div>'; // Fin teacher-config-section
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    /**
     * Sauvegarder la configuration des volumes horaires
     */
    public function saveVolumeConfiguration(Request $request)
    {
        \Log::info('🚀 ========== DÉBUT SAUVEGARDE PLANNING GÉNÉRAL (BACKEND) ==========');
        \Log::info('📥 Données reçues:', $request->all());

        $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|in:1,2',
            'volumes' => 'nullable|array',
            'volumes.*' => 'nullable|integer|min:0|max:200',
            'teachers' => 'nullable|array',
            'teachers.*' => 'nullable|array',
            'teachers.*.*' => 'exists:esbtp_teachers,id'
        ]);

        \Log::info('✅ Validation passée');
        \Log::info('📊 Volumes à sauvegarder:', $request->volumes);
        \Log::info('👨‍🏫 Professeurs à assigner:', $request->teachers ?? []);

        DB::beginTransaction();

        try {
            $savedCount = 0;
            $updatedCount = 0;
            $teachersAssignedCount = 0;
            
            foreach ($request->volumes as $matiereId => $volume) {
                if ($volume && $volume > 0) {
                    \Log::info("📚 Traitement Matière ID: {$matiereId}, Volume: {$volume}h");

                    $planification = ESBTPPlanificationAcademique::updateOrCreate(
                        [
                            'annee_universitaire_id' => $request->annee_id,
                            'filiere_id' => $request->filiere_id,
                            'niveau_etude_id' => $request->niveau_id,
                            'matiere_id' => $matiereId,
                            'semestre' => $request->semestre
                        ],
                        [
                            'volume_horaire_total' => $volume,
                            'volume_horaire_cm' => 0,
                            'volume_horaire_td' => 0,
                            'volume_horaire_tp' => 0,
                            'coefficient' => 1,
                            'credits_ects' => 0,
                            'statut' => ESBTPPlanificationAcademique::STATUT_PLANIFIE,
                            'updated_by' => Auth::id(),
                            'created_by' => Auth::id()
                        ]
                    );

                    if ($planification->wasRecentlyCreated) {
                        \Log::info("  ➕ Planification créée (ID: {$planification->id})");
                        $savedCount++;
                    } else {
                        \Log::info("  🔄 Planification mise à jour (ID: {$planification->id})");
                        $updatedCount++;
                    }
                    
                    // Gérer les assignations de professeurs pour cette planification
                    if (isset($request->teachers[$matiereId]) && !empty($request->teachers[$matiereId])) {
                        $teachersForThisMatiere = $request->teachers[$matiereId];
                        \Log::info("  👨‍🏫 Assignation de " . count($teachersForThisMatiere) . " professeur(s) pour matière {$matiereId}");
                        \Log::info("  📋 IDs professeurs: " . json_encode($teachersForThisMatiere));

                        // Supprimer les anciennes assignations
                        $deletedCount = DB::table('esbtp_planification_teachers')
                            ->where('planification_id', $planification->id)
                            ->delete();

                        \Log::info("  🗑️ {$deletedCount} ancienne(s) assignation(s) supprimée(s)");

                        // Récupérer le premier enseignant pour le définir comme principal
                        $firstTeacherId = null;

                        // Ajouter les nouvelles assignations
                        foreach ($teachersForThisMatiere as $index => $teacherId) {
                            if (!empty($teacherId)) {
                                DB::table('esbtp_planification_teachers')->insert([
                                    'planification_id' => $planification->id,
                                    'teacher_id' => $teacherId,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);

                                \Log::info("    ✅ Teacher ID {$teacherId} assigné à planification {$planification->id}");
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
                            $teacher = \App\Models\ESBTPTeacher::find($firstTeacherId);
                            if ($teacher && $teacher->user_id) {
                                $planification->update([
                                    'enseignant_principal_id' => $teacher->user_id
                                ]);
                                \Log::info("    ⭐ Enseignant principal défini: Teacher ID {$firstTeacherId} (User ID {$teacher->user_id})");
                            } else {
                                \Log::warning("    ⚠️ Impossible de définir enseignant principal: Teacher {$firstTeacherId} non trouvé ou sans user_id");
                            }
                        }
                    } else {
                        \Log::info("  ❌ Aucun professeur sélectionné pour matière {$matiereId}");

                        // Si aucun professeur sélectionné, supprimer les assignations existantes
                        $deletedCount = DB::table('esbtp_planification_teachers')
                            ->where('planification_id', $planification->id)
                            ->delete();

                        \Log::info("  🗑️ {$deletedCount} assignation(s) supprimée(s)");

                        // Supprimer l'enseignant principal
                        $planification->update([
                            'enseignant_principal_id' => null
                        ]);
                        \Log::info("  🚫 Enseignant principal retiré");
                    }
                } else {
                    // Supprimer si volume = 0
                    $planificationsToDelete = ESBTPPlanificationAcademique::where('annee_universitaire_id', $request->annee_id)
                        ->where('filiere_id', $request->filiere_id)
                        ->where('niveau_etude_id', $request->niveau_id)
                        ->where('matiere_id', $matiereId)
                        ->get();
                    
                    // Supprimer les assignations de professeurs associées
                    foreach ($planificationsToDelete as $planification) {
                        DB::table('esbtp_planification_teachers')
                            ->where('planification_id', $planification->id)
                            ->delete();
                    }
                    
                    // Supprimer les planifications
                    ESBTPPlanificationAcademique::where('annee_universitaire_id', $request->annee_id)
                        ->where('filiere_id', $request->filiere_id)
                        ->where('niveau_etude_id', $request->niveau_id)
                        ->where('matiere_id', $matiereId)
                        ->delete();
                }
            }
            
            DB::commit();

            \Log::info('💾 Transaction committée avec succès');
            \Log::info('📊 Résumé de la sauvegarde:', [
                'Planifications créées' => $savedCount,
                'Planifications mises à jour' => $updatedCount,
                'Professeurs assignés au total' => $teachersAssignedCount
            ]);

            $message = 'Configuration sauvegardée avec succès.';
            if ($savedCount > 0) {
                $message .= " {$savedCount} nouvelle(s) planification(s) créée(s).";
            }
            if ($updatedCount > 0) {
                $message .= " {$updatedCount} planification(s) mise(s) à jour.";
            }
            if ($teachersAssignedCount > 0) {
                $message .= " {$teachersAssignedCount} professeur(s) assigné(s).";
            }

            \Log::info('✅ Message de succès: ' . $message);
            \Log::info('========== FIN SAUVEGARDE PLANNING GÉNÉRAL (SUCCESS) ==========');

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('❌ ========== ERREUR SAUVEGARDE PLANNING GÉNÉRAL ==========');
            \Log::error('Message d\'erreur: ' . $e->getMessage());
            \Log::error('Fichier: ' . $e->getFile());
            \Log::error('Ligne: ' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('========== FIN ERREUR ==========');

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vue coordinateur - Vue d'ensemble avec options avancées
     */
    private function indexCoordinateur($anneeId, $annees, $anneeSelectionnee, $stats)
    {
        // Pour la vue d'ensemble, on utilise la vue index.blade.php
        // mais avec des données supplémentaires pour les coordinateurs
        
        // Répartition des heures par matière
        $repartitionMatieres = $this->calculerRepartitionMatieres($anneeId);
        
        // Emplois du temps par classe
        $emploisTempsClasses = $this->getEmploisTempsParClasse($anneeId);
        
        // Progression vs objectifs
        $progressionObjectifs = $this->calculerProgressionObjectifs($anneeId);
        
        // Classes avec conflits d'horaires
        $conflitsHoraires = $this->detecterConflitsHoraires($anneeId);

        return view('esbtp.planning-general.index', compact(
            'annees', 'anneeSelectionnee', 'stats', 'repartitionMatieres',
            'emploisTempsClasses', 'progressionObjectifs', 'conflitsHoraires'
        ));
    }

    /**
     * Vue enseignant - Planning personnel
     */
    private function indexEnseignant($anneeId, $annees, $anneeSelectionnee, $stats)
    {
        $user = Auth::user();
        
        // Séances de l'enseignant
        $seancesEnseignant = ESBTPSeanceCours::where('teacher_id', $user->id)
            ->where('type', ESBTPSeanceCours::TYPE_COURSE)
            ->whereHas('emploiTemps', function($query) use ($anneeId) {
                if ($anneeId) {
                    $query->where('annee_universitaire_id', $anneeId);
                }
            })
            ->with(['matiere', 'classe', 'emploiTemps'])
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get();
        
        // Grouper par semaine et jour
        $planningHebdomadaire = $this->grouperSeancesParSemaine($seancesEnseignant);
        
        // Charge horaire par matière
        $chargeHoraireMatiere = $this->calculerChargeHoraireEnseignant($user->id, $anneeId);

        return view('esbtp.planning-general.enseignant', compact(
            'annees', 'anneeSelectionnee', 'stats', 'seancesEnseignant',
            'planningHebdomadaire', 'chargeHoraireMatiere'
        ));
    }

    /**
     * Vue étudiant - Planning de classe
     */
    private function indexEtudiant($anneeId, $annees, $anneeSelectionnee, $stats)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();
        
        if (!$etudiant) {
            return view('esbtp.planning-general.etudiant-no-profile', compact(
                'annees', 'anneeSelectionnee'
            ));
        }
        
        // Inscription active pour l'année sélectionnée
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->where('annee_universitaire_id', $anneeId)
            ->first();
        
        if (!$inscription) {
            return view('esbtp.planning-general.etudiant-no-inscription', compact(
                'annees', 'anneeSelectionnee', 'etudiant'
            ));
        }
        
        // Emploi du temps de la classe
        $emploiTemps = ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
            ->where('is_current', true)
            ->first();
        
        $seancesClasse = $emploiTemps ? $emploiTemps->seances()
            ->with(['matiere', 'enseignant'])
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get() : collect();
        
        // Planning hebdomadaire
        $planningHebdomadaire = $this->grouperSeancesParJour($seancesClasse);

        return view('esbtp.planning-general.etudiant', compact(
            'annees', 'anneeSelectionnee', 'stats', 'etudiant', 'inscription',
            'emploiTemps', 'seancesClasse', 'planningHebdomadaire'
        ));
    }

    /**
     * Vue annuelle - Calendrier complet de l'année
     */
    public function annuel(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId) ?? 
                            ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeSelectionnee) {
            return redirect()->route('esbtp.planning-general.index')
                ->with('error', 'Aucune année universitaire trouvée.');
        }

        // Calendrier mensuel de l'année
        $calendrierMensuel = $this->genererCalendrierAnnuel($anneeSelectionnee);
        
        // Événements académiques importants
        $evenementsAcademiques = $this->getEvenementsAcademiques($anneeSelectionnee);
        
        // Statistiques par mois
        $statistiquesMensuelles = $this->calculerStatistiquesMensuelles($anneeSelectionnee);
        
        // Toutes les années pour le sélecteur
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.planning-general.annuel', compact(
            'anneeSelectionnee', 'calendrierMensuel', 'evenementsAcademiques', 
            'statistiquesMensuelles', 'annees'
        ));
    }

    /**
     * Répartition des heures par matière
     */
    public function repartitionMatieres(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $classeId = $request->input('classe_id');
        $periode = $request->input('periode', 'annee'); // semestre1, semestre2, ou annee
        
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        
        // Gérer la sélection d'année
        if (empty($anneeId) || $anneeId === 'all') {
            // "Toutes les années" - utiliser l'année courante pour l'affichage mais traiter toutes les données
            $anneeSelectionnee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if (!$anneeSelectionnee && $annees->count() > 0) {
                $anneeSelectionnee = $annees->first();
            }
            // Pour "toutes les années", on passe null à la méthode de calcul
            $anneeIdPourCalcul = null;
        } else {
            $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId);
            $anneeIdPourCalcul = $anneeId;
        }
        $classes = ESBTPClasse::with(['filiere', 'niveau'])->orderBy('name')->get();
        
        // Toujours afficher toutes les combinaisons détaillées
        // Le filtrage par classe se fera côté client en JavaScript
        $repartition = $this->calculerRepartitionMatieresParClasse($anneeIdPourCalcul, $periode, $classeId);
        $statsRepartition = $this->calculerStatsRepartitionParClasse($repartition);
        $chartData = $this->buildChartDataParClasse($repartition);
        
        // Debug: vérifier les données
        \Log::info('Repartition data:', [
            'count' => $repartition->count(),
            'anneeId' => $anneeId,
            'anneeIdPourCalcul' => $anneeIdPourCalcul,
            'classeId' => $classeId,
            'periode' => $periode,
            'sample' => $repartition->take(2)->toArray()
        ]);
        
        // Comparaison avec les objectifs
        $objectifsComparaison = $this->comparerAvecObjectifs($repartition, $classeId, $anneeIdPourCalcul);

        return view('esbtp.planning-general.repartition-matieres', compact(
            'annees', 'anneeSelectionnee', 'classes', 'repartition', 'objectifsComparaison', 'anneeId', 'classeId',
            'statsRepartition', 'chartData'
        ));
    }

    /**
     * Planning par coordinateur - Interface de gestion
     */
    public function coordinateur(Request $request)
    {
        if (!Auth::user()->hasRole(['coordinateur', 'superAdmin'])) {
            abort(403, 'Accès réservé aux coordinateurs.');
        }

        $anneeId = $request->input('annee_id');
        $mois = $request->input('mois', now()->month);
        
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId) ?? 
                            ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Allocation horaire par module
        $allocationHoraire = $this->getAllocationHoraireModules($anneeId);
        
        // Programmation hebdomadaire
        $programmationHebdomadaire = $this->getProgrammationHebdomadaire($anneeId, $mois);
        
        // Codes d'émargement actifs
        $codesEmargement = $this->getCodesEmargementActifs();
        
        // Taux de présence par classe
        $tauxPresenceClasses = $this->calculerTauxPresenceClasses($anneeId);

        return view('esbtp.planning-general.coordinateur', compact(
            'annees', 'anneeSelectionnee', 'allocationHoraire', 'programmationHebdomadaire',
            'codesEmargement', 'tauxPresenceClasses', 'mois'
        ));
    }

    /**
     * Configuration rapide d'une planification via AJAX
     */
    public function configureRapide(Request $request)
    {
        try {
            // Convertir la période du format frontend vers le format service
            $requestData = $request->all();
            if (isset($requestData['periode'])) {
                $requestData['semestre'] = match($requestData['periode']) {
                    'semestre1', 'S1' => 1,
                    'semestre2', 'S2' => 2,
                    'annee', 'Annuel' => 1, // Par défaut semestre 1 pour annuel
                    default => 1
                };
            }

            // Utiliser le service de configuration
            if ($request->filiere_id && $request->niveau_id) {
                // Configuration spécifique
                $planification = $this->planningConfigService->configureRapide($requestData);
                $matiere = ESBTPMatiere::find($request->matiere_id);
                
                return response()->json([
                    'success' => true,
                    'message' => "Configuration du planning de {$matiere->name} pour la filière/niveau spécifié enregistrée avec succès !",
                    'planification' => $planification
                ]);
            } else {
                // Configuration en lot pour toutes les combinaisons existantes
                $selections = $this->getExistingCombinations($request->annee_id);
                $baseConfig = $requestData;
                
                $results = $this->planningConfigService->configureBulk($selections, $baseConfig);
                $successCount = $results->where('success', true)->count();
                $matiere = ESBTPMatiere::find($request->matiere_id);
                
                return response()->json([
                    'success' => true,
                    'message' => "Configuration du planning de {$matiere->name} appliquée à {$successCount} combinaison(s) filière/niveau",
                    'results' => $results
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides : ' . implode(', ', $e->validator->errors()->all())
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la configuration : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Méthode pour la configuration avancée utilisant le service
     */
    public function configureAvance(Request $request)
    {
        try {
            $planification = $this->planningConfigService->configureAvance($request->all());
            
            return redirect()->back()->with('success', 'Configuration avancée enregistrée avec succès !');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la configuration : ' . $e->getMessage());
        }
    }

    /**
     * API pour obtenir les options de configuration d'une matière
     */
    public function getConfigurationOptions(int $matiereId, Request $request)
    {
        try {
            $options = $this->planningConfigService->getConfigurationOptions($matiereId, $request->input('annee_id'));
            
            return response()->json([
                'success' => true,
                'options' => $options
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les combinaisons existantes pour configuration en lot
     */
    private function getExistingCombinations(int $anneeId): array
    {
        $combinaisons = ESBTPPlanificationAcademique::where('annee_universitaire_id', $anneeId)
            ->select('filiere_id', 'niveau_id')
            ->distinct()
            ->get();

        return $combinaisons->map(function ($combinaison) use ($anneeId) {
            return [
                'filiere_id' => $combinaison->filiere_id,
                'niveau_id' => $combinaison->niveau_id,
                'annee_id' => $anneeId
            ];
        })->toArray();
    }

    // ============ MÉTHODES PRIVÉES DE CALCUL ============

    /**
     * Calcule les statistiques générales
     */
    private function calculerStatistiquesGenerales($anneeId)
    {
        $query = ESBTPSeanceCours::query();
        
        if ($anneeId) {
            $query->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
            });
        }

        return [
            'total_seances' => $query->count(),
            'total_heures' => $query->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600')),
            'total_classes' => ESBTPClasse::whereHas('emploiTemps', function($q) use ($anneeId) {
                if ($anneeId) {
                    $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                }
            })->count(),
            'total_matieres' => ESBTPMatiere::whereHas('seancesCours', function($q) use ($anneeId) {
                if ($anneeId) {
                    $q->whereHas('emploiTemps', function($q2) use ($anneeId) {
                        $q2->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                    });
                }
            })->count(),
            'total_enseignants' => User::role('enseignant')->whereHas('seancesCours', function($q) use ($anneeId) {
                if ($anneeId) {
                    $q->whereHas('emploiTemps', function($q2) use ($anneeId) {
                        $q2->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                    });
                }
            })->count()
        ];
    }

    /**
     * Calcule la répartition des heures par matière
     */
    private function calculerRepartitionMatieres($anneeId, $periode = 'annee')
    {
        // Récupérer les heures réalisées par matière
        $query = ESBTPSeanceCours::with('matiere')
            ->select('matiere_id', DB::raw('COUNT(*) as nb_seances'), 
                    DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures'))
            ->groupBy('matiere_id');
        
        if ($anneeId) {
            $query->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
            });
        }

        $results = $query->get();
        
        // Récupérer les heures planifiées par matière selon la période
        $planificationsQuery = ESBTPPlanificationAcademique::with('matiere')
            ->select('matiere_id', DB::raw('SUM(volume_horaire_total) as heures_planifiees'))
            ->groupBy('matiere_id');
            
        if ($anneeId) {
            $planificationsQuery->where('annee_universitaire_id', $anneeId);
        }
        
        // Filtrer par semestre si spécifié
        if ($periode === 'semestre1') {
            $planificationsQuery->where(function ($query) {
                $query->where('semestre', 1)->orWhereNull('semestre');
            });
        } elseif ($periode === 'semestre2') {
            $planificationsQuery->where(function ($query) {
                $query->where('semestre', 2)->orWhereNull('semestre');
            });
        }
        
        $planifications = $planificationsQuery->get()->keyBy('matiere_id');
        
        // Calcul du total pour les pourcentages
        $totalHeures = $results->sum('total_heures');
        
        return $results->map(function($item) use ($totalHeures, $planifications, $periode) {
            $planification = $planifications->get($item->matiere_id);
            $heuresPlanifiees = $planification ? $planification->heures_planifiees : 0;
            $heuresRestantes = max(0, $heuresPlanifiees - $item->total_heures);
            
            return [
                'matiere' => $item->matiere,
                'nb_seances' => $item->nb_seances,
                'total_heures' => round($item->total_heures, 2),
                'heures_planifiees' => round($heuresPlanifiees, 2),
                'heures_restantes' => round($heuresRestantes, 2),
                'pourcentage_realise' => $heuresPlanifiees > 0 ? round(($item->total_heures / $heuresPlanifiees) * 100, 1) : 0,
                'pourcentage' => $totalHeures > 0 ? round(($item->total_heures / $totalHeures) * 100, 1) : 0,
                'est_configure' => $heuresPlanifiees > 0,
                'periode' => $periode
            ];
        });
    }

    /**
     * Groupe les séances par jour de la semaine
     */
    private function grouperSeancesParJour($seances)
    {
        $jours = [
            0 => 'Lundi', 1 => 'Mardi', 2 => 'Mercredi', 
            3 => 'Jeudi', 4 => 'Vendredi', 5 => 'Samedi'
        ];

        $planning = [];
        foreach ($jours as $numero => $nom) {
            $planning[$nom] = $seances->where('jour', $numero)->sortBy('heure_debut');
        }

        return $planning;
    }

    /**
     * Calcule la charge horaire par matière pour un enseignant
     */
    private function calculerChargeHoraireEnseignant($enseignantId, $anneeId)
    {
        $query = ESBTPSeanceCours::where('teacher_id', $enseignantId)
            ->where('type', ESBTPSeanceCours::TYPE_COURSE)
            ->with('matiere')
            ->select('matiere_id', DB::raw('COUNT(*) as nb_seances'),
                    DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures'))
            ->groupBy('matiere_id');

        if ($anneeId) {
            $query->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
            });
        }

        return $query->get();
    }

    /**
     * Génère le calendrier annuel par mois
     */
    private function genererCalendrierAnnuel($annee)
    {
        // Créer des dates complètes à partir des années
        $debut = Carbon::create($annee->start_date, 9, 1); // 1er septembre de l'année de début
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin de l'année de fin
        
        $calendrier = [];
        $moisCourant = $debut->copy()->startOfMonth();
        
        while ($moisCourant->lte($fin)) {
            $calendrier[] = [
                'mois' => $moisCourant->format('Y-m'),
                'nom' => $moisCourant->translatedFormat('F Y'),
                'semaines' => $this->genererSemainesMois($moisCourant)
            ];
            
            $moisCourant->addMonth();
        }
        
        return $calendrier;
    }

    /**
     * Génère les semaines d'un mois
     */
    private function genererSemainesMois($mois)
    {
        $debut = $mois->copy()->startOfMonth()->startOfWeek();
        $fin = $mois->copy()->endOfMonth()->endOfWeek();
        
        $semaines = [];
        $semaineActuelle = $debut->copy();
        
        while ($semaineActuelle->lte($fin)) {
            $jours = [];
            for ($i = 0; $i < 7; $i++) {
                $jours[] = [
                    'date' => $semaineActuelle->copy(),
                    'dans_mois' => $semaineActuelle->month === $mois->month,
                    'est_aujourd_hui' => $semaineActuelle->isToday()
                ];
                $semaineActuelle->addDay();
            }
            $semaines[] = $jours;
        }
        
        return $semaines;
    }

    /**
     * Méthodes placeholder pour les fonctionnalités avancées
     */
    private function getEmploisTempsParClasse($anneeId) { 
        return collect(); 
    }
    
    private function calculerProgressionObjectifs($anneeId) { 
        return []; 
    }
    
    private function detecterConflitsHoraires($anneeId) { 
        return []; 
    }
    
    private function grouperSeancesParSemaine($seances) { 
        return []; 
    }

    /**
     * Interface d'émargement intégrée au planning général
     */
    public function emargement(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = $anneeId ? ESBTPAnneeUniversitaire::find($anneeId) : 
                           ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Codes actifs (peut y en avoir plusieurs maintenant)
        $activeCodes = ESBTPDailyCode::with(['seance.matiere', 'seance.classe'])
            ->where('status', 'active')
            ->where('valid_until', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Code actif principal (pour compatibilité avec la vue existante)
        $activeCode = $activeCodes->first();

        // Codes récents
        $recentCodes = ESBTPDailyCode::with('generator')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Récupérer les séances à venir (aujourd'hui et dans les 3 prochains jours)
        $seancesAVenir = collect();
        if ($anneeSelectionnee) {
            $today = now();
            $todayDayOfWeek = $today->dayOfWeek === 0 ? 7 : $today->dayOfWeek; // Dimanche = 7, Lundi = 1
            
            $seancesAVenir = ESBTPSeanceCours::with(['matiere', 'classe', 'teacher', 'emploiTemps'])
                ->whereHas('emploiTemps', function($query) use ($anneeSelectionnee) {
                    $query->where('annee_universitaire_id', $anneeSelectionnee->id)
                          ->where(function($subQuery) {
                              $subQuery->where('is_active', true)
                                       ->orWhere('is_current', true);
                          });
                })
                ->where('is_active', true)
                ->where(function($query) use ($today, $todayDayOfWeek) {
                    // Séances avec date précise (aujourd'hui et 3 prochains jours)
                    $query->whereBetween('date_seance', [
                        $today->format('Y-m-d'), 
                        $today->copy()->addDays(3)->format('Y-m-d')
                    ])
                    // Ou séances récurrentes pour aujourd'hui et prochains jours
                    ->orWhere(function($subQuery) use ($todayDayOfWeek) {
                        $subQuery->whereNull('date_seance')
                                 ->where('is_recurring', true)
                                 ->where(function($dayQuery) use ($todayDayOfWeek) {
                                     // Aujourd'hui et les 3 prochains jours de la semaine
                                     for ($i = 0; $i < 4; $i++) {
                                         $day = (($todayDayOfWeek + $i - 1) % 7) + 1;
                                         if ($day > 6) $day = $day - 6; // Samedi max
                                         $dayQuery->orWhere('jour', $day);
                                     }
                                 });
                    })
                    // Ou séances d'aujourd'hui (récurrentes sans date précise)
                    ->orWhere(function($subQuery) use ($todayDayOfWeek) {
                        $subQuery->where('jour', $todayDayOfWeek)
                                 ->whereNull('date_seance');
                    });
                })
                ->whereIn('type', ['course', 'td', 'tp', 'cm']) // Types de cours (pas pauses)
                ->orderByRaw('CASE WHEN date_seance IS NOT NULL THEN date_seance ELSE CURDATE() END')
                ->orderBy('heure_debut')
                ->take(10)
                ->get();
        }

        // Statistiques des émargements
        $stats = $this->calculerStatsEmargement($anneeSelectionnee);

        return view('esbtp.planning-general.emargement', compact(
            'annees', 'anneeSelectionnee', 'activeCode', 'activeCodes', 'recentCodes', 'stats', 'seancesAVenir'
        ));
    }

    /**
     * Génère un nouveau code d'émargement depuis l'interface planning
     */
    public function genererCodeEmargement(Request $request)
    {
        $request->validate([
            'type' => 'required|in:session,journee,personnalise',
            'duree' => 'required|integer|min:1|max:72',
            'activation' => 'nullable|in:immediate,1,2,4,24',
            'description' => 'nullable|string|max:255',
            'seance_id' => 'nullable|exists:esbtp_seance_cours,id'
        ]);

        try {
            $seanceId = $request->input('seance_id');
            
            // Logique d'invalidation intelligente
            if ($seanceId) {
                // Si un code est créé pour une séance spécifique, invalider seulement les codes pour cette même séance
                ESBTPDailyCode::where('status', 'active')
                    ->where('seance_id', $seanceId)
                    ->update(['status' => 'expired']);
            } else {
                // Si un code générique est créé, invalider seulement les codes génériques (sans seance_id)
                ESBTPDailyCode::where('status', 'active')
                    ->whereNull('seance_id')
                    ->update(['status' => 'expired']);
            }

            // Calculer les dates d'activation et d'expiration
            $activation = $request->input('activation', 'immediate');
            $duree = (int) $request->input('duree', 2);
            
            $validFrom = $activation === 'immediate' ? now() : now()->addHours((int) $activation);
            $validUntil = $validFrom->copy()->addHours($duree);

            // Générer le code
            $codeData = [
                'code' => ESBTPDailyCode::generateCode(),
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'is_active' => $activation === 'immediate',
                'status' => $activation === 'immediate' ? 'active' : 'scheduled',
                'created_by' => auth()->id(),
                'description' => $request->input('description'),
                'type' => $request->input('type')
            ];

            // Ajouter l'ID de la séance si fourni
            if ($request->filled('seance_id')) {
                $codeData['seance_id'] = $request->input('seance_id');
            }

            $code = ESBTPDailyCode::create($codeData);

            $message = 'Nouveau code généré avec succès : ' . $code->code;
            
            if ($activation !== 'immediate') {
                $heuresActivation = (int) $activation;
                $message .= " (activation dans {$heuresActivation}h)";
            }
            
            $message .= " - Valide pendant {$duree}h";

            return redirect()->route('esbtp.planning-general.emargement', ['annee_id' => $request->input('annee_id')])
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la génération du code : ' . $e->getMessage());
        }
    }

    /**
     * Calcule les statistiques d'émargement
     */
    private function calculerStatsEmargement($anneeSelectionnee)
    {
        if (!$anneeSelectionnee) {
            return [
                'total_emargements_aujourd_hui' => 0,
                'enseignants_emarges_aujourd_hui' => 0,
                'codes_generes_semaine' => 0,
                'taux_emargement_semaine' => 0,
            ];
        }

        $aujourd_hui = now()->toDateString();
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        return [
            'total_emargements_aujourd_hui' => ESBTPTeacherAttendance::whereDate('created_at', $aujourd_hui)->count(),
            'enseignants_emarges_aujourd_hui' => ESBTPTeacherAttendance::whereDate('created_at', $aujourd_hui)
                ->distinct('teacher_id')->count(),
            'codes_generes_semaine' => ESBTPDailyCode::whereBetween('created_at', [$debutSemaine, $finSemaine])->count(),
            'taux_emargement_semaine' => $this->calculerTauxEmargementSemaine(),
        ];
    }

    /**
     * Calcule le taux d'émargement de la semaine
     */
    private function calculerTauxEmargementSemaine()
    {
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();
        
        $enseignantsActifs = User::role(['enseignant'])->where('is_active', true)->count();
        $emargements = ESBTPTeacherAttendance::whereBetween('created_at', [$debutSemaine, $finSemaine])
            ->distinct('teacher_id')->count();
            
        return $enseignantsActifs > 0 ? round(($emargements / $enseignantsActifs) * 100, 1) : 0;
    }
    
    private function calculerRepartitionMatieresClasse($classeId, $anneeId, $periode = 'annee') { 
        // Récupérer les informations de la classe pour filtrer les planifications
        $classe = ESBTPClasse::find($classeId);
        
        $query = ESBTPSeanceCours::with('matiere')
            ->whereHas('emploiTemps', function($q) use ($classeId, $anneeId) {
                $q->where('classe_id', $classeId);
                if ($anneeId) {
                    $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                }
            })
            ->select('matiere_id', DB::raw('COUNT(*) as nb_seances'), 
                    DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures'))
            ->groupBy('matiere_id');

        $results = $query->get();
        
        // Récupérer les heures planifiées pour cette classe selon la période
        $planificationsQuery = ESBTPPlanificationAcademique::with('matiere')
            ->select('matiere_id', DB::raw('SUM(volume_horaire_total) as heures_planifiees'))
            ->groupBy('matiere_id');
            
        if ($anneeId) {
            $planificationsQuery->where('annee_universitaire_id', $anneeId);
        }
        
        // Filtrer par classe (filière et niveau)
        if ($classe) {
            $planificationsQuery->where('filiere_id', $classe->filiere_id)
                              ->where('niveau_etude_id', $classe->niveau_id);
        }
        
        // Filtrer par semestre si spécifié
        if ($periode === 'semestre1') {
            $planificationsQuery->where('semestre', 1);
        } elseif ($periode === 'semestre2') {
            $planificationsQuery->where('semestre', 2);
        }
        
        $planifications = $planificationsQuery->get()->keyBy('matiere_id');
        
        // Calcul du total pour les pourcentages
        $totalHeures = $results->sum('total_heures');
        
        return $results->map(function($item) use ($totalHeures, $planifications, $periode) {
            $planification = $planifications->get($item->matiere_id);
            $heuresPlanifiees = $planification ? $planification->heures_planifiees : 0;
            $heuresRestantes = max(0, $heuresPlanifiees - $item->total_heures);
            
            return [
                'matiere' => $item->matiere,
                'nb_seances' => $item->nb_seances,
                'total_heures' => round($item->total_heures, 2),
                'heures_planifiees' => round($heuresPlanifiees, 2),
                'heures_restantes' => round($heuresRestantes, 2),
                'pourcentage_realise' => $heuresPlanifiees > 0 ? round(($item->total_heures / $heuresPlanifiees) * 100, 1) : 0,
                'pourcentage' => $totalHeures > 0 ? round(($item->total_heures / $totalHeures) * 100, 1) : 0,
                'est_configure' => $heuresPlanifiees > 0,
                'periode' => $periode
            ];
        });
    }

    /**
     * Calcule la répartition des matières par classe
     */
    private function calculerRepartitionMatieresParClasse($anneeId, $periode = 'annee', $classeId = null)
    {
        $classesQuery = ESBTPClasse::with(['filiere', 'niveau'])->orderBy('name');
        if ($classeId) {
            $classesQuery->where('id', $classeId);
        }
        $classes = $classesQuery->get();

        if ($classes->isEmpty()) {
            return collect();
        }

        $classIds = $classes->pluck('id')->values();

        $planificationsQuery = ESBTPPlanificationAcademique::with(['matiere'])
            ->select('matiere_id', 'filiere_id', 'niveau_etude_id', DB::raw('SUM(volume_horaire_total) as heures_planifiees'))
            ->groupBy('matiere_id', 'filiere_id', 'niveau_etude_id');

        if ($anneeId) {
            $planificationsQuery->where('annee_universitaire_id', $anneeId);
        }

        if ($periode === 'semestre1') {
            $planificationsQuery->where(function ($query) {
                $query->where('semestre', 1)->orWhereNull('semestre');
            });
        } elseif ($periode === 'semestre2') {
            $planificationsQuery->where(function ($query) {
                $query->where('semestre', 2)->orWhereNull('semestre');
            });
        }

        $planifications = $planificationsQuery->get();
        $planificationsByCombo = $planifications->groupBy(function ($planification) {
            return $planification->filiere_id . '_' . $planification->niveau_etude_id;
        });

        $seancesQuery = ESBTPSeanceCours::query()
            ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
            ->leftJoin(DB::raw('(
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
            ) as latest_attendance'), 'latest_attendance.course_id', '=', 'esbtp_seance_cours.id')
            ->where(function ($query) {
                $query->whereNull('latest_attendance.status')
                      ->orWhere('latest_attendance.status', '!=', 'absent');
            })
            ->whereIn('esbtp_seance_cours.classe_id', $classIds)
            ->select(
                'esbtp_seance_cours.matiere_id',
                'esbtp_seance_cours.classe_id',
                'esbtp_seance_cours.teacher_id',
                DB::raw('COUNT(DISTINCT esbtp_seance_cours.id) as nb_seances'),
                DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(esbtp_seance_cours.heure_fin, esbtp_seance_cours.heure_debut))/3600) as total_heures')
            )
            ->groupBy('esbtp_seance_cours.matiere_id', 'esbtp_seance_cours.classe_id', 'esbtp_seance_cours.teacher_id');

        if ($anneeId) {
            $seancesQuery->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
        }

        if ($periode === 'semestre1') {
            $seancesQuery->where(function ($query) {
                $query->where('esbtp_emploi_temps.semestre', 1)
                    ->orWhereNull('esbtp_emploi_temps.semestre');
            });
        } elseif ($periode === 'semestre2') {
            $seancesQuery->where(function ($query) {
                $query->where('esbtp_emploi_temps.semestre', 2)
                    ->orWhereNull('esbtp_emploi_temps.semestre');
            });
        }

        $seancesRealisees = $seancesQuery->get();

        $teacherIds = $seancesRealisees->pluck('teacher_id')->filter()->unique();
        $teachers = ESBTPTeacher::with('user')
            ->whereIn('id', $teacherIds)
            ->get()
            ->keyBy('id');

        $matiereIds = $planifications->pluck('matiere_id')
            ->merge($seancesRealisees->pluck('matiere_id'))
            ->filter()
            ->unique();
        $matieres = ESBTPMatiere::whereIn('id', $matiereIds)->get()->keyBy('id');

        return $classes->map(function ($classe) use ($planificationsByCombo, $seancesRealisees, $teachers, $matieres, $periode) {
            $comboKey = $classe->filiere_id . '_' . $classe->niveau_etude_id;
            $planificationsCombo = $planificationsByCombo->get($comboKey, collect())->keyBy('matiere_id');
            $seancesClasse = $seancesRealisees->where('classe_id', $classe->id);

            $matiereIdsClasse = $planificationsCombo->keys()
                ->merge($seancesClasse->pluck('matiere_id'))
                ->filter()
                ->unique();

            $matieresData = $matiereIdsClasse->map(function ($matiereId) use ($planificationsCombo, $seancesClasse, $teachers, $matieres, $periode) {
                $planification = $planificationsCombo->get($matiereId);
                $heuresPlanifiees = $planification ? (float) $planification->heures_planifiees : 0;

                $seancesMatiere = $seancesClasse->where('matiere_id', $matiereId);
                $totalHeures = (float) $seancesMatiere->sum('total_heures');
                $nbSeances = (int) $seancesMatiere->sum('nb_seances');

                $enseignants = $seancesMatiere->groupBy('teacher_id')->map(function ($items, $teacherId) use ($teachers) {
                    $teacher = $teachers->get($teacherId);
                    if (!$teacher) {
                        return null;
                    }

                    $teacherName = trim((string) ($teacher->title ? $teacher->title . ' ' : '') . ($teacher->name ?? ''));

                    return [
                        'id' => $teacher->id,
                        'name' => $teacherName ?: 'Enseignant',
                        'heures_realisees' => round((float) $items->sum('total_heures'), 2),
                        'nb_seances' => (int) $items->sum('nb_seances')
                    ];
                })->filter()->values();

                $heuresRestantes = max(0, $heuresPlanifiees - $totalHeures);

                return [
                    'matiere' => $matieres->get($matiereId),
                    'nb_seances' => $nbSeances,
                    'heures_realisees' => round($totalHeures, 2),
                    'heures_planifiees' => round($heuresPlanifiees, 2),
                    'heures_restantes' => round($heuresRestantes, 2),
                    'pourcentage_realise' => $heuresPlanifiees > 0 ? round(($totalHeures / $heuresPlanifiees) * 100, 1) : 0,
                    'est_configure' => $heuresPlanifiees > 0,
                    'periode' => $periode,
                    'enseignants' => $enseignants
                ];
            })->filter()->sortBy(function ($item) {
                return $item['matiere']->name ?? '';
            })->values();

            $totalPlanifiees = $matieresData->sum('heures_planifiees');
            $totalRealisees = $matieresData->sum('heures_realisees');
            $totalSeances = $matieresData->sum('nb_seances');
            $taux = $totalPlanifiees > 0 ? round(($totalRealisees / $totalPlanifiees) * 100, 1) : 0;

            $matieresData = $matieresData->map(function ($item) use ($totalRealisees) {
                $item['pourcentage'] = $totalRealisees > 0 ? round(($item['heures_realisees'] / $totalRealisees) * 100, 1) : 0;
                return $item;
            })->values();

            return [
                'classe' => $classe,
                'matieres' => $matieresData,
                'stats' => [
                    'matieres_count' => $matieresData->count(),
                    'heures_planifiees_total' => round($totalPlanifiees, 2),
                    'heures_realisees_total' => round($totalRealisees, 2),
                    'nb_seances_total' => (int) $totalSeances,
                    'taux_realisation' => $taux
                ]
            ];
        });
    }

    private function calculerStatsRepartitionParClasse($repartition)
    {
        $totalClasses = $repartition->count();
        $totalMatieres = $repartition->sum(function ($item) {
            return $item['stats']['matieres_count'] ?? 0;
        });
        $totalHeuresPlanifiees = $repartition->sum(function ($item) {
            return $item['stats']['heures_planifiees_total'] ?? 0;
        });
        $totalHeuresRealisees = $repartition->sum(function ($item) {
            return $item['stats']['heures_realisees_total'] ?? 0;
        });
        $totalSeances = $repartition->sum(function ($item) {
            return $item['stats']['nb_seances_total'] ?? 0;
        });
        $tauxGlobal = $totalHeuresPlanifiees > 0 ? round(($totalHeuresRealisees / $totalHeuresPlanifiees) * 100, 1) : 0;

        return [
            'classes' => $totalClasses,
            'matieres' => $totalMatieres,
            'heures_planifiees' => round($totalHeuresPlanifiees, 1),
            'heures_realisees' => round($totalHeuresRealisees, 1),
            'seances' => (int) $totalSeances,
            'taux_realisation' => $tauxGlobal
        ];
    }

    private function buildChartDataParClasse($repartition)
    {
        $labels = $repartition->map(function ($item) {
            return $item['classe']->name ?? 'Classe';
        })->values();

        $planifiees = $repartition->map(function ($item) {
            return (float) ($item['stats']['heures_planifiees_total'] ?? 0);
        })->values();

        $realisees = $repartition->map(function ($item) {
            return (float) ($item['stats']['heures_realisees_total'] ?? 0);
        })->values();

        return [
            'labels' => $labels,
            'planifiees' => $planifiees,
            'realisees' => $realisees
        ];
    }

    
    private function comparerAvecObjectifs($repartition, $classeId, $anneeId) { 
        return []; 
    }
    
    private function getAllocationHoraireModules($anneeId) { 
        if (!$anneeId) {
            return [];
        }
        
        // Récupérer les vraies données des planifications académiques
        $allocations = ESBTPPlanificationAcademique::with(['matiere'])
            ->where('annee_universitaire_id', $anneeId)
            ->select('matiere_id', DB::raw('SUM(volume_horaire_total) as total_heures'))
            ->groupBy('matiere_id')
            ->get();
        
        return $allocations->map(function($allocation) {
            return [
                'module' => $allocation->matiere ? $allocation->matiere->name : 'Matière inconnue',
                'description' => $allocation->matiere ? $allocation->matiere->description : 'Description non disponible',
                'heures' => intval($allocation->total_heures ?? 0)
            ];
        })->sortByDesc('heures')->values()->toArray();
    }
    
    private function getProgrammationHebdomadaire($anneeId, $mois) { 
        if (!$anneeId) {
            return [];
        }
        
        // Récupérer les séances de cours pour l'année et le mois sélectionnés
        $seances = ESBTPSeanceCours::with(['matiere', 'classe', 'enseignant'])
            ->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            })
            ->whereMonth('created_at', $mois)
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get();
        
        // Grouper par jour de la semaine
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $programmation = [];
        
        foreach ($jours as $jour) {
            $programmation[$jour] = $seances->where('jour', ucfirst($jour))->map(function($seance) {
                return [
                    'id' => $seance->id,
                    'matiere' => $seance->matiere ? $seance->matiere->name : 'Matière inconnue',
                    'horaire' => $seance->heure_debut . '-' . $seance->heure_fin,
                    'classe' => $seance->classe ? $seance->classe->name : 'Classe inconnue'
                ];
            })->values()->toArray();
        }
        
        return $programmation;
    }
    
    private function getCodesEmargementActifs() { 
        if (!class_exists('App\Models\ESBTPDailyCode')) {
            return [];
        }
        
        // Récupérer les codes d'émargement actifs ou récents
        $codes = \App\Models\ESBTPDailyCode::whereDate('created_at', '>=', Carbon::today()->subDays(1))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return $codes->map(function($code) {
            $expireTime = $code->valid_until ?? Carbon::parse($code->created_at)->addMinutes(30);
            $now = Carbon::now();
            $expire = $now->greaterThan($expireTime);
            
            // Essayer de trouver les émargements associés pour identifier le cours
            $coursInfo = 'Cours général';
            if (class_exists('App\Models\ESBTPTeacherAttendance')) {
                $attendance = \App\Models\ESBTPTeacherAttendance::where('daily_code_id', $code->id)
                    ->with(['course.matiere', 'course.classe'])
                    ->first();
                    
                if ($attendance && $attendance->course) {
                    $matiere = $attendance->course->matiere ? $attendance->course->matiere->name : 'Matière inconnue';
                    $classe = $attendance->course->classe ? $attendance->course->classe->name : 'Classe inconnue';
                    $coursInfo = $matiere . ' - ' . $classe;
                }
            }
            
            return [
                'id' => $code->id,
                'code' => $code->code,
                'cours' => $coursInfo,
                'expire_dans' => $expire ? 'Expiré' : $expireTime->diffForHumans($now),
                'expire' => $expire
            ];
        })->toArray();
    }
    
    private function calculerTauxPresenceClasses($anneeId) { 
        if (!$anneeId) {
            return [];
        }
        
        // Récupérer les classes avec leurs taux de présence réels
        $classes = ESBTPClasse::with(['etudiants'])
            ->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            })
            ->get();
        
        return $classes->map(function($classe) {
            $effectif = $classe->etudiants->count();
            
            if ($effectif == 0) {
                return [
                    'nom' => $classe->name,
                    'effectif' => 0,
                    'taux' => 0
                ];
            }
            
            // Calculer le taux de présence moyen sur les 30 derniers jours
            if (class_exists('App\Models\ESBTPAttendance')) {
                $presences = \App\Models\ESBTPAttendance::whereHas('seanceCours', function($q) use ($classe) {
                        $q->where('classe_id', $classe->id);
                    })
                    ->whereDate('date', '>=', Carbon::today()->subDays(30))
                    ->get();
                
                $totalPresences = $presences->where('statut', 'present')->count();
                $totalSeances = $presences->count();
                
                $taux = $totalSeances > 0 ? round(($totalPresences / $totalSeances) * 100, 1) : 0;
            } else {
                // Taux simulé basé sur l'ID de la classe pour cohérence
                $taux = 70 + ($classe->id % 25);
            }
            
            return [
                'nom' => $classe->name,
                'effectif' => $effectif,
                'taux' => $taux
            ];
        })->sortByDesc('taux')->values()->toArray();
    }
    
    private function getEvenementsAcademiques($annee) { 
        // Récupérer les événements réels depuis la base de données
        if (class_exists('App\Models\ESBTPEvenementAcademique')) {
            $evenements = \App\Models\ESBTPEvenementAcademique::where('annee_universitaire_id', $annee->id)
                ->where('afficher_calendrier', true)
                ->where('is_active', true)
                ->orderBy('date_debut')
                ->get();
            
            return $evenements->map(function($evenement) {
                return [
                    'titre' => $evenement->titre,
                    'date' => $evenement->date_debut->format('d/m/Y'),
                    'description' => $evenement->description,
                    'icon' => $evenement->icone,
                    'type' => $evenement->type,
                    'couleur' => $evenement->couleur,
                    'statut' => $evenement->statut,
                    'lieu' => $evenement->lieu,
                    'heure_debut' => $evenement->heure_debut ? $evenement->heure_debut->format('H:i') : null,
                    'heure_fin' => $evenement->heure_fin ? $evenement->heure_fin->format('H:i') : null,
                    'date_fin' => $evenement->date_fin ? $evenement->date_fin->format('d/m/Y') : null
                ];
            })->toArray();
        }
        
        // Données de démonstration si le modèle n'existe pas
        $debut = Carbon::create($annee->start_date, 9, 1); // 1er septembre
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin
        
        return [
            [
                'titre' => 'Rentrée Académique',
                'date' => $debut->copy()->format('d/m/Y'),
                'description' => 'Ouverture officielle de l\'année académique - Toutes filières',
                'icon' => 'graduation-cap',
                'type' => 'rentree',
                'couleur' => 'success'
            ],
            [
                'titre' => 'Période d\'Orientation',
                'date' => $debut->copy()->addWeeks(2)->format('d/m/Y'),
                'description' => 'Séances d\'information pour nouveaux étudiants',
                'icon' => 'compass',
                'type' => 'orientation',
                'couleur' => 'info'
            ],
            [
                'titre' => 'Examens de 1er Semestre',
                'date' => Carbon::create($annee->start_date, 12, 15)->format('d/m/Y'),
                'description' => 'Évaluations semestrielles - Toutes classes',
                'icon' => 'file-alt',
                'type' => 'examens',
                'couleur' => 'warning'
            ],
            [
                'titre' => 'Vacances Semestrielles',
                'date' => Carbon::create($annee->start_date, 12, 22)->format('d/m/Y'),
                'description' => 'Période de vacances inter-semestrielle',
                'icon' => 'calendar-times',
                'type' => 'vacances',
                'couleur' => 'secondary'
            ],
            [
                'titre' => 'Reprise 2e Semestre',
                'date' => Carbon::create($annee->annee_fin, 1, 8)->format('d/m/Y'),
                'description' => 'Début du second semestre académique',
                'icon' => 'play-circle',
                'type' => 'reprise',
                'couleur' => 'success'
            ],
            [
                'titre' => 'Soutenances de Stages',
                'date' => Carbon::create($annee->annee_fin, 4, 15)->format('d/m/Y'),
                'description' => 'Présentations des stages professionnels - BTS2',
                'icon' => 'presentation',
                'type' => 'soutenances',
                'couleur' => 'primary'
            ],
            [
                'titre' => 'Examens Finaux',
                'date' => Carbon::create($annee->annee_fin, 5, 20)->format('d/m/Y'),
                'description' => 'Examens de fin d\'année - Toutes filières',
                'icon' => 'certificate',
                'type' => 'examens',
                'couleur' => 'danger'
            ],
            [
                'titre' => 'Cérémonie de Remise des Diplômes',
                'date' => Carbon::create($annee->annee_fin, 6, 20)->format('d/m/Y'),
                'description' => 'Cérémonie officielle de graduation',
                'icon' => 'trophy',
                'type' => 'ceremonie',
                'couleur' => 'primary'
            ],
            [
                'titre' => 'Fermeture Année Académique',
                'date' => $fin->copy()->format('d/m/Y'),
                'description' => 'Clôture officielle de l\'année académique',
                'icon' => 'flag-checkered',
                'type' => 'fermeture',
                'couleur' => 'dark'
            ]
        ];
    }
    
    private function calculerStatistiquesMensuelles($annee) { 
        // Calcul des statistiques mensuelles réelles
        $debut = Carbon::create($annee->start_date, 9, 1); // 1er septembre
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin
        
        $statistiques = [];
        $moisCourant = $debut->copy()->startOfMonth();
        
        while ($moisCourant->lte($fin)) {
            // Compter les séances programmées pour ce mois
            $totalSeances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($annee) {
                $query->where('annee_universitaire_id', $annee->id);
            })
            ->where('type', ESBTPSeanceCours::TYPE_COURSE)
            ->whereMonth('created_at', $moisCourant->month)
            ->whereYear('created_at', $moisCourant->year)
            ->count();
            
            // Calculer les heures totales
            $totalHeures = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($annee) {
                $query->where('annee_universitaire_id', $annee->id);
            })
            ->where('type', ESBTPSeanceCours::TYPE_COURSE)
            ->whereMonth('created_at', $moisCourant->month)
            ->whereYear('created_at', $moisCourant->year)
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600'));
            
            // Compter les planifications pour ce mois
            $totalPlanifications = ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
                ->whereMonth('created_at', $moisCourant->month)
                ->whereYear('created_at', $moisCourant->year)
                ->count();
            
            $statistiques[] = [
                'mois' => $moisCourant->translatedFormat('F Y'),
                'mois_court' => $moisCourant->translatedFormat('M'),
                'total_seances' => $totalSeances,
                'total_heures' => round($totalHeures, 1),
                'total_planifications' => $totalPlanifications,
                'date' => $moisCourant->copy()
            ];
            
            $moisCourant->addMonth();
        }
        
        return $statistiques;
    }

    /**
     * Calculer les statistiques de planification pour une filière/niveau/semestre
     */
    private function calculerStatistiquesPlanification($anneeId, $filiereId, $niveauId, $semestre)
    {
        if (!$anneeId || !$filiereId || !$niveauId) {
            return [
                'total_matieres_planifiees' => 0,
                'total_heures_planifiees' => 0,
                'total_enseignants_assignes' => 0,
                'repartition_types_cours' => ['cm' => 0, 'td' => 0, 'tp' => 0],
                'statuts_planification' => [],
                'taux_completion' => 0
            ];
        }

        $planifications = ESBTPPlanificationAcademique::forAnnee($anneeId)
            ->forFiliere($filiereId)
            ->forNiveau($niveauId)
            ->forSemestre($semestre)
            ->get();

        $totalMatieresDisponibles = ESBTPMatiere::whereHas('classes', function($query) use ($filiereId, $niveauId) {
            $query->where('filiere_id', $filiereId)
                  ->where('niveau_etude_id', $niveauId);
        })->count();

        $stats = [
            'total_matieres_planifiees' => $planifications->count(),
            'total_heures_planifiees' => $planifications->sum('volume_horaire_total'),
            'total_enseignants_assignes' => $planifications->whereNotNull('enseignant_principal_id')->pluck('enseignant_principal_id')->unique()->count(),
            'repartition_types_cours' => [
                'cm' => $planifications->sum('volume_horaire_cm'),
                'td' => $planifications->sum('volume_horaire_td'),
                'tp' => $planifications->sum('volume_horaire_tp')
            ],
            'statuts_planification' => $planifications->groupBy('statut')->map(function($items) {
                return $items->count();
            }),
            'taux_completion' => $totalMatieresDisponibles > 0 
                ? round(($planifications->count() / $totalMatieresDisponibles) * 100, 1)
                : 0
        ];

        return $stats;
    }

    /**
     * Créer ou mettre à jour une planification académique
     */
    public function storePlanification(Request $request)
    {
        $request->validate([
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'semestre' => 'required|integer|min:1|max:4',
            'volume_horaire_total' => 'required|integer|min:1|max:200',
            'volume_horaire_cm' => 'nullable|integer|min:0',
            'volume_horaire_td' => 'nullable|integer|min:0',
            'volume_horaire_tp' => 'nullable|integer|min:0',
            'coefficient' => 'nullable|numeric|min:0.5|max:10',
            'credits_ects' => 'nullable|integer|min:1|max:30',
            'enseignant_principal_id' => 'nullable|exists:users,id',
            'periode_debut' => 'nullable|date',
            'periode_fin' => 'nullable|date|after:periode_debut',
            'objectifs_pedagogiques' => 'nullable|string|max:1000',
            'prerequis' => 'nullable|string|max:500',
            'observations' => 'nullable|string|max:500'
        ]);

        // Vérifier que la somme des volumes horaires détaillés correspond au total
        $sommeDetaillee = ($request->volume_horaire_cm ?? 0) + 
                         ($request->volume_horaire_td ?? 0) + 
                         ($request->volume_horaire_tp ?? 0);
        
        if ($sommeDetaillee > 0 && $sommeDetaillee != $request->volume_horaire_total) {
            return back()->withErrors([
                'volume_horaire_total' => 'La somme des heures CM + TD + TP doit correspondre au volume horaire total'
            ]);
        }

        $planification = ESBTPPlanificationAcademique::updateOrCreate(
            [
                'annee_universitaire_id' => $request->annee_universitaire_id,
                'filiere_id' => $request->filiere_id,
                'niveau_etude_id' => $request->niveau_etude_id,
                'matiere_id' => $request->matiere_id,
                'semestre' => $request->semestre
            ],
            [
                'volume_horaire_total' => $request->volume_horaire_total,
                'volume_horaire_cm' => $request->volume_horaire_cm ?? 0,
                'volume_horaire_td' => $request->volume_horaire_td ?? 0,
                'volume_horaire_tp' => $request->volume_horaire_tp ?? 0,
                'coefficient' => $request->coefficient ?? 1,
                'credits_ects' => $request->credits_ects ?? 0,
                'enseignant_principal_id' => $request->enseignant_principal_id,
                'periode_debut' => $request->periode_debut,
                'periode_fin' => $request->periode_fin,
                'objectifs_pedagogiques' => $request->objectifs_pedagogiques,
                'prerequis' => $request->prerequis,
                'observations' => $request->observations,
                'statut' => ESBTPPlanificationAcademique::STATUT_PLANIFIE,
                'updated_by' => Auth::id()
            ]
        );

        if ($planification->wasRecentlyCreated) {
            $planification->update(['created_by' => Auth::id()]);
        }

        return redirect()->back()->with('success', 'Planification académique enregistrée avec succès');
    }

    /**
     * Supprimer une planification académique
     */
    public function destroyPlanification($id)
    {
        $planification = ESBTPPlanificationAcademique::findOrFail($id);
        
        // Vérifier que la planification peut être supprimée
        if (!$planification->isModifiable()) {
            return back()->withErrors(['error' => 'Cette planification ne peut plus être supprimée (statut: ' . $planification->statut . ')']);
        }

        $planification->delete();

        return redirect()->back()->with('success', 'Planification supprimée avec succès');
    }

    /**
     * Valider une planification académique
     */
    public function validerPlanification($id)
    {
        $planification = ESBTPPlanificationAcademique::findOrFail($id);
        
        // Valider la cohérence
        $erreurs = $planification->validerCoherence();
        if (!empty($erreurs)) {
            return back()->withErrors(['error' => 'Erreurs de validation: ' . implode(', ', $erreurs)]);
        }

        $planification->update([
            'statut' => ESBTPPlanificationAcademique::STATUT_VALIDE,
            'updated_by' => Auth::id()
        ]);

        return redirect()->back()->with('success', 'Planification validée avec succès');
    }

    /**
     * Interface admin pour voir l'impact des émargements sur la progression des planifications
     */
    public function impactEmargements(Request $request)
    {
        // Vérifier les permissions
        if (!Auth::user()->hasAnyRole(['superAdmin', 'coordinateur', 'directeurEtudes'])) {
            abort(403, 'Accès réservé aux administrateurs et coordinateurs.');
        }

        $anneeId = $request->input('annee_id');
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $periodeDebut = $request->input('periode_debut');
        $periodeFin = $request->input('periode_fin');

        // Données de base
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId) ?? 
                            ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('year')->get();

        if ($anneeSelectionnee) {
            $anneeId = $anneeSelectionnee->id;
        }

        // Récupérer les données d'impact des émargements
        $impactData = $this->calculerImpactEmargements($anneeId, $filiereId, $niveauId, $periodeDebut, $periodeFin);
        
        // Statistiques générales d'émargement
        $statistiquesEmargement = $this->calculerStatistiquesEmargement($anneeId, $filiereId, $niveauId, $periodeDebut, $periodeFin);

        // Progression par matière avec émargements
        $progressionMatieres = $this->calculerProgressionAvecEmargements($anneeId, $filiereId, $niveauId);

        // Enseignants avec taux d'émargement
        $enseignantsEmargement = $this->calculerTauxEmargementEnseignants($anneeId, $filiereId, $niveauId);

        return view('esbtp.planning-general.impact-emargements', compact(
            'annees', 'anneeSelectionnee', 'filieres', 'niveaux', 
            'impactData', 'statistiquesEmargement', 'progressionMatieres', 'enseignantsEmargement',
            'anneeId', 'filiereId', 'niveauId', 'periodeDebut', 'periodeFin'
        ));
    }

    /**
     * Calculer l'impact des émargements sur les planifications
     */
    private function calculerImpactEmargements($anneeId, $filiereId = null, $niveauId = null, $periodeDebut = null, $periodeFin = null)
    {
        $query = ESBTPPlanificationAcademique::with(['matiere', 'enseignantPrincipal', 'filiere', 'niveauEtude'])
            ->where('annee_universitaire_id', $anneeId);

        if ($filiereId) {
            $query->where('filiere_id', $filiereId);
        }
        if ($niveauId) {
            $query->where('niveau_etude_id', $niveauId);
        }

        $planifications = $query->get();

        return $planifications->map(function($planification) use ($periodeDebut, $periodeFin) {
            // Récupérer les émargements validés pour cette planification
            $emargements = $this->getEmargementsValidesParPlanification($planification, $periodeDebut, $periodeFin);
            
            // Calculer les heures effectuées via émargements
            $heuresEmargement = $emargements->sum(function($emargement) {
                if ($emargement->course) {
                    return Carbon::parse($emargement->course->heure_fin)->diffInMinutes(
                        Carbon::parse($emargement->course->heure_debut)
                    ) / 60;
                }
                return 0;
            });

            // Progression calculée
            $tauxProgression = $planification->volume_horaire_total > 0 
                ? round(($planification->heures_effectuees / $planification->volume_horaire_total) * 100, 1)
                : 0;

            $tauxProgressionEmargement = $planification->volume_horaire_total > 0 
                ? round(($heuresEmargement / $planification->volume_horaire_total) * 100, 1)
                : 0;

            return [
                'planification' => $planification,
                'heures_planifiees' => $planification->volume_horaire_total,
                'heures_effectuees_base' => $planification->heures_effectuees ?? 0,
                'heures_emargement' => round($heuresEmargement, 2),
                'nb_emargements_valides' => $emargements->count(),
                'taux_progression_base' => $tauxProgression,
                'taux_progression_emargement' => $tauxProgressionEmargement,
                'ecart_heures' => round($heuresEmargement - ($planification->heures_effectuees ?? 0), 2),
                'derniere_maj_heures' => $planification->derniere_mise_a_jour_heures,
                'statut_synchronisation' => $this->evaluerStatutSynchronisation($planification, $heuresEmargement),
                'emargements_recents' => $emargements->take(5)
            ];
        })->sortByDesc('nb_emargements_valides');
    }

    /**
     * Récupérer les émargements validés pour une planification
     */
    private function getEmargementsValidesParPlanification($planification, $periodeDebut = null, $periodeFin = null)
    {
        $query = \App\Models\ESBTPTeacherAttendance::with('course')
            ->where('status', 'validated')
            ->whereHas('course', function($q) use ($planification) {
                $q->where('matiere_id', $planification->matiere_id)
                  ->where('teacher_id', $planification->enseignant_principal_id);
            });

        if ($periodeDebut) {
            $query->where('date', '>=', $periodeDebut);
        }
        if ($periodeFin) {
            $query->where('date', '<=', $periodeFin);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Calculer les statistiques générales d'émargement
     */
    private function calculerStatistiquesEmargement($anneeId, $filiereId = null, $niveauId = null, $periodeDebut = null, $periodeFin = null)
    {
        $queryBase = \App\Models\ESBTPTeacherAttendance::query();
        
        // Filtrer par année via les séances
        $queryBase->whereHas('seance.emploiTemps', function($q) use ($anneeId) {
            $q->where('annee_universitaire_id', $anneeId);
        });

        // Filtrer par filière/niveau si spécifié
        if ($filiereId || $niveauId) {
            $queryBase->whereHas('course.classe', function($q) use ($filiereId, $niveauId) {
                if ($filiereId) $q->where('filiere_id', $filiereId);
                if ($niveauId) $q->where('niveau_etude_id', $niveauId);
            });
        }

        // Filtrer par période
        if ($periodeDebut) {
            $queryBase->where('date', '>=', $periodeDebut);
        }
        if ($periodeFin) {
            $queryBase->where('date', '<=', $periodeFin);
        }

        return [
            'total_emargements' => $queryBase->count(),
            'emargements_valides' => $queryBase->where('status', 'validated')->count(),
            'emargements_pending' => $queryBase->where('status', 'pending')->count(),
            'emargements_expires' => $queryBase->where('status', 'expired')->count(),
            'taux_validation' => $queryBase->count() > 0 
                ? round(($queryBase->where('status', 'validated')->count() / $queryBase->count()) * 100, 1)
                : 0,
            'heures_totales_emargees' => $this->calculerHeuresTotalesEmargees($queryBase),
            'derniere_mise_a_jour' => $queryBase->where('status', 'validated')->max('validated_at')
        ];
    }

    /**
     * Calculer la progression par matière avec émargements
     */
    private function calculerProgressionAvecEmargements($anneeId, $filiereId = null, $niveauId = null)
    {
        $query = ESBTPPlanificationAcademique::with(['matiere', 'enseignantPrincipal'])
            ->where('annee_universitaire_id', $anneeId);

        if ($filiereId) {
            $query->where('filiere_id', $filiereId);
        }
        if ($niveauId) {
            $query->where('niveau_etude_id', $niveauId);
        }

        $planifications = $query->get();

        return $planifications->groupBy('matiere_id')->map(function($planificationsByMatiere) {
            $matiere = $planificationsByMatiere->first()->matiere;
            $totalPlanifie = $planificationsByMatiere->sum('volume_horaire_total');
            $totalEffectue = $planificationsByMatiere->sum('heures_effectuees');
            
            // Calculer heures via émargements
            $totalEmargement = 0;
            foreach ($planificationsByMatiere as $planif) {
                $emargements = $this->getEmargementsValidesParPlanification($planif);
                $totalEmargement += $emargements->sum(function($emargement) {
                    if ($emargement->seance) {
                        return Carbon::parse($emargement->seance->heure_fin)->diffInMinutes(
                            Carbon::parse($emargement->seance->heure_debut)
                        ) / 60;
                    }
                    return 0;
                });
            }

            return [
                'matiere' => $matiere,
                'heures_planifiees' => $totalPlanifie,
                'heures_effectuees' => $totalEffectue,
                'heures_emargement' => round($totalEmargement, 2),
                'taux_progression_base' => $totalPlanifie > 0 ? round(($totalEffectue / $totalPlanifie) * 100, 1) : 0,
                'taux_progression_emargement' => $totalPlanifie > 0 ? round(($totalEmargement / $totalPlanifie) * 100, 1) : 0,
                'nb_planifications' => $planificationsByMatiere->count()
            ];
        })->sortByDesc('heures_emargement');
    }

    /**
     * Calculer le taux d'émargement des enseignants
     */
    private function calculerTauxEmargementEnseignants($anneeId, $filiereId = null, $niveauId = null)
    {
        $query = User::role('enseignant')
            ->whereHas('seancesCours.emploiTemps', function($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            });

        if ($filiereId || $niveauId) {
            $query->whereHas('seancesCours.classe', function($q) use ($filiereId, $niveauId) {
                if ($filiereId) $q->where('filiere_id', $filiereId);
                if ($niveauId) $q->where('niveau_etude_id', $niveauId);
            });
        }

        $enseignants = $query->with(['seancesCours', 'teacherAttendances'])->get();

        return $enseignants->map(function($enseignant) use ($anneeId) {
            $seancesTotales = $enseignant->seancesCours()
                ->whereHas('emploiTemps', function($q) use ($anneeId) {
                    $q->where('annee_universitaire_id', $anneeId);
                })
                ->count();

            $emargementsValides = $enseignant->teacherAttendances()
                ->where('status', 'validated')
                ->whereHas('seance.emploiTemps', function($q) use ($anneeId) {
                    $q->where('annee_universitaire_id', $anneeId);
                })
                ->count();

            $tauxEmargement = $seancesTotales > 0 ? round(($emargementsValides / $seancesTotales) * 100, 1) : 0;

            return [
                'enseignant' => $enseignant,
                'seances_totales' => $seancesTotales,
                'emargements_valides' => $emargementsValides,
                'taux_emargement' => $tauxEmargement,
                'dernier_emargement' => $enseignant->teacherAttendances()
                    ->where('status', 'validated')
                    ->latest('validated_at')
                    ->first()
            ];
        })->sortByDesc('taux_emargement');
    }

    /**
     * Calculer les heures totales émargées
     */
    private function calculerHeuresTotalesEmargees($query)
    {
        $emargements = $query->where('status', 'validated')->with('course')->get();
        
        return $emargements->sum(function($emargement) {
            if ($emargement->course) {
                return Carbon::parse($emargement->course->heure_fin)->diffInMinutes(
                    Carbon::parse($emargement->course->heure_debut)
                ) / 60;
            }
            return 0;
        });
    }

    /**
     * Évaluer le statut de synchronisation entre planification et émargements
     */
    private function evaluerStatutSynchronisation($planification, $heuresEmargement)
    {
        $heuresEffectuees = $planification->heures_effectuees ?? 0;
        $ecart = abs($heuresEmargement - $heuresEffectuees);

        if ($ecart < 0.5) {
            return ['statut' => 'synchronise', 'message' => 'Parfaitement synchronisé'];
        } elseif ($ecart < 2) {
            return ['statut' => 'leger_ecart', 'message' => 'Léger écart acceptable'];
        } elseif ($heuresEmargement > $heuresEffectuees) {
            return ['statut' => 'emargement_superieur', 'message' => 'Émargements en avance sur planification'];
        } else {
            return ['statut' => 'planification_superieure', 'message' => 'Planification en avance sur émargements'];
        }
    }
}
