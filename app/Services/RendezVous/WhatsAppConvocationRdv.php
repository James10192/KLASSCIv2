<?php

namespace App\Services\RendezVous;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseResult;

/**
 * Remise WhatsApp d'une convocation de rendez-vous.
 *
 * Le PDF reste accessible par son lien signe tant que le contrat MailPulse ne
 * garantit pas encore l'envoi natif d'un document. Le lien est toujours present
 * comme secours, y compris quand l'envoi de documents sera active plus tard.
 */
class WhatsAppConvocationRdv
{
    public function __construct(
        private readonly MailPulseClient $mailpulse,
        private readonly DonneesConvocationRdv $donnees,
    ) {}

    public function expedier(ESBTPRdvReservation $reservation, string $action): MailPulseResult
    {
        $telephone = PhoneNormalizer::toE164((string) $reservation->telephone);
        if ($telephone === null) {
            return new MailPulseResult(
                false,
                'invalid_recipient',
                422,
                null,
                null,
                'invalid_recipient',
                'Le numéro WhatsApp du dossier est invalide.',
                null,
                'failed',
            );
        }

        $this->mailpulse->createOrUpdateContact([
            'phone' => $telephone,
            'first_name' => $reservation->prenoms ?: $reservation->nom,
            'last_name' => $reservation->nom,
            'language' => 'fr',
            'preferred_channel' => 'whatsapp',
            'subscribed' => true,
            'metadata' => [
                'source' => 'klassci-rdv',
                'channel_opt_in' => ['whatsapp' => true],
            ],
        ]);

        $donnees = $this->donnees->pour($reservation, $action);
        $texte = $donnees['intro']
            ."\n\n".$donnees['date'].' '.$donnees['heure']
            .($donnees['lieu'] !== '' ? "\nLieu : ".$donnees['lieu'] : '')
            ."\nRéférence : ".$donnees['reference'];

        if ($donnees['lienPdf'] !== '') {
            $texte .= "\nConvocation PDF : ".$donnees['lienPdf'];
        }

        $texte .= "\nSuivre mon rendez-vous : ".$donnees['lien'];

        return $this->mailpulse->sendWhatsAppMessage([
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => $telephone],
            'content' => [
                'type' => 'text',
                'text' => $texte,
            ],
            'metadata' => [
                'source' => 'klassci',
                'workflow_event' => 'rendez_vous',
                'appointment_reference' => $donnees['reference'],
                'pdf_url' => $donnees['lienPdf'],
            ],
        ]);
    }
}
