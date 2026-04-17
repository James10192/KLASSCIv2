<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPAttendanceManualHours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPAcademicYear;
use App\Models\ESBTPTeacherAttendance;
use App\Http\Requests\Attendance\StoreManualAttendanceHoursRequest;
use App\Services\ESBTP\ManualAttendanceHoursService;
use App\Models\Notification;
use App\Notifications\AbsenceNotification;
use App\Services\MatiereService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\AbsenceJustificationNotification;
use App\Notifications\ESBTPNotification;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPAttendanceController extends Controller
{
    protected $matiereService;
    protected $notificationService;

    public function __construct(MatiereService $matiereService, NotificationService $notificationService)
    {
        $this->matiereService = $matiereService;
        $this->notificationService = $notificationService;
    }

    /**
     * Affiche la liste des présences.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get current academic year
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Get active classes for the filter dropdown
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();

        // Get all subjects for the filter dropdown
        $matieres = ESBTPMatiere::orderBy('name')->get();

        // Récupérer les enseignants pour le filtre
        $teachers = User::whereHas('roles', function($query) {
            $query->where('name', 'enseignant');
        })->get();

        // Build the base query with necessary relationships
        $query = ESBTPAttendance::with([
            'etudiant.user',
            'classe',
            'matiere',
            'teacher.user',
            'seanceCours.matiere',
            'seanceCours.emploiTemps.classe'
        ])
        // IMPORTANT: Utiliser finalOnly() pour ne récupérer que les présences finales fusionnées
        // (évite les doublons start + merged)
        ->finalOnly()
        // FILTRE GLOBAL OPTIMISÉ : filtrage direct par annee_universitaire_id
        // Plus besoin du whereHas indirect maintenant que la colonne existe
        ->where('annee_universitaire_id', $anneeUniversitaire->id)
        // ET vérifier que l'étudiant a une inscription active pour cette année
        // ET que cette inscription correspond à la classe de l'attendance (via classe_id de l'attendance)
        ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
            $q->where('annee_universitaire_id', $anneeUniversitaire->id)
              ->where('status', 'active')
              ->whereColumn('esbtp_inscriptions.classe_id', 'esbtp_attendances.classe_id'); // ← CRUCIAL
        });

        // Apply filters
        if ($request->filled('classe_id')) {
            $query->whereHas('seanceCours.emploiTemps', function ($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }

        if ($request->filled('matiere_id')) {
            $query->whereHas('seanceCours', function ($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Get student filter
        if ($request->filled('etudiant_id')) {
            $query->where('etudiant_id', $request->etudiant_id);
        }

        // Create a copy of the query for statistics BEFORE pagination
        $statsQuery = clone $query;

        // Get paginated results
        $attendances = $query->latest('date')->paginate(15);

        // Calculate total statistics
        $statsTotal = ESBTPAttendance::count();

        // Calculate statistics for each status using the unpaginated query ($statsQuery déjà cloné ligne 113)
        $stats = [
            'present' => (clone $statsQuery)->where('statut', 'present')->count(),
            'absent' => (clone $statsQuery)->where('statut', 'absent')->count(),
            'retard' => (clone $statsQuery)->whereIn('statut', ['retard', 'late'])->count(),
            'excuse' => (clone $statsQuery)->where('statut', 'excuse')->count()
        ];

        // Add total to stats array
        $stats['total'] = $stats['present'] + $stats['absent'] + $stats['retard'] + $stats['excuse'];

        // IMPORTANT: Les retards comptent comme présence
        $stats['total_present_with_retards'] = $stats['present'] + $stats['retard'];

        // Calculate total for filtered data
        $filteredTotal = $stats['present'] + $stats['absent'] + $stats['retard'] + $stats['excuse'];

        // Calculate percentages for each status
        // IMPORTANT: Pour le pourcentage de présence, inclure les retards
        $statsPresentPercent = $filteredTotal > 0 ? round(($stats['total_present_with_retards'] / $filteredTotal) * 100) : 0;
        $statsAbsentPercent = $filteredTotal > 0 ? round(($stats['absent'] / $filteredTotal) * 100) : 0;
        $statsRetardPercent = $filteredTotal > 0 ? round(($stats['retard'] / $filteredTotal) * 100) : 0;
        $statsExcusePercent = $filteredTotal > 0 ? round(($stats['excuse'] / $filteredTotal) * 100) : 0;

        $anneeLabel = $anneeUniversitaire ? $anneeUniversitaire->libelle : 'Année en cours';

        // Calculate statistics by day for chart
        $statsParJour = [];
        $statsParStatus = [];

        // Get data for the last 7 days
        $dateDebut = Carbon::now()->subDays(6)->startOfDay();
        $dateFin = Carbon::now()->endOfDay();

        // Create array with all 7 days
        for($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays(6-$i)->format('Y-m-d');
            $statsParJour[$date] = 0;
            $statsParStatus[$date] = [
                'present' => 0,
                'absent' => 0,
                'retard' => 0,
                'excuse' => 0
            ];
        }

        // Collect attendance data for each day (filtered by current academic year AND active inscriptions)
        // IMPORTANT: Utiliser finalOnly() pour éviter les doublons (start + merged)
        // IMPORTANT: Ajouter classe_id dans whereHas inscriptions pour cohérence avec les stats par classe
        $attendancesByDay = ESBTPAttendance::finalOnly()
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->whereColumn('esbtp_inscriptions.classe_id', 'esbtp_attendances.classe_id');  // ← FILTRE CLASSE AJOUTÉ
            })
            ->selectRaw('DATE(date) as jour, statut, COUNT(*) as total')
            ->groupBy('jour', 'statut')
            ->get();

        // Fill in the data
        foreach($attendancesByDay as $record) {
            $jour = $record->jour;
            $statut = $record->statut;
            $total = $record->total;

            if(isset($statsParJour[$jour])) {
                $statsParJour[$jour] += $total;
            }

            // Handle both 'late' and 'retard' as retard
            $normalizedStatut = ($statut === 'late') ? 'retard' : $statut;

            if(isset($statsParStatus[$jour][$normalizedStatut])) {
                $statsParStatus[$jour][$normalizedStatut] += $total;
            }
        }

        // IMPORTANT: Calculer present_with_retards pour le graphique (retards = présences métier)
        foreach($statsParStatus as $jour => $dailyStats) {
            $statsParStatus[$jour]['present_with_retards'] = $dailyStats['present'] + $dailyStats['retard'];
        }

        // Create variables for statistics
        $statsPresent = $stats['present'];
        $statsAbsent = $stats['absent'];
        $statsRetard = $stats['retard'];
        $statsExcuse = $stats['excuse'];

        // Calculate statistics per student
        $statsParEtudiant = [];

        // Get class filter
        $classeId = $request->filled('classe_id') ? $request->classe_id : null;

        // Get all students for the selected class or all active students
        $etudiants = collect();
        if ($classeId) {
            $classe = ESBTPClasse::find($classeId);
            if ($classe) {
                $etudiants = $classe->etudiants()->with('user')->get();
            }
        } else {
            // Get students from attendances to avoid loading too many students
            $etudiantIds = ESBTPAttendance::distinct('etudiant_id')->pluck('etudiant_id')->toArray();
            if (!empty($etudiantIds)) {
                $etudiants = ESBTPEtudiant::whereIn('id', $etudiantIds)->with('user')->get();
            }
        }

        // Calculate statistics by class
        $classeStats = [];
        $classesActive = ESBTPClasse::where('is_active', true)->get();

        foreach ($classesActive as $classe) {
            // Compter les présences pour cette classe (uniquement étudiants année courante ET inscriptions actives)
            // IMPORTANT: Utiliser finalOnly() pour éviter les doublons (start + merged)
            // IMPORTANT: Ajouter classe_id dans whereHas inscriptions pour ne compter que les attendances de cette classe
            $presentCount = ESBTPAttendance::finalOnly()
            ->whereHas('seanceCours.emploiTemps', function($q) use ($classe) {
                $q->where('classe_id', $classe->id);
            })
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);  // ← FILTRE CLASSE AJOUTÉ
            })
            ->where('statut', 'present')->count();

            $absentCount = ESBTPAttendance::finalOnly()
            ->whereHas('seanceCours.emploiTemps', function($q) use ($classe) {
                $q->where('classe_id', $classe->id);
            })
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);  // ← FILTRE CLASSE AJOUTÉ
            })
            ->where('statut', 'absent')->count();

            $retardCount = ESBTPAttendance::finalOnly()
            ->whereHas('seanceCours.emploiTemps', function($q) use ($classe) {
                $q->where('classe_id', $classe->id);
            })
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);  // ← FILTRE CLASSE AJOUTÉ
            })
            ->whereIn('statut', ['retard', 'late'])->count();

            $excuseCount = ESBTPAttendance::finalOnly()
            ->whereHas('seanceCours.emploiTemps', function($q) use ($classe) {
                $q->where('classe_id', $classe->id);
            })
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);  // ← FILTRE CLASSE AJOUTÉ
            })
            ->where('statut', 'excuse')->count();

            $totalAttendanceForClass = $presentCount + $absentCount + $retardCount + $excuseCount;

            // Récupérer uniquement les étudiants inscrits pour l'année universitaire courante ET actifs
            // IMPORTANT: Filtrer aussi par classe_id pour ne compter que les étudiants de CETTE classe
            $totalStudents = $classe->etudiants()
                ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active')
                      ->where('classe_id', $classe->id);
                })
                ->count();
            
            if ($totalAttendanceForClass > 0 || $totalStudents > 0) {
                // IMPORTANT: Le taux de présence inclut les retards (présents + retards)
                $totalPresenceWithRetards = $presentCount + $retardCount;
                $classeStats[] = [
                    'name' => $classe->name,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'retard' => $retardCount,
                    'excuse' => $excuseCount,
                    'total_present_with_retards' => $totalPresenceWithRetards,  // ← AJOUTÉ pour la vue
                    'total_attendance' => $totalAttendanceForClass,
                    'total_students' => $totalStudents,
                    'attendance_rate' => $totalAttendanceForClass > 0 ? round($totalPresenceWithRetards / $totalAttendanceForClass * 100, 1) : 0
                ];
            }
        }

        // Calculate statistics for each student
        foreach ($etudiants as $etudiant) {
            // Create a query specific to this student
            $etudiantQuery = (clone $statsQuery)->where('etudiant_id', $etudiant->id);

            // Count attendances by status
            $present = (clone $etudiantQuery)->where('statut', 'present')->count();
            $absent = (clone $etudiantQuery)->where('statut', 'absent')->count();
            $retard = (clone $etudiantQuery)->whereIn('statut', ['retard', 'late'])->count();
            $excuse = (clone $etudiantQuery)->where('statut', 'excuse')->count();
            $total = $present + $absent + $retard + $excuse;

            // Calculate percentages
            $presentPercent = $total > 0 ? round(($present / $total) * 100) : 0;
            $absentPercent = $total > 0 ? round(($absent / $total) * 100) : 0;
            $retardPercent = $total > 0 ? round(($retard / $total) * 100) : 0;
            $excusePercent = $total > 0 ? round(($excuse / $total) * 100) : 0;

            // Store statistics for this student
            $statsParEtudiant[$etudiant->id] = [
                'etudiant' => $etudiant,
                'present' => $present,
                'absent' => $absent,
                'retard' => $retard,
                'excuse' => $excuse,
                'total' => $total,
                'present_percent' => $presentPercent,
                'absent_percent' => $absentPercent,
                'retard_percent' => $retardPercent,
                'excuse_percent' => $excusePercent
            ];
        }

        // Calculate additional statistics for the view
        $totalAttendances = $statsTotal;

        // Calculate attendances for this month
        $currentMonth = Carbon::now()->startOfMonth();
        $attendancesThisMonth = ESBTPAttendance::whereDate('date', '>=', $currentMonth)->count();

        // Calculate average attendance rate
        $totalRecords = ESBTPAttendance::count();
        $totalPresent = ESBTPAttendance::where('statut', 'present')->count();
        $averageAttendanceRate = $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100) : 0;

        // Calculate number of classes with attendance records
        $classesWithAttendance = DB::table('esbtp_attendances')
            ->join('esbtp_seance_cours', 'esbtp_attendances.seance_cours_id', '=', 'esbtp_seance_cours.id')
            ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
            ->distinct('esbtp_emploi_temps.classe_id')
            ->count('esbtp_emploi_temps.classe_id');

        // Add coordinator-specific statistics if user has coordinator role
        $coordinatorStats = null;
        $unreadNotifications = 0;
        $recentActivities = [];

        if (Auth::user() && Auth::user()->can('can_coordinate_academics')) {
            $today = Carbon::today();
            $coordinatorStats = $this->calculateCoordinatorStats($today);

            // Get unread notifications count
            $unreadNotifications = Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count();
        }

        return view('esbtp.attendances.index', compact(
            'attendances',
            'classes',
            'matieres',
            'stats',
            'statsTotal',
            'anneeLabel',
            'statsPresent',
            'statsPresentPercent',
            'statsAbsent',
            'statsAbsentPercent',
            'statsRetard',
            'statsRetardPercent',
            'statsExcuse',
            'statsExcusePercent',
            'statsParJour',
            'statsParStatus',
            'filteredTotal',
            'statsParEtudiant',
            'etudiants',
            'totalAttendances',
            'attendancesThisMonth',
            'averageAttendanceRate',
            'classesWithAttendance',
            'teachers',
            'classeStats',
            'coordinatorStats',
            'unreadNotifications'
        ));
    }

    /**
     * Affiche le formulaire pour marquer les présences d'une séance.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Récupérer les classes pour le filtre
        $classes = ESBTPClasse::all();

        // Initialiser les variables
        $seances = collect();
        $etudiants = collect();
        $dateSeance = null;
        $messageErreur = null;
        $classeSelectionnee = false;
        $existingAttendances = []; // Tableau pour stocker les présences existantes (mode hybride create/update)
        $debug = []; // Tableau pour stocker les informations de débogage

        // Si une classe est sélectionnée (et que la valeur n'est pas vide)
        if ($request->filled('classe_id') && !empty($request->classe_id)) {
            $classeSelectionnee = true;
            $debug['classe_id'] = $request->classe_id;

            try {
                // Vérifier que la classe existe
                $classe = ESBTPClasse::findOrFail($request->classe_id);
                $debug['classe_trouvee'] = true;
                $debug['classe_nom'] = $classe->name;

                // Récupérer les séances de cours pour cette classe
                $seances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($request) {
                    $query->where('classe_id', $request->classe_id)
                          ->where('is_active', true);
                })->with(['emploiTemps', 'matiere'])->get();

                $debug['nombre_seances'] = $seances->count();

                // Ajouter des informations supplémentaires pour l'affichage
                $seances->each(function($seance) {
                    $seance->jour_nom = $seance->getNomJour();
                    // Utiliser date_seance de la base si disponible, sinon calculer
                    if (!empty($seance->date_seance)) {
                        $seance->date_calculee = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
                    } else {
                        $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
                    }
                });

                // Si aucune séance n'est trouvée, afficher un message
                if ($seances->isEmpty()) {
                    $messageErreur = 'Aucune séance active n\'a été trouvée pour cette classe. Veuillez vérifier que l\'emploi du temps est actif.';
                    $debug['erreur'] = 'aucune_seance_active';
                }

                // Si une séance est sélectionnée, vérifier qu'elle appartient à la classe sélectionnée
                if ($request->filled('seance_id')) {
                    $debug['seance_id'] = $request->seance_id;
                    $seanceAppartientAClasse = $seances->contains('id', $request->seance_id);
                    $debug['seance_appartient_a_classe'] = $seanceAppartientAClasse;

                    if (!$seanceAppartientAClasse) {
                        // La séance sélectionnée n'appartient pas à la classe sélectionnée
                        // Rediriger vers la même page sans le paramètre seance_id
                        return redirect()->route('esbtp.attendances.create', ['classe_id' => $request->classe_id])
                            ->with('warning', 'La séance sélectionnée n\'appartient pas à la classe sélectionnée.');
                    }

                    // Récupérer la séance avec ses relations
                    $seance = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere'])->findOrFail($request->seance_id);
                    $debug['seance_trouvee'] = true;
                    $debug['seance_emploi_temps_existe'] = isset($seance->emploiTemps);

                    // Vérifier si l'emploi du temps existe
                    if (!$seance->emploiTemps) {
                        $messageErreur = 'L\'emploi du temps associé à cette séance n\'existe pas ou a été supprimé.';
                        $debug['erreur'] = 'emploi_temps_manquant';
                    }
                    // Vérifier si la classe existe
                    elseif (!$seance->emploiTemps->classe) {
                        $messageErreur = 'La classe associée à cet emploi du temps n\'existe pas ou a été supprimée.';
                        $debug['erreur'] = 'classe_manquante';
                    }
                    else {
                        // Récupérer l'année universitaire courante
                        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

                        // Récupérer les étudiants directement de la classe sélectionnée
                        // filtrés par l'année universitaire courante ET statut inscription active
                        $etudiants = $classe->etudiants()
                            ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                                  ->where('status', 'active')
                                  ->where('classe_id', $classe->id);
                            })
                            ->get();
                        $debug['nombre_etudiants'] = $etudiants->count();
                        $debug['etudiants_ids'] = $etudiants->pluck('id')->toArray();

                        // Vérifier si la classe a des étudiants
                        if ($etudiants->isEmpty()) {
                            $messageErreur = 'Aucun étudiant n\'est inscrit dans cette classe. Veuillez d\'abord inscrire des étudiants.';
                            $debug['erreur'] = 'aucun_etudiant';
                        }

                        // Utiliser la date de la séance stockée en base (date_seance)
                        // au lieu de calculer via getDateSeance() qui peut donner une date incorrecte
                        if (!empty($seance->date_seance)) {
                            $dateSeance = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
                            $debug['date_seance'] = $dateSeance;
                            $debug['date_source'] = 'database_date_seance';
                        } else {
                            // Fallback: calculer si date_seance n'est pas définie
                            $dateCalculee = $seance->getDateSeance();
                            if ($dateCalculee) {
                                $dateSeance = $dateCalculee->format('Y-m-d');
                                $debug['date_seance'] = $dateSeance;
                                $debug['date_source'] = 'calculated_via_emploi_temps';
                            } else {
                                $messageErreur = 'Impossible de calculer la date de cette séance. Veuillez vérifier les dates de l\'emploi du temps.';
                                $debug['erreur'] = 'date_calcul_impossible';
                                $dateSeance = now()->format('Y-m-d'); // Date par défaut
                                $debug['date_source'] = 'fallback_now';
                            }
                        }

                        // NOUVEAU: Vérifier si des présences existent déjà pour cette séance
                        // Mode hybride create/update : charger les présences existantes si elles existent
                        $existingAttendances = [];
                        if (!$etudiants->isEmpty() && $dateSeance && $request->filled('seance_id')) {
                            foreach ($etudiants as $etudiant) {
                                // Récupérer uniquement les attendances 'merged' (finales) ou sans call_type (saisie manuelle)
                                $attendance = ESBTPAttendance::where([
                                    'seance_cours_id' => $request->seance_id,
                                    'etudiant_id' => $etudiant->id,
                                    'date' => $dateSeance
                                ])
                                ->where(function($query) {
                                    $query->where('call_type', 'merged')
                                          ->orWhereNull('call_type');
                                })
                                ->first();

                                if ($attendance) {
                                    $existingAttendances[$etudiant->id] = $attendance;
                                    $debug['attendance_loaded_for_etudiant_' . $etudiant->id] = [
                                        'id' => $attendance->id,
                                        'statut' => $attendance->statut,
                                        'call_type' => $attendance->call_type,
                                        'commentaire' => $attendance->commentaire,
                                        'date' => $attendance->date
                                    ];
                                }
                            }
                            $debug['existing_attendances_count'] = count($existingAttendances);
                            $debug['existing_attendances_ids'] = array_keys($existingAttendances);
                            $debug['mode'] = count($existingAttendances) > 0 ? 'update' : 'create';
                        }
                    }
                } else {
                    // Si la classe est sélectionnée mais pas de séance, récupérer quand même les étudiants
                    // pour vérifier s'il y en a dans cette classe
                    // Récupérer l'année universitaire courante
                    $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

                    $etudiants = $classe->etudiants()
                        ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                            $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                              ->where('status', 'active')
                              ->where('classe_id', $classe->id);
                        })
                        ->get();
                    $debug['nombre_etudiants_classe'] = $etudiants->count();

                    if ($etudiants->isEmpty()) {
                        $messageErreur = 'Aucun étudiant n\'est inscrit dans cette classe. Veuillez d\'abord inscrire des étudiants.';
                        $debug['erreur'] = 'aucun_etudiant_dans_classe';
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la récupération des données pour le marquage des présences: ' . $e->getMessage());
                $messageErreur = 'Une erreur est survenue lors de la récupération des données: ' . $e->getMessage();
                $debug['exception'] = $e->getMessage();
                $debug['exception_trace'] = config('app.debug') ? $e->getTraceAsString() : null;
            }
        } else {
            // Si aucune classe n'est sélectionnée mais qu'une séance est spécifiée,
            // rediriger vers la page sans paramètres pour éviter les incohérences
            if ($request->filled('seance_id')) {
                return redirect()->route('esbtp.attendances.create')
                    ->with('warning', 'Veuillez d\'abord sélectionner une classe.');
            }
        }

        // Enregistrer les informations de débogage dans le journal
        \Log::info('Débogage marquage présences', $debug);

        // Matières de la classe pour l'onglet "Saisie manuelle"
        // Source canonique : planifications académiques (filière + niveau), comme ClassPlanningService.
        // Fallback : pivot esbtp_classe_matiere (BTS pré-attachées) si planifications absentes.
        $matieresClasse = collect();
        if ($classeSelectionnee && isset($classe)) {
            $matieresClasse = $this->getMatieresClasse($classe);
        }

        $anneeUniversitaireCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return view('esbtp.attendances.create', compact(
            'classes',
            'seances',
            'etudiants',
            'dateSeance',
            'messageErreur',
            'classeSelectionnee',
            'existingAttendances',
            'debug',
            'matieresClasse',
            'anneeUniversitaireCourante'
        ));
    }

    /**
     * Charge les séances pour une classe donnée (AJAX pour refresh partiel).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadSeances(Request $request)
    {
        \Log::info('🔵 [AJAX] loadSeances appelé', ['classe_id' => $request->classe_id]);

        try {
            $request->validate(['classe_id' => 'required|exists:esbtp_classes,id']);

            $seances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($request) {
                $query->where('classe_id', $request->classe_id)->where('is_active', true);
            })->with(['emploiTemps', 'matiere'])->get();

            \Log::info('✅ [AJAX] Séances trouvées', ['nb_seances' => $seances->count()]);

            // Générer les options HTML avec indication de présence marquée
            $options = '<option value="">Sélectionner une séance</option>';
            foreach ($seances as $seance) {
                $seance->jour_nom = $seance->getNomJour();
                // Utiliser date_seance de la base si disponible, sinon calculer
                if (!empty($seance->date_seance)) {
                    $seance->date_calculee = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
                } else {
                    $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
                }

                $matiere = $seance->matiere->name ?? 'Matière inconnue';
                $heureDebut = $seance->heure_debut->format('H:i');
                $heureFin = $seance->heure_fin->format('H:i');
                $jour = $seance->jour_nom;
                $dateCalculee = $seance->date_calculee ? \Carbon\Carbon::parse($seance->date_calculee)->format('d/m/Y') : '';

                // Vérifier si des présences existent déjà pour cette séance
                // Utiliser la date stockée en base (date_seance) ou la date calculée comme fallback
                $dateRecherche = !empty($seance->date_seance)
                    ? \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d')
                    : ($seance->date_calculee ?: now()->format('Y-m-d'));

                $hasAttendances = ESBTPAttendance::where('seance_cours_id', $seance->id)
                    ->where('date', $dateRecherche)
                    ->where(function($q) {
                        $q->where('call_type', 'merged')->orWhereNull('call_type');
                    })
                    ->exists();

                // Log debug pour vérifier la détection
                if ($hasAttendances) {
                    $count = ESBTPAttendance::where('seance_cours_id', $seance->id)
                        ->where('date', $dateRecherche)
                        ->where(function($q) {
                            $q->where('call_type', 'merged')->orWhereNull('call_type');
                        })
                        ->count();
                    \Log::info("✅ [BADGE] Séance {$seance->id} ({$matiere}) a {$count} attendances pour {$dateRecherche}");
                } else {
                    \Log::info("⭕ [BADGE] Séance {$seance->id} ({$matiere}) AUCUNE attendance pour {$dateRecherche}");
                }

                // Badge visuel pour indiquer si présences marquées (icônes FontAwesome)
                $badge = $hasAttendances ? ' <i class="fas fa-check-circle text-success"></i> Présence marquée' : ' <i class="far fa-circle text-muted"></i> Non marquée';

                $options .= sprintf(
                    '<option value="%d" data-date="%s" data-jour="%s" data-has-attendances="%s">%s - %s à %s (%s)%s%s</option>',
                    $seance->id,
                    $seance->date_calculee ?? '',
                    $jour,
                    $hasAttendances ? 'true' : 'false',
                    $matiere,
                    $heureDebut,
                    $heureFin,
                    $jour,
                    $dateCalculee ? " - {$dateCalculee}" : '',
                    $badge
                );
            }

            // Matières de la classe pour l'onglet "Saisie manuelle" (via planifications académiques)
            $classe = ESBTPClasse::find($request->classe_id);
            $matieres = $classe
                ? $this->getMatieresClasse($classe)
                    ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])
                    ->values()
                    ->all()
                : [];

            return response()->json([
                'success' => true,
                'options' => $options,
                'nbSeances' => $seances->count(),
                'matieres' => $matieres,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [AJAX] Erreur loadSeances: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Charge les étudiants pour une séance donnée (AJAX pour refresh partiel).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadStudents(Request $request)
    {
        \Log::info('🔵 [AJAX] loadStudents appelé', [
            'classe_id' => $request->classe_id,
            'seance_id' => $request->seance_id,
            'headers' => $request->headers->all(),
            'is_ajax' => $request->ajax(),
            'is_xhr' => $request->header('X-Requested-With') === 'XMLHttpRequest'
        ]);

        try {
            $request->validate([
                'classe_id' => 'required|exists:esbtp_classes,id',
                'seance_id' => 'required|exists:esbtp_seance_cours,id',
            ]);

            \Log::info('✅ [AJAX] Validation passée');

            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $seance = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere'])->findOrFail($request->seance_id);

            \Log::info('✅ [AJAX] Classe et séance trouvées', [
                'classe_nom' => $classe->name,
                'seance_matiere' => $seance->matiere->name ?? 'N/A'
            ]);

            // Vérifier que la séance appartient à la classe
            if (!$seance->emploiTemps || $seance->emploiTemps->classe_id != $request->classe_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La séance sélectionnée n\'appartient pas à la classe sélectionnée.'
                ], 400);
            }

            // Récupérer l'année universitaire courante
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            // Récupérer les étudiants
            $etudiants = $classe->etudiants()
                ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active')
                      ->where('classe_id', $classe->id);
                })
                ->get();

            if ($etudiants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun étudiant inscrit dans cette classe.'
                ], 404);
            }

            // Utiliser la date de la séance stockée en base (date_seance)
            // au lieu de calculer via getDateSeance() qui peut donner une date incorrecte
            if (!empty($seance->date_seance)) {
                $dateSeance = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
                \Log::info('📅 [AJAX] Date from database', ['date_seance' => $dateSeance]);
            } else {
                // Fallback: calculer si date_seance n'est pas définie
                $dateCalculee = $seance->getDateSeance();
                $dateSeance = $dateCalculee ? $dateCalculee->format('Y-m-d') : now()->format('Y-m-d');
                \Log::info('📅 [AJAX] Date calculated', ['date_seance' => $dateSeance, 'calculated' => (bool)$dateCalculee]);
            }

            // Charger les présences existantes (uniquement 'merged' ou sans call_type)
            $existingAttendances = [];
            foreach ($etudiants as $etudiant) {
                $attendance = ESBTPAttendance::where([
                    'seance_cours_id' => $request->seance_id,
                    'etudiant_id' => $etudiant->id,
                    'date' => $dateSeance
                ])
                ->where(function($query) {
                    $query->where('call_type', 'merged')
                          ->orWhereNull('call_type');
                })
                ->first();

                if ($attendance) {
                    $existingAttendances[$etudiant->id] = $attendance;
                }
            }

            // Générer le HTML pour la liste des étudiants
            $html = view('esbtp.attendances.partials.student-list', [
                'etudiants' => $etudiants,
                'existingAttendances' => $existingAttendances
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'dateSeance' => $dateSeance,
                'nbEtudiants' => $etudiants->count(),
                'nbExisting' => count($existingAttendances),
                'mode' => count($existingAttendances) > 0 ? 'update' : 'create'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du chargement AJAX des étudiants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistre les présences des étudiants.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données
        $validatedData = $request->validate([
            'seance_cours_id' => 'required|exists:esbtp_seance_cours,id',
            'date' => 'required|date',
            'statuts' => 'required|array',
            'statuts.*' => 'required|in:present,absent,retard,excuse',
            'commentaires' => 'nullable|array',
            'commentaires.*' => 'nullable|string'
        ]);

        // Récupérer l'année universitaire courante
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Vérifier que la date correspond au jour de la séance
        $seance = ESBTPSeanceCours::findOrFail($validatedData['seance_cours_id']);
        $dateCalculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;

        if ($dateCalculee && $dateCalculee != $validatedData['date']) {
            return back()->withInput()->withErrors([
                'date' => 'La date sélectionnée ne correspond pas au jour de la séance dans l\'emploi du temps.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Initialiser un tableau pour suivre les opérations effectuées
            $summary = [
                'created' => 0,
                'updated' => 0,
                'students_processed' => count($validatedData['statuts'])
            ];

            foreach ($validatedData['statuts'] as $etudiantId => $statut) {
                // Vérifier si l'enregistrement existe déjà (uniquement attendances manuelles: merged ou null)
                // Ne pas confondre avec les attendances de l'émargement enseignant (call_type='start')
                $attendance = ESBTPAttendance::where([
                    'seance_cours_id' => $validatedData['seance_cours_id'],
                    'etudiant_id' => $etudiantId,
                    'date' => $validatedData['date']
                ])
                ->where(function($query) {
                    $query->where('call_type', 'merged')
                          ->orWhereNull('call_type');
                })
                ->first();

                $commentaire = $validatedData['commentaires'][$etudiantId] ?? null;

                if ($attendance) {
                    // Mémoriser l'ancien statut pour vérifier s'il change en absent
                    $oldStatut = $attendance->statut;

                    // Mettre à jour l'enregistrement existant avec call_type='merged' (saisie manuelle = finale)
                    $attendance->update([
                        'statut' => $statut,
                        'call_type' => 'merged', // Marquer comme version finale
                        'commentaire' => $commentaire,
                        'updated_by' => Auth::id()
                    ]);

                    // Si le statut est changé en 'absent', envoyer une notification
                    if ($statut === 'absent' && $oldStatut !== 'absent') {
                        $this->sendAbsenceNotification($etudiantId, $seance, $validatedData['date']);
                    }

                    $summary['updated']++;
                } else {
                    // Récupérer les heures de début et de fin de la séance
                    // S'assurer que les valeurs sont au format correct pour la base de données
                    $heureDebut = $seance->heure_debut ? $seance->heure_debut->format('H:i:s') : '08:00:00';
                    $heureFin = $seance->heure_fin ? $seance->heure_fin->format('H:i:s') : '10:00:00';

                    // Créer un nouvel enregistrement avec call_type='merged' (saisie manuelle = version finale)
                    ESBTPAttendance::create([
                        'seance_cours_id' => $validatedData['seance_cours_id'],
                        'etudiant_id' => $etudiantId,
                        'annee_universitaire_id' => $anneeUniversitaire->id,
                        'date' => $validatedData['date'],
                        'heure_debut' => $heureDebut,
                        'heure_fin' => $heureFin,
                        'statut' => $statut,
                        'call_type' => 'merged', // Saisie manuelle = version finale
                        'commentaire' => $commentaire,
                        'created_by' => Auth::id()
                    ]);

                    // Si le statut est 'absent', envoyer une notification
                    if ($statut === 'absent') {
                        $this->sendAbsenceNotification($etudiantId, $seance, $validatedData['date']);
                    }

                    $summary['created']++;
                }
            }

            DB::commit();

            // Message de succès détaillé
            $message = 'Les présences ont été enregistrées avec succès. ';
            if ($summary['created'] > 0) {
                $message .= $summary['created'] . ' nouvelles présences créées. ';
            }
            if ($summary['updated'] > 0) {
                $message .= $summary['updated'] . ' présences existantes mises à jour.';
            }

            return redirect()->route('esbtp.attendances.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des présences: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les détails d'une présence.
     *
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPAttendance $attendance)
    {
        return view('esbtp.attendances.show', compact('attendance'));
    }

    /**
     * Affiche le formulaire pour modifier une présence.
     *
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPAttendance $attendance)
    {
        return view('esbtp.attendances.edit', compact('attendance'));
    }

    /**
     * Met à jour une présence.
     *
     * @param Request $request
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPAttendance $attendance)
    {
        // Valider les données
        $validatedData = $request->validate([
            'statut' => 'required|in:present,absent,retard,excuse',
            'commentaire' => 'nullable|string'
        ]);

        // Mémoriser l'ancien statut pour vérifier s'il change en absent
        $oldStatut = $attendance->statut;

        // Ajouter l'identifiant de l'utilisateur qui modifie ET call_type='merged'
        $validatedData['updated_by'] = Auth::id();
        $validatedData['call_type'] = 'merged'; // Marquer comme version finale

        // Mettre à jour l'enregistrement
        $attendance->update($validatedData);

        // Si le statut est changé en 'absent', envoyer une notification
        if ($validatedData['statut'] === 'absent' && $oldStatut !== 'absent') {
            $attendance->load(['etudiant', 'seanceCours.matiere']);
            if ($attendance->etudiant && $attendance->seanceCours) {
                $this->sendAbsenceNotification(
                    $attendance->etudiant->id,
                    $attendance->seanceCours,
                    $attendance->date
                );
            }
        }

        return redirect()->route('esbtp.attendances.index')
            ->with('success', 'La présence a été mise à jour avec succès.');
    }

    /**
     * Supprime une présence.
     *
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPAttendance $attendance)
    {
        try {
            $attendance->delete();
            return redirect()->route('esbtp.attendances.index')->with('success', 'Présence supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Génère un rapport de présence.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rapport(Request $request)
    {
        // Valider les données
        $validatedData = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut'
        ]);

        // Récupérer la classe
        $classe = ESBTPClasse::findOrFail($validatedData['classe_id']);

        // Récupérer l'année universitaire courante
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer uniquement les étudiants inscrits pour l'année universitaire courante
        // avec inscription ACTIVE et pour CETTE classe spécifiquement
        $etudiants = $classe->etudiants()
            ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);
            })
            ->get();

        // Récupérer les séances de cours de la classe
        $seances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($classe) {
            $query->where('classe_id', $classe->id);
        })->get();

        // Récupérer les présences pour chaque étudiant
        $statistiques = [];

        foreach ($etudiants as $etudiant) {
            // IMPORTANT: Utiliser finalOnly() pour éviter les doublons (start + merged)
            $attendances = ESBTPAttendance::finalOnly()
                ->where('etudiant_id', $etudiant->id)
                ->whereHas('seanceCours.emploiTemps', function($query) use ($classe) {
                    $query->where('classe_id', $classe->id);
                })
                ->whereBetween('date', [$validatedData['date_debut'], $validatedData['date_fin']])
                ->get();

            // Calculer les statistiques
            $totalSeances = $seances->count();
            $present = $attendances->where('statut', 'present')->count();
            $absent = $attendances->where('statut', 'absent')->count();
            $retard = $attendances->whereIn('statut', ['retard', 'late'])->count();
            $excuse = $attendances->where('statut', 'excuse')->count();

            // Retard compte aussi comme présence pour le taux (étudiant était là même si en retard)
            $totalPresences = $present + $retard;
            $tauxPresence = $totalSeances > 0 ? round(($totalPresences / $totalSeances) * 100, 2) : 0;

            $statistiques[$etudiant->id] = [
                'etudiant' => $etudiant,
                'present' => $present,
                'absent' => $absent,
                'retard' => $retard,
                'excuse' => $excuse,
                'taux_presence' => $tauxPresence
            ];
        }

        return view('esbtp.attendances.rapport', compact('classe', 'etudiants', 'statistiques', 'validatedData'));
    }

    /**
     * Affiche le formulaire pour générer un rapport.
     *
     * @return \Illuminate\Http\Response
     */
    public function rapportForm()
    {
        $classes = ESBTPClasse::all();

        return view('esbtp.attendances.rapport-form', compact('classes'));
    }

    /**
     * Génère et télécharge le PDF du rapport de présence.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rapportPdf(Request $request)
    {
        // Valider les données
        $validatedData = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut'
        ]);

        // Récupérer la classe
        $classe = ESBTPClasse::findOrFail($validatedData['classe_id']);

        // Récupérer l'année universitaire courante
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer uniquement les étudiants inscrits pour l'année universitaire courante
        // avec inscription ACTIVE et pour CETTE classe spécifiquement
        $etudiants = $classe->etudiants()
            ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);
            })
            ->get();

        // Récupérer les séances de cours de la classe
        $seances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($classe) {
            $query->where('classe_id', $classe->id);
        })->get();

        // Récupérer les présences pour chaque étudiant
        $statistiques = [];

        foreach ($etudiants as $etudiant) {
            // IMPORTANT: Utiliser finalOnly() pour éviter les doublons (start + merged)
            $attendances = ESBTPAttendance::finalOnly()
                ->where('etudiant_id', $etudiant->id)
                ->whereHas('seanceCours.emploiTemps', function($query) use ($classe) {
                    $query->where('classe_id', $classe->id);
                })
                ->whereBetween('date', [$validatedData['date_debut'], $validatedData['date_fin']])
                ->get();

            // Calculer les statistiques
            $totalSeances = $seances->count();
            $present = $attendances->where('statut', 'present')->count();
            $absent = $attendances->where('statut', 'absent')->count();
            $retard = $attendances->whereIn('statut', ['retard', 'late'])->count();
            $excuse = $attendances->where('statut', 'excuse')->count();

            // Retard compte aussi comme présence pour le taux (étudiant était là même si en retard)
            $totalPresences = $present + $retard;
            $tauxPresence = $totalSeances > 0 ? round(($totalPresences / $totalSeances) * 100, 2) : 0;

            $statistiques[$etudiant->id] = [
                'etudiant' => $etudiant,
                'present' => $present,
                'absent' => $absent,
                'retard' => $retard,
                'excuse' => $excuse,
                'taux_presence' => $tauxPresence
            ];
        }

        // Générer le PDF
        $pdf = Pdf::loadView('esbtp.attendances.rapport-pdf', compact('classe', 'etudiants', 'statistiques', 'validatedData'));

        // Configurer le PDF
        $pdf->setPaper('a4', 'portrait');

        // Nom du fichier
        $filename = 'rapport-presence-' . str_replace(' ', '-', strtolower($classe->name)) . '-' . date('Y-m-d') . '.pdf';

        // Télécharger le PDF
        return $pdf->download($filename);
    }

    /**
     * Display the attendance list for authenticated student.
     */
    public function studentAttendance(Request $request)
    {
        // Debug mode
        if ($request->has('debug')) {
            return response()->json([
                'user' => auth()->user(),
                'roles' => auth()->user()->roles,
                'permissions' => auth()->user()->permissions,
                'request' => $request->all()
            ]);
        }

        // Get the authenticated student
        $etudiant = auth()->user()->etudiant;
        if (!$etudiant) {
            abort(403, 'Profil étudiant non trouvé');
        }

        // Check if student has an active inscription for current year
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $inscription = null;

        if ($anneeCourante) {
            $inscription = $etudiant->inscriptions()
                ->where('status', 'active')
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
                ->first();
        }

        if (!$inscription) {
            return view('etudiants.attendances', [
                'absences' => collect(),
                'presences' => collect(),
                'retards' => collect(),
                'excuses' => collect(),
                'matieres' => collect(),
                'absencesParMatiere' => [],
                'absencesMensuelles' => collect(),
                'inscription' => null,
                'anneeCourante' => $anneeCourante,
                'etudiant' => $etudiant,
                'error' => 'Vous n\'avez pas d\'inscription active pour l\'année en cours.'
            ])->with('warning', 'Vous n\'avez pas d\'inscription active pour l\'année en cours. Veuillez contacter l\'administration.');
        }

        // Build the base query
        $query = ESBTPAttendance::with(['seanceCours.matiere'])
            ->where('etudiant_id', $etudiant->id);

        // Apply date filters
        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }

        // Apply matiere filter
        if ($request->filled('matiere_id')) {
            $query->whereHas('seanceCours', function ($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }

        // Get all attendances
        $allAttendances = $query->get();

        // Group attendances by status
        $presences = $allAttendances->where('statut', 'present');
        $absences = $allAttendances->where('statut', 'absent');
        $retards = $allAttendances->whereIn('statut', ['retard', 'late']);
        $excuses = $allAttendances->where('statut', 'excuse');

        // Calculate absences by month
        $absencesMensuelles = $absences->groupBy(function($absence) {
            return $absence->date->format('Y-m');
        })->map->count();

        // Get list of subjects for filtering using the service
        $matieres = $this->matiereService->getMatieresForSelect($etudiant);

        // Extract all unique subject IDs from the attendances
        $matiereIds = $allAttendances->map(function ($attendance) {
            // Vérifier que seanceCours et matiere_id existent pour éviter des erreurs
            return $attendance->seanceCours->matiere_id ?? null;
        })->filter()->unique()->values()->toArray();

        // Get all subjects related to the attendances
        $matieresFromAttendances = ESBTPMatiere::whereIn('id', $matiereIds)->get();

        // Create a dictionary of all subjects (from both service and attendances)
        $allMatieres = [];
        foreach ($matieres as $id => $name) {
            if ($id !== 'all') { // Ignorer l'entrée 'all' => 'Toutes les matières'
                $allMatieres[$id] = $name;
            }
        }
        foreach ($matieresFromAttendances as $matiere) {
            $allMatieres[$matiere->id] = $matiere->name;
        }

        // Calculate statistics by subject
        $absencesParMatiere = [];
        foreach ($allMatieres as $matiereId => $matiereName) {
            $matiereAttendances = $allAttendances->filter(function ($attendance) use ($matiereId) {
                return ($attendance->seanceCours->matiere_id ?? null) == $matiereId;
            });

            $total = $matiereAttendances->count();
            if ($total > 0) {
                $present = $matiereAttendances->where('statut', 'present')->count();
                $absent = $matiereAttendances->where('statut', 'absent')->count();
                $retard = $matiereAttendances->whereIn('statut', ['retard', 'late'])->count();
                $excuse = $matiereAttendances->where('statut', 'excuse')->count();

                $absencesParMatiere[$matiereId] = [
                    'nom' => $matiereName, // Conserver 'nom' pour la compatibilité
                    'name' => $matiereName,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'retard' => $retard,
                    'excuse' => $excuse,
                    'taux_presence' => round(($present / $total) * 100)
                ];
            }
        }

        return view('etudiants.attendances', compact(
            'absences',
            'presences',
            'retards',
            'excuses',
            'matieres',
            'absencesParMatiere',
            'absencesMensuelles'
        ));
    }

    /**
     * Permet à un étudiant de justifier une absence
     */
    public function justifyAbsence(Request $request, $absenceId)
    {
        // Récupérer l'absence
        $absence = ESBTPAttendance::findOrFail($absenceId);

        // Vérifier que l'absence appartient bien à l'étudiant connecté
        $etudiant = auth()->user()->etudiant;

        if (!$etudiant || $absence->etudiant_id != $etudiant->id) {
            abort(403, 'Vous n\'êtes pas autorisé à justifier cette absence');
        }

        // Vérifier si l'absence a déjà un commentaire administratif
        $hasAdminComment = false;
        if (strpos($absence->commentaire, "Commentaire de l'administration:") !== false) {
            $hasAdminComment = true;
        }

        // Vérifications pour éviter les justifications en double:
        // 1. Si l'absence a déjà une date de justification (en attente de validation) et pas de commentaire admin
        if ($absence->justified_at && !$hasAdminComment) {
            return redirect()->back()->with('warning', 'Cette absence est déjà justifiée et en attente de validation par l\'administration');
        }

        // 2. Si l'absence a déjà été validée (statut 'excuse')
        if ($absence->statut == 'excuse') {
            return redirect()->back()->with('info', 'Cette absence a déjà été justifiée et validée par l\'administration');
        }

        // Validation des données
        $request->validate([
            'justification' => 'required|string|max:500',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Traitement du document justificatif
        $documentPath = $absence->document_path; // Conserver le document existant par défaut
        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            $documentPath = $request->file('document')->store('absences/justifications', 'public');
        }

        // Si c'est une re-soumission après rejet, ne garder que la partie commentaire de l'étudiant
        if ($hasAdminComment) {
            $parts = explode("Commentaire de l'administration:", $absence->commentaire);
            $oldStudentComment = trim($parts[0] ?? '');
            // On ajoute un préfixe pour indiquer qu'il s'agit d'une re-soumission
            $absence->commentaire = $request->justification;
        } else {
            // Mise à jour des informations mais on garde le statut comme 'absent'
            $absence->commentaire = $request->justification;
        }

        // Mettre à jour le chemin du document si un nouveau document a été soumis
        if ($documentPath) {
            $absence->document_path = $documentPath;
        }

        $absence->justified_at = now();
        $absence->save();

        // Envoyer une notification aux administrateurs
        $this->sendJustificationNotificationToAdmins($absence, $etudiant, $request->justification, $documentPath);

        if ($hasAdminComment) {
            return redirect()->back()->with('success', 'Votre justification a été re-soumise avec succès et est en attente de validation par l\'administration');
        } else {
            return redirect()->back()->with('success', 'Votre justification a été soumise avec succès et est en attente de validation par l\'administration');
        }
    }

    /**
     * Permet à un administrateur de traiter une justification d'absence
     */
    public function processJustification(Request $request, $absenceId)
    {
        // Vérifier que l'utilisateur est admin ou secrétaire
        if (!auth()->user()->hasAnyPermission(['access_admin', 'can_manage_school'])) {
            abort(403, 'Vous n\'êtes pas autorisé à traiter les justifications d\'absence');
        }

        // Récupérer l'absence
        $absence = ESBTPAttendance::findOrFail($absenceId);

        // Vérifier que l'absence a bien été justifiée
        if (!$absence->justified_at) {
            return redirect()->back()->with('error', 'Cette absence n\'a pas été justifiée');
        }

        // Validation des données
        $request->validate([
            'decision' => 'required|in:approve,reject',
            'admin_comment' => 'nullable|string|max:500',
        ]);

        $etudiant = ESBTPEtudiant::find($absence->etudiant_id);

        // Traiter la décision
        if ($request->decision === 'approve') {
            // Approuver la justification
            $absence->statut = 'excuse';
            $absence->save();

            // Utiliser le service de notifications
            $this->notificationService->notifyAbsenceJustificationApproved($absence, $etudiant, auth()->user());

            return redirect()->back()->with('success', 'La justification d\'absence a été approuvée');
        } else {
            // Rejeter la justification - le statut reste 'absent'
            // Ajouter un commentaire admin si fourni
            if ($request->filled('admin_comment')) {
                $absence->commentaire .= "\n\nCommentaire de l'administration: " . $request->admin_comment;
                $absence->save();
            }

            // Utiliser le service de notifications
            $this->notificationService->notifyAbsenceJustificationRejected($absence, $etudiant, $request->admin_comment, auth()->user());

            return redirect()->back()->with('info', 'La justification d\'absence a été rejetée');
        }
    }

    /**
     * Envoie une notification aux administrateurs concernant une justification d'absence
     *
     * @param ESBTPAttendance $absence
     * @param ESBTPEtudiant $etudiant
     * @param string $justification
     * @param string|null $documentPath
     * @return void
     */
    private function sendJustificationNotificationToAdmins(ESBTPAttendance $absence, ESBTPEtudiant $etudiant, string $justification, ?string $documentPath = null)
    {
        // Utiliser le service de notifications centralisé
        $this->notificationService->notifyAbsenceJustificationSubmitted($absence, $etudiant);
    }

    /**
     * Envoie une notification d'absence à un étudiant
     *
     * @param int $etudiantId ID de l'étudiant
     * @param ESBTPSeanceCours $seanceCours Séance de cours
     * @param string $date Date de l'absence
     * @return void
     */
    private function sendAbsenceNotification($etudiantId, $seanceCours, $date)
    {
        try {
            // Charger l'étudiant avec sa relation user
            $etudiant = ESBTPEtudiant::with('user')->find($etudiantId);

            // S'assurer que l'étudiant existe et a un compte utilisateur
            if (!$etudiant || !$etudiant->user) {
                \Log::warning("Impossible d'envoyer la notification d'absence: étudiant ou utilisateur non trouvé", [
                    'etudiant_id' => $etudiantId
                ]);
                return;
            }

            // Charger les informations de la séance de cours avec ses relations
            $seanceCours->load(['matiere', 'emploiTemps.classe']);

            // Formater la date et l'heure
            $dateAbsence = Carbon::parse($date);
            $jourSemaine = $dateAbsence->locale('fr')->dayName;
            $dateFormatee = $dateAbsence->format('d/m/Y');

            // Récupérer les informations du cours
            $matiereName = $seanceCours->matiere ? $seanceCours->matiere->name : 'Matière non définie';
            $heureDebut = $seanceCours->heure_debut ? substr($seanceCours->heure_debut, 0, 5) : 'Heure non définie';
            $heureFin = $seanceCours->heure_fin ? substr($seanceCours->heure_fin, 0, 5) : '';
            $heureFormatee = $heureDebut . ($heureFin ? ' - ' . $heureFin : '');
            $classeName = $seanceCours->emploiTemps && $seanceCours->emploiTemps->classe ? $seanceCours->emploiTemps->classe->name : 'Classe non définie';

            // Créer un message détaillé pour le cours
            $messageDetail = sprintf(
                "Absence lors d'un cours\n" .
                "Matière: %s\n" .
                "Date: %s (%s)\n" .
                "Heure: %s\n" .
                "Classe: %s",
                $matiereName,
                $dateFormatee,
                ucfirst($jourSemaine),
                $heureFormatee,
                $classeName
            );

            // Créer une entrée d'absence temporaire pour la notification avec informations enrichies
            $absence = new ESBTPAttendance();
            $absence->date = $dateAbsence;
            $absence->etudiant_id = $etudiantId;
            $absence->statut = 'absent';
            $absence->commentaire = $messageDetail;
            $absence->matiere_id = $seanceCours->matiere_id;
            $absence->type_activite = 'cours';
            $absence->heure_debut = $seanceCours->heure_debut;
            $absence->heure_fin = $seanceCours->heure_fin;

            // Utiliser le service de notifications
            $this->notificationService->notifyNewAbsence($absence, $etudiant);

            // Notifier les parents de l'absence
            $this->notificationService->notifyParentsAbsence($absence);

            \Log::info("Notification d'absence enrichie envoyée pour le cours", [
                'etudiant_id' => $etudiantId,
                'seance_cours_id' => $seanceCours->id,
                'matiere' => $matiereName,
                'date' => $dateFormatee,
                'jour' => $jourSemaine,
                'heure' => $heureFormatee,
                'classe' => $classeName
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de la notification d'absence", [
                'etudiant_id' => $etudiantId,
                'seance_cours_id' => $seanceCours->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
        }
    }

    /**
     * Exporte les données de présence au format CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportAttendances(Request $request)
    {
        // Build the query with necessary relationships
        $query = ESBTPAttendance::with([
            'etudiant.user',
            'seanceCours.matiere',
            'seanceCours.emploiTemps.classe'
        ]);

        // Apply filters if provided
        if ($request->filled('classe_id')) {
            $query->whereHas('seanceCours.emploiTemps', function ($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }

        if ($request->filled('matiere_id')) {
            $query->whereHas('seanceCours', function ($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Get student filter
        if ($request->filled('etudiant_id')) {
            $query->where('etudiant_id', $request->etudiant_id);
        }

        // Get all attendances based on filters
        $attendances = $query->orderBy('date', 'desc')->get();

        // Define filename
        $filename = 'presences_' . date('Y-m-d_His') . '.csv';

        // Create CSV response
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM to ensure French characters display correctly
            fputs($file, "\xEF\xBB\xBF");

            // CSV headers
            fputcsv($file, [
                'Date',
                'Classe',
                'Matière',
                'Étudiant',
                'Statut',
                'Heure Début',
                'Heure Fin',
                'Commentaire'
            ]);

            // CSV data
            foreach ($attendances as $attendance) {
                $row = [
                    $attendance->date ? $attendance->date->format('d/m/Y') : 'N/A',
                    $attendance->seanceCours && $attendance->seanceCours->emploiTemps && $attendance->seanceCours->emploiTemps->classe ? $attendance->seanceCours->emploiTemps->classe->name : 'N/A',
                    $attendance->seanceCours && $attendance->seanceCours->matiere ? $attendance->seanceCours->matiere->name : 'N/A',
                    $attendance->etudiant ? $attendance->etudiant->nom . ' ' . $attendance->etudiant->prenoms : 'N/A',
                    ucfirst($attendance->statut),
                    $attendance->seanceCours ? substr($attendance->seanceCours->heure_debut, 0, 5) : 'N/A',
                    $attendance->seanceCours ? substr($attendance->seanceCours->heure_fin, 0, 5) : 'N/A',
                    $attendance->commentaire
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculate coordinator-specific statistics for today
     */
    private function calculateCoordinatorStats($date)
    {
        try {
            $stats = [];

            // 1. Émargements enseignants aujourd'hui
            $stats['scheduled_courses_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->where('is_active', true)
                ->count();

            $stats['teacher_attendances_today'] = ESBTPTeacherAttendance::whereDate('validated_at', $date)
                ->count();

            $stats['teacher_attendance_rate'] = $stats['scheduled_courses_today'] > 0 
                ? round(($stats['teacher_attendances_today'] / $stats['scheduled_courses_today']) * 100, 1) 
                : 0;

            // 2. Appels terminés et présences étudiants
            $stats['roll_calls_completed_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereHas('attendances') // Séances avec des appels enregistrés
                ->count();

            $stats['students_present_today'] = ESBTPAttendance::whereDate('date', $date)
                ->where('statut', 'present')
                ->count();

            $stats['roll_call_completion_rate'] = $stats['scheduled_courses_today'] > 0 
                ? round(($stats['roll_calls_completed_today'] / $stats['scheduled_courses_today']) * 100, 1) 
                : 0;

            // 3. Retards détectés
            $stats['delays_today'] = max(0, $stats['scheduled_courses_today'] - $stats['teacher_attendances_today']);

            // 4. Cours clôturés
            $stats['courses_closed_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->where('status', 'completed')
                ->count();

            // 5. Classes avec forte absentéisme (plus de 30% d'absences)
            $stats['high_absence_classes'] = $this->getHighAbsenceClasses($date);

            return $stats;

        } catch (\Exception $e) {
            \Log::error('Erreur calcul statistiques coordinateur: ' . $e->getMessage());
            
            // Retourner des statistiques par défaut en cas d'erreur
            return [
                'scheduled_courses_today' => 0,
                'teacher_attendances_today' => 0,
                'teacher_attendance_rate' => 0,
                'roll_calls_completed_today' => 0,
                'students_present_today' => 0,
                'roll_call_completion_rate' => 0,
                'delays_today' => 0,
                'courses_closed_today' => 0,
                'high_absence_classes' => 0
            ];
        }
    }

    /**
     * Identify classes with high absence rate
     */
    private function getHighAbsenceClasses($date)
    {
        try {
            $classesWithHighAbsence = DB::table('esbtp_attendances')
                ->select('classe_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN statut = "absent" THEN 1 ELSE 0 END) as absents'))
                ->join('esbtp_seance_cours', 'esbtp_attendances.seance_cours_id', '=', 'esbtp_seance_cours.id')
                ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
                ->whereDate('esbtp_attendances.date', $date)
                ->groupBy('esbtp_emploi_temps.classe_id')
                ->havingRaw('(absents / total) > 0.3') // Plus de 30% d'absences
                ->count();

            return $classesWithHighAbsence;

        } catch (\Exception $e) {
            \Log::error('Erreur calcul classes forte absentéisme: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les matières d'une classe via planifications académiques (filière + niveau).
     * Fallback : pivot esbtp_classe_matiere si aucune planification n'existe (classes BTS legacy).
     * C'est la source canonique utilisée par ClassPlanningService pour la cohérence
     * planning / emploi-temps / notes / saisie manuelle.
     */
    private function getMatieresClasse(ESBTPClasse $classe): \Illuminate\Support\Collection
    {
        if (!$classe->filiere_id || !$classe->niveau_etude_id) {
            return collect();
        }

        $matieres = ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereNotNull('matiere_id')
            ->with('matiere:id,name')
            ->get()
            ->pluck('matiere')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        if ($matieres->isEmpty()) {
            // Fallback classes BTS historiques attachées directement via pivot
            $matieres = $classe->matieres()
                ->orderBy('name')
                ->get(['esbtp_matieres.id', 'esbtp_matieres.name']);
        }

        return $matieres;
    }

    public function loadManualTab(Request $request, ManualAttendanceHoursService $service)
    {
        $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
        ]);

        try {
            $classe = ESBTPClasse::with('filiere', 'niveau')->findOrFail($request->classe_id);
            $matiere = ESBTPMatiere::findOrFail($request->matiere_id);
            $periode = $request->periode;

            $anneeUniversitaire = $request->annee_universitaire_id
                ? ESBTPAnneeUniversitaire::findOrFail($request->annee_universitaire_id)
                : ESBTPAnneeUniversitaire::where('is_current', true)->firstOrFail();

            $etudiants = $classe->etudiants()
                ->whereHas('inscriptions', function ($q) use ($anneeUniversitaire, $classe) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                        ->where('status', 'active')
                        ->where('classe_id', $classe->id);
                })
                ->orderBy('nom')
                ->orderBy('prenoms')
                ->get();

            $existing = $service->getForClasseMatiere(
                $classe->id,
                $matiere->id,
                $anneeUniversitaire->id,
                $periode
            );

            $existingSessionsCount = ESBTPAttendance::where('classe_id', $classe->id)
                ->where('matiere_id', $matiere->id)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->where(function ($q) {
                    $q->where('call_type', 'merged')->orWhereNull('call_type');
                })
                ->count();

            // Volume horaire prévu pour cette matière et cette période (source: planifications académiques)
            $semestreFilter = match ($periode) {
                'semestre1' => 1,
                'semestre2' => 2,
                default => null,
            };
            $volumeHoraireTotal = (float) ESBTPPlanificationAcademique::query()
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('matiere_id', $matiere->id)
                ->when($semestreFilter !== null, fn ($q) => $q->where('semestre', $semestreFilter))
                ->sum('volume_horaire_total');

            $html = view('esbtp.attendances.partials.manual-hours-tab', [
                'classe' => $classe,
                'matiere' => $matiere,
                'periode' => $periode,
                'anneeUniversitaire' => $anneeUniversitaire,
                'etudiants' => $etudiants,
                'existing' => $existing,
                'existingSessionsCount' => $existingSessionsCount,
                'volumeHoraireTotal' => $volumeHoraireTotal,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'nbEtudiants' => $etudiants->count(),
                'nbExisting' => $existing->count(),
                'existingSessionsCount' => $existingSessionsCount,
                'volumeHoraireTotal' => $volumeHoraireTotal,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur loadManualTab: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du chargement.',
            ], 500);
        }
    }

    public function storeManualHours(StoreManualAttendanceHoursRequest $request, ManualAttendanceHoursService $service)
    {
        $classe = ESBTPClasse::findOrFail($request->classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee_universitaire_id);

        $validIds = $classe->etudiants()
            ->whereHas('inscriptions', function ($q) use ($anneeUniversitaire, $classe) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                    ->where('status', 'active')
                    ->where('classe_id', $classe->id);
            })
            ->pluck('esbtp_etudiants.id')
            ->all();

        $entries = collect($request->input('entries', []))
            ->filter(fn ($e) => in_array((int) ($e['etudiant_id'] ?? 0), $validIds, true))
            ->values()
            ->all();

        if (empty($entries)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun étudiant valide dans la saisie.',
            ], 422);
        }

        $count = $service->upsertBatch(
            $entries,
            [
                'classe_id' => (int) $request->classe_id,
                'matiere_id' => (int) $request->matiere_id,
                'annee_universitaire_id' => $anneeUniversitaire->id,
                'periode' => $request->periode,
            ],
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} ligne(s) enregistrée(s) avec succès.",
            'count' => $count,
        ]);
    }

    public function deleteManualHours($id, ManualAttendanceHoursService $service)
    {
        // Autorisation déjà gérée par le middleware `permission:create_attendance` sur la route.
        $row = ESBTPAttendanceManualHours::findOrFail((int) $id);

        $service->delete($row->id, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Entrée supprimée. Le bulletin utilisera à nouveau les séances.',
        ]);
    }
}
