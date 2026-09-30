<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Livraison du même lien d'activation sur les canaux activés par le tenant.
 *
 * Une panne d'un canal ne doit jamais annuler la transition métier : le
 * résultat est journalisé et l'autre canal reste utilisable.
 */
final class AdmissionActivationNotifier
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly MailPulseClient $mailPulse,
    ) {
    }

    /** @return array{email_sent:bool,whatsapp_sent:bool} */
    public function send(ESBTPCandidatureWorkflow $workflow, string $url): array
    {
        $workflow->loadMissing('candidature', 'etudiant.user');

        return [
            'email_sent' => $this->sendEmail($workflow, $url),
            'whatsapp_sent' => $this->sendWhatsApp($workflow, $url),
        ];
    }

    private function sendEmail(ESBTPCandidatureWorkflow $workflow, string $url): bool
    {
        $email = $workflow->candidature?->email ?: $workflow->etudiant?->email_personnel;
        if (! $this->settings->notifyEmail() || ! $email) {
            return false;
        }

        try {
            Mail::raw(
                "Votre dossier ESBTP a franchi l'étape de préinscription.\n\n"
                ."Activez votre espace KLASSCI et choisissez votre mot de passe : {$url}\n\n"
                ."Ce lien expire dans 48 heures et ne fonctionne qu'une fois.",
                function ($message) use ($email) {
                    $message->to($email)->subject('Activation de votre espace étudiant KLASSCI');
                },
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Activation KLASSCI : échec envoi e-mail', [
                'workflow_id' => $workflow->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendWhatsApp(ESBTPCandidatureWorkflow $workflow, string $url): bool
    {
        $telephone = $workflow->candidature?->telephone ?: $workflow->etudiant?->telephone;
        if (! $this->settings->notifyWhatsapp() || ! $telephone) {
            return false;
        }

        $username = $workflow->etudiant?->user?->username;
        $message = "Votre préinscription ESBTP a été validée.\n\n"
            .($username ? "Identifiant KLASSCI : {$username}\n" : '')
            ."Activez votre compte et choisissez votre mot de passe : {$url}\n\n"
            ."Ce lien expire dans 48 heures et ne fonctionne qu'une fois.";

        try {
            $result = $this->mailPulse->sendWhatsAppMessage([
                'to' => $telephone,
                'text' => $message,
                'external_id' => 'admission-activation-'.$workflow->id,
                'metadata' => [
                    'source' => 'klassci_admission',
                    'workflow_id' => $workflow->id,
                    'candidature_id' => $workflow->candidature_id,
                    'purpose' => 'student_account_activation',
                ],
            ], 'admission-activation-'.$workflow->id.'-'.substr(hash('sha256', $url), 0, 16));

            if (! $result->isDispatchAccepted()) {
                Log::warning('Activation KLASSCI : échec envoi WhatsApp', [
                    'workflow_id' => $workflow->id,
                    'status' => $result->status,
                    'request_id' => $result->requestId,
                    'message' => $result->message,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Activation KLASSCI : exception envoi WhatsApp', [
                'workflow_id' => $workflow->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
