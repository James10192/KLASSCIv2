<?php

namespace App\Http\Controllers;

use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendance;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CoordinateurDashboardController extends Controller
{
    /**
     * Constructeur avec middleware de rôle
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:coordinateur']);
    }

    /**
     * Affiche le tableau de bord des présences pour les coordinateurs
     */
    public function attendanceDashboard()
    {
        $today = Carbon::today();
        $stats = $this->calculateAttendanceStats($today);
        
        // Nombre de notifications non lues
        $unreadNotifications = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return view('coordinateur.dashboard-attendance', compact('stats', 'unreadNotifications'));
    }

    /**
     * Calcule les statistiques de présence pour le tableau de bord
     */
    private function calculateAttendanceStats($date)
    {
        try {
            $stats = [];

            // 1. Émargements enseignants aujourd'hui
            $stats['scheduled_courses_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->where('is_active', true)
                ->count();

            $stats['teacher_attendances_today'] = ESBTPTeacherAttendance::whereDate('validated_at', $date)
                ->count();

            $stats['teacher_attendance_rate'] = $stats['scheduled_courses_today'] > 0 
                ? round(($stats['teacher_attendances_today'] / $stats['scheduled_courses_today']) * 100, 1) 
                : 0;

            // 2. Appels terminés et présences étudiants
            $stats['roll_calls_completed_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereHas('attendances') // Séances avec des appels enregistrés
                ->count();

            $stats['students_present_today'] = ESBTPAttendance::whereDate('date', $date)
                ->where('status', 'present')
                ->count();

            $stats['roll_call_completion_rate'] = $stats['scheduled_courses_today'] > 0 
                ? round(($stats['roll_calls_completed_today'] / $stats['scheduled_courses_today']) * 100, 1) 
                : 0;

            // 3. Retards détectés
            $stats['delays_today'] = max(0, $stats['scheduled_courses_today'] - $stats['teacher_attendances_today']);

            // 4. Cours clôturés
            $stats['courses_closed_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->where('status', 'completed')
                ->count();

            // 5. Classes avec forte absentéisme (plus de 30% d'absences)
            $stats['high_absence_classes'] = $this->getHighAbsenceClasses($date);

            return $stats;

        } catch (\Exception $e) {
            \Log::error('Erreur calcul statistiques coordinateur: ' . $e->getMessage());
            
            // Retourner des statistiques par défaut en cas d'erreur
            return [
                'scheduled_courses_today' => 0,
                'teacher_attendances_today' => 0,
                'teacher_attendance_rate' => 0,
                'roll_calls_completed_today' => 0,
                'students_present_today' => 0,
                'roll_call_completion_rate' => 0,
                'delays_today' => 0,
                'courses_closed_today' => 0,
                'high_absence_classes' => 0
            ];
        }
    }

    /**
     * Identifie les classes avec un fort taux d'absentéisme
     */
    private function getHighAbsenceClasses($date)
    {
        try {
            $classesWithHighAbsence = DB::table('esbtp_attendances')
                ->select('classe_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absents'))
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
                $present = $attendances->where('status', 'present')->count();
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