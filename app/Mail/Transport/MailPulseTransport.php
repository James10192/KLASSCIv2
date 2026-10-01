<?php

namespace App\Mail\Transport;

use App\Services\MailPulse\MailPulseClient;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Le mailer `mailpulse` : tout courriel de Laravel (`Mail::`, canal `mail` des
 * notifications, mailables en file) part par l'API publique de MailPulse
 * (`POST /api/v1/messages`), un message par destinataire.
 *
 * Ce que l'API ne transporte pas, et ce qu'on en fait :
 * - pièce jointe : REFUS (exception). Un courriel « ci-joint votre export »
 *   parti sans l'export serait un succès mensonger ;
 * - image intégrée (`cid:`) : retirée du HTML, journalisée. Le gabarit commun
 *   passe par l'URL du logo quand ce mailer est actif ;
 * - HTML qui dépasse le plafond de `metadata` : remplacé par la version texte,
 *   journalisé. Le contenu arrive, sans la mise en page ;
 * - Reply-To : non transmis, journalisé ;
 * - Cc / Cci : chacun reçoit son propre message.
 *
 * Tout refus de MailPulse lève une `TransportException` : les appelants qui
 * affichent « le courriel n'a pas pu partir » continuent de le dire.
 */
final class MailPulseTransport extends AbstractTransport
{
    /** Plafond de l'objet `metadata` côté MailPulse (`MAX_METADATA_BYTES`). */
    public const PLAFOND_METADATA = 16384;

    /** Place laissée aux autres clés et à l'écart de mesure entre PHP et JavaScript. */
    private const MARGE = 512;

    public function __construct(private readonly MailPulseClient $client)
    {
        parent::__construct();
    }

    /** Le mailer par défaut de l'instance est-il celui-ci ? Le gabarit en dépend pour son logo. */
    public static function actif(): bool
    {
        return config('mail.default') === 'mailpulse';
    }

    public function __toString(): string
    {
        return 'mailpulse';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $contexte = ['message_id' => $message->getMessageId(), 'sujet_octets' => strlen((string) $email->getSubject())];

        $this->refuserLesPiecesJointes($email, $contexte);
        $this->signalerLeReplyTo($email, $contexte);

        $destinataires = $message->getEnvelope()->getRecipients();
        foreach ($destinataires as $rang => $destinataire) {
            $requestId = 'klassci-mail-'.hash('sha256', $message->getMessageId().'|'.strtolower($destinataire->getAddress()));
            $resultat = $this->client->sendEmailMessage($this->charge($email, $destinataire, $contexte), $requestId);

            if (! $resultat->ok) {
                Log::error('Courriel refusé par MailPulse', $contexte + [
                    'statut' => $resultat->status,
                    'http' => $resultat->httpStatus,
                    'request_id' => $resultat->requestId,
                    'code' => $resultat->errorCode,
                    'erreur' => $resultat->message,
                    'domaine_destinataire' => substr(strrchr($destinataire->getAddress(), '@') ?: '', 1),
                    'deja_partis' => $rang,
                    'destinataires' => count($destinataires),
                ]);

                throw new TransportException(sprintf(
                    'MailPulse n\'a pas accepté le courriel (%s%s) : %s',
                    $resultat->status,
                    $resultat->httpStatus ? ', HTTP '.$resultat->httpStatus : '',
                    $resultat->message ?? 'aucun détail'
                ));
            }
        }
    }

    /** @return array<string, mixed> */
    private function charge(Email $email, Address $destinataire, array $contexte): array
    {
        $sujet = trim((string) $email->getSubject());
        $html = $email->getHtmlBody();
        $html = is_string($html) ? $html : (is_resource($html) ? (string) stream_get_contents($html) : null);
        $texte = $email->getTextBody();
        $texte = is_string($texte) ? $texte : (is_resource($texte) ? (string) stream_get_contents($texte) : null);

        if ($html !== null) {
            [$html, $retirees] = CorpsPourMailPulse::sansPiecesIntegrees($html);
            if ($retirees > 0) {
                Log::warning('Courriel par MailPulse : images intégrées retirées', $contexte + ['images' => $retirees]);
            }
            $html = CorpsPourMailPulse::resserrer($html);
        }

        if ($texte === null || trim($texte) === '') {
            $texte = $html !== null ? CorpsPourMailPulse::texteDepuisHtml($html) : '';
        }
        if (trim($texte) === '') {
            $texte = $sujet !== '' ? $sujet : ' ';
        }

        $expediteur = $this->expediteur($email);
        $metadata = array_filter([
            'source' => 'klassci',
            'workflow_event' => 'laravel_mail',
            'external_tenant_id' => (string) config('app.tenant_code', ''),
            'subject' => $sujet,
            'sender_email' => $expediteur['adresse'],
            'sender_name' => $expediteur['nom'],
        ], fn ($valeur) => $valeur !== '');

        if ($html !== null && $html !== '') {
            $avecHtml = $metadata + ['email_html' => $html];
            if (CorpsPourMailPulse::octetsJson($avecHtml) <= self::PLAFOND_METADATA - self::MARGE) {
                $metadata = $avecHtml;
            } else {
                Log::warning('Courriel par MailPulse : HTML trop volumineux, version texte envoyée', $contexte + [
                    'octets' => CorpsPourMailPulse::octetsJson($avecHtml),
                    'plafond' => self::PLAFOND_METADATA - self::MARGE,
                ]);
            }
        }

        return [
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $destinataire->getAddress()],
            'content' => ['type' => 'text', 'text' => $texte],
            'metadata' => $metadata,
        ];
    }

    /**
     * L'adresse réglée pour MailPulse prime : c'est elle que l'école a fait
     * vérifier. À défaut, celle du courriel ; MailPulse retombe sur
     * l'expéditeur par défaut de l'organisation si son domaine n'est pas vérifié.
     *
     * @return array{adresse: string, nom: string}
     */
    private function expediteur(Email $email): array
    {
        $from = $email->getFrom()[0] ?? null;

        return [
            'adresse' => $this->client->getSetting('mailpulse_sender_email', 'sender_email', '') ?: (string) $from?->getAddress(),
            'nom' => trim((string) $from?->getName()) ?: $this->client->getSetting('mailpulse_sender_name', 'sender_name', ''),
        ];
    }

    private function refuserLesPiecesJointes(Email $email, array $contexte): void
    {
        $jointes = array_filter($email->getAttachments(), fn ($part) => $part->getDisposition() !== 'inline');
        if ($jointes === []) {
            return;
        }

        Log::error('Courriel avec pièce jointe : MailPulse ne la transporte pas', $contexte + ['pieces_jointes' => count($jointes)]);

        throw new TransportException('MailPulse ne transporte pas les pièces jointes : le courriel n\'est pas parti.');
    }

    private function signalerLeReplyTo(Email $email, array $contexte): void
    {
        if ($email->getReplyTo() !== []) {
            Log::warning('Courriel par MailPulse : adresse de réponse non transmise', $contexte);
        }
    }
}
