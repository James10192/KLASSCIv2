<?php

namespace App\Mail\Transport;

use App\Jobs\MailPulse\RemettreCourrielMailPulse;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
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
 * - Cc / Cci : chacun reçoit son propre message ;
 * - plafond de 60 par minute et par organisation : voir `CadenceMailPulse`.
 *   Un courriel retenu pour débit (plafond local ou 429) n'est PAS une erreur
 *   pour l'appelant : il est confié à `RemettreCourrielMailPulse`, qui le remet
 *   plus tard. Sans file (`QUEUE_CONNECTION=sync`), impossible de différer : il
 *   est refusé comme les autres.
 *
 * Tout autre refus de MailPulse lève une `TransportException` : les appelants
 * qui affichent « le courriel n'a pas pu partir » continuent de le dire.
 */
final class MailPulseTransport extends AbstractTransport
{
    /** Plafond de l'objet `metadata` côté MailPulse (`MAX_METADATA_BYTES`). */
    public const PLAFOND_METADATA = 16384;

    /** Place laissée aux autres clés et à l'écart de mesure entre PHP et JavaScript. */
    private const MARGE = 512;

    public function __construct(
        private readonly MailPulseClient $client,
        private readonly EnvoiMailPulse $envoi,
    ) {
        parent::__construct();
    }

    /**
     * Clé d'idempotence STABLE d'une tentative à l'autre : destinataire et
     * contenu, rangés dans l'heure. Une file qui rejoue un courriel (ou un
     * envoi en copie interrompu au deuxième destinataire) retombe sur la même
     * clé, et MailPulse ne le renvoie pas. L'heure borne la conséquence
     * inverse : MailPulse garde ses clés sans limite, donc sans elle un même
     * texte au même destinataire ne repartirait JAMAIS. Prix assumé : deux
     * courriels identiques au même destinataire dans la même heure n'en font
     * qu'un, et une reprise qui franchit l'heure peut doubler.
     */
    public static function cleIdempotence(array $charge, ?int $instant = null): string
    {
        $heure = intdiv($instant ?? time(), 3600);

        return 'klassci-mail-'.hash('sha256', $heure.'|'.json_encode([
            strtolower((string) ($charge['recipient']['value'] ?? '')),
            $charge['content'] ?? null,
            $charge['metadata'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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

        $corps = $this->corps($email, $contexte);
        $destinataires = $message->getEnvelope()->getRecipients();
        foreach ($destinataires as $rang => $destinataire) {
            $charge = ['channel' => 'email', 'recipient' => ['type' => 'email', 'value' => $destinataire->getAddress()]] + $corps;
            $requestId = self::cleIdempotence($charge);

            $attente = $this->envoi->tenter($charge, $requestId, $contexte);
            if ($attente !== null) {
                $this->differer($charge, $requestId, $attente, $contexte + [
                    'rang' => $rang,
                    'destinataires' => count($destinataires),
                ]);
            }
        }
    }

    /**
     * Refus de débit : un job par destinataire, qui remettra ce message-là.
     * Une file `sync` exécuterait le job sur-le-champ, donc retomberait sur le
     * même refus : on refuse alors franchement, comme avant.
     */
    private function differer(array $charge, string $requestId, int $attente, array $contexte): void
    {
        $journal = $contexte + [
            'reessayer_dans' => $attente,
            'domaine_destinataire' => substr(strrchr((string) $charge['recipient']['value'], '@') ?: '', 1),
        ];

        if (config('queue.default') === 'sync') {
            Log::error('Courriel par MailPulse : débit atteint et aucune file pour différer, envoi refusé', $journal);

            throw new TransportException(
                "Débit d'envoi MailPulse atteint et aucune file d'attente (QUEUE_CONNECTION=sync) : le courriel n'est pas parti, réessayez dans {$attente} s."
            );
        }

        RemettreCourrielMailPulse::dispatch($charge, $requestId, $contexte)->delay(now()->addSeconds($attente));
        Log::warning('Courriel par MailPulse : envoi différé', $journal);
    }

    /**
     * Ce qui est commun à tous les destinataires : calculé une fois, journalisé une fois.
     *
     * @return array{content: array<string, string>, metadata: array<string, string>}
     */
    private function corps(Email $email, array $contexte): array
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
            'content' => ['type' => 'text', 'text' => $texte],
            'metadata' => $metadata,
        ];
    }

    /**
     * L'adresse : le réglage `mailpulse_sender_email` s'il est posé, sinon celle
     * du courriel (`MAIL_FROM_ADDRESS`). MailPulse retombe sur l'expéditeur par
     * défaut de l'organisation si son domaine n'est pas vérifié chez lui.
     *
     * Le nom : le réglage `mailpulse_sender_name`, et lui seul. Il vaut
     * `KLASSCI` tant que l'école n'en pose pas un autre (config `services`) ;
     * `MAIL_FROM_NAME` ne sert pas, il n'aurait jamais l'occasion de servir.
     *
     * @return array{adresse: string, nom: string}
     */
    private function expediteur(Email $email): array
    {
        $from = $email->getFrom()[0] ?? null;

        return [
            'adresse' => $this->client->getSetting('mailpulse_sender_email', 'sender_email', '') ?: (string) $from?->getAddress(),
            'nom' => $this->client->getSetting('mailpulse_sender_name', 'sender_name', ''),
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
