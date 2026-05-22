<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryMembre;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDSession;
use App\Models\User;
use App\Services\JuryDeliberationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESBTPLMDJuryController extends Controller
{
    public function __construct(private readonly JuryDeliberationService $delib)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);

        $annee = $this->resolveAnnee($request);

        $jurys = ESBTPLMDJury::query()
            ->with(['parcours', 'classe', 'membres'])
            ->where('annee_universitaire_id', $annee->id)
            ->orderByDesc('date_jury')
            ->paginate(20)
            ->withQueryString();

        $kpis = [
            'total' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->count(),
            'preparation' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->where('status', 'preparation')->count(),
            'en_cours' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->where('status', 'en_cours')->count(),
            'publies' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->where('status', 'publie')->count(),
        ];

        $parcours = ESBTPLMDParcours::orderBy('nom')->get(['id', 'nom']);
        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $sessions = ESBTPLMDSession::orderByDesc('date_debut')->get(['id', 'libelle']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'libelle', 'is_current']);

        return view('esbtp.lmd.jurys.index', compact(
            'jurys', 'kpis', 'parcours', 'classes', 'sessions', 'annee', 'annees'
        ));
    }

    public function show(ESBTPLMDJury $jury): View
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);

        $jury->load([
            'anneeUniversitaire', 'parcours', 'classe', 'session',
            'membres.user', 'decisions.etudiant',
        ]);

        $quorum = $this->delib->verifierQuorum($jury);
        $stats = $this->delib->buildStatistiques($jury);

        $enseignants = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('esbtp.lmd.jurys.show', compact('jury', 'quorum', 'stats', 'enseignants'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.preside'), 403);

        $data = $request->validate([
            'annee_universitaire_id' => ['required', 'exists:esbtp_annee_universitaires,id'],
            'session_id' => ['nullable', 'exists:esbtp_lmd_sessions,id'],
            'parcours_id' => ['nullable', 'exists:esbtp_lmd_parcours,id'],
            'classe_id' => ['nullable', 'exists:esbtp_classes,id'],
            'semestre' => ['nullable', 'integer', 'between:1,8'],
            'libelle' => ['required', 'string', 'max:255'],
            'date_jury' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['status'] = 'preparation';
        $data['created_by'] = auth()->id();

        $jury = ESBTPLMDJury::create($data);

        return redirect()
            ->route('esbtp.lmd.jurys.show', $jury)
            ->with('success', "Jury créé : {$jury->libelle}");
    }

    public function destroy(ESBTPLMDJury $jury): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.preside'), 403);
        abort_if($jury->isLocked(), 422, 'PV déjà généré — suppression interdite');

        $jury->delete();

        return redirect()->route('esbtp.lmd.jurys.index')->with('success', 'Jury supprimé.');
    }

    /**
     * Ajoute un membre au jury.
     */
    public function addMembre(Request $request, ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.preside'), 403);
        abort_if($jury->isLocked(), 422, 'PV déjà généré');

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:president,assesseur,secretaire,consultatif'],
            'present' => ['nullable', 'boolean'],
        ]);

        $existing = ESBTPLMDJuryMembre::where('jury_id', $jury->id)
            ->where('user_id', $data['user_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'role' => $data['role'],
                'present' => $data['present'] ?? $existing->present,
            ]);
            $membre = $existing;
        } else {
            $membre = ESBTPLMDJuryMembre::create([
                'jury_id' => $jury->id,
                'user_id' => $data['user_id'],
                'role' => $data['role'],
                'present' => $data['present'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'membre' => [
                'id' => $membre->id,
                'user_id' => $membre->user_id,
                'user_name' => $membre->user?->name,
                'role' => $membre->role,
                'present' => $membre->present,
                'has_signed' => $membre->hasSigned(),
            ],
            'quorum' => $this->delib->verifierQuorum($jury->fresh('membres')),
        ]);
    }

    public function removeMembre(ESBTPLMDJury $jury, ESBTPLMDJuryMembre $membre): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.preside'), 403);
        abort_if($jury->isLocked(), 422, 'PV déjà généré');
        abort_if($membre->jury_id !== $jury->id, 404);

        $membre->delete();

        return response()->json([
            'success' => true,
            'quorum' => $this->delib->verifierQuorum($jury->fresh('membres')),
        ]);
    }

    public function appliquerAuto(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.deliberate'), 403);

        try {
            $created = $this->delib->appliquerDecisionsAuto($jury);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $jury->fresh()->update(['status' => 'en_cours', 'updated_by' => auth()->id()]);

        return response()->json([
            'success' => true,
            'created_count' => $created,
            'stats' => $this->delib->buildStatistiques($jury->fresh('decisions')),
        ]);
    }

    public function overrideDecision(Request $request, ESBTPLMDJury $jury, ESBTPEtudiant $etudiant): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.deliberate'), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:admis,admission_rattrapage,ajourne,exclu,admis_sous_condition,defere'],
            'motif' => ['required', 'string', 'min:5', 'max:1000'],
            'vote_resultat' => ['nullable', 'in:unanime,majorite,partage_voix_president'],
        ]);

        try {
            $decision = $this->delib->overrideDecision(
                $jury, $etudiant, $data['decision'], $data['motif'], $data['vote_resultat'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'decision' => [
                'id' => $decision->id,
                'etudiant_id' => $decision->etudiant_id,
                'decision_auto' => $decision->decision_auto,
                'decision' => $decision->decision,
                'mention' => $decision->mention,
                'override_par_jury' => $decision->override_par_jury,
                'motif_override' => $decision->motif_override,
                'vote_resultat' => $decision->vote_resultat,
                'moyenne_generale' => $decision->moyenne_generale,
            ],
            'stats' => $this->delib->buildStatistiques($jury->fresh('decisions')),
        ]);
    }

    public function signerMembre(Request $request, ESBTPLMDJury $jury, ESBTPLMDJuryMembre $membre): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.deliberate'), 403);
        abort_if($membre->jury_id !== $jury->id, 404);
        abort_if($jury->isLocked(), 422, 'PV déjà généré — signature interdite');

        $data = $request->validate([
            'signature_data' => ['required', 'string', 'max:200000'],
        ]);

        $this->delib->enregistrerSignature(
            $membre,
            $data['signature_data'],
            $request->ip(),
            substr((string) $request->userAgent(), 0, 500)
        );

        return response()->json([
            'success' => true,
            'membre' => [
                'id' => $membre->id,
                'has_signed' => true,
                'signature_at' => $membre->fresh()->signature_at?->toIso8601String(),
            ],
        ]);
    }

    public function kpis(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);

        return response()->json([
            'stats' => $this->delib->buildStatistiques($jury->fresh('decisions')),
            'quorum' => $this->delib->verifierQuorum($jury->fresh('membres')),
        ]);
    }

    public function genererPv(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.publish'), 403);

        $quorum = $this->delib->verifierQuorum($jury);
        if (! $quorum['ok']) {
            return response()->json([
                'success' => false,
                'message' => 'Quorum non atteint : ' . implode(', ', $quorum['reasons']),
            ], 422);
        }

        try {
            $path = $this->delib->genererPvDeliberation($jury);
            $jury->fresh()->update(['status' => 'clos', 'clos_at' => now(), 'updated_by' => auth()->id()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'pv' => [
                'numero' => $jury->fresh()->pv_numero,
                'path' => $path,
                'genere_at' => $jury->fresh()->pv_genere_at?->toIso8601String(),
                'download_url' => route('esbtp.lmd.jurys.pv-download', $jury),
            ],
        ]);
    }

    public function publier(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.publish'), 403);

        try {
            $this->delib->publierDecisions($jury);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'jury' => [
                'id' => $jury->id,
                'status' => $jury->fresh()->status,
                'publie_at' => $jury->fresh()->publie_at?->toIso8601String(),
            ],
        ]);
    }

    public function pvDownload(ESBTPLMDJury $jury): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);
        abort_unless($jury->pv_path, 404, 'PV non encore généré');

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($jury->pv_path), 404);

        return response($disk->get($jury->pv_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . basename($jury->pv_path) . '"',
        ]);
    }

    public function pvPreview(ESBTPLMDJury $jury): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);
        abort_unless($jury->pv_path, 404, 'PV non encore généré');

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($jury->pv_path), 404);

        return response($disk->get($jury->pv_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($jury->pv_path) . '"',
        ]);
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
