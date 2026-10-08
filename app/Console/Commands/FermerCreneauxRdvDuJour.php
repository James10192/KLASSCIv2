<?php

namespace App\Console\Commands;

use App\Services\RendezVous\FermetureAutomatiqueCreneaux;
use Illuminate\Console\Command;

class FermerCreneauxRdvDuJour extends Command
{
    protected $signature = 'inscriptions:fermer-creneaux-rdv-du-jour';

    protected $description = 'Ferme les créneaux du jour et passés quand le réglage de fermeture à minuit est actif';

    public function handle(FermetureAutomatiqueCreneaux $fermeture): int
    {
        $nombre = $fermeture->fermer();
        $this->info($nombre.' créneau(x) fermé(s).');

        return self::SUCCESS;
    }
}
