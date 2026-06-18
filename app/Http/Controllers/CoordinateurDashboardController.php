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
        $this->middleware(['auth', 'role:coordinateur|secretaire|superAdmin']);
        $this->middleware('permission:module.presences.access')->only('attendanceDashboard');
        $this->notificationService = $notificationService;
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

        // Envoyer des notifications pour les alertes critiques
        if (!empty($stats['alerts'])) {
            $this->notificationService->notifyCoordinateurCriticalAlerts($stats['alerts'], $today);
        }

        return view('coordinateur.dashboard-attendance', compact('stats', 'unreadNotifications'));
    }

    /**
     * Calcule les statistiques de présence pour le tableau de bord
     */
    private function calculateAttendanceStats($date)
    {
        try {
            $stats = [];

            // 1. Séances programmées aujourd'hui
            $stats['scheduled_courses_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->count();

            // 2. Émargements enseignants effectués (COMPLETS = début + fin)
            // Compter les cours avec DEUX émargements (start ET end)
            $dailyCode = \App\Models\ESBTPDailyCode::where('status', 'active')
                ->where('is_active', true)
                ->whereDate('created_at', $date->toDateString())
                ->first();

            // Compter les cours qui ont à la fois émargement début ET fin
            $stats['teacher_attendances_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereHas('teacherAttendances', function($q) use ($date) {
                    $q->whereDate('date', $date)
                      ->where('type', 'start');
                })
                ->whereHas('teacherAttendances', function($q) use ($date) {
                    $q->whereDate('date', $date)
                      ->where('type', 'end');
                })
                ->count();

            // Compter aussi les émargements de début seulement (pour statistiques)
            $stats['teacher_start_attendances_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereHas('teacherAttendances', function($q) use ($date) {
                    $q->whereDate('date', $date)
                      ->where('type', 'start');
                })
                ->count();

            // Compter émargements FIN seulement
            $stats['teacher_end_attendances_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereHas('teacherAttendances', function($q) use ($date) {
                    $q->whereDate('date', $date)
                      ->where('type', 'end');
                })
                ->count();

            // 3. Taux d'émargement (basé sur émargements COMPLETS)
            $stats['teacher_attendance_rate'] = $stats['scheduled_courses_today'] > 0
                ? round(($stats['teacher_attendances_today'] / $stats['scheduled_courses_today']) * 100, 1)
                : 0;

            // 4. Appels de DÉBUT terminés (séances avec workflow call_start_done)
            $stats['call_start_done_today'] = \App\Models\ESBTPSessionWorkflow::whereDate('call_start_done_at', $date)
                ->where('call_start_done', true)
                ->count();

            // 5. Appels de FIN terminés (séances avec workflow call_end_done)
            $stats['call_end_done_today'] = \App\Models\ESBTPSessionWorkflow::whereDate('call_end_done_at', $date)
                ->where('call_end_done', true)
                ->count();

            // 6. Appels TOTAUX terminés (les DEUX appels - début ET fin)
            $stats['roll_calls_completed_today'] = \App\Models\ESBTPSessionWorkflow::whereDate('call_start_done_at', $date)
                ->where('call_start_done', true)
                ->where('call_end_done', true)
                ->count();

            // Récupérer l'année universitaire en cours
            $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

            // 5. PRÉSENCES finales aujourd'hui (pas étudiants uniques, mais nombre de présences)
            // IMPORTANT: Utiliser finalOnly() pour ne compter que les statuts fusionnés
            // Filtré par année universitaire en cours et inscriptions active
            $stats['presences_today'] = \App\Models\ESBTPAttendance::finalOnly()
                ->whereDate('date', $date)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->whereIn('statut', ['present', 'late', 'retard'])
                ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active');
                })
                ->count();

            // 6. ABSENCES finales aujourd'hui
            $stats['absences_today'] = \App\Models\ESBTPAttendance::finalOnly()
                ->whereDate('date', $date)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->where('statut', 'absent')
                ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active');
                })
                ->count();

            // 7. RETARDS finaux aujourd'hui
            $stats['retards_today'] = \App\Models\ESBTPAttendance::finalOnly()
                ->whereDate('date', $date)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->whereIn('statut', ['late', 'retard'])
                ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active');
                })
                ->count();

            // 8. TOTAL des appels faits (toutes présences finales)
            $stats['total_calls_today'] = \App\Models\ESBTPAttendance::finalOnly()
                ->whereDate('date', $date)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', 'active');
                })
                ->count();

            // Garder aussi students_present_today et students_total_today pour compatibilité
            $stats['students_present_today'] = $stats['presences_today'];
            $stats['students_total_today'] = $stats['total_calls_today'];

            // 7. Taux de présence étudiants
            $stats['student_attendance_rate'] = $stats['students_total_today'] > 0
                ? round(($stats['students_present_today'] / $stats['students_total_today']) * 100, 1)
                : 0;

            // 8. Retards d'émargement (cours sans émargement enseignant)
            $stats['delays_today'] = max(0, $stats['scheduled_courses_today'] - $stats['teacher_attendances_today']);

            // 9. Cours avec workflow complet (2 émargements + 2 appels)
            // Un cours est complet si:
            // - Émargement début ET fin (les deux types)
            // - Appels d'étudiants effectués
            $stats['courses_completed_today'] = ESBTPSeanceCours::whereDate('date_seance', $date)
                // Vérifier émargement DÉBUT
                ->whereHas('teacherAttendances', function($q) use ($date) {
                    $q->whereDate('date', $date)
                      ->where('type', 'start');
                })
                // Vérifier émargement FIN
                ->whereHas('teacherAttendances', function($q) use ($date) {
                    $q->whereDate('date', $date)
                      ->where('type', 'end');
                })
                // Vérifier qu'il y a des appels d'étudiants
                ->whereHas('attendances')
                ->count();

            // 10. Enseignants actifs aujourd'hui
            $stats['active_teachers_today'] = ESBTPTeacherAttendance::whereDate('created_at', $date)
                ->distinct('teacher_id')
                ->count();

            // 11. Statistiques par matière
            $stats['subjects_stats'] = $this->getSubjectStats($date);

            // 12. Alertes importantes
            $stats['alerts'] = $this->getAttendanceAlerts($date);

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
     * Calcule les statistiques par matière
     */
    private function getSubjectStats($date)
    {
        try {
            return ESBTPSeanceCours::whereDate('date_seance', $date)
                ->with(['matiere', 'teacherAttendances', 'attendances'])
                ->get()
                ->groupBy('matiere_id')
                ->map(function ($seances, $matiereId) use ($date) {
                    $matiere = $seances->first()->matiere;
                    $totalSeances = $seances->count();

                    // Compter les émargements DÉBUT et FIN séparément
                    $emargementDebutCount = 0;
                    $emargementFinCount = 0;

                    // Compter les appels DÉBUT et FIN séparément
                    $appelDebutCount = 0;
                    $appelFinCount = 0;

                    foreach ($seances as $seance) {
                        // Vérifier émargements
                        $hasEmargementDebut = $seance->teacherAttendances()
                            ->whereDate('date', $date)
                            ->where('type', 'start')
                            ->exists();

                        $hasEmargementFin = $seance->teacherAttendances()
                            ->whereDate('date', $date)
                            ->where('type', 'end')
                            ->exists();

                        if ($hasEmargementDebut) $emargementDebutCount++;
                        if ($hasEmargementFin) $emargementFinCount++;

                        // Vérifier appels via ESBTPSessionWorkflow (plus fiable que compter les attendances)
                        $workflow = \App\Models\ESBTPSessionWorkflow::where('seance_cours_id', $seance->id)
                            ->first();

                        if ($workflow) {
                            // Appel début fait si call_start_done = true et call_start_done_at est aujourd'hui
                            if ($workflow->call_start_done && $workflow->call_start_done_at &&
                                \Carbon\Carbon::parse($workflow->call_start_done_at)->isToday()) {
                                $appelDebutCount++;
                            }

                            // Appel fin fait si call_end_done = true et call_end_done_at est aujourd'hui
                            if ($workflow->call_end_done && $workflow->call_end_done_at &&
                                \Carbon\Carbon::parse($workflow->call_end_done_at)->isToday()) {
                                $appelFinCount++;
                            }
                        }
                    }

                    // Total émargements possibles = 2 par séance (début + fin)
                    $totalEmargementsPossibles = $totalSeances * 2;
                    $totalEmargementsEffectues = $emargementDebutCount + $emargementFinCount;

                    // Total appels possibles = 2 par séance (début + fin)
                    $totalAppelsPossibles = $totalSeances * 2;
                    $totalAppelsEffectues = $appelDebutCount + $appelFinCount;

                    // Taux de complétion basé sur émargements ET appels
                    // Total opérations = 4 par séance (2 émargements + 2 appels)
                    $totalOperationsPossibles = ($totalEmargementsPossibles + $totalAppelsPossibles);
                    $totalOperationsEffectuees = ($totalEmargementsEffectues + $totalAppelsEffectues);

                    $tauxCompletion = $totalOperationsPossibles > 0
                        ? round(($totalOperationsEffectuees / $totalOperationsPossibles) * 100, 1)
                        : 0;

                    return [
                        'matiere_name' => $matiere->name ?? 'Non défini',
                        'total_seances' => $totalSeances,
                        'emargements_debut' => $emargementDebutCount,
                        'emargements_fin' => $emargementFinCount,
                        'emargements_effectues' => $totalEmargementsEffectues,
                        'emargements_possibles' => $totalEmargementsPossibles,
                        'appels_debut' => $appelDebutCount,
                        'appels_fin' => $appelFinCount,
                        'appels_effectues' => $totalAppelsEffectues,
                        'appels_possibles' => $totalAppelsPossibles,
                        'taux_completion' => $tauxCompletion
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
     * Génère les alertes importantes
     */
    private function getAttendanceAlerts($date)
    {
        $alerts = [];
        
        try {
            // Alerte retards d'émargement
            $courssSansEmargement = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereDoesntHave('teacherAttendance')
                ->with(['matiere', 'classe'])
                ->get();
            
            if ($courssSansEmargement->count() > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Émargements manquants',
                    'message' => $courssSansEmargement->count() . ' cours sans émargement enseignant',
                    'details' => $courssSansEmargement->take(3)->map(fn($c) => 
                        ($c->matiere->name ?? 'Matière') . ' - ' . ($c->classe->name ?? 'Classe')
                    )->toArray()
                ];
            }

            // Alerte cours sans appel
            $coursSansAppel = ESBTPSeanceCours::whereDate('date_seance', $date)
                ->whereHas('teacherAttendance')
                ->whereDoesntHave('attendances')
                ->with(['matiere', 'classe'])
                ->get();
                
            if ($coursSansAppel->count() > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Appels en attente',
                    'message' => $coursSansAppel->count() . ' cours émargés sans appel d\'étudiants',
                    'details' => $coursSansAppel->take(3)->map(fn($c) => 
                        ($c->matiere->name ?? 'Matière') . ' - ' . ($c->classe->name ?? 'Classe')
                    )->toArray()
                ];
            }

            // Alerte taux de présence faible
            // IMPORTANT: Filtrer par année universitaire en cours + inscriptions actives
            $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if ($anneeUniversitaire) {
                $totalEtudiants = \App\Models\ESBTPAttendance::whereDate('date', $date)
                    ->where('annee_universitaire_id', $anneeUniversitaire->id)
                    ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                        $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                          ->where('status', 'active');
                    })
                    ->count();

                if ($totalEtudiants > 0) {
                    // IMPORTANT: Les retards comptent comme présence
                    $presents = \App\Models\ESBTPAttendance::whereDate('date', $date)
                        ->where('annee_universitaire_id', $anneeUniversitaire->id)
                        ->whereIn('statut', ['present', 'late', 'retard'])
                        ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
                            $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                              ->where('status', 'active');
                        })
                        ->count();
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
            }

            // Alerte : Enseignants présents mais workflow incomplet (séance non clôturée)
            $enseignantsNonClotures = \App\Models\ESBTPSessionWorkflow::whereDate('attendance_start_signed_at', $date)
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