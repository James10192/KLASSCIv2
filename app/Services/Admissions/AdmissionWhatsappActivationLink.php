<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Facades\URL;

/**
 * Produit et livre le lien d'activation WhatsApp du parcours géré.
 *
 * La signature Laravel expire au bout de 48 h et le contrôleur d'activation
 * refuse en plus tout workflow déjà activé. L'URL ne contient donc ni mot de
 * passe, ni jeton stocké en clair.
 */
final class AdmissionWhatsappActivationLink
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly AdmissionActivationNotifier $notifier,
    ) {
    }

    public function url(ESBTPCandidatureWorkflow $workflow): string
    {
        return URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.form',
            now()->addHours(48),
            ['workflow' => $workflow->id],
        );
    }

    public function sendIfDue(ESBTPCandidatureWorkflow $workflow): bool
    {
        $workflow->refresh();

        if (! $this->settings->usesManagedWorkflow()
            || ! $this->settings->notifyWhatsapp()
            || $workflow->accessActivated()
            || ! $this->milestoneReached($workflow)) {
            return false;
        }

        return $this->notifier->sendWhatsAppLink(
            $workflow->loadMissing(['candidature', 'etudiant.user']),
            $this->url($workflow),
        );
    }

    public function milestoneReached(ESBTPCandidatureWorkflow $workflow): bool
    {
        return match ($this->settings->accountActivationStep()) {
            InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS => $workflow->documentsValidated(),
            default => $workflow->paymentRecorded(),
        };
    }
}
