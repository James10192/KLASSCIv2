<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Throwable;

final class AcademicPilotageBackfillService
{
    public function __construct(
        private readonly GradeSheetProvisioningService $provisioning,
    ) {}

    public function run(array $options, User $actor): array
    {
        $limit = max(1, min((int) ($options['limit'] ?? 500), 5000));
        $query = $this->candidateQuery($options)->limit($limit);

        $summary = [
            'dry_run' => (bool) ($options['dry_run'] ?? false),
            'scanned' => 0,
            'would_create' => 0,
            'created' => 0,
            'entries_created' => 0,
            'entries_updated' => 0,
            'entries_unchanged' => 0,
            'entries_deactivated' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        $query->chunkById(100, function ($evaluations) use (&$summary, $actor): void {
            foreach ($evaluations as $evaluation) {
                $summary['scanned']++;

                if ($summary['dry_run']) {
                    $summary['would_create']++;

                    continue;
                }

                try {
                    $result = $this->provisioning->provision(
                        $evaluation,
                        GradeSheetEntryMode::DIRECT,
                        $actor,
                    );

                    if ($result->created) {
                        $summary['created']++;
                    }

                    if ($result->entrySync) {
                        $summary['entries_created'] += $result->entrySync->created;
                        $summary['entries_updated'] += $result->entrySync->updated;
                        $summary['entries_unchanged'] += $result->entrySync->unchanged;
                        $summary['entries_deactivated'] += $result->entrySync->deactivated;
                    }
                } catch (Throwable $exception) {
                    $summary['failed']++;
                    $summary['failures'][] = [
                        'evaluation_id' => (int) $evaluation->id,
                        'message' => $exception->getMessage(),
                    ];
                }
            }
        });

        return $summary;
    }

    public function diagnose(array $filters = []): array
    {
        return [
            'eligible_evaluations' => (clone $this->candidateQuery($filters))->count(),
            'existing_grade_sheets' => $this->gradeSheetQuery($filters)->count(),
            'missing_required_scope' => $this->baseEvaluationQuery($filters)
                ->where(function (Builder $query): void {
                    $query->whereNull('classe_id')
                        ->orWhereNull('matiere_id')
                        ->orWhereNull('annee_universitaire_id')
                        ->orWhereNull('periode');
                })
                ->count(),
        ];
    }

    private function candidateQuery(array $filters): Builder
    {
        return $this->baseEvaluationQuery($filters)
            ->whereNotNull('classe_id')
            ->whereNotNull('matiere_id')
            ->whereNotNull('annee_universitaire_id')
            ->whereNotNull('periode')
            ->whereNotExists(function (QueryBuilder $query): void {
                $query->selectRaw('1')
                    ->from('esbtp_grade_sheets')
                    ->whereColumn('esbtp_grade_sheets.evaluation_id', 'esbtp_evaluations.id')
                    ->whereNull('esbtp_grade_sheets.deleted_at');
            })
            ->with(['classe' => fn ($query) => $query
                ->without(['filiere', 'niveau', 'annee'])
                ->select('id', 'systeme_academique')])
            ->orderBy('id');
    }

    private function gradeSheetQuery(array $filters): Builder
    {
        return GradeSheet::query()
            ->whereNotNull('evaluation_id')
            ->when($filters['year_id'] ?? null, fn (Builder $query, $yearId) => $query->where(
                'annee_universitaire_id',
                (int) $yearId,
            ))
            ->when($filters['class_id'] ?? null, fn (Builder $query, $classId) => $query->where(
                'classe_id',
                (int) $classId,
            ))
            ->when($filters['period'] ?? null, fn (Builder $query, $period) => $query->where(
                'semester',
                (string) $period,
            ));
    }

    private function baseEvaluationQuery(array $filters): Builder
    {
        return ESBTPEvaluation::query()
            ->where(function (Builder $query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);
            })
            ->when($filters['year_id'] ?? null, fn (Builder $query, $yearId) => $query->where(
                'annee_universitaire_id',
                (int) $yearId,
            ))
            ->when($filters['class_id'] ?? null, fn (Builder $query, $classId) => $query->where(
                'classe_id',
                (int) $classId,
            ))
            ->when($filters['period'] ?? null, fn (Builder $query, $period) => $query->where(
                'periode',
                (string) $period,
            ));
    }
}
