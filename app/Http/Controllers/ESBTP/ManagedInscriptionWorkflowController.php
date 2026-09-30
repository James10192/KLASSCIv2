<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPPieceDeposee;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagedInscriptionWorkflowController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly InscriptionWorkflowSettings $settings,
    ) {
    }

    public function index()
    {
        $this->guardManagedWorkflow();
        $this->settings->ensureDefaults();

        return view('esbtp.admissions.workflow.index', [
            'candidatures' => $this->managed->cashierQueue(),
            'mode' => $this->settings->mode(),
        ]);
    }

    public function show(ESBTPCandidature $candidature)
    {
        $this->guardManagedWorkflow();
        $workflow = $this->managed->ensure($candidature);

        return view('esbtp.admissions.workflow.show', [
            'candidature' => $candidature->loadMissing(['filiere', 'niveau', 'anneeUniversitaire', 'reservations.creneau']),
            'workflow' => $workflow->loadMissing(['paiement', 'etudiant.user', 'selectedClass', 'finalInscription']),
            'pieces' => $this->managed->provisionalPieces($workflow),
            'mode' => $this->settings->mode(),
            'activationStep' => $this->settings->accountActivationStep(),
            'paymentModes' => config('payment_modes.labels', []),
        ]);
    }

    public function pay(Request $request, ESBTPCandidature $candidature)
    {
        $this->guardManagedWorkflow();
        $allowedModes = array_keys(config('payment_modes.labels', []));

        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', 'string', Rule::in($allowedModes)],
            'reference_paiement' => ['nullable', 'string', 'max:120'],
            'numero_transaction' => ['nullable', 'string', 'max:120'],
        ]);

        $workflow = $this->managed->recordPayment($candidature, $data, (int) Auth::id());

        return redirect()
            ->route('esbtp.admissions.workflow.show', $candidature)
            ->with('success', 'Préinscription encaissée et rattachée à la candidature. Reçu : '.($workflow->paiement?->numero_recu ?? '—'));
    }

    public function receivePiece(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate([
            'piece_id' => ['required', 'integer'],
            'quantite' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $this->managed->receivePiece($workflow, (int) $data['piece_id'], (int) $data['quantite'], (int) Auth::id());

        return back()->with('success', 'Pièce enregistrée dans le dossier physique.');
    }

    public function decidePiece(Request $request, ESBTPCandidatureWorkflow $workflow, ESBTPPieceDeposee $depot)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['valider', 'refuser'])],
            'motif' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->managed->decidePiece(
            $workflow,
            $depot,
            $data['decision'] === 'valider',
            $data['motif'] ?? null,
            (int) Auth::id(),
        );

        return back()->with('success', $data['decision'] === 'valider' ? 'Pièce validée.' : 'Pièce refusée avec motif.');
    }

    public function validateDocuments(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->managed->validateDocuments($workflow, (int) Auth::id());

        return back()->with('success', 'Contrôle physique terminé : le dossier de pièces est complet.');
    }

    public function resendActivation(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();

        $milestoneReached = match ($this->settings->accountActivationStep()) {
            InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS => $workflow->documentsValidated(),
            default => $workflow->paymentRecorded(),
        };

        if (! $milestoneReached) {
            throw ValidationException::withMessages([
                'activation' => "Le dossier n'a pas encore atteint l'étape configurée pour ouvrir l'espace étudiant.",
            ]);
        }

        $result = $this->managed->issueActivation($workflow);

        return back()->with(
            'success',
            $result['email_sent']
                ? "Un nouveau lien d'activation a été envoyé par e-mail."
                : "Le lien d'activation a été régénéré. Vérifiez le canal de notification configuré."
        );
    }

    public function activationForm(string $token)
    {
        $this->guardManagedWorkflow();
        $workflow = $this->managed->workflowForActivationToken($token);

        return view('esbtp.admissions.workflow.activation', [
            'workflow' => $workflow,
            'token' => $token,
        ]);
    }

    public function activate(Request $request, string $token)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $workflow = $this->managed->activate($token, $data['password']);
        Auth::login($workflow->etudiant->user);
        $request->session()->regenerate();

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Votre espace étudiant est activé. Finalisez maintenant votre dossier.');
    }

    public function student(Request $request)
    {
        $this->guardManagedWorkflow();
        $workflow = ESBTPCandidatureWorkflow::query()
            ->with(['candidature', 'etudiant.user', 'paiement', 'selectedClass', 'finalInscription'])
            ->whereHas('etudiant', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('id')
            ->firstOrFail();

        abort_unless($this->managed->belongsToAuthenticatedStudent($workflow, $request->user()), 403);

        return view('esbtp.admissions.workflow.student', [
            'workflow' => $workflow,
            'classes' => $this->managed->eligibleClasses($workflow),
            'classChoiceOnce' => $this->settings->classChoiceOnce(),
        ]);
    }

    public function chooseClass(Request $request)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate(['classe_id' => ['required', 'integer']]);
        $workflow = ESBTPCandidatureWorkflow::query()
            ->with(['candidature', 'etudiant.user'])
            ->whereHas('etudiant', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('id')
            ->firstOrFail();

        abort_unless($this->managed->belongsToAuthenticatedStudent($workflow, $request->user()), 403);
        abort_unless($this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT, 403);

        $workflow = $this->managed->chooseClass($workflow, (int) $data['classe_id'], (int) $request->user()->id);
        $inscription = $this->managed->finalize($workflow, (int) $request->user()->id);

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Votre classe est confirmée et votre inscription académique est finalisée (n° '.$inscription->id.').');
    }

    public function overrideClass(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate([
            'classe_id' => ['required', 'integer'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $workflow = $this->managed->chooseClass($workflow, (int) $data['classe_id'], (int) Auth::id(), true);

        Log::notice('Classe du parcours inscription remplacée par un agent', [
            'workflow_id' => $workflow->id,
            'classe_id' => $workflow->selected_class_id,
            'motif' => $data['motif'],
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Classe remplacée. Le motif a été journalisé.');
    }

    public function finalize(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $inscription = $this->managed->finalize($workflow, (int) Auth::id());

        return redirect()
            ->route('esbtp.inscriptions.show', $inscription)
            ->with('success', 'Inscription académique finalisée depuis la candidature en ligne.');
    }

    private function guardManagedWorkflow(): void
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);
    }
}
