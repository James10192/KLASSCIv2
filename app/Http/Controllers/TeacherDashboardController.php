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
use App\Models\ESBTPTeacherAvailability;
use App\Models\ESBTPTeacher;
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
        
        $todayAttendance = ESBTPTeacherAttendance::where('teacher_id', $teacherId)
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
                'action' => route('esbtp.attendance.mark'),
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

        // 7. Jours de la semaine (1=Lundi, 2=Mardi, etc.)
        $joursSemaine = [
            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
            5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche', 7 => 'Dimanche'
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
        $callType = request()->get('type', 'start');
        
        // Récupérer le teacher associé à l'utilisateur connecté
        $teacher = ESBTPTeacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            abort(403, 'Vous n\'êtes pas enregistré comme enseignant.');
        }
        
        $seance = ESBTPSeanceCours::with(['matiere', 'classe', 'classe.etudiants'])
            ->where('id', $seanceId)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        // **WORKFLOW** : Vérifier le workflow et les permissions
        $workflow = \App\Models\ESBTPSessionWorkflow::getOrCreateForSession($seanceId, $user->id);

        // Vérifier si cette étape peut être exécutée
        if ($callType === 'start' && !$workflow->canExecuteStep('call_start')) {
            return redirect()->route('teacher.select-call-type', $seanceId)
                ->with('error', 'Vous devez d\'abord compléter l\'émargement avant de faire l\'appel de début.');
        }

        if ($callType === 'end' && !$workflow->canExecuteStep('call_end')) {
            return redirect()->route('teacher.select-call-type', $seanceId)
                ->with('error', 'Vous devez d\'abord effectuer l\'appel de début avant de faire l\'appel de fin.');
        }

        // **LOGIQUE DE FENÊTRE D'ÉMARGEMENT FIN**
        if ($callType === 'end') {
            $now = Carbon::now();

            // heure_fin est déjà un DATETIME complet
            $heureFin = Carbon::parse($seance->heure_fin);

            // FENÊTRE 1 : Avant heure_fin - 20min → ❌ TROP TÔT
            $fenetreDebut = $heureFin->copy()->subMinutes(20);
            if ($now < $fenetreDebut) {
                return redirect()->route('teacher.select-call-type', $seanceId)
                    ->with('error', 'L\'appel de fin ne peut être fait que 20 minutes avant la fin du cours (' . $fenetreDebut->format('H:i') . ').');
            }

            // FENÊTRE 2 : heure_fin - 20min → heure_fin + 30min → ✅ OK pour clôturer normalement
            $fenetreFin = $heureFin->copy()->addMinutes(30);

            // Stocker si on est dans la fenêtre normale ou pas
            request()->merge(['within_close_window' => $now <= $fenetreFin]);
        }

        // Récupérer l'année universitaire courante
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer les étudiants de la classe inscrits pour l'année universitaire courante
        // ET avec un statut d'inscription 'active' uniquement
        $etudiants = $seance->classe->etudiants()
            ->with('user')
            ->whereHas('inscriptions', function($query) use ($anneeUniversitaire) {
                $query->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active');
            })
            ->get();

        // Vérifier si l'appel a déjà été fait pour ce type
        // Pour 'end', on vérifie aussi 'merged' car après fusion les records sont marqués 'merged'
        if ($callType === 'end') {
            $existingAttendances = ESBTPAttendance::where('seance_cours_id', $seanceId)
                ->whereIn('call_type', ['end', 'merged'])
                ->get();
        } else {
            $existingAttendances = ESBTPAttendance::where('seance_cours_id', $seanceId)
                ->where('call_type', $callType)
                ->get();
        }
        $hasRollCall = $existingAttendances->isNotEmpty();

        return view('dashboard.teacher-roll-call', compact('seance', 'etudiants', 'existingAttendances', 'hasRollCall', 'callType'));
    }

    /**
     * Enregistrer l'appel des étudiants
     */
    public function storeRollCall(Request $request, $seanceId)
    {
        $user = Auth::user();
        $callType = $request->input('call_type', 'start');
        
        // Récupérer le teacher associé à l'utilisateur connecté
        $teacher = ESBTPTeacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            abort(403, 'Vous n\'êtes pas enregistré comme enseignant.');
        }
        
        $seance = ESBTPSeanceCours::where('id', $seanceId)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        // Récupérer l'année universitaire courante
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $request->validate([
            'attendances' => 'required|array',
            'attendances.*' => 'in:present,absent,late',
            'call_type' => 'required|in:start,end'
        ]);

        // **WORKFLOW** : Vérifier que cette étape peut être exécutée
        $workflow = \App\Models\ESBTPSessionWorkflow::getOrCreateForSession($seanceId, $user->id);
        
        if ($callType === 'start' && !$workflow->canExecuteStep('call_start')) {
            return redirect()->route('teacher.select-call-type', $seanceId)
                ->with('error', 'Vous ne pouvez pas effectuer l\'appel de début maintenant.');
        }
        
        if ($callType === 'end' && !$workflow->canExecuteStep('call_end')) {
            return redirect()->route('teacher.select-call-type', $seanceId)
                ->with('error', 'Vous ne pouvez pas effectuer l\'appel de fin maintenant.');
        }

        try {
            DB::beginTransaction();

            if ($callType === 'start') {
                // APPEL DE DÉBUT : Supprimer et recréer
                ESBTPAttendance::where('seance_cours_id', $seanceId)
                    ->where('call_type', 'start')
                    ->delete();

                foreach ($request->attendances as $etudiantId => $status) {
                    ESBTPAttendance::create([
                        'etudiant_id' => $etudiantId,
                        'seance_cours_id' => $seanceId,
                        'annee_universitaire_id' => $anneeUniversitaire->id,
                        'classe_id' => $seance->classe_id,
                        'matiere_id' => $seance->matiere_id,
                        'teacher_id' => $teacher->id,
                        'date' => Carbon::today(),
                        'heure_debut' => $seance->heure_debut,
                        'heure_fin' => $seance->heure_fin,
                        'statut' => $status,
                        'call_type' => 'start',
                        'is_justified' => false,
                        'created_by' => $user->id
                    ]);
                }

                $workflow->markCallStartDone();

            } elseif ($callType === 'end') {
                // APPEL DE FIN : Vérifier si on est dans la fenêtre de clôture
                $withinCloseWindow = $request->input('within_close_window', true);

                // Récupérer les appels de début
                $startAttendances = ESBTPAttendance::where('seance_cours_id', $seanceId)
                    ->where('call_type', 'start')
                    ->get()
                    ->keyBy('etudiant_id');

                // Supprimer les anciens appels de fin et merged pour éviter la duplication
                ESBTPAttendance::where('seance_cours_id', $seanceId)
                    ->whereIn('call_type', ['end', 'merged'])
                    ->delete();

                if (!$withinCloseWindow) {
                    // FENÊTRE DÉPASSÉE (30min+ après heure_fin) : Copier l'appel début avec retards → présents
                    foreach ($startAttendances as $startAtt) {
                        $finalStatus = $startAtt->statut;

                        // Convertir les retards en présents
                        if (in_array($startAtt->statut, ['late', 'retard'])) {
                            $finalStatus = 'present';
                        }

                        ESBTPAttendance::create([
                            'etudiant_id' => $startAtt->etudiant_id,
                            'seance_cours_id' => $seanceId,
                            'annee_universitaire_id' => $anneeUniversitaire->id,
                            'classe_id' => $seance->classe_id,
                            'matiere_id' => $seance->matiere_id,
                            'teacher_id' => $teacher->id,
                            'date' => Carbon::today(),
                            'heure_debut' => $seance->heure_debut,
                            'heure_fin' => $seance->heure_fin,
                            'statut' => $finalStatus,
                            'call_type' => 'merged',
                            'is_justified' => false,
                            'created_by' => $user->id
                        ]);
                    }

                    // Marquer workflow comme incomplet
                    $workflow->current_step = 'closed_incomplete';
                    $workflow->call_end_done = true;
                    $workflow->call_end_done_at = now();
                    $workflow->save();

                } else {
                    // DANS LA FENÊTRE : Fusion normale avec appel de fin
                    \Log::info('🔀 FUSION des appels début + fin', [
                        'seance_id' => $seanceId,
                        'nb_etudiants' => count($request->attendances)
                    ]);

                    foreach ($request->attendances as $etudiantId => $endStatus) {
                        $startAttendance = $startAttendances->get($etudiantId);
                        $startStatus = $startAttendance ? $startAttendance->statut : 'absent';

                        // LOGIQUE DE FUSION SELON LES RÈGLES MÉTIER :
                        // 1. Absent/Retard début + Présent fin = RETARD (arrivé en retard)
                        // 2. Présent début + Absent fin = ABSENT (parti avant la fin)
                        // 3. Présent début + Présent fin = PRÉSENT
                        // 4. Absent début + Absent fin = ABSENT
                        // 5. Retard début + Présent fin = RETARD (garde le retard)
                        // 6. Retard début + Absent fin = ABSENT (retard puis parti)

                        $finalStatus = 'absent'; // Par défaut

                        if (in_array($startStatus, ['absent', 'late', 'retard']) && $endStatus === 'present') {
                            // Cas 1 et 5: Arrivé en retard mais présent à la fin = RETARD
                            $finalStatus = 'late';
                        } elseif ($startStatus === 'present' && $endStatus === 'present') {
                            // Cas 3: Présent début ET fin = PRÉSENT
                            $finalStatus = 'present';
                        } elseif ($startStatus === 'present' && $endStatus === 'absent') {
                            // Cas 2: Présent au début mais parti avant la fin = ABSENT
                            $finalStatus = 'absent';
                        } elseif (in_array($startStatus, ['absent', 'late', 'retard']) && $endStatus === 'absent') {
                            // Cas 4 et 6: Absent/Retard au début ET absent à la fin = ABSENT
                            $finalStatus = 'absent';
                        }

                        \Log::info('  📊 Étudiant #' . $etudiantId . ': ' . $startStatus . ' (début) + ' . $endStatus . ' (fin) → ' . $finalStatus . ' (FINAL)');

                        ESBTPAttendance::create([
                            'etudiant_id' => $etudiantId,
                            'seance_cours_id' => $seanceId,
                            'annee_universitaire_id' => $anneeUniversitaire->id,
                            'classe_id' => $seance->classe_id,
                            'matiere_id' => $seance->matiere_id,
                            'teacher_id' => $teacher->id,
                            'date' => Carbon::today(),
                            'heure_debut' => $seance->heure_debut,
                            'heure_fin' => $seance->heure_fin,
                            'statut' => $finalStatus,
                            'call_type' => 'merged',
                            'is_justified' => false,
                            'created_by' => $user->id
                        ]);
                    }

                    $workflow->markCallEndDone();
                }
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

            // **REDIRECTION SELON LE TYPE D'APPEL**
            if ($callType === 'start') {
                // Après appel DÉBUT → Dashboard avec message pour clôturer plus tard
                return redirect()->route('teacher.dashboard')
                    ->with('success', 'Appel de début enregistré avec succès. Vous pourrez clôturer le cours 20 minutes avant la fin.');

            } else {
                // Après appel FIN → Vérifier si workflow incomplet ou normal
                $withinCloseWindow = $request->input('within_close_window', true);

                if (!$withinCloseWindow) {
                    // Fenêtre dépassée → Dashboard avec warning
                    return redirect()->route('teacher.dashboard')
                        ->with('warning', 'Appel de fin copié depuis l\'appel de début (délai dépassé). Workflow incomplet - séance marquée présent mais non clôturée.');
                } else {
                    // Normal → Rediriger vers rapport (ou select-call-type si rapport pas implémenté)
                    return redirect()->route('teacher.select-call-type', $seanceId)
                        ->with('success', 'Appel de fin enregistré avec succès. Veuillez maintenant rédiger le rapport de cours.');
                }
            }
                
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
        $userId = $user->id;

        // Récupérer les évaluations créées par cet enseignant
        $evaluations = ESBTPEvaluation::where('created_by', $userId)
            ->with(['matiere', 'classe'])
            ->orderBy('date_evaluation', 'desc')
            ->paginate(10);

        // Récupérer les dernières notes saisies par cet enseignant
        $recentGrades = ESBTPNote::whereHas('evaluation', function($query) use ($userId) {
                $query->where('created_by', $userId);
            })
            ->with(['etudiant', 'evaluation.matiere'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('teacher.grades', compact('evaluations', 'recentGrades', 'user'));
    }

    /**
     * Afficher les présences enregistrées par l'enseignant
     */
    public function showAttendance()
    {
        $user = Auth::user();
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;

        // Récupérer les séances de cours pour lesquelles l'enseignant a enregistré des présences
        $seances = ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->whereHas('attendances')
            ->with(['classe', 'matiere', 'attendances.etudiant'])
            ->orderBy('date_seance', 'desc')
            ->paginate(10);

        // Récupérer les statistiques de présence par classe
        $classeStats = DB::table('esbtp_attendances')
            ->join('esbtp_seance_cours', 'esbtp_attendances.seance_cours_id', '=', 'esbtp_seance_cours.id')
            ->join('esbtp_classes', 'esbtp_attendances.classe_id', '=', 'esbtp_classes.id')
            ->where('esbtp_seance_cours.teacher_id', $teacherId)
            ->select(
                'esbtp_classes.name as classe',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN esbtp_attendances.status = "present" THEN 1 ELSE 0 END) as presents'),
                DB::raw('SUM(CASE WHEN esbtp_attendances.status = "absent" THEN 1 ELSE 0 END) as absents'),
                DB::raw('SUM(CASE WHEN esbtp_attendances.status = "late" THEN 1 ELSE 0 END) as retards')
            )
            ->groupBy('esbtp_classes.name')
            ->get();

        return view('teacher.attendance', compact('seances', 'classeStats', 'user'));
    }

    /**
     * Récupérer les séances de cours à venir pour l'enseignant
     */
    private function getUpcomingClasses($teacherId)
    {
        $today = Carbon::today();
        $inAWeek = Carbon::today()->addDays(7);

        try {
            return ESBTPSeanceCours::where('teacher_id', $teacherId)
                ->whereBetween('date_seance', [$today->format('Y-m-d'), $inAWeek->format('Y-m-d')])
                ->with(['matiere', 'classe'])
                ->orderBy('date_seance')
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
    private function getAttendanceStats($teacherId)
    {
        try {
            $seances = ESBTPSeanceCours::where('teacher_id', $teacherId)->get();
            $totalSeances = $seances->count();

            // Compter les séances où l'enseignant a fait l'émargement
            $presentSeances = ESBTPTeacherAttendance::where('teacher_id', $teacherId)->count();

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

    /**
     * Afficher la page de gestion des disponibilités de l'enseignant
     */
    public function showAvailability()
    {
        $user = Auth::user();
        $teacher = ESBTPTeacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Profil enseignant non trouvé.');
        }

        // Récupérer les disponibilités existantes et les organiser comme les pages admin
        $availabilityData = $this->prepareAvailabilityData($teacher);

        return view('teacher.availability', compact('teacher', 'availabilityData'));
    }

    /**
     * Mettre à jour les disponibilités de l'enseignant via AJAX
     */
    public function updateAvailability(Request $request)
    {
        try {
            $user = Auth::user();
            $teacher = ESBTPTeacher::where('user_id', $user->id)->first();
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant non trouvé.'
                ], 404);
            }

            // Validation des données
            $request->validate([
                'changes' => 'required|array',
                'changes.*.day' => 'required|integer|min:0|max:6',
                'changes.*.startTime' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
                'changes.*.endTime' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
                'changes.*.status' => 'required|string|in:available,preferred,unavailable'
            ]);

            $changes = $request->input('changes');
            
            \Log::info('🔧 DEBUG updateAvailability METHOD - TEACHER SELF-SERVICE');
            \Log::info('Teacher ID: ' . $teacher->id . ' (User: ' . $user->name . ')');
            \Log::info('Request changes: ' . json_encode($changes));

            DB::beginTransaction();

            foreach ($changes as $change) {
                $day = $change['day'];
                $startTime = $change['startTime'];
                $endTime = $change['endTime'];
                $status = $change['status'];

                \Log::info("Processing change: day=$day, $startTime-$endTime, status=$status");

                // Convertir les heures en entiers pour la logique de chevauchement
                $clickedStart = (int) substr($startTime, 0, 2);
                $clickedEnd = (int) substr($endTime, 0, 2);
                
                // Supprimer les créneaux existants qui se chevauchent
                $existingAvailabilities = ESBTPTeacherAvailability::where([
                    'teacher_id' => $teacher->id,
                    'day_of_week' => $day
                ])->get();
                
                foreach ($existingAvailabilities as $existing) {
                    // Parser correctement les heures depuis les timestamps
                    if ($existing->start_time instanceof \Carbon\Carbon) {
                        $existingStart = $existing->start_time->hour;
                        $existingEnd = $existing->end_time->hour;
                    } else {
                        // Extraire l'heure depuis la position 11 du timestamp "YYYY-MM-DD HH:MM:SS"
                        $existingStart = (int) substr($existing->start_time, 11, 2);
                        $existingEnd = (int) substr($existing->end_time, 11, 2);
                    }
                    
                    // Vérifier s'il y a chevauchement exact ou partiel
                    $hasOverlap = ($clickedStart < $existingEnd) && ($clickedEnd > $existingStart);
                    $isExactMatch = ($clickedStart == $existingStart) && ($clickedEnd == $existingEnd);
                    
                    if ($hasOverlap || $isExactMatch) {
                        \Log::info("Deleting existing availability ID={$existing->id}: {$existing->start_time}-{$existing->end_time} (overlaps/matches with {$startTime}-{$endTime})");
                        $existing->delete();
                    }
                }

                // Créer la nouvelle disponibilité si le statut n'est pas 'unavailable'
                if ($status !== 'unavailable') {
                    $newAvailability = ESBTPTeacherAvailability::create([
                        'teacher_id' => $teacher->id,
                        'day_of_week' => $day,
                        'start_time' => Carbon::today()->setHour($clickedStart)->setMinute(0),
                        'end_time' => Carbon::today()->setHour($clickedEnd)->setMinute(0),
                        'availability_type' => $status,
                        'created_by' => $user->id,
                        'updated_by' => $user->id
                    ]);
                    
                    \Log::info("Created new availability: teacher_id={$teacher->id}, day_of_week=$day, start_time={$startTime}, end_time={$endTime}, type=$status");
                } else {
                    \Log::info("Skipping unavailable status (no DB entry needed)");
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vos disponibilités ont été mises à jour avec succès.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Données invalides: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la mise à jour des disponibilités: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Préparer les données de disponibilité pour l'affichage (méthode identique aux pages admin)
     */
    private function prepareAvailabilityData($teacher)
    {
        // Utiliser des créneaux par heure comme la page EDIT pour cohérence
        $hours = range(8, 18); // 8h à 18h = 11 heures 
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']; // Exclure dimanche
        
        // Initialiser avec 'unavailable' par défaut
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), 'unavailable');
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
            
            // Remplir toutes les heures entre start_time et end_time
            if ($dayName) {
                for ($hour = $startHour; $hour < $endHour; $hour++) {
                    $hourIndex = $hour - 8; // Index dans le tableau (8h = index 0)
                    if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                        $availability[$dayName][$hourIndex] = $avail->availability_type;
                    }
                }
            }
        }
        
        return $availability;
    }
}
