<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\StatutReservationRdv;
use Illuminate\Support\Facades\Log;

/**
 * Ce que devient le rendez-vous d'une famille une fois son dossier inscrit.
 *
 * Rien ne le touchait : une famille inscrite au guichet sans avoir ete cochee
 * restait « confirmee », son rendez-vous du jour n'apparaissait jamais comme
 * honore, et un rendez-vous pris pour la semaine suivante gardait sa place
 * alors que l'inscription etait deja faite.
 *
 * Deux cas, et seulement deux :
 * - le creneau est aujourd'hui ou passe : la famille est venue, puisqu'on
 *   l'inscrit — le rendez-vous est marque honore, au nom de l'agent ;
 * - le creneau est a venir : la place est rendue, et une convocation encore
 *   en file ne part plus.
 *
 * Jamais bloquant : l'inscription est deja enregistree quand on arrive ici.
 * Un echec se journalise, il ne remonte pas a l'ecran.
 */
class RendezVousApresInscription
{
    public function __construct(
        private readonly ReservateurRdv $reservateur,
        private readonly AccueilRdv $accueil,
    ) {
    }

    /** @return string|null ce qui a ete fait : `honore`, `libere`, ou null */
    public function clore(PorteurDeRendezVous $porteur, ?int $agentId): ?string
    {
        try {
            $reservation = $this->reservateur->reservationActive($porteur)?->load('creneau');
            if ($reservation === null || $reservation->creneau === null || $reservation->statut !== StatutReservationRdv::Confirmee) {
                return null;
            }

            if ($reservation->creneau->date->isFuture() && ! $reservation->creneau->date->isToday()) {
                return $this->reservateur->liberer($porteur, 'Dossier inscrit') !== null ? 'libere' : null;
            }

            return $this->accueil->marquerRecu($reservation, (int) $agentId) === null ? 'honore' : null;
        } catch (\Throwable $e) {
            Log::warning('Rendez-vous non clos apres inscription', [
                'porteur' => $porteur::class,
                'porteur_id' => $porteur->getKey(),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
