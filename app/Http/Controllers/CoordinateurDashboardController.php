<?php

namespace App\Http\Controllers;

use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendance;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CoordinateurDashboardController extends Controller
{
    protected $notificationService;

    /**
     * Constructeur avec middleware de rôle
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware(['auth', 'role:coordinateur|secretaire|superAdmin|directeurEtudes']);
        $this->middleware('permission:module.presences.access')->only(['attendanceDashboard', 'attendanceDashboardData']);
        $this->notificationService = $notificationService;
    }

    /**
     * Affiche le tableau de bord des présences pour les coordinateurs
     */
    public function attendanceDashboard(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->get('date'))->startOfDay() : Carbon::today();
        $stats = $this->calculateAttendanceStats($date);

        // Nombre de notifications non lues
        $unreadNotifications = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        // Envoyer des notifications pour les alertes critiques — uniquement pour aujourd'hui
        // (ne pas spammer quand le coordinateur consulte une journée passée).
        if (!empty($stats['alerts']) && $date->isToday()) {
            $this->notificationService->notifyCoordinateurCriticalAlerts($stats['alerts'], $date);
        }

        return view('coordinateur.dashboard-attendance', [
            'stats'               => $stats,
            'unreadNotifications' => $unreadNotifications,
            'date'                => $date,
        ]);
    }

    /**
     * Endpoint AJAX : recharge les statistiques d'une journée sans recharger la page.
     */
    public function attendanceDashboardData(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->get('date'))->startOfDay() : Carbon::today();
        $stats = $this->calculateAttendanceStats($date);

        return response()->json([
            'kpis_html'     => view('coordinateur.partials._cad_kpis', ['stats' => $stats])->render(),
            'workflow_html' => view('coordinateur.partials._cad_workflow', ['stats' => $stats])->render(),
            'subjects_html' => view('coordinateur.partials._cad_subjects', ['stats' => $stats])->render(),
            'alerts_html'   => view('coordinateur.partials._cad_alerts', ['stats' => $stats])->render(),
            'date_label'    => $date->translatedFormat('l d F Y'),
        ]);
    }

    /**
     * Calcule les statistiques de présence pour le tableau de bord.
     *
     * Les séances du jour sont lues UNE fois, avec leurs émargements du jour et
     * le compte de leurs appels : les KPI, la répartition par matière et les
     * alertes en dérivent sans relancer de requête par séance. Les filtres de
     * jour sont des intervalles (whereBetween) et non des whereDate, qui
     * empêchent MySQL d'utiliser un index sur la colonne.
     */
    private function calculateAttendanceStats($date)
    {
        try {
            $jour = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            $seances = $this->seancesDuJour($jour);
            $workflows = $this->workflowsDesSeances($seances);
            $stats = array_merge($this->statsEmargements($seances), $this->statsAppels($jour));

            $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if (! $anneeUniversitaire) {
                // Sans année courante, `$anneeUniversitaire->id` levait un \Error
                // (pas une \Exception) : le catch plus bas ne l'attrapait pas et
                // la page répondait 500. On passe par le repli prévu.
                throw new \RuntimeException('Aucune année universitaire courante : statistiques de présence indisponibles.');
            }

            // PRÉSENCES / ABSENCES / RETARDS / TOTAL finaux du jour, en une requête
            // (finalOnly() : statuts fusionnés seulement ; un retard est une présence).
            $finaux = $this->presencesDuJour($jour, $anneeUniversitaire->id)->finalOnly()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("COALESCE(SUM(CASE WHEN statut IN ('present', 'retard') THEN 1 ELSE 0 END), 0) as presences")
                ->selectRaw("COALESCE(SUM(CASE WHEN statut = 'absent' THEN 1 ELSE 0 END), 0) as absences")
                ->selectRaw("COALESCE(SUM(CASE WHEN statut = 'retard' THEN 1 ELSE 0 END), 0) as retards")
                ->toBase()->first();
            $stats['presences_today'] = (int) $finaux->presences;
            $stats['absences_today'] = (int) $finaux->absences;
            $stats['retards_today'] = (int) $finaux->retards;
            $stats['total_calls_today'] = (int) $finaux->total;

            // Garder aussi students_present_today et students_total_today pour compatibilité
            $stats['students_present_today'] = $stats['presences_today'];
            $stats['students_total_today'] = $stats['total_calls_today'];

            $stats['student_attendance_rate'] = $stats['students_total_today'] > 0
                ? round(($stats['students_present_today'] / $stats['students_total_today']) * 100, 1)
                : 0;

            // Retards d'émargement (cours sans émargement enseignant complet)
            $stats['delays_today'] = max(0, $stats['scheduled_courses_today'] - $stats['teacher_attendances_today']);

            $stats['active_teachers_today'] = ESBTPTeacherAttendance::whereBetween('created_at', $jour)
                ->distinct('teacher_id')
                ->count();

            $stats['subjects_stats'] = $this->getSubjectStats($seances, $workflows, $jour);
            $stats['alerts'] = $this->getAttendanceAlerts($seances, $jour, $anneeUniversitaire);

            return $stats;

        } catch (\Exception $e) {
            \Log::error('Erreur calcul statistiques coordinateur: ' . $e->getMessage());

            // Retourner des statistiques par défaut en cas d'erreur
            return [
                'scheduled_courses_today' => 0,
                'teacher_attendances_today' => 0,
                'teacher_attendance_rate' => 0,
                'students_present_today' => 0,
                'students_total_today' => 0,
                'student_attendance_rate' => 0,
                'courses_completed_today' => 0,
                'active_teachers_today' => 0,
                'subjects_stats' => [],
                'alerts' => [],
                'roll_calls_completed_today' => 0,
                'delays_today' => 0,
                'courses_closed_today' => 0,
                'high_absence_classes' => 0
            ];
        }
    }

    /**
     * Les séances du jour, avec matière, classe, émargements DU JOUR (début/fin),
     * nombre d'appels d'étudiants et nombre d'émargements toutes dates.
     */
    private function seancesDuJour(array $jour)
    {
        return ESBTPSeanceCours::whereBetween('date_seance', $jour)
            ->with([
                'matiere', 'classe',
                'teacherAttendances' => fn ($q) => $q->whereBetween('date', $jour)->whereIn('type', ['start', 'end']),
            ])
            ->withCount(['attendances', 'teacherAttendances as emargements_toutes_dates'])
            ->get();
    }

    /**
     * Le workflow de chaque séance : le premier enregistré l'emporte, comme le
     * ->first() lancé auparavant séance par séance.
     */
    private function workflowsDesSeances($seances)
    {
        return \App\Models\ESBTPSessionWorkflow::whereIn('seance_cours_id', $seances->pluck('id'))
            ->orderBy('id')->get()->unique('seance_cours_id')->keyBy('seance_cours_id');
    }

    /**
     * Séances programmées et émargements (début, fin, complets) du jour.
     */
    private function statsEmargements($seances): array
    {
        $aEmarge = fn ($seance, string $type) => $seance->teacherAttendances->contains('type', $type);
        $completes = $seances->filter(fn ($s) => $aEmarge($s, 'start') && $aEmarge($s, 'end'));

        $stats = [
            'scheduled_courses_today' => $seances->count(),
            'teacher_attendances_today' => $completes->count(),
            'teacher_start_attendances_today' => $seances->filter(fn ($s) => $aEmarge($s, 'start'))->count(),
            'teacher_end_attendances_today' => $seances->filter(fn ($s) => $aEmarge($s, 'end'))->count(),
            // Cours complet : émargement début ET fin, et des appels d'étudiants
            'courses_completed_today' => $completes->filter(fn ($s) => $s->attendances_count > 0)->count(),
        ];
        $stats['teacher_attendance_rate'] = $stats['scheduled_courses_today'] > 0
            ? round(($stats['teacher_attendances_today'] / $stats['scheduled_courses_today']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Appels de DÉBUT, de FIN et des DEUX terminés ce jour-là, en une requête.
     */
    private function statsAppels(array $jour): array
    {
        $appels = \App\Models\ESBTPSessionWorkflow::query()
            ->where(fn ($q) => $q->whereBetween('call_start_done_at', $jour)->orWhereBetween('call_end_done_at', $jour))
            ->get(['call_start_done', 'call_start_done_at', 'call_end_done', 'call_end_done_at']);
        $debutFait = fn ($w) => $w->call_start_done && $this->dansLeJour($w->call_start_done_at, $jour);

        return [
            'call_start_done_today' => $appels->filter($debutFait)->count(),
            'call_end_done_today' => $appels->filter(
                fn ($w) => $w->call_end_done && $this->dansLeJour($w->call_end_done_at, $jour)
            )->count(),
            // Les DEUX appels, la journée étant celle de l'appel de début
            'roll_calls_completed_today' => $appels->filter(fn ($w) => $debutFait($w) && $w->call_end_done)->count(),
        ];
    }

    private function dansLeJour($instant, array $jour): bool
    {
        return $instant !== null && Carbon::parse($instant)->between($jour[0], $jour[1]);
    }

    /**
     * Présences du jour de l'année courante, limitées aux étudiants inscrits
     * (inscription active) cette année.
     */
    private function presencesDuJour(array $jour, int $anneeId)
    {
        return \App\Models\ESBTPAttendance::whereBetween('date', $jour)
            ->where('annee_universitaire_id', $anneeId)
            ->whereHas('etudiant.inscriptions', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId)->where('status', 'active');
            });
    }

    /**
     * Calcule les statistiques par matière, à partir des séances déjà chargées.
     *
     * Un appel compte s'il a été fait le jour AFFICHÉ. Avant, le test était
     * isToday() : consulter une journée passée affichait toujours 0 appel.
     */
    private function getSubjectStats($seances, $workflows, array $jour)
    {
        try {
            return $seances
                ->groupBy('matiere_id')
                ->map(function ($seancesMatiere) use ($workflows, $jour) {
                    $totalSeances = $seancesMatiere->count();
                    $emargementDebutCount = $seancesMatiere->filter(fn ($s) => $s->teacherAttendances->contains('type', 'start'))->count();
                    $emargementFinCount = $seancesMatiere->filter(fn ($s) => $s->teacherAttendances->contains('type', 'end'))->count();

                    // Appels via ESBTPSessionWorkflow (plus fiable que compter les attendances)
                    $workflowsMatiere = $seancesMatiere->map(fn ($s) => $workflows->get($s->id))->filter();
                    $appelDebutCount = $workflowsMatiere->filter(
                        fn ($w) => $w->call_start_done && $this->dansLeJour($w->call_start_done_at, $jour)
                    )->count();
                    $appelFinCount = $workflowsMatiere->filter(
                        fn ($w) => $w->call_end_done && $this->dansLeJour($w->call_end_done_at, $jour)
                    )->count();

                    // 2 émargements et 2 appels possibles par séance.
                    $totalEmargementsEffectues = $emargementDebutCount + $emargementFinCount;
                    $totalAppelsEffectues = $appelDebutCount + $appelFinCount;
                    $totalOperationsPossibles = $totalSeances * 4;

                    return [
                        'matiere_name' => $seancesMatiere->first()->matiere->name ?? 'Non défini',
                        'total_seances' => $totalSeances,
                        'emargements_debut' => $emargementDebutCount,
                        'emargements_fin' => $emargementFinCount,
                        'emargements_effectues' => $totalEmargementsEffectues,
                        'emargements_possibles' => $totalSeances * 2,
                        'appels_debut' => $appelDebutCount,
                        'appels_fin' => $appelFinCount,
                        'appels_effectues' => $totalAppelsEffectues,
                        'appels_possibles' => $totalSeances * 2,
                        'taux_completion' => $totalOperationsPossibles > 0
                            ? round((($totalEmargementsEffectues + $totalAppelsEffectues) / $totalOperationsPossibles) * 100, 1)
                            : 0,
                    ];
                })
                ->sortByDesc('total_seances')
                ->take(8)
                ->values();
        } catch (\Exception $e) {
            \Log::error('Erreur stats matières: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Génère les alertes importantes, à partir des séances déjà chargées.
     */
    private function getAttendanceAlerts($seances, array $jour, $anneeUniversitaire)
    {
        $alerts = [];
        $libelle = fn ($c) => ($c->matiere->name ?? 'Matière') . ' - ' . ($c->classe->name ?? 'Classe');

        try {
            // Alerte retards d'émargement : aucun émargement, toutes dates confondues
            $courssSansEmargement = $seances->filter(fn ($s) => (int) $s->emargements_toutes_dates === 0);

            if ($courssSansEmargement->count() > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Émargements manquants',
                    'message' => $courssSansEmargement->count() . ' cours sans émargement enseignant',
                    'details' => $courssSansEmargement->take(3)->map($libelle)->values()->toArray()
                ];
            }

            // Alerte cours sans appel
            $coursSansAppel = $seances->filter(
                fn ($s) => (int) $s->emargements_toutes_dates > 0 && (int) $s->attendances_count === 0
            );

            if ($coursSansAppel->count() > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Appels en attente',
                    'message' => $coursSansAppel->count() . ' cours émargés sans appel d\'étudiants',
                    'details' => $coursSansAppel->take(3)->map($libelle)->values()->toArray()
                ];
            }

            // Alerte taux de présence faible, sur toutes les présences du jour (pas
            // seulement les finales) ; les retards comptent comme présence.
            $presence = $this->presencesDuJour($jour, $anneeUniversitaire->id)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("COALESCE(SUM(CASE WHEN statut IN ('present', 'late', 'retard') THEN 1 ELSE 0 END), 0) as presents")
                ->toBase()->first();
            $totalEtudiants = (int) $presence->total;
            $presents = (int) $presence->presents;

            if ($totalEtudiants > 0) {
                $tauxPresence = round(($presents / $totalEtudiants) * 100, 1);

                if ($tauxPresence < 70) {
                    $alerts[] = [
                        'type' => 'danger',
                        'title' => 'Taux de présence critique',
                        'message' => "Seulement {$tauxPresence}% de présence étudiants aujourd'hui",
                        'details' => ["{$presents} présents sur {$totalEtudiants} étudiants"]
                    ];
                }
            }

            // Alerte : Enseignants présents mais workflow incomplet (séance non clôturée)
            $enseignantsNonClotures = \App\Models\ESBTPSessionWorkflow::whereBetween('attendance_start_signed_at', $jour)
                ->where('attendance_start_signed', true)
                ->where('current_step', 'closed_incomplete')
                ->with(['seanceCours.teacher.user', 'seanceCours.matiere', 'seanceCours.classe'])
                ->get();

            if ($enseignantsNonClotures->count() > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Séances non clôturées',
                    'message' => $enseignantsNonClotures->count() . ' enseignant(s) présent(s) mais n\'ont pas clôturé leur séance dans les délais',
                    'details' => $enseignantsNonClotures->take(3)->map(fn($w) =>
                        ($w->seanceCours->teacher->user->name ?? 'N/A') . ' - ' .
                        ($w->seanceCours->matiere->name ?? 'N/A') . ' (' .
                        ($w->seanceCours->classe->name ?? 'N/A') . ')'
                    )->toArray()
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            \Log::error('Erreur génération alertes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Identifie les classes avec un fort taux d'absentéisme
     */
    private function getHighAbsenceClasses($date)
    {
        try {
            $classesWithHighAbsence = DB::table('esbtp_attendances')
                ->select('classe_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN statut = "absent" THEN 1 ELSE 0 END) as absents'))
                ->whereDate('date', $date)
                ->groupBy('classe_id')
                ->havingRaw('(absents / total) > 0.3') // Plus de 30% d'absences
                ->count();

            return $classesWithHighAbsence;

        } catch (\Exception $e) {
            \Log::error('Erreur calcul classes forte absentéisme: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * API pour obtenir les activités récentes
     */
    public function getRecentActivities(Request $request)
    {
        try {
            $activities = [];
            $limit = $request->get('limit', 10);

            // 1. Émargements récents
            $recentAttendances = ESBTPTeacherAttendance::with(['teacher', 'course.matiere'])
                ->whereDate('validated_at', '>=', Carbon::now()->subDay())
                ->orderBy('validated_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($recentAttendances as $attendance) {
                $activities[] = [
                    'type' => 'success',
                    'icon' => 'check',
                    'title' => 'Émargement effectué',
                    'description' => ($attendance->teacher->name ?? 'Enseignant') . ' - ' . 
                                   ($attendance->course->matiere->name ?? 'Matière') . ' - ' . 
                                   ($attendance->course->classe->name ?? 'Classe'),
                    'time' => $attendance->validated_at->diffForHumans(),
                    'timestamp' => $attendance->validated_at->timestamp
                ];
            }

            // 2. Appels récents
            $recentRollCalls = ESBTPAttendance::with(['seanceCours.matiere', 'seanceCours.classe'])
                ->whereDate('created_at', '>=', Carbon::now()->subDay())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->groupBy('seance_cours_id')
                ->take($limit);

            foreach ($recentRollCalls as $seanceId => $attendances) {
                $firstAttendance = $attendances->first();
                $present = $attendances->where('statut', 'present')->count();
                $total = $attendances->count();
                
                $activities[] = [
                    'type' => 'info',
                    'icon' => 'users',
                    'title' => 'Appel terminé',
                    'description' => ($firstAttendance->seanceCours->classe->name ?? 'Classe') . ' - ' . 
                                   $present . ' présents / ' . $total . ' étudiants',
                    'time' => $firstAttendance->created_at->diffForHumans(),
                    'timestamp' => $firstAttendance->created_at->timestamp
                ];
            }

            // Trier par timestamp décroissant
            usort($activities, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            return response()->json([
                'success' => true,
                'activities' => array_slice($activities, 0, $limit)
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération activités récentes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des activités'
            ], 500);
        }
    }

    /**
     * Génère un rapport quotidien
     */
    public function generateDailyReport(Request $request)
    {
        try {
            $date = $request->get('date', Carbon::today());
            $stats = $this->calculateAttendanceStats($date);
            
            $report = [
                'date' => Carbon::parse($date)->format('d/m/Y'),
                'summary' => [
                    'cours_prevus' => $stats['scheduled_courses_today'],
                    'emargements_effectues' => $stats['teacher_attendances_today'],
                    'taux_emargement' => $stats['teacher_attendance_rate'] . '%',
                    'appels_termines' => $stats['roll_calls_completed_today'],
                    'etudiants_presents' => $stats['students_present_today'],
                    'cours_clotures' => $stats['courses_closed_today'],
                    'retards_detectes' => $stats['delays_today']
                ],
                'recommendations' => $this->generateRecommendations($stats)
            ];

            return response()->json([
                'success' => true,
                'report' => $report
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur génération rapport quotidien: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la génération du rapport'
            ], 500);
        }
    }

    /**
     * Génère des recommandations basées sur les statistiques
     */
    private function generateRecommendations($stats)
    {
        $recommendations = [];

        // Taux d'émargement faible
        if ($stats['teacher_attendance_rate'] < 80) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Taux d\'émargement faible (' . $stats['teacher_attendance_rate'] . '%). Contacter les enseignants manquants.',
                'action' => 'Envoyer des rappels aux enseignants'
            ];
        }

        // Retards détectés
        if ($stats['delays_today'] > 0) {
            $recommendations[] = [
                'type' => 'info',
                'message' => $stats['delays_today'] . ' retard(s) d\'émargement détecté(s).',
                'action' => 'Vérifier les raisons des retards'
            ];
        }

        // Forte absentéisme
        if ($stats['high_absence_classes'] > 0) {
            $recommendations[] = [
                'type' => 'danger',
                'message' => $stats['high_absence_classes'] . ' classe(s) avec forte absentéisme.',
                'action' => 'Analyser les causes et contacter les étudiants'
            ];
        }

        // Tout va bien
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Excellente performance ! Tous les indicateurs sont au vert.',
                'action' => 'Maintenir le niveau actuel'
            ];
        }

        return $recommendations;
    }
}
