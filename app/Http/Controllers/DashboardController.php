<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Grade;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;

use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPNote;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMessage;
use App\Models\ESBTPStudent;
use App\Models\ESBTPAcademicYear;
use App\Models\ESBTPExam;
use App\Models\ESBTPGrade;
use App\Models\ESBTPSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Constructeur qui applique le middleware auth.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le tableau de bord principal en fonction du rôle de l'utilisateur.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur est un super admin
        if ($user->hasRole('superAdmin')) {
            return $this->superAdminDashboard();
        }

        // Vérifier si l'utilisateur est un secrétaire
        if ($user->hasRole('secretaire')) {
            return $this->secretaireDashboard();
        }

        // Vérifier si l'utilisateur est un enseignant
        if ($user->hasRole(['teacher', 'enseignant'])) {
            return redirect()->route('teacher.dashboard');
        }

        // Vérifier si l'utilisateur est un étudiant
        if ($user->hasRole('etudiant')) {
            return $this->etudiantDashboard();
        }

        // Si aucun rôle spécifique n'est trouvé, afficher un tableau de bord générique
        return view('dashboard.index');
    }

    /**
     * Tableau de bord pour les super administrateurs avec toutes les permissions.
     */
    private function superAdminDashboard()
    {
        $user = Auth::user();
        $data = [
            'user' => $user,
            'totalUsers' => User::count()
        ];

        // Inscriptions en attente - SuperAdmin peut voir toutes les inscriptions
        $data['pendingInscriptionsCount'] = \App\Models\ESBTPInscription::where('status', 'pending')->count();

        // Étudiants
        $data['totalStudents'] = ESBTPEtudiant::count();
        $data['recentStudents'] = ESBTPEtudiant::orderBy('created_at', 'desc')->take(5)->get();

        // Filières
        try {
            $data['totalFilieres'] = ESBTPFiliere::count();
            $data['recentFilieres'] = ESBTPFiliere::orderBy('created_at', 'desc')->take(5)->get();
        } catch (\Exception $e) {
            $data['totalFilieres'] = 0;
            $data['recentFilieres'] = collect();
        }


        // Niveaux d'études
        try {
            $data['totalNiveaux'] = ESBTPNiveauEtude::count();
        } catch (\Exception $e) {
            $data['totalNiveaux'] = 0;
        }

        // Classes
        try {
            $data['totalClasses'] = ESBTPClasse::count();
        } catch (\Exception $e) {
            $data['totalClasses'] = 0;
        }

        // Matières
        try {
            $data['totalMatieres'] = ESBTPMatiere::count();
        } catch (\Exception $e) {
            $data['totalMatieres'] = 0;
        }

        // Examens
        try {
            $data['totalExamens'] = ESBTPEvaluation::count();
            $data['recentExamens'] = ESBTPEvaluation::with(['classe', 'matiere'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['totalExamens'] = 0;
            $data['recentExamens'] = collect();
        }

        // Bulletins
        try {
            $data['totalBulletins'] = ESBTPBulletin::count();
            $data['pendingBulletins'] = ESBTPBulletin::where('status', 'pending')->count();
        } catch (\Exception $e) {
            $data['totalBulletins'] = 0;
            $data['pendingBulletins'] = 0;
        }

        // Notes
        try {
            $data['totalNotes'] = ESBTPNote::count();
        } catch (\Exception $e) {
            $data['totalNotes'] = 0;
        }

        // Présences
        try {
            $data['totalPresences'] = ESBTPAttendance::count();
            $data['todayAttendances'] = ESBTPAttendance::whereDate('date', today())->count();
        } catch (\Exception $e) {
            $data['totalPresences'] = 0;
            $data['todayAttendances'] = 0;
        }

        // Emplois du temps
        try {
            $data['totalEmploiTemps'] = ESBTPEmploiTemps::count();
            $data['activeEmploiTemps'] = ESBTPEmploiTemps::where('is_active', true)->count();
        } catch (\Exception $e) {
            $data['totalEmploiTemps'] = 0;
            $data['activeEmploiTemps'] = 0;
        }

        // Séances de cours
        try {
            $data['totalSeances'] = ESBTPSeanceCours::count();
            $today = Carbon::now()->format('Y-m-d');
            $data['todayClasses'] = ESBTPSeanceCours::whereDate('date', $today)->count();
        } catch (\Exception $e) {
            $data['totalSeances'] = 0;
            $data['todayClasses'] = 0;
        }

        // Messages
        try {
            $data['recentMessages'] = Message::where(function($query) {
                    $query->where('recipient_type', 'admins')
                        ->whereNull('recipient_group');
                })
                ->orWhere(function($query) {
                    $query->where('recipient_type', 'all')
                        ->whereNull('recipient_group');
                })
                ->orWhere('recipient_id', Auth::id())
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentMessages'] = collect();
        }

        // Notifications
        try {
            $data['recentNotifications'] = Notification::orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentNotifications'] = collect();
        }

        return view('dashboard.superadmin', $data);
    }

    /**
     * Tableau de bord pour les secrétaires avec les permissions limitées.
     */
    private function secretaireDashboard()
    {
        $user = Auth::user();
        $data = [
            'user' => $user
        ];

        // Inscriptions en attente
        $data['pendingInscriptionsCount'] = \App\Models\ESBTPInscription::where('status', 'pending')->count();

        // Étudiants - Les secrétaires peuvent voir et créer des étudiants
        try {
            $data['totalStudents'] = ESBTPEtudiant::count();
            $data['recentStudents'] = ESBTPEtudiant::with(['inscriptions' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }])
                ->whereHas('inscriptions')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['totalStudents'] = 0;
            $data['recentStudents'] = collect();
        }

        // Présences - Les secrétaires peuvent gérer les présences
        try {
            $data['todayAttendances'] = ESBTPAttendance::whereDate('date', today())->count();
            $data['pendingJustifications'] = ESBTPAttendance::whereDate('date', '>=', now()->subDays(7))
                ->where('status', 'absent')
                ->where('justified', false)
                ->count();
        } catch (\Exception $e) {
            $data['todayAttendances'] = 0;
            $data['pendingJustifications'] = 0;
        }

        // Emplois du temps - Les secrétaires peuvent créer et consulter les emplois du temps
        try {
            $data['totalTimetables'] = ESBTPEmploiTemps::count();
            $today = Carbon::now()->format('Y-m-d');
            $data['todayClasses'] = ESBTPSeanceCours::whereDate('date', $today)->count();
        } catch (\Exception $e) {
            $data['totalTimetables'] = 0;
            $data['todayClasses'] = 0;
        }

        // Bulletins - Les secrétaires peuvent générer et consulter les bulletins
        try {
            $data['pendingBulletins'] = ESBTPBulletin::where('status', 'pending')->count();
        } catch (\Exception $e) {
            $data['pendingBulletins'] = 0;
        }

        // Messages - Les secrétaires peuvent envoyer et recevoir des messages
        try {
            $data['recentMessages'] = Message::where(function($query) {
                    $query->where('recipient_type', 'secretaires')
                        ->whereNull('recipient_group');
                })
                ->orWhere(function($query) {
                    $query->where('recipient_type', 'all')
                        ->whereNull('recipient_group');
                })
                ->orWhere('recipient_id', Auth::id())
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentMessages'] = collect();
        }

        return view('dashboard.secretaire', $data);
    }

    /**
     * Tableau de bord pour les étudiants avec vue uniquement sur leurs propres données.
     */
    private function etudiantDashboard()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            // Au lieu de rediriger, afficher une vue spéciale pour les étudiants sans profil
            return view('dashboard.etudiant_setup', [
                'user' => $user
            ]);
        }

        $data = [
            'user' => $user,
            'student' => $student
        ];

        // Récupérer l'emploi du temps d'aujourd'hui pour l'étudiant
        try {
            $today = strtolower(date('l'));
            $data['todayTimetable'] = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($student) {
                    $query->where('classe_id', $student->classe_id);
                })
                ->where('jour', $today)
                ->orderBy('heure_debut')
                ->with(['matiere', 'emploiTemps.classe', 'enseignant'])
                ->get();
        } catch (\Exception $e) {
            $data['todayTimetable'] = collect();
        }

        // Récupérer les notifications récentes pour l'étudiant
        try {
            $data['notifications'] = ESBTPAnnonce::where(function($query) use ($student) {
                    $query->where('recipient_type', 'etudiant')
                        ->whereNull('recipient_id');
                })
                ->orWhere(function($query) use ($student) {
                    $query->where('recipient_type', 'specific_user')
                        ->where('recipient_id', $user->id);
                })
                ->orWhere(function($query) use ($student) {
                    $query->where('recipient_type', 'specific_class')
                        ->where('recipient_group', $student->classe_id);
                })
                ->orWhere('recipient_type', 'all')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['notifications'] = collect();
        }

        // Récupérer les notes récentes de l'étudiant
        try {
            $data['recentGrades'] = ESBTPNote::with(['evaluation.matiere'])
                ->where('etudiant_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentGrades'] = collect();
        }

        // Récupérer les statistiques de présence de l'étudiant
        try {
            $attendances = ESBTPAttendance::where('etudiant_id', $student->id)->get();
            $totalAttendances = $attendances->count();

            $data['attendanceStats'] = [
                'total' => $totalAttendances,
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'excused' => $attendances->where('status', 'excused')->count(),
                'rate' => $totalAttendances > 0
                    ? round(($attendances->where('status', 'present')->count() + $attendances->where('status', 'late')->count()) / $totalAttendances * 100, 2)
                    : 0
            ];
        } catch (\Exception $e) {
            $data['attendanceStats'] = [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'rate' => 0
            ];
        }

        return view('dashboard.etudiant', $data);
    }

    /**
     * Tableau de bord générique pour les utilisateurs sans rôle spécifique.
     */
    public function genericDashboard()
    {
        $user = Auth::user();

        return view('dashboard.index', [
            'user' => $user
        ]);
    }
}
