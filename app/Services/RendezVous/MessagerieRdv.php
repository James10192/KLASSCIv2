<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseResult;
use App\Services\MailPulse\RefusMailPulse;
use App\Support\ColonnesDeployees;
use Illuminate\Support\Facades\Log;

class MessagerieRdv
{
    /** Au-dela, une erreur passagere devient un echec que l'ecole doit relancer. */
    public const MAX_TENTATIVES = 5;

    /**
     * Refus qui tiennent a la CONFIGURATION, pas au destinataire : ils frapperont
     * toutes les convocations suivantes a l'identique. Ils ne consomment pas de
     * tentative, et arretent un lot au lieu de le parcourir pour rien.
     */
    private const REFUS_DE_CONFIGURATION = RefusMailPulse::CONFIGURATION;

    private const REFUS_PASSAGERS = RefusMailPulse::PASSAGERS;

    public function __construct(private readonly CourrielConvocationRdv $courriel)
    {
    }

    /**
     * Pose la convocation en attente, sans rien envoyer. L'envoi n'a qu'une
     * porte, FileConvocationsRdv, qui tient le verrou.
     */
    public function planifier(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        $reservation->forceFill([
            'convocation_statut' => $this->emailValide($reservation)
                ? StatutConvocationRdv::EnAttente
                : StatutConvocationRdv::SansEmail,
            'convocation_action' => $action,
            'convocation_tentatives' => 0,
            'convocation_envoyee_at' => null,
            'convocation_erreur' => null,
            'convocation_message_id' => null,
            'prevenue_par' => null,
        ] + $this->suiviDistantEfface())->save();
    }

    /**
     * Le suivi MailPulse d'une convocation precedente, remis a zero. Colonnes
     * absentes entre le pull et le migrate du deploiement : rien a effacer.
     *
     * @return array<string, null>
     */
    private function suiviDistantEfface(): array
    {
        if (! ColonnesDeployees::existe('esbtp_rdv_reservations', 'convocation_code_distant')) {
            return [];
        }

        return ['convocation_delivree_at' => null, 'convocation_synchro_at' => null, 'convocation_code_distant' => null];
    }

    /**
     * Tente l'envoi et consigne l'issue sur la reservation. Ne leve jamais : une
     * convocation qui echoue ne doit pas emporter celles qui la suivent.
     *
     * @return string|null la raison qui bloquera aussi les envois suivants
     *                     (MailPulse desactive, cle absente...), sinon null
     */
    public function envoyer(ESBTPRdvReservation $reservation): ?string
    {
        $reservation->loadMissing('creneau');
        $action = $reservation->convocation_action ?: 'confirme';

        if (! $this->emailValide($reservation)) {
            $this->consigner($reservation, StatutConvocationRdv::SansEmail, null);

            return null;
        }

        if ($action !== 'annule' && $this->creneauPasse($reservation)) {
            $this->consigner($reservation, StatutConvocationRdv::SansObjet, 'Le créneau est passé avant l\'envoi.');

            return null;
        }

        try {
            $resultat = $this->courriel->expedier($reservation, $action);
        } catch (\Throwable $e) {
            Log::warning('Convocation rdv : erreur inattendue', [
                'reservation_id' => $reservation->id,
                'erreur' => $e->getMessage(),
            ]);

            $this->echecPassager($reservation, $e->getMessage());

            return null;
        }

        if ($resultat->ok) {
            $reservation->forceFill([
                'convocation_statut' => StatutConvocationRdv::Envoyee,
                'convocation_envoyee_at' => now(),
                'convocation_erreur' => null,
                'convocation_message_id' => $resultat->id ? mb_substr($resultat->id, 0, 100) : null,
                'convocation_tentatives' => $reservation->convocation_tentatives + 1,
            ])->save();
            $reservation->porteur()?->marquerInviteRdv();

            return null;
        }

        $motif = $this->motifLisible($resultat);
        Log::warning('Convocation rdv refusée par MailPulse', [
            'reservation_id' => $reservation->id,
            'statut' => $resultat->status,
            'message' => $resultat->message,
        ]);

        if (in_array($resultat->status, self::REFUS_DE_CONFIGURATION, true)) {
            $reservation->forceFill(['convocation_erreur' => mb_substr($motif, 0, 255)])->save();

            return $motif;
        }

        // Injoignable ou sature : les suivantes echoueraient pareil. On compte la
        // tentative de celle-ci et on arrete le lot, plutot que d'user celles des autres.
        if (in_array($resultat->status, self::REFUS_PASSAGERS, true)) {
            $this->echecPassager($reservation, $motif);

            return $motif;
        }

        $this->consigner($reservation, StatutConvocationRdv::Echec, $motif);

        return null;
    }

    private function echecPassager(ESBTPRdvReservation $reservation, string $motif): void
    {
        $tentatives = $reservation->convocation_tentatives + 1;
        $reservation->forceFill([
            'convocation_tentatives' => $tentatives,
            'convocation_statut' => $tentatives >= self::MAX_TENTATIVES
                ? StatutConvocationRdv::Echec
                : StatutConvocationRdv::EnAttente,
            'convocation_erreur' => mb_substr($motif, 0, 255),
        ])->save();
    }

    private function consigner(ESBTPRdvReservation $reservation, StatutConvocationRdv $statut, ?string $erreur): void
    {
        $reservation->forceFill([
            'convocation_statut' => $statut,
            'convocation_erreur' => $erreur === null ? null : mb_substr($erreur, 0, 255),
        ])->save();
    }

    private function emailValide(ESBTPRdvReservation $reservation): bool
    {
        // Une adresse fabriquee (`@esbtp.edu.ci`) ou une faute connue
        // (`gmail.con`) rebondirait : la famille est « sans e-mail », donc a
        // appeler, plutot que convoquee dans le vide. Meme conduite pour un
        // contact que la famille n'a jamais confirme, tant que l'ecole ne l'a
        // pas confirme elle-meme.
        $porteur = $reservation->porteur();
        if ($porteur instanceof \Illuminate\Database\Eloquent\Model && method_exists($porteur, 'contactAConfirmer') && $porteur->contactAConfirmer()) {
            return false;
        }

        return app(\App\Services\Emails\AnalyseurEmail::class)->analyser($reservation->email)->joignable();
    }

    private function creneauPasse(ESBTPRdvReservation $reservation): bool
    {
        $creneau = $reservation->creneau;
        if ($creneau === null || $creneau->date === null) {
            return false;
        }

        return $creneau->debut()->isPast();
    }

    /**
     * La raison telle que l'ecole la lira sur la reservation. MailPulse renvoie
     * parfois l'objet d'erreur brut en guise de message : on garde alors le code.
     */
    private function motifLisible(MailPulseResult $resultat): string
    {
        $message = trim((string) $resultat->message);
        $code = $resultat->errorCode ?: $resultat->status;

        if ($message === '' || str_starts_with($message, '{') || str_starts_with($message, '[')) {
            $message = 'Refusé par MailPulse';
        }

        return $message.' ('.$code.')';
    }
}
