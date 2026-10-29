<?php

namespace App\Services\Admissions;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\MailPulse\MailPulseClient;
use App\Services\TenantScolariteSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

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
        private readonly AdmissionActivationDispatchLog $dispatches,
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

    /**
     * L'école envoie par e-mail, mais l'e-mail de ce dossier n'est pas prouvé :
     * le lien n'y part pas. Même si WhatsApp est utilisable, l'agent doit
     * pouvoir confirmer l'e-mail : un message WhatsApp peut ne jamais arriver.
     */
    public function emailEnAttente(ESBTPCandidatureWorkflow $workflow): bool
    {
        $workflow->loadMissing('candidature');

        return $this->settings->notifyEmail()
            && (bool) $workflow->candidature?->email
            && ! $this->emailUsable($workflow);
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

        $requestId = 'admission-activation-'.$workflow->id.'-'.substr(hash('sha256', $url), 0, 16);

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
            ], $requestId);

            $this->dispatches->record($workflow, 'whatsapp', $requestId, $result);

            // Une simulation ne constitue pas un message envoyé au destinataire.
            if ($result->status === 'dry_run' || ! $result->isDispatchAccepted()) {
                Log::warning('Activation KLASSCI : échec envoi WhatsApp', [
                    'workflow_id' => $workflow->id,
                    'status' => $result->status,
                    'request_id' => $result->requestId,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->dispatches->record($workflow, 'whatsapp', $requestId);
            Log::warning('Activation KLASSCI : exception envoi WhatsApp', [
                'workflow_id' => $workflow->id,
                'exception' => $e::class,
            ]);

            return false;
        }
    }

    public function sendEmail(ESBTPCandidatureWorkflow $workflow, string $url): bool
    {
        $workflow->loadMissing('candidature', 'etudiant.parents');

        // Conserver la politique de preuve du contact de la candidature.
        if (! $this->settings->notifyEmail() || ! $this->emailUsable($workflow)) {
            return false;
        }

        $c = $workflow->candidature;
        $primaryEmail = mb_strtolower(trim((string) $c->email));
        $emails = [$primaryEmail];

        // Dès que le rattachement familial a été enregistré par la scolarité,
        // un parent accède au même compte que son enfant. Un même lien à usage
        // unique est donc remis INDIVIDUELLEMENT à chaque destinataire.
        $analyseur = app(\App\Services\Emails\AnalyseurEmail::class);
        foreach ($workflow->etudiant?->parents ?? [] as $parent) {
            $parentEmail = mb_strtolower(trim((string) $parent->email));
            if ($parentEmail && $analyseur->analyser($parentEmail)->joignable()) {
                $emails[] = $parentEmail;
            }
        }

        $emails = array_slice(array_values(array_unique($emails)), 0, 10);
        $sujet = 'Activation de votre espace étudiant — '.$this->ecole();
        $texte = "Votre dossier d'inscription a été enregistré.\n\n"
            ."Activez votre espace étudiant KLASSCI et choisissez votre mot de passe : {$url}\n\n"
            ."Ce lien expire dans 48 heures et ne fonctionne qu'une fois.";
        $html = $this->html($workflow, $url);
        $sent = 0;

        foreach ($emails as $email) {
            $requestId = 'admission-activation-email-'.$workflow->id.'-'
                .substr(hash('sha256', $email === $primaryEmail ? $url : $url.'|'.$email), 0, 16);
            try {
                $this->mailPulse->createOrUpdateContact([
                    'email' => $email,
                    'first_name' => $c->prenoms ?: $c->nom,
                    'last_name' => $c->nom,
                    'language' => 'fr',
                    'preferred_channel' => 'email',
                    'subscribed' => true,
                    'metadata' => ['source' => 'klassci-admission', 'channel_opt_in' => ['email' => true]],
                ]);

                $result = $this->mailPulse->sendEmailMessage([
                    'channel' => 'email',
                    'recipient' => ['type' => 'email', 'value' => $email],
                    'content' => ['type' => 'text', 'text' => $texte],
                    'metadata' => [
                        'source' => 'klassci',
                        'workflow_event' => 'admission_activation',
                        'subject' => $sujet,
                        'workflow_id' => $workflow->id,
                    ] + array_filter(['email_html' => $html]),
                ], $requestId);

                $this->dispatches->record($workflow, 'email', $requestId, $result);
                if ($result->status !== 'dry_run' && $result->isDispatchAccepted()) {
                    $sent++;
                } else {
                    Log::warning('Activation KLASSCI : canal familial non accepté', [
                        'workflow_id' => $workflow->id,
                        'status' => $result->status,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->dispatches->record($workflow, 'email', $requestId);
                Log::warning('Activation KLASSCI : échec envoi familial', [
                    'workflow_id' => $workflow->id,
                    'exception' => $e::class,
                ]);
            }
        }

        return $sent > 0;
    }

    /**
     * Le même message, mis en page aux couleurs et au logo de l'école. Une mise
     * en page qui échoue ne doit pas retenir le lien : il part alors en texte.
     */
    private function html(ESBTPCandidatureWorkflow $workflow, string $url): ?string
    {
        try {
            return $this->rendre($workflow, $url);
        } catch (\Throwable $e) {
            Log::warning('Activation KLASSCI : mise en page du courriel impossible, envoi en texte', [
                'workflow_id' => $workflow->id,
                'exception' => $e::class,
            ]);

            return null;
        }
    }

    private function rendre(ESBTPCandidatureWorkflow $workflow, string $url): string
    {
        $c = $workflow->candidature;
        $etapes = [
            'Choisissez votre mot de passe',
            'Vérifiez et complétez votre profil',
            $this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT
                ? 'Choisissez votre classe'
                : "L'établissement vous attribue votre classe",
            'Votre inscription est finalisée',
        ];

        return View::make('esbtp.emails.admission-activation', [
            'nom' => trim(($c->nom ?? '').' '.($c->prenoms ?? '')) ?: 'futur étudiant',
            'url' => $url,
            'heures' => 48,
            'etapes' => $etapes,
        ])->render();
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
