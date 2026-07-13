<?php

namespace App\Console\Commands;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotRefreshService;
use Illuminate\Console\Command;

class AcademicPilotageRefreshSnapshotsCommand extends Command
{
    protected $signature = 'academic-pilotage:refresh-snapshots {--limit=100 : Maximum dirty snapshots to refresh}';

    protected $description = 'Refresh dirty academic pilotage metric snapshots.';

    public function handle(AcademicMetricSnapshotRefreshService $refresh): int
    {
        $result = $refresh->refreshDirty((int) $this->option('limit'));

        $this->components->info(sprintf(
            'Academic snapshots refreshed: scanned=%d refreshed=%d stale_retries=%d failed=%d',
            $result['scanned'],
            $result['refreshed'],
            $result['stale_retries'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
