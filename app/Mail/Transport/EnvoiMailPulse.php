<?php

namespace App\Mail\Transport;

use App\Services\MailPulse\MailPulseClient;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Une tentative de remise d'UN courriel à MailPulse, partagée par le mailer et
 * par le job qui remet plus tard (`RemettreCourrielMailPulse`).
 *
 * Trois issues, pas deux :
 * - `null` : MailPulse l'a accepté ;
 * - un entier : refus de DÉBIT (plafond local ou 429 de débit de MailPulse),
 *   rien n'est parti, réessayer dans autant de secondes ;
 * - `TransportException` : tout autre refus, journalisé en erreur. Le quota
 *   mensuel en fait partie : il ne passera pas avant le mois suivant.
 */
final class EnvoiMailPulse
{
    /** MailPulse ne renvoie pas de délai exploitable dans son 429 : une fenêtre entière. */
    public const ATTENTE_SUR_429 = 60;

    public function __construct(
        private readonly MailPulseClient $client,
        private readonly CadenceMailPulse $cadence,
    ) {
    }

    public function tenter(array $charge, string $cleIdempotence, array $contexte): ?int
    {
        $attente = $this->cadence->attenteAvantEnvoi();
        if ($attente !== null) {
            return $attente;
        }

        $resultat = $this->client->sendEmailMessage($charge, $cleIdempotence);

        if (! $resultat->ok) {
            $journal = $contexte + [
                'statut' => $resultat->status,
                'http' => $resultat->httpStatus,
                'request_id' => $resultat->requestId,
                'code' => $resultat->errorCode,
                'erreur' => $resultat->message,
                'domaine_destinataire' => substr(strrchr((string) ($charge['recipient']['value'] ?? ''), '@') ?: '', 1),
            ];

            if ($resultat->status === 'rate_limited') {
                // Pas une panne : le courriel repart plus tard, à l'identique.
                Log::warning('Courriel refusé par MailPulse', $journal);

                return self::ATTENTE_SUR_429;
            }

            Log::error('Courriel refusé par MailPulse', $journal);

            throw new TransportException(sprintf(
                'MailPulse n\'a pas accepté le courriel (%s%s) : %s',
                $resultat->status,
                $resultat->httpStatus ? ', HTTP '.$resultat->httpStatus : '',
                $resultat->message ?? 'aucun détail'
            ));
        }

        if (! $resultat->isDispatchAccepted()) {
            // Accepté par MailPulse mais pas encore remis au fournisseur
            // (« pending », « pending_reconciliation ») : MailPulse le rejoue
            // ou le tranche. Une ligne, pour qu'un courriel qui n'arrive pas se
            // retrouve sans deviner.
            Log::warning('Courriel par MailPulse : remise à confirmer', $contexte + [
                'etat' => $resultat->dispatchState,
                'request_id' => $resultat->requestId,
            ]);
        }

        return null;
    }
}
