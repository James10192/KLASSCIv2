@php
    $performanceScores = collect($performanceScores ?? []);
    $performanceSummary = $performanceSummary ?? ['count' => 0, 'average' => 0, 'top' => null, 'watch_count' => 0];
    $topScore = $performanceSummary['top'] ?? null;
@endphp

<div class="tar-score-panel">
    <div class="tar-score-head">
        <div class="tar-score-icon"><i class="fas fa-chart-line"></i></div>
        <div>
            <div class="tar-score-title">Scoring performance</div>
            <div class="tar-score-sub">Lecture croisee avec les heures et emargements</div>
        </div>
    </div>

    @if($performanceScores->isEmpty())
        <div class="tar-score-empty">
            <i class="fas fa-circle-info"></i>
            <span>Aucun snapshot de score disponible pour les enseignants filtres sur cette periode.</span>
        </div>
    @else
        <div class="tar-score-grid">
            <div class="tar-score-metric">
                <span>{{ (int) $performanceSummary['average'] }}%</span>
                <small>Score moyen</small>
            </div>
            <div class="tar-score-metric">
                <span>{{ (int) $performanceSummary['count'] }}</span>
                <small>Scores disponibles</small>
            </div>
            <div class="tar-score-metric">
                <span>{{ (int) $performanceSummary['watch_count'] }}</span>
                <small>A surveiller</small>
            </div>
        </div>

        @if($topScore)
            <div class="tar-score-top">
                <div>
                    <strong>{{ $topScore->user->name ?? 'Top performance' }}</strong>
                    <span>{{ config("personnel_scoring.levels.{$topScore->level}.label", $topScore->level) }}</span>
                </div>
                <b>{{ (int) $topScore->total_score }}%</b>
            </div>
        @endif
    @endif
</div>
