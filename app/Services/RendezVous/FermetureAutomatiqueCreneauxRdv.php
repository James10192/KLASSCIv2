<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvCreneau;
use Carbon\Carbon;

/**
 * Ferme les créneaux d'une journée sans toucher aux réservations existantes.
 *
 * La fermeture est un garde-fou de prise de rendez-vous, pas une annulation :
 * les familles déjà placées restent attendues. Le catalogue et ReservateurRdv
 * appliquent aussi la règle afin qu'un cron retardé ne rouvre pas une brèche.
 */
final class FermetureAutomatiqueCreneauxRdv
{
    public function __construct(private readonly RendezVousReglages $reglages) {}

    public function active(): bool
    {
        return $this->reglages->fermerJourAMinuit();
    }

    public function fermerAujourdHui(): int
    {
        return $this->fermer(Carbon::today());
    }

    public function fermer(Carbon $jour, bool $respecterReglage = true): int
    {
        if ($respecterReglage && ! $this->active()) {
            return 0;
        }

        return ESBTPRdvCreneau::query()
            ->whereDate('date', $jour->toDateString())
            ->where('ouvert', true)
            ->update(['ouvert' => false, 'updated_at' => now()]);
    }

    public function doitEtreFerme(ESBTPRdvCreneau $creneau): bool
    {
        return $this->active() && $creneau->date !== null && $creneau->date->lte(Carbon::today());
    }
}
