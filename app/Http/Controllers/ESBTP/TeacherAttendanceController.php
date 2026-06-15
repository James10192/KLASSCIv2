<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendanceSettings;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPSessionWorkflow;
use App\Services\NotificationService;
use App\Services\TeacherHoursService;
use App\Enums\TypeSeance;
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

            // Get the teacher record from esbtp_teachers table
            $teacher = $user->teacherProfile;

            if (!$teacher) {
                return back()->with('error', 'Profil enseignant non trouvé.');
            }

            // Check if teacher is assigned to this course
            if ($seanceCours->teacher_id !== $teacher->id) {
                return back()->with('error', 'Vous n\'êtes pas assigné à ce cours.');
            }

            // **VÉRIFICATION DES ÉMARGEMENTS EXISTANTS (DÉBUT ET FIN)**
            // Utiliser teacher_id (ESBTPTeacher.id) pas user_id et chercher par date pas par code
            $emargementDebut = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $seanceCours->id)
                ->whereDate('date', today())
                ->where('type', 'start')
                ->first();

            $emargementFin = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
                ->where('course_id', $seanceCours->id)
                ->whereDate('date', today())
                ->where('type', 'end')
                ->first();

            // **DÉTERMINER QUEL TYPE D'ÉMARGEMENT FAIRE**
            $now = Carbon::now();
            $heureDebut = Carbon::parse($seanceCours->heure_debut);
            $heureFin = Carbon::parse($seanceCours->heure_fin);
            $fenetreClotureDebut = $heureFin->copy()->subMinutes(20);

            // Est-on dans la fenêtre de clôture?
            $isInClosingWindow = $now->gte($fenetreClotureDebut);

            // Récupérer le workflow pour vérifier si l'appel de début est fait
            $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);

            // Déterminer le type d'émargement à faire
            if (!$emargementDebut) {
                // Pas encore d'émargement de début → FAIRE ÉMARGEMENT DÉBUT
                $emargementType = 'start';
            } elseif ($emargementDebut && $emargementFin) {
                // Les deux émargements sont déjà faits
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('success', 'Vous avez déjà émargé le début et la fin de cette séance.');
            } elseif (!$workflow->call_start_done) {
                // Émargement début fait mais appel de début pas encore fait
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('info', 'Vous devez d\'abord effectuer l\'appel de début avant de pouvoir émarger la fin de la séance.');
            } elseif (!$isInClosingWindow) {
                // Appel début fait mais pas encore dans la fenêtre de clôture
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('info', 'Émargement de début déjà effectué. L\'émargement de fin sera disponible à partir de ' . $fenetreClotureDebut->format('H:i') . '.');
            } elseif ($isInClosingWindow && !$emargementFin) {
                // Appel début fait + dans fenêtre clôture + pas encore émargement fin → FAIRE ÉMARGEMENT FIN
                $emargementType = 'end';
            } else {
                // Cas par défaut (ne devrait pas arriver)
                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('info', 'Veuillez vérifier l\'état de votre émargement.');
            }

            // **LOGIQUE SELON LE TYPE D'ÉMARGEMENT**
            if ($emargementType === 'start') {
                // ========== ÉMARGEMENT DE DÉBUT ==========

                // FENÊTRE 1 : AVANT heure_debut → ❌ IMPOSSIBLE d'émarger
                if ($now < $heureDebut) {
                    $dailyCode->recordAttempt(false);
                    return back()->with('error', 'Vous ne pouvez pas émarger avant le début du cours (' . $heureDebut->format('H:i') . ').');
                }

                // FENÊTRE 2 : heure_debut → heure_debut + 20min → ✅ PRÉSENT
                $limite20min = $heureDebut->copy()->addMinutes(20);

                // FENÊTRE 3 : heure_debut + 20min → heure_debut + 45min → ⚠️ RETARD
                $limite45min = $heureDebut->copy()->addMinutes(45);

                // FENÊTRE 4 : heure_debut + 45min et plus → ❌ ABSENT (workflow fermé)
                if ($now > $limite45min) {
                    // Marquer enseignant ABSENT
                    ESBTPTeacherAttendance::create([
                        'teacher_id' => $teacher->id,
                        'course_id' => $seanceCours->id,
                        'daily_code_id' => $dailyCode->id,
                        'date' => now()->toDateString(),
                        'status' => 'absent',
                        'type' => 'start',
                        'attempts' => 1,
                        'ip_address' => $request->ip(),
                        'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                        'validated_at' => now()
                    ]);

                    // Fermer le workflow directement
                    $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);
                    $workflow->current_step = 'closed_absent';
                    $workflow->save();

                    $dailyCode->recordAttempt(true);

                    return redirect()->route('teacher.dashboard')
                        ->with('error', 'Délai d\'émargement dépassé (45 minutes après le début). Vous êtes marqué ABSENT. La séance ne sera pas comptabilisée.');
                }

                // Déterminer le statut : present ou late
                $status = ($now <= $limite20min) ? 'present' : 'late';

                // Créer l'émargement de DÉBUT
                ESBTPTeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'course_id' => $seanceCours->id,
                    'daily_code_id' => $dailyCode->id,
                    'date' => now()->toDateString(),
                    'status' => $status,
                    'type' => 'start',
                    'attempts' => 1,
                    'ip_address' => $request->ip(),
                    'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                    'validated_at' => now()
                ]);

                $dailyCode->recordAttempt(true);

                // Mettre à jour le workflow - ÉMARGEMENT DE DÉBUT
                $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);
                $workflow->markAttendanceStartSigned();

                // Notification
                try {
                    $notificationService = app(NotificationService::class);
                    $notificationService->notifyCoordinateurTeacherAttendanceSigned($user, $seanceCours);
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'envoi de la notification d\'émargement: ' . $e->getMessage());
                }

                $successMessage = $status === 'late'
                    ? 'Émargement de DÉBUT enregistré avec RETARD. Veuillez maintenant effectuer l\'appel de début.'
                    : 'Émargement de DÉBUT enregistré avec succès. Veuillez maintenant effectuer l\'appel de début.';

                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('success', $successMessage);

            } else {
                // ========== ÉMARGEMENT DE FIN ==========

                // Vérifier qu'on est dans la fenêtre de clôture
                if (!$isInClosingWindow) {
                    return back()->with('error', 'L\'émargement de fin ne peut être fait qu\'à partir de ' . $fenetreClotureDebut->format('H:i') . ' (20 minutes avant la fin du cours).');
                }

                // FENÊTRE : heure_fin - 20min → heure_fin + 30min → ✅ OK
                $fenetreClotureFin = $heureFin->copy()->addMinutes(30);

                if ($now > $fenetreClotureFin) {
                    return back()->with('error', 'Délai d\'émargement de fin dépassé (30 minutes après la fin du cours).');
                }

                // Créer l'émargement de FIN
                ESBTPTeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'course_id' => $seanceCours->id,
                    'daily_code_id' => $dailyCode->id,
                    'date' => now()->toDateString(),
                    'status' => 'present', // Toujours present pour émargement de fin
                    'type' => 'end',
                    'attempts' => 1,
                    'ip_address' => $request->ip(),
                    'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                    'validated_at' => now()
                ]);

                $dailyCode->recordAttempt(true);

                // Mettre à jour le workflow - ÉMARGEMENT DE FIN
                $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceCours->id, $user->id);
                $workflow->markAttendanceEndSigned();

                return redirect()->route('teacher.select-call-type', $seanceCours->id)
                    ->with('success', 'Émargement de FIN enregistré avec succès. Vous pouvez maintenant clôturer la séance.');
            }

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
        $this->authorize('attendances.generate_codes');

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

    /**
     * Rapport premium des HEURES enseignants (coordination/pédagogie — sans montants).
     *
     * Heures précises réelles via TeacherHoursService, ventilées CM/TD/TP par
     * enseignant (baromètre comme emploi-temps.show), filtres classe/période/prof,
     * warnings de ponctualité, liste de séances en infinity scroll. AJAX no-reload.
     */
    public function report(Request $request, TeacherHoursService $hours)
    {
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeEnCours) {
            return redirect()->back()->with('error', 'Aucune année universitaire définie comme courante.');
        }

        [$from, $to] = $this->resolvePeriode($request, $anneeEnCours);
        $filtres = $this->reportFiltres($request);

        $report = $hours->report($from, $to, $filtres);

        $teachers = \App\Models\ESBTPTeacher::with('user:id,name')->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user->name ?? $t->name ?? 'Enseignant'])
            ->sortBy('name')->values();
        $classes  = \App\Models\ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $matieres = \App\Models\ESBTPMatiere::orderBy('name')->get(['id', 'name']);

        $paginator = $this->buildSeanceListQuery($from, $to, $filtres)->paginate(20);
        $rows = $this->decorateSeances($paginator->getCollection(), $hours);

        return view('esbtp.teacher-attendance.report', [
            'report'       => $report,
            'anneeEnCours' => $anneeEnCours,
            'from'         => $from,
            'to'           => $to,
            'preset'       => $request->get('preset', 'month'),
            'filtres'      => $filtres,
            'teachers'     => $teachers,
            'classes'      => $classes,
            'matieres'     => $matieres,
            'rows'         => $rows,
            'paginator'    => $paginator,
            'typeOptions'  => $this->typeSeanceOptions(),
        ]);
    }

    /**
     * Endpoint AJAX du rapport heures : recalcul filtres + pagination infinite scroll.
     * mode=filter → KPIs + cartes enseignants + 1ʳᵉ page ; mode=scroll → page séances seule.
     */
    public function reportData(Request $request, TeacherHoursService $hours)
    {
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeEnCours) {
            return response()->json(['error' => 'Aucune année universitaire courante.'], 422);
        }

        [$from, $to] = $this->resolvePeriode($request, $anneeEnCours);
        $filtres = $this->reportFiltres($request);
        $mode = $request->get('mode', 'filter');

        $paginator = $this->buildSeanceListQuery($from, $to, $filtres)->paginate(20);
        $rows = $this->decorateSeances($paginator->getCollection(), $hours);

        $payload = [
            'seances_html' => view('esbtp.teacher-attendance.partials._report_seances', [
                'rows' => $rows,
            ])->render(),
            'has_more'    => $paginator->hasMorePages(),
            'next_page'   => $paginator->currentPage() + 1,
            'total'       => $paginator->total(),
            'periode'     => ['from' => $from->format('d/m/Y'), 'to' => $to->format('d/m/Y')],
        ];

        if ($mode === 'filter') {
            $report = $hours->report($from, $to, $filtres);
            $payload['kpis_html'] = view('esbtp.teacher-attendance.partials._report_kpis', [
                'report' => $report,
            ])->render();
            $payload['teachers_html'] = view('esbtp.teacher-attendance.partials._report_teachers', [
                'report' => $report,
            ])->render();
        }

        return response()->json($payload);
    }

    /**
     * Résout la période [from, to] depuis preset (month|year) ou from/to explicites.
     *
     * @return array{0:\Carbon\Carbon,1:\Carbon\Carbon}
     */
    private function resolvePeriode(Request $request, $annee): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [
                Carbon::parse($request->get('from'))->startOfDay(),
                Carbon::parse($request->get('to'))->endOfDay(),
            ];
        }

        if ($request->get('preset') === 'year') {
            if ($annee && $annee->date_debut && $annee->date_fin) {
                return [Carbon::parse($annee->date_debut)->startOfDay(), Carbon::parse($annee->date_fin)->endOfDay()];
            }
            return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
        }

        // Défaut : mois courant (période de paie naturelle).
        return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
    }

    /**
     * Filtres normalisés du rapport (sans annee : la période temporelle borne déjà).
     *
     * @return array<string,mixed>
     */
    private function reportFiltres(Request $request): array
    {
        return array_filter([
            'teacher_id'  => $request->get('teacher_id'),
            'classe_id'   => $request->get('classe_id'),
            'matiere_id'  => $request->get('matiere_id'),
            'type_seance' => $request->get('type_seance'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Requête de la liste des séances (infinity scroll), scopée période + filtres.
     */
    private function buildSeanceListQuery(Carbon $from, Carbon $to, array $filtres)
    {
        $query = ESBTPSeanceCours::query()
            ->whereNotNull('teacher_id')
            ->whereNotIn('type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->whereNotNull('date_seance')
            ->whereDate('date_seance', '>=', $from->toDateString())
            ->whereDate('date_seance', '<=', $to->toDateString())
            ->with([
                'matiere:id,name',
                'teacher:id,user_id',
                'teacher.user:id,name',
                'classe:id,name',
                'teacherAttendances',
            ]);

        if (!empty($filtres['teacher_id'])) {
            $query->where('teacher_id', $filtres['teacher_id']);
        }
        if (!empty($filtres['classe_id'])) {
            $query->where('classe_id', $filtres['classe_id']);
        }
        if (!empty($filtres['matiere_id'])) {
            $query->where('matiere_id', $filtres['matiere_id']);
        }
        if (!empty($filtres['type_seance'])) {
            $query->where('type_seance', $filtres['type_seance']);
        }

        return $query->orderBy('date_seance', 'desc')->orderBy('heure_debut', 'asc');
    }

    /**
     * Décore les séances pour l'affichage (durée précise, statut, ponctualité).
     *
     * @return \Illuminate\Support\Collection<int, array<string,mixed>>
     */
    private function decorateSeances($seances, TeacherHoursService $hours)
    {
        $today = Carbon::today();

        return $seances->map(function ($seance) use ($hours, $today) {
            $statut = $this->resolveAttendanceStatus($seance, $today);
            // Valeur brute : le cast enum lève une ValueError sur les valeurs legacy ('cours').
            $type = TypeSeance::fromLegacy($seance->getRawOriginal('type_seance'));
            $estPassee = $seance->date_seance && Carbon::parse($seance->date_seance)->endOfDay()->isPast();

            // Séance encore à venir (date future, ou aujourd'hui avant la fin) → pas d'action.
            $estFuture = false;
            if ($seance->date_seance) {
                $dateSeance = Carbon::parse($seance->date_seance)->startOfDay();
                if ($dateSeance->gt($today)) {
                    $estFuture = true;
                } elseif ($dateSeance->eq($today) && $seance->heure_fin) {
                    $estFuture = $seance->heure_fin->gt(now());
                }
            }

            $statutMeta = [
                'present'    => ['label' => 'Présent', 'bg' => 'rgba(16,185,129,.12)', 'color' => '#065f46'],
                'late'       => ['label' => 'En retard', 'bg' => 'rgba(245,158,11,.14)', 'color' => '#92400e'],
                'absent'     => ['label' => 'Absent', 'bg' => 'rgba(220,38,38,.12)', 'color' => '#b91c1c'],
                'not_signed' => ['label' => $estPassee ? 'Non émargé' : 'À venir', 'bg' => 'rgba(100,116,139,.12)', 'color' => '#475569'],
            ][$statut] ?? ['label' => $statut, 'bg' => 'rgba(100,116,139,.12)', 'color' => '#475569'];

            return [
                'id'           => $seance->id,
                'date'         => $seance->date_seance ? Carbon::parse($seance->date_seance) : null,
                'classe'       => $seance->classe->name ?? '—',
                'matiere'      => $seance->matiere->name ?? '—',
                'teacher_id'   => $seance->teacher_id,
                'teacher'      => $seance->teacher?->user?->name ?? $seance->teacher?->name ?? 'Enseignant',
                'type'         => $type,
                'heure_debut'  => $seance->heure_debut?->format('H:i'),
                'heure_fin'    => $seance->heure_fin?->format('H:i'),
                'duree'        => $hours->dureeSeance($seance),
                'statut'       => $statut,
                'statut_label' => $statutMeta['label'],
                'statut_bg'    => $statutMeta['bg'],
                'statut_color' => $statutMeta['color'],
                'en_retard'    => $statut === 'late',
                'non_emarge'   => $estPassee && $statut === 'not_signed',
                'future'       => $estFuture,
                'salle'        => $seance->salle,
                'date_full'    => $seance->date_seance
                    ? Carbon::parse($seance->date_seance)->translatedFormat('l d F Y') : null,
                'show_url'     => route('esbtp.seances-cours.show', $seance->id),
            ];
        });
    }

    /** Options de type de séance pour le filtre (CM/TD/TP/… via l'enum). */
    private function typeSeanceOptions(): array
    {
        $out = [];
        foreach (TypeSeance::plannableCases() as $t) {
            $out[$t->value] = $t->label();
        }
        return $out;
    }

    /**
     * Fiche premium d'un enseignant : heures précises CM/TD/TP sur la période,
     * baromètre de réalisation, alertes de ponctualité et séances en infinity scroll.
     * Page pédagogique (heures seulement, aucun montant).
     */
    public function teacherReport(Request $request, \App\Models\ESBTPTeacher $teacher, TeacherHoursService $hours)
    {
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return redirect()->back()->with('error', 'Aucune année universitaire définie comme courante.');
        }

        [$from, $to] = $this->resolvePeriode($request, $anneeEnCours);
        $summary = $hours->summary($teacher, $from, $to);

        $paginator = $this->buildSeanceListQuery($from, $to, ['teacher_id' => $teacher->id])->paginate(20);
        $rows = $this->decorateSeances($paginator->getCollection(), $hours);

        return view('esbtp.teacher-attendance.teacher-report', [
            'teacher'      => $teacher,
            'anneeEnCours' => $anneeEnCours,
            'summary'      => $summary,
            'from'         => $from,
            'to'           => $to,
            'preset'       => $request->get('preset', 'year'),
            'rows'         => $rows,
            'paginator'    => $paginator,
        ]);
    }

    /**
     * Endpoint AJAX de la fiche enseignant : recalcul période + infinity scroll.
     */
    public function teacherReportData(Request $request, \App\Models\ESBTPTeacher $teacher, TeacherHoursService $hours)
    {
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return response()->json(['error' => 'Aucune année universitaire courante.'], 422);
        }

        [$from, $to] = $this->resolvePeriode($request, $anneeEnCours);
        $mode = $request->get('mode', 'filter');

        $paginator = $this->buildSeanceListQuery($from, $to, ['teacher_id' => $teacher->id])->paginate(20);
        $rows = $this->decorateSeances($paginator->getCollection(), $hours);

        $payload = [
            'seances_html' => view('esbtp.teacher-attendance.partials._report_seances', ['rows' => $rows])->render(),
            'has_more'     => $paginator->hasMorePages(),
            'next_page'    => $paginator->currentPage() + 1,
            'total'        => $paginator->total(),
            'periode'      => ['from' => $from->format('d/m/Y'), 'to' => $to->format('d/m/Y')],
        ];

        if ($mode === 'filter') {
            $summary = $hours->summary($teacher, $from, $to);
            $payload['kpis_html'] = view('esbtp.teacher-attendance.partials._teacher_kpis', ['summary' => $summary])->render();
            $payload['types_html'] = view('esbtp.teacher-attendance.partials._teacher_types', ['summary' => $summary])->render();
            $payload['warnings_html'] = view('esbtp.teacher-attendance.partials._teacher_warnings', ['summary' => $summary])->render();
        }

        return response()->json($payload);
    }

    private function resolveAttendanceStatus($seance, \Carbon\Carbon $today): string
    {
        $attendance = $seance->teacherAttendances
            ->first(function ($attendance) use ($today) {
                $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                    ? $attendance->date
                    : \Carbon\Carbon::parse($attendance->date);
                return $attendanceDate->isSameDay($today);
            });

        if (! $attendance) {
            $attendance = $seance->teacherAttendances
                ->first(function ($attendance) use ($seance) {
                    $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                        ? $attendance->date
                        : \Carbon\Carbon::parse($attendance->date);
                    return $attendanceDate->isSameDay(\Carbon\Carbon::parse($seance->date_seance));
                });
        }

        if (! $attendance) {
            $attendance = $seance->teacherAttendances->sortByDesc('created_at')->first();
        }

        return $attendance ? $attendance->status : 'not_signed';
    }

    /**
     * Affiche la page de sélection du type d'appel (début/fin)
     */
    public function selectCallType($seanceId)
    {
        $user = Auth::user();
        $seance = ESBTPSeanceCours::with(['matiere', 'classe'])->findOrFail($seanceId);

        // Récupérer le modèle enseignant associé à l'utilisateur
        $teacherModel = $user->teacherProfile;
        if (!$teacherModel) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Aucun profil enseignant associé à ce compte.');
        }

        // Vérifier que l'enseignant est assigné à cette séance
        if ($seance->teacher_id !== $teacherModel->id) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Vous n\'êtes pas autorisé à accéder à cette séance.');
        }

        // Récupérer ou créer le workflow pour cette séance
        $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceId, $user->id);

        // **VÉRIFICATION DE LA FENÊTRE POUR L'APPEL DE FIN**
        $now = Carbon::now();
        $heureFin = Carbon::parse($seance->heure_fin);
        $fenetreDebut = $heureFin->copy()->subMinutes(20); // 20 minutes avant la fin

        // Vérifier si on peut faire l'appel de fin (dans la fenêtre 20 min avant fin)
        $canEndCall = $now >= $fenetreDebut;
        $endCallMessage = null;

        if (!$canEndCall) {
            $endCallMessage = 'L\'appel de fin sera disponible à partir de ' . $fenetreDebut->format('H:i') . ' (20 minutes avant la fin du cours).';
        }

        return view('teacher.select-call-type', compact('seance', 'workflow', 'canEndCall', 'endCallMessage'));
    }
}
