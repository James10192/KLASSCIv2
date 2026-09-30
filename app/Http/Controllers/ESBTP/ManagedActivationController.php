<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\Admissions\AdmissionAccountActivator;
use App\Services\Admissions\AdmissionActivationNotifier;
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
        private readonly AdmissionActivationNotifier $notifier,
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
        // reçoit en parallèle une URL Laravel signée, elle aussi expirante et
        // inutilisable dès que le compte est activé.
        $emailResult = $this->managed->issueActivation($workflow);
        $whatsappUrl = $this->signedUrl($workflow);
        $whatsappSent = $this->notifier->sendWhatsAppLink($workflow->fresh(['candidature', 'etudiant.user']), $whatsappUrl);

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

    public function sendWhatsappIfDue(ESBTPCandidatureWorkflow $workflow): bool
    {
        if (! $this->settings->notifyWhatsapp() || $workflow->accessActivated()) {
            return false;
        }

        if (! $this->milestoneReached($workflow)) {
            return false;
        }

        return $this->notifier->sendWhatsAppLink(
            $workflow->fresh(['candidature', 'etudiant.user']),
            $this->signedUrl($workflow),
        );
    }

    private function signedUrl(ESBTPCandidatureWorkflow $workflow): string
    {
        return URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.form',
            now()->addHours(48),
            ['workflow' => $workflow->id],
        );
    }

    private function assertMilestoneReached(ESBTPCandidatureWorkflow $workflow): void
    {
        if (! $this->milestoneReached($workflow)) {
            throw ValidationException::withMessages([
                'activation' => "Le dossier n'a pas encore atteint l'étape configurée pour ouvrir l'espace étudiant.",
            ]);
        }
    }

    private function milestoneReached(ESBTPCandidatureWorkflow $workflow): bool
    {
        $workflow->refresh();

        return match ($this->settings->accountActivationStep()) {
            InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS => $workflow->documentsValidated(),
            default => $workflow->paymentRecorded(),
        };
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
