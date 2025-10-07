<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPDailyCode;
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
            $teacher = ESBTPTeacher::where('user_id', $user->id)->first();

            if (!$teacher) {
                return back()->with('error', 'Profil enseignant non trouvé.');
            }

            // Check if teacher is assigned to this course
            if ($seanceCours->teacher_id !== $teacher->id) {
                return back()->with('error', 'Vous n\'êtes pas assigné à ce cours.');
            }

            // Check if teacher has already marked attendance for this course today
            $existingAttendance = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $seanceCours->id)
                ->whereDate('date', today())
                ->first();

            if ($existingAttendance) {
                return back()->with('warning', 'Vous avez déjà émargé pour ce cours aujourd\'hui.');
            }

            // **LOGIQUE DE FENÊTRE D'ÉMARGEMENT DÉBUT**
            $now = Carbon::now();

            // heure_debut est déjà un DATETIME complet
            $heureDebut = Carbon::parse($seanceCours->heure_debut);

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
                    'attempts' => 1,
                    'ip_address' => $request->ip(),
                    'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                    'validated_at' => now()
                ]);

                // Fermer le workflow directement
                $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $teacher->id);
                $workflow->current_step = 'closed_absent';
                $workflow->save();

                $dailyCode->recordAttempt(true);

                return redirect()->route('teacher.dashboard')
                    ->with('error', 'Délai d\'émargement dépassé (45 minutes après le début). Vous êtes marqué ABSENT. La séance ne sera pas comptabilisée.');
            }

            // Déterminer le statut : present ou late
            $status = ($now <= $limite20min) ? 'present' : 'late';

            // Create attendance record
            ESBTPTeacherAttendance::create([
                'teacher_id' => $teacher->id,
                'course_id' => $seanceCours->id,
                'daily_code_id' => $dailyCode->id,
                'date' => now()->toDateString(),
                'status' => $status,
                'attempts' => 1,
                'ip_address' => $request->ip(),
                'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                'validated_at' => now()
            ]);

            // Record successful attempt on the daily code
            $dailyCode->recordAttempt(true);

            // **WORKFLOW** : Mettre à jour le workflow de la séance
            $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $teacher->id);
            $workflow->markAttendanceSigned();

            // **NOTIFICATION** : Notifier le coordinateur de l'émargement effectué
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyCoordinateurTeacherAttendanceSigned($user, $seanceCours);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'envoi de la notification d\'émargement: ' . $e->getMessage());
                // Ne pas interrompre le processus principal
            }

            // **REDIRECTION** : Rediriger vers select-call-type pour faire l'appel de début
            $successMessage = $status === 'late'
                ? 'Émargement enregistré avec RETARD. Veuillez maintenant effectuer l\'appel de début.'
                : 'Émargement enregistré avec succès. Veuillez maintenant effectuer l\'appel de début.';

            return redirect()->route('teacher.select-call-type', $seanceCours->id)
                ->with('success', $successMessage);

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
        $this->authorize('generate-attendance-code');

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
        // $this->authorize('view-attendance-reports');

        // Récupérer l'année universitaire en cours
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        if (!$anneeEnCours) {
            return redirect()->back()->with('error', 'Aucune année universitaire définie comme courante.');
        }

        // Récupérer toutes les données pour les filtres
        $teachers = \App\Models\User::role('teacher')->orderBy('name')->get();
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
        })->whereHas('teacher', function($query) {
            $query->role('teacher');
        })->count();
        
        // IMPORTANT: Compter d'abord les présents ET les retards séparément
        $presentOnly = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->whereHas('teacher', function($query) {
            $query->role('teacher');
        })->where('status', 'present')->count();

        $attendancesLate = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->whereHas('teacher', function($query) {
            $query->role('teacher');
        })->where('status', 'late')->count();

        // Le KPI "Présents" doit inclure les retards car un retard = présence quand même
        $attendancesPresent = $presentOnly + $attendancesLate;
        
        $attendancesToday = ESBTPTeacherAttendance::whereHas('course.emploiTemps', function($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        })->whereHas('course', function($q) {
            $q->where('type', 'course');
        })->whereHas('teacher', function($query) {
            $query->role('teacher');
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
            'teacherAttendances' => function($q) {
                $q->whereHas('teacher', function($query) {
                    $query->role('teacher');
                });
            }
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
            'attendancesToday'
        ));
    }

    /**
     * Affiche la page de sélection du type d'appel (début/fin)
     */
    public function selectCallType($seanceId)
    {
        $user = Auth::user();
        $seance = ESBTPSeanceCours::with(['matiere', 'classe'])->findOrFail($seanceId);
        
        // Récupérer le modèle enseignant associé à l'utilisateur
        $teacherModel = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
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
        
        return view('teacher.select-call-type', compact('seance', 'workflow'));
    }
}
