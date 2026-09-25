<?php

namespace App\Services\RendezVous\Renvoi;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Emails\AnalyseurEmail;

/**
 * Une reservation precise peut-elle recevoir de nouveau sa convocation ?
 *
 * Oui seulement si : elle est confirmee (active), son creneau n'a pas
 * commence, son adresse est joignable, le contact du dossier n'attend pas de
 * confirmation, et sa convocation n'est pas deja en file (en attente, donc
 * aussi pendant qu'un lot l'envoie).
 */
class EligibiliteRenvoi
{
    public const INTROUVABLE = 'introuvable';

    public const NON_ACTIVE = 'reservation_non_active';

    public const CRENEAU_PASSE = 'creneau_passe';

    public const ADRESSE = 'adresse_non_joignable';

    public const CONTACT = 'contact_a_confirmer';

    public const DEJA_EN_FILE = 'deja_en_file';

    public function __construct(private readonly AnalyseurEmail $emails) {}

    /** @return string|null la raison du refus, null si eligible */
    public function raison(?ESBTPRdvReservation $reservation): ?string
    {
        if ($reservation === null) {
            return self::INTROUVABLE;
        }
        $porteur = $reservation->porteur();

        return match (true) {
            $reservation->statut !== StatutReservationRdv::Confirmee => self::NON_ACTIVE,
            $reservation->creneau === null || $reservation->creneau->aCommence() => self::CRENEAU_PASSE,
            ! $this->emails->analyser($reservation->email)->joignable() => self::ADRESSE,
            $porteur !== null && method_exists($porteur, 'contactAConfirmer') && $porteur->contactAConfirmer() => self::CONTACT,
            $reservation->convocation_statut === StatutConvocationRdv::EnAttente => self::DEJA_EN_FILE,
            default => null,
        };
    }
}
