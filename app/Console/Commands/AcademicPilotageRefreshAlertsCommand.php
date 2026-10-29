<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\AcademicPilotage\Services\AcademicAlertDetectionService;
use App\Domain\AcademicPilotage\Services\AcademicSystemNormalizer;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AcademicPilotageRefreshAlertsCommand extends Command
{
    protected $signature = 'academic-pilotage:refresh-alerts
        {--class-id= : Limit refresh to one class}
        {--year-id= : Academic year id}
        {--period=semestre1 : Period to refresh}
        {--system= : BTS or LMD}
        {--chunk-size=100 : Number of classes to process per database chunk}
        {--limit= : Deprecated alias for --chunk-size}';

    protected $description = 'Refresh idempotent academic pilotage alerts.';

    public function handle(AcademicAlertDetectionService $alerts, AcademicSystemNormalizer $systems): int
    {
        $yearId = (int) ($this->option('year-id') ?: $this->currentYearId());
        if ($yearId <= 0) {
            $this->components->warn('Academic alert refresh skipped: no active academic year found.');

            return self::SUCCESS;
        }

        $period = (string) $this->option('period');
        $chunkSize = max(1, min((int) ($this->option('chunk-size') ?: $this->option('limit') ?: 100), 500));
        $systemFilter = $this->option('system')
            ? $systems->normalize((string) $this->option('system'))
            : null;
        $query = ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->where('annee_universitaire_id', $yearId)
            ->when($this->option('class-id'), fn ($scope) => $scope->whereKey((int) $this->option('class-id')))
            ->when($systemFilter === AcademicSystemNormalizer::BTS, fn ($scope) => $scope->where(
                fn ($legacyBts) => $legacyBts
                    ->where('systeme_academique', AcademicSystemNormalizer::BTS)
                    ->orWhereNull('systeme_academique')
                    ->orWhere('systeme_academique', ''),
            ))
            ->when($systemFilter === AcademicSystemNormalizer::LMD, fn ($scope) => $scope->where(
                'systeme_academique',
                AcademicSystemNormalizer::LMD,
            ))
            ->select(['id', 'systeme_academique']);

        $createdOrSeen = 0;
        $failed = 0;
        $processed = 0;
        $query->chunkById($chunkSize, function ($classes) use (
            $alerts,
            $systems,
            $yearId,
            $period,
            &$createdOrSeen,
            &$failed,
            &$processed,
        ): void {
            foreach ($classes as $class) {
                $processed++;
                try {
                    $academicSystem = $systems->normalize($class->systeme_academique);
                    $createdOrSeen += count($alerts->refreshClass(
                        (int) $class->id,
                        $yearId,
                        $academicSystem,
                        $period,
                    ));
                } catch (Throwable $exception) {
                    $failed++;
                    Log::error('Academic alert refresh failed for one class.', [
                        'class_id' => (int) $class->id,
                        'academic_year_id' => $yearId,
                        'academic_system' => (string) $class->systeme_academique,
                        'period' => $period,
                        'exception' => $exception,
                    ]);
                }
            }
        });

        $this->components->info(sprintf(
            'Academic alerts refreshed: classes=%d alerts=%d failed=%d',
            $processed,
            $createdOrSeen,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function currentYearId(): ?int
    {
        return ESBTPAnneeUniversitaire::query()
            ->where('is_active', true)
            ->latest('annee_debut')
            ->value('id');
    }
}
