<?php

namespace App\Services\Admissions;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\MailPulse\MailPulseClient;
use App\Services\TenantScolariteSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Livraison des accès d'activation sur les canaux activés par le tenant.
 *
 * Un lien d'activation donne le contrôle du futur compte étudiant : il ne part
 * que vers un contact PROUVÉ (code de vérification reçu, ou contact confirmé
 * au guichet). Quand l'établissement n'a pas activé la vérification des
 * contacts, le contact déclaré reste la seule information disponible.
 *
 * Une panne d'un canal ne doit jamais annuler la transition métier : le
 * résultat est journalisé et l'autre canal reste utilisable.
 */
final class AdmissionActivationNotifier
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly MailPulseClient $mailPulse,
        private readonly TenantScolariteSettings $scolarite,
    ) {
    }

    public function emailUsable(ESBTPCandidatureWorkflow $workflow): bool
    {
        $c = $workflow->candidature;

        return $c !== null && (bool) $c->email && $this->contactProuve($c, $c->email_verifie_at);
    }

    public function whatsappUsable(ESBTPCandidatureWorkflow $workflow): bool
    {
        $c = $workflow->candidature;

        return $c !== null && (bool) $c->telephone && $this->contactProuve($c, $c->telephone_verifie_at);
    }

    /**
     * Le lien d'activation peut-il partir par au moins un canal ? Même
     * condition que l'envoi : canal activé par l'école ET contact utilisable.
     */
    public function peutEnvoyer(ESBTPCandidatureWorkflow $workflow): bool
    {
        return ($this->settings->notifyEmail() && $this->emailUsable($workflow))
            || ($this->settings->notifyWhatsapp() && $this->whatsappUsable($workflow));
    }

    public function sendWhatsAppLink(ESBTPCandidatureWorkflow $workflow, string $url): bool
    {
        $workflow->loadMissing('candidature', 'etudiant.user');

        if (! $this->settings->notifyWhatsapp() || ! $this->whatsappUsable($workflow)) {
            return false;
        }

        $username = $workflow->etudiant?->user?->username;
        $message = 'Votre préinscription à '.$this->ecole()." a été validée.\n\n"
            .($username ? "Identifiant KLASSCI : {$username}\n" : '')
            ."Activez votre compte et choisissez votre mot de passe : {$url}\n\n"
            ."Ce lien expire dans 48 heures et ne fonctionne qu'une fois.";

        try {
            $result = $this->mailPulse->sendWhatsAppMessage([
                'to' => $workflow->candidature->telephone,
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
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Activation KLASSCI : exception envoi WhatsApp', [
                'workflow_id' => $workflow->id,
                'exception' => $e::class,
            ]);

            return false;
        }
    }

    public function sendEmail(ESBTPCandidatureWorkflow $workflow, string $url): bool
    {
        $workflow->loadMissing('candidature');

        if (! $this->settings->notifyEmail() || ! $this->emailUsable($workflow)) {
            return false;
        }

        $email = $workflow->candidature->email;
        $ecole = $this->ecole();

        try {
            Mail::raw(
                "Votre dossier d'inscription à {$ecole} a franchi l'étape de préinscription.\n\n"
                ."Activez votre espace étudiant et choisissez votre mot de passe : {$url}\n\n"
                ."Ce lien expire dans 48 heures et ne fonctionne qu'une fois.",
                function ($message) use ($email, $ecole) {
                    $message->to($email)->subject("Activation de votre espace étudiant — {$ecole}");
                },
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Activation KLASSCI : échec envoi e-mail', [
                'workflow_id' => $workflow->id,
                'exception' => $e::class,
            ]);

            return false;
        }
    }

    private function contactProuve(ESBTPCandidature $c, mixed $verifieAt): bool
    {
        return $verifieAt !== null
            || $c->contact_confirme_at !== null
            || ! $this->scolarite->verificationContactActive();
    }

    private function ecole(): string
    {
        return (string) (SettingsHelper::getSchoolInfo()['name'] ?? 'votre établissement');
    }
}
