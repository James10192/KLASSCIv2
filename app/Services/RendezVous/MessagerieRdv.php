<?php

namespace App\Services\RendezVous;

use App\Domain\Notifications\PhoneNormalizer;
use App\Enums\CanalConvocationRdv;
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

    /** Les refus de configuration toucheraient tous les canaux suivants. */
    private const REFUS_DE_CONFIGURATION = RefusMailPulse::CONFIGURATION;

    private const REFUS_PASSAGERS = RefusMailPulse::PASSAGERS;

    public function __construct(
        private readonly CourrielConvocationRdv $courriel,
        private readonly WhatsAppConvocationRdv $whatsapp,
    ) {}

    /**
     * Pose la convocation en attente, sans rien envoyer. L'envoi n'a qu'une
     * porte, FileConvocationsRdv, qui tient le verrou.
     */
    public function planifier(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        $canal = $this->canalInitial($reservation);

        $reservation->forceFill([
            'convocation_statut' => $canal !== null
                ? StatutConvocationRdv::EnAttente
                : StatutConvocationRdv::SansEmail,
            'convocation_action' => $action,
            'convocation_tentatives' => 0,
            'convocation_envoyee_at' => null,
            'convocation_erreur' => null,
            'convocation_message_id' => null,
            'prevenue_par' => null,
        ] + $this->suiviDistantEfface() + $this->suiviCanal($reservation, $canal, false))->save();
    }

    /** @return array<string, null> */
    private function suiviDistantEfface(): array
    {
        if (! ColonnesDeployees::existe('esbtp_rdv_reservations', 'convocation_code_distant')) {
            return [];
        }

        return ['convocation_delivree_at' => null, 'convocation_synchro_at' => null, 'convocation_code_distant' => null];
    }

    /**
     * Tente l'envoi et consigne l'issue sur la reservation. L'e-mail reste le
     * premier choix. Si MailPulse refuse definitivement CE destinataire et que
     * le dossier du portail porte un numero valide, WhatsApp prend le relais.
     * Une panne globale MailPulse ne declenche pas un second appel inutile.
     *
     * @return string|null la raison qui bloquera aussi les envois suivants
     */
    public function envoyer(ESBTPRdvReservation $reservation): ?string
    {
        $reservation->loadMissing('creneau');
        $action = $reservation->convocation_action ?: 'confirme';
        $canal = $this->canalInitial($reservation);
        $fallback = false;

        if ($canal === null) {
            $this->consigner($reservation, StatutConvocationRdv::SansEmail, null, null, false);

            return null;
        }

        if ($action !== 'annule' && ! $reservation->statut?->occupeLeCreneau()) {
            $this->consigner($reservation, StatutConvocationRdv::SansObjet, 'La réservation ne tient plus de créneau.', $canal, false);

            return null;
        }

        if ($action !== 'annule' && $this->creneauPasse($reservation)) {
            $this->consigner($reservation, StatutConvocationRdv::SansObjet, 'Le créneau est passé avant l\'envoi.', $canal, false);

            return null;
        }

        try {
            $resultat = $this->expedierSur($reservation, $action, $canal);
        } catch (\Throwable $e) {
            Log::warning('Convocation rdv : erreur inattendue', [
                'reservation_id' => $reservation->id,
                'canal' => $canal->value,
                'erreur' => $e->getMessage(),
            ]);

            $this->echecPassager($reservation, $e->getMessage(), $canal, false);

            return null;
        }

        if (
            ! $resultat->ok
            && $canal === CanalConvocationRdv::Email
            && $this->peutBasculerVersWhatsapp($resultat)
            && $this->whatsappValide($reservation)
        ) {
            $fallback = true;
            $canal = CanalConvocationRdv::Whatsapp;

            try {
                $resultat = $this->expedierSur($reservation, $action, $canal);
            } catch (\Throwable $e) {
                Log::warning('Convocation rdv : fallback WhatsApp en erreur', [
                    'reservation_id' => $reservation->id,
                    'erreur' => $e->getMessage(),
                ]);

                $this->echecPassager($reservation, $e->getMessage(), $canal, true);

                return null;
            }
        }

        if ($resultat->ok) {
            $reservation->forceFill([
                'convocation_statut' => StatutConvocationRdv::Envoyee,
                'convocation_envoyee_at' => now(),
                'convocation_erreur' => null,
                'convocation_message_id' => $resultat->id ? mb_substr($resultat->id, 0, 100) : null,
                'convocation_tentatives' => $reservation->convocation_tentatives + 1,
            ] + $this->suiviCanal($reservation, $canal, $fallback))->save();
            $reservation->porteur()?->marquerInviteRdv();

            return null;
        }

        $motif = $this->motifLisible($resultat);
        Log::warning('Convocation rdv refusée par MailPulse', [
            'reservation_id' => $reservation->id,
            'canal' => $canal->value,
            'fallback' => $fallback,
            'statut' => $resultat->status,
            'message' => $resultat->message,
        ]);

        if (in_array($resultat->status, self::REFUS_DE_CONFIGURATION, true)) {
            $reservation->forceFill([
                'convocation_erreur' => mb_substr($motif, 0, 255),
            ] + $this->suiviCanal($reservation, $canal, $fallback))->save();

            return $motif;
        }

        if (in_array($resultat->status, self::REFUS_PASSAGERS, true)) {
            $this->echecPassager($reservation, $motif, $canal, $fallback);

            return $motif;
        }

        $this->consigner($reservation, StatutConvocationRdv::Echec, $motif, $canal, $fallback);

        return null;
    }

    private function expedierSur(ESBTPRdvReservation $reservation, string $action, CanalConvocationRdv $canal): MailPulseResult
    {
        return match ($canal) {
            CanalConvocationRdv::Email => $this->courriel->expedier($reservation, $action),
            CanalConvocationRdv::Whatsapp => $this->whatsapp->expedier($reservation, $action),
        };
    }

    private function peutBasculerVersWhatsapp(MailPulseResult $resultat): bool
    {
        if ($resultat->dispatchState !== 'failed') {
            return false;
        }

        $code = $resultat->errorCode ?: $resultat->status;

        return ! RefusMailPulse::bloquant($resultat->status)
            && ! RefusMailPulse::bloquant($code);
    }

    private function echecPassager(
        ESBTPRdvReservation $reservation,
        string $motif,
        CanalConvocationRdv $canal,
        bool $fallback,
    ): void {
        $tentatives = $reservation->convocation_tentatives + 1;
        $reservation->forceFill([
            'convocation_tentatives' => $tentatives,
            'convocation_statut' => $tentatives >= self::MAX_TENTATIVES
                ? StatutConvocationRdv::Echec
                : StatutConvocationRdv::EnAttente,
            'convocation_erreur' => mb_substr($motif, 0, 255),
        ] + $this->suiviCanal($reservation, $canal, $fallback))->save();
    }

    private function consigner(
        ESBTPRdvReservation $reservation,
        StatutConvocationRdv $statut,
        ?string $erreur,
        ?CanalConvocationRdv $canal,
        bool $fallback,
    ): void {
        $reservation->forceFill([
            'convocation_statut' => $statut,
            'convocation_erreur' => $erreur === null ? null : mb_substr($erreur, 0, 255),
        ] + $this->suiviCanal($reservation, $canal, $fallback))->save();
    }

    private function canalInitial(ESBTPRdvReservation $reservation): ?CanalConvocationRdv
    {
        if ($this->emailValide($reservation)) {
            return CanalConvocationRdv::Email;
        }

        return $this->whatsappValide($reservation) ? CanalConvocationRdv::Whatsapp : null;
    }

    private function contactBloque(ESBTPRdvReservation $reservation): bool
    {
        $porteur = $reservation->porteur();

        return $porteur !== null
            && method_exists($porteur, 'contactAConfirmer')
            && $porteur->contactAConfirmer();
    }

    private function emailValide(ESBTPRdvReservation $reservation): bool
    {
        if ($this->contactBloque($reservation)) {
            return false;
        }

        return app(\App\Services\Emails\AnalyseurEmail::class)->analyser($reservation->email)->joignable();
    }

    private function whatsappValide(ESBTPRdvReservation $reservation): bool
    {
        // Le canal WhatsApp automatise est reserve aux dossiers du portail :
        // leur numero vient du meme dossier que la verification de contact.
        if ($reservation->porteur() === null || $this->contactBloque($reservation)) {
            return false;
        }

        return PhoneNormalizer::toE164((string) $reservation->telephone) !== null;
    }

    /** @return array<string, mixed> */
    private function suiviCanal(ESBTPRdvReservation $reservation, ?CanalConvocationRdv $canal, bool $fallback): array
    {
        if (! ColonnesDeployees::existe('esbtp_rdv_reservations', 'convocation_canal')) {
            return [];
        }

        $destination = match ($canal) {
            CanalConvocationRdv::Email => $this->masquerEmail((string) $reservation->email),
            CanalConvocationRdv::Whatsapp => $this->masquerTelephone((string) $reservation->telephone),
            null => null,
        };

        return [
            'convocation_canal' => $canal?->value,
            'convocation_destination_masquee' => $destination,
            'convocation_fallback_utilise' => $fallback,
        ];
    }

    private function masquerEmail(string $email): ?string
    {
        $email = trim($email);
        if (! str_contains($email, '@')) {
            return null;
        }

        [$local, $domaine] = explode('@', $email, 2);

        return ($local !== '' ? mb_substr($local, 0, 1) : '*').'***@'.$domaine;
    }

    private function masquerTelephone(string $telephone): ?string
    {
        $e164 = PhoneNormalizer::toE164($telephone);
        if ($e164 === null) {
            return null;
        }

        $chiffres = ltrim($e164, '+');
        if (strlen($chiffres) <= 7) {
            return '+***'.substr($chiffres, -2);
        }

        return '+'.substr($chiffres, 0, 3)
            .str_repeat('*', max(2, strlen($chiffres) - 7))
            .substr($chiffres, -4);
    }

    private function creneauPasse(ESBTPRdvReservation $reservation): bool
    {
        $creneau = $reservation->creneau;
        if ($creneau === null || $creneau->date === null) {
            return false;
        }

        return $creneau->debut()->isPast();
    }

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
