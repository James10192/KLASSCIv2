<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\Timetable;
use App\Models\ESBTPMatiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtudiantController extends Controller
{
    /**
     * Constructeur qui applique le middleware auth et role:etudiant.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:etudiant')->except(['index', 'create', 'store']);
    }

    /**
     * Affiche la liste des étudiants.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Rediriger vers le contrôleur approprié dans le dossier principal
        return app(\App\Http\Controllers\ESBTPEtudiantController::class)->index(request());
    }

    /**
     * Afficher le formulaire de création d'un nouvel étudiant
     */
    public function create()
    {
        // Rediriger vers la page d'inscription qui est plus complète
        return redirect()->route('esbtp.inscriptions.create')
            ->with('info', 'Veuillez utiliser le formulaire d\'inscription pour ajouter un nouvel étudiant.');

        /* Code commenté - ancienne implémentation
        // Récupérer les données nécessaires pour le formulaire
        $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();
        $parents = \App\Models\ESBTPParent::orderBy('nom')->get();

        return view('esbtp.etudiants.create', compact('filieres', 'niveaux', 'annees', 'parents'));
        */
    }

    /**
     * Enregistrer un nouvel étudiant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Déléguer au contrôleur principal
        return app(\App\Http\Controllers\ESBTPEtudiantController::class)->store($request);
    }

    /**
     * Affiche le tableau de bord de l'étudiant.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            // Au lieu de rediriger, afficher une vue spéciale pour les étudiants sans profil
            return view('dashboard.etudiant_setup', [
                'user' => $user
            ]);
        }

        // Récupérer l'emploi du temps d'aujourd'hui
        try {
            $todayTimetable = Timetable::where('class_id', $student->classe_id)
                ->where('day', strtolower(date('l')))
                ->orderBy('start_time')
                ->with(['subject', 'class', 'teacher'])
                ->get();
        } catch (\Exception $e) {
            $todayTimetable = collect(); // Collection vide si erreur
        }

        // Récupérer les notifications récentes
        try {
            $recentNotifications = Notification::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere(function($q) {
                            $q->whereNull('user_id')
                            ->where(function($sq) {
                                $sq->where('type', 'info')
                                    ->orWhere('type', 'warning')
                                    ->orWhere('type', 'success');
                            });
                        });
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Compter les notifications non lues
            $unreadNotifications = Notification::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere(function($q) {
                            $q->whereNull('user_id')
                            ->where(function($sq) {
                                $sq->where('type', 'info')
                                    ->orWhere('type', 'warning')
                                    ->orWhere('type', 'success');
                            });
                        });
                })
                ->where('is_read', false)
                ->count();
        } catch (\Exception $e) {
            $recentNotifications = collect(); // Collection vide si erreur
            $unreadNotifications = 0;
        }

        // Récupérer les derniers examens et notes
        try {
            $recentExams = \App\Models\Exam::where('class_id', $student->classe_id)
                ->orderBy('date', 'desc')
                ->take(3)
                ->get();

            $recentGrades = Grade::where('student_id', $student->id)
                ->with(['exam', 'subject'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $recentExams = collect(); // Collection vide si erreur
            $recentGrades = collect(); // Collection vide si erreur
        }

        // Récupérer les absences de l'étudiant
        try {
            $absencesCount = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')
                ->count();

            // Alternativement, utiliser le modèle ESBTPAttendance si c'est celui utilisé pour les absences
            /*
            $absencesCount = \App\Models\ESBTPAttendance::where('etudiant_id', $student->id)
                ->whereIn('statut', ['absent', 'retard'])
                ->count();
            */
        } catch (\Exception $e) {
            $absencesCount = 0;
        }

        // Récupérer les bulletins récents de l'étudiant
        try {
            $bulletins = \App\Models\Bulletin::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();

            // Alternativement, utiliser le modèle ESBTPBulletin si c'est celui utilisé pour les bulletins
            /*
            $bulletins = \App\Models\ESBTPBulletin::where('etudiant_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();
            */
        } catch (\Exception $e) {
            $bulletins = collect(); // Collection vide si erreur
        }

        $matieres = ESBTPMatiere::orderBy('name')->get();

        return view('dashboard.etudiant', compact(
            'student',
            'todayTimetable',
            'recentNotifications',
            'unreadNotifications',
            'recentExams',
            'recentGrades',
            'absencesCount',
            'bulletins',
            'matieres'
        ));
    }

    /**
     * Affiche le profil de l'étudiant.
     */
    public function profile()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('esbtp.etudiant.dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        return view('esbtp.etudiant.profile', compact('student'));
    }

    /**
     * Affiche les notes de l'étudiant.
     */
    public function notes()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('esbtp.etudiant.dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer toutes les notes de l'étudiant
        try {
            $grades = Grade::where('student_id', $student->id)
                ->with(['exam', 'subject'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Regrouper les notes par matière pour calculer les moyennes
            $subjectGrades = $grades->groupBy('subject_id');
            $averages = [];

            foreach ($subjectGrades as $subjectId => $gradesList) {
                $total = 0;
                $coefficients = 0;

                foreach ($gradesList as $grade) {
                    $total += $grade->grade * ($grade->exam->coefficient ?? 1);
                    $coefficients += ($grade->exam->coefficient ?? 1);
                }

                $averages[$subjectId] = [
                    'subject' => $gradesList->first()->subject,
                    'average' => $coefficients > 0 ? $total / $coefficients : 0,
                    'grades_count' => $gradesList->count()
                ];
            }
        } catch (\Exception $e) {
            $grades = collect(); // Collection vide si erreur
            $averages = [];
        }

        return view('esbtp.etudiant.notes', compact('student', 'grades', 'averages'));
    }

    /**
     * Affiche l'emploi du temps de l'étudiant.
     */
    public function emploiTemps()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('esbtp.etudiant.dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer l'emploi du temps complet
        try {
            $timetable = Timetable::where('class_id', $student->classe_id)
                ->orderBy('day')
                ->orderBy('start_time')
                ->with(['subject', 'class', 'teacher'])
                ->get()
                ->groupBy('day');
        } catch (\Exception $e) {
            $timetable = collect(); // Collection vide si erreur
        }

        // Jours de la semaine en français
        $days = [
            'monday' => 'Lundi',
            'tuesday' => 'Mardi',
            'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi',
            'friday' => 'Vendredi',
            'saturday' => 'Samedi',
            'sunday' => 'Dimanche'
        ];

        return view('esbtp.etudiant.emploi-temps', compact('student', 'timetable', 'days'));
    }

    /**
     * Affiche les bulletins de l'étudiant.
     */
    public function bulletins()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('esbtp.etudiant.dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer tous les bulletins de l'étudiant
        try {
            $bulletins = \App\Models\Bulletin::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            $bulletins = collect(); // Collection vide si erreur
        }

        return view('esbtp.etudiant.bulletins', compact('student', 'bulletins'));
    }

    /**
     * Affiche un bulletin spécifique.
     */
    public function showBulletin($id)
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('esbtp.etudiant.dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer le bulletin demandé
        try {
            $bulletin = \App\Models\Bulletin::where('id', $id)
                ->where('student_id', $student->id)
                ->firstOrFail();

            $grades = \App\Models\BulletinGrade::where('bulletin_id', $bulletin->id)
                ->with('subject')
                ->get();
        } catch (\Exception $e) {
            return redirect()->route('esbtp.etudiant.bulletins')
                ->with('error', 'Bulletin non trouvé ou vous n\'êtes pas autorisé à y accéder.');
        }

        return view('esbtp.etudiant.bulletin-show', compact('student', 'bulletin', 'grades'));
    }

    /**
     * Affiche les absences de l'étudiant.
     */
    public function absences()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('esbtp.etudiant.dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer toutes les absences de l'étudiant
        try {
            $absences = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')
                ->with(['subject', 'justification'])
                ->orderBy('date', 'desc')
                ->paginate(15);

            // Calculer les statistiques d'absences
            $totalAbsences = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')
                ->count();

            $justifiedAbsences = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')
                ->whereHas('justification', function($query) {
                    $query->where('status', 'approved');
                })
                ->count();

            $pendingJustifications = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')
                ->whereHas('justification', function($query) {
                    $query->where('status', 'pending');
                })
                ->count();
        } catch (\Exception $e) {
            $absences = collect(); // Collection vide si erreur
            $totalAbsences = 0;
            $justifiedAbsences = 0;
            $pendingJustifications = 0;
        }

        return view('esbtp.etudiant.absences', compact(
            'student',
            'absences',
            'totalAbsences',
            'justifiedAbsences',
            'pendingJustifications'
        ));
    }
}
