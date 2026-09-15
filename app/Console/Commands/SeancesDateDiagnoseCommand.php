<?php

namespace App\Console\Commands;

use App\Domain\EmploiTemps\DiagnosticDesDatesDeSeance;
use Illuminate\Console\Command;

/**
 * Recense les séances sans date, et ce qu'elles coûtent en heures.
 *
 * Lecture seule : rien n'est modifié. Le rattrapage est une autre commande,
 * `seances:date-backfill`, qui simule par défaut.
 *
 * Usage : php artisan seances:date-diagnose [--limite=200] [--json]
 */
class SeancesDateDiagnoseCommand extends Command
{
    protected $signature = 'seances:date-diagnose
                            {--limite=200 : Nombre de séances détaillées listées (les totaux portent sur tout)}
                            {--json : Sortie JSON brute}';

    protected $description = 'Recense les séances dont la date est vide, et les heures enseignant qu\'elles font disparaître';

    public function handle(DiagnosticDesDatesDeSeance $diagnostic): int
    {
        $rapport = $diagnostic->rapport((int) $this->option('limite'));

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->afficher($rapport);

        return self::SUCCESS;
    }

    private function afficher(array $rapport): void
    {
        $this->info('Séances sans date de séance');
        $this->newLine();

        if ($rapport['total_sans_date'] === 0) {
            $this->line('Aucune séance sans date : rien à rattraper.');

            return;
        }

        $this->line(sprintf('  Séances concernées      : %d', $rapport['total_sans_date']));
        $this->line(sprintf('  Heures hors de la paie  : %sh', $rapport['heures_perdues']));
        $this->line(sprintf('  Date recalculable       : %d', $rapport['rattrapables']));

        if ($rapport['irrattrapables'] !== []) {
            $this->newLine();
            $this->warn('  Séances dont la date ne peut PAS être recalculée :');
            foreach ($rapport['irrattrapables'] as $raison => $nombre) {
                $this->line(sprintf('    %-36s %d', $raison, $nombre));
            }
            $this->line('    Ces séances demandent une correction à la main, depuis leur emploi du temps.');
        }

        if ($rapport['par_enseignant'] !== []) {
            $this->newLine();
            $this->line('Par enseignant :');
            $this->table(
                ['Enseignant', 'Séances', 'Heures'],
                array_map(
                    fn (array $l) => [$l['enseignant'], $l['seances'], $l['heures'].'h'],
                    array_slice($rapport['par_enseignant'], 0, 30),
                ),
            );
        }

        $this->newLine();
        $this->line('Rattrapage : php artisan seances:date-backfill   (simule ; ajouter --appliquer pour écrire)');
    }
}
