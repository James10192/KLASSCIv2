<?php

namespace App\Http\Controllers;

use App\Models\ESBTPPersonnelScoreSnapshot;
use App\Models\User;
use App\Services\Scoring\PersonnelScoringService;
use Illuminate\Http\Request;

class ESBTPPersonnelPerformanceController extends Controller
{
    public function index(Request $request, PersonnelScoringService $scoring)
    {
        $this->authorizeViewAll();

        $period = $request->get('period', 'month');
        $scores = $scoring->scoreRows($period);
        $summary = $this->summary($scores);

        $byRole = $scores
            ->groupBy('role_name')
            ->map(fn ($rows, $role) => [
                'role' => $role ?: 'sans_role',
                'count' => $rows->count(),
                'average' => (int) round($rows->avg('total_score') ?? 0),
            ])
            ->values();

        return view('esbtp.personnel.performance.index', compact('scores', 'summary', 'byRole', 'period'));
    }

    public function data(Request $request, PersonnelScoringService $scoring)
    {
        $this->authorizeViewAll();

        $period = $request->get('period', 'month');
        $scores = $scoring->scoreRows($period);

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
        ]);

        if (! empty($validated['user_id'])) {
            $user = User::with(['roles', 'permissions', 'teacherProfile'])->findOrFail($validated['user_id']);
            $snapshot = $scoring->calculateAndStore($user, $validated['period'] ?? 'month');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Score recalcule pour {$user->name}: {$snapshot->total_score}/100.",
                ]);
            }

            return back()->with('success', "Score recalcule pour {$user->name}: {$snapshot->total_score}/100.");
        }

        $period = $validated['period'] ?? 'month';
        $count = $scoring->recalculateStaff($period);

        if ($request->expectsJson()) {
            $scores = $scoring->scoreRows($period);

            return response()->json([
                'message' => "{$count} score(s) personnel recalcules.",
                'summary' => $this->summary($scores),
                'data' => $this->scoreRowsPayload($scores),
            ]);
        }

        return back()->with('success', "{$count} score(s) personnel recalcules.");
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
        ])->values();
    }
}
