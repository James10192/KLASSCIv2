<?php

namespace App\Services\Scoring;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PersonnelAcademicObligationMetricsService
{
    private const TABLE = 'esbtp_grade_sheets';

    private const REQUIRED_COLUMNS = [
        'id',
        'teacher_id',
        'assigned_processor_id',
        'entry_mode',
        'status',
        'expected_at',
        'submitted_at',
        'received_at',
        'entered_at',
    ];

    private ?array $sourceCapabilities = null;

    public function teacherGradeObligation(
        ?int $teacherId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        if (! $teacherId) {
            return $this->insufficientData('Profil enseignant introuvable.');
        }

        if (! $this->sourceIsAvailable()) {
            return $this->insufficientData('Registre des obligations académiques indisponible.');
        }

        $periodEnd = $end->copy()->endOfDay();
        $sheets = $this->baseQuery()
            ->where('teacher_id', $teacherId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('expected_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('id')
            ->get($this->evidenceColumns());

        return $this->metric($sheets, fn ($sheet) => match ($sheet->entry_mode) {
            'paper' => $this->occurredBy($sheet->submitted_at, $periodEnd),
            'direct' => $this->occurredBy($sheet->entered_at, $periodEnd),
            default => false,
        });
    }

    public function delegatedEntryObligation(
        int $userId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        if (! $this->sourceIsAvailable()) {
            return $this->insufficientData('Registre des obligations académiques indisponible.');
        }

        $periodEnd = $end->copy()->endOfDay();
        $sheets = $this->baseQuery()
            ->where('assigned_processor_id', $userId)
            ->where('entry_mode', 'paper')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('received_at')
            ->whereBetween('received_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('id')
            ->get($this->evidenceColumns());

        return $this->metric($sheets, fn ($sheet) => $this->occurredBy($sheet->entered_at, $periodEnd));
    }

    private function sourceIsAvailable(): bool
    {
        $capabilities = $this->sourceCapabilities();

        return $capabilities['table']
            && collect(self::REQUIRED_COLUMNS)->every(
                fn (string $column) => in_array($column, $capabilities['columns'], true)
            );
    }

    private function baseQuery(): Builder
    {
        $query = DB::table(self::TABLE);

        if ($this->hasSourceColumn('deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    private function evidenceColumns(): array
    {
        return self::REQUIRED_COLUMNS;
    }

    private function sourceCapabilities(): array
    {
        if ($this->sourceCapabilities !== null) {
            return $this->sourceCapabilities;
        }

        $tableExists = Schema::hasTable(self::TABLE);

        return $this->sourceCapabilities = [
            'table' => $tableExists,
            'columns' => $tableExists ? Schema::getColumnListing(self::TABLE) : [],
        ];
    }

    private function hasSourceColumn(string $column): bool
    {
        return in_array($column, $this->sourceCapabilities()['columns'], true);
    }

    private function occurredBy(?string $timestamp, CarbonInterface $periodEnd): bool
    {
        return $timestamp !== null && CarbonImmutable::parse($timestamp)->lessThanOrEqualTo($periodEnd);
    }

    private function metric(Collection $sheets, callable $fulfilled): array
    {
        $denominator = $sheets->count();
        $numerator = $sheets->filter($fulfilled)->count();
        $evidence = $sheets
            ->map(fn ($sheet) => (array) $sheet)
            ->sortBy('id')
            ->values()
            ->all();
        $evidenceHash = hash('sha256', json_encode($evidence, JSON_THROW_ON_ERROR));

        if ($denominator === 0) {
            return [
                'state' => PersonnelScoreResult::STATE_NON_APPLICABLE,
                'score' => 0,
                'numerator' => 0,
                'denominator' => 0,
                'coverage' => null,
                'confidence' => null,
                'evidence_hash' => $evidenceHash,
                'messages' => ['Aucune obligation explicite sur la période.'],
            ];
        }

        return [
            'state' => PersonnelScoreResult::STATE_MEASURABLE,
            'score' => (int) round(($numerator / $denominator) * 100),
            'numerator' => $numerator,
            'denominator' => $denominator,
            'coverage' => 1.0,
            'confidence' => round(min(1, $denominator / max(1, (int) config('personnel_scoring.minimum_obligations_for_full_confidence', 5))), 4),
            'evidence_hash' => $evidenceHash,
            'messages' => [],
        ];
    }

    private function insufficientData(string $message): array
    {
        return [
            'state' => PersonnelScoreResult::STATE_INSUFFICIENT_DATA,
            'score' => 0,
            'numerator' => null,
            'denominator' => null,
            'coverage' => null,
            'confidence' => null,
            'evidence_hash' => null,
            'messages' => [$message],
        ];
    }
}
