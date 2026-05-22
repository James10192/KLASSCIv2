<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDSession;
use App\Services\RattrapageSchedulingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESBTPLMDSessionController extends Controller
{
    public function __construct(private readonly RattrapageSchedulingService $rattrapage)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.view'), 403);

        $annee = $this->resolveAnnee($request);

        $sessions = ESBTPLMDSession::query()
            ->with(['anneeUniversitaire', 'parcours', 'parentSession'])
            ->where('annee_universitaire_id', $annee->id)
            ->orderByDesc('date_debut')
            ->paginate(20)
            ->withQueryString();

        $kpis = [
            'normales' => ESBTPLMDSession::forAnnee($annee->id)->normales()->count(),
            'rattrapages' => ESBTPLMDSession::forAnnee($annee->id)->rattrapages()->count(),
            'en_cours' => ESBTPLMDSession::forAnnee($annee->id)->whereIn('status', ['planned', 'in_progress'])->count(),
            'publiees' => ESBTPLMDSession::forAnnee($annee->id)->where('status', 'published')->count(),
        ];

        $parcours = ESBTPLMDParcours::orderBy('nom')->get(['id', 'nom']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'libelle', 'is_current']);

        return view('esbtp.lmd.rattrapage.index', compact(
            'sessions', 'kpis', 'parcours', 'annee', 'annees'
        ));
    }

    public function show(ESBTPLMDSession $session): View
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.view'), 403);

        $session->load(['anneeUniversitaire', 'parcours', 'parentSession', 'childrenSessions', 'examens']);

        return view('esbtp.lmd.rattrapage.show', compact('session'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.manage'), 403);

        $data = $request->validate([
            'annee_universitaire_id' => ['required', 'exists:esbtp_annee_universitaires,id'],
            'parcours_id' => ['nullable', 'exists:esbtp_lmd_parcours,id'],
            'type' => ['required', 'in:normale,rattrapage,extra'],
            'parent_session_id' => ['nullable', 'exists:esbtp_lmd_sessions,id'],
            'semestre' => ['nullable', 'integer', 'between:1,8'],
            'libelle' => ['required', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        $session = ESBTPLMDSession::create($data);

        return redirect()
            ->route('esbtp.lmd.rattrapage.show', $session)
            ->with('success', "Session créée : {$session->libelle}");
    }

    /**
     * Action AJAX : lance le workflow rattrapage en cascade depuis une session normale.
     * (1) Génère session rattrapage enfant (2) identifie éligibles (3) crée examens.
     */
    public function lancerRattrapage(Request $request, ESBTPLMDSession $session): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.manage'), 403);

        $data = $request->validate([
            'date_debut' => ['nullable', 'date'],
        ]);

        try {
            $rattrapage = $this->rattrapage->genererSessionRattrapage(
                $session,
                ! empty($data['date_debut']) ? Carbon::parse($data['date_debut']) : null
            );
            $eligibles = $this->rattrapage->identifierEtudiantsEligibles($session);
            $examens = $this->rattrapage->genererExamensRattrapage($rattrapage);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'session_rattrapage' => [
                'id' => $rattrapage->id,
                'libelle' => $rattrapage->libelle,
                'date_debut' => $rattrapage->date_debut?->toDateString(),
            ],
            'eligibles_count' => $eligibles->count(),
            'examens_count' => $examens->count(),
        ]);
    }

    /**
     * Recalcule les notes finales pour la session rattrapage (cron-friendly).
     */
    public function recalculerNotes(Request $request, ESBTPLMDSession $session): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.manage'), 403);

        if ($session->type !== 'rattrapage') {
            return response()->json(['success' => false, 'message' => 'Session non-rattrapage'], 422);
        }

        $data = $request->validate([
            'etudiant_ids' => ['nullable', 'array'],
            'etudiant_ids.*' => ['integer', 'exists:esbtp_etudiants,id'],
        ]);

        $updated = 0;
        $etudiantIds = $data['etudiant_ids'] ?? [];

        if (empty($etudiantIds)) {
            // Tous les éligibles
            $bulletinIds = $session->parentSession?->parcours?->bulletins?->pluck('id') ?? collect();
            if ($bulletinIds->isNotEmpty()) {
                $etudiantIds = \App\Models\ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
                    ->where('rattrapage_eligible', true)
                    ->pluck('etudiant_id')
                    ->unique()
                    ->all();
            }
        }

        foreach ($etudiantIds as $etudiantId) {
            $updated += $this->rattrapage->recalculerMoyennesAvecRattrapage((int) $etudiantId, $session);
        }

        return response()->json([
            'success' => true,
            'updated_count' => $updated,
            'etudiants_count' => count($etudiantIds),
        ]);
    }

    /**
     * Inscription manuelle des éligibles (ou subset via etudiant_ids).
     */
    public function inscrireEligibles(Request $request, ESBTPLMDSession $session): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.manage'), 403);

        if ($session->type !== 'rattrapage') {
            return response()->json(['success' => false, 'message' => 'Session non-rattrapage'], 422);
        }

        $data = $request->validate([
            'etudiant_ids' => ['nullable', 'array'],
            'etudiant_ids.*' => ['integer', 'exists:esbtp_etudiants,id'],
        ]);

        $count = $this->rattrapage->inscrireEtudiantsEligibles(
            $session,
            $data['etudiant_ids'] ?? null
        );

        return response()->json(['success' => true, 'inscrits_count' => $count]);
    }

    /**
     * Publication finale d'une session (rend les notes officielles).
     */
    public function publier(ESBTPLMDSession $session): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.rattrapage.manage'), 403);

        if ($session->status === 'published') {
            return response()->json(['success' => false, 'message' => 'Déjà publiée'], 422);
        }

        $session->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'published_at' => $session->published_at?->toIso8601String(),
            ],
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
