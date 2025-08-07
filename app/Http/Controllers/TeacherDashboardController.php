<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    /**
     * Constructeur avec middleware
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:teacher|enseignant']);
    }

    /**
     * Afficher le tableau de bord de l'enseignant
     */
    public function index()
    {
        $user = Auth::user();
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;
        \Log::info('Dashboard enseignant - user_id', ['user_id' => $user->id, 'teacher_id' => $teacherId]);
        // 1. Séances à venir (7 prochains jours)
        $today = Carbon::today();
        $upcomingClasses = ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->whereDate('date_seance', '>=', $today)
            ->with(['matiere', 'classe'])
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->take(5)
            ->get();
        \Log::info('Dashboard enseignant - Nombre de séances trouvées', ['count' => $upcomingClasses->count()]);
        foreach ($upcomingClasses as $seance) {
            \Log::info('Dashboard enseignant - Séance', [
                'id' => $seance->id,
                'jour' => $seance->jour,
                'heure_debut' => $seance->heure_debut,
                'heure_fin' => $seance->heure_fin,
                'matiere' => $seance->matiere->name ?? null,
                'classe' => $seance->classe->name ?? null,
                'teacher_id' => $seance->teacher_id
            ]);
        }

        // 2. Statistiques de présence
        $totalSeances = ESBTPSeanceCours::where('teacher_id', $teacherId)->count();
        $attendedSeances = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)->count();
        $attendanceRate = $totalSeances > 0 ? round(($attendedSeances / $totalSeances) * 100, 2) : 0;
        $attendanceStats = [
            'totalCourses' => $totalSeances,
            'attendedCourses' => $attendedSeances,
            'absentCourses' => $totalSeances - $attendedSeances,
            'attendanceRate' => $attendanceRate
        ];

        // 3. Données d'émargement
        $dailyCode = ESBTPDailyCode::where('is_active', true)
            ->where('valid_until', '>', Carbon::now())
            ->first();
        
        $todayAttendance = ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->whereDate('validated_at', $today)
            ->latest()
            ->first();

        // 4. Séances du jour courantes et à venir
        $todayClasses = ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->whereDate('date_seance', $today)
            ->with(['matiere', 'classe', 'teacherAttendance'])
            ->orderBy('heure_debut')
            ->get();

        // 5. Appels en cours ou nécessaires
        $pendingRollCalls = ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->whereDate('date_seance', $today)
            ->where('heure_debut', '<=', Carbon::now()->addMinutes(15))  // Cours en cours ou qui vient de commencer
            ->whereDoesntHave('studentAttendances') // Pas d'appel fait encore
            ->with(['matiere', 'classe'])
            ->get();

        // 6. Notifications
        $notifications = [];
        if ($dailyCode && !$todayAttendance) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Vous n\'avez pas encore fait votre émargement aujourd\'hui.',
                'action' => route('esbtp.teacher.attendance.index'),
                'action_text' => 'Émarger maintenant'
            ];
        }
        if ($pendingRollCalls->count() > 0) {
            $notifications[] = [
                'type' => 'info',
                'message' => 'Vous avez ' . $pendingRollCalls->count() . ' appel(s) à faire.',
                'action' => '#pending-roll-calls',
                'action_text' => 'Voir les appels'
            ];
        }

        // 7. Jours de la semaine
        $joursSemaine = [
            0 => 'Lundi', 1 => 'Mardi', 2 => 'Mercredi', 3 => 'Jeudi',
            4 => 'Vendredi', 5 => 'Samedi'
        ];

        return view('dashboard.teacher', compact(
            'upcomingClasses',
            'attendanceStats',
            'notifications',
            'joursSemaine',
            'dailyCode',
            'todayAttendance',
            'todayClasses',
            'pendingRollCalls'
        ));
    }

    /**
     * Interface pour faire l'appel des étudiants
     */
    public function showRollCall($seanceId)
    {
        $user = Auth::user();
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        
        $seance = ESBTPSeanceCours::with(['matiere', 'classe', 'classe.etudiants'])
            ->where('id', $seanceId)
            ->where('teacher_id', $teacher->id ?? null)
            ->firstOrFail();

        // Vérifier que l'enseignant est émargé aujourd'hui
        $todayAttendance = ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->whereDate('validated_at', Carbon::today())
            ->first();

        if (!$todayAttendance) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Vous devez d\'abord faire votre émargement avant de pouvoir faire l\'appel.');
        }

        // Récupérer les étudiants de la classe
        $etudiants = $seance->classe->etudiants()->with('user')->get();

        // Vérifier si l'appel a déjà été fait
        $existingAttendances = ESBTPAttendance::where('seance_cours_id', $seanceId)->get();
        $hasRollCall = $existingAttendances->isNotEmpty();

        return view('dashboard.teacher-roll-call', compact('seance', 'etudiants', 'existingAttendances', 'hasRollCall'));
    }

    /**
     * Enregistrer l'appel des étudiants
     */
    public function storeRollCall(Request $request, $seanceId)
    {
        $user = Auth::user();
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        
        $seance = ESBTPSeanceCours::where('id', $seanceId)
            ->where('teacher_id', $teacher->id ?? null)
            ->firstOrFail();

        $request->validate([
            'attendances' => 'required|array',
            'attendances.*' => 'in:present,absent,late'
        ]);

        try {
            DB::beginTransaction();

            // Supprimer les anciens appels pour cette séance
            ESBTPAttendance::where('seance_cours_id', $seanceId)->delete();

            // Enregistrer les nouveaux appels
            foreach ($request->attendances as $etudiantId => $status) {
                ESBTPAttendance::create([
                    'etudiant_id' => $etudiantId,
                    'seance_cours_id' => $seanceId,
                    'classe_id' => $seance->classe_id,
                    'matiere_id' => $seance->matiere_id,
                    'teacher_id' => $user->id,
                    'date' => Carbon::today(),
                    'status' => $status,
                    'is_justified' => false,
                    'created_by' => $user->id
                ]);
            }

            DB::commit();

            // **NOTIFICATION** : Notifier le coordinateur et les étudiants absents
            try {
                $notificationService = app(NotificationService::class);
                
                // 1. Notifier le coordinateur de l'appel terminé
                $notificationService->notifyCoordinateurStudentRollCallCompleted($user, $seance, $request->attendances);
                
                // 2. Notifier les étudiants absents
                $absentStudentIds = collect($request->attendances)
                    ->filter(fn($status) => $status === 'absent')
                    ->keys()
                    ->toArray();
                
                if (!empty($absentStudentIds)) {
                    $absentStudents = \App\Models\ESBTPEtudiant::whereIn('id', $absentStudentIds)->get();
                    $notificationService->notifyStudentsAbsence($absentStudents, $seance, $user);
                }
                
            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'envoi des notifications d\'appel: ' . $e->getMessage());
                // Ne pas interrompre le processus principal
            }

            return redirect()->route('teacher.dashboard')
                ->with('success', 'Appel enregistré avec succès pour le cours de ' . $seance->matiere->name . '.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'enregistrement de l\'appel : ' . $e->getMessage());
        }
    }

    /**
     * Clôturer un cours
     */
    public function closeCourse($seanceId)
    {
        $user = Auth::user();
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        
        $seance = ESBTPSeanceCours::where('id', $seanceId)
            ->where('teacher_id', $teacher->id ?? null)
            ->firstOrFail();

        // Vérifier que l'appel a été fait
        $hasAttendances = ESBTPAttendance::where('seance_cours_id', $seanceId)->exists();
        
        if (!$hasAttendances) {
            return redirect()->back()
                ->with('error', 'Vous devez d\'abord faire l\'appel avant de clôturer le cours.');
        }

        // Marquer le cours comme terminé
        $seance->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
            'completed_by' => $user->id
        ]);

        // **NOTIFICATION** : Notifier le coordinateur de la clôture du cours
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->notifyCoordinateurCourseClosed($user, $seance, request('notes'));
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de la notification de clôture: ' . $e->getMessage());
            // Ne pas interrompre le processus principal
        }

        return redirect()->route('teacher.dashboard')
            ->with('success', 'Cours clôturé avec succès.');
    }

    /**
     * Afficher l'emploi du temps de l'enseignant
     */
    public function showTimetable()
    {
        $user = Auth::user();
        $teacherModel = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        $teacherId = $teacherModel ? $teacherModel->id : null;

        // Récupérer les IDs des emplois du temps actifs
        $idsActifs = \App\Models\ESBTPEmploiTemps::where('is_active', 1)->pluck('id')->toArray();

        // Récupérer toutes les séances de cours de l'enseignant liées à un emploi du temps actif
        $seances = ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->whereIn('emploi_temps_id', $idsActifs)
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->with(['emploiTemps.classe', 'matiere'])
            ->get();

        \Log::info('Emploi du temps enseignant - Nombre de séances trouvées', ['count' => $seances->count(), 'teacher_id' => $teacherId, 'idsActifs' => $idsActifs]);

        // Organiser les séances par jour (1=Lundi, 2=Mardi, ...)
        $emploiTempsSemaine = [];
        foreach ([1, 2, 3, 4, 5, 6] as $jour) {
            $emploiTempsSemaine[$jour] = $seances->where('jour', $jour)->sortBy('heure_debut');
        }

        // Définir les jours de la semaine en français pour l'affichage (1=Lundi, ...)
        $joursSemaine = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi'
        ];

        // Créneaux horaires d'1h de 08:00 à 18:00
        $creneaux = [];
        for ($h = 8; $h < 18; $h++) {
            $start = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $end = str_pad($h + 1, 2, '0', STR_PAD_LEFT) . ':00';
            $creneaux[] = "$start-$end";
        }

        return view('teacher.timetable', compact('emploiTempsSemaine', 'joursSemaine', 'creneaux'));
    }

    /**
     * Afficher les notes saisies par l'enseignant
     */
    public function showGrades()
    {
        $user = Auth::user();
        $enseignantNom = $user->name;

        // Récupérer les évaluations créées par cet enseignant
        $evaluations = ESBTPEvaluation::where('enseignant', $enseignantNom)
            ->with(['matiere', 'classe'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        // Récupérer les dernières notes saisies par cet enseignant
        $recentGrades = ESBTPNote::whereHas('evaluation', function($query) use ($enseignantNom) {
                $query->where('enseignant', $enseignantNom);
            })
            ->with(['etudiant', 'evaluation.matiere'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('teacher.grades', compact('evaluations', 'recentGrades', 'enseignantNom'));
    }

    /**
     * Afficher les présences enregistrées par l'enseignant
     */
    public function showAttendance()
    {
        $user = Auth::user();
        $enseignantNom = $user->name;

        // Récupérer les séances de cours pour lesquelles l'enseignant a enregistré des présences
        $seances = ESBTPSeanceCours::where('teacher', $enseignantNom)
            ->whereHas('attendances')
            ->with(['emploiTemps.classe', 'matiere', 'attendances.etudiant'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        // Récupérer les statistiques de présence par classe
        $classeStats = DB::table('esbtp_attendances')
            ->join('esbtp_seance_cours', 'esbtp_attendances.seance_cours_id', '=', 'esbtp_seance_cours.id')
            ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
            ->join('esbtp_classes', 'esbtp_emploi_temps.classe_id', '=', 'esbtp_classes.id')
            ->where('esbtp_seance_cours.teacher', $enseignantNom)
            ->select(
                'esbtp_classes.nom as classe',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN esbtp_attendances.status = "present" THEN 1 ELSE 0 END) as presents'),
                DB::raw('SUM(CASE WHEN esbtp_attendances.status = "absent" THEN 1 ELSE 0 END) as absents'),
                DB::raw('SUM(CASE WHEN esbtp_attendances.status = "late" THEN 1 ELSE 0 END) as retards')
            )
            ->groupBy('esbtp_classes.nom')
            ->get();

        return view('teacher.attendance', compact('seances', 'classeStats', 'enseignantNom'));
    }

    /**
     * Récupérer les séances de cours à venir pour l'enseignant
     */
    private function getUpcomingClasses($enseignantNom)
    {
        $today = Carbon::today();
        $inAWeek = Carbon::today()->addDays(7);

        try {
            return ESBTPSeanceCours::where('teacher', $enseignantNom)
                ->whereBetween('date', [$today->format('Y-m-d'), $inAWeek->format('Y-m-d')])
                ->with(['matiere', 'emploiTemps.classe'])
                ->orderBy('date')
                ->orderBy('heure_debut')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des séances à venir: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Calculer les statistiques de présence pour l'enseignant
     */
    private function getAttendanceStats($enseignantNom)
    {
        try {
            $seances = ESBTPSeanceCours::where('teacher', $enseignantNom)->get();
            $totalSeances = $seances->count();

            // Compter les séances où l'enseignant était présent (présence marquée)
            $presentSeances = $seances->filter(function($seance) {
                return $seance->presence_enseignant === true;
            })->count();

            // Calculer le taux de présence
            $attendanceRate = $totalSeances > 0 ? ($presentSeances / $totalSeances) * 100 : 0;

            return [
                'totalCourses' => $totalSeances,
                'attendedCourses' => $presentSeances,
                'absentCourses' => $totalSeances - $presentSeances,
                'attendanceRate' => $attendanceRate
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des statistiques de présence: ' . $e->getMessage());
            return [
                'totalCourses' => 0,
                'attendedCourses' => 0,
                'absentCourses' => 0,
                'attendanceRate' => 0
            ];
        }
    }

    /**
     * Récupérer les notifications pour l'enseignant
     */
    private function getNotifications()
    {
        try {
            return \App\Models\Notification::where('user_id', Auth::id())
                ->orWhere(function($query) {
                    $query->where('recipient_type', 'teacher')
                        ->whereNull('recipient_id');
                })
                ->orWhere(function($query) {
                    $query->where('recipient_type', 'all');
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des notifications: ' . $e->getMessage());
            return collect();
        }
    }
}
