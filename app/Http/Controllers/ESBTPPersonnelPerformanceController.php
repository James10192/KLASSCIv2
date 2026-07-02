<?php

namespace App\Http\Controllers;

use App\Models\ESBTPPersonnelScoreSnapshot;
use App\Models\User;
use App\Services\Scoring\PersonnelScoringService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ESBTPPersonnelPerformanceController extends Controller
{
    public function index(Request $request, PersonnelScoringService $scoring)
    {
        $this->authorizeViewAll();

        $period = $request->get('period', 'month');
        $filters = $this->filters($request);
        $scores = $scoring->scoreRows($period, $filters);
        $summary = $this->summary($scores);
        $filterOptions = $this->filterOptions($scoring);

        $byRole = $scores
            ->groupBy('role_name')
            ->map(fn ($rows, $role) => [
                'role' => $role ?: 'sans_role',
                'count' => $rows->count(),
                'average' => (int) round($rows->avg('total_score') ?? 0),
            ])
            ->values();

        return view('esbtp.personnel.performance.index', compact('scores', 'summary', 'byRole', 'period', 'filters', 'filterOptions'));
    }

    public function data(Request $request, PersonnelScoringService $scoring)
    {
        $this->authorizeViewAll();

        $period = $request->get('period', 'month');
        $filters = $this->filters($request);
        $scores = $scoring->scoreRows($period, $filters);

        return response()->json([
            'summary' => $this->summary($scores),
            'data' => $this->scoreRowsPayload($scores),
        ]);
    }

    public function recalculate(Request $request, PersonnelScoringService $scoring)
    {
        abort_unless($request->user()?->can('performance.recalculate'), 403);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'period' => ['nullable', 'in:month,quarter,year'],
            'role' => ['nullable', 'string'],
            'level' => ['nullable', 'string'],
        ]);

        if (! empty($validated['user_id'])) {
            $user = User::with(['roles', 'permissions', 'teacherProfile'])->findOrFail($validated['user_id']);
            $snapshot = $scoring->calculateAndStore($user, $validated['period'] ?? 'month');
            $scores = $scoring->scoreRows($validated['period'] ?? 'month', $this->filters($request));

            return response()->json([
                'message' => "Score recalcule pour {$user->name}: {$snapshot->total_score}/100.",
                'summary' => $this->summary($scores),
                'data' => $this->scoreRowsPayload($scores),
            ]);
        }

        $period = $validated['period'] ?? 'month';
        $count = $scoring->recalculateStaff(
            periodType: $period,
            role: $validated['role'] ?? null
        );

        $scores = $scoring->scoreRows($period, $this->filters($request));

        return response()->json([
            'message' => "{$count} score(s) personnel recalcules.",
            'summary' => $this->summary($scores),
            'data' => $this->scoreRowsPayload($scores),
        ]);
    }

    public function show(Request $request, User $user, PersonnelScoringService $scoring)
    {
        $this->authorizeViewAll();

        $period = $request->get('period', 'month');
        $snapshot = $scoring->latestFor($user->loadMissing(['roles', 'permissions', 'teacherProfile']), $period);
        $scoreData = $snapshot ?: $scoring->calculate($user, $period);
        $breakdown = collect($scoreData instanceof ESBTPPersonnelScoreSnapshot ? ($scoreData->breakdown ?? []) : ($scoreData['breakdown'] ?? []));
        $excludedDimensions = collect($scoreData instanceof ESBTPPersonnelScoreSnapshot ? [] : ($scoreData['excluded_dimensions'] ?? []));

        return view('esbtp.personnel.performance.show', compact('user', 'period', 'scoreData', 'breakdown', 'excludedDimensions'));
    }

    private function authorizeViewAll(): void
    {
        abort_unless(auth()->user()?->can('performance.view_all'), 403);
    }

    private function summary($scores): array
    {
        return [
            'average' => (int) round($scores->avg('total_score') ?? 0),
            'count' => $scores->count(),
            'watch' => $scores->whereIn('level', ['watch', 'critical'])->count(),
            'excellent' => $scores->where('level', 'excellent')->count(),
            'dimensions' => (int) round($scores->avg('applicable_dimensions_count') ?? 0),
        ];
    }

    private function scoreRowsPayload($scores)
    {
        return $scores->map(fn (ESBTPPersonnelScoreSnapshot $score) => [
            'id' => $score->id,
            'user_id' => $score->user_id,
            'name' => $score->user?->name,
            'role' => $score->role_name,
            'score' => $score->total_score,
            'level' => $score->level,
            'level_label' => config("personnel_scoring.levels.{$score->level}.label", $score->level),
            'dimensions' => $score->applicable_dimensions_count,
            'period' => $score->period_start?->format('d/m/Y').' - '.$score->period_end?->format('d/m/Y'),
            'show_url' => route('esbtp.personnel.performance.show', $score->user_id),
        ])->values();
    }

    private function filters(Request $request): array
    {
        return array_filter([
            'role' => $request->get('role'),
            'user_id' => $request->get('user_id'),
            'level' => $request->get('level'),
        ], fn ($value) => filled($value));
    }

    private function filterOptions(PersonnelScoringService $scoring): array
    {
        $users = $scoring->staffUsersQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user) => [
                $user->id => trim($user->name.' - '.($user->email ?: 'sans email')),
            ])
            ->all();

        return [
            'roles' => Role::query()
                ->whereNotIn('name', ['etudiant', 'parent'])
                ->orderBy('name')
                ->pluck('name', 'name')
                ->all(),
            'users' => $users,
            'levels' => collect(config('personnel_scoring.levels', []))
                ->mapWithKeys(fn ($level, $key) => [$key => $level['label'] ?? $key])
                ->all(),
        ];
    }
}
