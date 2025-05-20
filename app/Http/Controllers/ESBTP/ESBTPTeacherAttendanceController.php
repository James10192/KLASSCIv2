<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAttendanceCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPCourse;
use App\Models\ESBTPSecurityEvent;
use App\Models\ESBTPDailyCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class ESBTPTeacherAttendanceController extends Controller
{
    /**
     * Affiche la vue d'émargement avec les cours du jour
     */
    public function index()
    {
        $user = Auth::user();
        $teacherModel = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        $teacherId = $teacherModel ? $teacherModel->id : null;
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        $todaySeances = ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->whereDate('date_seance', $today)
            ->with(['matiere', 'classe', 'teacherAttendance'])
            ->orderBy('heure_debut')
            ->get();

        $settings = config('esbtp.attendance', []);

        return view('esbtp.attendance.mark', [
            'todayCourses' => $todaySeances, // pour compatibilité avec la vue
            'settings' => $settings
        ]);
    }

    /**
     * Traite la demande d'émargement
     */
    public function mark(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'course_id' => 'required|exists:esbtp_seance_cours,id',
            'latitude' => 'required_if:settings.geolocation_required,true|numeric',
            'longitude' => 'required_if:settings.geolocation_required,true|numeric',
        ]);

        try {
            DB::beginTransaction();

            $seance = ESBTPSeanceCours::findOrFail($request->course_id);
            $teacher = Auth::user();
            $settings = config('esbtp.attendance', []);

            // Récupérer le modèle enseignant lié à l'utilisateur
            $teacherModel = \App\Models\ESBTPTeacher::where('user_id', $teacher->id)->first();
            if (!$teacherModel) {
                throw new \Exception('Aucun profil enseignant associé à ce compte.');
            }
            // Vérifier que l'enseignant est bien assigné à cette séance
            if ($seance->teacher_id !== $teacherModel->id) {
                throw new \Exception('Vous n\'êtes pas autorisé à émarger pour cette séance.');
            }

            // Vérifier que la séance n'a pas déjà été émargée
            if ($seance->teacherAttendance()->exists()) {
                throw new \Exception('Cette séance a déjà été émargée.');
            }

            // Vérifier la fenêtre horaire d'émargement
            $now = Carbon::now();
            $seanceStart = Carbon::parse($seance->heure_debut);
            $seanceEnd = Carbon::parse($seance->heure_fin);

            $earlyWindow = $seanceStart->copy()->subMinutes($settings['allowed_early_minutes'] ?? 30);
            $lateWindow = $seanceEnd->copy()->addMinutes($settings['allowed_late_minutes'] ?? 15);

            if ($now->lt($earlyWindow) || $now->gt($lateWindow)) {
                throw new \Exception('L\'émargement n\'est pas disponible en dehors des horaires autorisés.');
            }

            // Récupérer le code actif
            $activeCode = ESBTPDailyCode::where('status', 'active')
                ->where('valid_until', '>', now())
                ->first();

            if (!$activeCode) {
                throw new \Exception('Aucun code d\'émargement actif n\'est disponible.');
            }

            // Vérifier le nombre de tentatives
            $attempts = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $seance->id)
                ->where('daily_code_id', $activeCode->id)
                ->count();

            if ($attempts >= ($settings['max_attempts'] ?? 3)) {
                throw new \Exception('Nombre maximum de tentatives atteint pour ce code.');
            }

            // Vérifier le code
            if ($request->code !== $activeCode->code) {
                // Enregistrer la tentative échouée
                $activeCode->recordAttempt($teacher->id, $seance->id, false, [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'error' => 'Code invalide'
                ]);

                throw new \Exception('Code d\'émargement invalide.');
            }

            // Créer l'émargement
            $agent = new Agent();
            $attendance = ESBTPTeacherAttendance::create([
                'teacher_id' => $teacher->id,
                'course_id' => $seance->id,
                'daily_code_id' => $activeCode->id,
                'marked_at' => now(),
                'date' => $seance->date_seance,
                'status' => 'fait',
                'ip_address' => $request->ip(),
                'device_info' => [
                    'device' => $agent->device(),
                    'platform' => $agent->platform(),
                    'browser' => $agent->browser(),
                    'robot' => $agent->isRobot(),
                ],
            ]);

            // Enregistrer la tentative réussie
            $activeCode->recordAttempt($teacher->id, $seance->id, true, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attendance_id' => $attendance->id
            ]);

            DB::commit();

            return redirect()->route('esbtp.attendance.mark')->with('success', 'Émargement enregistré avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('esbtp.attendance.mark')->with('error', $e->getMessage());
        }
    }

    /**
     * Calcule la distance en mètres entre deux points géographiques
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Rayon de la Terre en mètres

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'course_id' => 'required|exists:esbtp_courses,id',
            'latitude' => 'required_if:settings.geolocation_required,true|numeric',
            'longitude' => 'required_if:settings.geolocation_required,true|numeric',
        ]);

        try {
            DB::beginTransaction();

            $course = ESBTPCourse::findOrFail($request->course_id);
            $teacher = Auth::user();
            $settings = config('esbtp.attendance', []);

            // Vérifier que l'enseignant est bien assigné à ce cours
            if ($course->teacher_id !== $teacher->id) {
                ESBTPSecurityEvent::logEvent(
                    'UNAUTHORIZED_COURSE_ACCESS',
                    'Tentative d\'émargement pour un cours non assigné',
                    [
                        'course_id' => $course->id,
                        'teacher_id' => $teacher->id
                    ]
                );
                throw new \Exception('Vous n\'êtes pas autorisé à émarger pour ce cours.');
            }

            // Vérifier que le cours n'a pas déjà été émargé
            if ($course->attendance()->exists()) {
                ESBTPSecurityEvent::logEvent(
                    'DUPLICATE_ATTENDANCE',
                    'Tentative d\'émargement multiple pour le même cours',
                    [
                        'course_id' => $course->id,
                        'teacher_id' => $teacher->id
                    ]
                );
                throw new \Exception('Ce cours a déjà été émargé.');
            }

            // Vérifier la fenêtre horaire d'émargement
            $now = Carbon::now();
            $courseStart = Carbon::parse($course->start_time);
            $courseEnd = Carbon::parse($course->end_time);

            $earlyWindow = $courseStart->copy()->subMinutes($settings['allowed_early_minutes'] ?? 30);
            $lateWindow = $courseEnd->copy()->addMinutes($settings['allowed_late_minutes'] ?? 15);

            if ($now->lt($earlyWindow) || $now->gt($lateWindow)) {
                ESBTPSecurityEvent::logEvent(
                    'OUTSIDE_TIME_WINDOW',
                    'Tentative d\'émargement en dehors de la fenêtre horaire autorisée',
                    [
                        'course_id' => $course->id,
                        'teacher_id' => $teacher->id,
                        'attempt_time' => $now->toDateTimeString(),
                        'allowed_window' => [
                            'start' => $earlyWindow->toDateTimeString(),
                            'end' => $lateWindow->toDateTimeString()
                        ]
                    ]
                );
                throw new \Exception('L\'émargement n\'est pas disponible en dehors des horaires autorisés.');
            }

            // Récupérer le code actif
            $activeCode = ESBTPDailyCode::where('status', 'active')
                ->where('valid_until', '>', now())
                ->first();

            if (!$activeCode) {
                ESBTPSecurityEvent::logEvent(
                    'INVALID_CODE',
                    'Tentative d\'émargement avec un code expiré ou invalide',
                    [
                        'code' => $request->code,
                        'teacher_id' => $teacher->id
                    ]
                );
                throw new \Exception('Aucun code d\'émargement actif n\'est disponible.');
            }

            // Vérifier le nombre de tentatives
            $attempts = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $course->id)
                ->where('daily_code_id', $activeCode->id)
                ->count();

            if ($attempts >= ($settings['max_attempts'] ?? 3)) {
                ESBTPSecurityEvent::logEvent(
                    'MAX_ATTEMPTS_EXCEEDED',
                    'Nombre maximum de tentatives atteint',
                    [
                        'code' => $request->code,
                        'teacher_id' => $teacher->id,
                        'attempts' => $attempts
                    ]
                );
                throw new \Exception('Nombre maximum de tentatives atteint pour ce code.');
            }

            // Vérifier le code
            if ($request->code !== $activeCode->code) {
                ESBTPSecurityEvent::logEvent(
                    'INVALID_CODE_ATTEMPT',
                    'Tentative avec un code invalide',
                    [
                        'code' => $request->code,
                        'teacher_id' => $teacher->id,
                        'attempt_number' => $attempts + 1
                    ]
                );

                // Enregistrer la tentative échouée
                $activeCode->recordAttempt($teacher->id, $course->id, false, [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'error' => 'Code invalide'
                ]);

                throw new \Exception('Code d\'émargement invalide.');
            }

            // Create attendance record
            $attendance = new ESBTPTeacherAttendance([
                'teacher_id' => $teacher->id,
                'course_id' => $course->id,
                'daily_code_id' => $activeCode->id,
                'marked_at' => now(),
                'date' => $course->date ?? $course->date_seance,
                'status' => 'fait',
                'ip_address' => $request->ip_address,
                'device_info' => $request->device_info,
                'attempt_count' => $attempts + 1,
                'validation_status' => 'pending'
            ]);

            // Validate device info and IP
            if (!$attendance->hasValidDeviceInfo()) {
                ESBTPSecurityEvent::logEvent(
                    'DEVICE_CHANGE',
                    'Changement de dispositif détecté',
                    [
                        'teacher_id' => $teacher->id,
                        'device_info' => $request->device_info
                    ]
                );
            }

            if (!$attendance->hasValidIpAddress()) {
                ESBTPSecurityEvent::logEvent(
                    'IP_CHANGE',
                    'Changement d\'adresse IP détecté',
                    [
                        'teacher_id' => $teacher->id,
                        'ip_address' => $request->ip_address
                    ]
                );
            }

            $attendance->save();

            // Record successful attempt
            $activeCode->recordAttempt($teacher->id, $course->id, true, [
                'ip_address' => $request->ip_address,
                'device_info' => $request->device_info
            ]);

            ESBTPSecurityEvent::logEvent(
                'SUCCESSFUL_ATTENDANCE',
                'Émargement réussi',
                [
                    'teacher_id' => $teacher->id,
                    'course_id' => $course->id,
                    'daily_code_id' => $activeCode->id
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Présence enregistrée avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Affiche l'historique des émargements de l'enseignant connecté
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $attendances = ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->orderBy('marked_at', 'desc')
            ->paginate(15);

        return view('esbtp.attendance.history', compact('attendances'));
    }

    /**
     * Affiche l'historique global des émargements enseignants pour le superadmin
     */
    public function adminHistory(Request $request)
    {
        $query = ESBTPTeacherAttendance::with(['teacher', 'course.class', 'course.subject', 'dailyCode']);
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        $attendances = $query->orderByDesc('date')->paginate(20);
        $teachers = \App\Models\User::role('enseignant')->get();
        return view('esbtp.attendance.admin_history', compact('attendances', 'teachers'));
    }
}
