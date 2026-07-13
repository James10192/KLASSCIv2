<?php

namespace App\Console\Commands;

use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Models\User;
use Illuminate\Console\Command;

class AcademicPilotageBackfillCommand extends Command
{
    protected $signature = 'academic-pilotage:backfill
        {--dry-run : Count eligible legacy evaluations without writing}
        {--year-id= : Limit to one academic year}
        {--class-id= : Limit to one class}
        {--period= : Limit to one period}
        {--limit=500 : Maximum evaluations to process}
        {--actor-id= : User id recorded as backfill actor}
        {--json : Output machine-readable JSON}';

    protected $description = 'Backfill academic pilotage grade sheets from existing evaluations.';

    public function handle(AcademicPilotageBackfillService $backfill): int
    {
        $actor = $this->actor();
        if (! $actor && ! $this->option('dry-run')) {
            $this->components->error('Academic pilotage backfill requires a valid --actor-id for real execution.');

            return self::FAILURE;
        }

        $result = $backfill->run([
            'dry_run' => (bool) $this->option('dry-run'),
            'year_id' => $this->option('year-id'),
            'class_id' => $this->option('class-id'),
            'period' => $this->option('period'),
            'limit' => $this->option('limit'),
        ], $actor ?? new User);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->components->info(sprintf(
                'Academic pilotage backfill: scanned=%d would_create=%d created=%d failed=%d',
                $result['scanned'],
                $result['would_create'],
                $result['created'],
                $result['failed'],
            ));
        }

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function actor(): ?User
    {
        if (! $this->option('actor-id')) {
            return null;
        }

        return User::query()->find((int) $this->option('actor-id'));
    }
}
