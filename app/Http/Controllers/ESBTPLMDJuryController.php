<?php

namespace App\Http\Controllers;

use App\Domain\OfficialDocuments\Services\OfficialDocumentDownloadService;
use App\Domain\OfficialDocuments\Services\LegacyJuryPvReconciliationService;
use App\Domain\OfficialDocuments\Services\JuryPvRenderer;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryMembre;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDSession;
use App\Models\User;
use App\Services\JuryDeliberationService;
use App\Services\Security\SeparationOfDutiesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ESBTPLMDJuryController extends Controller
{
    public function __construct(
        private readonly JuryDeliberationService $delib,
        private readonly OfficialDocumentService $officialDocuments,
        private readonly OfficialDocumentDownloadService $officialDownloads,
        private readonly LegacyJuryPvReconciliationService $legacyReconciliation,
        private readonly JuryPvRenderer $pvRenderer,
        private readonly SeparationOfDutiesService $sod,
    )
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
            ->when((int) $request->input('classe_id'), fn ($q, $id) => $q->where('classe_id', $id))
            ->when((int) $request->input('parcours_id'), fn ($q, $id) => $q->where('parcours_id', $id))
            ->when((int) $request->input('semestre'), fn ($q, $s) => $q->where('semestre', $s))
            ->orderByDesc('date_jury')
            ->paginate(20)
            ->withQueryString();

        $kpis = [
            'total' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->count(),
            'preparation' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->where('status', 'preparation')->count(),
            'en_cours' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->where('status', 'en_cours')->count(),
            'publies' => ESBTPLMDJury::where('annee_universitaire_id', $annee->id)->where('status', 'publie')->count(),
        ];

        $parcours = ESBTPLMDParcours::orderBy('name')->get(['id', 'name']);
        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $sessions = ESBTPLMDSession::orderByDesc('date_debut')->get(['id', 'libelle']);
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->get(['id', 'name', 'libelle', 'is_current', 'start_date', 'end_date']);

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
        $readiness = $this->delib->verifierReadiness($jury);
        $stats = $this->delib->buildStatistiques($jury);

        $enseignants = User::query()
            ->select('id', 'name', 'email', 'username')
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();
        $officialDocument = $this->officialDocuments->existingJuryPv($jury);

        return view('esbtp.lmd.jurys.show', compact('jury', 'quorum', 'readiness', 'stats', 'enseignants', 'officialDocument'));
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
        abort_if($jury->isLocked(), 422, 'PV déjà généré , suppression interdite');

        $jury->delete();

        return redirect()->route('esbtp.lmd.jurys.index')->with('success', 'Jury supprimé.');
    }

    /**
     * Ajoute un membre au jury.
     */
    public function addMembre(Request $request, ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.preside'), 403);
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:president,assesseur,secretaire,consultatif'],
            'present' => ['nullable', 'boolean'],
        ]);

        $membre = $this->delib->addOrUpdateMembre($jury, $data);

        return response()->json([
            'success' => true,
            'membre' => [
                'id' => $membre->id,
                'user_id' => $membre->user_id,
                'user_name' => $membre->user?->name,
                'role' => $membre->role,
                'present' => $membre->present,
                'has_signed' => $membre->hasSigned(),
                'can_sign' => $membre->canBeSignedBy((int) auth()->id()) && ! $membre->hasSigned() && ! $jury->isLocked(),
            ],
            'quorum' => $this->delib->verifierQuorum($jury->fresh('membres')),
            'readiness' => $this->delib->verifierReadiness($jury->fresh()),
        ]);
    }

    public function removeMembre(ESBTPLMDJury $jury, ESBTPLMDJuryMembre $membre): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.preside'), 403);
        abort_if($membre->jury_id !== $jury->id, 404);
        $this->delib->removeMembre($jury, $membre);

        return response()->json([
            'success' => true,
            'quorum' => $this->delib->verifierQuorum($jury->fresh('membres')),
            'readiness' => $this->delib->verifierReadiness($jury->fresh()),
        ]);
    }

    public function appliquerAuto(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.deliberate'), 403);

        if (! request()->boolean('confirmed')) {
            return response()->json([
                'success' => false,
                'message' => 'La revue des décisions automatiques est obligatoire avant application.',
                'readiness' => $this->delib->verifierReadiness($jury->fresh()),
            ], 422);
        }

        try {
            $created = $this->delib->appliquerDecisionsAuto($jury);
        } catch (\Throwable $e) {
            Log::warning('Échec du calcul automatique des décisions du jury.', ['jury_id' => $jury->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Les décisions n ont pas pu être calculées.'], 422);
        }

        return response()->json([
            'success' => true,
            'created_count' => $created,
            'stats' => $this->delib->buildStatistiques($jury->fresh('decisions')),
            'readiness' => $this->delib->verifierReadiness($jury->fresh()),
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
            Log::warning('Échec de la décision manuelle du jury.', ['jury_id' => $jury->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => 'La décision n a pas pu être enregistrée.'], 422);
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
            'readiness' => $this->delib->verifierReadiness($jury->fresh()),
        ]);
    }

    public function signerMembre(Request $request, ESBTPLMDJury $jury, ESBTPLMDJuryMembre $membre): JsonResponse
    {
        // Signer n'est pas deliberer. Un enseignant membre du jury doit pouvoir
        // apposer sa signature sans detenir le droit de modifier une decision ;
        // enregistrerSignature() verifie ensuite, sous verrou, qu'il est bien le
        // membre vise (canBeSignedBy). Les detenteurs de lmd.jury.deliberate
        // conservent l'acces : la garde est elargie, jamais restreinte.
        $user = auth()->user();
        abort_unless($user?->can('lmd.jury.sign') || $user?->can('lmd.jury.deliberate'), 403);
        abort_if($membre->jury_id !== $jury->id, 404);
        $data = $request->validate([
            'signature_data' => ['required', 'string', 'max:200000'],
        ]);
        try {
            $this->decodePngSignature($data['signature_data']);
        } catch (\InvalidArgumentException $exception) {
            abort(422, 'La signature PNG fournie est invalide.');
        }

        try {
            $this->delib->enregistrerSignature($membre, $data['signature_data'], (int) auth()->id(), $request->ip(), substr((string) $request->userAgent(), 0, 500));
        } catch (\Throwable $exception) {
            Log::notice('Signature de jury refusée.', ['jury_id' => $jury->id, 'member_id' => $membre->id, 'exception' => $exception]);
            return response()->json(['success' => false, 'message' => 'La signature est déjà enregistrée ou le jury est verrouillé.'], 422);
        }

        return response()->json([
            'success' => true,
            'membre' => [
                'id' => $membre->id,
                'has_signed' => true,
                'can_sign' => false,
                'signature_at' => $membre->fresh()->signature_at?->toIso8601String(),
            ],
            'readiness' => $this->delib->verifierReadiness($jury->fresh()),
        ]);
    }

    public function kpis(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);

        return response()->json([
            'stats' => $this->delib->buildStatistiques($jury->fresh('decisions')),
            'quorum' => $this->delib->verifierQuorum($jury->fresh('membres')),
            'readiness' => $this->delib->verifierReadiness($jury->fresh()),
        ]);
    }

    public function genererPv(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.publish'), 403);

        $readiness = $this->delib->verifierReadiness($jury);
        if (! $readiness['ok']) {
            return response()->json([
                'success' => false,
                'message' => 'Jury non pret : '.implode(', ', $readiness['reasons']),
                'readiness' => $readiness,
            ], 422);
        }

        if ($jury->publie_par && $message = $this->sod->violation('lmd.jury.generate_pv_after_publication', $jury->publie_par, auth()->user())) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        try {
            $path = $this->delib->genererPvDeliberation($jury);
        } catch (\Throwable $e) {
            Log::error('Echec de l emission du PV officiel.', ['jury_id' => $jury->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Le PV officiel n a pas pu etre emis. Verifiez les prerequis du jury.'], 422);
        }

        $document = $this->officialDocuments->existingJuryPv($jury);
        return response()->json([
            'success' => true,
            'pv' => [
                'numero' => $jury->fresh()->pv_numero,
                'reference' => $document?->reference,
                'version' => $document?->version,
                'status' => $document?->status,
                'genere_at' => $jury->fresh()->pv_genere_at?->toIso8601String(),
                'download_url' => route('esbtp.lmd.jurys.pv-download', $jury),
            ],
        ]);
    }
    public function rectifierPv(Request $request, ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.publish'), 403);

        $data = $request->validate([
            'motif' => ['required', 'string', 'min:12', 'max:1000'],
        ]);

        $previousDocument = $this->officialDocuments->existingJuryPv($jury);
        if (! $previousDocument) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun PV officiel existant ne peut etre rectifie.',
            ], 422);
        }

        if ($message = $this->sod->violation('lmd.jury.rectify_pv', $previousDocument->issued_by, auth()->user())) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        try {
            $document = $this->officialDocuments->issueJuryPv($jury, auth()->user(), $data['motif']);
        } catch (\Throwable $e) {
            Log::error('Echec de la rectification du PV officiel.', ['jury_id' => $jury->id, 'exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Le PV rectificatif n a pas pu etre emis. Verifiez le motif et les prerequis du jury.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'PV rectificatif emis. La version precedente est conservee comme remplacee.',
            'document' => [
                'reference' => $document->reference,
                'version' => (int) $document->version,
                'status' => $document->status,
                'checksum_sha256' => $document->checksum_sha256,
                'issued_at' => $document->issued_at?->toIso8601String(),
                'pv_numero' => $jury->fresh()->pv_numero,
                'supersedes_document_id' => $document->supersedes_document_id,
            ],
        ]);
    }
    public function publier(ESBTPLMDJury $jury): JsonResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.publish'), 403);
        $document = $this->officialDocuments->existingJuryPv($jury);
        if ($message = $this->sod->violation('lmd.jury.publish', $document?->issued_by ?? $jury->pv_genere_par, auth()->user())) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        try {
            $this->delib->publierDecisions($jury);
        } catch (\Throwable $e) {
            Log::error('Echec de la publication du jury.', ['jury_id' => $jury->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Le jury n a pas pu etre publie.'], 422);
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
    public function pvDownload(ESBTPLMDJury $jury): RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);
        $document = $this->officialDocuments->existingJuryPv($jury);
        abort_unless($document, 404, 'Aucun document officiel disponible.');
        return redirect()->away($this->officialDownloads->signedUrl($document));
    }

    public function pvPreview(ESBTPLMDJury $jury): Response
    {
        abort_unless(auth()->user()?->can('lmd.jury.view'), 403);
        $document = $this->officialDocuments->existingJuryPv($jury);
        abort_unless($document && is_array($document->snapshot), 404, 'Aucun document officiel disponible.');
        $pdf = $this->pvRenderer->render($document->snapshot, (string) $document->reference);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($document->original_name ?? 'pv.pdf').'"',
        ]);
    }

    public function reconcileLegacyPv(Request $request, ESBTPLMDJury $jury): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()?->can('lmd.jury.documents.reconcile'), 403);
        try {
            $document = $this->legacyReconciliation->reconcile($jury, auth()->user());
        } catch (\Throwable $exception) {
            Log::error('Échec de la réconciliation du PV historique.', ['jury_id' => $jury->id, 'exception' => $exception]);
            if ($request->expectsJson()) return response()->json(['success' => false, 'message' => 'Le PV historique n a pas pu être réconcilié.'], 422);
            return back()->with('error', 'Le PV historique n a pas pu être réconcilié.');
        }
        if (! $request->expectsJson()) return back()->with('success', 'Le PV historique est maintenant inscrit dans le registre officiel.');
        return response()->json(['success' => true, 'document' => ['reference' => $document->reference, 'version' => $document->version, 'status' => $document->status]]);
    }

    private function decodePngSignature(string $signatureData): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/]+={0,2})$/D', $signatureData, $matches)) {
            throw new \InvalidArgumentException('La signature doit être une image PNG encodée en data URL.');
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false || $binary === '' || strlen($binary) > 150000) {
            throw new \InvalidArgumentException('La signature PNG est invalide ou dépasse la taille autorisée.');
        }

        if (! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            throw new \InvalidArgumentException('La signature doit contenir une image PNG réelle.');
        }

        $image = @getimagesizefromstring($binary);
        if ($image === false || ($image['mime'] ?? null) !== 'image/png' || $image[0] > 4096 || $image[1] > 4096) {
            throw new \InvalidArgumentException('La signature doit contenir une image PNG valide de taille raisonnable.');
        }

        return $binary;
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
