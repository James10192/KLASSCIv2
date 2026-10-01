<?php

namespace App\Mail\Transport;

use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\RefusMailPulse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Une tentative de remise d'UN courriel à MailPulse, partagée par le mailer et
 * par le job qui remet plus tard (`RemettreCourrielMailPulse`).
 *
 * Trois issues, pas deux :
 * - `null` : MailPulse l'a accepté ;
 * - un entier : refus PASSAGER (plafond local, 429 de débit, MailPulse
 *   indisponible ou injoignable, délai dépassé — `RefusMailPulse::PASSAGERS`),
 *   réessayer dans autant de secondes. Rejouer est sans risque : la clé
 *   d'idempotence fait renvoyer par MailPulse le message déjà pris au lieu
 *   d'en créer un second ;
 * - `TransportException` : tout autre refus, journalisé en erreur. Le quota
 *   mensuel en fait partie : il ne passera pas avant le mois suivant.
 */
final class EnvoiMailPulse
{
    /** Attente quand MailPulse ne dit pas combien (pas d'en-tête Retry-After) : une fenêtre de débit. */
    public const ATTENTE_PAR_DEFAUT = 60;

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
                'domaine_destinataire' => substr(strrchr((string) ($charge['recipient']['value'] ?? ''), '@') ?: '', 1),
            ];

            if (in_array($resultat->status, RefusMailPulse::PASSAGERS, true)) {
                // Pas une panne définitive : le courriel repart plus tard, à l'identique.
                Log::warning('Courriel refusé par MailPulse', $journal);

                return $resultat->retryAfter ?? self::ATTENTE_PAR_DEFAUT;
            }

            Log::error('Courriel refusé par MailPulse', $journal);

            // Le message brut de MailPulse peut citer l'adresse : ni au journal, ni dans
            // l'exception, qui finit en clair dans `failed_jobs.exception`.
            throw new TransportException(sprintf(
                'MailPulse n\'a pas accepté le courriel (%s%s%s).',
                $resultat->status,
                $resultat->httpStatus ? ', HTTP '.$resultat->httpStatus : '',
                $resultat->errorCode ? ', code '.$resultat->errorCode : ''
            ), (int) ($resultat->httpStatus ?? 0));
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
