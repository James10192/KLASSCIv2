@extends('layouts.app')

@section('title', 'Performance Personnel - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .perf-page { background:#f4f7fb; min-height:100vh; padding:24px; }
    .perf-wrap { max-width:1280px; margin:0 auto; }
    .perf-header { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
    .perf-title { color:#1e293b; font-weight:800; font-size:1.6rem; margin:0; }
    .perf-sub { color:#64748b; margin:4px 0 0; }
    .perf-filter { display:flex; gap:8px; align-items:center; }
    .perf-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .perf-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:16px; }
    .perf-kpi-value { color:#0453cb; font-weight:800; font-size:1.45rem; }
    .perf-kpi-label { color:#64748b; font-size:.78rem; text-transform:uppercase; font-weight:700; margin-top:4px; }
    .perf-table { width:100%; border-collapse:collapse; }
    .perf-table th { text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:1px solid #e2e8f0; padding:12px; }
    .perf-table td { border-bottom:1px solid #f1f5f9; padding:12px; color:#1e293b; }
    .perf-badge { display:inline-flex; border-radius:999px; padding:4px 10px; font-size:.75rem; font-weight:700; background:#f1f5f9; color:#475569; }
    .perf-badge.excellent { background:#dcfce7; color:#166534; }
    .perf-badge.good { background:#dbeafe; color:#1d4ed8; }
    .perf-badge.watch { background:#fef3c7; color:#92400e; }
    .perf-badge.critical { background:#fee2e2; color:#991b1b; }
</style>
@endsection

@section('content')
<div class="perf-page">
    <div class="perf-wrap">
        <div class="perf-header">
            <div>
                <h1 class="perf-title">Performance personnel</h1>
                <p class="perf-sub">Scores automatiques bases sur les permissions effectives et les actions KLASSCI.</p>
            </div>
            <div class="perf-filter">
                @can('performance.recalculate')
                    <form method="POST" action="{{ route('esbtp.personnel.performance.recalculate') }}">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period }}">
                        <button type="submit" class="btn-acasi primary">Recalculer</button>
                    </form>
                @endcan
                <form method="GET">
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        @foreach(config('personnel_scoring.periods') as $key => $label)
                            <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <div class="perf-kpis">
            <div class="perf-card"><div class="perf-kpi-value">{{ $summary['average'] }}%</div><div class="perf-kpi-label">Score moyen</div></div>
            <div class="perf-card"><div class="perf-kpi-value">{{ $summary['count'] }}</div><div class="perf-kpi-label">Personnels scores</div></div>
            <div class="perf-card"><div class="perf-kpi-value">{{ $summary['excellent'] }}</div><div class="perf-kpi-label">Excellent</div></div>
            <div class="perf-card"><div class="perf-kpi-value">{{ $summary['watch'] }}</div><div class="perf-kpi-label">Points d'attention</div></div>
        </div>

        <div class="perf-card">
            <table class="perf-table">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Role</th>
                        <th>Score</th>
                        <th>Niveau</th>
                        <th>Dimensions</th>
                        <th>Periode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scores as $score)
                        <tr>
                            <td>{{ $score->user->name ?? 'Utilisateur #' . $score->user_id }}</td>
                            <td>{{ $score->role_name ?? '---' }}</td>
                            <td><strong>{{ $score->total_score }}%</strong></td>
                            <td><span class="perf-badge {{ $score->level }}">{{ config("personnel_scoring.levels.{$score->level}.label", $score->level) }}</span></td>
                            <td>{{ $score->applicable_dimensions_count }}</td>
                            <td>{{ $score->period_start?->format('d/m/Y') }} - {{ $score->period_end?->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:#64748b;padding:24px;">
                                Aucun snapshot disponible. Utilisez le bouton de recalcul pour initialiser les scores.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
