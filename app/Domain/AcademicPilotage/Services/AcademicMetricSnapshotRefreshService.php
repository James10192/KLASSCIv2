<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class AcademicMetricSnapshotRefreshService
{
    public function __construct(
        private readonly StudentAcademicHealthService $students,
        private readonly ClassAcademicHealthService $classes,
        private readonly AcademicMetricSnapshotService $snapshots,
    ) {}

    public function refreshDirty(int $limit = 100): array
    {
        if (! Schema::hasTable('esbtp_academic_metric_snapshots')) {
            return ['scanned' => 0, 'refreshed' => 0, 'stale_retries' => 0, 'failed' => 0];
        }

        $limit = max(1, min($limit, (int) config('academic_pilotage.refresh.max_dirty_batch', 250)));
        $token = Str::random(40);
        $rows = $this->claimDirtySnapshots($limit, $token);
        $refreshed = 0;
        $staleRetries = 0;
        $failed = 0;

        foreach ($rows as $snapshot) {
            try {
                if ($this->refresh($snapshot)) {
                    $refreshed++;
                } else {
                    $staleRetries++;
                    $this->markStaleRetry($snapshot, $token);
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->markFailed($snapshot, $token, $exception);
                Log::error('Academic metric snapshot refresh failed.', [
                    'snapshot_id' => $snapshot->id,
                    'scope_type' => $snapshot->scope_type,
                    'scope_id' => $snapshot->scope_id,
                    'exception' => $exception,
                ]);
            } finally {
                $this->releaseClaim($snapshot, $token);
            }
        }

        return [
            'scanned' => $rows->count(),
            'refreshed' => $refreshed,
            'stale_retries' => $staleRetries,
            'failed' => $failed,
        ];
    }

    private function claimDirtySnapshots(int $limit, string $token)
    {
        if (! $this->claimColumnsAvailable()) {
            return AcademicMetricSnapshot::query()
                ->where('is_dirty', true)
                ->orderByRaw('stale_at is null')
                ->orderBy('stale_at')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        $ttl = now()->subMinutes((int) config('academic_pilotage.refresh.claim_ttl_minutes', 10));
        $candidates = AcademicMetricSnapshot::query()
            ->where('is_dirty', true)
            ->where(function ($query) use ($ttl): void {
                $query->whereNull('refresh_token')
                    ->orWhere('refresh_started_at', '<', $ttl);
            })
            ->orderByRaw('stale_at is null')
            ->orderBy('stale_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($candidates as $snapshot) {
            AcademicMetricSnapshot::query()
                ->whereKey($snapshot->id)
                ->where('is_dirty', true)
                ->whereRaw('COALESCE(source_revision, 0) = ?', [(int) $snapshot->source_revision])
                ->where(function ($query) use ($ttl): void {
                    $query->whereNull('refresh_token')
                        ->orWhere('refresh_started_at', '<', $ttl);
                })
                ->update([
                    'refresh_token' => $token,
                    'refresh_started_at' => now(),
                    ...$this->attemptPayload(),
                    'updated_at' => now(),
                ]);
        }

        return AcademicMetricSnapshot::query()
            ->where('refresh_token', $token)
            ->orderBy('id')
            ->get();
    }

    private function refresh(AcademicMetricSnapshot $snapshot): bool
    {
        $revision = (int) ($snapshot->source_revision ?? 0);

        if ($snapshot->scope_type === 'student' && $snapshot->etudiant_id !== null) {
            $context = new StudentMetricContext(
                (int) $snapshot->etudiant_id,
                (int) $snapshot->classe_id,
                (int) $snapshot->annee_universitaire_id,
                strtoupper((string) $snapshot->academic_system),
                (string) $snapshot->semester,
            );

            return $this->snapshots->storeStudentWhenRevisionCurrent(
                $context,
                $this->students->evaluate($context),
                $revision,
            );
        }

        if ($snapshot->scope_type === 'class' && $snapshot->classe_id !== null) {
            return $this->snapshots->storeClassWhenRevisionCurrent(
                (int) $snapshot->annee_universitaire_id,
                strtoupper((string) $snapshot->academic_system),
                (string) $snapshot->semester,
                $this->classes->evaluate(
                    (int) $snapshot->classe_id,
                    (int) $snapshot->annee_universitaire_id,
                    strtoupper((string) $snapshot->academic_system),
                    (string) $snapshot->semester,
                ),
                $revision,
            );
        }

        return false;
    }

    private function attemptPayload(): array
    {
        $payload = [];
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_attempts')) {
            $payload['refresh_attempts'] = DB::raw('COALESCE(refresh_attempts, 0) + 1');
        }
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'last_refresh_error')) {
            $payload['last_refresh_error'] = null;
        }

        return $payload;
    }

    private function markStaleRetry(AcademicMetricSnapshot $snapshot, string $token): void
    {
        if (! Schema::hasColumn('esbtp_academic_metric_snapshots', 'last_refresh_error')) {
            return;
        }

        AcademicMetricSnapshot::query()
            ->whereKey($snapshot->id)
            ->where(fn ($query) => $query->where('refresh_token', $token)->orWhereNull('refresh_token'))
            ->update([
                'last_refresh_error' => 'source_revision_changed',
                'updated_at' => now(),
            ]);
    }

    private function markFailed(AcademicMetricSnapshot $snapshot, string $token, Throwable $exception): void
    {
        if (! Schema::hasColumn('esbtp_academic_metric_snapshots', 'last_refresh_error')) {
            return;
        }

        AcademicMetricSnapshot::query()
            ->whereKey($snapshot->id)
            ->where(fn ($query) => $query->where('refresh_token', $token)->orWhereNull('refresh_token'))
            ->update([
                'last_refresh_error' => mb_substr($exception->getMessage(), 0, 255),
                'updated_at' => now(),
            ]);
    }

    private function releaseClaim(AcademicMetricSnapshot $snapshot, string $token): void
    {
        if (! $this->claimColumnsAvailable()) {
            return;
        }

        AcademicMetricSnapshot::query()
            ->whereKey($snapshot->id)
            ->where('refresh_token', $token)
            ->update([
                'refresh_token' => null,
                'refresh_started_at' => null,
                'updated_at' => now(),
            ]);
    }

    private function claimColumnsAvailable(): bool
    {
        return Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_token')
            && Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_started_at')
            && Schema::hasColumn('esbtp_academic_metric_snapshots', 'source_revision');
    }
}
