<?php

namespace App\Console\Commands;

use App\Services\RendezVous\SynchroStatutsConvocations;
use Illuminate\Console\Command;

class SynchroniserConvocationsRdv extends Command
{
    public const NOM = 'inscriptions:synchroniser-convocations-rdv';

    protected $signature = self::NOM.' {--max=100 : nombre maximal de convocations relues par passage}';

    protected $description = 'Relit chez MailPulse l\'état réel des convocations envoyées (remise, rebond, suppression) et met en échec celles qui ne sont pas arrivées.';

    /** Tache planifiee toutes les 15 min : pas de max_execution_time, mais un passage ne doit pas chevaucher le suivant. */
    private const BUDGET_SECONDES = 300.0;

    public function handle(SynchroStatutsConvocations $synchro): int
    {
        $rapport = $synchro->synchroniser(max(1, (int) $this->option('max')), self::BUDGET_SECONDES);

        $this->info(sprintf(
            'Relues : %d · délivrées : %d · non remises : %d · en transit : %d · erreurs de lecture : %d',
            $rapport['lues'],
            $rapport['delivrees'],
            $rapport['echecs'],
            $rapport['en_transit'],
            $rapport['erreurs']
        ));

        if ($rapport['bloque'] === SynchroStatutsConvocations::BUDGET_EPUISE) {
            $this->warn('Budget de temps épuisé : la suite au prochain passage.');

            return self::SUCCESS;
        }
        if ($rapport['bloque'] !== null) {
            $this->error('Arrêt : '.$rapport['bloque']);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
