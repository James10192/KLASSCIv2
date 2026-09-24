<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvReservation;
use App\Services\Reinscription\PortailReinscriptionService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Les rendez-vous « de la saison » : ceux dont le creneau appartient a l'annee
 * CIBLE des inscriptions, celle sur laquelle GenerateurCreneaux et
 * CatalogueCreneaux posent et offrent les creneaux
 * (PortailReinscriptionService::anneeCible() : le reglage, a defaut l'annee
 * courante). Pas l'annee courante : en septembre on inscrit pour l'annee
 * suivante, et filtrer sur `is_current` ne trouvait aucune famille.
 *
 * Seule source du perimetre pour le diagnostic des convocations, le
 * diagnostic e-mails et les familles a recontacter : leurs chiffres ne
 * peuvent pas diverger.
 *
 * Sans annee resoluble (ni reglage ni annee courante), les creneaux des douze
 * derniers mois.
 */
class PerimetreRdv
{
    public const MOIS_SANS_ANNEE = 12;

    public function __construct(private readonly PortailReinscriptionService $saison) {}

    /**
     * @param  Builder<ESBTPRdvReservation>  $reservations
     * @return Builder<ESBTPRdvReservation>
     */
    public function appliquer(Builder $reservations): Builder
    {
        $annee = $this->saison->anneeCible()?->id;

        return $reservations->whereHas('creneau', fn (Builder $q) => $annee !== null
            ? $q->where('annee_universitaire_id', $annee)
            : $q->whereDate('date', '>=', now()->subMonths(self::MOIS_SANS_ANNEE)->toDateString()));
    }

    /** @return Builder<ESBTPRdvReservation> */
    public function reservations(): Builder
    {
        return $this->appliquer(ESBTPRdvReservation::query());
    }
}
