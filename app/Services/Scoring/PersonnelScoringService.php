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
    ) {
    }

    public function calculate(User $user, string $periodType = 'month', ?CarbonInterface $referenceDate = null): array
    {
        $periodType = $this->normalizePeriodType($periodType);
        [$start, $end] = $this->periodBounds($periodType, $referenceDate ?: now());
        $permissions = $this->effectivePermissions($user);
        $roleName = $this->primaryRole($user);

        $applicable = [];
        $excluded = [];
        foreach (config('personnel_scoring.dimensions', []) as $key => $definition) {
            if ($this->dimensionApplies($definition, $permissions)) {
                $applicable[$key] = $definition;
            } else {
                $excluded[$key] = [
                    'label' => $definition['label'] ?? $key,
                    'permissions' => $definition['permissions'] ?? [],
                ];
            }
        }

        $results = [];
        $weightedScore = 0;
        $totalWeight = 0;

        foreach ($applicable as $dimension => $definition) {
            $result = $this->calculateDimension($dimension, $definition, $user, $start, $end);
            $results[$dimension] = $result->toArray();
            $weightedScore += $result->score * $result->weight;
            $totalWeight += $result->weight;
        }

        $totalScore = $totalWeight > 0 ? (int) round($weightedScore / $totalWeight) : 0;
        $level = $totalWeight > 0 ? $this->levelForScore($totalScore) : 'insufficient_data';

        return [
            'user_id' => $user->id,
            'teacher_id' => $user->teacherProfile?->id,
            'role_name' => $roleName,
            'period_type' => $periodType,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_score' => $totalScore,
            'level' => $level,
            'level_label' => config("personnel_scoring.levels.$level.label", $level),
            'level_class' => config("personnel_scoring.levels.$level.class", 'muted'),
            'applicable_dimensions_count' => count($applicable),
            'excluded_dimensions_count' => count($excluded),
            'permissions' => $permissions,
            'metrics' => collect($results)->map(fn ($result) => $result['metrics'] ?? [])->all(),
            'breakdown' => $results,
            'excluded_dimensions' => $excluded,
            'calculated_at' => now(),
        ];
    }

    public function calculateAndStore(User $user, string $periodType = 'month', ?CarbonInterface $referenceDate = null): ESBTPPersonnelScoreSnapshot
    {
        $score = $this->calculate($user, $periodType, $referenceDate);

        return ESBTPPersonnelScoreSnapshot::updateOrCreate(
            [
                'user_id' => $score['user_id'],
                'role_name' => $score['role_name'],
                'period_type' => $score['period_type'],
                'period_start' => $score['period_start'],
                'period_end' => $score['period_end'],
            ],
            [
                'teacher_id' => $score['teacher_id'],
                'total_score' => $score['total_score'],
                'level' => $score['level'],
                'applicable_dimensions_count' => $score['applicable_dimensions_count'],
                'excluded_dimensions_count' => $score['excluded_dimensions_count'],
                'permissions' => $score['permissions'],
                'metrics' => $score['metrics'],
                'breakdown' => $score['breakdown'],
                'calculated_at' => $score['calculated_at'],
            ]
        );
    }

    public function latestFor(User $user, string $periodType = 'month'): ?ESBTPPersonnelScoreSnapshot
    {
        $periodType = $this->normalizePeriodType($periodType);

        return ESBTPPersonnelScoreSnapshot::where('user_id', $user->id)
            ->where('period_type', $periodType)
            ->latest('period_end')
            ->first();
    }

    public function scoreRows(string $periodType = 'month'): Collection
    {
        $periodType = $this->normalizePeriodType($periodType);

        return ESBTPPersonnelScoreSnapshot::with('user')
            ->where('period_type', $periodType)
            ->latest('period_end')
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

    private function primaryRole(User $user): ?string
    {
        return $user->getRoleNames()->first() ?: 'sans_role';
    }

    public function normalizePeriodType(string $periodType): string
    {
        return array_key_exists($periodType, config('personnel_scoring.periods', []))
            ? $periodType
            : 'month';
    }
}
