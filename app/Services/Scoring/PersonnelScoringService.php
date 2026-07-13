<?php

namespace App\Services\Scoring;

use App\Models\ESBTPPersonnelScoreSnapshot;
use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\Scoring\Calculators\AcademicCoordinatorScoringCalculator;
use App\Services\Scoring\Calculators\AdministrativeScoringCalculator;
use App\Services\Scoring\Calculators\CashierScoringCalculator;
use App\Services\Scoring\Calculators\FinanceScoringCalculator;
use App\Services\Scoring\Calculators\TeacherScoringCalculator;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PersonnelScoringService
{
    public function __construct(
        private readonly PermissionRegistry $permissionRegistry,
        private readonly TeacherScoringCalculator $teacherCalculator,
        private readonly FinanceScoringCalculator $financeCalculator,
        private readonly CashierScoringCalculator $cashierCalculator,
        private readonly AcademicCoordinatorScoringCalculator $academicCalculator,
        private readonly AdministrativeScoringCalculator $administrativeCalculator
    ) {}

    public function calculate(User $user, string $periodType = 'month', ?CarbonInterface $referenceDate = null): array
    {
        $periodType = $this->normalizePeriodType($periodType);
        $referenceDate = $referenceDate ?: now();
        [$start, $end] = $this->periodBounds($periodType, $referenceDate);
        $asOf = Carbon::parse($referenceDate)->lessThan($end) ? Carbon::parse($referenceDate) : $end;
        $permissions = $this->effectivePermissions($user);
        [$applicable, $excluded] = $this->classifyDimensions($permissions);
        $scored = $this->scoreDimensions($applicable, $excluded, $user, $start, $asOf);

        return $this->scorePayload($user, $periodType, $start, $end, $permissions, $scored);
    }

    private function scorePayload(
        User $user,
        string $periodType,
        CarbonInterface $start,
        CarbonInterface $end,
        array $permissions,
        array $scored
    ): array {
        $totalScore = $scored['weight'] > 0
            ? (int) round($scored['weighted_score'] / $scored['weight'])
            : 0;
        $level = $this->levelForWeightedScore($totalScore, $scored['weight']);
        $evidence = $this->aggregateEvidence($scored['weighted_results']);

        return [
            'user_id' => $user->id,
            'teacher_id' => $user->teacherProfile?->id,
            'role_name' => $this->primaryRole($user),
            'period_type' => $periodType,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_score' => $totalScore,
            'level' => $level,
            'level_label' => config("personnel_scoring.levels.$level.label", $level),
            'level_class' => config("personnel_scoring.levels.$level.class", 'muted'),
            'applicable_dimensions_count' => count($scored['weighted_results']),
            'excluded_dimensions_count' => count($scored['excluded']),
            'coverage' => $evidence['coverage'],
            'confidence' => $evidence['confidence'],
            'engine_version' => config('personnel_scoring.engine_version'),
            'evidence_hash' => $evidence['evidence_hash'],
            'permissions' => $permissions,
            'metrics' => collect($scored['results'])->map(fn ($result) => $result['metrics'] ?? [])->all(),
            'breakdown' => $scored['results'],
            'excluded_dimensions' => $scored['excluded'],
            'calculated_at' => now(),
        ];
    }

    private function classifyDimensions(array $permissions): array
    {
        $applicable = [];
        $excluded = [];

        foreach (config('personnel_scoring.dimensions', []) as $key => $definition) {
            if ($this->dimensionApplies($definition, $permissions)) {
                $applicable[$key] = $definition;

                continue;
            }

            $excluded[$key] = $this->excludedDimension($key, $definition);
        }

        return [$applicable, $excluded];
    }

    private function scoreDimensions(
        array $applicable,
        array $excluded,
        User $user,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $scored = ['results' => [], 'weighted_results' => [], 'weighted_score' => 0, 'weight' => 0, 'excluded' => $excluded];

        foreach ($applicable as $dimension => $definition) {
            $result = $this->calculateDimension($dimension, $definition, $user, $start, $end);
            $scored['results'][$dimension] = $result->toArray();

            if ($result->state !== PersonnelScoreResult::STATE_MEASURABLE) {
                $scored['excluded'][$dimension] = $this->excludedDimension($dimension, $definition, $result->state);

                continue;
            }

            if ($result->weight === 0) {
                $scored['excluded'][$dimension] = $this->excludedDimension($dimension, $definition, 'zero_weight');

                continue;
            }

            $scored['weighted_results'][$dimension] = $result->toArray();
            $scored['weighted_score'] += $result->score * $result->weight;
            $scored['weight'] += $result->weight;
        }

        return $scored;
    }

    private function excludedDimension(string $key, array $definition, ?string $reason = null): array
    {
        return array_filter([
            'label' => $definition['label'] ?? $key,
            'permissions' => $definition['permissions'] ?? [],
            'reason' => $reason,
        ], fn ($value) => $value !== null);
    }

    public function calculateAndStore(User $user, string $periodType = 'month', ?CarbonInterface $referenceDate = null): ESBTPPersonnelScoreSnapshot
    {
        $score = $this->calculate($user, $periodType, $referenceDate);

        ESBTPPersonnelScoreSnapshot::query()->upsert([[
            'user_id' => $score['user_id'],
            'role_name' => $score['role_name'],
            'period_type' => $score['period_type'],
            'period_start' => $score['period_start'],
            'period_end' => $score['period_end'],
            'teacher_id' => $score['teacher_id'],
            'total_score' => $score['total_score'],
            'level' => $score['level'],
            'applicable_dimensions_count' => $score['applicable_dimensions_count'],
            'excluded_dimensions_count' => $score['excluded_dimensions_count'],
            'coverage' => $score['coverage'],
            'confidence' => $score['confidence'],
            'engine_version' => $score['engine_version'],
            'evidence_hash' => $score['evidence_hash'],
            'permissions' => json_encode($score['permissions'], JSON_THROW_ON_ERROR),
            'metrics' => json_encode($score['metrics'], JSON_THROW_ON_ERROR),
            'breakdown' => json_encode($score['breakdown'], JSON_THROW_ON_ERROR),
            'calculated_at' => $score['calculated_at'],
            'created_at' => now(),
            'updated_at' => now(),
        ]], [
            'user_id',
            'role_name',
            'period_type',
            'period_start',
            'period_end',
        ], [
            'teacher_id',
            'total_score',
            'level',
            'applicable_dimensions_count',
            'excluded_dimensions_count',
            'coverage',
            'confidence',
            'engine_version',
            'evidence_hash',
            'permissions',
            'metrics',
            'breakdown',
            'calculated_at',
            'updated_at',
        ]);

        return ESBTPPersonnelScoreSnapshot::query()->where(
            [
                'user_id' => $score['user_id'],
                'role_name' => $score['role_name'],
                'period_type' => $score['period_type'],
                'period_start' => $score['period_start'],
                'period_end' => $score['period_end'],
            ]
        )->firstOrFail();
    }

    public function latestFor(User $user, string $periodType = 'month'): ?ESBTPPersonnelScoreSnapshot
    {
        $periodType = $this->normalizePeriodType($periodType);

        return ESBTPPersonnelScoreSnapshot::where('user_id', $user->id)
            ->where('period_type', $periodType)
            ->latest('period_end')
            ->first();
    }

    public function scoreRows(string $periodType = 'month', array $filters = []): Collection
    {
        $periodType = $this->normalizePeriodType($periodType);

        $query = ESBTPPersonnelScoreSnapshot::with('user')
            ->where('period_type', $periodType)
            ->latest('period_end');

        if (! empty($filters['role'])) {
            $query->where('role_name', $filters['role']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query
            ->orderByDesc('total_score')
            ->get()
            ->unique('user_id')
            ->values();
    }

    public function recalculateStaff(
        string $periodType = 'month',
        ?CarbonInterface $referenceDate = null,
        bool $includeInactive = false,
        ?string $role = null,
        ?int $userId = null
    ): int {
        $periodType = $this->normalizePeriodType($periodType);
        $referenceDate ??= now();
        $count = 0;

        $this->staffUsersQuery($includeInactive, $role, $userId)
            ->chunkById(100, function ($users) use ($periodType, $referenceDate, &$count) {
                foreach ($users as $user) {
                    $this->calculateAndStore($user, $periodType, $referenceDate);
                    $count++;
                }
            });

        return $count;
    }

    public function staffUsersQuery(bool $includeInactive = false, ?string $role = null, ?int $userId = null): Builder
    {
        $query = User::query()->with(['roles', 'permissions', 'teacherProfile']);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        if ($userId) {
            return $query->whereKey($userId);
        }

        if ($role) {
            return $query->role($role);
        }

        return $query->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['etudiant', 'parent']));
    }

    public function periodBounds(string $periodType, CarbonInterface $referenceDate): array
    {
        $periodType = $this->normalizePeriodType($periodType);
        $date = Carbon::parse($referenceDate);

        return match ($periodType) {
            'year' => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
            'quarter' => [$date->copy()->startOfQuarter(), $date->copy()->endOfQuarter()],
            default => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
        };
    }

    private function calculateDimension(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        return match ($definition['calculator'] ?? 'administrative') {
            'teacher' => $this->teacherCalculator->calculate($dimension, $definition, $user, $start, $end),
            'finance' => $this->financeCalculator->calculate($dimension, $definition, $user, $start, $end),
            'cashier' => $this->cashierCalculator->calculate($dimension, $definition, $user, $start, $end),
            'academic' => $this->academicCalculator->calculate($dimension, $definition, $user, $start, $end),
            default => $this->administrativeCalculator->calculate($dimension, $definition, $user, $start, $end),
        };
    }

    private function effectivePermissions(User $user): array
    {
        return $user->getAllPermissions()
            ->pluck('name')
            ->map(fn (string $name) => $this->permissionRegistry->canonicalize($name))
            ->unique()
            ->values()
            ->all();
    }

    private function dimensionApplies(array $definition, array $permissions): bool
    {
        return count(array_intersect($definition['permissions'] ?? [], $permissions)) > 0;
    }

    private function levelForScore(int $score): string
    {
        foreach (config('personnel_scoring.levels', []) as $key => $level) {
            if ($score >= ($level['min'] ?? 0)) {
                return $key;
            }
        }

        return 'insufficient_data';
    }

    private function levelForWeightedScore(int $score, int $weight): string
    {
        return $weight > 0 ? $this->levelForScore($score) : 'insufficient_data';
    }

    private function primaryRole(User $user): ?string
    {
        return $user->getRoleNames()->first() ?: 'sans_role';
    }

    private function aggregateEvidence(array $weightedResults): array
    {
        $results = collect($weightedResults);
        $evidenceBacked = $results->filter(
            fn (array $result): bool => ($result['coverage'] ?? null) !== null
                && ($result['confidence'] ?? null) !== null,
        );
        $totalWeight = $evidenceBacked->sum('weight');
        $hashes = $results->pluck('evidence_hash')->filter()->sort()->values();

        if ($totalWeight <= 0) {
            return ['coverage' => null, 'confidence' => null, 'evidence_hash' => null];
        }

        $coverage = $evidenceBacked->sum(fn (array $result) => $result['coverage'] * $result['weight']);
        $confidence = $evidenceBacked->sum(fn (array $result) => $result['confidence'] * $result['weight']);

        return [
            'coverage' => round($coverage / $totalWeight, 4),
            'confidence' => round($confidence / $totalWeight, 4),
            'evidence_hash' => $hashes->isEmpty()
                ? null
                : hash('sha256', json_encode($hashes->all(), JSON_THROW_ON_ERROR)),
        ];
    }

    public function normalizePeriodType(string $periodType): string
    {
        return array_key_exists($periodType, config('personnel_scoring.periods', []))
            ? $periodType
            : 'month';
    }
}
