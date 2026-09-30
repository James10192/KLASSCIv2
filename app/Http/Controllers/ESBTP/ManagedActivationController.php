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

    public function signedForm(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $workflow->loadMissing(['candidature', 'etudiant.user']);
        $this->assertActivatable($workflow);

        $submitUrl = URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.submit',
            now()->addMinutes(30),
            ['workflow' => $workflow->id],
        );

        return view('esbtp.admissions.workflow.activation-signed', [
            'workflow' => $workflow,
            'submitUrl' => $submitUrl,
        ]);
    }

    public function signedActivate(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->assertActivatable($workflow);

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

        // Le canal e-mail conserve le jeton aléatoire historique. WhatsApp
        // reçoit une URL Laravel signée, elle aussi expirante et inutilisable
        // dès que le compte est activé.
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
                : "Le lien d'activation a été régénéré, mais aucun canal configuré n'a pu le délivrer.",
        );
    }

    private function assertMilestoneReached(ESBTPCandidatureWorkflow $workflow): void
    {
        $workflow->refresh();

        if (! $this->whatsapp->milestoneReached($workflow)) {
            throw ValidationException::withMessages([
                'activation' => "Le dossier n'a pas encore atteint l'étape configurée pour ouvrir l'espace étudiant.",
            ]);
        }
    }

    private function assertActivatable(ESBTPCandidatureWorkflow $workflow): void
    {
        if ($workflow->accessActivated()) {
            throw ValidationException::withMessages([
                'activation' => 'Cet espace étudiant a déjà été activé.',
            ]);
        }

        $this->assertMilestoneReached($workflow);

        if (! $workflow->etudiant?->user) {
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
