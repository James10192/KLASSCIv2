<?php

namespace App\Services\RendezVous;

use App\Domain\Notifications\PhoneNormalizer;
use App\Enums\CanalConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Emails\AnalyseurEmail;

/**
 * Choisit le meilleur canal numerique encore utilisable pour une convocation.
 *
 * L'e-mail reste prioritaire. WhatsApp n'est automatise que pour une
 * reservation rattachee a un dossier du portail, afin de ne pas envoyer vers
 * un numero administratif saisi hors du parcours public.
 */
class CanalConvocationDisponible
{
    public function __construct(private readonly AnalyseurEmail $emails) {}

    public function pour(ESBTPRdvReservation $reservation): ?CanalConvocationRdv
    {
        if ($this->contactBloque($reservation)) {
            return null;
        }

        if ($this->emails->analyser($reservation->email)->joignable()) {
            return CanalConvocationRdv::Email;
        }

        return $this->whatsappValide($reservation)
            ? CanalConvocationRdv::Whatsapp
            : null;
    }

    public function whatsappValide(ESBTPRdvReservation $reservation): bool
    {
        return $reservation->porteur() !== null
            && ! $this->contactBloque($reservation)
            && PhoneNormalizer::toE164((string) $reservation->telephone) !== null;
    }

    public function contactBloque(ESBTPRdvReservation $reservation): bool
    {
        $porteur = $reservation->porteur();

        return $porteur !== null
            && method_exists($porteur, 'contactAConfirmer')
            && $porteur->contactAConfirmer();
    }
}
