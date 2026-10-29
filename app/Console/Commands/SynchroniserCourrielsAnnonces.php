<?php

namespace App\Console\Commands;

use App\Services\Annonces\SynchroniserCourrielsAnnonce;
use Illuminate\Console\Command;

final class SynchroniserCourrielsAnnonces extends Command
{
    protected $signature = 'annonces:synchroniser-courriels
        {--max=60 : Nombre de statuts MailPulse lus par passage}
        {--budget=45 : Budget reseau en secondes}';

    protected $description = 'Rapproche les remises réelles et retards des annonces MailPulse, sans réexpédition.';

    public function handle(SynchroniserCourrielsAnnonce $service): int
    {
        $bilan = $service->synchroniser(
            max(1, (int) $this->option('max')),
            max(1, (int) $this->option('budget'))
        );
        $this->line(sprintf(
            'Relues : %d ; délivrées : %d ; retardées : %d ; échecs : %d ; transit : %d ; erreurs : %d',
            $bilan['lues'], $bilan['delivrees'], $bilan['retardees'],
            $bilan['echecs'], $bilan['en_transit'], $bilan['erreurs']
        ));
        if ($bilan['bloque'] !== null && $bilan['bloque'] !== 'budget_epuise') {
            $this->warn('MailPulse momentanément indisponible : '.$bilan['bloque']);
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
