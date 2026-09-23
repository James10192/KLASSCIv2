<?php

namespace App\Console\Commands;

use App\Services\RendezVous\SynchroStatutsConvocations;
use Illuminate\Console\Command;

class SynchroniserConvocationsRdv extends Command
{
    public const NOM = 'inscriptions:synchroniser-convocations-rdv';

    protected $signature = self::NOM.' {--max=100 : convocations relues au plus par passage}';

    protected $description = 'Relit chez MailPulse l\'etat reel des convocations envoyees (remise, rebond, suppression) et met en echec celles qui ne sont pas arrivees.';

    public function handle(SynchroStatutsConvocations $synchro): int
    {
        $rapport = $synchro->synchroniser(max(1, (int) $this->option('max')));

        $this->info(sprintf(
            'Relues : %d · délivrées : %d · non remises : %d · en transit : %d · erreurs de lecture : %d',
            $rapport['lues'],
            $rapport['delivrees'],
            $rapport['echecs'],
            $rapport['en_transit'],
            $rapport['erreurs']
        ));

        if ($rapport['bloque'] !== null) {
            $this->error('Arrêt : '.$rapport['bloque']);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
