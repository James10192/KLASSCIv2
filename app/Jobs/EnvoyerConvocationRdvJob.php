<?php

namespace App\Jobs;

use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\MessagerieRdv;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnvoyerConvocationRdvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $reservationId,
        public readonly string $action = 'confirme',
    ) {
    }

    public function handle(MessagerieRdv $mails): void
    {
        $reservation = ESBTPRdvReservation::query()->with('creneau')->find($this->reservationId);
        if ($reservation === null) {
            return;
        }

        if ($mails->expedierConvocation($reservation, $this->action)) {
            $reservation->porteur()?->marquerInviteRdv();
        }
    }
}
