<?php

namespace App\Http\Controllers;

use App\Exports\NotesClasseMatiereExport;
use App\Http\Requests\Notes\ImportNotesRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Http\Requests\Notes\StoreBulkNotesRequest;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Models\User;
use App\Services\NoteCalculationService;
use App\Services\NotesImportService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ESBTPNoteController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware(['auth']);
        $this->middleware('permission:module.notes_evaluations.access');
        $this->notificationService = $notificationService;
    }

    /**
     * Affiche la liste des notes avec filtre par classe et matière
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get current academic year
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', 1)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : 'Aucune année active';

        $semesterWeights = [
            'semester1' => floatval(\App\Helpers\SettingsHelper::get('bulletin_semester1_weight', '50')),
            'semester2' => floatval(\App\Helpers\SettingsHelper::get('bulletin_semester2_weight', '50')),
        ];
        if (($semesterWeights['semester1'] + $semesterWeights['semester2']) <= 0) {
            $semesterWeights = ['semester1' => 50, 'semester2' => 50];
        }

        // Check if we're using the new modal system (via AJAX call)
        $isAjax = $request->ajax() || $request->wantsJson();

        $classesQuery = ESBTPClasse::query()
            ->where(fn($q) => $q->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->withCount(['inscriptions' => function ($query) use ($anneeCourante) {
                $query->where('status', 'active');
                if ($anneeCourante) {
                    $query->where('annee_universitaire_id', $anneeCourante->id);
                }
            }])
            ->with(['filiere', 'niveau', 'annee']);

        // Enseignant : ne voir que les classes où il a des séances dans l'emploi du temps
        $user = Auth::user();
        if ($user && $user->can('identity.teach')) {
            $teacher = $user->teacherProfile;
            if ($teacher && $anneeCourante) {
                $classeIds = ESBTPSeanceCours::query()
                    ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
                    ->where('esbtp_seance_cours.teacher_id', $teacher->id)
                    ->where('esbtp_emploi_temps.annee_universitaire_id', $anneeCourante->id)
                    ->distinct()
                    ->pluck('esbtp_seance_cours.classe_id');
                $classesQuery->whereIn('id', $classeIds);
            } elseif ($teacher) {
                $classeIds = ESBTPSeanceCours::where('teacher_id', $teacher->id)
                    ->distinct()->pluck('classe_id');
                $classesQuery->whereIn('id', $classeIds);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $classesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhereHas('filiere', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('niveau', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($request->filled('filiere_id')) {
            $classesQuery->where('filiere_id', $request->input('filiere_id'));
        }

        if ($request->filled('niveau_id')) {
            $classesQuery->where('niveau_etude_id', $request->input('niveau_id'));
        }

        if ($request->filled('statut')) {
            $statut = $request->input('statut');
            if ($statut === 'active') {
                $classesQuery->where('is_active', true);
            } elseif ($statut === 'inactive') {
                $classesQuery->where('is_active', false);
            }
        }

        $classesQuery->orderBy('name');

        $classes = $classesQuery->get();

        if ($request->filled('capacite')) {
            $capacityFilter = $request->input('capacite');
            $classes = $classes->filter(function ($classe) use ($capacityFilter) {
                $placesDisponibles = ($classe->places_totales ?? 0) - ($classe->inscriptions_count ?? 0);
                if ($capacityFilter === 'disponible') {
                    return $placesDisponibles > 0;
                }
                if ($capacityFilter === 'pleine') {
                    return $placesDisponibles <= 0;
                }
                return true;
            })->values();
        }

        $classeIds = $classes->pluck('id');
        $filiereIds = $classes->pluck('filiere_id')->filter()->unique();
        $niveauIds = $classes->pluck('niveau_etude_id')->filter()->unique();

        $matieresTotals = collect();
        if ($filiereIds->isNotEmpty() && $niveauIds->isNotEmpty()) {
            $matiereCounts = DB::table('esbtp_matiere_filiere as mf')
                ->join('esbtp_matiere_niveau as mn', 'mn.matiere_id', '=', 'mf.matiere_id')
                ->join('esbtp_matieres as m', 'm.id', '=', 'mf.matiere_id')
                ->whereIn('mf.filiere_id', $filiereIds)
                ->whereIn('mn.niveau_etude_id', $niveauIds)
                ->where('m.is_active', 1)
                ->select('mf.filiere_id', 'mn.niveau_etude_id', DB::raw('count(distinct mf.matiere_id) as total'))
                ->groupBy('mf.filiere_id', 'mn.niveau_etude_id')
                ->get();

            $matiereCountMap = [];
            foreach ($matiereCounts as $row) {
                $matiereCountMap[$row->filiere_id.'|'.$row->niveau_etude_id] = (int) $row->total;
            }

            foreach ($classes as $classe) {
                if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
                    $matieresTotals[$classe->id] = 0;
                    continue;
                }
                $key = $classe->filiere_id.'|'.$classe->niveau_etude_id;
                $matieresTotals[$classe->id] = $matiereCountMap[$key] ?? 0;
            }
        }

        $matieresConfigured = ($anneeCourante && $classeIds->isNotEmpty())
            ? DB::table('esbtp_evaluations')
                ->select('classe_id', DB::raw('count(distinct matiere_id) as total'))
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->whereIn('classe_id', $classeIds)
                ->groupBy('classe_id')
                ->pluck('total', 'classe_id')
            : collect();

        $bulletinAverages = ($anneeCourante && $classeIds->isNotEmpty())
            ? DB::table('esbtp_bulletins')
                ->select('classe_id', 'periode', DB::raw('avg(moyenne_generale) as moyenne'), DB::raw('count(*) as total'))
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->whereIn('classe_id', $classeIds)
                ->whereNotNull('moyenne_generale')
                ->whereNull('archived_at')
                ->groupBy('classe_id', 'periode')
                ->get()
            : collect();

        $avgByClass = [];
        foreach ($bulletinAverages as $row) {
            $periodKey = $row->periode === '2' ? 'semestre2' : ($row->periode === '1' ? 'semestre1' : $row->periode);
            $avgByClass[$row->classe_id][$periodKey] = [
                'moyenne' => $row->total > 0 ? floatval($row->moyenne) : null,
                'total' => (int) $row->total,
            ];
        }

        $classStatsById = [];
        foreach ($classes as $classe) {
            $totalMatieres = (int) ($matieresTotals[$classe->id] ?? 0);
            $configuredMatieresRaw = (int) ($matieresConfigured[$classe->id] ?? 0);
            $configuredMatieres = $totalMatieres > 0 ? min($configuredMatieresRaw, $totalMatieres) : 0;
            $completion = $totalMatieres > 0 ? round(($configuredMatieres / $totalMatieres) * 100) : 0;
            $moyenneS1 = $avgByClass[$classe->id]['semestre1']['moyenne'] ?? null;
            $moyenneS2 = $avgByClass[$classe->id]['semestre2']['moyenne'] ?? null;
            $annual = null;
            if ($moyenneS1 !== null && $moyenneS2 !== null) {
                $totalWeight = $semesterWeights['semester1'] + $semesterWeights['semester2'];
                $annual = $totalWeight > 0
                    ? (($moyenneS1 * $semesterWeights['semester1']) + ($moyenneS2 * $semesterWeights['semester2'])) / $totalWeight
                    : null;
            }

            $classStatsById[$classe->id] = [
                'matieres_total' => $totalMatieres,
                'matieres_configured' => $configuredMatieres,
                'matieres_configured_raw' => $configuredMatieresRaw,
                'completion' => $completion,
                'moyenne_s1' => $moyenneS1,
                'moyenne_s2' => $moyenneS2,
                'moyenne_annuelle' => $annual,
            ];
        }

        // Hero KPI aggregates
        $heroStats = [
            'total_matieres' => array_sum(array_column($classStatsById, 'matieres_total')),
            'total_configured' => array_sum(array_column($classStatsById, 'matieres_configured')),
            'avg_completion' => count($classStatsById) > 0
                ? round(array_sum(array_column($classStatsById, 'completion')) / count($classStatsById))
                : 0,
            'global_avg' => (function () use ($classStatsById) {
                $vals = array_filter(array_column($classStatsById, 'moyenne_annuelle'), fn ($v) => $v !== null);

                return count($vals) > 0 ? array_sum($vals) / count($vals) : null;
            })(),
        ];

        if ($request->ajax() && $request->boolean('classes_ajax')) {
            return response()->json([
                'success' => true,
                'html' => view('esbtp.notes.partials.classes-items', [
                    'classes' => $classes,
                    'classStatsById' => $classStatsById,
                ])->render(),
                'total' => $classes->count(),
            ]);
        }

        if ($isAjax) {
            // For AJAX requests, use the old system
            $query = ESBTPNote::whereHas('evaluation', function ($q) use ($anneeCourante) {
                if ($anneeCourante) {
                    $q->where('annee_universitaire_id', $anneeCourante->id);
                }
            })
                ->with([
                    'evaluation.matiere',
                    'evaluation.classe',
                    'etudiant',
                    'createdBy',
                ]);

            // Apply filters
            if ($request->filled('classe_id')) {
                $query->whereHas('evaluation', function ($q) use ($request) {
                    $q->where('classe_id', $request->classe_id);
                });
            }

            if ($request->filled('matiere_id')) {
                $query->whereHas('evaluation', function ($q) use ($request) {
                    $q->whereHas('matiere', function ($mq) use ($request) {
                        $mq->where('id', $request->matiere_id);
                    });
                });
            }

            // Get the paginated results
            $notes = $query->latest()->paginate(50);

            // Get filter options
            $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
            $matieres = ESBTPMatiere::orderBy('name')->get();
            $filieres = ESBTPFiliere::orderBy('name')->get();
            $niveaux = ESBTPNiveauEtude::orderBy('name')->get();
            $allClasses = $classes;

            $evaluationTypes = ESBTPEvaluation::getTypes();
            $enseignants = Auth::user()->can('identity.teach')
                ? collect()
                : User::whereHas('roles', fn ($q) => $q->whereIn('name', ['teacher', 'enseignant']))->orderBy('name')->get();

            return view('esbtp.notes.index', compact('notes', 'classes', 'allClasses', 'matieres', 'anneeAcademique', 'filieres', 'niveaux', 'classStatsById', 'heroStats', 'semesterWeights', 'evaluationTypes', 'enseignants'));
        }

        // Get filter options for dropdowns (if needed)
        $allClasses = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $matieres = ESBTPMatiere::orderBy('name')->get();

        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        $evaluationTypes = ESBTPEvaluation::getTypes();
        $enseignants = Auth::user()->can('identity.teach')
            ? collect()
            : User::whereHas('roles', fn ($q) => $q->whereIn('name', ['teacher', 'enseignant']))->orderBy('name')->get();

        return view('esbtp.notes.index', compact(
            'classes',
            'allClasses',
            'matieres',
            'anneeAcademique',
            'filieres',
            'niveaux',
            'classStatsById',
            'heroStats',
            'semesterWeights',
            'evaluationTypes',
            'enseignants'
        ));
    }

    /**
     * Affiche le formulaire de création d'une note.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $today = Carbon::today();
        $evaluations = ESBTPEvaluation::with(['classe', 'matiere'])
            ->where('is_published', true)
            ->whereNotNull('date_evaluation')
            ->whereDate('date_evaluation', '<=', $today)
            ->whereIn('status', [
                ESBTPEvaluation::STATUS_SCHEDULED,
                ESBTPEvaluation::STATUS_IN_PROGRESS,
                ESBTPEvaluation::STATUS_COMPLETED,
            ])
            ->orderBy('date_evaluation', 'desc')
            ->get();
        $etudiants = ESBTPEtudiant::orderBy('nom')->get();

        // Ajouter un message flash pour tester
        session()->flash('info', 'Formulaire de création de note chargé. Veuillez remplir tous les champs requis.');

        return view('esbtp.notes.create', compact('evaluations', 'etudiants'));
    }

    /**
     * Enregistre une nouvelle note.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'note' => 'required_unless:is_absent,on|numeric|min:0',
            'is_absent' => 'nullable|in:on,1,true',
            'commentaire' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'L\'étudiant est obligatoire',
            'evaluation_id.required' => 'L\'évaluation est obligatoire',
            'note.required_unless' => 'La note est obligatoire si l\'étudiant n\'est pas absent',
            'note.numeric' => 'La note doit être un nombre',
            'note.min' => 'La note doit être positive',
            'is_absent.in' => 'Le statut d\'absence n\'est pas valide',
        ]);

        try {
            // Débogage : Log du début du try
            \Log::info('Début du traitement de la note après validation');

            // Vérifier si l'étudiant a déjà une note pour cette évaluation
            $existingNote = ESBTPNote::where('etudiant_id', $request->etudiant_id)
                ->where('evaluation_id', $request->evaluation_id)
                ->first();

            if ($existingNote) {
                return redirect()->back()
                    ->with('error', 'Cet étudiant a déjà une note pour cette évaluation.')
                    ->withInput();
            }

            // Récupérer l'évaluation pour obtenir le barème et la classe
            $evaluation = ESBTPEvaluation::findOrFail($request->evaluation_id);
            if ($evaluation->date_evaluation && $evaluation->date_evaluation->isFuture()) {
                return redirect()->back()
                    ->with('error', "La saisie des notes est disponible uniquement après la date d'évaluation.")
                    ->withInput();
            }
            if (! $evaluation->is_published) {
                return redirect()->back()
                    ->with('error', 'Cette évaluation n\'est pas publiée. Activez-la avant de saisir les notes.')
                    ->withInput();
            }

            // Récupérer la classe associée à l'évaluation
            $classe_id = $evaluation->classe_id;

            // Récupérer la période de l'évaluation
            $semestre = $evaluation->periode;

            // Convertir is_absent en booléen
            $isAbsent = $request->has('is_absent') && in_array($request->is_absent, ['on', '1', 'true', true]);

            // Créer la note
            $note = new ESBTPNote;
            $note->etudiant_id = $request->etudiant_id;
            $note->evaluation_id = $request->evaluation_id;
            $note->classe_id = $classe_id; // Utiliser la classe de l'évaluation
            $note->matiere_id = $evaluation->matiere_id; // Ajouter le matiere_id de l'évaluation
            $note->semestre = $semestre; // Utiliser la période de l'évaluation
            $note->note = $isAbsent ? 0 : $request->note;
            $note->is_absent = $isAbsent ? 1 : 0;
            $note->commentaire = $request->commentaire;
            $note->created_by = Auth::id();
            $note->type_evaluation = $evaluation->type; // Ajouter le type d'évaluation
            $note->annee_universitaire = $evaluation->anneeUniversitaire ? $evaluation->anneeUniversitaire->name : 'N/A'; // Ajouter l'année universitaire
            $note->save();

            // Envoyer une notification d'absence si l'étudiant est marqué absent
            if ($note->is_absent) {
                $this->sendAbsenceNotificationForNote($note, $evaluation);
            }

            // Débogage : Log des détails de la note créée
            \Log::info('Note créée', [
                'id' => $note->id,
                'etudiant_id' => $note->etudiant_id,
                'evaluation_id' => $note->evaluation_id,
                'note' => $note->note,
                'is_absent' => $note->is_absent,
                'classe_id' => $note->classe_id,
                'semestre' => $note->semestre,
            ]);

            return redirect()->route('esbtp.notes.index')
                ->with('success', 'Note créée avec succès.');
        } catch (\Exception $e) {
            // Débogage : Log de l'erreur
            \Log::error('Erreur lors de la création de la note : '.$e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la note : '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche une note spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPNote $note)
    {
        $this->authorize('view', $note);

        $note->load(['evaluation.matiere', 'evaluation.classe', 'etudiant', 'createdBy', 'updatedBy']);

        return view('esbtp.notes.show', compact('note'));
    }

    /**
     * Affiche le formulaire de modification d'une note.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPNote $note)
    {
        $this->authorize('update', $note);

        $note->load(['evaluation.matiere', 'evaluation.classe', 'etudiant']);

        return view('esbtp.notes.edit', compact('note'));
    }

    /**
     * Met à jour une note spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPNote $note)
    {
        $this->authorize('update', $note);

        $request->validate([
            'note' => 'required_unless:is_absent,on|numeric|min:0',
            'is_absent' => 'nullable|in:on,1,true',
            'commentaire' => 'nullable|string',
        ]);

        try {
            // Récupérer l'évaluation associée à cette note
            $evaluation = $note->evaluation;

            if (! $evaluation) {
                return redirect()->back()
                    ->with('error', 'Évaluation introuvable pour cette note.')
                    ->withInput();
            }

            // Convertir is_absent en booléen
            $isAbsent = $request->has('is_absent') && in_array($request->is_absent, ['on', '1', 'true', true]);

            // Synchroniser le semestre avec la période de l'évaluation
            $note->semestre = $evaluation->periode;

            // Mettre à jour les autres champs
            $note->note = $isAbsent ? 0 : $request->note;
            $note->is_absent = $isAbsent ? 1 : 0;
            $note->commentaire = $request->commentaire;
            $note->updated_by = Auth::id();
            $note->save();

            // Débogage : Log des détails de la note mise à jour
            \Log::info('Note mise à jour', [
                'id' => $note->id,
                'etudiant_id' => $note->etudiant_id,
                'evaluation_id' => $note->evaluation_id,
                'note' => $note->note,
                'is_absent' => $note->is_absent,
                'semestre' => $note->semestre,
            ]);

            return redirect()->route('esbtp.notes.index')
                ->with('success', 'Note mise à jour avec succès.');
        } catch (\Exception $e) {
            // Débogage : Log de l'erreur
            \Log::error('Erreur lors de la mise à jour de la note : '.$e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la note : '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Sauvegarde (création ou mise à jour) d'une note via AJAX depuis notes.index.
     * Retourne toujours du JSON. Utilise un UPSERT pour éviter le "already exists".
     *
     * Validation déléguée à StoreNoteRequest :
     *   - permissions notes.create / notes.edit / notes.manage_own
     *   - exists evaluation_id / etudiant_id
     *   - note ≤ barème via la rule NoteRespectsBareme
     */
    public function saveNoteAjax(StoreNoteRequest $request)
    {
        try {
            $evaluation = ESBTPEvaluation::findOrFail($request->input('evaluation_id'));

            if (! $evaluation->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette évaluation n'est pas publiée. Activez-la avant de saisir les notes.",
                ], 422);
            }

            $existingNote = ESBTPNote::where('etudiant_id', $request->input('etudiant_id'))
                ->where('evaluation_id', $request->input('evaluation_id'))
                ->first();

            if ($existingNote && ! Auth::user()->can('notes.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => "Vous n'avez pas la permission de modifier les notes déjà enregistrées.",
                ], 403);
            }

            // Transaction explicite : la création/modification d'une note
            // déclenche un Observer (recalcul moyennes) qui peut écrire en
            // cascade. On garde l'atomicité pour éviter les états partiels.
            $result = DB::transaction(function () use ($evaluation, $existingNote, $request) {
                return $this->processNoteEntry($evaluation, $existingNote, [
                    'etudiant_id'   => $request->input('etudiant_id'),
                    'evaluation_id' => $request->input('evaluation_id'),
                    'note'          => $request->input('note'),
                    'is_absent'     => $request->boolean('is_absent'),
                    'commentaire'   => $request->input('commentaire'),
                ]);
            });

            // La notification est envoyée APRÈS commit pour éviter qu'un échec
            // d'envoi ne rollback la note enregistrée.
            if ($result['is_new_absent']) {
                $this->sendAbsenceNotificationForNote($result['note'], $evaluation);
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Note enregistrée avec succès.',
                'note_id'   => $result['note']->id,
                'is_absent' => (bool) $result['note']->is_absent,
                'note'      => $result['note']->note,
            ]);

        } catch (\Exception $e) {
            \Log::error('saveNoteAjax error: ' . $e->getMessage(), [
                'evaluation_id' => $request->input('evaluation_id'),
                'etudiant_id' => $request->input('etudiant_id'),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de l\'enregistrement de la note.',
            ], 500);
        }
    }

    /**
     * Sauvegarde en masse des notes (une seule requête HTTP au lieu d'une par étudiant).
     *
     * Validation déléguée à StoreBulkNotesRequest :
     *   - permissions notes.create / notes.edit / notes.manage_own
     *   - max 500 notes par batch (anti-abus)
     *   - exists evaluation_id / etudiant_id sur chaque entrée
     */
    public function saveNotesAjaxBulk(StoreBulkNotesRequest $request)
    {
        $errors = 0;
        $saved  = 0;
        $notes = $request->input('notes', []);

        DB::beginTransaction();
        try {
            $evalIds = collect($notes)->pluck('evaluation_id')->unique();
            $evaluations = ESBTPEvaluation::whereIn('id', $evalIds)->get()->keyBy('id');

            // Requête tuple-based IN avec cast int pour éviter injection SQL
            $pairs = collect($notes)
                ->map(fn($e) => '(' . (int) $e['etudiant_id'] . ', ' . (int) $e['evaluation_id'] . ')')
                ->implode(',');
            $existingNotes = $pairs
                ? ESBTPNote::whereRaw("(etudiant_id, evaluation_id) IN ({$pairs})")
                    ->get()->keyBy(fn($n) => $n->etudiant_id . '_' . $n->evaluation_id)
                : collect();

            $canEdit = Auth::user()->can('notes.edit');
            $pendingNotifications = [];

            foreach ($notes as $entry) {
                $evaluation = $evaluations->get($entry['evaluation_id']);
                if (! $evaluation || ! $evaluation->is_published) {
                    $errors++;
                    continue;
                }

                // Garde-fou cohérence note ≤ barème (défense en profondeur,
                // déjà couvert pour les saisies unitaires par NoteRespectsBareme).
                $rawNote = $entry['note'] ?? null;
                $isAbsent = (bool) ($entry['is_absent'] ?? false);
                if (! $isAbsent && $rawNote !== null && $rawNote !== '') {
                    $bareme = (float) $evaluation->bareme;
                    if ($bareme <= 0 || (float) $rawNote > $bareme) {
                        $errors++;
                        continue;
                    }
                }

                $key  = $entry['etudiant_id'] . '_' . $entry['evaluation_id'];
                $note = $existingNotes->get($key);

                if ($note && ! $canEdit) {
                    $errors++;
                    continue;
                }

                $result = $this->processNoteEntry($evaluation, $note, $entry);

                if ($result['is_new_absent']) {
                    $pendingNotifications[] = [$result['note'], $evaluation];
                }
                $saved++;
            }

            DB::commit();

            // Envoyer les notifications après le commit (évite rollback en cascade)
            foreach ($pendingNotifications as [$note, $evaluation]) {
                $this->sendAbsenceNotificationForNote($note, $evaluation);
            }

            return response()->json([
                'success' => $errors === 0,
                'saved'   => $saved,
                'errors'  => $errors,
                'total'   => count($notes),
                'message' => $errors === 0
                    ? "{$saved} note(s) enregistrée(s) avec succès."
                    : "{$saved} enregistrée(s), {$errors} erreur(s).",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('saveNotesAjaxBulk error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'count' => count($notes),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de l\'enregistrement des notes.',
            ], 500);
        }
    }

    /**
     * Logique partagée : crée ou met à jour une note pour un étudiant/évaluation.
     */
    private function processNoteEntry(ESBTPEvaluation $evaluation, ?ESBTPNote $existingNote, array $entry): array
    {
        $isAbsent = in_array($entry['is_absent'] ?? '', ['on', '1', 'true', true], true);
        $isNew = false;

        if (! $existingNote) {
            $isNew = true;
            $existingNote = new ESBTPNote;
            $existingNote->etudiant_id        = $entry['etudiant_id'];
            $existingNote->evaluation_id      = $entry['evaluation_id'];
            $existingNote->classe_id          = $evaluation->classe_id;
            $existingNote->matiere_id         = $evaluation->matiere_id;
            $existingNote->semestre           = $evaluation->periode;
            $existingNote->annee_universitaire = $evaluation->anneeUniversitaire
                ? $evaluation->anneeUniversitaire->name : 'N/A';
            $existingNote->type_evaluation    = $evaluation->type;
            $existingNote->created_by         = Auth::id();
        } else {
            $existingNote->semestre = $evaluation->periode;
        }

        $existingNote->note       = $isAbsent ? 0 : (float) ($entry['note'] ?? 0);
        $existingNote->is_absent  = $isAbsent ? 1 : 0;
        $existingNote->updated_by = Auth::id();
        if (isset($entry['commentaire'])) {
            $existingNote->commentaire = $entry['commentaire'];
        }
        $existingNote->save();

        return ['note' => $existingNote, 'is_new_absent' => $isAbsent && $isNew];
    }

    /**
     * Supprime une note spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPNote $note)
    {
        try {
            $note->delete();

            return redirect()->route('esbtp.notes.index')->with('success', 'Note supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression de la note: '.$e->getMessage());
        }
    }

    /**
     * Affiche la page de saisie rapide des notes pour une évaluation.
     *
     * @return \Illuminate\Http\Response
     */
    public function saisieRapide(ESBTPEvaluation $evaluation)
    {
        $user = Auth::user();
        $evaluation->load(['classe', 'matiere', 'notes.etudiant']);

        if ($evaluation->date_evaluation && $evaluation->date_evaluation->isFuture()) {
            return redirect()
                ->route('esbtp.evaluations.show', $evaluation)
                ->with('error', "La saisie des notes est disponible uniquement après la date d'évaluation.");
        }

        if ($user->can('identity.teach') && $user->can('notes.manage_own')) {
            $isOwner = $evaluation->enseignant_id === $user->id || $evaluation->created_by === $user->id;
            if (! $isOwner) {
                return redirect()
                    ->route('teacher.grades')
                    ->with('error', "Vous n'êtes pas autorisé à saisir les notes pour cette évaluation.");
            }
        }

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer uniquement les étudiants avec inscriptions actives sur l'année courante
        // ET workflow_step = etudiant_cree (exclut les pré-inscriptions / prospects).
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($evaluation, $anneeCourante) {
            $query->where('classe_id', $evaluation->classe_id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree');
            if ($anneeCourante) {
                $query->where('annee_universitaire_id', $anneeCourante->id);
            }
        })
            ->with(['notes' => function ($query) use ($evaluation) {
                $query->where('evaluation_id', $evaluation->id);
            }])
            ->orderBy('nom')
            ->get();

        // Récupérer uniquement les notes des étudiants de l'année courante pour cette évaluation
        $etudiantsIds = $etudiants->pluck('id');
        $notes = $evaluation->notes->whereIn('etudiant_id', $etudiantsIds);

        return view('esbtp.notes.saisie-rapide', compact('evaluation', 'etudiants', 'notes'));
    }

    /**
     * Télécharge le PDF de saisie rapide d'une évaluation (attachment).
     */
    public function saisieRapidePDF(ESBTPEvaluation $evaluation)
    {
        [$pdf, $filename] = $this->buildSaisieRapidePdf($evaluation);

        return $pdf->download($filename);
    }

    /**
     * Aperçu inline du PDF de saisie rapide d'une évaluation.
     */
    public function saisieRapidePDFPreview(ESBTPEvaluation $evaluation)
    {
        [$pdf, $filename] = $this->buildSaisieRapidePdf($evaluation);

        return $pdf->stream($filename);
    }

    /**
     * Construit le PDF de saisie rapide pour une évaluation. Retourne [PDF, filename].
     */
    private function buildSaisieRapidePdf(ESBTPEvaluation $evaluation): array
    {
        $evaluation->load(['classe', 'matiere']);

        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($evaluation, $anneeCourante) {
            $query->where('classe_id', $evaluation->classe_id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree');
            if ($anneeCourante) {
                $query->where('annee_universitaire_id', $anneeCourante->id);
            }
        })
            ->orderBy('nom')
            ->get();

        $etablissement = [
            'nom' => \App\Models\Setting::get('school_name', 'KLASSCI'),
            'adresse' => \App\Models\Setting::get('school_address', ''),
            'telephone' => \App\Models\Setting::get('school_phone', ''),
            'email' => \App\Models\Setting::get('school_email', ''),
            'logo' => \App\Models\Setting::get('school_logo', ''),
        ];

        $notesByEtudiant = collect();
        $isBlank = true;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.notes.saisie-rapide-pdf', compact('evaluation', 'etudiants', 'anneeCourante', 'etablissement', 'notesByEtudiant', 'isBlank'));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'saisie-notes-'.\Illuminate\Support\Str::slug($evaluation->titre).'-'.date('Y-m-d').'.pdf';

        return [$pdf, $filename];
    }

    /**
     * Télécharge le PDF de saisie rapide vierge pour une classe (attachment).
     */
    public function saisieRapideBlankPDF(ESBTPClasse $classe)
    {
        [$pdf, $filename] = $this->buildSaisieRapideBlankPdf($classe);

        return $pdf->download($filename);
    }

    /**
     * Aperçu inline du PDF de saisie rapide vierge pour une classe.
     */
    public function saisieRapideBlankPDFPreview(ESBTPClasse $classe)
    {
        [$pdf, $filename] = $this->buildSaisieRapideBlankPdf($classe);

        return $pdf->stream($filename);
    }

    /**
     * Construit le PDF vierge de saisie rapide pour une classe. Retourne [PDF, filename].
     */
    private function buildSaisieRapideBlankPdf(ESBTPClasse $classe): array
    {
        $classe->load(['filiere']);

        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe, $anneeCourante) {
            $query->where('classe_id', $classe->id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree');
            if ($anneeCourante) {
                $query->where('annee_universitaire_id', $anneeCourante->id);
            }
        })
            ->orderBy('nom')
            ->get();

        $etablissement = [
            'nom' => \App\Models\Setting::get('school_name', 'KLASSCI'),
            'adresse' => \App\Models\Setting::get('school_address', ''),
            'telephone' => \App\Models\Setting::get('school_phone', ''),
            'email' => \App\Models\Setting::get('school_email', ''),
            'logo' => \App\Models\Setting::get('school_logo', ''),
        ];

        $evaluation = (object) [
            'titre' => '',
            'matiere' => (object) ['name' => ''],
            'classe' => $classe,
            'type' => '',
            'coefficient' => '',
            'bareme' => '',
            'date_evaluation' => null,
            'duree_minutes' => null,
        ];

        $notesByEtudiant = collect();
        $isBlank = true;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.notes.saisie-rapide-pdf', compact('evaluation', 'etudiants', 'anneeCourante', 'etablissement', 'notesByEtudiant', 'isBlank'));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'saisie-notes-'.\Illuminate\Support\Str::slug($classe->name ?? 'classe').'-'.date('Y-m-d').'.pdf';

        return [$pdf, $filename];
    }

    /**
     * Enregistre les notes saisies en masse pour une évaluation.
     *
     * @return \Illuminate\Http\Response
     */
    public function enregistrerSaisieRapide(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'notes.*.valeur' => 'required_without:notes.*.absent|nullable|numeric|min:0',
            'notes.*.commentaire' => 'nullable|string',
            'notes.*.absent' => 'nullable|boolean',
        ], [
            'notes.*.valeur.required_without' => 'La valeur de la note est obligatoire si l\'étudiant n\'est pas absent',
            'notes.*.valeur.numeric' => 'La valeur doit être un nombre',
            'notes.*.valeur.min' => 'La valeur doit être positive',
        ]);

        $evaluation = ESBTPEvaluation::findOrFail($request->evaluation_id);
        $user = Auth::user();

        if ($user->can('identity.teach') && $user->can('notes.manage_own')) {
            $isOwner = $evaluation->enseignant_id === $user->id || $evaluation->created_by === $user->id;
            if (! $isOwner) {
                return redirect()->back()
                    ->with('error', "Vous n'êtes pas autorisé à modifier les notes pour cette évaluation.")
                    ->withInput();
            }
        }

        if ($evaluation->date_evaluation && $evaluation->date_evaluation->isFuture()) {
            return redirect()->back()
                ->with('error', "La saisie des notes est disponible uniquement après la date d'évaluation.")
                ->withInput();
        }

        // Vérifier si l'utilisateur a le droit de modifier les notes existantes
        if (! $user->can('notes.edit')) {
            $existingNotesCount = ESBTPNote::where('evaluation_id', $evaluation->id)->count();

            if ($existingNotesCount > 0) {
                return redirect()->back()
                    ->with('error', "Vous n'avez pas la permission de modifier les notes déjà enregistrées. Vous pouvez seulement ajouter des notes si aucune n'existe encore.")
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->notes as $noteData) {
                // Vérifier si nous avons une valeur de note ou si l'étudiant est marqué comme absent
                $hasValue = isset($noteData['valeur']) && $noteData['valeur'] !== null && $noteData['valeur'] !== '';
                $isAbsent = isset($noteData['absent']) && $noteData['absent'] == '1';

                // Ignorer les entrées sans valeur et non marquées comme absentes
                if (! $hasValue && ! $isAbsent) {
                    continue;
                }

                $etudiantId = $noteData['etudiant_id'];

                // Vérifier si l'étudiant a déjà une note pour cette évaluation
                $note = ESBTPNote::where('evaluation_id', $evaluation->id)
                    ->where('etudiant_id', $etudiantId)
                    ->first();

                if ($note) {
                    // Mise à jour de la note existante
                    $wasAbsent = $note->is_absent;
                    $note->note = $isAbsent ? 0 : $noteData['valeur'];
                    $note->is_absent = $isAbsent;
                    $note->commentaire = $noteData['commentaire'] ?? null;
                    $note->updated_by = Auth::id();

                    // S'assurer que tous les champs requis sont définis
                    if (! $note->matiere_id) {
                        $note->matiere_id = $evaluation->matiere_id;
                    }
                    if (! $note->classe_id) {
                        $note->classe_id = $evaluation->classe_id;
                    }
                    if (! $note->semestre) {
                        $note->semestre = $evaluation->periode;
                    }
                    if (! $note->annee_universitaire) {
                        $note->annee_universitaire = $evaluation->anneeUniversitaire ? $evaluation->anneeUniversitaire->name : 'N/A';
                    }
                    if (! $note->type_evaluation) {
                        $note->type_evaluation = $evaluation->type;
                    }

                    $note->save();

                    // Envoyer une notification d'absence si l'étudiant vient d'être marqué absent
                    if ($isAbsent && ! $wasAbsent) {
                        $this->sendAbsenceNotificationForNote($note, $evaluation);
                    }
                } else {
                    // Création d'une nouvelle note
                    $note = new ESBTPNote;
                    $note->evaluation_id = $evaluation->id;
                    $note->etudiant_id = $etudiantId;
                    $note->matiere_id = $evaluation->matiere_id;
                    $note->classe_id = $evaluation->classe_id;
                    $note->semestre = $evaluation->periode;
                    $note->annee_universitaire = $evaluation->anneeUniversitaire ? $evaluation->anneeUniversitaire->name : 'N/A';
                    $note->note = $isAbsent ? 0 : $noteData['valeur'];
                    $note->type_evaluation = $evaluation->type;
                    $note->is_absent = $isAbsent;
                    $note->commentaire = $noteData['commentaire'] ?? null;
                    $note->created_by = Auth::id();
                    $note->save();

                    // Envoyer une notification d'absence si l'étudiant est marqué absent
                    if ($isAbsent) {
                        $this->sendAbsenceNotificationForNote($note, $evaluation);
                    }
                }
            }

            DB::commit();

            return redirect()->route('esbtp.evaluations.show', $evaluation)
                ->with('success', 'Les notes ont été enregistrées avec succès');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des notes: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les notes de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentGrades(Request $request)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (! $etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Vérifier si l'étudiant a une inscription active pour l'année courante
        $inscription = null;
        if ($anneeCourante) {
            $inscription = $etudiant->inscriptions()
                ->where('status', 'active')
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
                ->first();
        }

        if (! $inscription) {
            return view('esbtp.etudiants.notes', [
                'notes' => collect([]),
                'etudiant' => $etudiant,
                'inscription' => null,
                'anneeCourante' => $anneeCourante,
            ])->with('warning', 'Vous n\'avez pas d\'inscription active pour l\'année en cours. Veuillez contacter l\'administration.');
        }

        // Récupérer les notes de l'année courante uniquement
        $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
            ->whereHas('evaluation', function ($query) use ($anneeCourante) {
                $query->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->with(['evaluation', 'matiere'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('esbtp.etudiants.notes', compact('notes', 'etudiant', 'inscription', 'anneeCourante'));
    }

    /**
     * Affiche le formulaire de saisie rapide des notes.
     *
     * @return \Illuminate\Http\Response
     */
    public function saisieRapideForm()
    {
        return view('esbtp.notes.saisie-rapide-form');
    }

    /**
     * Envoie une notification d'absence à un étudiant lors de la saisie des notes
     *
     * @return void
     */
    private function sendAbsenceNotificationForNote(ESBTPNote $note, ESBTPEvaluation $evaluation)
    {
        try {
            // Charger l'étudiant avec sa relation user
            $etudiant = ESBTPEtudiant::with('user')->find($note->etudiant_id);

            // S'assurer que l'étudiant existe et a un compte utilisateur
            if (! $etudiant || ! $etudiant->user) {
                \Log::warning("Impossible d'envoyer la notification d'absence pour la note: étudiant ou utilisateur non trouvé", [
                    'etudiant_id' => $note->etudiant_id,
                    'note_id' => $note->id,
                ]);

                return;
            }

            // Charger la matière associée à l'évaluation
            $matiere = $evaluation->matiere;
            $matiereName = $matiere ? $matiere->name : 'Matière non définie';

            // Formater la date et l'heure
            $dateEvaluation = $evaluation->date_evaluation ? \Carbon\Carbon::parse($evaluation->date_evaluation) : \Carbon\Carbon::now();
            $jourSemaine = $dateEvaluation->locale('fr')->dayName;
            $dateFormatee = $dateEvaluation->format('d/m/Y');
            $heureFormatee = $evaluation->heure_debut ? $evaluation->heure_debut : 'Heure non définie';

            // Déterminer le type d'activité
            $typeActivite = 'Évaluation';
            $typeEvaluation = ucfirst($evaluation->type ?? 'évaluation');

            // Créer un message détaillé
            $messageDetail = sprintf(
                "Absence lors d'une %s (%s)\n".
                "Matière: %s\n".
                "Date: %s (%s)\n".
                "Heure: %s\n".
                'Titre: %s',
                strtolower($typeActivite),
                $typeEvaluation,
                $matiereName,
                $dateFormatee,
                ucfirst($jourSemaine),
                $heureFormatee,
                $evaluation->titre ?? 'Sans titre'
            );

            // Créer une entrée d'absence temporaire pour la notification avec informations enrichies
            $absence = new \App\Models\ESBTPAttendance;
            $absence->date = $dateEvaluation;
            $absence->etudiant_id = $note->etudiant_id;
            $absence->statut = 'absent';
            $absence->commentaire = $messageDetail;
            $absence->matiere_id = $evaluation->matiere_id;
            $absence->type_activite = 'evaluation';
            $absence->heure_debut = $evaluation->heure_debut;
            $absence->heure_fin = $evaluation->heure_fin;

            // Utiliser le service de notifications
            $this->notificationService->notifyNewAbsence($absence, $etudiant);

            \Log::info("Notification d'absence enrichie envoyée pour la note", [
                'etudiant_id' => $note->etudiant_id,
                'note_id' => $note->id,
                'evaluation_id' => $evaluation->id,
                'matiere' => $matiereName,
                'date' => $dateFormatee,
                'jour' => $jourSemaine,
                'heure' => $heureFormatee,
                'type' => $typeEvaluation,
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de la notification d'absence pour la note", [
                'etudiant_id' => $note->etudiant_id,
                'note_id' => $note->id,
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // PR #7 — Excel import/export bidirectionnel + preview impact bulletin
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Export Excel des notes d'une classe + matière + période.
     * Format optimisé pour saisie offline puis ré-import.
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->can('notes.view')) {
            abort(403, "Vous n'avez pas la permission d'exporter les notes.");
        }

        $validator = \Validator::make($request->all(), [
            'classe' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'matiere' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $classeId = (int) $request->input('classe');
        $matiereId = (int) $request->input('matiere');
        $periode = (string) $request->input('periode');
        $anneeId = $request->filled('annee_universitaire_id')
            ? (int) $request->input('annee_universitaire_id')
            : null;

        $export = new NotesClasseMatiereExport($classeId, $matiereId, $periode, $anneeId);

        if ($export->cellsCount() > 5000) {
            return response()->json([
                'success' => false,
                'message' => 'Volume trop important : '
                    . $export->studentsCount() . ' étudiants × '
                    . $export->evaluationsCount() . ' évaluations dépasse la limite de 5000 cellules. Affinez les filtres.',
            ], 422);
        }

        $classeSlug = Str::slug($export->classeName() ?? ('classe-' . $classeId));
        $matiereSlug = Str::slug($export->matiereName() ?? ('matiere-' . $matiereId));
        $filename = sprintf('notes_%s_%s_%s_%s.xlsx', $classeSlug, $matiereSlug, $periode, now()->format('Ymd-His'));

        return Excel::download($export, $filename);
    }

    /**
     * Dry-run : analyse l'import Excel et retourne le diff Avant/Après sans persister.
     */
    public function importDryRun(ImportNotesRequest $request, NotesImportService $service)
    {
        try {
            $file = $request->file('file');
            $parsed = $service->parseFile($file);

            $diff = $service->dryRun(
                $parsed,
                (int) $request->input('classe_id'),
                (int) $request->input('matiere_id'),
                (string) $request->input('periode'),
                $request->filled('annee_universitaire_id')
                    ? (int) $request->input('annee_universitaire_id')
                    : null
            );

            return response()->json([
                'success' => true,
                'summary' => $diff['summary'],
                'changes' => $diff['changes'],
                'errors' => $diff['errors'],
                'evaluations' => $diff['evaluations'],
                'parsed_signature' => $this->parsedSignature($parsed),
            ]);
        } catch (\Throwable $e) {
            \Log::error('importDryRun error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de lire le fichier Excel : ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Applique l'import Excel (transaction).
     */
    public function importApply(ImportNotesRequest $request, NotesImportService $service)
    {
        try {
            $file = $request->file('file');
            $parsed = $service->parseFile($file);

            $result = $service->apply(
                $parsed,
                (int) $request->input('classe_id'),
                (int) $request->input('matiere_id'),
                (string) $request->input('periode'),
                $request->filled('annee_universitaire_id')
                    ? (int) $request->input('annee_universitaire_id')
                    : null
            );

            if (($result['errors'] ?? 0) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import bloqué : ' . $result['errors'] . ' erreur(s) détectée(s). Corrigez le fichier puis réessayez.',
                    'errors' => $result['error_details'] ?? [],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'created' => $result['created'],
                'updated' => $result['updated'],
                'message' => sprintf('Import réussi : %d note(s) créée(s), %d mise(s) à jour.', $result['created'], $result['updated']),
            ]);
        } catch (\Throwable $e) {
            \Log::error('importApply error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'application de l\'import : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcule l'impact d'une note hypothétique sur la moyenne matière + générale.
     * Utilisé par l'UI temps réel sous chaque ligne étudiant.
     */
    public function previewImpact(Request $request, NoteCalculationService $calc)
    {
        $user = Auth::user();
        if (! $user || (! $user->can('notes.view') && ! $user->can('notes.view_own') && ! $user->can('notes.create') && ! $user->can('notes.edit'))) {
            return response()->json(['success' => false, 'message' => 'Permission refusée.'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'matiere_id' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
            'evaluation_id' => ['required', 'integer', 'exists:esbtp_evaluations,id'],
            'hypothetical_note' => ['required', 'numeric', 'min:0'],
            'is_absent' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $etudiantId = (int) $request->input('etudiant_id');
        $classeId = (int) $request->input('classe_id');
        $matiereId = (int) $request->input('matiere_id');
        $periode = (string) $request->input('periode');
        $evaluationId = (int) $request->input('evaluation_id');
        $hypoValue = (float) $request->input('hypothetical_note');
        $isAbsent = (bool) $request->input('is_absent', false);

        // Évaluation cible
        $targetEval = ESBTPEvaluation::find($evaluationId);
        if (! $targetEval) {
            return response()->json(['success' => false, 'message' => 'Évaluation introuvable.'], 404);
        }

        // Validation barème
        $bareme = max(0.01, (float) $targetEval->bareme);
        if (! $isAbsent && $hypoValue > $bareme) {
            return response()->json([
                'success' => false,
                'message' => sprintf('Note %s hors barème (max %s).', $hypoValue, $bareme),
            ], 422);
        }

        // Toutes les évaluations de la matière + période
        $evaluations = ESBTPEvaluation::query()
            ->where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->where('periode', $periode)
            ->where('is_published', 1)
            ->get();

        // Notes existantes pour cet étudiant sur ces évaluations
        $notes = ESBTPNote::where('etudiant_id', $etudiantId)
            ->whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get()
            ->keyBy('evaluation_id');

        // Moyenne matière AVANT
        $moyenneMatiereAvant = $this->computeMatiereAverage($calc, $evaluations, $notes, null, null);

        // Moyenne matière APRÈS (en remplaçant la note de cette éval)
        $moyenneMatiereApres = $this->computeMatiereAverage($calc, $evaluations, $notes, $evaluationId, [
            'note' => $hypoValue,
            'is_absent' => $isAbsent,
        ]);

        $mentionAvant = $moyenneMatiereAvant !== null ? $calc->getMention($moyenneMatiereAvant) : null;
        $mentionApres = $moyenneMatiereApres !== null ? $calc->getMention($moyenneMatiereApres) : null;

        // Moyenne générale (toutes matières confondues, période)
        $moyenneGeneraleAvant = $this->computeGeneralAverage($calc, $etudiantId, $classeId, $periode, null, null, null);
        $moyenneGeneraleApres = $this->computeGeneralAverage($calc, $etudiantId, $classeId, $periode, $matiereId, $evaluationId, [
            'note' => $hypoValue,
            'is_absent' => $isAbsent,
        ]);

        return response()->json([
            'success' => true,
            'matiere_avant' => $moyenneMatiereAvant,
            'matiere_apres' => $moyenneMatiereApres,
            'mention_avant' => $mentionAvant,
            'mention_apres' => $mentionApres,
            'moyenne_generale_avant' => $moyenneGeneraleAvant,
            'moyenne_generale_apres' => $moyenneGeneraleApres,
            'changed_mention' => $mentionAvant !== $mentionApres,
        ]);
    }

    /**
     * Construit le tableau de notes (note/bareme/coefficient/is_absent) à partir d'une
     * collection d'évaluations + notes existantes, en appliquant un override hypothétique
     * sur une évaluation cible si fourni.
     *
     * Délègue ensuite le calcul à NoteCalculationService::studentMatiereAverage()
     * pour garantir la cohérence avec BTS / LMD / bulletins.
     *
     * @param  \Illuminate\Support\Collection  $evaluations
     * @param  \Illuminate\Support\Collection  $notesByEvalId  (keyBy evaluation_id)
     * @param  array{note: float, is_absent: bool}|null  $override
     * @return float|null  null si aucune note exploitable (UI = "—"), sinon moyenne sur 20.
     */
    private function computeMatiereAverage(
        NoteCalculationService $calc,
        $evaluations,
        $notesByEvalId,
        ?int $overrideEvalId = null,
        ?array $override = null
    ): ?float {
        $payload = [];

        foreach ($evaluations as $eval) {
            $bareme = max(0.01, (float) ($eval->bareme ?? 20));
            $coef = (float) ($eval->coefficient ?? 1);

            if ($overrideEvalId !== null && $eval->id === $overrideEvalId && $override !== null) {
                $payload[] = [
                    'note' => (float) $override['note'],
                    'bareme' => $bareme,
                    'coefficient' => $coef,
                    'is_absent' => (bool) $override['is_absent'],
                ];
                continue;
            }

            $note = $notesByEvalId->get($eval->id);
            if (! $note) {
                continue;
            }
            $payload[] = [
                'note' => (float) ($note->note ?? 0),
                'bareme' => $bareme,
                'coefficient' => $coef,
                'is_absent' => (bool) $note->is_absent,
            ];
        }

        // Aucune note exploitable (toutes vides ou absentes) → null pour distinguer "vide" de "0".
        $hasUsableNote = false;
        foreach ($payload as $row) {
            if (empty($row['is_absent']) && $row['bareme'] > 0 && $row['coefficient'] > 0) {
                $hasUsableNote = true;
                break;
            }
        }
        if (! $hasUsableNote) {
            return null;
        }

        return $calc->studentMatiereAverage($payload);
    }

    /**
     * Calcule la moyenne générale pondérée (par coefficient matière) d'un étudiant
     * pour une période donnée.
     *
     * Si $overrideMatiereId + $overrideEvalId fournis, recalcule la matière concernée
     * en remplaçant la note. Délègue à NoteCalculationService.
     */
    private function computeGeneralAverage(
        NoteCalculationService $calc,
        int $etudiantId,
        int $classeId,
        string $periode,
        ?int $overrideMatiereId,
        ?int $overrideEvalId,
        ?array $override
    ): ?float {
        $evaluations = ESBTPEvaluation::query()
            ->where('classe_id', $classeId)
            ->where('periode', $periode)
            ->where('is_published', 1)
            ->get();

        if ($evaluations->isEmpty()) {
            return null;
        }

        $matiereIds = $evaluations->pluck('matiere_id')->unique()->values()->all();

        $matiereCoefs = ESBTPMatiere::whereIn('id', $matiereIds)
            ->get(['id', 'coefficient'])
            ->mapWithKeys(fn ($m) => [$m->id => max(0.01, (float) ($m->coefficient ?? 1))]);

        $notes = ESBTPNote::where('etudiant_id', $etudiantId)
            ->whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get();

        $byMatiere = $evaluations->groupBy('matiere_id');

        $matieresPayload = [];

        foreach ($byMatiere as $matiereId => $evalsMat) {
            $notesMat = $notes->where('matiere_id', $matiereId)->keyBy('evaluation_id');

            $isOverridden = ($overrideMatiereId === (int) $matiereId);
            $moyMat = $this->computeMatiereAverage(
                $calc,
                $evalsMat,
                $notesMat,
                $isOverridden ? $overrideEvalId : null,
                $isOverridden ? $override : null
            );

            if ($moyMat === null) {
                continue;
            }

            $matieresPayload[] = [
                'moyenne' => $moyMat,
                'coefficient' => $matiereCoefs->get($matiereId, 1.0),
            ];
        }

        if (empty($matieresPayload)) {
            return null;
        }

        return $calc->studentGeneralAverage($matieresPayload);
    }

    /**
     * Petit hash de la signature du fichier parsé pour idempotence (debug).
     */
    private function parsedSignature(array $parsed): string
    {
        return substr(md5(json_encode([
            'rows_count' => count($parsed['rows'] ?? []),
            'has_meta' => isset($parsed['meta']),
        ])), 0, 12);
    }
}
