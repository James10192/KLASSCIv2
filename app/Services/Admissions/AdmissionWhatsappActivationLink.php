<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Facades\URL;

/**
 * Produit et livre le lien d'activation WhatsApp du parcours géré.
 *
 * La signature Laravel expire au bout de 48 h. Le lien porte en plus la
 * version du jeton courant (`v`) : régénérer le lien rend les précédents
 * inutilisables, et activer le compte les éteint tous.
 */
final class AdmissionWhatsappActivationLink
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly AdmissionActivationNotifier $notifier,
        private readonly ManagedInscriptionWorkflow $managed,
    ) {
    }

    public function url(ESBTPCandidatureWorkflow $workflow): string
    {
        return URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.form',
            now()->addHours(48),
            ['workflow' => $workflow->id, 'v' => ManagedInscriptionWorkflow::linkVersion($workflow)],
        );
    }

    public function isCurrent(ESBTPCandidatureWorkflow $workflow, ?string $version): bool
    {
        $courante = ManagedInscriptionWorkflow::linkVersion($workflow);

        return $courante !== '' && $version !== null && hash_equals($courante, $version);
    }

    public function sendIfDue(ESBTPCandidatureWorkflow $workflow): bool
    {
        $workflow->refresh();

        if (! $this->settings->usesManagedWorkflow()
            || ! $this->settings->notifyWhatsapp()
            || $workflow->accessActivated()
            || ! $workflow->activation_token_hash
            || ! $this->managed->activationMilestoneReached($workflow)) {
            return false;
        }

        return $this->notifier->sendWhatsAppLink(
            $workflow->loadMissing(['candidature', 'etudiant.user']),
            $this->url($workflow),
        );
    }
}
