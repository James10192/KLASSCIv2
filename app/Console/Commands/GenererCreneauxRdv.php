<?php

namespace App\Console\Commands;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Services\RendezVous\GenerateurCreneaux;
use Illuminate\Console\Command;

class GenererCreneauxRdv extends Command
{
    public const NOM = 'inscriptions:generer-creneaux-rdv';

    protected $signature = self::NOM;

    protected $description = 'Genere les creneaux de rendez-vous d\'inscription a partir des reglages.';

    public function handle(GenerateurCreneaux $generateur): int
    {
        try {
            $rapport = $generateur->generer();
        } catch (ReglagesRdvIncomplets $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Créés : %d · mis à jour : %d · fermés : %d · conservés occupés : %d',
            $rapport->crees,
            $rapport->misAJour,
            $rapport->fermes,
            $rapport->conservesOccupes
        ));

        return self::SUCCESS;
    }
}
