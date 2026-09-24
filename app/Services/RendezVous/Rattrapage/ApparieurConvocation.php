<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Enums\EtatEmail;
use App\Services\Emails\DiagnosticEmail;

/**
 * Choisit, pour UNE reservation, le courriel MailPulse qui l'a convoquee.
 *
 * `envoyeAt` d'un courriel est son `createdAt` chez MailPulse : l'instant ou
 * MailPulse a accepte le message, juste AVANT que KLASSCI ne pose
 * `rdv_invite_at` sur le dossier.
 *
 * 1. Candidats : meme reference de dossier, action compatible (une
 *    convocation « confirme » d'avant le suivi a pu partir comme confirmation
 *    ou deplacement ; une annulation ne prend jamais une confirmation), parti
 *    dans la fenetre de la reservation (ContexteReservation).
 * 2. Destinataire : adresse presente, meme empreinte ; adresse videe par le
 *    nettoyage, l'empreinte de l'ancienne adresse si la sauvegarde la donne,
 *    sinon un domaine fabrique.
 * 3. Le plus recent parti au plus tard au dernier envoi au dossier (`ancre`,
 *    a TOLERANCE_ANCRE_SECONDES pres), a defaut le plus recent. Deux
 *    candidats au meme instant : ambigu.
 * 4. Retenu mais parti avant la creation de la reservation alors que le
 *    dossier en avait une precedente : il peut etre celui de la precedente,
 *    ambigu.
 *
 * Decide seulement ; n'ecrit rien.
 */
class ApparieurConvocation
{
    public const APPARIEE = 'appariee';

    public const AMBIGUE = 'ambigue';

    public const SANS_MESSAGE = 'sans_message';

    /**
     * `rdv_invite_at` est pose apres la reponse de MailPulse et tronque a la
     * seconde : le `createdAt` du dernier envoi peut le suivre de quelques
     * fractions de seconde, ou le preceder de la duree de l'appel.
     */
    public const TOLERANCE_ANCRE_SECONDES = 120;

    private const ACTIONS_COMPATIBLES = [
        'confirme' => ['confirme', 'deplace'],
        'deplace' => ['deplace'],
        'annule' => ['annule'],
    ];

    public function __construct(private readonly DiagnosticEmail $classement) {}

    /**
     * @param  list<MessageConvocation>  $candidats  meme reference de dossier
     * @return array{0: string, 1: ?MessageConvocation}
     */
    public function choisir(array $candidats, ContexteReservation $contexte): array
    {
        $actions = self::ACTIONS_COMPATIBLES[$contexte->action] ?? [];
        $depuis = $contexte->depuis();
        $retenus = array_values(array_filter($candidats, fn (MessageConvocation $m) => in_array($m->action, $actions, true)
            && $m->envoyeAt->gte($depuis)
            && $m->envoyeAt->lt($contexte->jusqua)
            && $this->memeDestinataire($m, $contexte)));
        if ($retenus === []) {
            return [self::SANS_MESSAGE, null];
        }

        $limite = $contexte->ancre?->copy()->addSeconds(self::TOLERANCE_ANCRE_SECONDES);
        $avantAncre = $limite === null ? [] : array_values(array_filter(
            $retenus,
            fn (MessageConvocation $m) => $m->envoyeAt->lte($limite),
        ));
        $pool = $avantAncre !== [] ? $avantAncre : $retenus;
        usort($pool, fn ($a, $b) => $b->envoyeAt <=> $a->envoyeAt);

        if (count($pool) > 1 && $pool[0]->envoyeAt->eq($pool[1]->envoyeAt)) {
            return [self::AMBIGUE, null];
        }
        if ($contexte->aUnePrecedente && $pool[0]->envoyeAt->lt($contexte->creeLe)) {
            return [self::AMBIGUE, null];
        }

        return [self::APPARIEE, $pool[0]];
    }

    private function memeDestinataire(MessageConvocation $message, ContexteReservation $contexte): bool
    {
        $empreinte = match ($contexte->modeDestinataire()) {
            ContexteReservation::PAR_ADRESSE => MessageConvocation::empreinte((string) $contexte->email),
            ContexteReservation::PAR_SAUVEGARDE => $contexte->ancienneEmpreinte,
            default => null,
        };
        if ($empreinte !== null) {
            return $message->destinataireSha256 !== null && hash_equals($empreinte, $message->destinataireSha256);
        }

        return $message->destinataireDomaine !== ''
            && $this->classement->classerDomaine($message->destinataireDomaine, false)->etat === EtatEmail::Factice;
    }
}
