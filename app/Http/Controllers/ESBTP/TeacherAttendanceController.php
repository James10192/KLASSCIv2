<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendanceSettings;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
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
            
            // Check if teacher is assigned to this course
            if ($seanceCours->teacher_id !== $user->id) {
                return back()->with('error', 'Vous n\'êtes pas assigné à ce cours.');
            }
            
            // Check if teacher has already marked attendance for this course today
            $existingAttendance = ESBTPTeacherAttendance::where('teacher_id', $user->id)
                ->where('course_id', $seanceCours->id)
                ->whereDate('date', today())
                ->first();

            if ($existingAttendance) {
                return back()->with('warning', 'Vous avez déjà émargé pour ce cours aujourd\'hui.');
            }

            // Create attendance record
            ESBTPTeacherAttendance::create([
                'teacher_id' => $user->id,
                'course_id' => $seanceCours->id,
                'daily_code_id' => $dailyCode->id,
                'date' => now()->toDateString(),
                'status' => 'present',
                'attempts' => 1,
                'ip_address' => $request->ip(),
                'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                'validated_at' => now()
            ]);

            // Record successful attempt on the daily code
            $dailyCode->recordAttempt(true);

            // **NOTIFICATION** : Notifier le coordinateur de l'émargement effectué
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyCoordinateurTeacherAttendanceSigned($user, $seanceCours);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'envoi de la notification d\'émargement: ' . $e->getMessage());
                // Ne pas interrompre le processus principal
            }

            return back()->with('success', 'Émargement enregistré avec succès.');

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

    public function report()
    {
        $this->authorize('view-attendance-reports');

        $attendances = ESBTPTeacherAttendance::with(['teacher', 'course'])
            ->latest()
            ->paginate(20);

        return view('esbtp.teacher-attendance.report', compact('attendances'));
    }
}
