<?php

namespace App\Http\Controllers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Domain\AcademicPilotage\Services\GradeSheetNoteMutationGuard;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ESBTPLMDNoteController extends Controller
{
    public function __construct(private readonly GradeSheetNoteMutationGuard $noteMutationGuard) {}

    /**
     * Liste des classes LMD avec leurs UEs/ECUEs pour la saisie de notes.
     */
    public function index(Request $request)
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId = $anneeCourante?->id;

        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->where('is_active', true)
            ->with(['filiere', 'niveau'])
            ->withCount([
                'inscriptions as etudiants_count' => fn ($q) => $q
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->when($anneeId, fn ($q2, $id) => $q2->where('annee_universitaire_id', $id)),
            ])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->get();

        // Nombre d'évaluations par classe (uniquement celles liées à des ECUEs LMD)
        $evalCounts = ESBTPEvaluation::whereHas('classe', fn ($q) => $q->where('systeme_academique', 'LMD'))
            ->whereHas('matiere', fn ($q) => $q->whereNotNull('unite_enseignement_id'))
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->select('classe_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('classe_id')
            ->pluck('total', 'classe_id');

        return view('esbtp.lmd.notes.index', compact('classes', 'evalCounts', 'anneeCourante'));
    }

    /**
     * Données JSON d'une classe pour le modal de gestion de notes.
     */
    public function classeData(ESBTPClasse $classe)
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Étudiants actifs de cette classe (même pattern que classes.show)
        $etudiants = $classe->inscriptions()
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->when($anneeCourante, fn ($q) => $q->where('annee_universitaire_id', $anneeCourante->id))
            ->with('etudiant:id,nom,prenoms,matricule')
            ->get()
            ->map(fn ($i) => $i->etudiant)
            ->filter()
            ->sortBy('nom')
            ->values();

        // Évaluations de cette classe (uniquement celles liées à des ECUEs LMD)
        $evaluations = ESBTPEvaluation::where('classe_id', $classe->id)
            ->whereHas('matiere', fn ($q) => $q->whereNotNull('unite_enseignement_id'))
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->with(['matiere:id,name,code,unite_enseignement_id', 'matiere.uniteEnseignement:id,name,code'])
            ->withCount('notes')
            ->orderByDesc('date_evaluation')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'titre' => $e->titre ?? $e->type_evaluation,
                'type' => $e->type_evaluation,
                'date' => $e->date_evaluation?->format('d/m/Y'),
                'matiere' => $e->matiere?->name,
                'matiere_code' => $e->matiere?->code_affiche,
                'ue' => $e->matiere?->uniteEnseignement?->name,
                'ue_code' => $e->matiere?->uniteEnseignement?->code_affiche,
                'status' => $e->status,
                'notes_count' => $e->notes_count,
                'saisie_url' => route('esbtp.lmd.notes.saisie', $e),
            ]);

        // Matières (ECUEs) disponibles pour cette classe via parcours + filtrées par semestres du niveau
        $matieres = collect();
        $uesDisponibles = collect();
        if ($classe->parcours) {
            $semestresAutorises = $classe->getSemestresLMD();
            $uesDisponibles = $classe->parcours->unitesEnseignement()
                ->wherePivotIn('semestre', $semestresAutorises)
                ->with(['matieres' => fn ($q) => $q->where('is_active', true)->orderBy('ordre_bulletin')->orderBy('code')])
                ->get()
                ->unique('id')
                ->values();

            $matieres = $uesDisponibles
                ->flatMap(fn ($ue) => $ue->matieres->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'code' => $m->code,
                    'ue_name' => $ue->name,
                    'ue_code' => $ue->code_affiche,
                ]))
                ->unique('id')
                ->values();
        }

        return response()->json([
            'classe' => [
                'id' => $classe->id,
                'name' => $classe->name,
                'filiere' => $classe->filiere?->name,
                'niveau' => $classe->niveau?->name,
                'semestres' => $classe->getSemestresLMD(),
            ],
            'etudiants' => $etudiants,
            'evaluations' => $evaluations,
            'matieres' => $matieres,
            'ues' => $uesDisponibles->map(fn ($ue) => [
                'id' => $ue->id,
                'name' => $ue->name,
                'code' => $ue->code_affiche,
                'semestre' => $ue->pivot->semestre ?? $ue->semestre,
                'ecues_count' => $ue->matieres->count(),
            ]),
        ]);
    }

    /**
     * Saisie rapide de notes pour une evaluation (meme pattern que BTS).
     */
    public function saisieRapide(ESBTPEvaluation $evaluation)
    {
        $this->assertEvaluationConfieeAEnseignant($evaluation);

        $evaluation->load([
            'classe.inscriptions' => fn ($q) => $q->where('status', 'active')->where('workflow_step', 'etudiant_cree'),
            'classe.inscriptions.etudiant',
            'matiere.uniteEnseignement',
        ]);

        $etudiants = $evaluation->classe->inscriptions
            ->map(fn ($i) => $i->etudiant)
            ->filter()
            ->sortBy('nom');

        // Charger les notes existantes (1 seule requete)
        $existingNotes = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->get(['etudiant_id', 'note', 'is_absent']);
        $notesExistantes = $existingNotes->pluck('note', 'etudiant_id')->toArray();
        $absencesExistantes = $existingNotes->pluck('is_absent', 'etudiant_id')->toArray();

        return view('esbtp.lmd.notes.saisie-rapide', compact(
            'evaluation', 'etudiants', 'notesExistantes', 'absencesExistantes'
        ));
    }

    /**
     * Enregistrer les notes en masse.
     */
    public function saveBulk(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0',
            'notes.*.is_absent' => 'nullable|boolean',
        ]);

        $evaluation = ESBTPEvaluation::findOrFail($request->evaluation_id);
        $evaluation->loadMissing('classe');
        abort_unless($evaluation->classe?->systeme_academique === 'LMD', 422, 'La saisie groupée est réservée aux classes LMD.');
        $this->assertEvaluationConfieeAEnseignant($evaluation);

        $studentIds = DB::transaction(function () use ($request, $evaluation): array {
            $notes = collect($request->notes)
                ->filter(fn ($note) => ($note['note'] ?? null) !== null || ! empty($note['is_absent']))
                ->values();
            $studentIds = $notes->pluck('etudiant_id')->map(fn ($studentId): int => (int) $studentId);

            abort_if($studentIds->count() !== $studentIds->unique()->count(), 422, 'Un étudiant ne peut figurer qu’une fois dans une saisie groupée.');

            $this->noteMutationGuard->assertEvaluationMutable((int) $evaluation->id, true);

            $activeStudentIds = ESBTPInscription::query()
                ->where('classe_id', $evaluation->classe_id)
                ->where('annee_universitaire_id', $evaluation->annee_universitaire_id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->whereIn('etudiant_id', $studentIds)
                ->lockForUpdate()
                ->pluck('etudiant_id')
                ->map(fn ($studentId): int => (int) $studentId);
            abort_if($studentIds->diff($activeStudentIds)->isNotEmpty(), 422, 'Tous les étudiants doivent avoir une inscription active dans la classe et l’année de l’évaluation.');

            $existingStudentIds = ESBTPNote::query()
                ->where('evaluation_id', $evaluation->id)
                ->whereIn('etudiant_id', $studentIds)
                ->lockForUpdate()
                ->pluck('etudiant_id')
                ->map(fn ($studentId): int => (int) $studentId);
            abort_unless($studentIds->diff($existingStudentIds)->isEmpty() || auth()->user()?->can('notes.create'), 403);
            abort_unless($existingStudentIds->isEmpty() || auth()->user()?->can('notes.edit'), 403);

            $rows = $notes
                ->map(fn ($n) => [
                    'evaluation_id' => $evaluation->id,
                    'etudiant_id' => $n['etudiant_id'],
                    'matiere_id' => $evaluation->matiere_id,
                    'classe_id' => $evaluation->classe_id,
                    'note' => ($n['is_absent'] ?? false) ? 0 : ($n['note'] ?? 0),
                    'is_absent' => $n['is_absent'] ?? false,
                    'semestre' => $evaluation->periode,
                    'commentaire' => $n['commentaire'] ?? null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->values()->toArray();

            if (! empty($rows)) {
                ESBTPNote::upsert(
                    $rows,
                    ['evaluation_id', 'etudiant_id'],
                    ['note', 'is_absent', 'semestre', 'commentaire', 'updated_by', 'updated_at']
                );
            }

            return collect($rows)
                ->pluck('etudiant_id')
                ->map(fn ($studentId): int => (int) $studentId)
                ->filter(fn (int $studentId): bool => $studentId > 0)
                ->unique()
                ->values()
                ->all();
        });

        $baseContext = [
            'classId' => (int) $evaluation->classe_id,
            'academicYearId' => (int) $evaluation->annee_universitaire_id,
            'period' => (string) $evaluation->periode,
        ];
        app(AcademicMetricSnapshotInvalidationService::class)->invalidateManyAfterCommit(
            $this->bulkNoteInvalidationContexts($baseContext, $studentIds),
            'lmd_notes_bulk_upsert',
        );

        return redirect()->route('esbtp.lmd.notes.index')
            ->with('success', 'Notes enregistrées avec succès pour '.count($request->notes).' étudiants.');
    }

    /**
     * Un enseignant ne saisit que les notes des évaluations qui lui sont confiées.
     *
     * `lmd.notes.manage` ouvre la saisie groupée, mais ne dit rien de la classe ni
     * de la matière : sans ce contrôle, tout titulaire de la permission pourrait
     * noter n'importe quelle évaluation LMD de l'établissement. Les gardes déjà
     * présentes plus bas (`notes.create` / `notes.edit`) ne le disent pas non plus,
     * puisque tous les enseignants les détiennent.
     *
     * La restriction ne vise que le profil « enseignant » : quiconque coordonne
     * (coordinateur, directeur des études, administration) garde une vue d'ensemble.
     * Les deux rattachements reconnus sont ceux qui font foi ailleurs dans
     * l'application (API LMS) : l'enseignant nommé sur l'évaluation, ou son
     * affectation active à la matière pour l'année de l'évaluation.
     */
    private function assertEvaluationConfieeAEnseignant(ESBTPEvaluation $evaluation): void
    {
        $user = auth()->user();

        if (! $user || ! $user->can('identity.teach') || $user->can('identity.coordinate')) {
            return;
        }

        if ((int) $evaluation->enseignant_id === (int) $user->id) {
            return;
        }

        $affecte = $evaluation->matiere_id !== null && DB::table('esbtp_enseignant_matiere')
            ->where('matiere_id', $evaluation->matiere_id)
            ->where('enseignant_id', $user->id)
            ->where('annee_universitaire_id', $evaluation->annee_universitaire_id)
            ->where('is_active', true)
            ->exists();

        abort_unless($affecte, 403, "Cette évaluation ne vous est pas confiée : vous ne pouvez pas en saisir les notes.");
    }

    private function bulkNoteInvalidationContexts(array $baseContext, array $studentIds): array
    {
        return array_merge(
            [$baseContext + ['studentId' => null]],
            array_map(
                fn (int $studentId): array => $baseContext + ['studentId' => $studentId],
                array_values(array_unique(array_filter($studentIds, fn (int $studentId): bool => $studentId > 0))),
            ),
        );
    }
}
