<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Notifications\PhoneNormalizer;
use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use App\Services\Admissions\ManagedWorkflowPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Lecture du dossier (guichets et étudiant), activation par e-mail, profil et
 * affectation de classe par l'administration.
 *
 * Les écritures physiques (caisse, pièces) vivent dans
 * ManagedInscriptionStepController ; la finalisation dans
 * ManagedInscriptionCompletionController. Ce contrôleur n'en garde aucune copie.
 */
class ManagedInscriptionWorkflowController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly InscriptionWorkflowSettings $settings,
        private readonly ManagedWorkflowPresenter $presenter,
    ) {
    }

    public function show(ESBTPCandidature $candidature)
    {
        $this->guardManagedWorkflow();
        $workflow = $this->managed->ensure($candidature);
        $classChoiceActor = $this->settings->classChoiceActor();

        return view('esbtp.admissions.workflow.show', [
            'candidature' => $candidature->loadMissing(['filiere', 'niveau', 'anneeUniversitaire', 'reservations.creneau']),
            'workflow' => $workflow->loadMissing(['paiement.fraisCategory', 'etudiant.user', 'selectedClass', 'finalInscription']),
            'pieces' => $this->managed->provisionalPieces($workflow),
            'etapes' => $this->presenter->etapes($workflow),
            'prochaineEtape' => $this->presenter->prochaineEtape($workflow),
            'contactJoignable' => $this->presenter->contactJoignable($workflow),
            'emailEnAttente' => $this->presenter->emailEnAttente($workflow),
            'activationStep' => $this->settings->accountActivationStep(),
            'classChoiceActor' => $classChoiceActor,
            'eligibleClasses' => $workflow->final_inscription_id ? collect() : $this->managed->eligibleClasses($workflow),
            'fraisEncaissables' => $workflow->paymentRecorded() ? collect() : $this->managed->fraisEncaissables($candidature),
            'paymentModes' => config('payment_modes.labels', []),
        ]);
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
            'etapes' => $this->presenter->etapes($workflow),
            'classes' => $this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT
                && ! $workflow->final_inscription_id
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

        if ($workflow->final_inscription_id) {
            throw ValidationException::withMessages([
                'profil' => 'Votre inscription est finalisée : modifiez vos informations depuis votre profil.',
            ]);
        }

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

        foreach (['telephone', 'urgence_contact_telephone'] as $champ) {
            if (! PhoneNormalizer::isValid($data[$champ])) {
                throw ValidationException::withMessages([$champ => 'Numéro de téléphone invalide.']);
            }
            $data[$champ] = PhoneNormalizer::toE164($data[$champ]);
        }

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
        ]);
        $workflow->state = ManagedInscriptionWorkflow::stateFor($workflow, $this->settings->mode());
        $workflow->save();

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Vos informations ont été enregistrées. Vous pouvez poursuivre la finalisation de votre inscription.');
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
        $this->managed->assignClassByAdministration($workflow, (int) $data['classe_id'], (int) Auth::id());

        return back()->with('success', "Classe affectée. La place est réservée au moment de la finalisation de l'inscription.");
    }

    /**
     * Correction exceptionnelle AVANT finalisation (étudiant bloqué, choix
     * erroné). Après finalisation, le service refuse : la correction passe par
     * le changement de classe officiel de l'inscription, qui recalcule les frais.
     */
    public function overrideClass(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $data = $request->validate([
            'classe_id' => ['required', 'integer'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $workflow = $this->managed->assignClassByAdministration($workflow, (int) $data['classe_id'], (int) Auth::id());

        Log::notice('Classe du parcours inscription remplacée par un agent', [
            'workflow_id' => $workflow->id,
            'classe_id' => $workflow->selected_class_id,
            'motif' => $data['motif'],
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Classe remplacée. Le motif a été journalisé.');
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
