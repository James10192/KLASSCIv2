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
        $classChoiceActor = $this->settings->classChoiceActor();

        return view('esbtp.admissions.workflow.show', [
            'candidature' => $candidature->loadMissing(['filiere', 'niveau', 'anneeUniversitaire', 'reservations.creneau']),
            'workflow' => $workflow->loadMissing(['paiement', 'etudiant.user', 'selectedClass', 'finalInscription']),
            'pieces' => $this->managed->provisionalPieces($workflow),
            'mode' => $this->settings->mode(),
            'activationStep' => $this->settings->accountActivationStep(),
            'classChoiceActor' => $classChoiceActor,
            'eligibleClasses' => $classChoiceActor === InscriptionWorkflowSettings::CLASS_ACTOR_ADMIN
                ? $this->managed->eligibleClasses($workflow)
                : collect(),
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
            ->with('success', 'Votre espace étudiant est activé. Complétez maintenant vos informations avant le choix de la classe.');
    }

    public function student(Request $request)
    {
        $this->guardManagedWorkflow();
        $workflow = $this->studentWorkflow($request);

        return view('esbtp.admissions.workflow.student', [
            'workflow' => $workflow,
            'classes' => $this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT
                ? $this->managed->eligibleClasses($workflow)
                : collect(),
            'classChoiceOnce' => $this->settings->classChoiceOnce(),
            'classChoiceActor' => $this->settings->classChoiceActor(),
        ]);
    }

    /**
     * L'étudiant complète uniquement les informations encore nécessaires au
     * dossier. La candidature reste la source des informations d'admission ;
     * cette étape alimente la fiche étudiante provisoire avant finalisation.
     */
    public function updateStudentProfile(Request $request)
    {
        $this->guardManagedWorkflow();
        $workflow = $this->studentWorkflow($request);

        abort_unless($workflow->accessActivated(), 403);

        $data = $request->validate([
            'adresse' => ['required', 'string', 'max:500'],
            'ville' => ['required', 'string', 'max:120'],
            'commune' => ['nullable', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:40'],
            'email_personnel' => ['nullable', 'email', 'max:255'],
            'groupe_sanguin' => ['nullable', 'string', 'max:10'],
            'situation_matrimoniale' => ['nullable', 'string', 'max:50'],
            'nombre_enfants' => ['nullable', 'integer', 'min:0', 'max:30'],
            'urgence_contact_nom' => ['required', 'string', 'max:150'],
            'urgence_contact_telephone' => ['required', 'string', 'max:40'],
            'urgence_contact_relation' => ['nullable', 'string', 'max:80'],
        ]);

        $workflow->etudiant->forceFill([
            'adresse' => $data['adresse'],
            'ville' => $data['ville'],
            'commune' => $data['commune'] ?? null,
            'telephone' => $data['telephone'],
            'email_personnel' => $data['email_personnel'] ?? $workflow->etudiant->email_personnel,
            'groupe_sanguin' => $data['groupe_sanguin'] ?? null,
            'situation_matrimoniale' => $data['situation_matrimoniale'] ?? null,
            'nombre_enfants' => $data['nombre_enfants'] ?? 0,
            'urgence_contact_nom' => $data['urgence_contact_nom'],
            'urgence_contact_telephone' => $data['urgence_contact_telephone'],
            'urgence_contact_relation' => $data['urgence_contact_relation'] ?? null,
            'updated_by' => $request->user()->id,
        ])->save();

        $workflow->forceFill([
            'profile_payload' => $data,
            'profile_completed_at' => now(),
        ])->save();

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Vos informations ont été enregistrées. Vous pouvez poursuivre la finalisation de votre inscription.');
    }

    public function chooseClass(Request $request)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate(['classe_id' => ['required', 'integer']]);
        $workflow = $this->studentWorkflow($request);

        abort_unless($this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT, 403);

        if (! $workflow->profileCompleted()) {
            throw ValidationException::withMessages([
                'profil' => "Complétez d'abord vos informations avant de confirmer votre classe.",
            ]);
        }

        $workflow = $this->managed->chooseClass($workflow, (int) $data['classe_id'], (int) $request->user()->id);
        $inscription = $this->managed->finalize($workflow, (int) $request->user()->id);

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Votre classe est confirmée et votre inscription académique est finalisée (n° '.$inscription->id.').');
    }

    /**
     * Mode alternatif paramétrable : l'administration choisit la classe, sans
     * modifier le code commun ni le parcours des autres tenants.
     */
    public function chooseClassAsAdmin(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        abort_unless($this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_ADMIN, 403);

        $data = $request->validate(['classe_id' => ['required', 'integer']]);
        $this->managed->chooseClass($workflow, (int) $data['classe_id'], (int) Auth::id(), true);

        return back()->with('success', "Classe affectée par l'administration. L'inscription sera finalisée lorsque les autres étapes seront terminées.");
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

        if (! $workflow->profileCompleted()) {
            throw ValidationException::withMessages([
                'profil' => "L'étudiant doit compléter ses informations avant la finalisation de l'inscription.",
            ]);
        }

        $inscription = $this->managed->finalize($workflow, (int) Auth::id());

        return redirect()
            ->route('esbtp.inscriptions.show', $inscription)
            ->with('success', 'Inscription académique finalisée depuis la candidature en ligne.');
    }

    private function studentWorkflow(Request $request): ESBTPCandidatureWorkflow
    {
        $workflow = ESBTPCandidatureWorkflow::query()
            ->with(['candidature', 'etudiant.user', 'paiement', 'selectedClass', 'finalInscription'])
            ->whereHas('etudiant', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('id')
            ->firstOrFail();

        abort_unless($this->managed->belongsToAuthenticatedStudent($workflow, $request->user()), 403);

        return $workflow;
    }

    private function guardManagedWorkflow(): void
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);
    }
}
