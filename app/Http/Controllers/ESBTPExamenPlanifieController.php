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
            ->with(['classe', 'classes', 'matiere', 'uniteEnseignement', 'parcours', 'createdBy'])
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
        if ($systeme = strtoupper($request->string('systeme')->trim()->value())) {
            $query->where(function ($q) use ($systeme) {
                if ($systeme === 'LMD') {
                    // LMD : soit ECUE dénormalisé, soit classe principale LMD, soit pivot LMD
                    $q->whereNotNull('unite_enseignement_id')
                      ->orWhereHas('classes', fn ($c) => $c->whereRaw('UPPER(systeme_academique) = ?', ['LMD']))
                      ->orWhereHas('classe', fn ($c) => $c->whereRaw('UPPER(systeme_academique) = ?', ['LMD']));
                } else {
                    // BTS : pas d'ECUE ET aucune classe LMD
                    $q->whereNull('unite_enseignement_id')
                      ->whereDoesntHave('classes', fn ($c) => $c->whereRaw('UPPER(systeme_academique) = ?', ['LMD']))
                      ->where(function ($cq) {
                          $cq->whereHas('classe', fn ($c) => $c->whereRaw('UPPER(systeme_academique) != ?', ['LMD']))
                             ->orWhereDoesntHave('classe');
                      });
                }
            });
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

        // Auto-hide filtre Système si tenant mono-système (0 ou 1 valeur distincte)
        $systemesPresents = ESBTPClasse::query()
            ->whereNotNull('systeme_academique')
            ->distinct()
            ->pluck('systeme_academique')
            ->map(fn ($s) => strtoupper((string) $s))
            ->filter()
            ->unique()
            ->values();
        $hasMixedSystemes = $systemesPresents->count() >= 2;

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
            'matieres', 'parcours', 'sessions',
            'hasMixedSystemes'
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
            // classe_id requis seulement si scope=classe ; sinon optionnel (classe principale dérivée)
            'classe_id' => ['nullable', 'exists:esbtp_classes,id'],
            'classe_ids' => ['nullable', 'array'],
            'classe_ids.*' => ['integer', 'exists:esbtp_classes,id'],
            'matiere_id' => ['required', 'exists:esbtp_matieres,id'],
            'parcours_id' => ['nullable', 'exists:esbtp_lmd_parcours,id'],
            'parcours_ids' => ['nullable', 'array'],
            'parcours_ids.*' => ['integer', 'exists:esbtp_lmd_parcours,id'],
            'scope_type' => ['nullable', 'in:'.implode(',', ESBTPExamenPlanifie::SCOPE_TYPES)],
            'scope_id' => ['nullable', 'integer'],
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

        // Dérive unite_enseignement_id depuis la matière (ECUE LMD) si présent
        $matiere = ESBTPMatiere::find($data['matiere_id']);
        $data['unite_enseignement_id'] = $matiere?->unite_enseignement_id;

        // Scope par défaut = classe (rétrocompat BTS)
        $data['scope_type'] = $data['scope_type'] ?? 'classe';
        $data['scope_id'] = $data['scope_id'] ?? null;

        // Résolution des classes ciblées :
        //   1) Si classe_ids fourni explicitement → utiliser tel quel
        //   2) Sinon, si scope ≠ classe → resolveScopedClasses()
        //   3) Sinon → [classe_id]
        $classeIds = $data['classe_ids'] ?? null;
        if ($classeIds === null) {
            if ($data['scope_type'] !== 'classe' && $data['scope_id']) {
                $classeIds = $this->scheduler->resolveScopedClasses(
                    $data['scope_type'],
                    $data['scope_id'],
                    $data['parcours_ids'] ?? []
                )->pluck('id')->all();
            } elseif ($data['classe_id']) {
                $classeIds = [$data['classe_id']];
            } else {
                $classeIds = [];
            }
        }

        if (empty($classeIds)) {
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => ['classe_ids' => ['Aucune classe ciblée — précisez un scope ou une classe.']],
            ], 422);
        }

        // Validation cohérence système BTS/LMD sur les classes ciblées
        $systemes = ESBTPClasse::whereIn('id', $classeIds)
            ->pluck('systeme_academique')
            ->map(fn ($s) => strtoupper((string) ($s ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($systemes->count() >= 2) {
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => ['classe_ids' => [
                    'Mélange BTS + LMD non autorisé sur un même examen — un examen ne peut cibler que des classes du même système académique.',
                ]],
            ], 422);
        }

        // Validation cohérence ECUE LMD ↔ classes BTS
        if (! empty($data['unite_enseignement_id']) && $systemes->first() === 'BTS') {
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => ['matiere_id' => [
                    'Cette matière est un ECUE (LMD) — elle ne peut pas être assignée à des classes BTS.',
                ]],
            ], 422);
        }

        // classe_id "principale" = première classe (legacy + convocation per-class)
        $data['classe_id'] = $data['classe_id'] ?? $classeIds[0];

        $data['created_by'] = auth()->id();
        $data['is_anonymous'] = (bool) ($data['is_anonymous'] ?? false);
        $data['coefficient'] = $data['coefficient'] ?? 1;
        $data['bareme'] = $data['bareme'] ?? 20;
        $data['status'] = ExamenStatus::PLANNED->value;

        // parcours_ids n'est utile que pour scope=parcours en inter-parcours
        if ($data['scope_type'] !== 'parcours') {
            $data['parcours_ids'] = null;
        }

        // Cleanup avant Eloquent create — classe_ids n'est pas fillable
        unset($data['classe_ids']);

        $examen = \DB::transaction(function () use ($data, $classeIds) {
            $examen = ESBTPExamenPlanifie::create($data);
            $examen->numero_convocation = $this->scheduler->genererNumeroConvocation($examen);
            $examen->save();
            $this->scheduler->syncExamenClasses($examen, $classeIds);
            return $examen;
        });

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'examen' => $this->serializeExamen($examen->fresh(['classe', 'matiere', 'classes'])),
                'kpis' => $this->buildKpis(ESBTPAnneeUniversitaire::find($data['annee_universitaire_id'])),
            ]);
        }

        return redirect()
            ->route('esbtp.examens.show', $examen)
            ->with('success', "Examen créé : {$examen->numero_convocation}");
    }

    /**
     * Endpoint AJAX : preview des classes ciblées par un scope donné.
     * Utilisé par le modal pour afficher la liste avant submit (avec
     * checkboxes pour exclure manuellement certaines classes).
     */
    public function resolveScopeClasses(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $data = $request->validate([
            'scope_type' => ['required', 'in:'.implode(',', ESBTPExamenPlanifie::SCOPE_TYPES)],
            'scope_id' => ['nullable', 'integer'],
            'parcours_ids' => ['nullable', 'array'],
            'parcours_ids.*' => ['integer'],
            'matiere_id' => ['nullable', 'integer', 'exists:esbtp_matieres,id'],
        ]);

        $classes = $this->scheduler->resolveScopedClasses(
            $data['scope_type'],
            $data['scope_id'] ?? null,
            $data['parcours_ids'] ?? []
        );

        // Détecte les parcours qui partagent l'ECUE (pour toggle inter-parcours)
        $sharedParcours = collect();
        if (! empty($data['matiere_id'])) {
            $excludeParcoursId = $data['scope_type'] === 'parcours' ? ($data['scope_id'] ?? null) : null;
            $sharedParcours = $this->scheduler->detectSharedParcours($data['matiere_id'], $excludeParcoursId);
        }

        return response()->json([
            'classes' => $classes->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'filiere_id' => $c->filiere_id,
                'parcours_id' => $c->parcours_id,
            ])->values(),
            'shared_parcours' => $sharedParcours->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
            ])->values(),
        ]);
    }

    /**
     * Endpoint AJAX : retourne les UE et leurs ECUE pour un parcours+niveau.
     * Utilisé par le modal cascade UEMOA.
     */
    public function ecuesByParcours(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.manage'), 403);

        $data = $request->validate([
            'parcours_id' => ['required', 'integer', 'exists:esbtp_lmd_parcours,id'],
            'niveau_id' => ['nullable', 'integer'],
        ]);

        $groups = $this->scheduler->getEcuesGroupedByUe($data['parcours_id'], $data['niveau_id'] ?? null);

        return response()->json([
            'groups' => $groups->map(fn ($g) => [
                'ue' => [
                    'id' => $g['ue']->id,
                    'name' => $g['ue']->name,
                    'code' => $g['ue']->code,
                ],
                'ecues' => $g['ecues']->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'code' => $e->code,
                ])->values(),
            ])->values(),
        ]);
    }

    public function show(ESBTPExamenPlanifie $examen): View
    {
        abort_unless(auth()->user()?->can('lmd.examens.view'), 403);

        $examen->load([
            'classe',
            'classes.filiere',
            'classes.niveau',
            'matiere',
            'uniteEnseignement',
            'parcours',
            'surveillants.user',
            'createdBy',
        ]);

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
     * Feed JSON FullCalendar — retourne les examens de l'année courante
     * (filtrables) au format attendu par FullCalendar 5+.
     * Événements colorés par type (EXAMEN bleu, PARTIEL bleu clair, RATTRAPAGE
     * orange UEMOA, SOUTENANCE bleu marine).
     */
    public function calendarFeed(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.examens.view'), 403);

        $annee = $this->resolveAnnee($request);

        $query = ESBTPExamenPlanifie::with(['classe', 'classes', 'matiere'])
            ->where('annee_universitaire_id', $annee->id);

        if ($classeId = $request->integer('classe_id')) {
            $query->forClasse($classeId);
        }
        if ($type = $request->string('type')->trim()->value()) {
            $query->where('type_examen', $type);
        }
        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }
        if ($systeme = strtoupper($request->string('systeme')->trim()->value())) {
            $query->where(function ($q) use ($systeme) {
                if ($systeme === 'LMD') {
                    $q->whereNotNull('unite_enseignement_id')
                      ->orWhereHas('classes', fn ($c) => $c->whereRaw('UPPER(systeme_academique) = ?', ['LMD']))
                      ->orWhereHas('classe', fn ($c) => $c->whereRaw('UPPER(systeme_academique) = ?', ['LMD']));
                } else {
                    $q->whereNull('unite_enseignement_id')
                      ->whereDoesntHave('classes', fn ($c) => $c->whereRaw('UPPER(systeme_academique) = ?', ['LMD']))
                      ->where(function ($cq) {
                          $cq->whereHas('classe', fn ($c) => $c->whereRaw('UPPER(systeme_academique) != ?', ['LMD']))
                             ->orWhereDoesntHave('classe');
                      });
                }
            });
        }
        if ($from = $request->date('from')) {
            $query->where('date_debut', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->where('date_fin', '<=', $to);
        }

        $colors = [
            'EXAMEN' => '#0453cb',
            'PARTIEL' => '#3b7ddb',
            'RATTRAPAGE' => '#b45309',  // Orange semaforique UEMOA pour 2e session
            'SOUTENANCE' => '#033a8e',
        ];

        $examens = $query->get();

        $events = $examens->map(function ($e) use ($colors) {
            $classes = $e->classes;
            $classesCount = $classes->count();
            $classeLabel = $classesCount > 1
                ? ($classes->first()?->name ?? 'Classe') . ' +' . ($classesCount - 1)
                : ($classes->first()?->name ?? $e->classe?->name ?? '—');

            $color = $colors[$e->type_examen] ?? '#0453cb';
            // Statut annulé → grisé barré
            if ($e->status === 'cancelled') {
                $color = '#94a3b8';
            }

            // Distinction système : chip dans event card (pas de préfixe titre v2)
            // WCAG 1.4.1 : texte + couleur (border CSS) + icône (chip rendered)
            $systeme = $e->systeme;  // 'BTS' | 'LMD' via accessor

            // Icône type d'épreuve pour event card
            $typeIcons = [
                'EXAMEN' => 'fa-pen-ruler',
                'PARTIEL' => 'fa-pen-to-square',
                'RATTRAPAGE' => 'fa-rotate-right',
                'SOUTENANCE' => 'fa-microphone',
            ];

            return [
                'id' => $e->id,
                'title' => $e->titre,
                'start' => $e->date_debut?->toIso8601String(),
                'end' => $e->date_fin?->toIso8601String(),
                'backgroundColor' => 'transparent',  // override par CSS .exp-fc-card
                'borderColor' => $color,
                'textColor' => '#0f172a',
                'classNames' => array_filter([
                    'exp-fc-event',
                    'exp-fc-event--' . strtolower($e->type_examen),
                    'exp-fc-event--sys-' . strtolower($systeme),
                    $e->notes_locked ? 'exp-fc-event--locked' : null,
                    $e->status === 'cancelled' ? 'exp-fc-event--cancelled' : null,
                ]),
                'extendedProps' => [
                    'type_examen' => $e->type_examen,
                    'type_label' => TypeExamen::labelFor($e->type_examen),
                    'type_icon' => $typeIcons[$e->type_examen] ?? 'fa-calendar-check',
                    'type_color' => $color,
                    'systeme' => $systeme,
                    'systeme_icon' => $systeme === 'LMD' ? 'fa-graduation-cap' : 'fa-screwdriver-wrench',
                    'status' => $e->status,
                    'status_label' => ExamenStatus::labelFor($e->status),
                    'numero_convocation' => $e->numero_convocation,
                    'classe_label' => $classeLabel,
                    'classes_count' => $classesCount,
                    'matiere' => $e->matiere?->name,
                    'salle' => $e->salle,
                    'duree_minutes' => $e->duree_minutes,
                    'coefficient' => $e->coefficient ? rtrim(rtrim(number_format($e->coefficient, 2, '.', ''), '0'), '.') : null,
                    'bareme' => (int) $e->bareme,
                    'is_anonymous' => (bool) $e->is_anonymous,
                    'notes_locked' => (bool) $e->notes_locked,
                    'show_url' => route('esbtp.examens.show', $e),
                ],
                'url' => route('esbtp.examens.show', $e),
            ];
        })->values();

        return response()->json($events);
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
