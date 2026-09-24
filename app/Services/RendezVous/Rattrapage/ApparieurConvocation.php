<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Enums\EtatEmail;
use App\Services\Emails\DiagnosticEmail;
use Carbon\CarbonInterface;

/**
 * Choisit, pour UNE reservation, le courriel MailPulse qui l'a convoquee.
 *
 * 1. Candidats : meme reference de dossier, meme action que la convocation.
 * 2. Reservation avec adresse : seuls les courriels partis vers cette adresse
 *    (empreinte). Adresse videe par le nettoyage des adresses fabriquees :
 *    seuls les courriels partis vers un domaine fabrique.
 * 3. Le plus proche de la date d'invitation du dossier, a defaut le plus
 *    recent. Deux candidats a egalite : ambigu, rien n'est attribue.
 *
 * Decide seulement ; n'ecrit rien.
 */
class ApparieurConvocation
{
    public const APPARIEE = 'appariee';

    public const AMBIGUE = 'ambigue';

    public const SANS_MESSAGE = 'sans_message';

    public function __construct(private readonly DiagnosticEmail $classement) {}

    /**
     * @param  list<MessageConvocation>  $candidats  meme reference de dossier
     * @return array{0: string, 1: ?MessageConvocation}
     */
    public function choisir(array $candidats, string $action, ?string $email, ?CarbonInterface $invitation): array
    {
        $retenus = array_values(array_filter(
            $candidats,
            fn (MessageConvocation $m) => $m->action === $action && $this->memeDestinataire($m, $email),
        ));
        if ($retenus === []) {
            return [self::SANS_MESSAGE, null];
        }

        // Distance a la date d'invitation ; sans elle, le plus recent d'abord.
        $cle = $invitation === null
            ? fn (MessageConvocation $m) => -$m->envoyeAt->getTimestamp()
            : fn (MessageConvocation $m) => abs($m->envoyeAt->getTimestamp() - $invitation->getTimestamp());
        usort($retenus, fn ($a, $b) => $cle($a) <=> $cle($b));

        if (count($retenus) > 1 && $cle($retenus[0]) === $cle($retenus[1])) {
            return [self::AMBIGUE, null];
        }

        return [self::APPARIEE, $retenus[0]];
    }

    private function memeDestinataire(MessageConvocation $message, ?string $email): bool
    {
        if ($email !== null && trim($email) !== '') {
            return $message->destinataireSha256 !== null
                && hash_equals(MessageConvocation::empreinte($email), $message->destinataireSha256);
        }

        return $message->destinataireDomaine !== ''
            && $this->classement->classerDomaine($message->destinataireDomaine, false)->etat === EtatEmail::Factice;
    }
}
