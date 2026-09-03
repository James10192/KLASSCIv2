<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
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
            ->when((int) $request->input('parcours_id'), fn ($q, $id) => $q->where('parcours_id', $id))
            ->when((int) $request->input('semestre'), fn ($q, $s) => $q->where('semestre', $s))
            ->orderByDesc('date_debut')
            ->paginate(20)
            ->withQueryString();

        $kpis = [
            'normales' => ESBTPLMDSession::forAnnee($annee->id)->normales()->count(),
            'rattrapages' => ESBTPLMDSession::forAnnee($annee->id)->rattrapages()->count(),
            'en_cours' => ESBTPLMDSession::forAnnee($annee->id)->whereIn('status', ['planned', 'in_progress'])->count(),
            'publiees' => ESBTPLMDSession::forAnnee($annee->id)->where('status', 'published')->count(),
        ];

        $parcours = ESBTPLMDParcours::orderBy('name')->get(['id', 'name']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'name', 'libelle', 'is_current', 'start_date', 'end_date']);

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

    /**
     * Ecran de saisie des notes de seconde session.
     */
    public function notesRattrapage(ESBTPLMDSession $session): View
    {
        // L'ecran de saisie s'ouvre aussi au seul detenteur de la saisie : sans cela,
        // lmd.rattrapage.notes.saisir ne servirait a rien seul, l'enseignant butant
        // sur un refus avant meme d'atteindre l'ecran qui produit l'envoi autorise.
        $user = auth()->user();
        abort_unless(
            $user?->can('lmd.rattrapage.view') || $user?->can('lmd.rattrapage.notes.saisir'),
            403,
            "La saisie des notes de seconde session ne vous est pas ouverte. Demandez le droit de saisie au responsable de la session."
        );

        // Sans titre de supervision, l'ecran se borne aux elements constitutifs
        // confies a l'enseignant.
        $limiterAEnseignantId = $this->limiteEnseignantRattrapage();
        abort_unless($session->type === 'rattrapage', 404);

        $session->load(['anneeUniversitaire', 'parcours', 'parentSession']);

        try {
            $lignes = $this->rattrapage->lignesSaisieRattrapage($session, $limiterAEnseignantId);
        } catch (\DomainException $e) {
            return view('esbtp.lmd.rattrapage.notes', [
                'session' => $session,
                'groupes' => collect(),
                'remplace' => $this->remplaceNoteSessionNormale(),
                'messageBloquant' => $e->getMessage(),
                'peutSaisir' => false,
            ]);
        }

        return view('esbtp.lmd.rattrapage.notes', [
            'session' => $session,
            'groupes' => $lignes->groupBy('etudiant_id'),
            'remplace' => $this->remplaceNoteSessionNormale(),
            'messageBloquant' => null,
            'peutSaisir' => $this->peutSaisirNotes(),
        ]);
    }

    /**
     * Enregistre les notes de seconde session (AJAX, sans rechargement).
     */
    public function enregistrerNotesRattrapage(Request $request, ESBTPLMDSession $session): JsonResponse
    {
        abort_unless(
            $this->peutSaisirNotes(),
            403,
            "La saisie des notes de seconde session ne vous est pas ouverte. Demandez le droit de saisie au responsable de la session."
        );

        // Meme perimetre qu'a l'affichage : l'identifiant de resultat arrive du
        // formulaire, une liste bornee a l'ecran ne protegerait rien si
        // l'enregistrement acceptait n'importe quelle ligne de la session.
        $limiterAEnseignantId = $this->limiteEnseignantRattrapage();

        $data = $request->validate([
            'notes' => ['required', 'array', 'min:1'],
            'notes.*.resultat_id' => ['required', 'integer'],
            'notes.*.note' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $bilan = $this->rattrapage->saisirNotesRattrapage($session, $data['notes'], $limiterAEnseignantId);
        } catch (\DomainException|\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $this->messageBilan($bilan),
            'bilan' => $bilan,
            'lignes' => $this->etatLignes($session, $limiterAEnseignantId),
        ]);
    }

    /**
     * Etat courant des lignes, pour rafraichir l'ecran sans le recharger.
     *
     * @return array<int, array{resultat_id: int, note_rattrapage: float|null, note_finale: float|null}>
     */
    private function etatLignes(ESBTPLMDSession $session, ?int $limiterAEnseignantId = null): array
    {
        return $this->rattrapage->lignesSaisieRattrapage($session, $limiterAEnseignantId)
            ->map(fn ($ligne): array => [
                'resultat_id' => (int) $ligne->id,
                'note_rattrapage' => $ligne->note_rattrapage === null ? null : (float) $ligne->note_rattrapage,
                'note_finale' => $ligne->note_finale === null ? null : (float) $ligne->note_finale,
            ])
            ->values()
            ->all();
    }

    private function messageBilan(array $bilan): string
    {
        $parties = [];
        if ($bilan['saisies'] > 0) {
            $parties[] = $bilan['saisies'] . ' note' . ($bilan['saisies'] > 1 ? 's' : '') . ' enregistree' . ($bilan['saisies'] > 1 ? 's' : '');
        }
        if ($bilan['effacees'] > 0) {
            $parties[] = $bilan['effacees'] . ' effacee' . ($bilan['effacees'] > 1 ? 's' : '');
        }
        if ($parties === []) {
            return 'Aucune modification.';
        }

        return implode(', ', $parties) . '. ' . $bilan['recalculees'] . ' note' . ($bilan['recalculees'] > 1 ? 's finales recalculees' : ' finale recalculee') . '.';
    }

    /**
     * La saisie exige la permission dediee, ou a defaut celle qui pilote deja le rattrapage.
     */
    /**
     * A quel enseignant l'ecran de seconde session est-il borne ?
     *
     * `null` pour qui supervise le rattrapage — il voit la session entiere. Sinon
     * l'identifiant de l'utilisateur : le droit de saisie ouvre la saisie de SES
     * elements constitutifs, pas la lecture des notes de toute la promotion.
     */
    private function limiteEnseignantRattrapage(): ?int
    {
        $user = auth()->user();

        if ($user?->can('lmd.rattrapage.view') || $user?->can('lmd.rattrapage.manage')) {
            return null;
        }

        return $user?->id;
    }

    private function peutSaisirNotes(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('lmd.rattrapage.notes.saisir') || $user?->can('lmd.rattrapage.manage'));
    }

    /**
     * Regle d'instance : la note de seconde session remplace-t-elle celle de la premiere ?
     */
    private function remplaceNoteSessionNormale(): bool
    {
        return (bool) SettingsHelper::get('lmd_rattrapage_replace', false);
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
            // Tous les inscrits au rattrapage, sur le perimetre exact de la session.
            try {
                $etudiantIds = $this->rattrapage->lignesSaisieRattrapage($session)
                    ->pluck('etudiant_id')
                    ->unique()
                    ->values()
                    ->all();
            } catch (\DomainException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
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
