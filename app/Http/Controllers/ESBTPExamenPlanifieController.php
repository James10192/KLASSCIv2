<?php

namespace App\Http\Controllers;

use App\Enums\ExamenStatus;
use App\Enums\TypeExamen;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDSession;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Services\ExamenSchedulingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $kpis = $this->buildKpis($annee);

        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'name', 'is_current']);

        // Données pour le modal de création (chargées server-side pour que les
        // pickers premium au-select soient rendus directement — pas d'AJAX
        // post-mount).
        $matieres = ESBTPMatiere::orderBy('name')->get(['id', 'name']);
        $parcours = ESBTPLMDParcours::orderBy('name')->get(['id', 'name', 'code']);
        // ESBTPLMDSession utilise la colonne `libelle` (pas `name`) — convention
        // historique de la table esbtp_lmd_sessions.
        $sessions = ESBTPLMDSession::where('annee_universitaire_id', $annee->id)
            ->orderByDesc('date_debut')
            ->get(['id', 'libelle', 'type']);

        return view('esbtp.examens.index', compact(
            'examens', 'kpis', 'classes', 'annee', 'annees',
            'matieres', 'parcours', 'sessions'
        ));
    }

    /**
     * Endpoint AJAX consommé par le modal "Nouvel examen" :
     * retourne classes + matières + parcours + sessions LMD pour les
     * pickers premium. Évite d'aller chercher ces données quand le
     * modal n'est jamais ouvert.
     */
    public function options(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $annee = $this->resolveAnnee($request);

        return response()->json([
            'classes' => ESBTPClasse::orderBy('name')->get(['id', 'name'])->values(),
            'matieres' => ESBTPMatiere::orderBy('name')->get(['id', 'name', 'code'])->values(),
            'parcours' => ESBTPLMDParcours::orderBy('name')->get(['id', 'name', 'code'])->values(),
            'sessions' => ESBTPLMDSession::where('annee_universitaire_id', $annee->id)
                ->orderBy('date_debut', 'desc')
                ->get(['id', 'libelle', 'type'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->libelle, 'type' => $s->type])
                ->values(),
            'annee' => ['id' => $annee->id, 'name' => $annee->name],
            'types' => TypeExamen::selectOptions(),
            'statuses' => ExamenStatus::editableOptions(),
        ]);
    }

    /**
     * `create` redirige vers index — la création se fait via modal Alpine.
     * Conservé en GET pour rétrocompat éventuelle des liens externes.
     */
    public function create(Request $request): RedirectResponse
    {
        $annee = $this->resolveAnnee($request);
        return redirect()->route('esbtp.examens.index', [
            'annee_universitaire_id' => $annee->id,
            'open_create' => 1,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $data = $request->validate([
            'annee_universitaire_id' => ['required', 'exists:esbtp_annee_universitaires,id'],
            'classe_id' => ['required', 'exists:esbtp_classes,id'],
            'matiere_id' => ['required', 'exists:esbtp_matieres,id'],
            'parcours_id' => ['nullable', 'exists:esbtp_lmd_parcours,id'],
            'session_id' => ['nullable', 'integer', 'exists:esbtp_lmd_sessions,id'],
            'semestre' => ['nullable', 'integer', 'between:1,8'],
            'type_examen' => ['required', 'in:'.implode(',', TypeExamen::values())],
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
        $data['status'] = ExamenStatus::PLANNED->value;

        $examen = ESBTPExamenPlanifie::create($data);
        $examen->numero_convocation = $this->scheduler->genererNumeroConvocation($examen);
        $examen->save();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'examen' => $this->serializeExamen($examen->loadMissing(['classe', 'matiere'])),
                'kpis' => $this->buildKpis(ESBTPAnneeUniversitaire::find($data['annee_universitaire_id'])),
            ]);
        }

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
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'name']);

        return view('esbtp.examens.edit', compact('examen', 'classes', 'matieres', 'annees'));
    }

    public function update(Request $request, ESBTPExamenPlanifie $examen)
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);
        abort_if($examen->notes_locked, 403, 'Notes verrouillées : modification interdite.');

        $editableStatuses = array_keys(ExamenStatus::editableOptions());

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
            'status' => ['nullable', 'in:'.implode(',', $editableStatuses)],
        ]);
        $data['updated_by'] = auth()->id();
        if (array_key_exists('is_anonymous', $data)) {
            $data['is_anonymous'] = (bool) $data['is_anonymous'];
        }

        $examen->update($data);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'examen' => $this->serializeExamen($examen->fresh(['classe', 'matiere'])),
            ]);
        }

        return redirect()
            ->route('esbtp.examens.show', $examen)
            ->with('success', 'Examen mis à jour.');
    }

    public function destroy(Request $request, ESBTPExamenPlanifie $examen)
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);
        abort_if($examen->notes_locked, 403, 'Notes verrouillées : suppression interdite.');

        $examen->update(['updated_by' => auth()->id(), 'status' => ExamenStatus::CANCELLED->value]);
        $examen->delete();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'kpis' => $this->buildKpis(ESBTPAnneeUniversitaire::find($examen->annee_universitaire_id)),
            ]);
        }

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
            'type_examen' => ['required', 'in:'.implode(',', TypeExamen::values())],
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
        return response()->json($this->buildKpis($annee));
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

    private function buildKpis(?ESBTPAnneeUniversitaire $annee): array
    {
        if (! $annee) {
            return ['total' => 0, 'a_venir' => 0, 'en_cours' => 0, 'notes_lockees' => 0];
        }
        $base = ESBTPExamenPlanifie::where('annee_universitaire_id', $annee->id);
        return [
            'total' => (clone $base)->count(),
            'a_venir' => (clone $base)->upcoming()->count(),
            'en_cours' => (clone $base)->where('status', ExamenStatus::IN_PROGRESS->value)->count(),
            'notes_lockees' => (clone $base)->where('notes_locked', true)->count(),
        ];
    }

    private function serializeExamen(ESBTPExamenPlanifie $examen): array
    {
        return [
            'id' => $examen->id,
            'numero_convocation' => $examen->numero_convocation,
            'titre' => $examen->titre,
            'type_examen' => $examen->type_examen,
            'type_label' => TypeExamen::labelFor($examen->type_examen),
            'date_debut' => $examen->date_debut?->toIso8601String(),
            'date_fin' => $examen->date_fin?->toIso8601String(),
            'date_debut_fr' => $examen->date_debut?->format('d/m/Y'),
            'heure_debut' => $examen->date_debut?->format('H:i'),
            'heure_fin' => $examen->date_fin?->format('H:i'),
            'duree_minutes' => $examen->duree_minutes,
            'salle' => $examen->salle,
            'coefficient' => (float) $examen->coefficient,
            'bareme' => (int) $examen->bareme,
            'status' => $examen->status,
            'status_label' => ExamenStatus::labelFor($examen->status),
            'is_anonymous' => (bool) $examen->is_anonymous,
            'notes_locked' => (bool) $examen->notes_locked,
            'classe_name' => $examen->classe?->name,
            'matiere_name' => $examen->matiere?->name,
            'show_url' => route('esbtp.examens.show', $examen),
        ];
    }
}
