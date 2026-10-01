<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\Admissions\AdmissionAccountActivator;
use App\Services\Admissions\AdmissionWhatsappActivationLink;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

final class ManagedActivationController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly InscriptionWorkflowSettings $settings,
        private readonly AdmissionWhatsappActivationLink $whatsapp,
        private readonly AdmissionAccountActivator $activator,
    ) {
    }

    public function signedForm(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $workflow->loadMissing(['candidature', 'etudiant.user']);
        $this->assertActivatable($workflow, $request->query('v'));

        $submitUrl = URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.submit',
            now()->addMinutes(30),
            ['workflow' => $workflow->id, 'v' => $request->query('v')],
        );

        return view('esbtp.admissions.workflow.activation-signed', [
            'workflow' => $workflow,
            'submitUrl' => $submitUrl,
        ]);
    }

    public function signedActivate(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->assertActivatable($workflow, $request->query('v'));

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $workflow = $this->activator->activateWorkflow($workflow, $data['password']);
        Auth::login($workflow->etudiant->user);
        $request->session()->regenerate();

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Votre espace étudiant est activé. Complétez maintenant vos informations avant le choix de la classe.');
    }

    public function resend(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->assertMilestoneReached($workflow);

        if ($workflow->accessActivated()) {
            throw ValidationException::withMessages(['activation' => 'Cet espace étudiant est déjà activé.']);
        }

        // Le canal e-mail conserve le jeton aléatoire historique. WhatsApp
        // reçoit une URL Laravel signée portant la version de ce jeton : la
        // régénération ci-dessous rend donc caducs TOUS les anciens liens.
        $emailResult = $this->managed->issueActivation($workflow);
        $whatsappSent = $this->whatsapp->sendIfDue($workflow);

        $channels = [];
        if ($emailResult['email_sent'] ?? false) {
            $channels[] = 'e-mail';
        }
        if ($whatsappSent) {
            $channels[] = 'WhatsApp';
        }

        return back()->with(
            'success',
            $channels
                ? 'Nouveau lien d’activation envoyé par '.implode(' et ', $channels).'.'
                : "Lien régénéré, mais aucun contact vérifié ne permet de l'envoyer. Vérifiez l'e-mail ou le numéro du candidat, puis renvoyez.",
        );
    }

    private function assertMilestoneReached(ESBTPCandidatureWorkflow $workflow): void
    {
        $workflow->refresh();

        if (! $this->managed->activationMilestoneReached($workflow)) {
            throw ValidationException::withMessages([
                'activation' => "Le dossier n'a pas encore atteint l'étape configurée pour ouvrir l'espace étudiant.",
            ]);
        }
    }

    private function assertActivatable(ESBTPCandidatureWorkflow $workflow, ?string $version): void
    {
        $workflow->refresh();

        if (! $this->whatsapp->isCurrent($workflow, $version) && ! $workflow->accessActivated()) {
            throw ValidationException::withMessages([
                'activation' => "Ce lien a été remplacé par un lien plus récent. Utilisez le dernier message reçu.",
            ]);
        }

        if ($workflow->accessActivated()) {
            throw ValidationException::withMessages([
                'activation' => 'Cet espace étudiant a déjà été activé.',
            ]);
        }

        $this->assertMilestoneReached($workflow);

        if (! $workflow->loadMissing('etudiant.user')->etudiant?->user) {
            throw ValidationException::withMessages([
                'compte' => "Le compte étudiant n'a pas encore été préparé.",
            ]);
        }
    }

    private function guardManagedWorkflow(): void
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);
    }
}
