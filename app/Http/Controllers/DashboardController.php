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
use App\Models\ESBTPSystemSetting;
use App\Models\ESBTPEtablissement;
use App\Domain\Students\StudentCountService;
use App\Services\PermissionRegistry;
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

        // Lot 9 — Si l'utilisateur n'a QUE des rôles custom (créés via UI Lot 8),
        // pas de dashboard hard-codé : router vers le dashboard widget-based.
        // Les rôles système (superAdmin, secretaire...) gardent leur dashboard
        // dédié pour préserver l'UX existante.
        if ($this->userHasOnlyCustomRoles($user)) {
            return redirect()->route('dashboard.widgets.index');
        }

        // SuperAdmin/Admin en premier — ils ont TOUTES les permissions,
        // donc tout check permission-based matcherait. On utilise hasRole()
        // car c'est du routing UI par rôle (pattern validé post-overhaul).
        if ($user->hasRole(['superAdmin', 'admin'])) {
            return $this->superAdminDashboard();
        }

        // Service technique — a aussi toutes les permissions, donc hasRole requis
        if ($user->hasRole('serviceTechnique')) {
            return $this->serviceTechniqueDashboard();
        }

        // Secrétaire
        if ($user->can('identity.school_manager')) {
            return $this->secretaireDashboard();
        }

        // Caissier
        if ($user->can('module.caisse.access')) {
            return $this->caissierDashboard();
        }

        // Comptable
        if ($user->can('comptabilite.access')) {
            return $this->comptableDashboard();
        }

        // Coordinateur
        if ($user->can('identity.coordinate')) {
            return $this->coordinateurDashboard();
        }

        // Enseignant
        if ($user->can('identity.teach')) {
            return redirect()->route('teacher.dashboard');
        }

        // Étudiant
        if ($user->can('identity.student')) {
            return $this->etudiantDashboard();
        }

        // Fallback — tableau de bord générique
        return view('dashboard.index', compact('user'));
    }

    /**
     * Lot 9 — Détecte si l'utilisateur n'a que des rôles custom (créés via UI Lot 8).
     *
     * Si oui, on n'a pas de dashboard hard-codé pour eux : router vers le
     * dashboard widget-based (config/dashboard_widgets.php).
     *
     * Retourne false si l'utilisateur a au moins un rôle système (gestion legacy)
     * ou aucun rôle (fallback générique).
     */
    private function userHasOnlyCustomRoles($user): bool
    {
        $roleNames = $user->roles->pluck('name')->all();
        if (empty($roleNames)) {
            return false;
        }

        $registry = app(PermissionRegistry::class);
        // Si la méthode roleIsCustom n'existe pas (Lot 8 pas encore mergé),
        // on revient au comportement legacy (false → continue le routing standard).
        if (! method_exists($registry, 'roleIsCustom')) {
            return false;
        }

        foreach ($roleNames as $name) {
            if (! $registry->roleIsCustom($name)) {
                return false;
            }
        }

        return true;
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

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // Inscriptions en attente - inclut les actives dont le workflow n'est pas finalisé (etudiant_cree)
        $pendingQuery = \App\Models\ESBTPInscription::where(function ($q) {
            $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                $subQ->where('status', 'active')
                    ->where(function ($wq) {
                        $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                            ->orWhereNull('workflow_step');
                    });
            });
        });
        if ($anneeEnCours) {
            $pendingQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }
        $data['pendingInscriptionsCount'] = $pendingQuery->count();

        $data['pendingCurrentYearInscriptionsCount'] = 0;
        $data['pendingCurrentYearInscriptionsByStep'] = [];
        if ($anneeEnCours) {
            $pendingCurrentYearQuery = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                ->where(function($query) {
                    $query->whereIn('status', ['en_attente', 'pending'])
                        ->orWhere(function($subQuery) {
                            $subQuery->where('status', 'active')
                                ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                        });
                });

            $data['pendingCurrentYearInscriptionsCount'] = (clone $pendingCurrentYearQuery)->count();
            $data['pendingCurrentYearInscriptionsByStep'] = [
                'prospect' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'prospect')->count(),
                'documents_complets' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'documents_complets')->count(),
                'en_validation' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'en_validation')->count(),
            ];
        }

        // Étudiants — Service centralisé (distinct etudiant_id, inscriptions actives+validées année courante)
        $studentCounts = app(StudentCountService::class)->counts();
        $data['totalStudents'] = $studentCounts['inscrits_annee_courante'];
        $data['totalStudentsBase'] = $studentCounts['total_base'];
        $data['anneeLabel'] = $studentCounts['annee_courante_label'];

        if ($anneeEnCours) {
            $data['recentStudents'] = ESBTPInscription::with(['etudiant'])
                ->where('annee_universitaire_id', $anneeEnCours->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function($inscription) {
                    return $inscription->etudiant;
                });
        } else {
            $data['recentStudents'] = ESBTPEtudiant::orderBy('created_at', 'desc')->take(5)->get();
        }

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

        // Classes (pas de filtrage par année)
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

        // Examens (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['totalExamens'] = ESBTPEvaluation::whereHas('classe', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id);
                })->count();
                $data['recentExamens'] = ESBTPEvaluation::with(['classe', 'matiere'])
                    ->whereHas('classe', function($q) use ($anneeEnCours) {
                        $q->where('annee_universitaire_id', $anneeEnCours->id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            } else {
                $data['totalExamens'] = ESBTPEvaluation::count();
                $data['recentExamens'] = ESBTPEvaluation::with(['classe', 'matiere'])
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            }
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

        // Présences (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['totalPresences'] = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->count();
                $data['todayAttendances'] = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->whereDate('date', today())->count();
            } else {
                $data['totalPresences'] = ESBTPAttendance::count();
                $data['todayAttendances'] = ESBTPAttendance::whereDate('date', today())->count();
            }
        } catch (\Exception $e) {
            $data['totalPresences'] = 0;
            $data['todayAttendances'] = 0;
        }

        // Emplois du temps (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['totalEmploiTemps'] = ESBTPEmploiTemps::where('annee_universitaire_id', $anneeEnCours->id)->count();
                $data['activeEmploiTemps'] = ESBTPEmploiTemps::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('is_active', true)->count();
            } else {
                $data['totalEmploiTemps'] = ESBTPEmploiTemps::count();
                $data['activeEmploiTemps'] = ESBTPEmploiTemps::where('is_active', true)->count();
            }
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

        // Inscriptions récentes (vraies données) (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere',
                    'etudiant.classe.filiere'
                ])
                    ->where('annee_universitaire_id', $anneeEnCours->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere',
                    'etudiant.classe.filiere'
                ])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        } catch (\Exception $e) {
            $data['recentInscriptions'] = collect();
        }

        // Examens à venir (vraies données) (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['upcomingExams'] = ESBTPEvaluation::with(['matiere', 'classe'])
                    ->whereHas('classe', function($q) use ($anneeEnCours) {
                        $q->where('annee_universitaire_id', $anneeEnCours->id);
                    })
                    ->where('date_evaluation', '>=', now())
                    ->orderBy('date_evaluation', 'asc')
                    ->limit(5)
                    ->get();
            } else {
                $data['upcomingExams'] = ESBTPEvaluation::with(['matiere', 'classe'])
                    ->where('date_evaluation', '>=', now())
                    ->orderBy('date_evaluation', 'asc')
                    ->limit(5)
                    ->get();
            }
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

        // Statistiques par filière avec couleurs pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $filiereStatsRaw = ESBTPFiliere::withCount(['inscriptions' => function($query) use ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }])->get();
        } else {
            $filiereStatsRaw = ESBTPFiliere::withCount('inscriptions')->get();
        }
        $colors = ['#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#5e91de', '#f97316', '#06b6d4', '#84cc16', '#f43f5e', '#0453cb'];

        $data['filiereStats'] = $filiereStatsRaw->map(function($filiere, $index) use ($colors) {
            return [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'students' => $filiere->inscriptions_count,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // Données mensuelles pour les graphiques (filtré par année en cours)
        $data['monthlyStats'] = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            if ($anneeEnCours) {
                // Courbe verte: inscriptions VALIDÉES dans le mois (date_validation)
                $studentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES dans le mois (created_at)
                $inscriptionsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('created_at', '<=', $endOfMonth)  // Créées avant ou pendant ce mois
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            } else {
                // Sans année en cours: courbe verte = inscriptions VALIDÉES (date_validation)
                $studentsCount = ESBTPInscription::where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES
                $inscriptionsCount = ESBTPInscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('created_at', '<=', $endOfMonth)
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            }

            $data['monthlyStats'][] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => $studentsCount,
                'inscriptions' => $inscriptionsCount,
                'pending_payments' => $pendingPaymentsCount,
            ];
        }

        // Inscriptions par mois pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $data['inscriptionsByMonth'] = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('annee_universitaire_id', $anneeEnCours->id)
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        } else {
            $data['inscriptionsByMonth'] = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        // Statistiques de présence (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $totalPresent = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->where('status', 'present')->whereDate('date', today())->count();

                $totalAbsent = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->where('status', 'absent')->whereDate('date', today())->count();
            } else {
                $totalPresent = ESBTPAttendance::where('status', 'present')->whereDate('date', today())->count();
                $totalAbsent = ESBTPAttendance::where('status', 'absent')->whereDate('date', today())->count();
            }

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

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // Inscriptions en attente - inclut les actives dont le workflow n'est pas finalisé
        $pendingSecQuery = \App\Models\ESBTPInscription::where(function ($q) {
            $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                $subQ->where('status', 'active')
                    ->where(function ($wq) {
                        $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                            ->orWhereNull('workflow_step');
                    });
            });
        });
        if ($anneeEnCours) {
            $pendingSecQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }
        $data['pendingInscriptionsCount'] = $pendingSecQuery->count();

        $data['pendingCurrentYearInscriptionsCount'] = 0;
        $data['pendingCurrentYearInscriptionsByStep'] = [];
        if ($anneeEnCours) {
            $pendingCurrentYearQuery = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                ->where(function($query) {
                    $query->whereIn('status', ['en_attente', 'pending'])
                        ->orWhere(function($subQuery) {
                            $subQuery->where('status', 'active')
                                ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                        });
                });

            $data['pendingCurrentYearInscriptionsCount'] = (clone $pendingCurrentYearQuery)->count();
            $data['pendingCurrentYearInscriptionsByStep'] = [
                'prospect' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'prospect')->count(),
                'documents_complets' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'documents_complets')->count(),
                'en_validation' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'en_validation')->count(),
            ];
        }

        // Étudiants - Les secrétaires peuvent voir et créer des étudiants
        try {
            $studentCountsSec = app(StudentCountService::class)->counts();
            $data['totalStudents'] = $studentCountsSec['inscrits_annee_courante'];
            $data['totalStudentsBase'] = $studentCountsSec['total_base'];
            $data['anneeLabel'] = $studentCountsSec['annee_courante_label'];
            $data['recentStudents'] = ESBTPEtudiant::with(['inscriptions' => function($q) {
                    $q->with(['classe', 'anneeUniversitaire'])
                        ->orderBy('created_at', 'desc');
                }])
                ->whereHas('inscriptions')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['totalStudents'] = 0;
            $data['totalStudentsBase'] = 0;
            $data['anneeLabel'] = null;
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
            $data['totalBulletins'] = ESBTPBulletin::count();
        } catch (\Exception $e) {
            $data['totalBulletins'] = 0;
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
     * Tableau de bord pour les comptables avec les données financières.
     */
    private function comptableDashboard()
    {
        $user = Auth::user();
        $data = ['user' => $user];

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // --- KPIs financiers ---
        try {
            $paiementsQuery = \App\Models\ESBTPPaiement::query()->whereNull('deleted_at');
            if ($anneeEnCours) {
                // Filtrer via la relation inscription pour garantir la cohérence
                $paiementsQuery->whereHas('inscription', function ($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id);
                });
            }

            // Montants par statut
            $data['totalEncaisse'] = (clone $paiementsQuery)->where('status', 'validé')->sum('montant');
            $data['totalEnAttente'] = (clone $paiementsQuery)->where('status', 'en_attente')->sum('montant');
            $data['paiementsEnAttenteCount'] = (clone $paiementsQuery)->where('status', 'en_attente')->count();

            // Total frais dus (souscriptions actives de l'année courante)
            $subscriptionsQuery = \App\Models\ESBTPFraisSubscription::query()->where('is_active', true);
            if ($anneeEnCours) {
                $subscriptionsQuery->whereHas('inscription', function ($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id);
                });
            }
            $data['totalFraisDus'] = $subscriptionsQuery->sum('amount');

            // Taux de recouvrement (plafonné à 100%)
            $data['tauxRecouvrement'] = $data['totalFraisDus'] > 0
                ? min(round(($data['totalEncaisse'] / $data['totalFraisDus']) * 100, 1), 100)
                : 0;

            $data['montantRestant'] = max(0, $data['totalFraisDus'] - $data['totalEncaisse']);

            // Paiements du mois en cours
            $data['encaisseMois'] = (clone $paiementsQuery)
                ->where('status', 'validé')
                ->whereMonth('date_paiement', now()->month)
                ->whereYear('date_paiement', now()->year)
                ->sum('montant');

        } catch (\Exception $e) {
            $data['totalEncaisse'] = 0;
            $data['totalEnAttente'] = 0;
            $data['paiementsEnAttenteCount'] = 0;
            $data['totalFraisDus'] = 0;
            $data['tauxRecouvrement'] = 0;
            $data['montantRestant'] = 0;
            $data['encaisseMois'] = 0;
        }

        // --- Paiements récents ---
        try {
            $recentQuery = \App\Models\ESBTPPaiement::with(['etudiant', 'inscription.classe'])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc');
            if ($anneeEnCours) {
                $recentQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['recentPaiements'] = $recentQuery->take(8)->get();
        } catch (\Exception $e) {
            $data['recentPaiements'] = collect();
        }

        // --- Étudiants avec impayés (top 5 plus gros soldes) ---
        try {
            if ($anneeEnCours) {
                $data['topImpayes'] = DB::table('esbtp_frais_subscriptions as fs')
                    ->join('esbtp_inscriptions as i', 'fs.inscription_id', '=', 'i.id')
                    ->join('esbtp_etudiants as e', 'i.etudiant_id', '=', 'e.id')
                    ->leftJoin(DB::raw('(SELECT inscription_id, frais_category_id, SUM(montant) as total_paye FROM esbtp_paiements WHERE status = \'validé\' AND deleted_at IS NULL GROUP BY inscription_id, frais_category_id) as p'), function ($join) {
                        $join->on('p.inscription_id', '=', 'i.id')
                             ->on('p.frais_category_id', '=', 'fs.frais_category_id');
                    })
                    ->where('i.annee_universitaire_id', $anneeEnCours->id)
                    ->where('i.status', 'active')
                    ->whereNull('e.deleted_at')
                    ->select(
                        'e.id as etudiant_id',
                        'e.nom',
                        'e.prenoms',
                        'e.matricule',
                        DB::raw('SUM(fs.amount) as total_du'),
                        DB::raw('SUM(COALESCE(p.total_paye, 0)) as total_paye'),
                        DB::raw('SUM(fs.amount) - SUM(COALESCE(p.total_paye, 0)) as solde_restant')
                    )
                    ->groupBy('e.id', 'e.nom', 'e.prenoms', 'e.matricule')
                    ->havingRaw('solde_restant > 0')
                    ->orderByDesc('solde_restant')
                    ->limit(5)
                    ->get();
            } else {
                $data['topImpayes'] = collect();
            }
        } catch (\Exception $e) {
            $data['topImpayes'] = collect();
        }

        // --- Répartition par mode de paiement (pour le mois) ---
        try {
            $modesQuery = \App\Models\ESBTPPaiement::query()
                ->whereNull('deleted_at')
                ->where('status', 'validé')
                ->whereMonth('date_paiement', now()->month)
                ->whereYear('date_paiement', now()->year);
            if ($anneeEnCours) {
                $modesQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['paiementsParMode'] = $modesQuery
                ->select('mode_paiement', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant) as total'))
                ->groupBy('mode_paiement')
                ->get();
        } catch (\Exception $e) {
            $data['paiementsParMode'] = collect();
        }

        return view('dashboard.comptable', $data);
    }

    /**
     * Tableau de bord pour les caissiers.
     */
    private function caissierDashboard()
    {
        $user = Auth::user();
        $today = now()->startOfDay();
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        try {
            // KPIs — scoped to current caissier
            $paiementsAujourdhuiCount = \App\Models\ESBTPPaiement::whereDate('created_at', $today)
                ->where('created_by', $user->id)
                ->count();

            $montantEncaisseAujourdhui = \App\Models\ESBTPPaiement::whereDate('created_at', $today)
                ->where('created_by', $user->id)
                ->where('status', 'validé')
                ->sum('montant');

            $preInscriptionsAujourdhui = \App\Models\ESBTPInscription::whereDate('created_at', $today)
                ->where('workflow_step', 'prospect')
                ->where('created_by', $user->id)
                ->count();

            $preInscriptionsEnAttente = \App\Models\ESBTPInscription::where('workflow_step', 'prospect')
                ->where('status', 'en_attente')
                ->count();

            // Recent payments (last 10 by this caissier)
            $paiementsRecents = \App\Models\ESBTPPaiement::with(['etudiant', 'inscription'])
                ->where('created_by', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } catch (\Exception $e) {
            $paiementsAujourdhuiCount = 0;
            $montantEncaisseAujourdhui = 0;
            $preInscriptionsAujourdhui = 0;
            $preInscriptionsEnAttente = 0;
            $paiementsRecents = collect();
        }

        return view('dashboard.caissier', compact(
            'user',
            'anneeEnCours',
            'paiementsAujourdhuiCount',
            'montantEncaisseAujourdhui',
            'preInscriptionsAujourdhui',
            'preInscriptionsEnAttente',
            'paiementsRecents'
        ));
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

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // Statistiques accessibles aux coordinateurs
        try {
            // Étudiants — Service centralisé (distinct etudiant_id, inscriptions actives+validées année courante)
            $studentCounts = app(StudentCountService::class)->counts();
            $data['totalStudents'] = $studentCounts['inscrits_annee_courante'];
            $data['totalStudentsBase'] = $studentCounts['total_base'];
            $data['anneeLabel'] = $studentCounts['annee_courante_label'];

            if ($anneeEnCours) {
                $data['recentStudents'] = ESBTPInscription::with(['etudiant', 'classe.filiere'])
                    ->where('annee_universitaire_id', $anneeEnCours->id)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function($inscription) {
                        return $inscription->etudiant;
                    });
            } else {
                $data['recentStudents'] = ESBTPEtudiant::with(['classe.filiere'])
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            }
        } catch (\Exception $e) {
            $data['totalStudents'] = 0;
            $data['totalStudentsBase'] = 0;
            $data['anneeLabel'] = null;
            $data['recentStudents'] = collect();
        }

        // Classes - Coordinateurs supervisent les classes (pas de filtrage par année)
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

        // Évaluations - Coordinateurs peuvent voir les évaluations (filtré par année en cours)
        try {
            $evalQuery = ESBTPEvaluation::query();
            if ($anneeEnCours) {
                $evalQuery->whereHas('classe', fn($q) => $q->where('annee_universitaire_id', $anneeEnCours->id));
            }
            $data['totalExamens'] = (clone $evalQuery)->count();
            $data['recentExamens'] = (clone $evalQuery)
                ->with(['classe', 'matiere'])
                ->withCount('notes')
                ->orderBy('date_evaluation', 'desc')
                ->take(5)
                ->get();

            // Évaluations passées sans notes (alerte pour le coordinateur)
            $data['evaluationsSansNotes'] = (clone $evalQuery)
                ->with(['classe', 'matiere'])
                ->whereDate('date_evaluation', '<', today())
                ->whereDoesntHave('notes')
                ->orderBy('date_evaluation', 'desc')
                ->take(5)
                ->get();
            $data['evaluationsSansNotesCount'] = (clone $evalQuery)
                ->whereDate('date_evaluation', '<', today())
                ->whereDoesntHave('notes')
                ->count();
        } catch (\Exception $e) {
            $data['totalExamens'] = 0;
            $data['recentExamens'] = collect();
            $data['evaluationsSansNotes'] = collect();
            $data['evaluationsSansNotesCount'] = 0;
        }

        // Emplois du temps - Coordinateurs gèrent la planification (filtré par année en cours)
        try {
            $edtQuery = ESBTPEmploiTemps::query();
            if ($anneeEnCours) {
                $edtQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['totalEmploiTemps'] = (clone $edtQuery)->count();
            $data['activeEmploiTemps'] = (clone $edtQuery)
                ->where('is_active', true)
                ->whereDate('date_debut', '<=', today())
                ->whereDate('date_fin', '>=', today())
                ->count();
            $data['expiredEmploiTemps'] = (clone $edtQuery)
                ->whereDate('date_fin', '<', today())
                ->count();

            // Classes sans emploi du temps pour l'année courante
            $classesAvecEdt = (clone $edtQuery)->pluck('classe_id')->unique();
            $data['classesWithoutTimetable'] = ESBTPClasse::where('is_active', true)
                ->whereNotIn('id', $classesAvecEdt)
                ->count();
        } catch (\Exception $e) {
            $data['totalEmploiTemps'] = 0;
            $data['activeEmploiTemps'] = 0;
            $data['expiredEmploiTemps'] = 0;
            $data['classesWithoutTimetable'] = 0;
        }

        // Présences - Coordinateurs suivent les présences (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['todayAttendances'] = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->whereDate('date', today())->count();

                $totalPresent = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->where('status', 'present')->whereDate('date', today())->count();

                $totalAbsent = ESBTPAttendance::whereHas('etudiant.inscriptions', function($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active');
                })->where('status', 'absent')->whereDate('date', today())->count();
            } else {
                $data['todayAttendances'] = ESBTPAttendance::whereDate('date', today())->count();
                $totalPresent = ESBTPAttendance::where('status', 'present')->whereDate('date', today())->count();
                $totalAbsent = ESBTPAttendance::where('status', 'absent')->whereDate('date', today())->count();
            }

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

        // Messages pour coordinateurs (fix: wrapper parent pour les OR)
        try {
            $data['recentMessages'] = Message::with('sender')
                ->whereNull('parent_id')
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->where('recipient_type', 'coordinateurs')
                          ->whereNull('recipient_group');
                    })
                    ->orWhere(function($q) {
                        $q->where('recipient_type', 'all')
                          ->whereNull('recipient_group');
                    })
                    ->orWhere('recipient_id', Auth::id());
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentMessages'] = collect();
        }

        // Inscriptions en attente (filtré par année en cours) - inclut workflow non finalisé
        try {
            $pendingCoordQuery = ESBTPInscription::where(function ($q) {
                $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                    $subQ->where('status', 'active')
                        ->where(function ($wq) {
                            $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                                ->orWhereNull('workflow_step');
                        });
                });
            });
            if ($anneeEnCours) {
                $data['pendingInscriptionsCount'] = (clone $pendingCoordQuery)
                    ->where('annee_universitaire_id', $anneeEnCours->id)->count();
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere'
                ])
                    ->where('annee_universitaire_id', $anneeEnCours->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                $data['pendingInscriptionsCount'] = $pendingCoordQuery->count();
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere'
                ])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
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
     * Données JSON du dashboard coordinateur (AJAX refresh)
     */
    public function coordinateurDashboardData()
    {
        $user = Auth::user();
        if (!$user || !$user->can('identity.coordinate')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Réutiliser la même logique que coordinateurDashboard
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Service centralisé : 2 valeurs distinctes (cf. memory_studentcount)
        $studentCounts = app(StudentCountService::class)->counts();
        $totalStudents = $studentCounts['inscrits_annee_courante'];
        $totalStudentsBase = $studentCounts['total_base'];
        $anneeLabel = $studentCounts['annee_courante_label'];

        $pendingInscriptionsCount = 0;
        $totalClasses = 0;
        $totalTeachers = 0;
        $totalExamens = 0;
        $evaluationsSansNotesCount = 0;
        $totalEmploiTemps = 0;
        $activeEmploiTemps = 0;
        $expiredEmploiTemps = 0;
        $classesWithoutTimetable = 0;
        $attendanceStats = ['total_present' => 0, 'total_absent' => 0, 'attendance_rate' => 0];

        try {
            $totalClasses = ESBTPClasse::count();
            $totalTeachers = ESBTPTeacher::count();
        } catch (\Exception $e) {
            // Keep defaults
        }

        try {
            if ($anneeEnCours) {
                // $totalStudents déjà calculé par StudentCountService ci-dessus
                $pendingInscriptionsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where(function ($q) {
                        $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                            $subQ->where('status', 'active')
                                ->where(function ($wq) {
                                    $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                                        ->orWhereNull('workflow_step');
                                });
                        });
                    })->count();

                $evalQuery = ESBTPEvaluation::whereHas('classe', fn($q) => $q->where('annee_universitaire_id', $anneeEnCours->id));
                $totalExamens = (clone $evalQuery)->count();
                $evaluationsSansNotesCount = (clone $evalQuery)
                    ->whereDate('date_evaluation', '<', today())
                    ->whereDoesntHave('notes')->count();

                $edtQuery = ESBTPEmploiTemps::where('annee_universitaire_id', $anneeEnCours->id);
                $totalEmploiTemps = (clone $edtQuery)->count();
                $activeEmploiTemps = (clone $edtQuery)->where('is_active', true)
                    ->whereDate('date_debut', '<=', today())
                    ->whereDate('date_fin', '>=', today())->count();
                $expiredEmploiTemps = (clone $edtQuery)->whereDate('date_fin', '<', today())->count();
                $classesAvecEdt = (clone $edtQuery)->pluck('classe_id')->unique();
                $classesWithoutTimetable = ESBTPClasse::where('is_active', true)
                    ->whereNotIn('id', $classesAvecEdt)->count();

                $totalPresent = ESBTPAttendance::whereHas('etudiant.inscriptions', fn($q) => $q->where('annee_universitaire_id', $anneeEnCours->id)->where('status', 'active'))
                    ->where('status', 'present')->whereDate('date', today())->count();
                $totalAbsent = ESBTPAttendance::whereHas('etudiant.inscriptions', fn($q) => $q->where('annee_universitaire_id', $anneeEnCours->id)->where('status', 'active'))
                    ->where('status', 'absent')->whereDate('date', today())->count();
                $attendanceStats = [
                    'total_present' => $totalPresent,
                    'total_absent' => $totalAbsent,
                    'attendance_rate' => $totalPresent + $totalAbsent > 0 ? round(($totalPresent / ($totalPresent + $totalAbsent)) * 100, 1) : 0,
                ];
            }
        } catch (\Exception $e) {
            // Keep defaults
        }

        return response()->json([
            'totalStudents' => $totalStudents,
            'totalStudentsBase' => $totalStudentsBase,
            'anneeLabel' => $anneeLabel,
            'pendingInscriptionsCount' => $pendingInscriptionsCount,
            'totalClasses' => $totalClasses,
            'totalTeachers' => $totalTeachers,
            'totalExamens' => $totalExamens,
            'evaluationsSansNotesCount' => $evaluationsSansNotesCount,
            'totalEmploiTemps' => $totalEmploiTemps,
            'activeEmploiTemps' => $activeEmploiTemps,
            'expiredEmploiTemps' => $expiredEmploiTemps,
            'classesWithoutTimetable' => $classesWithoutTimetable,
            'attendanceStats' => $attendanceStats,
        ]);
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

        $data['anneeEnCours'] = ESBTPAnneeUniversitaire::where('is_current', true)->first();

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
        if (!$user->can('admin.access')) {
            abort(403, 'Accès non autorisé');
        }

        // Statistiques principales — Service centralisé pour distinguer année courante vs base totale
        $studentCounts = app(StudentCountService::class)->counts();
        $totalStudents = $studentCounts['inscrits_annee_courante'];
        $totalStudentsBase = $studentCounts['total_base'];
        $anneeLabel = $studentCounts['annee_courante_label'];
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

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Statistiques par filière avec couleurs pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $filiereStatsRaw = ESBTPFiliere::withCount(['inscriptions' => function($query) use ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }])->get();
        } else {
            $filiereStatsRaw = ESBTPFiliere::withCount('inscriptions')->get();
        }
        $colors = ['#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#5e91de', '#f97316', '#06b6d4', '#84cc16', '#f43f5e', '#0453cb'];

        $filiereStats = $filiereStatsRaw->map(function($filiere, $index) use ($colors) {
            return [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'students' => $filiere->inscriptions_count,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // Données mensuelles pour les graphiques (filtré par année en cours)
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            if ($anneeEnCours) {
                // Courbe verte: inscriptions VALIDÉES dans le mois (date_validation)
                $studentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES dans le mois (created_at)
                $inscriptionsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('created_at', '<=', $endOfMonth)  // Créées avant ou pendant ce mois
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            } else {
                // Sans année en cours: courbe verte = inscriptions VALIDÉES (date_validation)
                $studentsCount = ESBTPInscription::where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES
                $inscriptionsCount = ESBTPInscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('created_at', '<=', $endOfMonth)
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            }

            $monthlyStats[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => $studentsCount,
                'inscriptions' => $inscriptionsCount,
                'pending_payments' => $pendingPaymentsCount,
            ];
        }

        // Inscriptions par mois pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $inscriptionsByMonth = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('annee_universitaire_id', $anneeEnCours->id)
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        } else {
            $inscriptionsByMonth = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        return view('dashboard.superadmin', compact(
            'totalStudents',
            'totalStudentsBase',
            'anneeLabel',
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

        if (!$user->can('identity.school_manager')) {
            abort(403, 'Accès non autorisé');
        }

        return $this->secretaireDashboard();
    }

    /**
     * Dashboard Étudiant
     */
    public function etudiant()
    {
        $user = Auth::user();

        if (!$user->can('identity.student')) {
            abort(403, 'Accès non autorisé');
        }
        return $this->etudiantDashboard();
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
     * Dashboard Service Technique
     */
    private function serviceTechniqueDashboard()
    {
        $user = Auth::user();

        // Vérifier l'accès
        if (!$user->can('module.technical_support.access')) {
            abort(403, 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting');
        }

        // Récupérer tous les établissements (simule multi-tenant via git branches)
        $etablissements = collect([
            (object)[
                'id' => 1,
                'nom' => 'École Actuelle',
                'branch' => 'presentation', // Current branch
                'status' => 'active',
                'created_at' => Carbon::now()->subMonths(6)
            ]
        ]);

        // Statistiques globales de l'établissement actuel
        $studentCountsST = app(StudentCountService::class)->counts();
        $stats = [
            'total_users' => User::count(),
            'total_students' => $studentCountsST['inscrits_annee_courante'], // = inscrits année courante
            'total_students_base' => $studentCountsST['total_base'],          // = total base DB
            'total_teachers' => User::whereHas('roles', function($query) {
                $query->whereIn('name', ['enseignant', 'teacher']);
            })->count(),
            'total_classes' => ESBTPClasse::count(),
            'total_inscriptions_year' => ESBTPInscription::whereHas('anneeUniversitaire', function($query) {
                $query->where('is_current', true);
            })->count(),
        ];

        // Configuration paywall actuelle
        $paywallConfig = [
            'is_active' => ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
            'plan_name' => ESBTPSystemSetting::getValue('paywall_plan_name', 'Non configuré'),
            'plan_price' => ESBTPSystemSetting::getValue('paywall_plan_price', 0),
        ];

        // Statut paywall
        $paywallStatus = $this->checkPaywallStatusForDashboard($paywallConfig, $stats);

        // Activité récente
        $recentActivity = [
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'new_students_this_month' => ESBTPEtudiant::whereMonth('created_at', now()->month)->count(),
            'total_active_users' => User::where('created_at', '>=', now()->subDays(30))->count()
        ];

        // Codes d'urgence actifs
        $activeCodes = collect();
        $allSettings = ESBTPSystemSetting::where('key', 'LIKE', 'emergency_code_%')->get();
        foreach ($allSettings as $setting) {
            $codeData = json_decode($setting->value, true);
            if ($codeData && !$codeData['used'] && time() <= $codeData['expires_at']) {
                $activeCodes->push((object)[
                    'code' => str_replace('emergency_code_', '', $setting->key),
                    'expires_at' => Carbon::createFromTimestamp($codeData['expires_at']),
                    'created_by' => $codeData['created_by']
                ]);
            }
        }

        return view('dashboard.service-technique', compact(
            'etablissements',
            'stats',
            'paywallConfig',
            'paywallStatus',
            'recentActivity',
            'activeCodes'
        ));
    }

    /**
     * Vérifier le statut paywall pour le dashboard
     */
    private function checkPaywallStatusForDashboard($config, $stats)
    {
        $status = [
            'is_blocked' => false,
            'is_warning' => false,
            'message' => 'Système opérationnel',
            'level' => 'success'
        ];

        if (!$config['is_active']) {
            return $status;
        }

        // Vérifier expiration
        if ($config['subscription_end']) {
            $endDate = Carbon::parse($config['subscription_end']);
            $now = Carbon::now();

            if ($now->gt($endDate)) {
                $status['is_blocked'] = true;
                $status['message'] = 'Abonnement expiré';
                $status['level'] = 'danger';
            } elseif ($now->diffInDays($endDate) <= 7) {
                $status['is_warning'] = true;
                $status['message'] = 'Expiration proche (' . $now->diffInDays($endDate) . ' jours)';
                $status['level'] = 'warning';
            }
        }

        // Vérifier limites
        if ($stats['total_users'] >= $config['max_users'] * 0.9) {
            $status['is_warning'] = true;
            $status['message'] = 'Limite utilisateurs bientôt atteinte';
            $status['level'] = 'warning';
        }

        if ($stats['total_inscriptions_year'] >= $config['max_inscriptions_per_year'] * 0.9) {
            $status['is_warning'] = true;
            $status['message'] = 'Limite inscriptions bientôt atteinte';
            $status['level'] = 'warning';
        }

        return $status;
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
