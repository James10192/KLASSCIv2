<?php

namespace App\Console\Commands;

use App\Services\RendezVous\FileConvocationsRdv;
use Illuminate\Console\Command;

class EnvoyerConvocationsRdv extends Command
{
    public const NOM = 'inscriptions:envoyer-convocations-rdv';

    protected $signature = self::NOM.' {--max=50 : convocations au plus par passage} {--budget=45 : secondes au plus}';

    protected $description = 'Envoie les convocations de rendez-vous en attente, par paquets, et s\'arrete sur un refus de configuration.';

    public function handle(FileConvocationsRdv $file): int
    {
        $rapport = $file->envoyerUnPaquet(max(1, (int) $this->option('max')), max(1.0, (float) $this->option('budget')));
        if ($rapport['en_cours']) {
            $this->info('Un autre envoi est en cours : rien tenté. '.$rapport['restantes'].' en attente.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Envoyées : %d · échecs : %d · encore en attente : %d',
            $rapport['envoyees'],
            $rapport['echecs'],
            $rapport['restantes']
        ));

        if ($rapport['bloque'] !== null) {
            $this->error('Arrêt : '.$rapport['bloque']);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
