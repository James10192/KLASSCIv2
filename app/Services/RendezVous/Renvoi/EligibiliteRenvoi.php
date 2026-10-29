<?php

namespace App\Services\RendezVous\Renvoi;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\CanalConvocationDisponible;

/**
 * Une reservation precise peut-elle recevoir de nouveau sa convocation ?
 *
 * Oui seulement si : elle est confirmee (active), son dossier est encore
 * ouvert (un candidat deja inscrit garde sa reservation, il ne doit pas etre
 * reconvoque), son creneau existe et n'a pas commence, sa convocation n'est
 * pas un avis d'annulation, au moins un canal numerique est joignable, le
 * contact du dossier n'attend pas de confirmation, et sa convocation n'est pas
 * deja en file (en attente, donc aussi pendant qu'un lot l'envoie).
 */
class EligibiliteRenvoi
{
    public const INTROUVABLE = 'introuvable';

    public const NON_ACTIVE = 'reservation_non_active';

    public const DOSSIER_CLOS = 'dossier_clos';

    public const SANS_CRENEAU = 'sans_creneau';

    public const CRENEAU_PASSE = 'creneau_passe';

    public const ANNULATION = 'avis_d_annulation';

    /** Valeur historique gardee pour compatibilite CLI/API. */
    public const ADRESSE = 'adresse_non_joignable';

    public const CONTACT = 'contact_a_confirmer';

    public const DEJA_EN_FILE = 'deja_en_file';

    public function __construct(private readonly CanalConvocationDisponible $canaux) {}

    /** @return string|null la raison du refus, null si eligible */
    public function raison(?ESBTPRdvReservation $reservation): ?string
    {
        if ($reservation === null) {
            return self::INTROUVABLE;
        }
        $porteur = $reservation->porteur();

        return match (true) {
            $reservation->statut !== StatutReservationRdv::Confirmee => self::NON_ACTIVE,
            ! $reservation->dossierOuvert() => self::DOSSIER_CLOS,
            $reservation->creneau === null => self::SANS_CRENEAU,
            $reservation->creneau->aCommence() => self::CRENEAU_PASSE,
            $reservation->convocation_action === 'annule' => self::ANNULATION,
            $porteur !== null && method_exists($porteur, 'contactAConfirmer') && $porteur->contactAConfirmer() => self::CONTACT,
            $this->canaux->pour($reservation) === null => self::ADRESSE,
            $reservation->convocation_statut === StatutConvocationRdv::EnAttente => self::DEJA_EN_FILE,
            default => null,
        };
    }
}
