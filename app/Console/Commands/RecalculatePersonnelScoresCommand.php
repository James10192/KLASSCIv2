<?php

namespace App\Console\Commands;

use App\Services\Scoring\PersonnelScoringService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecalculatePersonnelScoresCommand extends Command
{
    protected $signature = 'personnel-scores:recalculate
        {--period=month : month, quarter ou year}
        {--date= : Date de reference YYYY-MM-DD}
        {--role= : Recalculer uniquement un role}
        {--user= : Recalculer uniquement un utilisateur}
        {--include-inactive : Inclure les utilisateurs inactifs}';

    protected $description = 'Recalcule les snapshots de scoring du personnel selon les permissions effectives.';

    public function handle(PersonnelScoringService $scoring): int
    {
        $period = (string) $this->option('period');
        if (! in_array($period, array_keys(config('personnel_scoring.periods', [])), true)) {
            $this->error("Periode invalide: {$period}");
            return self::FAILURE;
        }

        $referenceDate = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $query = $scoring->staffUsersQuery(
            includeInactive: (bool) $this->option('include-inactive'),
            role: $this->option('role') ? (string) $this->option('role') : null,
            userId: $this->option('user') ? (int) $this->option('user') : null
        );

        $count = 0;
        $bar = $this->output->createProgressBar((clone $query)->count());
        $bar->start();

        $query->chunkById(100, function ($users) use ($scoring, $period, $referenceDate, &$count, $bar) {
            foreach ($users as $user) {
                $scoring->calculateAndStore($user, $period, $referenceDate);
                $count++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("{$count} score(s) recalcules pour la periode {$period}.");

        return self::SUCCESS;
    }
}
