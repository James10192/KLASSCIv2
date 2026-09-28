<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseResult;
use Illuminate\Support\Facades\View;

/**
 * Le courriel de convocation : son contenu, et sa remise a MailPulse.
 *
 * Ne decide de rien. Ce qu'il advient de la reservation selon la reponse —
 * envoyee, a relancer, en echec — est l'affaire de MessagerieRdv.
 */
class CourrielConvocationRdv
{
    public function __construct(
        private readonly MailPulseClient $mailpulse,
        private readonly DonneesConvocationRdv $donnees,
    ) {}

    public function expedier(ESBTPRdvReservation $reservation, string $action): MailPulseResult
    {
        $email = trim((string) $reservation->email);

        $this->mailpulse->createOrUpdateContact([
            'email' => $email,
            'first_name' => $reservation->prenoms ?: $reservation->nom,
            'last_name' => $reservation->nom,
            'language' => 'fr',
            'preferred_channel' => 'email',
            'subscribed' => true,
            'metadata' => [
                'source' => 'klassci-rdv',
                'channel_opt_in' => ['email' => true],
            ],
        ]);

        $donnees = $this->donnees->pour($reservation, $action);
        $texte = $donnees['sujet']."\n\n".$donnees['date'].' '.$donnees['heure']."\nRéférence : ".$donnees['reference'];
        if ($donnees['lienPdf'] !== '') {
            $texte .= "\nPDF : ".$donnees['lienPdf'];
        }
        $html = View::make('esbtp.emails.parents.rendez-vous-convocation', $donnees)->render();

        return $this->mailpulse->sendEmailMessage([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'content' => [
                'type' => 'text',
                'text' => $texte,
            ],
            'metadata' => [
                'source' => 'klassci',
                'workflow_event' => 'rendez_vous',
                'subject' => $donnees['sujet'],
                'email_html' => $html,
                'pdf_url' => $donnees['lienPdf'],
            ],
        ]);
    }
}
