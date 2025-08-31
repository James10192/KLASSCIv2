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
use App\Models\ESBTPInscription;
use App\Models\ESBTPTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Vérifier si l'utilisateur est un coordinateur
        if ($user->hasRole('coordinateur')) {
            return $this->coordinateurDashboard();
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
        return view('dashboard.index', compact('user'));
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

        // Enseignants
        try {
            $data['totalTeachers'] = ESBTPTeacher::count();
        } catch (\Exception $e) {
            $data['totalTeachers'] = 0;
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

        // Inscriptions récentes (vraies données)
        try {
            $data['recentInscriptions'] = ESBTPInscription::with([
                'etudiant',
                'classe.filiere',
                'etudiant.classe.filiere'
            ])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentInscriptions'] = collect();
        }

        // Examens à venir (vraies données)
        try {
            $data['upcomingExams'] = ESBTPEvaluation::with(['matiere', 'classe'])
                ->where('date_evaluation', '>=', now())
                ->orderBy('date_evaluation', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $data['upcomingExams'] = collect();
        }

        // Annonces récentes
        try {
            $data['recentAnnouncements'] = ESBTPAnnonce::orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $data['recentAnnouncements'] = collect();
        }

        // Statistiques par filière avec couleurs pour le graphique
        $filiereStatsRaw = ESBTPFiliere::withCount('inscriptions')->get();
        $colors = ['#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#5e91de', '#f97316', '#06b6d4', '#84cc16', '#f43f5e', '#0453cb'];

        $data['filiereStats'] = $filiereStatsRaw->map(function($filiere, $index) use ($colors) {
            return [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'students' => $filiere->inscriptions_count,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // Données mensuelles pour les graphiques
        $data['monthlyStats'] = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $studentsCount = ESBTPEtudiant::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $inscriptionsCount = ESBTPInscription::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $data['monthlyStats'][] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => $studentsCount,
                'inscriptions' => $inscriptionsCount,
            ];
        }

        // Inscriptions par mois pour le graphique
        $data['inscriptionsByMonth'] = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Statistiques de présence
        try {
            $totalPresent = ESBTPAttendance::where('status', 'present')->whereDate('date', today())->count();
            $totalAbsent = ESBTPAttendance::where('status', 'absent')->whereDate('date', today())->count();
            $attendanceRate = $totalPresent + $totalAbsent > 0
                ? round(($totalPresent / ($totalPresent + $totalAbsent)) * 100, 1)
                : 0;

            $data['attendanceStats'] = [
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'attendance_rate' => $attendanceRate
            ];
        } catch (\Exception $e) {
            $data['attendanceStats'] = [
                'total_present' => 0,
                'total_absent' => 0,
                'attendance_rate' => 0
            ];
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
     * Tableau de bord pour les coordinateurs avec permissions de coordination.
     */
    private function coordinateurDashboard()
    {
        $user = Auth::user();
        $data = [
            'user' => $user
        ];

        // Statistiques accessibles aux coordinateurs
        try {
            // Étudiants - Coordinateurs peuvent voir et gérer les étudiants
            $data['totalStudents'] = ESBTPEtudiant::count();
            $data['recentStudents'] = ESBTPEtudiant::with(['classe.filiere'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['totalStudents'] = 0;
            $data['recentStudents'] = collect();
        }

        // Classes - Coordinateurs supervisent les classes
        try {
            $data['totalClasses'] = ESBTPClasse::count();
        } catch (\Exception $e) {
            $data['totalClasses'] = 0;
        }

        // Enseignants - Coordinateurs supervisent les enseignants
        try {
            $data['totalTeachers'] = ESBTPTeacher::count();
        } catch (\Exception $e) {
            $data['totalTeachers'] = 0;
        }

        // Évaluations - Coordinateurs peuvent voir les évaluations
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

        // Emplois du temps - Coordinateurs gèrent la planification
        try {
            $data['totalEmploiTemps'] = ESBTPEmploiTemps::count();
            $data['activeEmploiTemps'] = ESBTPEmploiTemps::where('is_active', true)->count();
        } catch (\Exception $e) {
            $data['totalEmploiTemps'] = 0;
            $data['activeEmploiTemps'] = 0;
        }

        // Présences - Coordinateurs suivent les présences
        try {
            $data['todayAttendances'] = ESBTPAttendance::whereDate('date', today())->count();
            $totalPresent = ESBTPAttendance::where('status', 'present')->whereDate('date', today())->count();
            $totalAbsent = ESBTPAttendance::where('status', 'absent')->whereDate('date', today())->count();
            $attendanceRate = $totalPresent + $totalAbsent > 0
                ? round(($totalPresent / ($totalPresent + $totalAbsent)) * 100, 1)
                : 0;

            $data['attendanceStats'] = [
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'attendance_rate' => $attendanceRate
            ];
        } catch (\Exception $e) {
            $data['todayAttendances'] = 0;
            $data['attendanceStats'] = [
                'total_present' => 0,
                'total_absent' => 0,
                'attendance_rate' => 0
            ];
        }

        // Messages pour coordinateurs
        try {
            $data['recentMessages'] = Message::where(function($query) {
                    $query->where('recipient_type', 'coordinateurs')
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

        // Inscriptions en attente
        try {
            $data['pendingInscriptionsCount'] = ESBTPInscription::where('status', 'pending')->count();
            $data['recentInscriptions'] = ESBTPInscription::with([
                'etudiant',
                'classe.filiere'
            ])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $data['pendingInscriptionsCount'] = 0;
            $data['recentInscriptions'] = collect();
        }

        // Annonces récentes
        try {
            $data['recentAnnouncements'] = ESBTPAnnonce::orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $data['recentAnnouncements'] = collect();
        }

        return view('dashboard.coordinateur', $data);
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

    /**
     * Dashboard Super Admin
     */
    public function superadmin()
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est bien super admin
        if (!$user->hasRole('superAdmin')) {
            abort(403, 'Accès non autorisé');
        }

        // Statistiques principales
        $totalStudents = ESBTPEtudiant::count();
        $totalFilieres = ESBTPFiliere::count();
        $totalMatieres = ESBTPMatiere::count();
        $totalClasses = ESBTPClasse::count();
        $totalTeachers = ESBTPTeacher::count();
        $totalUsers = User::count();

        // Inscriptions récentes (vraies données)
        $recentInscriptions = ESBTPInscription::with(['etudiant', 'classe.filiere'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Examens à venir (vraies données)
        $upcomingExams = ESBTPEvaluation::with(['matiere', 'classe'])
            ->where('date_evaluation', '>=', now())
            ->orderBy('date_evaluation', 'asc')
            ->limit(5)
            ->get();

        // Annonces récentes
        $recentAnnouncements = ESBTPAnnonce::orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Statistiques par filière avec couleurs pour le graphique
        $filiereStatsRaw = ESBTPFiliere::withCount('inscriptions')->get();
        $colors = ['#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#5e91de', '#f97316', '#06b6d4', '#84cc16', '#f43f5e', '#0453cb'];

        $filiereStats = $filiereStatsRaw->map(function($filiere, $index) use ($colors) {
            return [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'students' => $filiere->inscriptions_count,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // Données mensuelles pour les graphiques
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $studentsCount = ESBTPEtudiant::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $inscriptionsCount = ESBTPInscription::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $monthlyStats[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => $studentsCount,
                'inscriptions' => $inscriptionsCount,
            ];
        }

        // Inscriptions par mois pour le graphique
        $inscriptionsByMonth = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return view('dashboard.superadmin', compact(
            'totalStudents',
            'totalFilieres',
            'totalMatieres',
            'totalClasses',
            'totalTeachers',
            'totalUsers',
            'recentInscriptions',
            'upcomingExams',
            'recentAnnouncements',
            'filiereStats',
            'monthlyStats',
            'inscriptionsByMonth'
        ));
    }

    /**
     * Dashboard Secrétaire
     */
    public function secretaire()
    {
        $user = Auth::user();

        if (!$user->hasRole('secretaire')) {
            abort(403, 'Accès non autorisé');
        }

        // Statistiques pour secrétaire
        $totalStudents = ESBTPEtudiant::count();
        $pendingInscriptions = ESBTPInscription::where('status', 'pending')->count();
        $totalClasses = ESBTPClasse::count();
        $recentStudents = ESBTPEtudiant::with('classe.filiere')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.secretaire', compact(
            'totalStudents',
            'pendingInscriptions',
            'totalClasses',
            'recentStudents'
        ));
    }

    /**
     * Dashboard Étudiant
     */
    public function etudiant()
    {
        $user = Auth::user();

        if (!$user->hasRole('etudiant')) {
            abort(403, 'Accès non autorisé');
        }

        // Récupérer l'étudiant associé
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$etudiant) {
            abort(404, 'Profil étudiant non trouvé');
        }

        // Prochains examens pour cet étudiant
        $upcomingExams = ESBTPEvaluation::where('classe_id', $etudiant->classe_id)
            ->where('date_evaluation', '>=', now())
            ->with('matiere')
            ->orderBy('date_evaluation', 'asc')
            ->limit(5)
            ->get();

        // Dernières notes
        $recentGrades = DB::table('esbtp_notes')
            ->join('esbtp_evaluations', 'esbtp_notes.evaluation_id', '=', 'esbtp_evaluations.id')
            ->join('esbtp_matieres', 'esbtp_evaluations.matiere_id', '=', 'esbtp_matieres.id')
            ->where('esbtp_notes.etudiant_id', $etudiant->id)
            ->select('esbtp_notes.*', 'esbtp_matieres.nom as matiere_nom', 'esbtp_evaluations.type')
            ->orderBy('esbtp_notes.created_at', 'desc')
            ->limit(5)
            ->get();

        // Annonces pour la classe
        $announcements = ESBTPAnnonce::whereHas('destinataires', function($query) use ($etudiant) {
                $query->where('destinataire_type', 'classe')
                      ->where('destinataire_id', $etudiant->classe_id);
            })
            ->orWhereHas('destinataires', function($query) {
                $query->where('destinataire_type', 'tous');
            })
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return view('dashboard.etudiant', compact(
            'etudiant',
            'upcomingExams',
            'recentGrades',
            'announcements'
        ));
    }

    /**
     * Obtenir les statistiques mensuelles
     */
    private function getMonthlyStats()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => ESBTPEtudiant::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'inscriptions' => ESBTPInscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }
        return $months;
    }

    /**
     * Obtenir les statistiques de présence
     */
    private function getAttendanceStats()
    {
        // Vérifier si le modèle de présence existe
        if (!class_exists('App\Models\ESBTPPresence')) {
            return [
                'total_present' => 0,
                'total_absent' => 0,
                'attendance_rate' => 0
            ];
        }

        $totalPresent = DB::table('esbtp_presences')
            ->where('statut', 'present')
            ->whereDate('date', today())
            ->count();

        $totalAbsent = DB::table('esbtp_presences')
            ->where('statut', 'absent')
            ->whereDate('date', today())
            ->count();

        $attendanceRate = $totalPresent + $totalAbsent > 0
            ? round(($totalPresent / ($totalPresent + $totalAbsent)) * 100, 1)
            : 0;

        return [
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'attendance_rate' => $attendanceRate
        ];
    }

    /**
     * Obtenir les données financières (simulées)
     */
    private function getFinancialData()
    {
        // Pour l'instant, données simulées
        // À remplacer par de vraies données quand le module comptabilité sera implémenté
        return [
            'total_paid' => 45070000,
            'total_due' => 32400000,
            'monthly_revenue' => [
                'Jan' => 3500000,
                'Fév' => 4200000,
                'Mar' => 3800000,
                'Avr' => 4100000,
                'Mai' => 3900000,
                'Jun' => 4300000,
            ]
        ];
    }

    /**
     * Générer une couleur aléatoire pour les graphiques
     */
    private function getRandomColor()
    {
        $colors = [
            '#0453cb', '#5e91de', '#06b6d4', '#10b981',
            '#f59e0b', '#ef4444', '#ec4899', '#84cc16'
        ];
        return $colors[array_rand($colors)];
    }
}
