<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AcademicPilotageRefreshCommand extends Command
{
    protected $signature = 'academic-pilotage:refresh
        {--limit=100 : Maximum dirty snapshots and alert chunk size}';

    protected $description = 'Refresh academic pilotage dirty snapshots and global alerts.';

    public function handle(): int
    {
        $snapshotExit = $this->call('academic-pilotage:refresh-snapshots', [
            '--limit' => (int) $this->option('limit'),
        ]);
        $alertExit = $this->call('academic-pilotage:refresh-alerts', [
            '--chunk-size' => (int) $this->option('limit'),
        ]);

        return $snapshotExit === self::SUCCESS && $alertExit === self::SUCCESS
            ? self::SUCCESS
            : self::FAILURE;
    }
}
