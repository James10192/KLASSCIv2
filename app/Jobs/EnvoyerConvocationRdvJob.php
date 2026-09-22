<?php

namespace App\Jobs;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\MessagerieRdv;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Une convocation, une tentative. L'issue est consignee sur la reservation par
 * MessagerieRdv, qui ne leve jamais : pas de `$tries` ici, les relances passent
 * par FileConvocationsRdv (tache planifiee, bouton de l'ecran), qui voient l'etat.
 *
 * Il n'y a plus d'action dans le constructeur : elle vit sur la reservation
 * (`convocation_action`), sinon une relance ne saurait pas quoi renvoyer.
 */
class EnvoyerConvocationRdvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $reservationId)
    {
    }

    public function handle(MessagerieRdv $mails): void
    {
        $reservation = ESBTPRdvReservation::query()->with('creneau')->find($this->reservationId);
        if ($reservation === null || $reservation->convocation_statut !== StatutConvocationRdv::EnAttente) {
            return;
        }

        $mails->envoyer($reservation);
    }
}
