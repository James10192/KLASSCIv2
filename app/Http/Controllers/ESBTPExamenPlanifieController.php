<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Services\ExamenSchedulingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ESBTPExamenPlanifieController extends Controller
{
    public function __construct(private readonly ExamenSchedulingService $scheduler)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('lmd.examens.view'), 403);

        $annee = $this->resolveAnnee($request);

        $query = ESBTPExamenPlanifie::query()
            ->with(['classe', 'matiere', 'parcours', 'createdBy'])
            ->where('annee_universitaire_id', $annee->id)
            ->orderBy('date_debut');

        if ($classeId = $request->integer('classe_id')) {
            $query->where('classe_id', $classeId);
        }
        if ($type = $request->string('type')->trim()->value()) {
            $query->where('type_examen', $type);
        }
        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }
        if ($from = $request->date('from')) {
            $query->where('date_debut', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->where('date_fin', '<=', $to);
        }

        $examens = $query->paginate(25)->withQueryString();

        $kpis = [
            'total' => ESBTPExamenPlanifie::where('annee_universitaire_id', $annee->id)->count(),
            'a_venir' => ESBTPExamenPlanifie::where('annee_universitaire_id', $annee->id)
                ->upcoming()->count(),
            'en_cours' => ESBTPExamenPlanifie::where('annee_universitaire_id', $annee->id)
                ->where('status', 'in_progress')->count(),
            'notes_lockees' => ESBTPExamenPlanifie::where('annee_universitaire_id', $annee->id)
                ->where('notes_locked', true)->count(),
        ];

        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'libelle', 'is_current']);

        return view('esbtp.examens.index', compact(
            'examens', 'kpis', 'classes', 'annee', 'annees'
        ));
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $classeId = $request->integer('classe_id');
        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $matieres = ESBTPMatiere::orderBy('name')->get(['id', 'name']);
        $annee = $this->resolveAnnee($request);

        return view('esbtp.examens.create', compact('classes', 'matieres', 'annee', 'classeId'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $data = $request->validate([
            'annee_universitaire_id' => ['required', 'exists:esbtp_annee_universitaires,id'],
            'classe_id' => ['required', 'exists:esbtp_classes,id'],
            'matiere_id' => ['required', 'exists:esbtp_matieres,id'],
            'parcours_id' => ['nullable', 'exists:esbtp_lmd_parcours,id'],
            'semestre' => ['nullable', 'integer', 'between:1,8'],
            'type_examen' => ['required', 'in:EXAMEN,PARTIEL,RATTRAPAGE,SOUTENANCE'],
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'duree_minutes' => ['nullable', 'integer', 'between:15,360'],
            'salle' => ['nullable', 'string', 'max:100'],
            'coefficient' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'bareme' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_anonymous'] = (bool) ($data['is_anonymous'] ?? false);
        $data['coefficient'] = $data['coefficient'] ?? 1;
        $data['bareme'] = $data['bareme'] ?? 20;
        $data['status'] = 'planned';

        $examen = ESBTPExamenPlanifie::create($data);
        $examen->numero_convocation = $this->scheduler->genererNumeroConvocation($examen);
        $examen->save();

        return redirect()
            ->route('esbtp.examens.show', $examen)
            ->with('success', "Examen créé : {$examen->numero_convocation}");
    }

    public function show(ESBTPExamenPlanifie $examen): View
    {
        abort_unless(auth()->user()?->can('lmd.examens.view'), 403);

        $examen->load(['classe', 'matiere', 'parcours', 'surveillants.user', 'createdBy']);

        $surveillantsDispo = User::role(['enseignant', 'serviceTechnique', 'secretaire'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('esbtp.examens.show', compact('examen', 'surveillantsDispo'));
    }

    public function edit(ESBTPExamenPlanifie $examen): View
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);
        abort_if($examen->notes_locked, 403, 'Les notes de cet examen sont verrouillées.');

        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $matieres = ESBTPMatiere::orderBy('name')->get(['id', 'name']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'libelle']);

        return view('esbtp.examens.edit', compact('examen', 'classes', 'matieres', 'annees'));
    }

    public function update(Request $request, ESBTPExamenPlanifie $examen): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);
        abort_if($examen->notes_locked, 403, 'Notes verrouillées : modification interdite.');

        $data = $request->validate([
            'titre' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date', 'after:date_debut'],
            'duree_minutes' => ['nullable', 'integer', 'between:15,360'],
            'salle' => ['nullable', 'string', 'max:100'],
            'coefficient' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'bareme' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'is_anonymous' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,planned,in_progress,completed,cancelled'],
        ]);
        $data['updated_by'] = auth()->id();

        $examen->update($data);

        return redirect()
            ->route('esbtp.examens.show', $examen)
            ->with('success', 'Examen mis à jour.');
    }

    public function destroy(ESBTPExamenPlanifie $examen): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);
        abort_if($examen->notes_locked, 403, 'Notes verrouillées : suppression interdite.');

        $examen->update(['updated_by' => auth()->id(), 'status' => 'cancelled']);
        $examen->delete();

        return redirect()
            ->route('esbtp.examens.index')
            ->with('success', 'Examen supprimé (soft delete).');
    }

    /**
     * Génération bulk pour un scope (classe + semestre + type).
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $data = $request->validate([
            'classe_id' => ['required', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => ['required', 'exists:esbtp_annee_universitaires,id'],
            'semestre' => ['required', 'integer', 'between:1,8'],
            'type_examen' => ['required', 'in:EXAMEN,PARTIEL,RATTRAPAGE,SOUTENANCE'],
            'date_premier_examen' => ['nullable', 'date'],
            'session_id' => ['nullable', 'integer'],
        ]);

        $classe = ESBTPClasse::findOrFail($data['classe_id']);
        $annee = ESBTPAnneeUniversitaire::findOrFail($data['annee_universitaire_id']);
        $date = ! empty($data['date_premier_examen']) ? Carbon::parse($data['date_premier_examen']) : null;

        $created = $this->scheduler->genererExamensSession(
            $classe,
            $annee,
            $data['semestre'],
            $data['type_examen'],
            $data['session_id'] ?? null,
            $date
        );

        return response()->json([
            'success' => true,
            'created_count' => $created->count(),
            'examens' => $created->map(fn ($e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'date_debut' => $e->date_debut?->toIso8601String(),
                'numero_convocation' => $e->numero_convocation,
            ])->values(),
        ]);
    }

    /**
     * Endpoint AJAX : attribue/retire surveillants.
     */
    public function assignSurveillants(Request $request, ESBTPExamenPlanifie $examen): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'role' => ['nullable', 'in:surveillant,surveillant_principal,secretaire,responsable_salle'],
        ]);

        $count = $this->scheduler->assignerSurveillants(
            $examen,
            $data['user_ids'],
            $data['role'] ?? 'surveillant'
        );

        return response()->json([
            'success' => true,
            'assigned_count' => $count,
            'surveillants' => $examen->fresh('surveillants.user')->surveillants
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'user_id' => $s->user_id,
                    'user_name' => $s->user?->name,
                    'role' => $s->role,
                    'confirmed' => $s->confirmed,
                ])->values(),
        ]);
    }

    /**
     * Lock anti-tampering des notes.
     */
    public function lockNotes(ESBTPExamenPlanifie $examen): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.notes_lock'), 403);

        $locked = $this->scheduler->lockNotesAfterExam($examen);

        return response()->json([
            'success' => $locked,
            'examen' => [
                'id' => $examen->id,
                'notes_locked' => $examen->notes_locked,
                'notes_locked_at' => $examen->notes_locked_at?->toIso8601String(),
                'notes_locked_by' => $examen->notes_locked_by,
                'status' => $examen->status,
            ],
        ]);
    }

    /**
     * KPIs live (AJAX) pour rafraîchir la hero sans reload.
     */
    public function kpis(Request $request): JsonResponse
    {
        $annee = $this->resolveAnnee($request);
        $base = ESBTPExamenPlanifie::where('annee_universitaire_id', $annee->id);

        return response()->json([
            'total' => (clone $base)->count(),
            'a_venir' => (clone $base)->upcoming()->count(),
            'en_cours' => (clone $base)->where('status', 'in_progress')->count(),
            'notes_lockees' => (clone $base)->where('notes_locked', true)->count(),
        ]);
    }

    /**
     * Aperçu PDF des convocations (groupé par classe).
     */
    public function convocationsPreview(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()?->can('lmd.examens.view'), 403);

        [$pdf, $filename] = $this->buildConvocationsPdf($request);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function convocationsDownload(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()?->can('lmd.examens.view'), 403);

        [$pdf, $filename] = $this->buildConvocationsPdf($request);

        return $pdf->download($filename);
    }

    private function buildConvocationsPdf(Request $request): array
    {
        $annee = $this->resolveAnnee($request);

        $query = ESBTPExamenPlanifie::with(['classe', 'matiere', 'surveillants.user'])
            ->where('annee_universitaire_id', $annee->id)
            ->orderBy('date_debut');

        if ($classeId = $request->integer('classe_id')) {
            $query->where('classe_id', $classeId);
        }
        if ($semestre = $request->integer('semestre')) {
            $query->where('semestre', $semestre);
        }
        if ($type = $request->string('type')->trim()->value()) {
            $query->where('type_examen', $type);
        }

        $examens = $query->get();

        $pdf = Pdf::loadView('esbtp.examens.pdf.convocations', [
            'examens' => $examens,
            'annee' => $annee,
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('convocations-%s.pdf', now()->format('Y-m-d'));

        return [$pdf, $filename];
    }

    private function resolveAnnee(Request $request): ESBTPAnneeUniversitaire
    {
        if ($id = $request->integer('annee_universitaire_id')) {
            return ESBTPAnneeUniversitaire::findOrFail($id);
        }

        $current = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return $current ?? ESBTPAnneeUniversitaire::orderByDesc('id')->firstOrFail();
    }
}
