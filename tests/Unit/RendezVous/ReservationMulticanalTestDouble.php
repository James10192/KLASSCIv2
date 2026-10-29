<?php

namespace Tests\Unit\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Models\ESBTPRdvReservation;

class ReservationMulticanalTestDouble extends ESBTPRdvReservation
{
    public ?PorteurDeRendezVous $fakePorteur = null;

    public function porteur(): ?PorteurDeRendezVous
    {
        return $this->fakePorteur;
    }
}
