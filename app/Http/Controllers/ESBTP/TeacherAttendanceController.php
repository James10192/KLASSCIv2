<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendanceSettings;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPSessionWorkflow;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceController extends Controller
{
    /**
     * Affiche la page d'émargement avec les cours du jour
     */
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Get today's courses for the teacher  
        $dayOfWeek = $today->dayOfWeek; // 0=Sunday, 1=Monday, etc.
        // Convert to database format (1=Monday, 7=Sunday)
        $dayOfWeekDb = $dayOfWeek == 0 ? 7 : $dayOfWeek;
        
        $todayCourses = ESBTPSeanceCours::with(['matiere', 'emploiTemps.classe'])
            ->where('teacher_id', $user->id) // Direct teacher assignment on seance
            ->where('is_active', true)
            ->where('jour', $dayOfWeekDb)
            ->get();

        // Load teacher attendance status for each course
        $todayCourses->each(function($course) use ($user, $today) {
            $course->teacherAttendance = ESBTPTeacherAttendance::where('teacher_id', $user->id)
                ->where('course_id', $course->id)
                ->whereDate('date', $today)
                ->first();
        });

        return view('esbtp.teacher-attendance.index', compact('todayCourses'));
    }

    /**
     * Affiche l'historique des émargements
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur est connecté et a un profil enseignant
        if (!$user || !$user->enseignant) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Vous devez avoir un profil enseignant pour accéder à cette page.');
        }

        $teacher = $user->enseignant;

        // Récupérer les paramètres de filtrage
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        // Récupérer l'historique des émargements
        $attendances = ESBTPTeacherAttendance::with(['emploiDuTemps.matiere', 'emploiDuTemps.classe'])
            ->where('enseignant_id', $teacher->id)
            ->whereYear('validated_at', $year)
            ->whereMonth('validated_at', $month)
            ->orderBy('validated_at', 'desc')
            ->paginate(15);

        // Calculer les statistiques
        $stats = [
            'total' => $attendances->total(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count()
        ];

        return view('esbtp.teacher.attendance.history', compact('attendances', 'stats', 'month', 'year'));
    }

    /**
     * Traite la signature de présence
     */
    public function sign(Request $request)
    {
        $user = Auth::user();

        // Valider les données du formulaire
        $request->validate([
            'code' => 'required|string|size:6',
            'course_id' => 'required|exists:esbtp_seance_cours,id'
        ]);

        try {
            // Find the active daily code
            $dailyCode = ESBTPDailyCode::where('code', $request->code)
                ->where('status', 'active')
                ->where('is_active', true)
                ->first();

            if (!$dailyCode || !$dailyCode->isValid()) {
                return back()->with('error', 'Code d\'émargement invalide ou expiré.');
            }

            // Get the course (seance)
            $seanceCours = ESBTPSeanceCours::findOrFail($request->course_id);

            // Get the teacher record from esbtp_teachers table
            $teacher = $user->teacherProfile;

            if (!$teacher) {
                return back()->with('error', 'Profil enseignant non trouvé.');
            }

            // Check if teacher is assigned to this course
            if ($seanceCours->teacher_id !== $teacher->id) {
                return back()->with('error', 'Vous n\'êtes pas assigné à ce cours.');
            }

            // **VÉRIFICATION DES ÉMARGEMENTS EXISTANTS (DÉBUT ET FIN)**
            // Utiliser teacher_id (ESBTPTeacher.id) pas user_id et chercher par date pas par code
            $emargementDebut = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $seanceCours->id)
                ->whereDate('date', today())
                ->where('type', 'start')
                ->first();

            $emargementFin = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $seanceCours->id)
                ->whereDate('date', today())
                ->where('type', 'end')
                ->first();

            // **DÉTERMINER QUEL TYPE D'ÉMARGEMENT FAIRE**
            $now = Carbon::now();
            $heureDebut = Carbon::parse($seanceCours->heure_debut);
            $heureFin = Carbon::parse($seanceCours->heure_fin);
            $fenetreClotureDebut = $heureFin->copy()->subMinutes(20);

            // Est-on dans la fenêtre de clôture?
            $isInClosingWindow = $now->gte($fenetreClotureDebut);

            // Récupérer le workflow pour vérifier si l'appel de début est fait
            $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);

            // Déterminer le type d'émargement à faire
            if (!$emargementDebut) {
                // Pas encore d'émargement de début → FAIRE ÉMARGEMENT DÉBUT
                $emargementType = 'start';
            } elseif ($emargementDebut && $emargementFin) {
                // Les deux émargements sont déjà faits
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('success', 'Vous avez déjà émargé le début et la fin de cette séance.');
            } elseif (!$workflow->call_start_done) {
                // Émargement début fait mais appel de début pas encore fait
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('info', 'Vous devez d\'abord effectuer l\'appel de début avant de pouvoir émarger la fin de la séance.');
            } elseif (!$isInClosingWindow) {
                // Appel début fait mais pas encore dans la fenêtre de clôture
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('info', 'Émargement de début déjà effectué. L\'émargement de fin sera disponible à partir de ' . $fenetreClotureDebut->format('H:i') . '.');
            } elseif ($isInClosingWindow && !$emargementFin) {
                // Appel début fait + dans fenêtre clôture + pas encore émargement fin → FAIRE ÉMARGEMENT FIN
                $emargementType = 'end';
            } else {
                // Cas par défaut (ne devrait pas arriver)
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('info', 'Veuillez vérifier l\'état de votre émargement.');
            }

            // **LOGIQUE SELON LE TYPE D'ÉMARGEMENT**
            if ($emargementType === 'start') {
                // ========== ÉMARGEMENT DE DÉBUT ==========

                // FENÊTRE 1 : AVANT heure_debut → ❌ IMPOSSIBLE d'émarger
                if ($now < $heureDebut) {
                    $dailyCode->recordAttempt(false);
                    return back()->with('error', 'Vous ne pouvez pas émarger avant le début du cours (' . $heureDebut->format('H:i') . ').');
                }

                // FENÊTRE 2 : heure_debut → heure_debut + 20min → ✅ PRÉSENT
                $limite20min = $heureDebut->copy()->addMinutes(20);

                // FENÊTRE 3 : heure_debut + 20min → heure_debut + 45min → ⚠️ RETARD
                $limite45min = $heureDebut->copy()->addMinutes(45);

                // FENÊTRE 4 : heure_debut + 45min et plus → ❌ ABSENT (workflow fermé)
                if ($now > $limite45min) {
                    // Marquer enseignant ABSENT
                    ESBTPTeacherAttendance::create([
                        'teacher_id' => $teacher->id,
                        'course_id' => $seanceCours->id,
                        'daily_code_id' => $dailyCode->id,
                        'date' => now()->toDateString(),
                        'status' => 'absent',
                        'type' => 'start',
                        'attempts' => 1,
                        'ip_address' => $request->ip(),
                        'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                        'validated_at' => now()
                    ]);

                    // Fermer le workflow directement
                    $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);
                    $workflow->current_step = 'closed_absent';
                    $workflow->save();

                    $dailyCode->recordAttempt(true);

                    return redirect()->route('teacher.dashboard')
                        ->with('error', 'Délai d\'émargement dépassé (45 minutes après le début). Vous êtes marqué ABSENT. La séance ne sera pas comptabilisée.');
                }

                // Déterminer le statut : present ou late
                $status = ($now <= $limite20min) ? 'present' : 'late';

                // Créer l'émargement de DÉBUT
                ESBTPTeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'course_id' => $seanceCours->id,
                    'daily_code_id' => $dailyCode->id,
                    'date' => now()->toDateString(),
                    'status' => $status,
                    'type' => 'start',
                    'attempts' => 1,
                    'ip_address' => $request->ip(),
                    'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                    'validated_at' => now()
                ]);

                $dailyCode->recordAttempt(true);

                // Mettre à jour le workflow - ÉMARGEMENT DE DÉBUT
                $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);
                $workflow->markAttendanceStartSigned();

                // Notification
                try {
                    $notificationService = app(NotificationService::class);
                    $notificationService->notifyCoordinateurTeacherAttendanceSigned($user, $seanceCours);
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'envoi de la notification d\'émargement: ' . $e->getMessage());
                }

                $successMessage = $status === 'late'
                    ? 'Émargement de DÉBUT enregistré avec RETARD. Veuillez maintenant effectuer l\'appel de début.'
                    : 'Émargement de DÉBUT enregistré avec succès. Veuillez maintenant effectuer l\'appel de début.';

                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('success', $successMessage);

            } else {
                // ========== ÉMARGEMENT DE FIN ==========

                // Vérifier qu'on est dans la fenêtre de clôture
                if (!$isInClosingWindow) {
                    return back()->with('error', 'L\'émargement de fin ne peut être fait qu\'à partir de ' . $fenetreClotureDebut->format('H:i') . ' (20 minutes avant la fin du cours).');
                }

                // FENÊTRE : heure_fin - 20min → heure_fin + 30min → ✅ OK
                $fenetreClotureFin = $heureFin->copy()->addMinutes(30);

                if ($now > $fenetreClotureFin) {
                    return back()->with('error', 'Délai d\'émargement de fin dépassé (30 minutes après la fin du cours).');
                }

                // Créer l'émargement de FIN
                ESBTPTeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'course_id' => $seanceCours->id,
                    'daily_code_id' => $dailyCode->id,
                    'date' => now()->toDateString(),
                    'status' => 'present', // Toujours present pour émargement de fin
                    'type' => 'end',
                    'attempts' => 1,
                    'ip_address' => $request->ip(),
                    'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                    'validated_at' => now()
                ]);

                $dailyCode->recordAttempt(true);

                // Mettre à jour le workflow - ÉMARGEMENT DE FIN
                $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);
                $workflow->markAttendanceEndSigned();

                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('success', 'Émargement de FIN enregistré avec succès. Vous pouvez maintenant clôturer la séance.');
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'émargement: ' . $e->getMessage());
            if (isset($dailyCode)) {
                $dailyCode->recordAttempt(false);
            }
            return back()->with('error', 'Une erreur est survenue lors de l\'émargement. Veuillez réessayer.');
        }
    }

    public function generateDailyCode()
    {
        $this->authorize('attendances.generate_codes');

        $code = ESBTPDailyCode::create([
            'code' => ESBTPDailyCode::generateCode(),
            'expiration' => now()->addHours(24),
            'is_active' => true,
            'generated_by' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Code généré avec succès: ' . $code->code);
    }

    public function signAttendance(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'course_id' => 'required|exists:esbtp_matieres,id'
        ]);

        $dailyCode = ESBTPDailyCode::where('code', $request->code)
            ->where('is_active', true)
            ->where('expiration', '>', now())
            ->firstOrFail();

        // Vérifier si l'enseignant n'a pas déjà émargé pour ce cours
        $existingAttendance = ESBTPTeacherAttendance::where([
            'teacher_id' => auth()->id(),
            'course_id' => $request->course_id,
            'daily_code_id' => $dailyCode->id
        ])->first();

        if ($existingAttendance) {
            return redirect()->back()->with('error', 'Vous avez déjà émargé pour ce cours.');
        }

        // Créer l'enregistrement de présence
        ESBTPTeacherAttendance::create([
            'teacher_id' => auth()->id(),
            'course_id' => $request->course_id,
            'daily_code_id' => $dailyCode->id,
            'validated_at' => now(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent()
        ]);

        return redirect()->back()->with('success', 'Présence enregistrée avec succès.');
    }

    public function report(Request $request)
    {
        // Autorisation gérée par le middleware de route (teacher|superAdmin)
        // $this->authorize('attendances.view_reports');

        // Récupérer l'année universitaire en cours
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        if (!$anneeEnCours) {
            return redirect()->back()->with('error', 'Aucune année universitaire définie comme courante.');
        }

        // Récupérer toutes les données pour les filtres
        $teachers = \App\Models\User::role(['enseignant', 'teacher'])->orderBy('name')->get();
        $matieres = \App\Models\ESBTPMatiere::orderBy('name')->get();
        $classes = \App\Models\ESBTPClasse::with('filiere', 'niveau')->orderBy('name')->get();
        
        // Statistiques globales pour l'année en cours (seulement les cours)
        $totalSeances = \App\Models\ESBTPSeanceCours::whereHas('emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->where('type', 'course')->count();
        
        $totalAttendances = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->whereIn('status', ['present', 'late'])->count();
        
        // IMPORTANT: Compter d'abord les présents ET les retards séparément
        $presentOnly = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->where('status', 'present')->count();

        $attendancesLate = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->where('status', 'late')->count();

        $attendancesAbsent = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->where('status', 'absent')->count();

        // Le KPI "Présents" doit inclure les retards car un retard = présence quand même pour la comptabilité globale
        $attendancesPresent = $presentOnly + $attendancesLate;
        
        $attendancesToday = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->whereDate('created_at', today())->count();

        // Récupérer toutes les séances de cours de l'année avec leur statut d'émargement
        $seancesQuery = \App\Models\ESBTPSeanceCours::with([
            'matiere:id,name',
            'teacher:id,user_id',
            'teacher.user:id,name,email',
            'emploiTemps:id,classe_id,titre,annee_universitaire_id,is_active,date_debut,date_fin',
            'emploiTemps.classe:id,name,filiere_id,niveau_etude_id',
            'emploiTemps.classe.filiere:id,name',
            'emploiTemps.classe.niveau:id,name',
            'teacherAttendances',
            'sessionReport'
        ])
        ->where('type', 'course') // Filtrer seulement les cours
        ->whereHas('emploiTemps', function($q) use ($anneeEnCours, $request) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
            
            // Filtre emploi du temps actifs/tous
            if ($request->filled('emploi_status') && $request->emploi_status === 'active_only') {
                $q->where('is_active', true);
            }
            // Par défaut, on montre tous les emplois du temps (actifs et inactifs)
        });

        // Appliquer les filtres
        if ($request->filled('date')) {
            $seancesQuery->whereDate('date_seance', $request->date);
        }

        if ($request->filled('teacher_id')) {
            $seancesQuery->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('matiere_id')) {
            $seancesQuery->where('matiere_id', $request->matiere_id);
        }

        if ($request->filled('classe_id')) {
            $seancesQuery->whereHas('emploiTemps', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }

        // Filtre par statut d'émargement
        if ($request->filled('status')) {
            if ($request->status === 'not_signed') {
                // Séances sans émargement
                $seancesQuery->whereDoesntHave('teacherAttendances');
            } else {
                // Séances avec émargement du statut demandé
                $seancesQuery->whereHas('teacherAttendances', function($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }
        }

        $seances = $seancesQuery->orderBy('date_seance', 'desc')
                              ->orderBy('heure_debut', 'asc')
                              ->paginate(20);

        $seancesForStats = (clone $seancesQuery)->get();
        $teacherStats = [];
        $today = \Carbon\Carbon::today();

        foreach ($seancesForStats as $seance) {
            if (! $seance->teacher_id) {
                continue;
            }

            $teacherId = $seance->teacher_id;
            $teacherName = $seance->teacher?->user?->name
                ?? $seance->teacher?->name
                ?? 'Enseignant';

            if (! isset($teacherStats[$teacherId])) {
                $teacherStats[$teacherId] = [
                    'teacher_id' => $teacherId,
                    'name' => $teacherName,
                    'total' => 0,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'not_signed' => 0,
                ];
            }

            $status = $this->resolveAttendanceStatus($seance, $today);
            $teacherStats[$teacherId]['total']++;

            if (isset($teacherStats[$teacherId][$status])) {
                $teacherStats[$teacherId][$status]++;
            } else {
                $teacherStats[$teacherId]['not_signed']++;
            }
        }

        $teacherStats = collect($teacherStats)
            ->map(function ($stats) {
                $presentLike = $stats['present'] + $stats['late'];
                $stats['taux'] = $stats['total'] > 0
                    ? round(($presentLike / $stats['total']) * 100)
                    : 0;
                return $stats;
            })
            ->sortByDesc('taux')
            ->values();

        return view('esbtp.teacher-attendance.report', compact(
            'seances',
            'teachers', 
            'matieres',
            'classes',
            'anneeEnCours',
            'totalSeances',
            'totalAttendances',
            'attendancesPresent', 
            'attendancesLate',
            'attendancesAbsent',
            'attendancesToday',
            'teacherStats'
        ));
    }

    public function teacherReport(Request $request, \App\Models\ESBTPTeacher $teacher)
    {
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return redirect()->back()->with('error', 'Aucune année universitaire définie comme courante.');
        }

        $today = \Carbon\Carbon::today();

        $seancesQuery = \App\Models\ESBTPSeanceCours::with([
            'matiere:id,name',
            'teacher:id,user_id',
            'teacher.user:id,name,email',
            'emploiTemps:id,classe_id,titre,annee_universitaire_id,is_active,date_debut,date_fin',
            'emploiTemps.classe:id,name,filiere_id,niveau_etude_id',
            'emploiTemps.classe.filiere:id,name',
            'emploiTemps.classe.niveau:id,name',
            'teacherAttendances',
            'sessionReport'
        ])
            ->where('type', 'course')
            ->where('teacher_id', $teacher->id)
            ->whereHas('emploiTemps', function ($q) use ($anneeEnCours) {
                $q->where('annee_universitaire_id', $anneeEnCours->id);
            });

        if ($request->filled('date')) {
            $seancesQuery->whereDate('date_seance', $request->date);
        }

        $seances = $seancesQuery->orderBy('date_seance', 'desc')
            ->orderBy('heure_debut', 'asc')
            ->paginate(20);

        $seancesAll = (clone $seancesQuery)->get();
        $stats = [
            'total' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'not_signed' => 0,
        ];

        $monthly = [];
        $monthLabels = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juil', 8 => 'Aout', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        foreach ($seancesAll as $seance) {
            $status = $this->resolveAttendanceStatus($seance, $today);
            $stats['total']++;
            if (isset($stats[$status])) {
                $stats[$status]++;
            } else {
                $stats['not_signed']++;
            }

            $monthIndex = (int) \Carbon\Carbon::parse($seance->date_seance)->format('n');
            if (! isset($monthly[$monthIndex])) {
                $monthly[$monthIndex] = [
                    'label' => $monthLabels[$monthIndex] ?? (string) $monthIndex,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'not_signed' => 0,
                ];
            }
            if (isset($monthly[$monthIndex][$status])) {
                $monthly[$monthIndex][$status]++;
            } else {
                $monthly[$monthIndex]['not_signed']++;
            }
        }

        ksort($monthly);
        $monthlyStats = array_values($monthly);
        $presentLike = $stats['present'] + $stats['late'];
        $attendanceRate = $stats['total'] > 0 ? round(($presentLike / $stats['total']) * 100) : 0;

        return view('esbtp.teacher-attendance.teacher-report', [
            'teacher' => $teacher,
            'anneeEnCours' => $anneeEnCours,
            'seances' => $seances,
            'stats' => $stats,
            'attendanceRate' => $attendanceRate,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    private function resolveAttendanceStatus($seance, \Carbon\Carbon $today): string
    {
        $attendance = $seance->teacherAttendances
            ->first(function ($attendance) use ($today) {
                $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                    ? $attendance->date
                    : \Carbon\Carbon::parse($attendance->date);
                return $attendanceDate->isSameDay($today);
            });

        if (! $attendance) {
            $attendance = $seance->teacherAttendances
                ->first(function ($attendance) use ($seance) {
                    $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                        ? $attendance->date
                        : \Carbon\Carbon::parse($attendance->date);
                    return $attendanceDate->isSameDay(\Carbon\Carbon::parse($seance->date_seance));
                });
        }

        if (! $attendance) {
            $attendance = $seance->teacherAttendances->sortByDesc('created_at')->first();
        }

        return $attendance ? $attendance->status : 'not_signed';
    }

    /**
     * Affiche la page de sélection du type d'appel (début/fin)
     */
    public function selectCallType($seanceId)
    {
        $user = Auth::user();
        $seance = ESBTPSeanceCours::with(['matiere', 'classe'])->findOrFail($seanceId);

        // Récupérer le modèle enseignant associé à l'utilisateur
        $teacherModel = $user->teacherProfile;
        if (!$teacherModel) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Aucun profil enseignant associé à ce compte.');
        }

        // Vérifier que l'enseignant est assigné à cette séance
        if ($seance->teacher_id !== $teacherModel->id) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Vous n\'êtes pas autorisé à accéder à cette séance.');
        }

        // Récupérer ou créer le workflow pour cette séance
        $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceId, $user->id);

        // **VÉRIFICATION DE LA FENÊTRE POUR L'APPEL DE FIN**
        $now = Carbon::now();
        $heureFin = Carbon::parse($seance->heure_fin);
        $fenetreDebut = $heureFin->copy()->subMinutes(20); // 20 minutes avant la fin

        // Vérifier si on peut faire l'appel de fin (dans la fenêtre 20 min avant fin)
        $canEndCall = $now >= $fenetreDebut;
        $endCallMessage = null;

        if (!$canEndCall) {
            $endCallMessage = 'L\'appel de fin sera disponible à partir de ' . $fenetreDebut->format('H:i') . ' (20 minutes avant la fin du cours).';
        }

        return view('teacher.select-call-type', compact('seance', 'workflow', 'canEndCall', 'endCallMessage'));
    }
}
