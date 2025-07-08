<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendanceSettings;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
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

        // Get the teacher's identifier (full name or other unique identifier)
        $teacherIdentifier = $user->firstname . ' ' . $user->lastname;

        // Get today's courses through SeanceCours
        $todayCourses = ESBTPSeanceCours::whereHas('matiere')
            ->whereHas('emploiTemps', function($query) use ($today) {
                $query->where('is_active', true)
                      ->where('is_current', true)
                      ->where('date_debut', '<=', $today)
                      ->where('date_fin', '>=', $today);
            })
            ->where('enseignant', $teacherIdentifier)
            ->with(['matiere', 'emploiTemps.classe'])
            ->get();

        $attendances = ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->whereDate('validated_at', Carbon::today())
            ->get();

        return view('esbtp.teacher-attendance.index', compact('todayCourses', 'attendances'));
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

        // Vérifier si l'utilisateur est connecté et a un profil enseignant
        if (!$user || !$user->enseignant) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Vous devez avoir un profil enseignant pour émarger.');
        }

        $teacher = $user->enseignant;

        // Valider les données du formulaire
        $request->validate([
            'code' => 'required|string|size:6',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric'
        ]);

        try {
            // Vérifier si le code est valide
            $dailyCode = ESBTPDailyCode::where('code', $request->code)
                ->where('expiration', '>', now())
                ->first();

            if (!$dailyCode) {
                return back()->with('error', 'Code d\'émargement invalide ou expiré.');
            }

            // Vérifier si l'enseignant a déjà émargé avec ce code
            $existingAttendance = ESBTPTeacherAttendance::where('enseignant_id', $teacher->id)
                ->where('daily_code_id', $dailyCode->id)
                ->first();

            if ($existingAttendance) {
                return back()->with('error', 'Vous avez déjà émargé avec ce code.');
            }

            // Créer l'enregistrement de présence
            $attendance = new ESBTPTeacherAttendance([
                'enseignant_id' => $teacher->id,
                'daily_code_id' => $dailyCode->id,
                'validated_at' => now(),
                'status' => 'present', // ou calculer en fonction de l'heure
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'validation_status' => 'pending'
            ]);

            $attendance->save();

            return back()->with('success', 'Émargement enregistré avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'émargement: ' . $e->getMessage());
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
