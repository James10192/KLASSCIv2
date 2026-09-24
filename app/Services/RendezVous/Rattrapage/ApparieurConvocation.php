<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Enums\EtatEmail;
use App\Services\Emails\DiagnosticEmail;

/**
 * Choisit, pour UNE reservation, le courriel MailPulse qui l'a convoquee.
 *
 * 1. Candidats : meme reference de dossier, action compatible (une
 *    convocation « confirme » d'avant le suivi a pu partir comme confirmation
 *    ou deplacement ; une annulation ne prend jamais une confirmation), parti
 *    dans les bornes de la reservation (ContexteReservation).
 * 2. Destinataire : adresse presente, meme empreinte ; adresse videe par le
 *    nettoyage, l'empreinte de l'ancienne adresse si la sauvegarde la donne,
 *    sinon un domaine fabrique.
 * 3. Le plus recent parti au plus tard au dernier envoi au dossier (`ancre`),
 *    a defaut le plus recent. Deux candidats au meme instant : ambigu.
 *
 * Decide seulement ; n'ecrit rien.
 */
class ApparieurConvocation
{
    public const APPARIEE = 'appariee';

    public const AMBIGUE = 'ambigue';

    public const SANS_MESSAGE = 'sans_message';

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
        $retenus = array_values(array_filter($candidats, fn (MessageConvocation $m) => in_array($m->action, $actions, true)
            && $m->envoyeAt->gte($contexte->depuis)
            && $m->envoyeAt->lt($contexte->jusqua)
            && $this->memeDestinataire($m, $contexte)));
        if ($retenus === []) {
            return [self::SANS_MESSAGE, null];
        }

        $avantAncre = $contexte->ancre === null ? [] : array_values(array_filter(
            $retenus,
            fn (MessageConvocation $m) => $m->envoyeAt->lte($contexte->ancre),
        ));
        $pool = $avantAncre !== [] ? $avantAncre : $retenus;
        usort($pool, fn ($a, $b) => $b->envoyeAt->getTimestamp() <=> $a->envoyeAt->getTimestamp());

        if (count($pool) > 1 && $pool[0]->envoyeAt->getTimestamp() === $pool[1]->envoyeAt->getTimestamp()) {
            return [self::AMBIGUE, null];
        }

        return [self::APPARIEE, $pool[0]];
    }

    private function memeDestinataire(MessageConvocation $message, ContexteReservation $contexte): bool
    {
        $email = $contexte->email === null ? '' : trim($contexte->email);
        $empreinte = $email !== '' ? MessageConvocation::empreinte($email) : $contexte->ancienneEmpreinte;
        if ($empreinte !== null) {
            return $message->destinataireSha256 !== null && hash_equals($empreinte, $message->destinataireSha256);
        }

        return $message->destinataireDomaine !== ''
            && $this->classement->classerDomaine($message->destinataireDomaine, false)->etat === EtatEmail::Factice;
    }
}
